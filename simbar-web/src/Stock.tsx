import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { rpc } from './supabase';
import { ItemPicker } from './ItemPicker';
import { ItemPhotoEditor } from './FotoBarang';

type Option = { id: number; name: string };
type Category = Option & { description: string | null; items: number };
type Unit = Option & { symbol: string };
type ItemOption = Option & { code: string; stock: number; unit: string; category?: string };
type Options = { categories: Category[]; units: Unit[]; items: ItemOption[] };
type ItemDetail = {
  photo?: string | null; photoSource?: string | null;
  id: number; code: string; name: string; categoryId: number; unitId: number; description: string | null;
  perolehan: string; price: number; stock: number; minimumStock: number; barcode: string | null;
};
type Movement = {
  id: number; date: string; quantity: number; notes: string | null; item_id: number;
  code: string; name: string; unit: string; recorded_by: string | null;
};
type MovementPage = { total: number; quantity: number; rows: Movement[] };

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const perolehan = ['APBD Balikpapan', 'BLUD', 'Dropping IFK/DKK - APBD Balikpapan', 'Dropping IFK/DKK - APBN', 'HIBAH OPD LAIN BALIKPAPAN'];
const today = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 10); // Asia/Jakarta
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();

function useOptions(): [Options | undefined, string, () => void] {
  const [state, setState] = useState<{ options?: Options; error?: string }>({});
  const [version, setVersion] = useState(0);
  useEffect(() => {
    let active = true;
    rpc<Options>('simbar_stock_options').then((options) => active && setState({ options }))
      .catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [version]);
  return [state.options, state.error ?? '', () => setVersion((value) => value + 1)];
}

function Status({ message, tone }: { message: string; tone: 'ok' | 'error' }) {
  return message ? <p className={tone === 'ok' ? 'success' : 'notice'} role={tone === 'ok' ? 'status' : 'alert'}>{message}</p> : null;
}

// Data Barang for people who may change barang: the shared list (passed in) plus the add/edit form and jenis barang.
export function Catalog({ list }: { list: (edit: (id: number) => void, version: number, add: ReactNode, refresh: () => void) => ReactNode }) {
  const [options, loadError, reload] = useOptions();
  const [editing, setEditing] = useState<ItemDetail | 'new' | null>(null);
  const [message, setMessage] = useState<{ text: string; tone: 'ok' | 'error' }>({ text: '', tone: 'ok' });
  const [busy, setBusy] = useState(false);
  const [version, setVersion] = useState(0);

  async function run(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, tone: 'ok' });
      setEditing(null);
      reload();
      setVersion((v) => v + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, tone: 'error' });
    } finally {
      setBusy(false);
    }
  }

  async function edit(id: number) {
    setMessage({ text: '', tone: 'ok' });
    try {
      setEditing(await rpc<ItemDetail>('simbar_item_get', { item_id: id }));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
      setMessage({ text: (error as Error).message, tone: 'error' });
    }
  }

  function saveItem(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const id = editing === 'new' ? null : editing?.id ?? null;
    run(() => rpc('simbar_item_save', {
      item_id: id, item_code: text(data, 'code'), item_name: text(data, 'name'),
      item_category: Number(data.get('category')), item_unit: Number(data.get('unit')),
      item_perolehan: text(data, 'perolehan'), item_price: Number(data.get('price')),
      item_minimum: Number(data.get('minimum')), item_description: text(data, 'description'),
      item_barcode: text(data, 'barcode'), initial_stock: id ? 0 : Number(data.get('stock') || 0),
    }), id ? 'Barang berhasil diupdate.' : 'Barang berhasil dibuat.');
  }

  function saveCategory(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const name = text(new FormData(form), 'category');
    run(() => rpc('simbar_category_save', { category_id: null, category_name: name }), 'Jenis barang ditambahkan.')
      .then(() => form.reset());
  }

  if (loadError) return <section className="panel"><h2>Data barang</h2><Status message={loadError} tone="error" /></section>;
  if (!options) return <p role="status">Memuat data barang…</p>;

  const draft = editing === 'new' ? null : editing;
  const add = <button className="primary" onClick={() => { setMessage({ text: '', tone: 'ok' }); setEditing('new'); }}>
    <i className="fas fa-plus" aria-hidden="true" /> Tambah barang</button>;

  return <>
    {(editing || message.text) && <section className="panel">
      <Status message={message.text} tone={message.tone} />
      {editing && <form className="record-form" onSubmit={saveItem} key={draft?.id ?? 'new'}>
        <h3>{draft ? 'Ubah ' + draft.name : 'Barang baru'}</h3>
        {draft && <ItemPhotoEditor itemId={draft.id} name={draft.name} photo={draft.photo} source={draft.photoSource}
          onChange={(photo, photoSource) => { setEditing({ ...draft, photo, photoSource }); setVersion((v) => v + 1); }} />}
        <label>Kode barang<input name="code" required maxLength={50} defaultValue={draft?.code} /></label>
        <label>Nama barang<input name="name" required maxLength={255} defaultValue={draft?.name} /></label>
        <label>Jenis barang<select name="category" required defaultValue={draft?.categoryId ?? ''}>
          <option value="" disabled>Pilih jenis</option>
          {options.categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
        </select></label>
        <label>Satuan<select name="unit" required defaultValue={draft?.unitId ?? ''}>
          <option value="" disabled>Pilih satuan</option>
          {options.units.map((unit) => <option key={unit.id} value={unit.id}>{unit.name} ({unit.symbol})</option>)}
        </select></label>
        <label>Perolehan<select name="perolehan" required defaultValue={draft?.perolehan ?? ''}>
          <option value="" disabled>Pilih perolehan</option>
          {[...new Set([...perolehan, ...(draft?.perolehan ? [draft.perolehan] : [])])].map((value) => <option key={value}>{value}</option>)}
        </select></label>
        <label>Harga satuan (Rp)<input name="price" type="number" min="0" step="any" required defaultValue={draft?.price ?? 0} /></label>
        {!draft && <label>Stok awal<input name="stock" type="number" min="0" step="1" defaultValue={0} /></label>}
        <label>Stok minimum<input name="minimum" type="number" min="0" step="1" required defaultValue={draft?.minimumStock ?? 0} /></label>
        <label>Barcode<input name="barcode" maxLength={255} defaultValue={draft?.barcode ?? ''} /></label>
        <label className="wide">Keterangan<textarea name="description" rows={2} defaultValue={draft?.description ?? ''} /></label>
        <div className="form-actions wide">
          <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan'}</button>
          <button className="secondary" type="button" onClick={() => setEditing(null)}>Batal</button>
          {draft && <button className="danger" type="button" disabled={busy} onClick={() => {
            if (confirm(`Hapus barang "${draft.name}"?`)) run(() => rpc('simbar_item_delete', { item_id: draft.id }), 'Barang dihapus.');
          }}>Hapus barang</button>}
        </div>
      </form>}
    </section>}
    {list(edit, version, add, () => { reload(); setVersion((v) => v + 1); })}
    <section className="panel">
      <h2>Jenis barang</h2>
      <form className="filters" onSubmit={saveCategory}>
        <label className="sr-only" htmlFor="new-category">Jenis barang baru</label>
        <input id="new-category" name="category" required maxLength={255} placeholder="Jenis barang baru" />
        <button className="primary" type="submit" disabled={busy}>Tambah</button>
      </form>
      <ul className="photo-list">{options.categories.map((category) => <li key={category.id}>
        <div><strong>{category.name}</strong><span>{number.format(category.items)} barang</span></div>
        <button className="secondary" disabled={busy || category.items > 0} onClick={() => {
          if (confirm(`Hapus jenis "${category.name}"?`)) run(() => rpc('simbar_category_delete', { category_id: category.id }), 'Jenis barang dihapus.');
        }}>Hapus</button>
      </li>)}</ul>
    </section>
  </>;
}

