import { useEffect, useRef, useState } from 'react';

// Ambience: seasonal background particles, generated background music and a soft cursor follower.
// Everything that moves stays off for people who ask their device for reduced motion.

export function remembered(key: string) {
  try { return window.localStorage.getItem(key); } catch { return null; }
}
export function remember(key: string, value: string) {
  try { window.localStorage.setItem(key, value); } catch { /* private mode */ }
}
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Seasons ---------- */

export type Season = 'hujan' | 'pancaroba' | 'kemarau' | 'semi' | 'panas' | 'gugur' | 'salju';
export type SeasonChoice = 'auto' | Season | 'off';
export const seasonNames: Record<SeasonChoice, string> = {
  auto: 'Otomatis', hujan: 'Musim hujan', pancaroba: 'Pancaroba', kemarau: 'Musim kemarau', semi: 'Musim semi',
  panas: 'Musim panas', gugur: 'Musim gugur', salju: 'Musim salju', off: 'Tanpa efek',
};
const seasonIcons: Record<SeasonChoice, string> = {
  auto: 'fas fa-wand-magic-sparkles', hujan: 'fas fa-cloud-rain', pancaroba: 'fas fa-wind', kemarau: 'fas fa-leaf',
  semi: 'fas fa-seedling', panas: 'fas fa-temperature-high', gugur: 'fab fa-canadian-maple-leaf', salju: 'fas fa-snowflake', off: 'fas fa-ban',
};

// The Indonesian calendar: hujan Nov–Feb, pancaroba Mar–Apr and Sep–Oct, kemarau May–Aug; December gets snow for the year-end mood.
export function seasonForDate(date = new Date()): Season {
  const month = date.getMonth();
  if (month === 11) return 'salju';
  if (month <= 1 || month === 10) return 'hujan';
  if (month <= 3 || month === 8 || month === 9) return 'pancaroba';
  return 'kemarau';
}

export function useSeason() {
  const [choice, setChoice] = useState<SeasonChoice>(() => {
    const saved = remembered('simbar-season');
    return saved && saved in seasonNames ? saved as SeasonChoice : 'auto';
  });
  const season: Season | null = choice === 'off' ? null : choice === 'auto' ? seasonForDate() : choice;
  useEffect(() => {
    remember('simbar-season', choice);
    if (season) document.documentElement.dataset.season = season;
    else delete document.documentElement.dataset.season;
  }, [choice, season]);
  return { choice, setChoice, season };
}

type Kind = 'rain' | 'snow' | 'leaf' | 'petal' | 'spark';
type Particle = { kind: Kind; x: number; y: number; vx: number; vy: number; size: number; rot: number; spin: number; phase: number; alpha: number; color: string };
const PALETTES: Partial<Record<Season, string[]>> = {
  kemarau: ['#d97706', '#b45309', '#ca8a04', '#a16207', '#92400e', '#84cc16'],
  pancaroba: ['#65a30d', '#84cc16', '#ca8a04', '#a16207'],
  gugur: ['#dc2626', '#ea580c', '#b91c1c', '#c2410c', '#f59e0b', '#9a3412'],
  semi: ['#f9a8d4', '#fbcfe8', '#f472b6', '#fda4af', '#fecdd3'],
};
// Which particles each season uses (repeats weight the mix) and how many, on a phone and on a larger screen.
const SEASON_PARTICLES: Record<Season, { kinds: Kind[]; phone: number; wide: number }> = {
  hujan: { kinds: ['rain'], phone: 40, wide: 85 },
  pancaroba: { kinds: ['rain', 'rain', 'leaf'], phone: 26, wide: 55 },
  kemarau: { kinds: ['leaf'], phone: 10, wide: 20 },
  semi: { kinds: ['petal'], phone: 16, wide: 34 },
  panas: { kinds: ['spark'], phone: 22, wide: 45 },
  gugur: { kinds: ['leaf'], phone: 16, wide: 34 },
  salju: { kinds: ['snow'], phone: 40, wide: 85 },
};

