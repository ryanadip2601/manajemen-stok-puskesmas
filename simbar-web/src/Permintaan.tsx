import { useEffect, useState, type FormEvent } from 'react';
import { PhotoField, StoredPhoto } from './media';
import { rpc } from './supabase';

type Status = 'pending' | 'diproses' | 'disetujui' | 'ditolak' | 'revisi' | 'tersedia' | 'dibaca';
type Tipe = 'barang_baru' | 'restok';
type Prioritas = 'rendah' | 'sedang' | 'tinggi';
type Option = { id: number; name: string };
type ItemOption = Option & { code: string; stock: number; minimum: number; unit: string; low: boolean };
type Options = { rooms: Option[]; items: ItemOption[]; categories: Option[]; units: (Option & { symbol: string })[] };
type Row = {
  id: number; code: string; tipe: Tipe; status: Status; prioritas: Prioritas; jumlah: number; name: string; date: string;
  stock: number | null; unit: string | null; room: string | null; requested_by: string; verified_by: string | null;
};
type Page = { counts: Record<Status, number>; total: number; rows: Row[] };
type Detail = {
  id: number; code: string; tipe: Tipe; status: Status; itemId: number | null; name: string; roomId: number | null; room: string | null;
  jumlah: number; prioritas: Prioritas; keterangan: string | null; date: string; catatanAdmin: string | null;
  alasanPenolakan: string | null; permintaanRevisi: string | null; requestedBy: string; verifiedBy: string | null;
  foto: string | null; fotoRef: string | null; hasUploadedPhoto: boolean; mine: boolean;
  item: { name: string; code: string; stock: number; minimum: number; unit: string } | null;
  createdItem: { code: string; name: string } | null;
};
type Notice = { id: number; code: string; name: string; jumlah: number; stock: number };

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const statusLabels: Record<Status, string> = {
  pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak',
  revisi: 'Perlu revisi', tersedia: 'Barang tersedia', dibaca: 'Sudah dibaca',
};
const tipeLabels: Record<Tipe, string> = { barang_baru: 'Barang baru', restok: 'Restok' };
const prioritasLabels: Record<Prioritas, string> = { rendah: 'Rendah', sedang: 'Sedang', tinggi: 'Tinggi' };
const perolehan = ['APBD Balikpapan', 'BLUD', 'Dropping IFK/DKK - APBD Balikpapan', 'Dropping IFK/DKK - APBN', 'HIBAH OPD LAIN BALIKPAPAN'];
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const date = (value: string) => dateFormat.format(new Date(value.slice(0, 10)));

