import { useState } from 'react';
import { rpc } from './supabase';

// Every menu in sidebar order. `share` marks the ones the administrator can hand to Admin Gudang and Staf;
// Dashboard is always shown, Pengguna and Atur Menu stay with the administrator.
export type MenuId = 'summary' | 'items' | 'catalog' | 'in' | 'out' | 'checklist' | 'verifikasi' | 'usulan-admin'
  | 'aset' | 'pemeliharaan' | 'penyusutan' | 'pickup' | 'permintaan' | 'usulan' | 'laporan' | 'users' | 'menus';
// `right` entries are not sidebar menus but a permission inside another page (shown indented in Atur Menu).
export type MenuEntry = { id: MenuId; label: string; icon: string; color: string; share: 'always' | 'yes' | 'admin'; hint: string; right?: boolean };
export const MENU_GROUPS: { title: string; items: MenuEntry[] }[] = [
  { title: 'Menu Utama', items: [
    { id: 'summary', label: 'Dashboard', icon: 'fa-house', color: 'blue', share: 'always', hint: 'Ringkasan stok, aset dan pengajuan.' },
    { id: 'items', label: 'Data Barang', icon: 'fa-box', color: 'emerald', share: 'yes', hint: 'Melihat daftar barang dan stoknya.' },
    { id: 'catalog', label: 'Boleh ubah barang', icon: 'fa-pen-to-square', color: 'teal', share: 'yes', right: true,
      hint: 'Di Data Barang: menambah, mengubah dan menghapus barang serta jenis barang.' },
  ] },
  { title: 'Transaksi Gudang', items: [
    { id: 'in', label: 'Barang Masuk', icon: 'fa-arrow-down', color: 'green', share: 'yes', hint: 'Mencatat barang masuk; stok bertambah.' },
    { id: 'out', label: 'Barang Keluar', icon: 'fa-arrow-up', color: 'red', share: 'yes', hint: 'Mencatat barang keluar; stok berkurang.' },
    { id: 'checklist', label: 'Checklist Pengambilan', icon: 'fa-clipboard-check', color: 'pink', share: 'yes', hint: 'Menyetujui pengambilan; stok langsung berkurang.' },
  ] },
  { title: 'Verifikasi', items: [
    { id: 'verifikasi', label: 'Verifikasi Permintaan', icon: 'fa-hand-holding-medical', color: 'orange', share: 'yes', hint: 'Menyetujui permintaan; barang baru ikut dibuat.' },
    { id: 'usulan-admin', label: 'Verifikasi Usulan', icon: 'fa-file-import', color: 'teal', share: 'yes', hint: 'Menyetujui usulan; aset baru ikut dibuat.' },
  ] },
  { title: 'Manajemen Aset', items: [
    { id: 'aset', label: 'Barang Aset', icon: 'fa-boxes-packing', color: 'cyan', share: 'yes', hint: 'Melihat aset; mengubahnya tetap khusus Admin Gudang.' },
    { id: 'pemeliharaan', label: 'Pemeliharaan', icon: 'fa-screwdriver-wrench', color: 'purple', share: 'yes', hint: 'Melaporkan aset rusak dan memantau perbaikannya.' },
    { id: 'penyusutan', label: 'Penyusutan', icon: 'fa-calculator', color: 'amber', share: 'yes', hint: 'Menghitung dan memproses penyusutan aset.' },
  ] },
  { title: 'Pengajuan Saya', items: [
    { id: 'pickup', label: 'Pengambilan Barang', icon: 'fa-hand-holding', color: 'orange', share: 'yes', hint: 'Mengajukan pengambilan barang dari gudang.' },
    { id: 'permintaan', label: 'Permintaan Barang', icon: 'fa-clipboard-list', color: 'amber', share: 'yes', hint: 'Meminta restok atau barang baru.' },
    { id: 'usulan', label: 'Usulan Penerimaan', icon: 'fa-file-signature', color: 'blue', share: 'yes', hint: 'Melaporkan barang yang diterima ruangan.' },
  ] },
  { title: 'Administrasi', items: [
    { id: 'laporan', label: 'Laporan', icon: 'fa-chart-line', color: 'indigo', share: 'yes', hint: 'Laporan stok dan transaksi, termasuk ekspor CSV.' },
    { id: 'users', label: 'Pengguna', icon: 'fa-users-gear', color: 'rose', share: 'admin', hint: 'Mengelola akun dan peran.' },
    { id: 'menus', label: 'Atur Menu', icon: 'fa-sliders', color: 'teal', share: 'admin', hint: 'Mengatur menu tiap peran.' },
  ] },
];

