import { useEffect, useRef, useState } from 'react';

// Categorical slots 1-2 of the validated reference palette (adjacent CVD ΔE ≥ 8 on the light surface).
export const SERIES = ['#2a78d6', '#eb6834'];
const number = new Intl.NumberFormat('id-ID');

type Series = { name: string; values: number[] };

function niceMax(value: number) {
  if (value <= 0) return 1;
  const power = 10 ** Math.floor(Math.log10(value));
  const step = [1, 2, 2.5, 5, 10].find((candidate) => candidate * power >= value / 4)! * power;
  return Math.ceil(value / step) * step;
}

// Grouped vertical bars on one shared axis, with legend, hover/focus readout and a table view.
export function GroupedBars({ title, labels, series, unit = '' }: { title: string; labels: string[]; series: Series[]; unit?: string }) {
  const [active, setActive] = useState<number | null>(null);
  const [width, setWidth] = useState(640);
  const frame = useRef<HTMLDivElement>(null);
  useEffect(() => {
    const node = frame.current;
    if (!node) return;
    const observer = new ResizeObserver(([entry]) => setWidth(Math.max(280, Math.round(entry.contentRect.width))));
    observer.observe(node);
    return () => observer.disconnect();
  }, []);
  const height = width < 480 ? 200 : 240;
  const pad = { top: 12, right: 8, bottom: 28, left: 44 };
  const plotW = width - pad.left - pad.right;
  const plotH = height - pad.top - pad.bottom;
  const max = niceMax(Math.max(0, ...series.flatMap((entry) => entry.values)));
  const group = plotW / labels.length;
  const barW = Math.min(22, (group * 0.7 - 2 * (series.length - 1)) / series.length);
  const ticks = [0, max / 2, max];
  const y = (value: number) => pad.top + plotH - (value / max) * plotH;

  return <figure className="chart">
    <figcaption><strong>{title}</strong>
      {series.length > 1 && <span className="legend">{series.map((entry, index) =>
        <span key={entry.name}><i style={{ background: SERIES[index] }} aria-hidden="true" />{entry.name}</span>)}</span>}
    </figcaption>
    <div className="chart-readout" aria-live="polite">{active === null ? 'Arahkan ke grafik untuk melihat angka.'
      : <><strong>{labels[active]}</strong>{series.map((entry) => <span key={entry.name}> · {entry.name}: <b>{number.format(entry.values[active])}</b>{unit}</span>)}</>}</div>
    <div ref={frame}><svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} role="img" aria-label={title} onPointerLeave={() => setActive(null)}>
      {ticks.map((tick) => <g key={tick}>
        <line x1={pad.left} x2={width - pad.right} y1={y(tick)} y2={y(tick)} className="grid" />
        <text x={pad.left - 6} y={y(tick)} className="axis" textAnchor="end" dominantBaseline="middle">{number.format(tick)}</text>
      </g>)}
      {labels.map((label, index) => {
        const x0 = pad.left + group * index;
        const start = x0 + (group - (barW * series.length + 2 * (series.length - 1))) / 2;
        return <g key={label} tabIndex={0} onPointerEnter={() => setActive(index)} onFocus={() => setActive(index)} onBlur={() => setActive(null)}
          className={active === index ? 'bar-group active' : 'bar-group'}>
          <rect x={x0} y={pad.top} width={group} height={plotH} className="hit" />
          {series.map((entry, s) => {
            const value = entry.values[index];
            const top = y(value);
            const h = Math.max(0, pad.top + plotH - top);
            const x = start + s * (barW + 2);
            const r = Math.min(4, barW / 2, h);
            return <path key={entry.name} fill={SERIES[s]}
              d={h <= 0 ? '' : `M${x},${top + h} V${top + r} Q${x},${top} ${x + r},${top} H${x + barW - r} Q${x + barW},${top} ${x + barW},${top + r} V${top + h} Z`} />;
          })}
          {(labels.length <= 6 || width >= 480 || index % 2 === 0) &&
            <text x={x0 + group / 2} y={height - 8} className="axis" textAnchor="middle">{label}</text>}
        </g>;
      })}
      <line x1={pad.left} x2={width - pad.right} y1={pad.top + plotH} y2={pad.top + plotH} className="baseline" />
    </svg></div>
    <details className="chart-table"><summary>Lihat tabel</summary>
      <table><thead><tr><th scope="col">Periode</th>{series.map((entry) => <th key={entry.name} scope="col">{entry.name}</th>)}</tr></thead>
        <tbody>{labels.map((label, index) => <tr key={label}><th scope="row">{label}</th>
          {series.map((entry) => <td key={entry.name}>{number.format(entry.values[index])}</td>)}</tr>)}</tbody></table>
    </details>
  </figure>;
}

// Single-series horizontal bars (ranked magnitude), value labels outside the bar end.
export function RankedBars({ title, rows, unit = '' }: { title: string; rows: { label: string; value: number; note?: string }[]; unit?: string }) {
  const max = Math.max(1, ...rows.map((row) => row.value));
  return <figure className="chart">
    <figcaption><strong>{title}</strong></figcaption>
    {rows.length ? <ul className="ranked">{rows.map((row) => <li key={row.label} tabIndex={0}
      title={`${row.label}: ${number.format(row.value)}${unit}${row.note ? ' ' + row.note : ''}`}>
      <span className="ranked-label">{row.label}</span>
      <span className="ranked-track"><span className="ranked-bar" style={{ width: `${(row.value / max) * 100}%`, background: SERIES[0] }} /></span>
      <span className="ranked-value">{number.format(row.value)}{row.note ? ' ' + row.note : unit}</span>
    </li>)}</ul> : <p>Belum ada data.</p>}
  </figure>;
}
