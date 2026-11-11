import { useEffect, useRef, useState } from 'react';
import type { CSSProperties, PointerEvent, ReactNode } from 'react';

// Bento cards with a border glow and spotlight that follow the pointer (mouse, pen or finger).
// Adapted from the 21st.dev "interactive tech stack bento grid" without Tailwind.

export type Accent = 'indigo' | 'cyan' | 'emerald' | 'fuchsia' | 'amber' | 'rose';
const ACCENTS: Record<Accent, string> = {
  indigo: '129, 140, 248',
  cyan: '34, 211, 238',
  emerald: '52, 211, 153',
  fuchsia: '232, 121, 249',
  amber: '251, 191, 36',
  rose: '251, 113, 133',
};

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// The pointer position is written straight into CSS variables, so moving the mouse never re-renders React.
export function GlowCard({ accent, className = '', children }: { accent: Accent; className?: string; children: ReactNode }) {
  const card = useRef<HTMLDivElement>(null);
  const move = (event: PointerEvent<HTMLDivElement>) => {
    const node = card.current;
    if (!node) return;
    const rect = node.getBoundingClientRect();
    node.style.setProperty('--x', `${event.clientX - rect.left}px`);
    node.style.setProperty('--y', `${event.clientY - rect.top}px`);
    node.classList.add('glowing');
  };
  const leave = () => card.current?.classList.remove('glowing');
  return <div ref={card} className={'bento-card ' + className} style={{ '--rgb': ACCENTS[accent] } as CSSProperties}
    onPointerMove={move} onPointerDown={move} onPointerLeave={leave} onPointerUp={(event) => event.pointerType !== 'mouse' && leave()}
    onPointerCancel={leave}>
    <span className="bento-lines" aria-hidden="true" />
    <div className="bento-body">{children}</div>
  </div>;
}

export function BentoTitle({ icon, children, right }: { icon: string; children: ReactNode; right?: ReactNode }) {
  return <div className="bento-title">
    <h2><i className={'fas ' + icon} aria-hidden="true" />{children}</h2>
    {right}
  </div>;
}

// Counts up from the previous value to the new one; jumps straight there when motion is reduced.
export function useAnimatedNumber(target: number, duration = 900) {
  const [value, setValue] = useState(0);
  const current = useRef(0);
  useEffect(() => {
    if (reducedMotion()) {
      current.current = target;
      setValue(target);
      return;
    }
    const from = current.current;
    const start = performance.now();
    let frame = 0;
    const tick = (now: number) => {
      const t = Math.min((now - start) / duration, 1);
      current.current = from + (target - from) * (1 - Math.pow(1 - t, 3));
      setValue(current.current);
      if (t < 1) frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frame);
  }, [target, duration]);
  return value;
}

const number = new Intl.NumberFormat('id-ID');
export function CountUp({ value, className }: { value: number; className?: string }) {
  const shown = useAnimatedNumber(value);
  // Screen readers get the final number straight away; the animation is visual only.
  return <span className={className}><span aria-hidden="true">{number.format(Math.round(shown))}</span>
    <span className="sr-only">{number.format(value)}</span></span>;
}

// Small bar sparkline; the latest bar is highlighted.
export function MiniBars({ values, label }: { values: number[]; label: string }) {
  const max = Math.max(1, ...values);
  return <div className="bento-bars" role="img" aria-label={label}>
    {values.map((value, index) => <span key={index} className={index === values.length - 1 ? 'latest' : undefined}
      style={{ height: `${Math.max((value / max) * 100, 6)}%` }} />)}
  </div>;
}
