import { useEffect, useRef, useState } from 'react';
import { remember, remembered, type Season } from './effects';

// Background music, generated in the browser (no audio files, no licensing).
// Several relaxing sounds; it keeps playing while the app is minimised and stops when the app closes or the person signs out.

export type Sound = 'musim' | 'piano' | 'kotak' | 'gamelan' | 'meditasi' | 'ombak' | 'hujan';
export const soundNames: Record<Sound, string> = {
  musim: 'Ikuti musim', piano: 'Piano tenang', kotak: 'Kotak musik', gamelan: 'Gamelan relaksasi',
  meditasi: 'Meditasi', ombak: 'Ombak laut', hujan: 'Hujan tenang',
};
const soundNotes: Record<Sound, string> = {
  musim: 'Nada berganti mengikuti efek musim', piano: 'Piano dan akor lembut', kotak: 'Denting kotak musik',
  gamelan: 'Saron dan gong, laras slendro', meditasi: 'Dengung dan mangkuk Tibet', ombak: 'Deburan ombak pelan', hujan: 'Rintik hujan yang lembut',
};
const soundIcons: Record<Sound, string> = {
  musim: 'fas fa-wand-magic-sparkles', piano: 'fas fa-music', kotak: 'fas fa-gift', gamelan: 'fas fa-bell',
  meditasi: 'fas fa-spa', ombak: 'fas fa-water', hujan: 'fas fa-cloud-rain',
};

type Mood = { root: number; scale: number[]; chords: number[][]; cutoff: number; gap: [number, number]; bell: boolean };
// Roots sit in the range small phone and laptop speakers can actually play.
const MOODS: Record<Season, Mood> = {
  hujan: { root: 220, scale: [0, 3, 5, 7, 10, 12, 15], chords: [[0, 3, 7], [-4, 0, 3], [-2, 2, 5], [-5, -1, 2]], cutoff: 1500, gap: [3200, 4600], bell: false },
  pancaroba: { root: 246.94, scale: [0, 2, 3, 5, 7, 9, 10, 12], chords: [[0, 3, 7], [5, 9, 12], [3, 7, 10], [-2, 2, 5]], cutoff: 1700, gap: [2800, 4200], bell: false },
  kemarau: { root: 261.63, scale: [0, 2, 4, 7, 9, 12, 14], chords: [[0, 4, 7], [5, 9, 12], [-3, 0, 4], [7, 11, 14]], cutoff: 1900, gap: [2600, 3800], bell: false },
  semi: { root: 293.66, scale: [0, 2, 4, 7, 9, 12, 14, 16], chords: [[0, 4, 7], [-3, 0, 4], [5, 9, 12], [7, 11, 14]], cutoff: 2300, gap: [2400, 3600], bell: true },
  panas: { root: 329.63, scale: [0, 2, 4, 6, 7, 9, 11, 12], chords: [[0, 4, 7, 11], [2, 6, 9], [7, 11, 14], [4, 7, 11]], cutoff: 2100, gap: [2400, 3400], bell: false },
  gugur: { root: 196, scale: [0, 2, 3, 7, 8, 10, 12], chords: [[0, 3, 7], [8, 12, 15], [5, 8, 12], [7, 10, 14]], cutoff: 1400, gap: [3000, 4400], bell: false },
  salju: { root: 392, scale: [0, 2, 4, 6, 7, 11, 12, 14], chords: [[0, 4, 7, 11], [2, 6, 9], [-1, 2, 7], [4, 7, 11]], cutoff: 2600, gap: [2800, 4400], bell: true },
};
const PIANO: Mood = { root: 261.63, scale: [0, 2, 4, 7, 9, 12], chords: [[0, 4, 7], [-3, 0, 4], [5, 9, 12], [-5, -1, 2]], cutoff: 1700, gap: [3600, 5200], bell: false };
// Evens out loudness between sounds (the quieter, sparser ones get a lift; the final limiter still guards peaks).
const SOUND_LEVEL: Record<Sound, number> = { musim: 1, piano: 1, kotak: 1.6, gamelan: 1.8, meditasi: 2.3, ombak: 1.1, hujan: 1 };
const SLENDRO = [1, 1.1487, 1.3195, 1.5157, 1.7411, 2];
const GENDING = [[2, 1, 2, 3, 2, 1, 5, 0], [3, 5, 3, 2, 1, 2, 1, 5], [5, 3, 2, 1, 3, 2, 1, 0], [1, 2, 3, 5, 3, 2, 1, 2]];
const hz = (root: number, semitones: number) => root * 2 ** (semitones / 12);
const pick = <T,>(list: T[]) => list[Math.floor(Math.random() * list.length)];
// Notes are scheduled ahead of time; while the app is minimised the browser slows timers down, so we plan much further ahead.
const LOOKAHEAD = .3;
const AHEAD_VISIBLE = 5;
const AHEAD_HIDDEN = 80;

