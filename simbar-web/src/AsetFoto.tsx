import { useState } from 'react';
import { PrivateThumb, uploadPhoto } from './media';
import { rpc } from './supabase';

// Foto aset: the asset's own photo, or an example photo from a same-name asset ("Contoh"), or a placeholder.
export type Contoh = { foto: string; kode: string } | null;

export function AssetThumb({ photo, contoh, name, size = 48 }: { photo?: string | null; contoh?: Contoh; name: string; size?: number }) {
  if (photo) return <PrivateThumb reference={photo} alt={'Foto ' + name} size={96} display={size} />;
  if (contoh) return <span className="contoh-wrap" title={'Foto contoh dari ' + contoh.kode}>
    <PrivateThumb reference={contoh.foto} alt={'Foto contoh ' + name} size={96} display={size} className="contoh" />
    <span className="contoh-badge">Contoh</span></span>;
  return <span className="item-thumb empty" style={{ width: size, height: size }} title="Belum difoto" aria-label="Belum difoto"><i className="fas fa-camera" /></span>;
}

const when = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'UTC' });

// Top of the asset detail: the big photo and, for the warehouse, "Ambil foto" (opens the phone camera).
// onScanNext appears when the detail was opened by scanning a label, to continue with the next asset.
export function AssetPhotoPanel({ asetId, name, photo, contoh, takenAt, takenBy, canTake, onSaved, onScanNext }: {
  asetId: number; name: string; photo: string | null; contoh: Contoh; takenAt?: string | null; takenBy?: string | null;
  canTake: boolean; onSaved: (photo: string, takenAt: string, takenBy: string) => void; onScanNext?: () => void;
}) {
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState({ text: '', ok: true });

  async function take(file?: File) {
    if (!file) return;
    setBusy(true);
    setMessage({ text: '', ok: true });
    try {
      const reference = await uploadPhoto(file);
      const saved = await rpc<{ foto: string; foto_diambil_at: string; foto_diambil_oleh_name: string }>('simbar_aset_set_foto', { aset_id: asetId, reference });
      onSaved(saved.foto, saved.foto_diambil_at, saved.foto_diambil_oleh_name);
      setMessage({ text: 'Foto tersimpan.', ok: true });
    } catch (error) {
      setMessage({ text: (error as Error).message, ok: false });
    } finally {
      setBusy(false);
    }
  }

  return <div className="asset-photo-panel wide">
    <div className="asset-photo-frame">
      {photo ? <PrivateThumb reference={photo} alt={'Foto ' + name} size={640} display={240} className="asset-photo-big" />
        : contoh ? <span className="contoh-wrap big"><PrivateThumb reference={contoh.foto} alt={'Foto contoh ' + name} size={640} display={240} className="asset-photo-big contoh" />
          <span className="contoh-badge">Contoh</span></span>
        : <span className="item-thumb empty asset-photo-big" style={{ width: 240, height: 240 }}><i className="fas fa-camera" /></span>}
    </div>
    <div className="asset-photo-info">
      {photo ? <p className="item-photo-source real"><i className="fas fa-circle-check" aria-hidden="true" /> Foto asli
        {takenAt && <span className="muted"> · {when.format(new Date(takenAt))}{takenBy ? ' oleh ' + takenBy : ''}</span>}</p>
        : contoh ? <p className="item-photo-source">Belum difoto. Ini foto contoh dari aset {contoh.kode} dengan nama yang sama.</p>
        : <p className="item-photo-source">Belum difoto.</p>}
      {message.text && <p className={message.ok ? 'success' : 'notice'} role={message.ok ? 'status' : 'alert'}>{message.text}</p>}
      <div className="form-actions">
        {canTake && <label className="file-button primary big-action">
          <i className="fas fa-camera" aria-hidden="true" /> {busy ? 'Menyimpan foto…' : photo ? 'Ambil ulang foto' : 'Ambil foto'}
          <input type="file" accept="image/*" capture="environment" disabled={busy}
            onChange={(event) => { take(event.target.files?.[0]); event.target.value = ''; }} /></label>}
        {onScanNext && <button className="secondary big-action" type="button" disabled={busy} onClick={onScanNext}>
          <i className="fas fa-qrcode" aria-hidden="true" /> Pindai berikutnya</button>}
      </div>
    </div>
  </div>;
}
