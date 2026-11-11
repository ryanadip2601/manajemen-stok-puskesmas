import { useEffect, useMemo, useState } from 'react';
import { rpc } from './supabase';

// Rapikan data barang: pairs of barang with very similar names, for a person to decide one by one.
// "Varian berbeda" remembers the pair so it is not suggested again; "Ubah" opens the normal edit form;
// "Gabungkan" (administrator only) moves all history to one barang and adds the stock up. Nothing is merged automatically.
type Item = { id: number; code: string; name: string; stock: number; unit: string; category?: string };
type Pair = { a: Item; b: Item; score: number; variant: boolean };

const number = new Intl.NumberFormat('id-ID');
const words = (name: string) => name.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, ' ').trim().split(' ').filter(Boolean);
function bigrams(text: string) {
  const list: string[] = [];
  for (let i = 0; i < text.length - 1; i++) list.push(text.slice(i, i + 2));
  return list;
}
// Dice coefficient on letter pairs: 1 means the same text, around 0.8 means a word or a letter apart.
function similarity(a: string, b: string) {
  const x = bigrams(a), y = bigrams(b);
  if (!x.length || !y.length) return a === b ? 1 : 0;
  const pool = new Map<string, number>();
  for (const g of x) pool.set(g, (pool.get(g) ?? 0) + 1);
  let hits = 0;
  for (const g of y) { const n = pool.get(g) ?? 0; if (n) { hits++; pool.set(g, n - 1); } }
  return (2 * hits) / (x.length + y.length);
}
// Names that differ only in a short code (Y/M/C/BK, AA/AAA, 3 Ply/4 Ply, sizes) are probably real variants.
function looksLikeVariant(a: string[], b: string[]) {
  const onlyA = a.filter((w) => !b.includes(w)), onlyB = b.filter((w) => !a.includes(w));
  const short = (w: string) => w.length <= 3 || /\d/.test(w);
  return onlyA.length + onlyB.length > 0 && onlyA.length <= 2 && onlyB.length <= 2 && [...onlyA, ...onlyB].every(short);
}