export function SeasonCanvas({ season }: { season: Season | null }) {
  const ref = useRef<HTMLCanvasElement>(null);
  useEffect(() => {
    const canvas = ref.current;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx || !season || reducedMotion()) return;
    let width = 0, height = 0;
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    const resize = () => {
      width = window.innerWidth;
      height = window.innerHeight;
      canvas.width = Math.round(width * ratio);
      canvas.height = Math.round(height * ratio);
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    };
    resize();

    const plan = SEASON_PARTICLES[season];
    const palette = PALETTES[season] ?? PALETTES.kemarau!;
    const windy = season === 'pancaroba';
    const spawn = (kind: Kind, anywhere: boolean): Particle => {
      const r = Math.random;
      const p = { kind, x: r() * width, y: anywhere ? r() * height : -24, rot: r() * Math.PI * 2, phase: r() * Math.PI * 2,
        color: palette[Math.floor(r() * palette.length)], spin: 0 };
      switch (kind) {
        case 'rain': return windy ? { ...p, vx: 3.5, vy: 7 + r() * 4, size: 8 + r() * 6, alpha: .2 + r() * .2 }
          : { ...p, vx: 1.4, vy: 9 + r() * 6, size: 10 + r() * 12, alpha: .25 + r() * .3 };
        case 'snow': return { ...p, vx: -.2 + r() * .4, vy: .5 + r() * 1.1, size: 1.2 + r() * 2.6, alpha: .55 + r() * .4 };
        case 'petal': return { ...p, vx: .3 + r() * .8, vy: .6 + r() * .7, size: 4 + r() * 3, spin: (r() - .5) * .06, alpha: .65 + r() * .3 };
        case 'spark': return { ...p, y: anywhere ? r() * height : height + 10, vx: (r() - .5) * .3, vy: -(.2 + r() * .45), size: 1 + r() * 1.8, alpha: .5 + r() * .5 };
        default: return windy
          ? { ...p, x: anywhere ? p.x : -20, y: anywhere ? p.y : r() * height * .7, vx: 2 + r() * 2, vy: .6 + r() * .6, size: 6 + r() * 5, spin: (r() - .5) * .12, alpha: .6 + r() * .3 }
          : { ...p, vx: -.3 + r() * .6, vy: .5 + r() * .7, size: (season === 'gugur' ? 8 : 6) + r() * 7, spin: (r() - .5) * .05, alpha: .55 + r() * .35 };
      }
    };
    const count = width < 700 ? plan.phone : plan.wide;
    const particles = Array.from({ length: count }, (_, i) => spawn(plan.kinds[i % plan.kinds.length], true));
    const pointer = { x: -1e4, y: -1e4 };
    const sway: Record<Kind, number> = { rain: 0, snow: .45, leaf: windy ? 1.5 : 1, petal: 1.2, spark: .3 };

    let frame = 0;
    let last = performance.now();
    const draw = (now: number) => {
      const step = Math.min((now - last) / 16.67, 3);
      last = now;
      ctx.clearRect(0, 0, width, height);
      const dark = document.documentElement.dataset.theme === 'dark';
      if (season === 'panas') {
        // A slowly breathing sun glow in the top right corner.
        const glow = ctx.createRadialGradient(width * .88, -height * .05, 0, width * .88, -height * .05, Math.max(width, height) * .65);
        const strength = .16 + Math.sin(now / 2200) * .04;
        glow.addColorStop(0, dark ? `rgba(251, 146, 60, ${strength})` : `rgba(251, 191, 36, ${strength + .06})`);
        glow.addColorStop(1, 'rgba(251, 191, 36, 0)');
        ctx.fillStyle = glow;
        ctx.fillRect(0, 0, width, height);
      }
      for (const p of particles) {
        // Particles drift away from the cursor or finger.
        const dx = p.x - pointer.x, dy = p.y - pointer.y, distance = Math.hypot(dx, dy);
        if (distance < 120 && distance > 1) {
          const push = (1 - distance / 120) * (p.kind === 'rain' ? .8 : 1.8) * step;
          p.x += (dx / distance) * push;
          p.y += (dy / distance) * push * .4;
        }
        p.phase += .02 * step;
        p.x += (p.vx + Math.sin(p.phase) * sway[p.kind]) * step;
        p.y += p.vy * step;
        p.rot += p.spin * step;
        if (p.y > height + 24 || p.y < -30 || p.x < -40 || p.x > width + 40) Object.assign(p, spawn(p.kind, false));

        ctx.globalAlpha = p.alpha;
        if (p.kind === 'rain') {
          ctx.strokeStyle = dark ? '#93c5fd' : '#3b82f6';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p.x - p.vx * 1.6, p.y - p.size);
          ctx.stroke();
        } else if (p.kind === 'snow') {
          ctx.fillStyle = '#ffffff';
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
          ctx.fill();
          if (!dark) {
            ctx.strokeStyle = 'rgba(100, 116, 139, .35)';
            ctx.lineWidth = .6;
            ctx.stroke();
          }
        } else if (p.kind === 'spark') {
          ctx.globalAlpha = p.alpha * (.45 + .55 * Math.abs(Math.sin(p.phase * 3)));
          ctx.fillStyle = dark ? '#fde68a' : '#f59e0b';
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size * 2.4, 0, Math.PI * 2);
          ctx.globalAlpha *= .25;
          ctx.fill();
          ctx.globalAlpha *= 4;
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
          ctx.fill();
        } else {
          ctx.save();
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rot);
          ctx.scale(1, .45 + .55 * Math.abs(Math.sin(p.phase)));
          ctx.fillStyle = p.color;
          ctx.beginPath();
          if (p.kind === 'petal') {
            ctx.moveTo(0, -p.size);
            ctx.quadraticCurveTo(p.size, -p.size * .2, 0, p.size);
            ctx.quadraticCurveTo(-p.size, -p.size * .2, 0, -p.size);
            ctx.fill();
          } else {
            ctx.moveTo(-p.size, 0);
            ctx.quadraticCurveTo(0, -p.size * .65, p.size, 0);
            ctx.quadraticCurveTo(0, p.size * .65, -p.size, 0);
            ctx.fill();
            ctx.strokeStyle = 'rgba(255, 255, 255, .35)';
            ctx.lineWidth = .8;
            ctx.beginPath();
            ctx.moveTo(-p.size * 1.15, 0);
            ctx.lineTo(p.size * .8, 0);
            ctx.stroke();
          }
          ctx.restore();
        }
      }
      ctx.globalAlpha = 1;
      frame = requestAnimationFrame(draw);
    };
    frame = requestAnimationFrame(draw);

    const move = (event: PointerEvent) => { pointer.x = event.clientX; pointer.y = event.clientY; };
    const away = () => { pointer.x = -1e4; pointer.y = -1e4; };
    const visibility = () => {
      cancelAnimationFrame(frame);
      if (!document.hidden) { last = performance.now(); frame = requestAnimationFrame(draw); }
    };
    window.addEventListener('resize', resize);
    window.addEventListener('pointermove', move, { passive: true });
    window.addEventListener('pointerup', away);
    document.documentElement.addEventListener('pointerleave', away);
    document.addEventListener('visibilitychange', visibility);
    return () => {
      cancelAnimationFrame(frame);
      ctx.clearRect(0, 0, width, height);
      window.removeEventListener('resize', resize);
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerup', away);
      document.documentElement.removeEventListener('pointerleave', away);
      document.removeEventListener('visibilitychange', visibility);
    };
  }, [season]);
  return <canvas ref={ref} className="season-canvas" aria-hidden="true" />;
}

