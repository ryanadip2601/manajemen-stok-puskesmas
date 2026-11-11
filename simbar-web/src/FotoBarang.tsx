import { useEffect, useState } from 'react';
import { ItemThumb, isRealPhoto, photoSourceLabel, photoThumb, uploadItemPhoto } from './media';
import { rpc } from './supabase';

// Foto barang: change one barang's photo (Ubah barang), and a panel to replace temporary photos with real ones, step by step.
type Item = { id: number; code: string; name: string; category?: string; photo?: string | null; photoSource?: string | null };

async function save(itemId: number, url: string | null, source: string) {
  await rpc('simbar_item_set_photo', { item_ref: itemId, photo_url: url, source_label: source });
}

export function ItemPhotoEditor({ itemId, name, photo, source, onChange }: {
  itemId: number; name: string; photo?: string | null; source?: string | null; onChange: (photo: string | null, source: string | null) => void;
}) {
  const [busy, setBusy] = useState(false);
  const [link, setLink] = useState('');
  const [problem, setProblem] = useState('');

  async function run(action: () => Promise<[string | null, string | null]>) {
    setBusy(true);
    setProblem('');
    try {
      const [url, label] = await action();
      onChange(url, label);
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }
  const upload = (file?: File) => file && run(async () => { const url = await uploadItemPhoto(file); await save(itemId, url, 'Foto asli'); return [url, 'Foto asli']; });
  const useLink = () => run(async () => { const url = link.trim(); await save(itemId, url, 'Link (sementara)'); setLink(''); return [url, 'Link (sementara)']; });
  const remove = () => confirm('Hapus foto barang ini?') && run(async () => { await save(itemId, null, ''); return [null, null]; });

  return <fieldset className="wide choice photo-field item-photo-editor">
    <legend>Foto barang</legend>
    <div className="item-photo-row">
      {photo ? <img className="item-photo-large" src={photoThumb(photo, 480)} alt={'Foto ' + name} referrerPolicy="no-referrer" />
        : <span className="item-thumb empty" style={{ width: 120, height: 120 }}><i className="fas fa-box" /></span>}
      <div>
        <p className={'item-photo-source' + (isRealPhoto(source) ? ' real' : '')}>{photoSourceLabel(source)}</p>
        {!isRealPhoto(source) && <p className="muted">Ganti dengan foto barang yang ada di gudang bila sempat.</p>}
        <div className="form-actions">
          <label className="file-button primary">{busy ? 'Mengunggah…' : isRealPhoto(source) ? 'Ganti foto' : 'Ambil / pilih foto asli'}
            <input type="file" accept="image/*" capture="environment" disabled={busy}
              onChange={(event) => { upload(event.target.files?.[0]); event.target.value = ''; }} /></label>
          {photo && <button className="secondary" type="button" disabled={busy} onClick={remove}>Hapus foto</button>}
        </div>
      </div>
    </div>
    <div className="filters">
      <input type="url" maxLength={2000} value={link} onChange={(event) => setLink(event.target.value)}
        placeholder="atau tempel link gambar https://…" aria-label="Link foto barang" />
      <button className="secondary" type="button" disabled={busy || !/^https:\/\/\S+$/.test(link.trim())} onClick={useLink}>Pakai link</button>
    </div>
    {problem && <p className="notice" role="alert">{problem}</p>}
  </fieldset>;
}

// Lists barang whose photo is still temporary, each with a one-tap "Foto asli" upload (works from a phone camera).
export function FotoAsliPanel({ version, refresh }: { version: number; refresh: () => void }) {
  const [items, setItems] = useState<Item[] | null>(null);
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState<number | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });

  useEffect(() => {
    let live = true;
    rpc<{ items: Item[] }>('simbar_stock_options').then((options) => { if (live) setItems(options.items); }).catch(() => {});
    return () => { live = false; };
  }, [version]);

  if (!items) return null;
  const temporary = items.filter((item) => !isRealPhoto(item.photoSource));
  const real = items.length - temporary.length;

  async function upload(item: Item, file?: File) {
    if (!file) return;
    setBusy(item.id);
    setMessage({ text: '', ok: true });
    try {
      const url = await uploadItemPhoto(file);
      await save(item.id, url, 'Foto asli');
      setItems((current) => current?.map((entry) => entry.id === item.id ? { ...entry, photo: url, photoSource: 'Foto asli' } : entry) ?? null);
      setMessage({ text: `Foto asli ${item.name} tersimpan.`, ok: true });
      refresh();
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(null);
    }
  }

  return <section className="panel foto-asli">
    <div className="panel-head">
      <h2><i className="fas fa-camera" aria-hidden="true" /> Lengkapi foto asli</h2>
      <button className="secondary" onClick={() => setOpen(!open)} aria-expanded={open}>{open ? 'Tutup' : `Lihat ${temporary.length} barang`}</button>
    </div>
    <div className="foto-progress" role="img" aria-label={`${real} dari ${items.length} barang sudah memakai foto asli`}>
      <span style={{ width: `${items.length ? (real / items.length) * 100 : 0}%` }} />
    </div>
    <p>{real} dari {items.length} barang sudah memakai foto asli. Sisanya masih foto sementara dan bisa diganti kapan saja, satu per satu.</p>
    {message.text && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
    {open && <ul className="foto-grid">{temporary.map((item) => <li key={item.id}>
      <ItemThumb url={item.photo} name={item.name} size={96} />
      <strong>{item.name}</strong>
      <span>{item.code} · {photoSourceLabel(item.photoSource)}</span>
      <label className="file-button primary">{busy === item.id ? 'Mengunggah…' : 'Foto asli'}
        <input type="file" accept="image/*" capture="environment" disabled={busy !== null}
          onChange={(event) => { upload(item, event.target.files?.[0]); event.target.value = ''; }} /></label>
    </li>)}</ul>}
  </section>;
}
