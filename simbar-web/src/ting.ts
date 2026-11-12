import { remember, remembered } from './effects';

// In-app "ting": a short bell made with Web Audio, played while SIMBAR is open and something new arrives.
// Browsers only allow sound after the person has touched the page once, so the audio is unlocked on the first tap.

const KEY = 'simbar-ting';
export const tingEnabled = () => remembered(KEY) !== 'off';
export const setTingEnabled = (on: boolean) => remember(KEY, on ? 'on' : 'off');

let ctx: AudioContext | null = null;
function audio() {
  if (!ctx) {
    const Ctor = window.AudioContext ?? (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext;
    if (!Ctor) return null;
    ctx = new Ctor();
  }
  if (ctx.state === 'suspended') ctx.resume().catch(() => {});
  return ctx;
}
const unlock = () => { audio(); window.removeEventListener('pointerdown', unlock); window.removeEventListener('keydown', unlock); };
window.addEventListener('pointerdown', unlock);
window.addEventListener('keydown', unlock);

let lastPush = 0;

// source 'push': a push notification arrived; 'queue': the waiting count went up (checked every minute),
// skipped when a push already rang for the same news a moment ago. 'test': the "Coba bunyi" button.
export function playTing(source: 'push' | 'queue' | 'test' = 'push') {
  if (source !== 'test' && (!tingEnabled() || document.visibilityState !== 'visible')) return;
  const now = Date.now();
  if (source === 'queue' && now - lastPush < 70_000) return;
  if (source === 'push') lastPush = now;
  const ac = audio();
  if (!ac || ac.state !== 'running') return;
  const start = ac.currentTime + 0.01;
  const out = ac.createGain();
  out.gain.value = 0.35;
  out.connect(ac.destination);
  // Two bell partials: a bright strike that rings out softly.
  for (const [freq, level, length] of [[1318.5, 0.6, 0.9], [2637, 0.18, 0.45], [3951, 0.06, 0.25]] as const) {
    const osc = ac.createOscillator();
    const gain = ac.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    gain.gain.setValueAtTime(0.0001, start);
    gain.gain.exponentialRampToValueAtTime(level, start + 0.008);
    gain.gain.exponentialRampToValueAtTime(0.0001, start + length);
    osc.connect(gain).connect(out);
    osc.start(start);
    osc.stop(start + length + 0.05);
  }
}