export function SeasonMenu({ choice, onChange }: { choice: SeasonChoice; onChange: (choice: SeasonChoice) => void }) {
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
  return <div className="season-menu" ref={box}>
    <button className="icon-button season-button" aria-haspopup="menu" aria-expanded={open} aria-label="Efek musim"
      title={'Efek musim: ' + seasonNames[choice]} onClick={() => setOpen(!open)}>
      <i className={seasonIcons[choice]} aria-hidden="true" />
    </button>
    {open && <ul className="season-list" role="menu" aria-label="Efek musim">
      {(Object.keys(seasonNames) as SeasonChoice[]).map((key) => <li key={key} role="none">
        <button role="menuitemradio" aria-checked={choice === key} onClick={() => { onChange(key); setOpen(false); }}>
          <i className={seasonIcons[key] + ' season-' + key} aria-hidden="true" />
          <span>{seasonNames[key]}{key === 'auto' && <small>sekarang: {seasonNames[seasonForDate()].toLowerCase()}</small>}</span>
          {choice === key && <i className="fas fa-check season-check" aria-hidden="true" />}
        </button>
      </li>)}
    </ul>}
  </div>;
}

/* ---------- Background music, generated in the browser (no audio files, no licensing) ---------- */

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
const hz = (root: number, semitones: number) => root * 2 ** (semitones / 12);
// Notes are scheduled this far ahead so a busy page (animations, charts) can never make them start late and click.
const LOOKAHEAD = .25;

