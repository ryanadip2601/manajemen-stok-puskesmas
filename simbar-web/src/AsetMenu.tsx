import { useState } from 'react';
import { Assets } from './Aset';
import { Pemeliharaan } from './Pemeliharaan';
import { Penyusutan } from './Penyusutan';

// Aset: one menu for everything about assets. Tabs follow the role's rights (Atur Menu: aset, pemeliharaan, penyusutan);
// Ruangan and Kategori stay with the warehouse, like the database rules for changing them.
export type AsetTab = 'list' | 'pemeliharaan' | 'penyusutan' | 'rooms' | 'categories';
const TABS: { id: AsetTab; label: string; icon: string }[] = [
  { id: 'list', label: 'Daftar aset', icon: 'fa-boxes-packing' },
  { id: 'pemeliharaan', label: 'Pemeliharaan', icon: 'fa-screwdriver-wrench' },
  { id: 'penyusutan', label: 'Penyusutan', icon: 'fa-calculator' },
  { id: 'rooms', label: 'Ruangan', icon: 'fa-door-open' },
  { id: 'categories', label: 'Kategori', icon: 'fa-tags' },
];

export function AsetMenu({ allowed, warehouse, initial }: { allowed: AsetTab[]; warehouse: boolean; initial?: AsetTab }) {
  const tabs = TABS.filter((tab) => allowed.includes(tab.id));
  const [tab, setTab] = useState<AsetTab | undefined>(initial && allowed.includes(initial) ? initial : tabs[0]?.id);
  const [report, setReport] = useState<number | null>(null);

  if (!tab) return <section className="panel"><h2>Aset</h2><p role="status">Peran ini belum diberi akses aset.</p></section>;
  const choose = (next: AsetTab) => { setTab(next); setReport(null); };
  // "Laporkan rusak" in an asset's detail opens the Pemeliharaan form with that asset chosen.
  const reportAsset = allowed.includes('pemeliharaan') ? (asetId: number) => { setReport(asetId); setTab('pemeliharaan'); window.scrollTo(0, 0); } : undefined;
  return <>
    {tabs.length > 1 && <div className="filters aset-tabs" role="group" aria-label="Bagian aset">
      {tabs.map((entry) => <button key={entry.id} className="secondary" aria-pressed={tab === entry.id} onClick={() => choose(entry.id)}>
        <i className={'fas ' + entry.icon} aria-hidden="true" /> {entry.label}</button>)}
    </div>}
    {tab === 'pemeliharaan' ? <Pemeliharaan key={report ?? 'list'} warehouse={warehouse} start={report !== null} preset={report ?? undefined} />
      : tab === 'penyusutan' ? <Penyusutan />
      : <Assets key={tab} warehouse={warehouse} section={tab} onReport={tab === 'list' ? reportAsset : undefined} />}
  </>;
}
