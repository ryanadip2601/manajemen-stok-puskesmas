import { Suspense, lazy, useCallback, useState } from 'react';
// The camera reader is loaded only when someone taps "Pindai label".
const QrScanner = lazy(() => import('./QrScanner').then((m) => ({ default: m.QrScanner })));
import { Assets } from './Aset';
import { Pemeliharaan } from './Pemeliharaan';
import { Penyusutan } from './Penyusutan';
import { AsetLabel } from './AsetLabel';

// Aset: one menu for everything about assets. Tabs follow the role's rights (Atur Menu: aset, pemeliharaan, penyusutan);
// Ruangan and Kategori stay with the warehouse, like the database rules for changing them.
export type AsetTab = 'list' | 'pemeliharaan' | 'penyusutan' | 'label' | 'rooms' | 'categories';
const TABS: { id: AsetTab; label: string; icon: string }[] = [
  { id: 'list', label: 'Daftar aset', icon: 'fa-boxes-packing' },
  { id: 'pemeliharaan', label: 'Pemeliharaan', icon: 'fa-screwdriver-wrench' },
  { id: 'penyusutan', label: 'Penyusutan', icon: 'fa-calculator' },
  { id: 'label', label: 'Nomor & label', icon: 'fa-qrcode' },
  { id: 'rooms', label: 'Ruangan', icon: 'fa-door-open' },
  { id: 'categories', label: 'Kategori', icon: 'fa-tags' },
];

// `openAset` (from a scanned label QR) opens that asset's detail in the list.
export function AsetMenu({ allowed, warehouse, initial, openAset }: { allowed: AsetTab[]; warehouse: boolean; initial?: AsetTab; openAset?: number }) {
  const tabs = TABS.filter((tab) => allowed.includes(tab.id));
  const [tab, setTab] = useState<AsetTab | undefined>(initial && allowed.includes(initial) ? initial : tabs[0]?.id);
  const [report, setReport] = useState<number | null>(null);
  const [scanning, setScanning] = useState(false);
  // The asset to open: from a scanned label (in the app) or from a label link opened by the phone camera.
  const [scanned, setScanned] = useState<{ id: number; n: number } | null>(openAset ? { id: openAset, n: 0 } : null);
  const found = useCallback((id: number) => { setScanning(false); setScanned((current) => ({ id, n: (current?.n ?? 0) + 1 })); setTab('list'); setReport(null); }, []);

  if (!tab) return <section className="panel"><h2>Aset</h2><p role="status">Peran ini belum diberi akses aset.</p></section>;
  const choose = (next: AsetTab) => { setTab(next); setReport(null); };
  // "Laporkan rusak" in an asset's detail opens the Pemeliharaan form with that asset chosen.
  const reportAsset = allowed.includes('pemeliharaan') ? (asetId: number) => { setReport(asetId); setTab('pemeliharaan'); window.scrollTo(0, 0); } : undefined;
  return <>
    {scanning && <Suspense fallback={<div className="scanner"><p className="scanner-box" role="status">Membuka kamera…</p></div>}>
      <QrScanner onFound={found} onClose={() => setScanning(false)} /></Suspense>}
    <div className="aset-toolbar">
    {tabs.length > 1 && <div className="filters aset-tabs" role="group" aria-label="Bagian aset">
      {tabs.map((entry) => <button key={entry.id} className="secondary" aria-pressed={tab === entry.id} onClick={() => choose(entry.id)}>
        <i className={'fas ' + entry.icon} aria-hidden="true" /> {entry.label}</button>)}
    </div>}
    {allowed.includes('list') && <button className="primary scan-button" onClick={() => setScanning(true)}>
      <i className="fas fa-qrcode" aria-hidden="true" /> Pindai label</button>}
    </div>
    {tab === 'pemeliharaan' ? <Pemeliharaan key={report ?? 'list'} warehouse={warehouse} start={report !== null} preset={report ?? undefined} />
      : tab === 'penyusutan' ? <Penyusutan />
      : tab === 'label' ? <AsetLabel />
      : <Assets key={tab + (tab === 'list' && scanned ? '-' + scanned.n : '')} warehouse={warehouse} section={tab}
          onReport={tab === 'list' ? reportAsset : undefined}
          openId={tab === 'list' ? scanned?.id : undefined} onScanNext={() => setScanning(true)} />}
  </>;
}