// Soft piano-and-strings ambience: pure sine voices with a quiet octave for warmth, a gentle low-pass and a
// generated room reverb. No noise layers and nothing that bends the waveform, so it stays clean when loud.
class Ambient {
  playing = false;
  private ctx: AudioContext;
  private master: GainNode;
  private tone: BiquadFilterNode;
  private timer = 0;
  private chord = 0;
  private mood: Mood = MOODS.hujan;

  constructor() {
    // iPhones mute web audio with the silent switch unless the page asks for media playback.
    const session = (navigator as Navigator & { audioSession?: { type: string } }).audioSession;
    if (session) session.type = 'playback';
    this.ctx = new AudioContext({ latencyHint: 'playback' });
    this.master = this.ctx.createGain();
    this.tone = this.ctx.createBiquadFilter();
    this.tone.type = 'lowpass';
    this.tone.Q.value = .5;
    this.master.gain.value = 0;

    const reverb = this.ctx.createConvolver();
    reverb.buffer = this.room(2.6);
    const wet = this.ctx.createGain();
    wet.gain.value = .4;
    // Level first, then a fast limiter as the very last stage, so chance pile-ups of notes can never pass 1.0.
    // It only touches rare peaks (threshold near the top), so the music itself is not squashed or bent.
    const level = this.ctx.createGain();
    level.gain.value = 1.7;
    const limiter = this.ctx.createDynamicsCompressor();
    limiter.threshold.value = -3;
    limiter.knee.value = 0;
    limiter.ratio.value = 20;
    limiter.attack.value = .001;
    limiter.release.value = .25;

    this.tone.connect(this.master);
    this.tone.connect(reverb).connect(wet).connect(this.master);
    this.master.connect(level).connect(limiter).connect(this.ctx.destination);
  }

  // Call straight from a tap or click: Safari only unlocks sound inside the gesture itself.
  play(season: Season) {
    this.setSeason(season);
    this.ctx.resume().catch(() => {});
    const now = this.ctx.currentTime;
    this.master.gain.cancelScheduledValues(now);
    this.master.gain.setValueAtTime(this.master.gain.value, now);
    this.master.gain.linearRampToValueAtTime(1, now + 1.5);
    this.playing = true;
    if (!this.timer) this.next();
  }

  setSeason(season: Season) {
    this.mood = MOODS[season];
    this.tone.frequency.setTargetAtTime(this.mood.cutoff, this.ctx.currentTime, .8);
  }

  pause() {
    this.playing = false;
    window.clearTimeout(this.timer);
    this.timer = 0;
    const now = this.ctx.currentTime;
    this.master.gain.cancelScheduledValues(now);
    this.master.gain.setValueAtTime(this.master.gain.value, now);
    this.master.gain.linearRampToValueAtTime(0, now + .8);
    window.setTimeout(() => { if (!this.playing) this.ctx.suspend().catch(() => {}); }, 1500);
  }

  close() {
    window.clearTimeout(this.timer);
    this.ctx.close().catch(() => {});
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
        const fadeIn = Math.min(1, i / (rate * .02));
        data[i] = smooth * fadeIn * Math.pow(1 - i / length, 3.2);
      }
    }
    return buffer;
  }

  private next = () => {
    const { chords, root, scale, gap } = this.mood;
    const length = (gap[0] + Math.random() * (gap[1] - gap[0])) / 1000;
    const start = this.ctx.currentTime + LOOKAHEAD;
    this.pad(chords[this.chord % chords.length].map((note) => hz(root, note)), start, length * 1.2);
    this.chord++;
    const notes = 1 + Math.floor(Math.random() * 2);
    for (let i = 0; i < notes; i++) {
      this.pluck(hz(root * 2, scale[Math.floor(Math.random() * scale.length)]), start + .4 + Math.random() * (length - .8));
    }
    this.timer = window.setTimeout(this.next, length * 1000);
  };

  private voice(freq: number, level: number, start: number, attack: number, end: number, release: 'linear' | 'exp') {
    const osc = this.ctx.createOscillator();
    const gain = this.ctx.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0, start);
    gain.gain.linearRampToValueAtTime(level, start + attack);
    if (release === 'exp') gain.gain.setTargetAtTime(0, start + attack, (end - start - attack) / 5);
    else gain.gain.linearRampToValueAtTime(0, end);
    osc.connect(gain).connect(this.tone);
    osc.start(start);
    osc.stop(end + .05);
  }

  // Strings: slow swell and fade, the root plus a quiet octave above.
  private pad(freqs: number[], start: number, length: number) {
    for (const freq of freqs) {
      this.voice(freq, .1, start, 1.8, start + length, 'linear');
      this.voice(freq * 2, .024, start, 2.2, start + length, 'linear');
    }
  }

  // Piano-like note: soft 30 ms attack and a long natural fade; bell seasons add a faint shimmer.
  private pluck(freq: number, start: number) {
    const tail = this.mood.bell ? 3.4 : 2.6;
    this.voice(freq, .14, start, .03, start + tail, 'exp');
    this.voice(freq * 2, .035, start, .03, start + tail * .6, 'exp');
    if (this.mood.bell) this.voice(freq * 3, .012, start, .03, start + tail * .4, 'exp');
  }
}

