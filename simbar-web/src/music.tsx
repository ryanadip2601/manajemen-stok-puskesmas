import { useEffect, useRef, useState } from 'react';
import { remember, remembered } from './effects';
import { SONGS, type Song } from './songs';

// Background music: ten public-domain melodies played on an acoustic guitar that is synthesised in the browser
// (Karplus-Strong plucked strings, a guitar-body resonance and a room reverb). No audio files, no licensing.
// It keeps playing while the app is minimised and stops when the app closes or the person signs out.

const LETTERS: Record<string, number> = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 };
const midiOf = (name: string) => {
  const [, letter, accidental, octave] = name.match(/^([A-G])([#b]?)(\d)$/)!;
  return LETTERS[letter] + (accidental === '#' ? 1 : accidental === 'b' ? -1 : 0) + (Number(octave) + 1) * 12;
};
const QUALITIES: Record<string, number[]> = { '': [0, 4, 7], m: [0, 3, 7], '7': [0, 4, 7, 10], maj7: [0, 4, 7, 11], m7: [0, 3, 7, 10] };
// Guitar-like voicing: the root low in the bass, the other chord tones on the middle strings (G3 and up).
function voicing(symbol: string) {
  const [, letter, accidental, quality] = symbol.match(/^([A-G])([#b]?)(.*)$/)!;
  const root = (LETTERS[letter] + (accidental === '#' ? 1 : accidental === 'b' ? -1 : 0) + 12) % 12;
  const bass = 40 + ((root - 4 + 12) % 12);
  const tones = (QUALITIES[quality] ?? QUALITIES['']).slice(1).map((interval) => {
    let note = 52 + ((root + interval - 4 + 24) % 12);
    if (note < 55) note += 12;
    return note;
  }).sort((a, b) => a - b);
  // tones: three notes for rolling patterns; upper: every chord tone above the bass, for block chords.
  return { bass, tones: [bass + 12, ...tones].slice(0, 3).sort((a, b) => a - b), upper: tones };
}

// Notes are planned ahead; while minimised the browser slows timers, so we plan much further ahead.
const LOOKAHEAD = .3;
const AHEAD_VISIBLE = 5;
const AHEAD_HIDDEN = 80;

type Session = { song: number; fade: GainNode; strings: AudioNode; until: number };

class Guitar {
  playing = false;
  onSong: (index: number) => void = () => {};
  private ctx: AudioContext;
  private master: GainNode;
  private mix: GainNode;
  private reverb: GainNode;
  private element: HTMLAudioElement | null = null;
  private limiter: DynamicsCompressorNode;
  private session: Session | null = null;
  private timer = 0;
  private notes = new Map<number, AudioBuffer>();

  constructor() {
    // iPhones mute web audio with the silent switch unless the page asks for media playback.
    const audioSession = (navigator as Navigator & { audioSession?: { type: string } }).audioSession;
    if (audioSession) audioSession.type = 'playback';
    this.ctx = new AudioContext({ latencyHint: 'playback' });
    this.mix = this.ctx.createGain();
    this.master = this.ctx.createGain();
    this.master.gain.value = 0;
    this.reverb = this.ctx.createGain();
    const room = this.ctx.createConvolver();
    room.buffer = this.room(2.2);
    const wet = this.ctx.createGain();
    wet.gain.value = .3;
    this.reverb.connect(room).connect(wet).connect(this.mix);
    // Level first, then a fast limiter as the very last stage, so chance pile-ups of notes can never pass 1.0.
    const level = this.ctx.createGain();
    level.gain.value = 1.15;
    this.limiter = this.ctx.createDynamicsCompressor();
    this.limiter.threshold.value = -3;
    this.limiter.knee.value = 0;
    this.limiter.ratio.value = 20;
    this.limiter.attack.value = .001;
    this.limiter.release.value = .25;
    this.mix.connect(this.master).connect(level).connect(this.limiter);
    // Playing through an <audio> element makes it real media playback: it keeps going while minimised
    // and shows up in the system media controls. Falls back to the plain speaker output.
    try {
      const stream = this.ctx.createMediaStreamDestination();
      this.limiter.connect(stream);
      this.element = new Audio();
      this.element.srcObject = stream.stream;
    } catch {
      this.limiter.connect(this.ctx.destination);
    }
  }

  // Call straight from a tap or click: browsers only unlock sound inside the gesture itself.
  play(song: number, volume: number) {
    this.ctx.resume().catch(() => {});
    this.element?.play().catch(() => {
      this.element = null;
      this.limiter.connect(this.ctx.destination);
    });
    this.ramp(this.master.gain, volume, 1.2);
    this.playing = true;
    if (!this.session) this.switchTo(song);
    if (!this.timer) this.timer = window.setInterval(this.tick, 500);
    this.tick();
  }

  setSong(song: number) { if (this.playing) { this.switchTo(song); this.tick(); } }
  setVolume(volume: number) { if (this.playing) this.ramp(this.master.gain, volume, .15); }

  stop() {
    this.playing = false;
    window.clearInterval(this.timer);
    this.timer = 0;
    this.ramp(this.master.gain, 0, .8);
    window.setTimeout(() => {
      if (this.playing) return;
      this.session?.fade.disconnect();
      this.session = null;
      this.element?.pause();
      this.ctx.suspend().catch(() => {});
    }, 1000);
  }

  close() {
    window.clearInterval(this.timer);
    this.element?.pause();
    this.ctx.close().catch(() => {});
  }

  private ramp(param: AudioParam, value: number, seconds: number) {
    const now = this.ctx.currentTime;
    param.cancelScheduledValues(now);
    param.setValueAtTime(param.value, now);
    param.linearRampToValueAtTime(value, now + seconds);
  }

  // Cross-fade to another song: notes already planned for the old one fade out with its bus.
  private switchTo(song: number) {
    const old = this.session;
    if (old) {
      this.ramp(old.fade.gain, 0, 1.5);
      window.setTimeout(() => old.fade.disconnect(), 2000);
    }
    const fade = this.ctx.createGain();
    fade.gain.value = 0;
    fade.connect(this.mix);
    // Guitar body: a warm low resonance and a softened top, so the strings sound wooden rather than wiry.
    const body = this.ctx.createBiquadFilter();
    body.type = 'peaking';
    body.frequency.value = 110;
    body.Q.value = 1.1;
    body.gain.value = 4;
    const air = this.ctx.createBiquadFilter();
    air.type = 'peaking';
    air.frequency.value = 230;
    air.Q.value = 1.4;
    air.gain.value = 2.5;
    const soft = this.ctx.createBiquadFilter();
    soft.type = 'lowpass';
    soft.frequency.value = 3600;
    soft.Q.value = .5;
    const softer = this.ctx.createBiquadFilter();
    softer.type = 'lowpass';
    softer.frequency.value = 4800;
    softer.Q.value = .5;
    body.connect(air).connect(soft).connect(softer).connect(fade);
    softer.connect(this.reverb);
    this.session = { song, fade, strings: body, until: this.ctx.currentTime + LOOKAHEAD };
    this.ramp(fade.gain, 1, 1.2);
  }

  private tick = () => {
    const session = this.session;
    if (!this.playing || !session) return;
    const horizon = this.ctx.currentTime + (document.hidden ? AHEAD_HIDDEN : AHEAD_VISIBLE);
    session.until = Math.max(session.until, this.ctx.currentTime + LOOKAHEAD);
    while (session.until < horizon) {
      const index = session.song;
      const startsIn = (session.until - this.ctx.currentTime) * 1000;
      window.setTimeout(() => { if (this.session === session) this.onSong(index); }, Math.max(0, startsIn));
      session.until += this.perform(session, SONGS[index], session.until) + 1.6;
      session.song = (index + 1) % SONGS.length;
    }
  };

  /* ---------- The guitar ---------- */

  // One plucked string (Karplus-Strong): a soft noise burst circulating in a tuned delay loop that loses
  // a little energy and treble on every pass, which is exactly how a real string decays.
  private string(midi: number) {
    const cached = this.notes.get(midi);
    if (cached) return cached;
    const rate = this.ctx.sampleRate;
    const freq = 440 * 2 ** ((midi - 69) / 12);
    const seconds = 3;
    const length = Math.floor(rate * seconds);
    const buffer = this.ctx.createBuffer(1, length, rate);
    const out = buffer.getChannelData(0);
    // Loop length with an all-pass for the fractional part, so every note is in tune.
    const period = rate / freq;
    let delay = Math.floor(period - .5);
    let fraction = period - .5 - delay;
    if (fraction < .1) { delay -= 1; fraction += 1; }
    const allpass = (1 - fraction) / (1 + fraction);
    // Low notes ring longer than high ones.
    const ring = Math.max(1.4, 3.4 - (midi - 40) * .05);
    const loss = Math.pow(10, -3 / (ring * freq));
    // Excitation: noise softened twice (a fingertip rather than a hard pick), plucked about a fifth of the way along.
    const burst = new Float32Array(delay + 1);
    let warm = 0, warmer = 0;
    for (let i = 0; i <= delay; i++) {
      warm += ((Math.random() * 2 - 1) - warm) * .3;
      warmer += (warm - warmer) * .3;
      burst[i] = warmer;
    }
    const position = Math.max(1, Math.round(delay * .2));
    for (let i = delay; i >= position; i--) burst[i] -= burst[i - position] * .85;
    let x1 = 0, y1 = 0;
    for (let i = 0; i < length; i++) {
      if (i <= delay) { out[i] = burst[i]; continue; }
      const x = loss * .5 * (out[i - delay] + out[i - delay - 1]);
      const y = allpass * x + x1 - allpass * y1;
      x1 = x;
      y1 = y;
      out[i] = y;
    }
    // Soften the very first milliseconds and normalise, so every string plays at the same strength.
    let peak = 0;
    const fadeIn = Math.floor(rate * .005);
    for (let i = 0; i < length; i++) {
      if (i < fadeIn) out[i] *= i / fadeIn;
      peak = Math.max(peak, Math.abs(out[i]));
    }
    for (let i = 0; i < length; i++) out[i] /= peak || 1;
    this.notes.set(midi, buffer);
    return buffer;
  }

  private pluck(s: Session, midi: number, at: number, level: number, hold: number) {
    const source = this.ctx.createBufferSource();
    const gain = this.ctx.createGain();
    source.buffer = this.string(midi);
    // A tiny bit of human timing and touch.
    const when = at + (Math.random() - .5) * .012;
    const end = when + Math.min(hold, 2.8);
    gain.gain.setValueAtTime(level * (.9 + Math.random() * .2), when);
    gain.gain.setValueAtTime(level, end - .08);
    gain.gain.linearRampToValueAtTime(0, end);
    source.connect(gain).connect(s.strings);
    source.start(when);
    source.stop(end + .02);
  }

  // Plays one song (repeated as set) from `start`; returns its length in seconds.
  private perform(s: Session, song: Song, start: number) {
    const beat = 60 / song.bpm;
    const tokens = song.melody.trim().split(/\s+/).map((token) => {
      const [name, length] = token.split(':');
      return { midi: name === 'r' ? null : midiOf(name) + (song.shift ?? 0), beats: Number(length) };
    });
    const songBeats = tokens.reduce((sum, t) => sum + t.beats, 0);
    const times = song.repeat ?? 2;
    for (let round = 0; round < times; round++) {
      const offset = start + round * (songBeats * beat + .8);
      let at = 0;
      for (const { midi, beats } of tokens) {
        if (midi !== null) this.pluck(s, midi, offset + at * beat, .5, beats * beat + .9);
        at += beats;
      }
      at = 0;
      for (const [symbol, beats] of song.chords) {
        if (symbol !== '-') this.accompany(s, song, voicing(symbol), offset + at * beat, beats, beat);
        at += beats;
      }
    }
    return times * (songBeats * beat + .8) - .8;
  }

  // The right hand: fingerpicking patterns under the melody.
  private accompany(s: Session, song: Song, { bass, tones, upper }: ReturnType<typeof voicing>, at: number, beats: number, beat: number) {
    const ring = 1.6;
    switch (song.style) {
      case 'triplet':
        for (let b = 0; b < beats; b++) {
          if (b === 0) this.pluck(s, bass, at, .3, beats * beat);
          this.pluck(s, tones[1], at + (b + 1 / 3) * beat, .16, ring);
          this.pluck(s, tones[2], at + (b + 2 / 3) * beat, .16, ring);
        }
        break;
      case 'gymno':
        this.pluck(s, bass, at, .32, beats * beat);
        for (const tone of upper) this.pluck(s, tone, at + beat, .14, (beats - 1) * beat + .5);
        break;
      case 'bass3':
        this.pluck(s, bass, at, .3, 1.2);
        this.pluck(s, bass + 7, at + .5 * beat, .18, 1);
        this.pluck(s, bass + 12, at + beat, .18, 1);
        break;
      default: {
        // Eighth notes: bass on the downbeat (and on beat three in 4/4), then the chord tones rolling up and down.
        const order = [0, 1, 2, 1];
        for (let k = 0; k < beats * 2; k++) {
          const when = at + k * beat / 2;
          if (k === 0 || (song.style === 'arp8' && k % 4 === 0)) this.pluck(s, bass, when, .3, beat * 2);
          else this.pluck(s, tones[order[(k - 1) % 4]], when, .15, ring);
        }
      }
    }
  }

  private room(seconds: number) {
    const rate = this.ctx.sampleRate;
    const length = Math.round(rate * seconds);
    const buffer = this.ctx.createBuffer(2, length, rate);
    for (let channel = 0; channel < 2; channel++) {
      const data = buffer.getChannelData(channel);
      let smooth = 0;
      for (let i = 0; i < length; i++) {
        smooth = smooth * .7 + (Math.random() * 2 - 1) * .3;
        data[i] = smooth * Math.min(1, i / (rate * .02)) * Math.pow(1 - i / length, 3.2);
      }
    }
    return buffer;
  }
}

/* ---------- Hook and menu ---------- */

// `on` is what is playing right now; the remembered choice ("simbar-music") is what the person last chose.
// Signing out stops the music but keeps that choice, and signing in again restores it. Signed out, the music
// never starts by itself: it only plays when someone presses play.
export function useMusic(signedIn: boolean) {
  const [on, setOn] = useState(false);
  const [song, setSongState] = useState(() => { const saved = SONGS.findIndex((s) => s.id === remembered('simbar-music-song')); return saved < 0 ? 0 : saved; });
  const [volume, setVolumeState] = useState(() => { const saved = Number(remembered('simbar-music-volume')); return saved >= 0 && saved <= 1 && remembered('simbar-music-volume') !== null ? saved : .8; });
  const engine = useRef<Guitar | null>(null);
  const latest = useRef({ song, volume });
  latest.current = { song, volume };

  const start = (next = latest.current.song) => {
    try {
      engine.current ??= new Guitar();
      engine.current.onSong = (index) => setSongState(index);
      engine.current.play(next, latest.current.volume);
    } catch { /* no audio support */ }
    setOn(true);
  };
  const play = (next?: number) => { remember('simbar-music', 'on'); start(next); };
  // Stop and remember "off" (the person turned it off).
  const stop = () => {
    remember('simbar-music', 'off');
    engine.current?.stop();
    setOn(false);
  };
  // Stop but keep the remembered choice (signing out).
  const pause = () => {
    engine.current?.stop();
    setOn(false);
  };

  // Signed in with the music left on: start straight away (allowed once the person has interacted, e.g. pressed
  // "Masuk"); browsers that still refuse get it on the next tap or key press.
  useEffect(() => {
    if (!signedIn || remembered('simbar-music') !== 'on' || engine.current?.playing) return;
    if (navigator.userActivation?.hasBeenActive) start();
    // Only if it is still wanted and not already playing (otherwise the tap on "Keluar" would restart it).
    const once = () => { if (remembered('simbar-music') === 'on' && !engine.current?.playing) start(); };
    window.addEventListener('pointerdown', once, { once: true });
    window.addEventListener('keydown', once, { once: true });
    return () => {
      window.removeEventListener('pointerdown', once);
      window.removeEventListener('keydown', once);
    };
  }, [signedIn]);

  // Signing out (or the session ending) silences the music without forgetting the choice.
  const wasSignedIn = useRef(signedIn);
  useEffect(() => {
    if (wasSignedIn.current && !signedIn) pause();
    wasSignedIn.current = signedIn;
  }, [signedIn]);

  // System media controls (lock screen, media keys, the browser's media hub).
  useEffect(() => {
    const media = navigator.mediaSession;
    if (!media) return;
    media.playbackState = on ? 'playing' : 'paused';
    if (on && 'MediaMetadata' in window) {
      media.metadata = new MediaMetadata({ title: SONGS[song].title, artist: 'Gitar akustik · SIMBAR', album: 'UPTD Puskesmas Karang Rejo',
        artwork: [{ src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' }] });
    }
    try {
      media.setActionHandler('pause', () => stop());
      media.setActionHandler('stop', () => stop());
      media.setActionHandler('play', () => play());
      media.setActionHandler('nexttrack', () => chooseSong((latest.current.song + 1) % SONGS.length));
      media.setActionHandler('previoustrack', () => chooseSong((latest.current.song + SONGS.length - 1) % SONGS.length));
    } catch { /* not supported */ }
  }, [on, song]);

  useEffect(() => () => { engine.current?.close(); engine.current = null; }, []);

  const toggle = () => { if (on) stop(); else play(); };
  const chooseSong = (next: number) => {
    remember('simbar-music-song', SONGS[next].id);
    setSongState(next);
    if (on && engine.current?.playing) engine.current.setSong(next);
    else play(next);
  };
  const setVolume = (next: number) => {
    remember('simbar-music-volume', String(next));
    setVolumeState(next);
    engine.current?.setVolume(next);
  };
  return { on, song, volume, toggle, stop, pause, chooseSong, setVolume };
}

export type Music = ReturnType<typeof useMusic>;

export function MusicMenu({ music }: { music: Music }) {
  const [open, setOpen] = useState(false);
  const box = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (!open) return;
    const outside = (event: PointerEvent) => { if (!box.current?.contains(event.target as Node)) setOpen(false); };
    const escape = (event: KeyboardEvent) => { if (event.key === 'Escape') setOpen(false); };
    document.addEventListener('pointerdown', outside);
    document.addEventListener('keydown', escape);
    return () => {
      document.removeEventListener('pointerdown', outside);
      document.removeEventListener('keydown', escape);
    };
  }, [open]);
  return <div className="season-menu music-menu" ref={box}>
    <button className={'icon-button music-toggle' + (music.on ? ' on' : '')} aria-haspopup="dialog" aria-expanded={open}
      aria-label="Musik latar" title={music.on ? 'Gitar akustik: ' + SONGS[music.song].title : 'Musik latar'} onClick={() => setOpen(!open)}>
      {music.on ? <span className="eq" aria-hidden="true"><b /><b /><b /></span> : <i className="fas fa-guitar" aria-hidden="true" />}
    </button>
    {open && <div className="season-list music-panel" role="dialog" aria-label="Musik latar">
      <p className="music-title"><i className="fas fa-guitar" aria-hidden="true" /> Gitar akustik</p>
      <button className={music.on ? 'secondary music-play' : 'primary music-play'} aria-pressed={music.on} onClick={music.toggle}>
        <i className={music.on ? 'fas fa-stop' : 'fas fa-play'} aria-hidden="true" /> {music.on ? 'Hentikan musik' : 'Putar musik'}
      </button>
      <div role="radiogroup" aria-label="Pilih lagu">
        {SONGS.map((item, index) => <button key={item.id} role="radio" aria-checked={music.song === index} onClick={() => music.chooseSong(index)}>
          <span className="song-number" aria-hidden="true">{music.on && music.song === index ? <span className="eq"><b /><b /><b /></span> : index + 1}</span>
          <span>{item.title}<small>{item.note}</small></span>
          {music.song === index && <i className="fas fa-check season-check" aria-hidden="true" />}
        </button>)}
      </div>
      <label className="music-volume">
        <i className="fas fa-volume-low" aria-hidden="true" />
        <span className="sr-only">Volume</span>
        <input type="range" min="0" max="100" step="5" value={Math.round(music.volume * 100)} aria-label="Volume"
          onChange={(event) => music.setVolume(Number(event.target.value) / 100)} />
        <i className="fas fa-volume-high" aria-hidden="true" />
      </label>
      <p className="music-hint">Diputar berurutan. Tetap berbunyi saat aplikasi diminimize; berhenti saat aplikasi ditutup atau Anda keluar.</p>
    </div>}
  </div>;
}