type Session = { sound: Sound; fade: GainNode; voices: BiquadFilterNode; dry: GainNode; until: number; bar: number; sources: AudioScheduledSourceNode[]; swell?: GainNode };

class Engine {
  playing = false;
  private ctx: AudioContext;
  private master: GainNode;
  private mix: GainNode;
  private reverb: GainNode;
  private element: HTMLAudioElement | null = null;
  private limiter: DynamicsCompressorNode;
  private session: Session | null = null;
  private timer = 0;
  private season: Season = 'hujan';
  private volume = .8;
  private buffers = new Map<string, AudioBuffer>();

  constructor() {
    // iPhones mute web audio with the silent switch unless the page asks for media playback.
    const session = (navigator as Navigator & { audioSession?: { type: string } }).audioSession;
    if (session) session.type = 'playback';
    this.ctx = new AudioContext({ latencyHint: 'playback' });
    this.mix = this.ctx.createGain();
    this.master = this.ctx.createGain();
    this.master.gain.value = 0;
    // Shared room reverb for the melodic voices.
    this.reverb = this.ctx.createGain();
    const room = this.ctx.createConvolver();
    room.buffer = this.room(2.6);
    const wet = this.ctx.createGain();
    wet.gain.value = .4;
    this.reverb.connect(room).connect(wet).connect(this.mix);
    // Level first, then a fast limiter as the very last stage, so chance pile-ups of notes can never pass 1.0.
    const level = this.ctx.createGain();
    level.gain.value = 1.7;
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
  play(sound: Sound, season: Season, volume: number) {
    this.season = season;
    this.volume = volume;
    this.ctx.resume().catch(() => {});
    this.element?.play().catch(() => {
      // The element was refused; send the sound straight to the speakers instead.
      this.element = null;
      this.limiter.connect(this.ctx.destination);
    });
    this.ramp(this.master.gain, volume, 1.5);
    this.playing = true;
    if (this.session?.sound !== sound) this.switchTo(sound);
    if (!this.timer) this.timer = window.setInterval(this.tick, 500);
    this.tick();
  }

  setSound(sound: Sound) { if (this.playing && this.session?.sound !== sound) { this.switchTo(sound); this.tick(); } }
  setSeason(season: Season) { this.season = season; }
  setVolume(volume: number) { this.volume = volume; if (this.playing) this.ramp(this.master.gain, volume, .15); }

  stop() {
    this.playing = false;
    window.clearInterval(this.timer);
    this.timer = 0;
    this.ramp(this.master.gain, 0, .8);
    window.setTimeout(() => {
      if (this.playing) return;
      this.end(this.session);
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

  // Cross-fade to a new sound: the old one fades out over two seconds while the new one fades in.
  private switchTo(sound: Sound) {
    const old = this.session;
    if (old) {
      this.ramp(old.fade.gain, 0, 2);
      window.setTimeout(() => this.end(old), 2500);
    }
    const fade = this.ctx.createGain();
    fade.gain.value = 0;
    fade.connect(this.mix);
    const voices = this.ctx.createBiquadFilter();
    voices.type = 'lowpass';
    voices.Q.value = .5;
    voices.frequency.value = 1800;
    voices.connect(fade);
    voices.connect(this.reverb);
    const dry = this.ctx.createGain();
    dry.connect(fade);
    this.session = { sound, fade, voices, dry, until: this.ctx.currentTime + LOOKAHEAD, bar: 0, sources: [] };
    this.ramp(fade.gain, SOUND_LEVEL[sound], 2);
    if (sound === 'ombak' || sound === 'hujan') this.startBed(this.session);
  }

  private end(session: Session | null) {
    if (!session) return;
    for (const source of session.sources) { try { source.stop(); } catch { /* already stopped */ } }
    session.fade.disconnect();
  }

  private tick = () => {
    const session = this.session;
    if (!this.playing || !session) return;
    const horizon = this.ctx.currentTime + (document.hidden ? AHEAD_HIDDEN : AHEAD_VISIBLE);
    session.until = Math.max(session.until, this.ctx.currentTime + LOOKAHEAD);
    while (session.until < horizon) {
      session.until += this.bar(session, session.until);
      session.bar++;
    }
  };

  /* ---------- Building blocks ---------- */

  private voice(dest: AudioNode, freq: number, level: number, start: number, attack: number, end: number, release: 'linear' | 'exp') {
    const osc = this.ctx.createOscillator();
    const gain = this.ctx.createGain();
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0, start);
    gain.gain.linearRampToValueAtTime(level, start + attack);
    if (release === 'exp') gain.gain.setTargetAtTime(0, start + attack, (end - start - attack) / 5);
    else gain.gain.linearRampToValueAtTime(0, end);
    osc.connect(gain).connect(dest);
    osc.start(start);
    osc.stop(end + .05);
  }

  private pad(s: Session, freqs: number[], start: number, length: number, level = .1) {
    for (const freq of freqs) {
      this.voice(s.voices, freq, level, start, 1.8, start + length, 'linear');
      this.voice(s.voices, freq * 2, level * .24, start, 2.2, start + length, 'linear');
    }
  }

  private pluck(s: Session, freq: number, start: number, bell: boolean, level = .14) {
    const tail = bell ? 3.4 : 2.6;
    this.voice(s.voices, freq, level, start, .03, start + tail, 'exp');
    this.voice(s.voices, freq * 2, level * .25, start, .03, start + tail * .6, 'exp');
    if (bell) this.voice(s.voices, freq * 3, level * .08, start, .03, start + tail * .4, 'exp');
  }

  // Decaying stereo room response for the reverb; smooth because it is filtered and faded in.
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

  // Seamless looping noise for the ocean and rain beds: deep (brown) or soft (pink), with the loop ends joined.
  private noise(kind: 'brown' | 'pink') {
    const cached = this.buffers.get(kind);
    if (cached) return cached;
    const rate = this.ctx.sampleRate;
    const length = rate * 8;
    const buffer = this.ctx.createBuffer(1, length, rate);
    const data = buffer.getChannelData(0);
    let b0 = 0, b1 = 0, b2 = 0, last = 0;
    for (let i = 0; i < length; i++) {
      const white = Math.random() * 2 - 1;
      if (kind === 'brown') {
        last = (last + .02 * white) / 1.02;
        data[i] = last;
      } else {
        b0 = .99765 * b0 + white * .099046;
        b1 = .963 * b1 + white * .2965164;
        b2 = .57 * b2 + white * 1.0526913;
        data[i] = b0 + b1 + b2 + white * .1848;
      }
    }
    let mean = 0;
    for (let i = 0; i < length; i++) mean += data[i] / length;
    const drift = data[length - 1] - data[0];
    let peak = 0;
    for (let i = 0; i < length; i++) {
      data[i] -= mean + drift * (i / (length - 1));
      peak = Math.max(peak, Math.abs(data[i]));
    }
    for (let i = 0; i < length; i++) data[i] /= peak;
    this.buffers.set(kind, buffer);
    return buffer;
  }

  private lowpass(frequency: number) {
    const filter = this.ctx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.value = frequency;
    filter.Q.value = .5;
    return filter;
  }

  private startBed(s: Session) {
    const source = this.ctx.createBufferSource();
    source.buffer = this.noise(s.sound === 'ombak' ? 'brown' : 'pink');
    source.loop = true;
    const swell = this.ctx.createGain();
    swell.gain.value = s.sound === 'ombak' ? .1 : .55;
    // Two low-pass stages so nothing hissy reaches the speakers.
    const cutoff = s.sound === 'ombak' ? 450 : 1200;
    let chain: AudioNode = source.connect(this.lowpass(cutoff)).connect(this.lowpass(cutoff));
    if (s.sound === 'hujan') {
      const high = this.ctx.createBiquadFilter();
      high.type = 'highpass';
      high.frequency.value = 180;
      chain = chain.connect(high);
    }
    chain.connect(swell).connect(s.dry);
    source.start();
    s.sources.push(source);
    s.swell = swell;
  }

  /* ---------- One bar of each sound; returns its length in seconds ---------- */

  private bar(s: Session, t: number): number {
    switch (s.sound) {
      case 'kotak': return this.musicBox(s, t);
      case 'gamelan': return this.gamelan(s, t);
      case 'meditasi': return this.meditation(s, t);
      case 'ombak': return this.ocean(s, t);
      case 'hujan': return this.rain(s, t);
      default: return this.chords(s, t, s.sound === 'piano' ? PIANO : MOODS[this.season], s.sound === 'piano' ? 3 : 2);
    }
  }

  private chords(s: Session, t: number, mood: Mood, maxNotes: number, level = .1) {
    const length = (mood.gap[0] + Math.random() * (mood.gap[1] - mood.gap[0])) / 1000;
    s.voices.frequency.setValueAtTime(mood.cutoff, t);
    this.pad(s, mood.chords[s.bar % mood.chords.length].map((note) => hz(mood.root, note)), t, length * 1.2, level);
    const notes = 1 + Math.floor(Math.random() * maxNotes);
    for (let i = 0; i < notes; i++) this.pluck(s, hz(mood.root * 2, pick(mood.scale)), t + .4 + Math.random() * (length - .8), mood.bell);
    return length;
  }

  private musicBox(s: Session, t: number) {
    const scale = [0, 2, 4, 7, 9, 12, 14, 16];
    const step = .42;
    const count = 6;
    s.voices.frequency.setValueAtTime(3200, t);
    let index = Math.floor(Math.random() * 4);
    for (let i = 0; i < count; i++) {
      index = Math.max(0, Math.min(scale.length - 1, index + pick([-2, -1, 1, 1, 2])));
      const freq = hz(523.25, scale[index]);
      const at = t + i * step;
      this.voice(s.voices, freq, .12, at, .01, at + 1.8, 'exp');
      this.voice(s.voices, freq * 2, .03, at, .01, at + 1, 'exp');
      this.voice(s.voices, freq * 3, .012, at, .01, at + .6, 'exp');
    }
    if (s.bar % 2 === 0) this.pad(s, [261.63, 329.63, 392].map((f) => f / 2), t, count * step * 2.2, .035);
    return count * step + .5;
  }

  private gamelan(s: Session, t: number) {
    const beat = .75;
    const tune = 293.66;
    const melody = GENDING[Math.floor(s.bar / 2) % GENDING.length];
    s.voices.frequency.setValueAtTime(2600, t);
    melody.forEach((note, i) => {
      const at = t + i * beat;
      const freq = tune * SLENDRO[note];
      // Saron: bright bronze key with an inharmonic overtone.
      this.voice(s.voices, freq, .13, at, .012, at + 2.2, 'exp');
      this.voice(s.voices, freq * 2.76, .022, at, .012, at + .9, 'exp');
      // Kenong on the half and full cycle.
      if (i === 3 || i === 7) {
        const kenong = tune / 2 * SLENDRO[(note + 2) % 5];
        this.voice(s.voices, kenong, .08, at, .02, at + 3.2, 'exp');
        this.voice(s.voices, kenong * 2, .02, at, .02, at + 2, 'exp');
      }
    });
    // Gong ageng closes every second cycle: a deep beating hum.
    if (s.bar % 2 === 1) {
      const at = t + 7 * beat;
      for (const [freq, level] of [[65.4, .2], [66.2, .18], [130.8, .07], [196.2, .025]] as const) this.voice(s.voices, freq, level, at, .05, at + 9, 'exp');
    }
    return melody.length * beat;
  }

  private meditation(s: Session, t: number) {
    const length = 9 + Math.random() * 3;
    s.voices.frequency.setValueAtTime(3000, t);
    // Drone: two close tones that slowly beat, plus a fifth.
    for (const [freq, level] of [[110, .06], [110.55, .05], [164.8, .035]] as const) this.voice(s.voices, freq, level, t, 3, t + length + 3, 'linear');
    // Singing bowl: struck once per bar, each partial slightly detuned so it shimmers as it fades.
    const bowl = pick([196, 220, 261.63]);
    const at = t + .5 + Math.random() * 1.5;
    for (const [ratio, level] of [[1, .14], [2.71, .05], [5.15, .014]] as const) {
      this.voice(s.voices, bowl * ratio, level, at, .02, at + 12, 'exp');
      this.voice(s.voices, bowl * ratio * 1.004, level * .7, at, .02, at + 12, 'exp');
    }
    return length;
  }

  private ocean(s: Session, t: number) {
    const length = 8 + Math.random() * 3;
    const crest = .5 + Math.random() * .25;
    s.swell!.gain.setValueAtTime(.1, t);
    s.swell!.gain.linearRampToValueAtTime(crest, t + length * .42);
    s.swell!.gain.linearRampToValueAtTime(.1, t + length);
    if (s.bar % 2 === 0) this.pad(s, PIANO.chords[(s.bar / 2) % 4].map((note) => hz(PIANO.root / 2, note)), t, length * 2.1, .05);
    return length;
  }

  private rain(s: Session, t: number) {
    const length = 6;
    s.swell!.gain.setValueAtTime(.5, t);
    s.swell!.gain.linearRampToValueAtTime(.42 + Math.random() * .2, t + length);
    // A few soft drops on leaves.
    const drops = 3 + Math.floor(Math.random() * 4);
    for (let i = 0; i < drops; i++) {
      const at = t + Math.random() * length;
      this.voice(s.voices, 1200 + Math.random() * 900, .025, at, .006, at + .3, 'exp');
    }
    if (s.bar % 2 === 0) this.pad(s, MOODS.hujan.chords[(s.bar / 2) % 4].map((note) => hz(MOODS.hujan.root, note)), t, length * 2.2, .045);
    return length;
  }
}

/* ---------- Hook and menu ---------- */

const SOUNDS = Object.keys(soundNames) as Sound[];

export function useMusic(season: Season) {
  const [on, setOn] = useState(() => remembered('simbar-music') === 'on');
  const [sound, setSoundState] = useState<Sound>(() => { const saved = remembered('simbar-music-sound'); return SOUNDS.find((s) => s === saved) ?? 'musim'; });
  const [volume, setVolumeState] = useState(() => { const saved = Number(remembered('simbar-music-volume')); return saved >= 0 && saved <= 1 && remembered('simbar-music-volume') !== null ? saved : .8; });
  const engine = useRef<Engine | null>(null);
  const latest = useRef({ season, sound, volume });
  latest.current = { season, sound, volume };

  const start = (next = latest.current.sound) => {
    try { (engine.current ??= new Engine()).play(next, latest.current.season, latest.current.volume); } catch { /* no audio support */ }
  };
  const stop = () => {
    engine.current?.stop();
    remember('simbar-music', 'off');
    setOn(false);
  };

  // Music left on last time waits for the first tap or key press, which browsers count as permission.
  useEffect(() => {
    if (!on || engine.current?.playing) return;
    const once = () => start();
    window.addEventListener('pointerdown', once, { once: true });
    window.addEventListener('keydown', once, { once: true });
    return () => {
      window.removeEventListener('pointerdown', once);
      window.removeEventListener('keydown', once);
    };
  }, [on]);

  useEffect(() => { engine.current?.setSeason(season); }, [season]);

  // System media controls (lock screen, media keys, the browser's media hub).
  useEffect(() => {
    const media = navigator.mediaSession;
    if (!media) return;
    media.playbackState = on ? 'playing' : 'paused';
    if (on && 'MediaMetadata' in window) {
      media.metadata = new MediaMetadata({ title: soundNames[sound], artist: 'SIMBAR · Musik latar', album: 'UPTD Puskesmas Karang Rejo',
        artwork: [{ src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' }] });
    }
    try {
      media.setActionHandler('pause', () => stop());
      media.setActionHandler('stop', () => stop());
      media.setActionHandler('play', () => { start(); remember('simbar-music', 'on'); setOn(true); });
    } catch { /* not supported */ }
  }, [on, sound]);

  useEffect(() => () => { engine.current?.close(); engine.current = null; }, []);

  const toggle = () => {
    if (on) { stop(); return; }
    start();
    remember('simbar-music', 'on');
    setOn(true);
  };
  const setSound = (next: Sound) => {
    remember('simbar-music-sound', next);
    setSoundState(next);
    if (on && engine.current?.playing) engine.current.setSound(next);
    else { start(next); remember('simbar-music', 'on'); setOn(true); }
  };
  const setVolume = (next: number) => {
    remember('simbar-music-volume', String(next));
    setVolumeState(next);
    engine.current?.setVolume(next);
  };
  return { on, sound, volume, toggle, stop, setSound, setVolume };
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
      aria-label="Musik latar" title={music.on ? 'Musik latar: ' + soundNames[music.sound] : 'Musik latar'} onClick={() => setOpen(!open)}>
      {music.on ? <span className="eq" aria-hidden="true"><b /><b /><b /></span> : <i className="fas fa-music" aria-hidden="true" />}
    </button>
    {open && <div className="season-list music-panel" role="dialog" aria-label="Musik latar">
      <button className={music.on ? 'secondary music-play' : 'primary music-play'} aria-pressed={music.on} onClick={music.toggle}>
        <i className={music.on ? 'fas fa-stop' : 'fas fa-play'} aria-hidden="true" /> {music.on ? 'Hentikan musik' : 'Putar musik'}
      </button>
      <div role="radiogroup" aria-label="Pilih suara">
        {SOUNDS.map((key) => <button key={key} role="radio" aria-checked={music.sound === key} onClick={() => music.setSound(key)}>
          <i className={soundIcons[key] + ' sound-' + key} aria-hidden="true" />
          <span>{soundNames[key]}<small>{soundNotes[key]}</small></span>
          {music.sound === key && <i className="fas fa-check season-check" aria-hidden="true" />}
        </button>)}
      </div>
      <label className="music-volume">
        <i className="fas fa-volume-low" aria-hidden="true" />
        <span className="sr-only">Volume</span>
        <input type="range" min="0" max="100" step="5" value={Math.round(music.volume * 100)} aria-label="Volume"
          onChange={(event) => music.setVolume(Number(event.target.value) / 100)} />
        <i className="fas fa-volume-high" aria-hidden="true" />
      </label>
      <p className="music-hint">Tetap berbunyi saat aplikasi diminimize. Berhenti saat aplikasi ditutup atau Anda keluar.</p>
    </div>}
  </div>;
}