export function RapikanBarang({ admin, edit, refresh, version }: { admin: boolean; edit: (id: number) => void; refresh: () => void; version: number }) {
  const [items, setItems] = useState<Item[] | null>(null);
  const [ignored, setIgnored] = useState<Set<string>>(new Set());
  const [open, setOpen] = useState(false);
  const [showVariants, setShowVariants] = useState(false);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let live = true;
    Promise.all([rpc<{ items: Item[] }>('simbar_stock_options'), rpc<[number, number][]>('simbar_item_pairs_ignored')])
      .then(([options, pairs]) => { if (!live) return; setItems(options.items); setIgnored(new Set(pairs.map(([a, b]) => `${a}-${b}`))); })
      .catch((error: Error) => { if (live) setMessage({ text: error.message, ok: false }); });
    return () => { live = false; };
  }, [version]);

  const pairs = useMemo(() => {
    if (!items) return [];
    const prepared = items.map((item) => ({ item, words: words(item.name), text: words(item.name).join(' ') }));
    const found: Pair[] = [];
    for (let i = 0; i < prepared.length; i++) for (let j = i + 1; j < prepared.length; j++) {
      const x = prepared[i], y = prepared[j];
      const [a, b] = x.item.id < y.item.id ? [x, y] : [y, x];
      if (ignored.has(`${a.item.id}-${b.item.id}`)) continue;
      const score = x.text.replace(/ /g, '') === y.text.replace(/ /g, '') ? 1 : similarity(x.text, y.text);
      if (score >= 0.8) found.push({ a: a.item, b: b.item, score, variant: score < 1 && looksLikeVariant(a.words, b.words) });
    }
    return found.sort((p, q) => Number(p.variant) - Number(q.variant) || q.score - p.score);
  }, [items, ignored]);
  const suspects = pairs.filter((pair) => !pair.variant);
  const shown = showVariants ? pairs : suspects;

  async function act(action: () => Promise<unknown>, text: string, reload = true) {
    setBusy(true);
    try {
      await action();
      setMessage({ text, ok: true });
      if (reload) refresh();
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }
  function ignore(pair: Pair) {
    act(() => rpc('simbar_item_pair_ignore', { first_id: pair.a.id, second_id: pair.b.id }), 'Ditandai sebagai varian berbeda.', false)
      .then(() => setIgnored((current) => new Set([...current, `${pair.a.id}-${pair.b.id}`])));
  }
  function merge(keep: Item, drop: Item) {
    if (!confirm(`Gabungkan "${drop.name}" (${drop.code}, stok ${drop.stock}) ke "${keep.name}" (${keep.code})?\n\n`
      + `Semua riwayat masuk/keluar dan pengajuan pindah ke ${keep.code}, stok menjadi ${keep.stock + drop.stock} ${keep.unit}, `
      + `dan ${drop.code} dihapus. Ini tidak bisa dibatalkan.`)) return;
    act(() => rpc('simbar_item_merge', { keep_id: keep.id, drop_id: drop.id }), `${drop.name} digabung ke ${keep.name}.`);
  }

  if (!items) return null;
  const card = (item: Item) => <div className="rapikan-item">
    <strong>{item.name}</strong>
    <span>{item.code} · {item.category ?? '-'}</span>
    <span>stok {number.format(item.stock)} {item.unit}</span>
  </div>;
  return <section className="panel rapikan">
    <div className="panel-head">
      <h2><i className="fas fa-broom" aria-hidden="true" /> Rapikan data barang</h2>
      <button className="secondary" onClick={() => setOpen(!open)} aria-expanded={open}>
        {open ? 'Tutup' : suspects.length ? `Periksa ${suspects.length} pasangan` : 'Lihat'}</button>
    </div>
    <p>{suspects.length ? `Ada ${suspects.length} pasangan barang dengan nama sangat mirip yang mungkin dobel.` : 'Tidak ada barang yang terlihat dobel.'}
      {' '}{pairs.length - suspects.length > 0 && `${pairs.length - suspects.length} pasangan lain kemungkinan varian (warna, ukuran, tipe).`}</p>
    {message.text && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
    {open && <>
      <label className="rapikan-toggle"><input type="checkbox" checked={showVariants} onChange={(event) => setShowVariants(event.target.checked)} />
        Tampilkan juga pasangan yang kemungkinan varian</label>
      {!shown.length ? <p role="status">Tidak ada pasangan untuk diperiksa.</p> : <ul className="rapikan-list">
        {shown.map((pair) => {
          const sameUnit = pair.a.unit === pair.b.unit;
          return <li key={`${pair.a.id}-${pair.b.id}`} className={pair.variant ? 'variant' : undefined}>
            <div className="rapikan-pair">{card(pair.a)}<span className="rapikan-score" title="Kemiripan nama">{Math.round(pair.score * 100)}%</span>{card(pair.b)}</div>
            {pair.variant && <p className="rapikan-hint">Hanya beda kode pendek, kemungkinan memang barang berbeda.</p>}
            {!sameUnit && <p className="rapikan-hint">Satuan berbeda ({pair.a.unit} dan {pair.b.unit}). Untuk menggabung, samakan satuannya dulu lewat Ubah.</p>}
            <div className="form-actions">
              <button className="secondary" disabled={busy} onClick={() => ignore(pair)}>Varian berbeda, biarkan</button>
              <button className="secondary" disabled={busy} onClick={() => edit(pair.a.id)}>Ubah {pair.a.code}</button>
              <button className="secondary" disabled={busy} onClick={() => edit(pair.b.id)}>Ubah {pair.b.code}</button>
              {admin && sameUnit && <>
                <button className="danger" disabled={busy} onClick={() => merge(pair.a, pair.b)}>Gabung ke {pair.a.code}</button>
                <button className="danger" disabled={busy} onClick={() => merge(pair.b, pair.a)}>Gabung ke {pair.b.code}</button>
              </>}
            </div>
          </li>;
        })}
      </ul>}
    </>}
  </section>;
}