export type SharedRole = 'admin_gudang' | 'staff';
export type RoleMenus = Record<SharedRole, MenuId[]>;
// Same as the first-run defaults in db/ops-menus.sql: used until the saved settings arrive.
export const DEFAULT_ROLE_MENUS: RoleMenus = {
  admin_gudang: ['items', 'catalog', 'in', 'out', 'checklist', 'verifikasi', 'usulan-admin', 'aset', 'pemeliharaan', 'penyusutan', 'pickup', 'permintaan', 'usulan'],
  staff: ['items', 'aset', 'pemeliharaan', 'pickup', 'permintaan', 'usulan'],
};
const ROLES: { id: SharedRole; label: string }[] = [{ id: 'admin_gudang', label: 'Admin Gudang' }, { id: 'staff', label: 'Staf' }];
const same = (a: MenuId[], b: MenuId[]) => a.length === b.length && a.every((id) => b.includes(id));

export function RoleMenusPage({ saved, onSaved }: { saved: RoleMenus; onSaved: (next: RoleMenus) => void }) {
  const [draft, setDraft] = useState<RoleMenus>(saved);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ ok: boolean; text: string } | null>(null);
  const dirty = ROLES.some((role) => !same(draft[role.id], saved[role.id]));

  function toggle(role: SharedRole, id: MenuId) {
    setMessage(null);
    setDraft({ ...draft, [role]: draft[role].includes(id) ? draft[role].filter((m) => m !== id) : [...draft[role], id] });
  }
  async function save() {
    setBusy(true);
    setMessage(null);
    try {
      for (const role of ROLES) if (!same(draft[role.id], saved[role.id])) await rpc('simbar_role_menus_save', { target_role: role.id, menus: draft[role.id] });
      onSaved(draft);
      setMessage({ ok: true, text: 'Menu tersimpan. Pengguna melihat perubahan saat membuka atau memuat ulang SIMBAR.' });
    } catch (error) {
      setMessage({ ok: false, text: (error as Error).message });
    } finally {
      setBusy(false);
    }
  }

  return <section className="panel role-menus">
    <h2>Atur menu per peran</h2>
    <p className="role-menus-lead">Centang menu yang boleh dipakai Admin Gudang dan Staf. Menu yang dicentang juga membuka aksesnya:
      misalnya Staf dengan <strong>Barang Masuk</strong> bisa mencatat barang masuk. Administrator selalu memiliki semua menu.</p>
    <div className="inventory-scroll" tabIndex={0} role="region" aria-label="Tabel menu per peran">
      <table>
        <thead><tr><th scope="col">Menu</th><th scope="col" className="role-col">Administrator</th>
          {ROLES.map((role) => <th scope="col" className="role-col" key={role.id}>{role.label}</th>)}</tr></thead>
        {MENU_GROUPS.map((group) => <tbody key={group.title}>
          <tr className="role-menus-group"><th scope="rowgroup" colSpan={4}>{group.title}</th></tr>
          {group.items.map((item) => <tr key={item.id} className={item.right ? 'role-menus-right' : undefined}>
            <th scope="row"><span className={'role-menus-name c-' + item.color}><i className={'fas ' + item.icon} aria-hidden="true" />
              <span><strong>{item.label}</strong><small>{item.hint}</small></span></span></th>
            <td className="role-col"><i className="fas fa-check role-menus-fixed" aria-label="Selalu" /></td>
            {ROLES.map((role) => <td className="role-col" key={role.id}>
              {item.share === 'yes'
                ? <input type="checkbox" checked={draft[role.id].includes(item.id)} onChange={() => toggle(role.id, item.id)}
                    aria-label={`${item.label} untuk ${role.label}`} />
                : item.share === 'always' ? <i className="fas fa-check role-menus-fixed" aria-label="Selalu" />
                : <span className="role-menus-locked" title="Khusus Administrator"><i className="fas fa-lock" aria-hidden="true" /><span className="sr-only">Khusus Administrator</span></span>}
            </td>)}
          </tr>)}
        </tbody>)}
      </table>
    </div>
    {message && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
    <div className="form-actions">
      <button className="primary" disabled={!dirty || busy} onClick={save}>{busy ? 'Menyimpan…' : 'Simpan perubahan'}</button>
      <button className="secondary" disabled={busy || !dirty} onClick={() => { setDraft(saved); setMessage(null); }}>Batalkan</button>
      <button className="secondary" disabled={busy} onClick={() => { setDraft(DEFAULT_ROLE_MENUS); setMessage(null); }}>Kembalikan bawaan</button>
    </div>
  </section>;
}
