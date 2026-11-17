import { lazy, Suspense, useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import { rpc } from './supabase';
import { ItemThumb, uploadItemPhoto } from './media';
import { ItemPicker } from './ItemPicker';

// Terima barang (batch): scan the barcode on each package (or search by name), fill in the quantities, save them all
// as barang masuk in one go. The photo comes with the barang, so a known barang shows its picture right away.
const BarcodeScanner = lazy(() => import('./BarcodeScanner').then((module) => ({ default: module.BarcodeScanner })));

type Option = { id: number; name: string };
type Unit = Option & { symbol: string };
export type BatchItem = { id: number; code: string; name: string; stock: number; unit: string; category?: string; photo?: string | null };
type Line = { item: BatchItem; quantity: string };

const number = new Intl.NumberFormat('id-ID');
const perolehan = ['APBD Balikpapan', 'BLUD', 'Dropping IFK/DKK - APBD Balikpapan', 'Dropping IFK/DKK - APBN', 'HIBAH OPD LAIN BALIKPAPAN'];
const today = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 10); // Asia/Jakarta
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();

export function TerimaBatch({ items, categories, units, onSaved, onCancel, onItemsChanged, autoScan = false }: {
  autoScan?: boolean; items: BatchItem[]; categories: Option[]; units: Unit[]; onSaved: (note: string) => void; onCancel: () => void; onItemsChanged: () => void;
}) {
  const [lines, setLines] = useState<Line[]>([]);
  const [scanning, setScanning] = useState(autoScan);
  const [unknown, setUnknown] = useState<string | null>(null); // a scanned barcode no barang has yet
  const [canCreate, setCanCreate] = useState(false);
  const [busy, setBusy] = useState(false);
  const [problem, setProblem] = useState('');
  const [note, setNote] = useState('');
  const focusId = useRef<number | null>(null);

  useEffect(() => { rpc<boolean>('simbar_can_edit_items').then(setCanCreate).catch(() => {}); }, []);
  useEffect(() => {
    if (focusId.current === null) return;
    document.getElementById('batch-qty-' + focusId.current)?.focus();
    focusId.current = null;
  }, [lines]);

  // A barang already in the list gets one more; a new one joins at the top with its quantity ready to type.
  const add = useCallback((item: BatchItem, scanned: boolean) => {
    setProblem('');
    setLines((current) => {
      const found = current.find((line) => line.item.id === item.id);
      if (!found) return [{ item, quantity: scanned ? '1' : '' }, ...current];
      return current.map((line) => line === found ? { ...line, quantity: String((Number(line.quantity) || 0) + 1) } : line);
    });
    focusId.current = item.id;
    setNote(item.name + ' masuk daftar.');
  }, []);

  const scanned = useCallback(async (code: string) => {
    setScanning(false);
    setProblem('');
    setNote('');
    try {
      const item = await rpc<BatchItem | null>('simbar_item_by_barcode', { scanned: code });
      if (item) add(item, true); else setUnknown(code);
    } catch (error) {
      setProblem((error as Error).message);
    }
  }, [add]);

  async function link(id: string) {
    const item = items.find((entry) => String(entry.id) === id);
    if (!item || !unknown) return;
    setBusy(true);
    try {
      await rpc('simbar_item_link_barcode', { item_ref: item.id, scanned: unknown });
      setUnknown(null);
      add(item, true);
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }

  async function create(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const file = data.get('photo');
    const unit = units.find((entry) => entry.id === Number(data.get('unit')));
    setBusy(true);
    setProblem('');
    try {
      const id = await rpc<number>('simbar_item_save', {
        item_id: null, item_code: text(data, 'code'), item_name: text(data, 'name'), item_category: Number(data.get('category')),
        item_unit: Number(data.get('unit')), item_perolehan: text(data, 'perolehan'), item_price: Number(data.get('price') || 0),
        item_minimum: 0, item_description: null, item_barcode: unknown, initial_stock: 0,
      });
      let photo: string | null = null;
      let photoProblem = '';
      if (file instanceof File && file.size > 0) {
        try {
          photo = await uploadItemPhoto(file);
          await rpc('simbar_item_set_photo', { item_ref: id, photo_url: photo, source_label: 'Foto asli' });
        } catch (error) {
          photo = null;
          photoProblem = ' Fotonya belum tersimpan (' + (error as Error).message + '), tambahkan lewat Data Barang.';
        }
      }
      setUnknown(null);
      onItemsChanged();
      add({ id, code: text(data, 'code'), name: text(data, 'name'), stock: 0, unit: unit?.symbol ?? '', photo }, true);
      if (photoProblem) setProblem('Barang baru dibuat.' + photoProblem);
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const bad = lines.find((line) => !/^[1-9][0-9]{0,6}$/.test(line.quantity));
    if (!lines.length) { setProblem('Daftar masih kosong. Scan atau cari barang dulu.'); return; }
    if (bad) { setProblem(`Isi jumlah ${bad.item.name} (minimal 1).`); focusId.current = bad.item.id; setLines([...lines]); return; }
    setBusy(true);
    setProblem('');
    try {
      const result = await rpc<{ lines: number; units: number }>('simbar_stock_in_batch', {
        movement_date: text(data, 'date'), movement_notes: text(data, 'notes') || null,
        lines: lines.map((line) => ({ item: line.item.id, quantity: Number(line.quantity) })),
      });
      onSaved(`${number.format(result.lines)} barang masuk stok, total ${number.format(result.units)} unit.`);
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setBusy(false);
    }
  }

  const total = lines.reduce((sum, line) => sum + (Number(line.quantity) || 0), 0);
  const inList = new Set(lines.map((line) => String(line.item.id)));

  return <div className="record-form batch-form">
    {scanning && <Suspense fallback={<div className="scanner"><p className="scanner-box" role="status">Membuka kamera…</p></div>}>
      <BarcodeScanner onFound={scanned} onClose={() => setScanning(false)} /></Suspense>}
    <h3>Terima barang (batch)</h3>
    {problem && <p className="notice wide" role="alert">{problem}</p>}
    {!problem && note && <p className="success wide" role="status">{note}</p>}

    {unknown ? <div className="wide batch-unknown">
      <p><strong>Barcode {unknown} belum dikenal.</strong> Pilih barangnya sekali saja, berikutnya langsung dikenali.</p>
      <label>Hubungkan ke barang yang sudah ada
        <ItemPicker items={items} value="" onChange={link} placeholder="Ketik nama atau kode barang…" /></label>
      {canCreate ? <form className="record-form" onSubmit={create}>
        <h3>Atau barang baru</h3>
        <label>Kode barang<input name="code" required maxLength={50} /></label>
        <label>Nama barang<input name="name" required maxLength={255} placeholder="Nama, merk dan ukuran" /></label>
        <label>Jenis barang<select name="category" required defaultValue=""><option value="" disabled>Pilih jenis</option>
          {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></label>
        <label>Satuan<select name="unit" required defaultValue=""><option value="" disabled>Pilih satuan</option>
          {units.map((unit) => <option key={unit.id} value={unit.id}>{unit.name} ({unit.symbol})</option>)}</select></label>
        <label>Perolehan<select name="perolehan" required defaultValue=""><option value="" disabled>Pilih perolehan</option>
          {perolehan.map((value) => <option key={value}>{value}</option>)}</select></label>
        <label>Harga satuan (Rp)<input name="price" type="number" min="0" step="any" defaultValue={0} /></label>
        <label className="wide">Foto barang (langsung dari kamera)<input name="photo" type="file" accept="image/*" capture="environment" /></label>
        <div className="form-actions wide">
          <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Buat dan masukkan ke daftar'}</button>
        </div>
      </form> : <p className="cell-note">Barang baru hanya bisa dibuat oleh yang punya hak "Boleh ubah barang".</p>}
      <div className="form-actions"><button className="secondary" type="button" onClick={() => setUnknown(null)}>Lewati barcode ini</button></div>
    </div> : <>
      <div className="wide form-actions">
        <button className="primary" type="button" onClick={() => { setNote(''); setScanning(true); }}>
          <i className="fas fa-barcode" aria-hidden="true" /> Scan barcode</button>
      </div>
      <label className="wide">Atau cari barang tanpa barcode
        <ItemPicker items={items} value="" disabledIds={inList} placeholder="Ketik nama, kode atau jenis barang…"
          onChange={(id) => { const item = items.find((entry) => String(entry.id) === id); if (item) add(item, false); }} /></label>
    </>}

    <form className="wide batch-lines" onSubmit={save}>
      <strong>{number.format(lines.length)} barang di daftar{total ? `, total ${number.format(total)} unit` : ''}</strong>
      {lines.length > 0 && <ul className="photo-list">{lines.map((line) => <li key={line.item.id}>
        <ItemThumb url={line.item.photo} name={line.item.name} />
        <div><strong>{line.item.name}</strong><span>{line.item.code} · stok sekarang {number.format(line.item.stock)} {line.item.unit}</span></div>
        <label className="batch-qty"><span className="sr-only">Jumlah {line.item.name}</span>
          <input id={'batch-qty-' + line.item.id} type="number" inputMode="numeric" min="1" max="9999999" step="1" required value={line.quantity}
            placeholder="Jumlah" onFocus={(event) => event.target.select()}
            onChange={(event) => setLines(lines.map((entry) => entry === line ? { ...entry, quantity: event.target.value } : entry))} />
          <span>{line.item.unit}</span></label>
        <button className="secondary" type="button" aria-label={'Hapus ' + line.item.name + ' dari daftar'}
          onClick={() => setLines(lines.filter((entry) => entry !== line))}>Hapus</button>
      </li>)}</ul>}
      <div className="batch-meta">
        <label>Tanggal masuk<input name="date" type="date" required max={today()} defaultValue={today()} /></label>
        <label>Keterangan (untuk semua baris)<input name="notes" maxLength={500} placeholder="Contoh: Dropping IFK November" /></label>
      </div>
      <div className="form-actions">
        <button className="primary" type="submit" disabled={busy || !lines.length || !!unknown}>{busy ? 'Menyimpan…' : 'Simpan semua ke stok'}</button>
        <button className="secondary" type="button" onClick={() => {
          if (!lines.length || confirm('Batalkan? Daftar barang yang sudah di-scan akan hilang.')) onCancel();
        }}>Batal</button>
      </div>
    </form>
  </div>;
}