export function Movements({ kind }: { kind: 'in' | 'out' }) {
  const label = kind === 'in' ? 'Barang masuk' : 'Barang keluar';
  const [options, loadError, reloadOptions] = useOptions();
  const [filter, setFilter] = useState({ item: '', from: '', to: '', page: 1 });
  const [state, setState] = useState<{ data?: MovementPage; error?: string }>({});
  const [version, setVersion] = useState(0);
  const [editing, setEditing] = useState<Movement | 'new' | null>(null);
  const [message, setMessage] = useState<{ text: string; tone: 'ok' | 'error' }>({ text: '', tone: 'ok' });
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    let active = true;
    setState({});
    rpc<MovementPage>('simbar_stock_list', {
      movement_kind: kind, item_filter: filter.item ? Number(filter.item) : null,
      date_from: filter.from || null, date_to: filter.to || null, page_number: filter.page,
    }).then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [kind, filter, version]);

  async function run(action: () => Promise<unknown>, success: string) {
    setBusy(true);
    try {
      await action();
      setMessage({ text: success, tone: 'ok' });
      setEditing(null);
      setVersion((value) => value + 1);
      reloadOptions();
    } catch (error) {
      setMessage({ text: (error as Error).message, tone: 'error' });
    } finally {
      setBusy(false);
    }
  }

  function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const current = editing === 'new' ? null : editing;
    run(() => rpc('simbar_stock_save', {
      movement_kind: kind, movement_id: current?.id ?? null,
      item_ref: current ? current.item_id : Number(data.get('item')),
      movement_quantity: Number(data.get('quantity')), movement_date: text(data, 'date'), movement_notes: text(data, 'notes'),
    }), current ? label + ' berhasil diupdate.' : label + ' berhasil ditambahkan.');
  }

  function applyFilter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    setFilter({ item: text(data, 'item'), from: text(data, 'from'), to: text(data, 'to'), page: 1 });
  }

  if (loadError) return <section className="panel"><h2>{label}</h2><Status message={loadError} tone="error" /></section>;
  if (!options) return <p role="status">Memuat {label.toLowerCase()}…</p>;

  const current = editing === 'new' ? null : editing;
  const total = state.data?.total ?? 0;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const filtered = filter.item || filter.from || filter.to;

  return <section className="panel">
    <div className="panel-head">
      <h2>{label}</h2>
      <button className="primary" onClick={() => { setMessage({ text: '', tone: 'ok' }); setEditing('new'); }}>Catat {label.toLowerCase()}</button>
    </div>
    <Status message={message.text} tone={message.tone} />
    {editing && <form className="record-form" onSubmit={save} key={current?.id ?? 'new'}>
      <h3>{current ? 'Ubah transaksi ' + current.name : label + ' baru'}</h3>
      {current ? <p className="wide">{current.name} · {current.code}</p>
        : <label className="wide">Barang<ItemPicker name="item" required items={options.items} /></label>}
      <label>Jumlah<input name="quantity" type="number" min="1" step="1" required defaultValue={current?.quantity ?? ''} /></label>
      <label>Tanggal<input name="date" type="date" required defaultValue={current?.date ?? today()} /></label>
      <label className="wide">Keterangan<textarea name="notes" rows={2} defaultValue={current?.notes ?? ''} /></label>
      <div className="form-actions wide">
        <button className="primary" type="submit" disabled={busy}>{busy ? 'Menyimpan…' : 'Simpan'}</button>
        <button className="secondary" type="button" onClick={() => setEditing(null)}>Batal</button>
        {current && <button className="danger" type="button" disabled={busy} onClick={() => {
          if (confirm(`Hapus ${label.toLowerCase()} ${current.name} sebanyak ${current.quantity}? Stok akan disesuaikan.`)) {
            run(() => rpc('simbar_stock_delete', { movement_kind: kind, movement_id: current.id }), label + ' dihapus.');
          }
        }}>Hapus transaksi</button>}
      </div>
    </form>}
    <form className="movement-filter" onSubmit={applyFilter} key={filtered ? 'filtered' : 'all'}>
      <label>Barang<ItemPicker name="item" defaultValue={filter.item} allLabel="Semua barang" items={options.items} /></label>
      <label>Dari<input name="from" type="date" defaultValue={filter.from} /></label>
      <label>Sampai<input name="to" type="date" defaultValue={filter.to} /></label>
      <div className="form-actions">
        <button className="primary" type="submit">Terapkan</button>
        {filtered && <button className="secondary" type="button" onClick={() => setFilter({ item: '', from: '', to: '', page: 1 })}>Hapus filter</button>}
      </div>
    </form>
    {state.error ? <Status message={state.error} tone="error" /> : !state.data ? <p role="status">Memuat transaksi…</p>
      : !total ? <p role="status">Belum ada transaksi.</p> : <>
        <p role="status">{number.format(total)} transaksi, total {number.format(state.data.quantity)} unit.</p>
        <div className="inventory-scroll" tabIndex={0} role="region" aria-label={'Tabel ' + label.toLowerCase()}>
          <table>
            <thead><tr><th scope="col">Barang</th><th scope="col">Tanggal</th><th scope="col">Jumlah</th>
              <th scope="col">Keterangan</th><th scope="col">Dicatat oleh</th><th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
            <tbody>{state.data.rows.map((row) => <tr key={row.id}>
              <th scope="row"><strong>{row.name}</strong><span>{row.code}</span></th>
              <td>{dateFormat.format(new Date(row.date))}</td>
              <td>{number.format(row.quantity)} {row.unit}</td>
              <td>{row.notes ?? '—'}</td>
              <td>{row.recorded_by ?? '—'}</td>
              <td><button className="secondary" onClick={() => { setMessage({ text: '', tone: 'ok' }); setEditing(row); }}>Ubah</button></td>
            </tr>)}</tbody>
          </table>
        </div>
        <nav className="inventory-pages" aria-label="Halaman transaksi">
          <button className="secondary" disabled={filter.page <= 1} onClick={() => setFilter({ ...filter, page: filter.page - 1 })}>Sebelumnya</button>
          <span>Halaman {number.format(filter.page)} dari {number.format(lastPage)}</span>
          <button className="secondary" disabled={filter.page >= lastPage} onClick={() => setFilter({ ...filter, page: filter.page + 1 })}>Berikutnya</button>
        </nav>
      </>}
  </section>;
}

// Mutasi Barang: barang masuk and barang keluar on one page, one tab per right the role has.
export function Mutasi({ allowed, initial }: { allowed: ('in' | 'out')[]; initial?: 'in' | 'out' }) {
  const [tab, setTab] = useState<'in' | 'out'>(initial && allowed.includes(initial) ? initial : allowed[0] ?? 'in');
  const shown = allowed.includes(tab) ? tab : allowed[0];
  if (!shown) return <section className="panel"><h2>Mutasi barang</h2><p role="status">Tidak ada akses mutasi untuk peran ini.</p></section>;
  return <>
    {allowed.length > 1 && <div className="mutasi-tabs" role="group" aria-label="Jenis mutasi">
      <button className="secondary" aria-pressed={shown === 'in'} onClick={() => setTab('in')}>
        <i className="fas fa-arrow-down" aria-hidden="true" /> Masuk</button>
      <button className="secondary" aria-pressed={shown === 'out'} onClick={() => setTab('out')}>
        <i className="fas fa-arrow-up" aria-hidden="true" /> Keluar</button>
    </div>}
    <Movements key={shown} kind={shown} />
  </>;
}
