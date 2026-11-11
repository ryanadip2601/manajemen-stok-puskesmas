import { useEffect, useState } from 'react';

// Puskesmas Karang Rejo is in Balikpapan, so the clock always shows WITA (UTC+8) whatever the device is set to.
const ZONE = 'Asia/Makassar';
const timeFormat = new Intl.DateTimeFormat('id-ID', { timeZone: ZONE, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
const shortDate = new Intl.DateTimeFormat('id-ID', { timeZone: ZONE, weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
const longDate = new Intl.DateTimeFormat('id-ID', { timeZone: ZONE, weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

function useNow() {
  const [now, setNow] = useState(() => new Date());
  useEffect(() => {
    // Tick on the second boundary so every clock on the page changes together.
    let timer = window.setTimeout(function tick() {
      setNow(new Date());
      timer = window.setTimeout(tick, 1000 - (Date.now() % 1000));
    }, 1000 - (Date.now() % 1000));
    return () => window.clearTimeout(timer);
  }, []);
  return now;
}

function parts(now: Date) {
  const [h, m, s] = timeFormat.format(now).split(/[.:]/).map(Number);
  return { h, m, s };
}
const pad = (n: number) => String(n).padStart(2, '0');

function greeting(hour: number) {
  if (hour >= 4 && hour < 11) return 'Selamat pagi';
  if (hour < 15 && hour >= 11) return 'Selamat siang';
  if (hour < 18 && hour >= 15) return 'Selamat sore';
  return 'Selamat malam';
}

// Compact clock for the header, on every page.
export function HeaderClock() {
  const now = useNow();
  const { h, m, s } = parts(now);
  return <time className="header-clock" dateTime={now.toISOString()} title={longDate.format(now) + ' WITA'}>
    <i className="fas fa-clock" aria-hidden="true" />
    <span className="clock-date">{shortDate.format(now)}</span>
    <span className="clock-time">{pad(h)}:{pad(m)}<span className="clock-seconds">:{pad(s)}</span></span>
    <span className="clock-zone">WITA</span>
  </time>;
}

// Analog and digital clock card for the dashboard.
export function ClockCard() {
  const now = useNow();
  const { h, m, s } = parts(now);
  const hand = (angle: number) => ({ transform: `rotate(${angle}deg)` });
  return <div className="clock-card">
    <svg className="analog" viewBox="0 0 100 100" role="img" aria-label={`Jam ${pad(h)}.${pad(m)} WITA`}>
      <circle cx="50" cy="50" r="46" className="face" />
      {Array.from({ length: 12 }, (_, i) => <line key={i} x1="50" y1="8" x2="50" y2={i % 3 ? 13 : 16} className={i % 3 ? 'tick' : 'tick major'}
        transform={`rotate(${i * 30} 50 50)`} />)}
      <line x1="50" y1="50" x2="50" y2="27" className="hand hour" style={hand((h % 12) * 30 + m / 2)} />
      <line x1="50" y1="50" x2="50" y2="17" className="hand minute" style={hand(m * 6 + s / 10)} />
      <line x1="50" y1="58" x2="50" y2="13" className="hand second" style={hand(s * 6)} />
      <circle cx="50" cy="50" r="3" className="pin" />
    </svg>
    <div className="clock-text">
      <p className="clock-greeting">{greeting(h)}</p>
      <p className="clock-digital">{pad(h)}:{pad(m)}<span>:{pad(s)}</span></p>
      <p className="clock-long">{longDate.format(now)}</p>
      <p className="clock-zone-label">WITA · Balikpapan</p>
    </div>
  </div>;
}
