import { useEffect, useState, type FormEvent } from 'react';
import type { Session } from '@supabase/supabase-js';

export type InventoryKind = 'items' | 'assets';
type InventoryRow = {
  id: number;
  code: string;
  name: string;
  category: string;
  quantity: number;
  unit: string | null;
  minimum: number | null;
  room: string | null;
  condition: string | null;
  status: string | null;
};
type InventoryPage = { rows: InventoryRow[]; total: number };
type Props = {
  kind: InventoryKind;
  session: Session;
  request: (session: Session, signal: AbortSignal, query: Record<string, string>) => Promise<Response>;
};

const perPage = 15;
const number = new Intl.NumberFormat('id-ID');
const labels: Record<string, string> = {
  baik: 'Baik',
  rusak_ringan: 'Rusak ringan',
  rusak_berat: 'Rusak berat',
  aktif: 'Aktif',
  tidak_aktif: 'Tidak aktif',
  dihapuskan: 'Dihapuskan',
  dipindahkan: 'Dipindahkan',
};

export function Inventory({ kind, session, request }: Props) {
  const isItem = kind === 'items';
  const [draft, setDraft] = useState('');
  const [filter, setFilter] = useState({ term: '', page: 1 });
  const [state, setState] = useState<{ data?: InventoryPage; error?: string }>({});

  useEffect(() => {
    const controller = new AbortController();
    request(session, controller.signal, { list: kind, q: filter.term, page: String(filter.page) })
      .then((response) => response.json()).then((data: InventoryPage) => {
        if (!controller.signal.aborted) setState({ data });
      }).catch((error: Error) => {
        if (!controller.signal.aborted) setState({ error: error.message });
      });
    return () => controller.abort();
  }, [session, kind, filter, request]);

  function load(term: string, page: number) {
    setState({});
    setFilter({ term, page });
  }

  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    load(draft.trim(), 1);
  }

  const total = state.data?.total ?? 0;
  const lastPage = Math.max(1, Math.ceil(total / perPage));

  return <section className="panel">
    <h2>{isItem ? 'Daftar barang' : 'Daftar aset'}</h2>
    <form className="inventory-search" onSubmit={search} role="search">
      <label htmlFor="inventory-search">Cari {isItem ? 'barang' : 'aset'}</label>
      <div className="filters">
        <input id="inventory-search" type="search" value={draft} onChange={(event) => setDraft(event.target.value)}
          maxLength={100} placeholder={isItem ? 'Nama, kode, atau kategori' : 'Nama, kode, kategori, atau ruangan'} />
        <button className="primary" type="submit">Cari</button>
        {filter.term && <button className="secondary" type="button" onClick={() => {
          setDraft('');
          load('', 1);
        }}>Hapus filter</button>}
      </div>
    </form>
    {state.error ? <>
      <p className="notice" role="alert">{state.error}</p>
      <button className="secondary" onClick={() => load(filter.term, filter.page)}>Coba lagi</button>
    </> : !state.data ? <p role="status">Memuat daftar…</p> : !total ? <p role="status">Tidak ada hasil.</p> : <>
      <p role="status">{number.format(total)} {isItem ? 'jenis barang' : 'aset'} ditemukan.</p>
      <div className="inventory-scroll" tabIndex={0} role="region" aria-label={isItem ? 'Tabel barang' : 'Tabel aset'}>
        <table>
          <thead><tr><th scope="col">Nama dan kode</th><th scope="col">Kategori</th>
            <th scope="col">{isItem ? 'Stok' : 'Jumlah'}</th>
            {isItem ? <th scope="col">Stok minimum</th> : <>
              <th scope="col">Ruangan</th><th scope="col">Kondisi</th><th scope="col">Status</th>
            </>}
          </tr></thead>
          <tbody>{state.data.rows.map((row) => <tr key={row.id}>
            <th scope="row"><strong>{row.name}</strong><span>{row.code}</span></th>
            <td>{row.category}</td>
            <td>{number.format(row.quantity)}{row.unit && ' ' + row.unit}</td>
            {isItem ? <td>{row.minimum === null ? '—' : number.format(row.minimum)}</td> : <>
              <td>{row.room}</td><td>{row.condition && labels[row.condition]}</td><td>{row.status && labels[row.status]}</td>
            </>}
          </tr>)}</tbody>
        </table>
      </div>
      <nav className="inventory-pages" aria-label="Halaman hasil pencarian">
        <button className="secondary" disabled={filter.page <= 1} onClick={() => load(filter.term, filter.page - 1)}>Sebelumnya</button>
        <span>Halaman {number.format(filter.page)} dari {number.format(lastPage)}</span>
        <button className="secondary" disabled={filter.page >= lastPage} onClick={() => load(filter.term, filter.page + 1)}>Berikutnya</button>
      </nav>
    </>}
  </section>;
}