// Google/Bing image result links carry the real picture in a query parameter (normalizeFotoUrl in Laravel).
function normalizePhotoUrl(value: string) {
  try {
    const url = new URL(value.trim());
    for (const key of ['imgurl', 'mediaurl', 'url']) {
      const inner = url.searchParams.get(key);
      if (inner && /^https?:\/\//.test(inner)) return inner;
    }
    return url.href;
  } catch {
    return value.trim();
  }
}

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

function useList(mine: boolean, status: 'all' | Status, tipe: 'all' | Tipe, search: string, page: number, version: number) {
  const [state, setState] = useState<{ data?: Page; error?: string }>({});
  useEffect(() => {
    let active = true;
    setState({});
    rpc<Page>('simbar_permintaan_list', { mine_only: mine, status_filter: status, tipe_filter: tipe, search_text: search, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [mine, status, tipe, search, page, version]);
  return state;
}

function useOptions() {
  const [options, setOptions] = useState<Options | null>(null);
  useEffect(() => {
    let active = true;
    rpc<Options>('simbar_permintaan_options').then((value) => active && setOptions(value)).catch(() => {});
    return () => { active = false; };
  }, []);
  return options;
}

export function RestockNotices() {
  const [notices, setNotices] = useState<Notice[]>([]);
  useEffect(() => {
    let active = true;
    rpc<Notice[]>('simbar_permintaan_notifications').then((value) => active && setNotices(value)).catch(() => {});
    return () => { active = false; };
  }, []);
  if (!notices.length) return null;
  return <section className="success notice-box" role="status">
    <strong>Barang restok Anda sudah tersedia</strong>
    <ul>{notices.map((notice) => <li key={notice.id}>{notice.name} ({notice.code}) — stok sekarang {number.format(notice.stock)}</li>)}</ul>
    <button className="secondary" onClick={() => rpc('simbar_permintaan_mark_read').then(() => setNotices([])).catch(() => {})}>Tandai sudah dibaca</button>
  </section>;
}

function Filters({ counts, status, setStatus, tipe, setTipe, onSearch }: {
  counts?: Record<Status, number>; status: 'all' | Status; setStatus: (value: 'all' | Status) => void;
  tipe: 'all' | Tipe; setTipe: (value: 'all' | Tipe) => void; onSearch: (value: string) => void;
}) {
  const [draft, setDraft] = useState('');
  return <>
    <div className="filters" role="group" aria-label="Status permintaan">
      {(['all', 'pending', 'diproses', 'revisi', 'disetujui', 'tersedia', 'ditolak'] as const).map((value) =>
        <button key={value} className="secondary" aria-pressed={status === value} onClick={() => setStatus(value)}>
          {value === 'all' ? 'Semua' : statusLabels[value]}{counts && value !== 'all' ? ` (${number.format(counts[value])})` : ''}</button>)}
    </div>
    <form className="filters" role="search" onSubmit={(event) => { event.preventDefault(); onSearch(draft.trim()); }}>
      <label className="sr-only" htmlFor="permintaan-search">Cari permintaan</label>
      <input id="permintaan-search" type="search" maxLength={100} value={draft} onChange={(event) => setDraft(event.target.value)}
        placeholder="Kode, nama barang, atau pemohon" />
      <select aria-label="Jenis permintaan" value={tipe} onChange={(event) => setTipe(event.target.value as 'all' | Tipe)}>
        <option value="all">Semua jenis</option><option value="restok">Restok</option><option value="barang_baru">Barang baru</option>
      </select>
      <button className="primary" type="submit">Cari</button>
    </form>
  </>;
}

function Table({ page, mine, open }: { page: Page; mine: boolean; open: (id: number) => void }) {
  return <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel permintaan barang">
    <table>
      <thead><tr><th scope="col">Barang</th><th scope="col">Jenis</th><th scope="col">Jumlah</th>
        {!mine && <th scope="col">Pemohon</th>}<th scope="col">Tanggal</th><th scope="col">Status</th>
        <th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
      <tbody>{page.rows.map((row) => <tr key={row.id}>
        <th scope="row"><strong>{row.name}</strong><span>{row.code}{row.room ? ' · ' + row.room : ''}</span></th>
        <td>{tipeLabels[row.tipe]}<span className="cell-note">Prioritas {prioritasLabels[row.prioritas].toLowerCase()}</span></td>
        <td>{number.format(row.jumlah)} {row.unit ?? ''}{row.stock !== null && <span className="cell-note">stok {number.format(row.stock)}</span>}</td>
        {!mine && <td>{row.requested_by}</td>}
        <td>{date(row.date)}</td>
        <td><span className={'badge ' + row.status}>{statusLabels[row.status]}</span></td>
        <td><button className="secondary" onClick={() => open(row.id)}>Detail</button></td>
      </tr>)}</tbody>
    </table>
  </div>;
}

function Pages({ page, total, go }: { page: number; total: number; go: (page: number) => void }) {
  const last = Math.max(1, Math.ceil(total / perPage));
  return <nav className="inventory-pages" aria-label="Halaman permintaan">
    <button className="secondary" disabled={page <= 1} onClick={() => go(page - 1)}>Sebelumnya</button>
    <span>Halaman {number.format(page)} dari {number.format(last)}</span>
    <button className="secondary" disabled={page >= last} onClick={() => go(page + 1)}>Berikutnya</button>
  </nav>;
}

function DetailView({ detail }: { detail: Detail }) {
  return <dl className="detail-list">
    <div><dt>Kode</dt><dd>{detail.code}</dd></div>
    <div><dt>Jenis</dt><dd>{tipeLabels[detail.tipe]}</dd></div>
    <div><dt>Status</dt><dd><span className={'badge ' + detail.status}>{statusLabels[detail.status]}</span></dd></div>
    <div><dt>Barang</dt><dd>{detail.name}{detail.item && ` (${detail.item.code}) — stok ${number.format(detail.item.stock)} ${detail.item.unit}, minimum ${number.format(detail.item.minimum)}`}</dd></div>
    <div><dt>Jumlah</dt><dd>{number.format(detail.jumlah)}</dd></div>
    <div><dt>Prioritas</dt><dd>{prioritasLabels[detail.prioritas]}</dd></div>
    {detail.room && <div><dt>Ruangan</dt><dd>{detail.room}</dd></div>}
    <div><dt>Pemohon</dt><dd>{detail.requestedBy} · {date(detail.date)}</dd></div>
    {detail.keterangan && <div className="wide"><dt>Keterangan</dt><dd>{detail.keterangan}</dd></div>}
    {detail.permintaanRevisi && <div className="wide"><dt>Permintaan revisi</dt><dd>{detail.permintaanRevisi}</dd></div>}
    {detail.alasanPenolakan && <div className="wide"><dt>Alasan penolakan</dt><dd>{detail.alasanPenolakan}</dd></div>}
    {detail.catatanAdmin && <div className="wide"><dt>Catatan admin</dt><dd>{detail.catatanAdmin}</dd></div>}
    {detail.verifiedBy && <div><dt>Diverifikasi oleh</dt><dd>{detail.verifiedBy}</dd></div>}
    {detail.createdItem && <div><dt>Data barang dibuat</dt><dd>{detail.createdItem.name} ({detail.createdItem.code})</dd></div>}
    {(detail.foto ?? detail.fotoRef) && <div className="wide"><dt>Foto barang</dt><dd>
      <StoredPhoto reference={(detail.foto ?? detail.fotoRef)!} alt={'Foto ' + detail.name} /></dd></div>}
    {detail.hasUploadedPhoto && <div className="wide"><dt>Foto barang</dt><dd>Foto unggahan lama belum dipindahkan.</dd></div>}
  </dl>;
}

function RequestForm({ options, editing, busy, onSubmit, onCancel }: {
  options: Options; editing: Detail | null; busy: boolean;
  onSubmit: (values: Record<string, unknown>) => void; onCancel: () => void;
}) {
  const [tipe, setTipe] = useState<Tipe>(editing?.tipe ?? 'restok');
  const original = editing?.foto ?? editing?.fotoRef ?? null;
  const [photo, setPhoto] = useState<string | null>(original);
  const restockable = options.items.filter((item) => item.low || item.id === editing?.itemId);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    onSubmit({
      permintaan_id: editing?.id ?? null, request_tipe: tipe,
      item_ref: tipe === 'restok' ? Number(data.get('item')) : null,
      request_name: tipe === 'barang_baru' ? text(data, 'name') : null,
      room_ref: tipe === 'barang_baru' && data.get('room') ? Number(data.get('room')) : null,
      request_jumlah: Number(data.get('jumlah')), request_prioritas: text(data, 'prioritas'),
      request_keterangan: text(data, 'keterangan'),
      foto_url: photo && photo !== original ? (photo.startsWith('media:') ? photo : normalizePhotoUrl(photo)) : null,
      remove_photo: !photo && original !== null,
    });
  }

  return <form className="record-form" onSubmit={submit}>
    <h3>{editing ? 'Perbarui ' + editing.code : 'Permintaan baru'}</h3>
    {editing?.permintaanRevisi && <p className="notice wide">Revisi diminta: {editing.permintaanRevisi}</p>}
    <fieldset className="wide choice">
      <legend>Jenis permintaan</legend>
      <label><input type="radio" name="tipe" checked={tipe === 'restok'} onChange={() => setTipe('restok')} /> Restok barang yang habis/menipis</label>
      <label><input type="radio" name="tipe" checked={tipe === 'barang_baru'} onChange={() => setTipe('barang_baru')} /> Barang baru (belum ada di daftar)</label>
    </fieldset>
    {tipe === 'restok' ? <label className="wide">Barang<select name="item" required defaultValue={editing?.itemId ?? ''}>
      <option value="" disabled>{restockable.length ? 'Pilih barang' : 'Tidak ada barang yang stoknya menipis'}</option>
      {restockable.map((item) => <option key={item.id} value={item.id}>
        {item.name} ({item.code}) — stok {number.format(item.stock)} {item.unit}, minimum {number.format(item.minimum)}</option>)}
    </select></label> : <>
      <label>Nama barang<input name="name" required maxLength={255} defaultValue={editing?.tipe === 'barang_baru' ? editing.name : ''} /></label>
      <label>Ruangan (opsional)<select name="room" defaultValue={editing?.roomId ?? ''}>
        <option value="">Tidak ditentukan</option>
        {options.rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
      </select></label>
    </>}
    <label>Jumlah<input name="jumlah" type="number" min="1" max={tipe === 'restok' ? 99999 : 9999} step="1" required defaultValue={editing?.jumlah ?? 1} /></label>
    <label>Prioritas<select name="prioritas" required defaultValue={editing?.prioritas ?? 'sedang'}>
      <option value="rendah">Rendah</option><option value="sedang">Sedang</option><option value="tinggi">Tinggi</option>
    </select></label>
    <label className="wide">Keterangan<textarea name="keterangan" rows={2} maxLength={2000} defaultValue={editing?.keterangan ?? ''} /></label>
    <PhotoField label="Foto barang (opsional)" value={photo} onChange={setPhoto} allowLink />
    <div className="form-actions wide">
      <button className="primary" type="submit" disabled={busy}>{busy ? 'Mengirim…' : editing ? 'Kirim ulang' : 'Ajukan'}</button>
      <button className="secondary" type="button" onClick={onCancel}>Batal</button>
    </div>
  </form>;
}

// Opened from Pengajuan: `start` opens the form, `openId` opens one request, `onBack` returns to the combined list.
export function MyPermintaan({ start, openId, onBack }: { start?: boolean; openId?: number; onBack?: () => void } = {}) {
  const options = useOptions();
  const [status, setStatus] = useState<'all' | Status>('all');
  const [tipe, setTipe] = useState<'all' | Tipe>('all');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [view, setView] = useState<{ mode: 'new' } | { mode: 'detail' | 'edit'; detail: Detail } | null>(start ? { mode: 'new' } : null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useList(true, status, tipe, search, page, version);

  useEffect(() => { if (openId) open(openId); }, [openId]); // eslint-disable-line react-hooks/exhaustive-deps

  async function open(id: number) {
    try {
      setView({ mode: 'detail', detail: await rpc<Detail>('simbar_permintaan_get', { permintaan_id: id }) });
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function act(action: () => Promise<unknown>, success: (result: unknown) => string) {
    setBusy(true);
    try {
      const result = await action();
      setMessage({ text: success(result), ok: true });
      setView(null);
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  return <section className="panel">
      {onBack && <button className="secondary back-link" onClick={onBack}><i className="fas fa-arrow-left" aria-hidden="true" /> Semua pengajuan</button>}
    <div className="panel-head">
      <h2>Permintaan barang</h2>
      {!view && <button className="primary" disabled={!options} onClick={() => { setMessage({ text: '', ok: true }); setView({ mode: 'new' }); }}>Ajukan permintaan</button>}
    </div>
    <p>Minta restok barang yang habis atau menipis, atau usulkan barang baru ke Admin Gudang.</p>
    <Message value={message} />
    {view && options && view.mode !== 'detail' && <RequestForm options={options} busy={busy} editing={view.mode === 'edit' ? view.detail : null}
      onCancel={() => setView(null)} onSubmit={(values) => act(() => rpc<string>('simbar_permintaan_save', values),
        (code) => view.mode === 'edit' ? 'Permintaan berhasil diperbarui.' : 'Permintaan berhasil diajukan dengan kode: ' + code)} />}
    {view?.mode === 'detail' && <div className="record-form">
      <h3>{view.detail.code}</h3>
      <DetailView detail={view.detail} />
      <div className="form-actions wide">
        {['pending', 'revisi'].includes(view.detail.status) && <>
          <button className="primary" onClick={() => setView({ mode: 'edit', detail: view.detail })}>Ubah</button>
          <button className="danger" disabled={busy} onClick={() => {
            if (confirm(`Batalkan permintaan ${view.detail.code}?`)) {
              act(() => rpc('simbar_permintaan_cancel', { permintaan_id: view.detail.id }), () => 'Permintaan berhasil dibatalkan.');
            }
          }}>Batalkan permintaan</button>
        </>}
        <button className="secondary" onClick={() => setView(null)}>Tutup</button>
      </div>
    </div>}
    <Filters counts={state.data?.counts} status={status} setStatus={(value) => { setStatus(value); setPage(1); }}
      tipe={tipe} setTipe={(value) => { setTipe(value); setPage(1); }} onSearch={(value) => { setSearch(value); setPage(1); }} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat permintaan…</p>
      : !state.data.total ? <p role="status">Belum ada permintaan.</p> : <>
        <Table page={state.data} mine open={open} />
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}

// Opened from Verifikasi: `openId` opens one request, `onBack` returns to the queue.
export function AdminPermintaan({ openId, onBack }: { openId?: number; onBack?: () => void } = {}) {
  const options = useOptions();
  const [status, setStatus] = useState<'all' | Status>('pending');
  const [tipe, setTipe] = useState<'all' | Tipe>('all');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [detail, setDetail] = useState<Detail | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useList(false, status, tipe, search, page, version);

  useEffect(() => { if (openId) open(openId); }, [openId]); // eslint-disable-line react-hooks/exhaustive-deps

  async function open(id: number) {
    setMessage({ text: '', ok: true });
    try {
      setDetail(await rpc<Detail>('simbar_permintaan_get', { permintaan_id: id }));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function act(action: string, note: string | null, newItem: Record<string, unknown> | null, success: string) {
    if (!detail) return;
    setBusy(true);
    try {
      const code = await rpc<string | null>('simbar_permintaan_act', { permintaan_id: detail.id, request_action: action, note, new_item: newItem });
      setMessage({ text: success + (code ? ' Kode barang: ' + code : ''), ok: true });
      setDetail(null);
      setVersion((value) => value + 1);
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  function approve(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!detail) return;
    const data = new FormData(event.currentTarget);
    const newItem = detail.tipe === 'barang_baru' ? {
      code: text(data, 'code'), category_id: Number(data.get('category')), unit_id: Number(data.get('unit')),
      price: Number(data.get('price')), perolehan: text(data, 'perolehan'), minimum_stock: Number(data.get('minimum')),
    } : null;
    act('approve', text(data, 'catatan') || null, newItem, detail.tipe === 'barang_baru'
      ? 'Permintaan disetujui dan data barang dibuat.'
      : 'Permintaan restok disetujui. Barang akan ditandai tersedia ketika stok masuk.');
  }

  function ask(action: 'reject' | 'revision') {
    const note = prompt(action === 'reject' ? 'Alasan penolakan (wajib):' : 'Apa yang perlu direvisi pengusul? (wajib)', '');
    if (note === null) return;
    act(action, note, null, action === 'reject' ? 'Permintaan ditolak.' : 'Permintaan revisi telah dikirim ke pengusul.');
  }

  const open_ = detail && ['pending', 'diproses'].includes(detail.status);

  return <section className="panel">
    {onBack && <button className="secondary back-link" onClick={onBack}><i className="fas fa-arrow-left" aria-hidden="true" /> Kembali ke antrean</button>}
    <h2>Verifikasi permintaan barang</h2>
    <p>Proses, setujui, tolak, atau minta revisi permintaan dari petugas.</p>
    <Message value={message} />
    {detail && <div className="record-form">
      <h3>{detail.code}</h3>
      <DetailView detail={detail} />
      {open_ && <form className="approve-form wide" onSubmit={approve}>
        {detail.tipe === 'barang_baru' && options && <>
          <p className="wide">Lengkapi data barang. Stok awal diisi dengan jumlah yang diminta ({number.format(detail.jumlah)}).</p>
          <label>Kode barang<input name="code" required maxLength={50} /></label>
          <label>Jenis barang<select name="category" required defaultValue="">
            <option value="" disabled>Pilih jenis</option>
            {options.categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
          </select></label>
          <label>Satuan<select name="unit" required defaultValue="">
            <option value="" disabled>Pilih satuan</option>
            {options.units.map((unit) => <option key={unit.id} value={unit.id}>{unit.name} ({unit.symbol})</option>)}
          </select></label>
          <label>Perolehan<select name="perolehan" required defaultValue="">
            <option value="" disabled>Pilih perolehan</option>{perolehan.map((value) => <option key={value}>{value}</option>)}
          </select></label>
          <label>Harga satuan (Rp)<input name="price" type="number" min="0" step="any" required defaultValue={0} /></label>
          <label>Stok minimum<input name="minimum" type="number" min="0" step="1" required defaultValue={5} /></label>
        </>}
        <label className="wide">Catatan admin (opsional)<textarea name="catatan" rows={2} maxLength={1000} /></label>
        <div className="form-actions wide">
          <button className="primary" type="submit" disabled={busy}>Setujui</button>
          {detail.status === 'pending' && <button className="secondary" type="button" disabled={busy}
            onClick={() => act('proses', null, null, 'Permintaan sedang diproses.')}>Tandai diproses</button>}
          <button className="secondary" type="button" disabled={busy} onClick={() => ask('revision')}>Minta revisi</button>
          <button className="danger" type="button" disabled={busy} onClick={() => ask('reject')}>Tolak</button>
        </div>
      </form>}
      <div className="form-actions wide"><button className="secondary" onClick={() => setDetail(null)}>Tutup</button></div>
    </div>}
    <Filters counts={state.data?.counts} status={status} setStatus={(value) => { setStatus(value); setPage(1); }}
      tipe={tipe} setTipe={(value) => { setTipe(value); setPage(1); }} onSearch={(value) => { setSearch(value); setPage(1); }} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat permintaan…</p>
      : !state.data.total ? <p role="status">Tidak ada permintaan.</p> : <>
        <Table page={state.data} mine={false} open={open} />
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}