export function useMusic(season: Season) {
  const [on, setOn] = useState(() => remembered('simbar-music') === 'on');
  const engine = useRef<Ambient | null>(null);
  const seasonRef = useRef(season);
  seasonRef.current = season;

  const start = () => {
    try { (engine.current ??= new Ambient()).play(seasonRef.current); } catch { /* no audio support */ }
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

  useEffect(() => { if (on) engine.current?.setSeason(season); }, [season, on]);

  useEffect(() => {
    const visibility = () => {
      if (!engine.current || !on) return;
      if (document.hidden) engine.current.pause();
      else engine.current.play(seasonRef.current);
    };
    document.addEventListener('visibilitychange', visibility);
    return () => document.removeEventListener('visibilitychange', visibility);
  }, [on]);

  useEffect(() => () => { engine.current?.close(); engine.current = null; }, []);

  const toggle = () => {
    if (on) engine.current?.pause();
    else start();
    remember('simbar-music', on ? 'off' : 'on');
    setOn(!on);
  };
  return [on, toggle] as const;
}

/* ---------- Cursor follower (mouse only) ---------- */

const INTERACTIVE = 'button, a, input, select, textarea, label, summary, [role="button"], [tabindex]:not([tabindex="-1"])';

export function CursorTrail() {
  const ring = useRef<HTMLDivElement>(null);
  const dot = useRef<HTMLDivElement>(null);
  const [enabled] = useState(() => window.matchMedia('(pointer: fine)').matches && !reducedMotion());
  useEffect(() => {
    const outer = ring.current, inner = dot.current;
    if (!enabled || !outer || !inner) return;
    let x = -100, y = -100, rx = -100, ry = -100, frame = 0, shown = false;
    const tick = () => {
      rx += (x - rx) * .2;
      ry += (y - ry) * .2;
      outer.style.transform = `translate3d(${rx}px, ${ry}px, 0)`;
      inner.style.transform = `translate3d(${x}px, ${y}px, 0)`;
      frame = Math.abs(x - rx) + Math.abs(y - ry) > .3 ? requestAnimationFrame(tick) : 0;
    };
    const move = (event: PointerEvent) => {
      if (event.pointerType !== 'mouse') return;
      x = event.clientX;
      y = event.clientY;
      if (!shown) { shown = true; rx = x; ry = y; document.documentElement.classList.add('cursor-on'); }
      outer.classList.toggle('hover', !!(event.target as Element | null)?.closest?.(INTERACTIVE));
      if (!frame) frame = requestAnimationFrame(tick);
    };
    const down = (event: PointerEvent) => {
      if (event.pointerType !== 'mouse') return;
      outer.classList.add('down');
      const burst = document.createElement('span');
      burst.className = 'cursor-burst';
      burst.style.left = event.clientX + 'px';
      burst.style.top = event.clientY + 'px';
      document.body.appendChild(burst);
      burst.addEventListener('animationend', () => burst.remove());
    };
    const up = () => outer.classList.remove('down');
    const leave = () => { shown = false; document.documentElement.classList.remove('cursor-on'); };
    window.addEventListener('pointermove', move, { passive: true });
    window.addEventListener('pointerdown', down);
    window.addEventListener('pointerup', up);
    document.documentElement.addEventListener('mouseleave', leave);
    return () => {
      cancelAnimationFrame(frame);
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerdown', down);
      window.removeEventListener('pointerup', up);
      document.documentElement.removeEventListener('mouseleave', leave);
      document.documentElement.classList.remove('cursor-on');
    };
  }, [enabled]);
  if (!enabled) return null;
  return <>
    <div ref={ring} className="cursor-ring" aria-hidden="true" />
    <div ref={dot} className="cursor-dot" aria-hidden="true" />
  </>;
}
