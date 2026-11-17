import { useEffect, useState } from 'react';
import { rpc } from './supabase';
import { zoomProps } from './media';
import { golonganLabels, hasilLabels, jenisLabels, kalibrasiStatus, kondisiLabels, pemeliharaanStatus, statusLabels } from './Aset';

// Kartu aset: what a scanned label shows before signing in (a phone camera opening /?aset=<id> in the browser).
// Read-only, and only what simbar_aset_public returns: the label's data, condition, kalibrasi and maintenance dates.
// Signing in opens the same asset in the app, where the role decides what can be changed.
type Card = {
  id: number; kode_aset: string; nama_aset: string; merk: string | null; kategori: string; golongan: string; ruangan: string;
  kondisi: string; butuh_perbaikan: boolean; status_aset: string; jumlah: number; tahun_perolehan: number | null;
  nomor_register: string | null; spesifikasi: string | null; ukuran_konstruksi: string | null;
  foto: boolean; contoh: string | null; // contoh: kode of the same-name asset whose photo stands in
  kalibrasi_tanggal: string | null; kalibrasi_hasil: string | null; kalibrasi_berikutnya: string | null; kalibrasi_status: string | null;
  pemeliharaan: { id: number; tanggal: string; jenis: string; status: string; hasil: string | null }[];
};

const number = new Intl.NumberFormat('id-ID');
const dateFormat = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const date = (value: string | null) => value ? dateFormat.format(new Date(value.slice(0, 10))) : '—';

// The card's photo comes from /api/media?aset=<id>, which serves the asset's own photo or the example photo, without a session.
function PublicPhoto({ card }: { card: Card }) {
  const [broken, setBroken] = useState(false);
  const has = card.foto || !!card.contoh;
  const caption = card.foto ? card.nama_aset : `${card.nama_aset} · foto contoh dari ${card.contoh}`;
  const src = `/api/media?aset=${card.id}`;
  return <div className="asset-photo-panel wide">
    <div className="asset-photo-frame">
      {has && !broken ? <span className={card.contoh ? 'contoh-wrap big' : undefined}>
          <img className={'item-thumb zoomable asset-photo-big' + (card.contoh ? ' contoh' : '')} src={src + '&w=640'} alt={'Foto ' + card.nama_aset}
            width={240} height={240} onError={() => setBroken(true)} {...zoomProps({ url: src, alt: 'Foto ' + card.nama_aset, caption })} />
          {card.contoh && <span className="contoh-badge">Contoh</span>}</span>
        : <span className="item-thumb empty asset-photo-big" style={{ width: 240, height: 240 }} title="Belum difoto"><i className="fas fa-camera" /></span>}
    </div>
    <div className="asset-photo-info">
      {card.foto && !broken ? <p className="item-photo-source real"><i className="fas fa-circle-check" aria-hidden="true" /> Foto asli</p>
        : card.contoh && !broken ? <p className="item-photo-source">Belum difoto. Ini foto contoh dari aset {card.contoh} dengan nama yang sama.</p>
        : <p className="item-photo-source">Belum difoto.</p>}
    </div>
  </div>;
}

function Kalibrasi({ card }: { card: Card }) {
  if (!card.kalibrasi_status) return <>—</>;
  if (card.kalibrasi_status === 'belum') return <span className="badge">{kalibrasiStatus.belum}</span>;
  return <>
    <span className={'badge hasil-' + card.kalibrasi_hasil}>{hasilLabels[card.kalibrasi_hasil ?? ''] ?? '—'}</span> {date(card.kalibrasi_tanggal)}
    <span className="cell-note"><span className={'badge kal-' + card.kalibrasi_status}>{kalibrasiStatus[card.kalibrasi_status]} s.d. {date(card.kalibrasi_berikutnya)}</span></span>
  </>;
}

export function AsetPublik({ asetId, onScan, onBack, onLogin }: { asetId: number; onScan: () => void; onBack: () => void; onLogin: () => void }) {
  const [state, setState] = useState<{ card?: Card; error?: string }>({});

  useEffect(() => {
    let live = true;
    setState({});
    rpc<Card>('simbar_aset_public', { aset_id: asetId }).then((card) => live && setState({ card }))
      .catch((error: Error) => live && setState({ error: error.message }));
    return () => { live = false; };
  }, [asetId]);

  const card = state.card;
  const fields: [string, React.ReactNode][] = card ? [
    ['Kode aset', card.kode_aset], ['Nomor register', card.nomor_register ?? '—'],
    ['Kategori', `${card.kategori} · ${golonganLabels[card.golongan] ?? card.golongan}`], ['Ruangan', card.ruangan],
    ['Kondisi', <span className={'badge kondisi-' + card.kondisi}>{kondisiLabels[card.kondisi] ?? card.kondisi}{card.butuh_perbaikan ? ' · butuh perbaikan' : ''}</span>],
    ['Status', statusLabels[card.status_aset] ?? card.status_aset],
    ['Jumlah', number.format(card.jumlah)], ['Tahun perolehan', card.tahun_perolehan ?? '—'],
    ['Merk', card.merk ?? '—'], ['Kalibrasi', <Kalibrasi card={card} />],
  ] : [];

  return <section className="panel public-aset">
    <p className="eyebrow"><i className="fas fa-qrcode" aria-hidden="true" /> Kartu aset · hanya lihat</p>
    {state.error ? <p className="notice" role="alert">{state.error}</p>
      : !card ? <p role="status">Memuat aset…</p>
      : <>
        <h2>{card.nama_aset}</h2>
        <PublicPhoto card={card} />
        <dl className="detail-list">
          {fields.map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}
          {card.spesifikasi && <div className="wide"><dt>Spesifikasi</dt><dd>{card.spesifikasi}</dd></div>}
          {card.ukuran_konstruksi && <div className="wide"><dt>Ukuran/konstruksi</dt><dd>{card.ukuran_konstruksi}</dd></div>}
          <div className="wide"><dt>Pemeliharaan terakhir</dt><dd>{card.pemeliharaan.length ? <ul className="plain-list">
            {card.pemeliharaan.map((entry) => <li key={entry.id}>{date(entry.tanggal)} · {jenisLabels[entry.jenis] ?? entry.jenis}
              {entry.hasil ? ' (' + hasilLabels[entry.hasil] + ')' : ''} · {pemeliharaanStatus[entry.status] ?? entry.status}</li>)}
          </ul> : 'Belum ada.'}</dd></div>
        </dl>
      </>}
    <p className="muted">Untuk mengubah data, mengambil foto, memindahkan, atau melaporkan aset ini, masuk ke SIMBAR dengan akun petugas.</p>
    <div className="form-actions">
      <button className="primary" onClick={onScan}><i className="fas fa-qrcode" aria-hidden="true" /> Pindai label lain</button>
      <button className="secondary" onClick={onLogin}><i className="fas fa-right-to-bracket" aria-hidden="true" /> Masuk</button>
      <button className="secondary" onClick={onBack}>Ke dashboard</button>
    </div>
  </section>;
}
