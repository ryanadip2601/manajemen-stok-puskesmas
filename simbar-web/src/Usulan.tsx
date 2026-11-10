import { useEffect, useState, type FormEvent } from 'react';
import { downloadCsv, stamp } from './csv';
import { StoredPhoto, uploadPhoto } from './media';
import { rpc } from './supabase';

type Status = 'pending' | 'diproses' | 'disetujui' | 'ditolak' | 'revisi';
type Kondisi = 'baru' | 'bekas_baik' | 'bekas_rusak_ringan';
type Option = { id: number; name: string };
type Options = { rooms: Option[]; categories: Option[] };
type Row = {
  id: number; code: string; status: Status; prioritas: string; jumlah: number; name: string; merk: string | null;
  kondisi: Kondisi; kelengkapan: string; harga: number | null; date: string; verified_at: string | null;
  room: string; requested_by: string; verified_by: string | null;
};
type Page = { counts: Record<Status, number>; total: number; rows: Row[] };
type Detail = {
  id: number; code: string; status: Status; roomId: number; room: string; kategoriId: number | null; kategori: string | null;
  name: string; merk: string | null; spesifikasi: string | null; jumlah: number; kondisi: Kondisi; kelengkapan: string;
  harga: number | null; sumber: string | null; tanggalFisik: string | null; prioritas: string; keterangan: string | null;
  date: string; catatanAdmin: string | null; alasanPenolakan: string | null; permintaanRevisi: string | null;
  requestedBy: string; verifiedBy: string | null; photos: string[]; aset: { code: string; name: string } | null; mine: boolean;
};

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const statusLabels: Record<Status, string> = {
  pending: 'Menunggu verifikasi', diproses: 'Sedang diproses', disetujui: 'Disetujui', ditolak: 'Ditolak', revisi: 'Perlu revisi',
};
const kondisiLabels: Record<Kondisi, string> = { baru: 'Baru', bekas_baik: 'Bekas - kondisi baik', bekas_rusak_ringan: 'Bekas - rusak ringan' };
const kelengkapanLabels: Record<string, string> = { lengkap: 'Lengkap', tidak_lengkap: 'Tidak lengkap' };
const prioritasLabels: Record<string, string> = { rendah: 'Rendah', sedang: 'Sedang', tinggi: 'Tinggi' };
const sumber = ['Pembelian', 'Hibah', 'Transfer', 'Donasi', 'Lainnya'];
const sumberLabels: Record<string, string> = { Transfer: 'Transfer dari unit lain' };
const text = (data: FormData, name: string) => String(data.get(name) ?? '').trim();
const date = (value: string | null) => value ? dateFormat.format(new Date(value.slice(0, 10))) : '—';

function Message({ value }: { value: { text: string; ok: boolean } }) {
  return value.text ? <p className={value.ok ? 'success' : 'notice'} role={value.ok ? 'status' : 'alert'}>{value.text}</p> : null;
}

function useOptions() {
  const [options, setOptions] = useState<Options | null>(null);
  useEffect(() => {
    let active = true;
    rpc<Options>('simbar_usulan_options').then((value) => active && setOptions(value)).catch(() => {});
    return () => { active = false; };
  }, []);
  return options;
}

function useList(mine: boolean, status: 'all' | Status, room: string, search: string, page: number, version: number) {
  const [state, setState] = useState<{ data?: Page; error?: string }>({});
  useEffect(() => {
    let active = true;
    setState({});
    rpc<Page>('simbar_usulan_list', { mine_only: mine, status_filter: status, room_filter: room ? Number(room) : null, search_text: search, page_number: page })
      .then((data) => active && setState({ data })).catch((error: Error) => active && setState({ error: error.message }));
    return () => { active = false; };
  }, [mine, status, room, search, page, version]);
  return state;
}

