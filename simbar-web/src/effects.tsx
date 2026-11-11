import { useEffect, useRef, useState } from 'react';

// Ambience: seasonal background particles, generated background music and a soft cursor follower.
// Everything stays off for people who ask their device for reduced motion.

export function remembered(key: string) {
  try { return window.localStorage.getItem(key); } catch { return null; }
}
export function remember(key: string, value: string) {
  try { window.localStorage.setItem(key, value); } catch { /* private mode */ }
}
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ---------- Seasons ---------- */

export type Season = 'hujan' | 'kemarau' | 'salju';
export type SeasonChoice = 'auto' | Season | 'off';
export const seasonNames: Record<SeasonChoice, string> = {
  auto: 'Otomatis', hujan: 'Musim hujan', kemarau: 'Musim kemarau', salju: 'Salju', off: 'Tanpa efek',
};
const seasonIcons: Record<SeasonChoice, string> = {
  auto: 'fa-wand-magic-sparkles', hujan: 'fa-cloud-rain', kemarau: 'fa-leaf', salju: 'fa-snowflake', off: 'fa-ban',
};

// Indonesian seasons: kemarau April–September, hujan October–March; December gets snow for the year-end mood.
export function seasonForDate(date = new Date()): Season {
  const month = date.getMonth();
  if (month === 11) return 'salju';
  return month >= 3 && month <= 8 ? 'kemarau' : 'hujan';
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

type Particle = { x: number; y: number; vx: number; vy: number; size: number; rot: number; spin: number; phase: number; alpha: number; color: string };
const LEAVES = ['#d97706', '#b45309', '#ca8a04', '#a16207', '#92400e', '#84cc16'];

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

    const phone = width < 700;
    const count = season === 'kemarau' ? (phone ? 10 : 20) : phone ? 40 : 85;
    const spawn = (anywhere: boolean): Particle => {
      const r = Math.random;
      const base = { x: r() * width, y: anywhere ? r() * height : -24, rot: r() * Math.PI * 2, phase: r() * Math.PI * 2, color: LEAVES[Math.floor(r() * LEAVES.length)] };
      if (season === 'hujan') return { ...base, vx: 1.4, vy: 9 + r() * 6, size: 10 + r() * 12, spin: 0, alpha: .25 + r() * .3 };
      if (season === 'salju') return { ...base, vx: -.2 + r() * .4, vy: .5 + r() * 1.1, size: 1.2 + r() * 2.6, spin: 0, alpha: .55 + r() * .4 };
      return { ...base, vx: -.3 + r() * .6, vy: .5 + r() * .7, size: 6 + r() * 7, spin: (r() - .5) * .05, alpha: .55 + r() * .35 };
    };
    const particles = Array.from({ length: count }, () => spawn(true));
    const pointer = { x: -1e4, y: -1e4 };

    let frame = 0;
    let last = performance.now();
    const draw = (now: number) => {
      const step = Math.min((now - last) / 16.67, 3);
      last = now;
      ctx.clearRect(0, 0, width, height);
      const dark = document.documentElement.dataset.theme === 'dark';
      for (const p of particles) {
        // Particles drift away from the cursor or finger.
        const dx = p.x - pointer.x, dy = p.y - pointer.y, distance = Math.hypot(dx, dy);
        if (distance < 120 && distance > 1) {
          const push = (1 - distance / 120) * (season === 'hujan' ? .8 : 1.8) * step;
          p.x += (dx / distance) * push;
          p.y += (dy / distance) * push * .4;
        }
        p.phase += .02 * step;
        p.x += (p.vx + Math.sin(p.phase) * (season === 'hujan' ? 0 : season === 'salju' ? .45 : 1)) * step;
        p.y += p.vy * step;
        p.rot += p.spin * step;
        if (p.y > height + 24 || p.x < -40 || p.x > width + 40) Object.assign(p, spawn(false));

        ctx.globalAlpha = p.alpha;
        if (season === 'hujan') {
          ctx.strokeStyle = dark ? '#93c5fd' : '#3b82f6';
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(p.x, p.y);
          ctx.lineTo(p.x - p.vx * 1.6, p.y - p.size);
          ctx.stroke();
        } else if (season === 'salju') {
          ctx.fillStyle = '#ffffff';
          ctx.beginPath();
          ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
          ctx.fill();
          if (!dark) {
            ctx.strokeStyle = 'rgba(100, 116, 139, .35)';
            ctx.lineWidth = .6;
            ctx.stroke();
          }
        } else {
          ctx.save();
          ctx.translate(p.x, p.y);
          ctx.rotate(p.rot);
          ctx.scale(1, .45 + .55 * Math.abs(Math.sin(p.phase)));
          ctx.fillStyle = p.color;
          ctx.beginPath();
          ctx.moveTo(-p.size, 0);
          ctx.quadraticCurveTo(0, -p.size * .65, p.size, 0);
          ctx.quadraticCurveTo(0, p.size * .65, -p.size, 0);
          ctx.fill();
          ctx.strokeStyle = 'rgba(255, 255, 255, .35)';
          ctx.lineWidth = .8;
          ctx.beginPath();
          ctx.moveTo(-p.size * .8, 0);
          ctx.lineTo(p.size * .8, 0);
          ctx.stroke();
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
      <i className={'fas ' + seasonIcons[choice]} aria-hidden="true" />
    </button>
    {open && <ul className="season-list" role="menu" aria-label="Efek musim">
      {(Object.keys(seasonNames) as SeasonChoice[]).map((key) => <li key={key} role="none">
        <button role="menuitemradio" aria-checked={choice === key} onClick={() => { onChange(key); setOpen(false); }}>
          <i className={'fas ' + seasonIcons[key]} aria-hidden="true" />
          <span>{seasonNames[key]}{key === 'auto' && <small>sekarang: {seasonNames[seasonForDate()].toLowerCase()}</small>}</span>
          {choice === key && <i className="fas fa-check season-check" aria-hidden="true" />}
        </button>
      </li>)}
    </ul>}
  </div>;
}

/* ---------- Background music, generated in the browser (no audio files, no licensing) ---------- */

type Mood = { root: number; scale: number[]; chords: number[][]; wave: OscillatorType; cutoff: number; gap: [number, number]; bell: boolean; rain: boolean };
const MOODS: Record<Season, Mood> = {
  hujan: { root: 220, scale: [0, 3, 5, 7, 10, 12, 15], chords: [[0, 3, 7], [-4, 0, 3], [-2, 2, 5], [-5, -1, 2]], wave: 'sine', cutoff: 950, gap: [2800, 4400], bell: false, rain: true },
  kemarau: { root: 261.63, scale: [0, 2, 4, 7, 9, 12, 14], chords: [[0, 4, 7], [5, 9, 12], [-3, 0, 4], [7, 11, 14]], wave: 'triangle', cutoff: 1700, gap: [2000, 3400], bell: false, rain: false },
  salju: { root: 392, scale: [0, 2, 4, 6, 7, 11, 12, 14], chords: [[0, 4, 7, 11], [2, 6, 9], [-1, 2, 7], [4, 7, 11]], wave: 'sine', cutoff: 2600, gap: [2400, 4200], bell: true, rain: false },
};
const hz = (root: number, semitones: number) => root * 2 ** (semitones / 12);

class Ambient {
  private ctx = new AudioContext();
  private master = this.ctx.createGain();
  private tone = this.ctx.createBiquadFilter();
  private rain: { source: AudioBufferSourceNode; gain: GainNode } | null = null;
  private timer = 0;
  private chord = 0;
  private mood: Mood = MOODS.hujan;

  constructor() {
    const delay = this.ctx.createDelay(1);
    const feedback = this.ctx.createGain();
    const wet = this.ctx.createGain();
    this.master.gain.value = 0;
    this.tone.type = 'lowpass';
    delay.delayTime.value = .38;
    feedback.gain.value = .35;
    wet.gain.value = .3;
    this.tone.connect(this.master);
    this.tone.connect(delay);
    delay.connect(feedback).connect(delay);
    delay.connect(wet).connect(this.master);
    this.master.connect(this.ctx.destination);
  }

  async play(season: Season) {
    this.setSeason(season);
    await this.ctx.resume();
    const now = this.ctx.currentTime;
    this.master.gain.cancelScheduledValues(now);
    this.master.gain.setTargetAtTime(.32, now, .8);
    if (!this.timer) this.next();
  }

  setSeason(season: Season) {
    this.mood = MOODS[season];
    this.tone.frequency.setTargetAtTime(this.mood.cutoff, this.ctx.currentTime, .5);
    if (this.mood.rain && !this.rain) this.startRain();
    if (!this.mood.rain && this.rain) this.stopRain();
  }

  pause() {
    window.clearTimeout(this.timer);
    this.timer = 0;
    this.master.gain.setTargetAtTime(0, this.ctx.currentTime, .3);
    window.setTimeout(() => { if (!this.timer) this.ctx.suspend(); }, 1500);
  }

  close() {
    window.clearTimeout(this.timer);
    this.ctx.close();
  }

  private next = () => {
    const { chords, root, scale, gap } = this.mood;
    const length = (gap[0] + Math.random() * (gap[1] - gap[0])) / 1000;
    const start = this.ctx.currentTime + .05;
    this.pad(chords[this.chord % chords.length].map((note) => hz(root / 2, note)), start, length * 1.6);
    this.chord++;
    const notes = 1 + Math.floor(Math.random() * 3);
    for (let i = 0; i < notes; i++) {
      this.pluck(hz(root, scale[Math.floor(Math.random() * scale.length)]), start + .3 + Math.random() * (length - .5));
    }
    this.timer = window.setTimeout(this.next, length * 1000);
  };

  private pad(freqs: number[], start: number, length: number) {
    for (const freq of freqs) {
      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();
      osc.type = this.mood.wave;
      osc.frequency.value = freq;
      osc.detune.value = (Math.random() - .5) * 8;
      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(.05, start + 1.4);
      gain.gain.linearRampToValueAtTime(0, start + length);
      osc.connect(gain).connect(this.tone);
      osc.start(start);
      osc.stop(start + length + .1);
    }
  }

  private pluck(freq: number, start: number) {
    const voices = this.mood.bell ? [[1, .07], [2.76, .025]] : [[1, .07]];
    for (const [ratio, level] of voices) {
      const osc = this.ctx.createOscillator();
      const gain = this.ctx.createGain();
      osc.type = this.mood.bell ? 'sine' : this.mood.wave;
      osc.frequency.value = freq * ratio;
      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(level, start + .015);
      gain.gain.exponentialRampToValueAtTime(.0001, start + (this.mood.bell ? 2.6 : 1.6));
      osc.connect(gain).connect(this.tone);
      osc.start(start);
      osc.stop(start + 2.8);
    }
  }

  private startRain() {
    const buffer = this.ctx.createBuffer(1, this.ctx.sampleRate * 2, this.ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
    const source = this.ctx.createBufferSource();
    const band = this.ctx.createBiquadFilter();
    const gain = this.ctx.createGain();
    source.buffer = buffer;
    source.loop = true;
    band.type = 'bandpass';
    band.frequency.value = 1400;
    band.Q.value = .6;
    gain.gain.value = 0;
    gain.gain.setTargetAtTime(.05, this.ctx.currentTime, 1);
    source.connect(band).connect(gain).connect(this.master);
    source.start();
    this.rain = { source, gain };
  }

  private stopRain() {
    const rain = this.rain!;
    rain.gain.gain.setTargetAtTime(0, this.ctx.currentTime, .4);
    rain.source.stop(this.ctx.currentTime + 2);
    this.rain = null;
  }
}

export function useMusic(season: Season) {
  const [on, setOn] = useState(() => remembered('simbar-music') === 'on');
  const engine = useRef<Ambient | null>(null);
  const seasonRef = useRef(season);
  seasonRef.current = season;

  useEffect(() => {
    remember('simbar-music', on ? 'on' : 'off');
    if (!on) { engine.current?.pause(); return; }
    const begin = () => { (engine.current ??= new Ambient()).play(seasonRef.current).catch(() => {}); };
    // Browsers only allow sound after the person has clicked or typed on the page.
    if (navigator.userActivation?.hasBeenActive) { begin(); return; }
    window.addEventListener('pointerdown', begin, { once: true });
    window.addEventListener('keydown', begin, { once: true });
    return () => {
      window.removeEventListener('pointerdown', begin);
      window.removeEventListener('keydown', begin);
    };
  }, [on]);

  useEffect(() => { if (on) engine.current?.setSeason(season); }, [season, on]);

  useEffect(() => {
    const visibility = () => {
      if (!engine.current || !on) return;
      if (document.hidden) engine.current.pause();
      else engine.current.play(seasonRef.current).catch(() => {});
    };
    document.addEventListener('visibilitychange', visibility);
    return () => document.removeEventListener('visibilitychange', visibility);
  }, [on]);

  useEffect(() => () => { engine.current?.close(); engine.current = null; }, []);
  return [on, () => setOn(!on)] as const;
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