async function exportCsv(mine: boolean, status: 'all' | Status, room: string, search: string) {
  const { rows } = await rpc<Page>('simbar_usulan_list', {
    mine_only: mine, status_filter: status, room_filter: room ? Number(room) : null, search_text: search, page_number: 0,
  });
  downloadCsv(`${mine ? 'usulan_penerimaan_saya' : 'laporan_usulan_penerimaan'}_${stamp()}.csv`,
    ['Kode Usulan', 'Tanggal Usulan', ...(mine ? [] : ['Pengusul']), 'Ruangan', 'Nama Barang', 'Merk', 'Jumlah', 'Kondisi',
      'Kelengkapan', 'Estimasi Harga', 'Prioritas', 'Status', 'Diverifikasi Oleh', 'Tanggal Verifikasi'],
    rows.map((row) => [row.code, date(row.date), ...(mine ? [] : [row.requested_by]), row.room, row.name, row.merk ?? '-', row.jumlah,
      kondisiLabels[row.kondisi], kelengkapanLabels[row.kelengkapan], row.harga === null ? '-' : rupiah.format(row.harga),
      prioritasLabels[row.prioritas], statusLabels[row.status], row.verified_by ?? '-', date(row.verified_at)]));
}

function Toolbar({ options, counts, status, setStatus, room, setRoom, onSearch, onExport }: {
  options: Options | null; counts?: Record<Status, number>; status: 'all' | Status; setStatus: (value: 'all' | Status) => void;
  room: string; setRoom: (value: string) => void; onSearch: (value: string) => void; onExport?: () => void;
}) {
  const [draft, setDraft] = useState('');
  return <>
    <div className="filters" role="group" aria-label="Status usulan">
      {(['all', 'pending', 'diproses', 'revisi', 'disetujui', 'ditolak'] as const).map((value) =>
        <button key={value} className="secondary" aria-pressed={status === value} onClick={() => setStatus(value)}>
          {value === 'all' ? 'Semua' : statusLabels[value]}{counts && value !== 'all' ? ` (${number.format(counts[value])})` : ''}</button>)}
    </div>
    <form className="filters" role="search" onSubmit={(event) => { event.preventDefault(); onSearch(draft.trim()); }}>
      <label className="sr-only" htmlFor="usulan-search">Cari usulan</label>
      <input id="usulan-search" type="search" maxLength={100} value={draft} onChange={(event) => setDraft(event.target.value)}
        placeholder="Kode, nama barang, atau pengusul" />
      <select aria-label="Ruangan" value={room} onChange={(event) => setRoom(event.target.value)}>
        <option value="">Semua ruangan</option>
        {options?.rooms.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
      </select>
      <button className="primary" type="submit">Cari</button>
      {onExport && <button className="secondary" type="button" onClick={onExport}>Export CSV</button>}
    </form>
  </>;
}

function Table({ page, mine, open }: { page: Page; mine: boolean; open: (id: number) => void }) {
  return <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel usulan penerimaan">
    <table>
      <thead><tr><th scope="col">Barang</th><th scope="col">Ruangan</th><th scope="col">Jumlah</th>
        {!mine && <th scope="col">Pengusul</th>}<th scope="col">Tanggal</th><th scope="col">Status</th>
        <th scope="col"><span className="sr-only">Aksi</span></th></tr></thead>
      <tbody>{page.rows.map((row) => <tr key={row.id}>
        <th scope="row"><strong>{row.name}</strong><span>{row.code}{row.merk ? ' · ' + row.merk : ''}</span></th>
        <td>{row.room}</td>
        <td>{number.format(row.jumlah)}<span className="cell-note">{kondisiLabels[row.kondisi]}</span></td>
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
  return <nav className="inventory-pages" aria-label="Halaman usulan">
    <button className="secondary" disabled={page <= 1} onClick={() => go(page - 1)}>Sebelumnya</button>
    <span>Halaman {number.format(page)} dari {number.format(last)}</span>
    <button className="secondary" disabled={page >= last} onClick={() => go(page + 1)}>Berikutnya</button>
  </nav>;
}

function DetailView({ detail }: { detail: Detail }) {
  const shown = detail.photos.filter((photo) => !photo.startsWith('uploads/'));
  return <dl className="detail-list">
    <div><dt>Kode</dt><dd>{detail.code}</dd></div>
    <div><dt>Status</dt><dd><span className={'badge ' + detail.status}>{statusLabels[detail.status]}</span></dd></div>
    <div><dt>Barang</dt><dd>{detail.name}{detail.merk ? ` (${detail.merk})` : ''}</dd></div>
    <div><dt>Ruangan</dt><dd>{detail.room}</dd></div>
    <div><dt>Jumlah</dt><dd>{number.format(detail.jumlah)}</dd></div>
    <div><dt>Kondisi</dt><dd>{kondisiLabels[detail.kondisi]} · {kelengkapanLabels[detail.kelengkapan]}</dd></div>
    <div><dt>Estimasi harga</dt><dd>{detail.harga === null ? '—' : rupiah.format(detail.harga)}</dd></div>
    <div><dt>Sumber barang</dt><dd>{detail.sumber ? sumberLabels[detail.sumber] ?? detail.sumber : '—'}</dd></div>
    <div><dt>Diterima fisik</dt><dd>{date(detail.tanggalFisik)}</dd></div>
    <div><dt>Prioritas</dt><dd>{prioritasLabels[detail.prioritas]}</dd></div>
    <div><dt>Kategori aset</dt><dd>{detail.kategori ?? '—'}</dd></div>
    <div><dt>Pengusul</dt><dd>{detail.requestedBy} · {date(detail.date)}</dd></div>
    {detail.spesifikasi && <div className="wide"><dt>Spesifikasi</dt><dd>{detail.spesifikasi}</dd></div>}
    {detail.keterangan && <div className="wide"><dt>Keterangan</dt><dd>{detail.keterangan}</dd></div>}
    {detail.permintaanRevisi && <div className="wide"><dt>Permintaan revisi</dt><dd>{detail.permintaanRevisi}</dd></div>}
    {detail.alasanPenolakan && <div className="wide"><dt>Alasan penolakan</dt><dd>{detail.alasanPenolakan}</dd></div>}
    {detail.catatanAdmin && <div className="wide"><dt>Catatan admin</dt><dd>{detail.catatanAdmin}</dd></div>}
    {detail.verifiedBy && <div><dt>Diverifikasi oleh</dt><dd>{detail.verifiedBy}</dd></div>}
    {detail.aset && <div><dt>Barang aset dibuat</dt><dd>{detail.aset.name} ({detail.aset.code})</dd></div>}
    {detail.photos.length > 0 && <div className="wide"><dt>Foto barang</dt><dd className="photo-row">
      {shown.map((photo) => <StoredPhoto key={photo} reference={photo} alt={'Foto ' + detail.name} />)}
      {shown.length < detail.photos.length && <span>{detail.photos.length - shown.length} foto unggahan lama belum dipindahkan.</span>}
    </dd></div>}
  </dl>;
}

function UsulanForm({ options, editing, busy, onSubmit, onCancel }: {
  options: Options; editing: Detail | null; busy: boolean; onSubmit: (form: Record<string, unknown>) => void; onCancel: () => void;
}) {
  const [photos, setPhotos] = useState<string[]>(editing?.photos ?? []);
  const [draft, setDraft] = useState('');
  const [uploading, setUploading] = useState(false);
  const [problem, setProblem] = useState('');

  async function addFile(file: File | undefined) {
    if (!file) return;
    setUploading(true);
    setProblem('');
    try {
      const reference = await uploadPhoto(file);
      setPhotos((current) => [...current, reference].slice(0, 5));
    } catch (error) {
      setProblem((error as Error).message);
    } finally {
      setUploading(false);
    }
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    onSubmit({
      ruangan_id: Number(data.get('room')), kategori_aset_id: data.get('kategori') ? Number(data.get('kategori')) : null,
      nama_barang: text(data, 'name'), merk: text(data, 'merk'), spesifikasi: text(data, 'spesifikasi'), jumlah: Number(data.get('jumlah')),
      kondisi_barang: text(data, 'kondisi'), kelengkapan: text(data, 'kelengkapan'),
      estimasi_harga: text(data, 'harga') === '' ? null : Number(data.get('harga')), sumber_barang: text(data, 'sumber'),
      tanggal_penerimaan_fisik: text(data, 'fisik') || null, prioritas: text(data, 'prioritas'),
      keterangan_user: text(data, 'keterangan'), photos,
    });
  }

  return <form className="record-form" onSubmit={submit}>
    <h3>{editing ? 'Perbarui ' + editing.code : 'Usulan penerimaan baru'}</h3>
    {editing?.permintaanRevisi && <p className="notice wide">Revisi diminta: {editing.permintaanRevisi}</p>}
    <label>Nama barang<input name="name" required maxLength={255} defaultValue={editing?.name} /></label>
    <label>Ruangan<select name="room" required defaultValue={editing?.roomId ?? ''}>
      <option value="" disabled>Pilih ruangan</option>{options.rooms.map((room) => <option key={room.id} value={room.id}>{room.name}</option>)}
    </select></label>
    <label>Kategori aset (opsional)<select name="kategori" defaultValue={editing?.kategoriId ?? ''}>
      <option value="">Biarkan admin memilih</option>{options.categories.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
    </select></label>
    <label>Merk<input name="merk" maxLength={100} defaultValue={editing?.merk ?? ''} /></label>
    <label>Jumlah<input name="jumlah" type="number" min="1" max="9999" step="1" required defaultValue={editing?.jumlah ?? 1} /></label>
    <label>Kondisi<select name="kondisi" required defaultValue={editing?.kondisi ?? 'baru'}>
      {Object.entries(kondisiLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
    </select></label>
    <label>Kelengkapan<select name="kelengkapan" required defaultValue={editing?.kelengkapan ?? 'lengkap'}>
      <option value="lengkap">Lengkap</option><option value="tidak_lengkap">Tidak lengkap</option>
    </select></label>
    <label>Estimasi harga (Rp)<input name="harga" type="number" min="0" step="any" defaultValue={editing?.harga ?? ''} /></label>
    <label>Sumber barang<select name="sumber" defaultValue={editing?.sumber ?? ''}>
      <option value="">Tidak diisi</option>{sumber.map((value) => <option key={value} value={value}>{sumberLabels[value] ?? value}</option>)}
    </select></label>
    <label>Tanggal diterima fisik<input name="fisik" type="date" defaultValue={editing?.tanggalFisik ?? ''} /></label>
    <label>Prioritas<select name="prioritas" required defaultValue={editing?.prioritas ?? 'sedang'}>
      <option value="rendah">Rendah</option><option value="sedang">Sedang</option><option value="tinggi">Tinggi</option>
    </select></label>
    <label className="wide">Spesifikasi<textarea name="spesifikasi" rows={2} maxLength={1000} defaultValue={editing?.spesifikasi ?? ''} /></label>
    <label className="wide">Keterangan<textarea name="keterangan" rows={2} maxLength={2000} defaultValue={editing?.keterangan ?? ''} /></label>
    <fieldset className="wide choice">
      <legend>Foto barang (maks. 5)</legend>
      {photos.map((photo) => <div className="photo-link" key={photo}>
        {photo.startsWith('uploads/') ? <span>Foto unggahan lama</span> : <StoredPhoto reference={photo} alt="Foto barang" />}
        <button className="secondary" type="button" onClick={() => setPhotos(photos.filter((entry) => entry !== photo))}>Hapus</button>
      </div>)}
      {photos.length < 5 && <label className="file-button secondary">{uploading ? 'Mengunggah…' : 'Ambil / pilih foto'}
        <input type="file" accept="image/*" capture="environment" disabled={uploading}
          onChange={(event) => { addFile(event.target.files?.[0]); event.target.value = ''; }} /></label>}
      {problem && <p className="notice" role="alert">{problem}</p>}
      {photos.length < 5 && <div className="filters">
        <input type="url" maxLength={2048} value={draft} onChange={(event) => setDraft(event.target.value)} placeholder="https://…" aria-label="Link foto baru" />
        <button className="secondary" type="button" disabled={!/^https?:\/\/\S+$/.test(draft.trim())}
          onClick={() => { setPhotos([...new Set([...photos, draft.trim()])]); setDraft(''); }}>Tambah foto</button>
      </div>}
    </fieldset>
    <div className="form-actions wide">
      <button className="primary" type="submit" disabled={busy}>{busy ? 'Mengirim…' : editing ? 'Kirim ulang' : 'Ajukan'}</button>
      <button className="secondary" type="button" onClick={onCancel}>Batal</button>
    </div>
  </form>;
}

export function MyUsulan({ canExport }: { canExport: boolean }) {
  const options = useOptions();
  const [status, setStatus] = useState<'all' | Status>('all');
  const [room, setRoom] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [view, setView] = useState<{ mode: 'new' } | { mode: 'detail' | 'edit'; detail: Detail } | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useList(true, status, room, search, page, version);

  async function open(id: number) {
    try {
      setView({ mode: 'detail', detail: await rpc<Detail>('simbar_usulan_get', { usulan_id: id }) });
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
    <div className="panel-head">
      <h2>Usulan penerimaan barang</h2>
      {!view && <button className="primary" disabled={!options} onClick={() => { setMessage({ text: '', ok: true }); setView({ mode: 'new' }); }}>Ajukan usulan</button>}
    </div>
    <p>Laporkan barang yang diterima ruangan agar dicatat sebagai aset oleh Admin Gudang.</p>
    <Message value={message} />
    {view && options && view.mode !== 'detail' && <UsulanForm options={options} busy={busy} editing={view.mode === 'edit' ? view.detail : null}
      onCancel={() => setView(null)} onSubmit={(form) => act(() => rpc<string>('simbar_usulan_save', { usulan_id: view.mode === 'edit' ? view.detail.id : null, form }),
        (code) => view.mode === 'edit' ? 'Usulan berhasil diperbarui.' : 'Usulan penerimaan barang berhasil diajukan dengan kode: ' + code)} />}
    {view?.mode === 'detail' && <div className="record-form">
      <h3>{view.detail.code}</h3>
      <DetailView detail={view.detail} />
      <div className="form-actions wide">
        {['pending', 'revisi'].includes(view.detail.status) && <>
          <button className="primary" onClick={() => setView({ mode: 'edit', detail: view.detail })}>Ubah</button>
          <button className="danger" disabled={busy} onClick={() => {
            if (confirm(`Batalkan usulan ${view.detail.code}?`)) act(() => rpc('simbar_usulan_cancel', { usulan_id: view.detail.id }), () => 'Usulan berhasil dibatalkan.');
          }}>Batalkan usulan</button>
        </>}
        <button className="secondary" onClick={() => setView(null)}>Tutup</button>
      </div>
    </div>}
    <Toolbar options={options} counts={state.data?.counts} status={status} setStatus={(value) => { setStatus(value); setPage(1); }}
      room={room} setRoom={(value) => { setRoom(value); setPage(1); }} onSearch={(value) => { setSearch(value); setPage(1); }}
      onExport={canExport ? () => exportCsv(true, status, room, search).catch((error: Error) => setMessage({ text: error.message, ok: false })) : undefined} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat usulan…</p>
      : !state.data.total ? <p role="status">Belum ada usulan.</p> : <>
        <Table page={state.data} mine open={open} />
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}

export function AdminUsulan() {
  const options = useOptions();
  const [status, setStatus] = useState<'all' | Status>('pending');
  const [room, setRoom] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [version, setVersion] = useState(0);
  const [detail, setDetail] = useState<Detail | null>(null);
  const [message, setMessage] = useState({ text: '', ok: true });
  const [busy, setBusy] = useState(false);
  const state = useList(false, status, room, search, page, version);

  async function open(id: number) {
    setMessage({ text: '', ok: true });
    try {
      setDetail(await rpc<Detail>('simbar_usulan_get', { usulan_id: id }));
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    }
  }

  async function act(action: string, note: string | null, kategori: number | null, success: string) {
    if (!detail) return;
    setBusy(true);
    try {
      const code = await rpc<string | null>('simbar_usulan_act', { usulan_id: detail.id, request_action: action, note, kategori_ref: kategori });
      setMessage({ text: success + (code ? ' Kode aset: ' + code : ''), ok: true });
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
    const data = new FormData(event.currentTarget);
    act('approve', text(data, 'catatan') || null, Number(data.get('kategori')), 'Usulan disetujui dan barang aset berhasil dibuat.');
  }

  function ask(action: 'reject' | 'revision') {
    const note = prompt(action === 'reject' ? 'Alasan penolakan (wajib):' : 'Apa yang perlu direvisi pengusul? (wajib)', '');
    if (note !== null) act(action, note, null, action === 'reject' ? 'Usulan ditolak.' : 'Permintaan revisi telah dikirim ke pengusul.');
  }

  return <section className="panel">
    <h2>Verifikasi usulan penerimaan</h2>
    <p>Setujui usulan untuk langsung mencatatnya sebagai barang aset.</p>
    <Message value={message} />
    {detail && <div className="record-form">
      <h3>{detail.code}</h3>
      <DetailView detail={detail} />
      {['pending', 'diproses'].includes(detail.status) && options && <form className="approve-form wide" onSubmit={approve}>
        <label>Kategori aset<select name="kategori" required defaultValue={detail.kategoriId ?? ''}>
          <option value="" disabled>Pilih kategori</option>{options.categories.map((entry) => <option key={entry.id} value={entry.id}>{entry.name}</option>)}
        </select></label>
        <label className="wide">Catatan admin (opsional)<textarea name="catatan" rows={2} maxLength={1000} /></label>
        <div className="form-actions wide">
          <button className="primary" type="submit" disabled={busy}>Setujui &amp; buat aset</button>
          {detail.status === 'pending' && <button className="secondary" type="button" disabled={busy}
            onClick={() => act('proses', null, null, 'Usulan sedang diproses.')}>Tandai diproses</button>}
          <button className="secondary" type="button" disabled={busy} onClick={() => ask('revision')}>Minta revisi</button>
          <button className="danger" type="button" disabled={busy} onClick={() => ask('reject')}>Tolak</button>
        </div>
      </form>}
      <div className="form-actions wide">
        <button className="danger" disabled={busy} onClick={() => {
          if (confirm(`Hapus usulan ${detail.code}? Barang aset yang sudah dibuat tidak ikut terhapus.`)) act('delete', null, null, 'Usulan berhasil dihapus.');
        }}>Hapus usulan</button>
        <button className="secondary" onClick={() => setDetail(null)}>Tutup</button>
      </div>
    </div>}
    <Toolbar options={options} counts={state.data?.counts} status={status} setStatus={(value) => { setStatus(value); setPage(1); }}
      room={room} setRoom={(value) => { setRoom(value); setPage(1); }} onSearch={(value) => { setSearch(value); setPage(1); }}
      onExport={() => exportCsv(false, status, room, search).catch((error: Error) => setMessage({ text: error.message, ok: false }))} />
    {state.error ? <p className="notice" role="alert">{state.error}</p> : !state.data ? <p role="status">Memuat usulan…</p>
      : !state.data.total ? <p role="status">Tidak ada usulan.</p> : <>
        <Table page={state.data} mine={false} open={open} />
        <Pages page={page} total={state.data.total} go={setPage} />
      </>}
  </section>;
}
