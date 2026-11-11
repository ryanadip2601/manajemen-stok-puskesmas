import { useEffect, useRef, useState } from 'react';
import jsQR from 'jsqr';

// "Pindai label": reads a SIMBAR asset label with the phone camera inside the app (works on Android and iPhone)
// and hands back the asset id from its link (…/?aset=<id>).
export function QrScanner({ onFound, onClose }: { onFound: (asetId: number) => void; onClose: () => void }) {
  const video = useRef<HTMLVideoElement>(null);
  const [problem, setProblem] = useState('');
  const [hint, setHint] = useState('');

  useEffect(() => {
    let stream: MediaStream | null = null;
    let frame = 0;
    let stopped = false;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    function scan() {
      if (stopped) return;
      const el = video.current;
      if (el && el.readyState >= 2 && context) {
        const scale = Math.min(1, 640 / Math.max(el.videoWidth, el.videoHeight));
        canvas.width = Math.round(el.videoWidth * scale);
        canvas.height = Math.round(el.videoHeight * scale);
        context.drawImage(el, 0, 0, canvas.width, canvas.height);
        const code = jsQR(context.getImageData(0, 0, canvas.width, canvas.height).data, canvas.width, canvas.height, { inversionAttempts: 'dontInvert' });
        if (code?.data) {
          let id = 0;
          try { id = Number(new URL(code.data).searchParams.get('aset')); } catch { /* not a link */ }
          if (Number.isSafeInteger(id) && id > 0) {
            navigator.vibrate?.(80);
            stopped = true;
            onFound(id);
            return;
          }
          setHint('QR ini bukan label aset SIMBAR.');
        }
      }
      frame = requestAnimationFrame(scan);
    }

    if (!navigator.mediaDevices?.getUserMedia) setProblem('Kamera tidak tersedia di perangkat atau browser ini.');
    else navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then((media) => {
      if (stopped) { media.getTracks().forEach((track) => track.stop()); return; }
      stream = media;
      if (video.current) {
        video.current.srcObject = media;
        video.current.play().catch(() => {});
      }
      frame = requestAnimationFrame(scan);
    }).catch(() => setProblem('Izin kamera ditolak. Izinkan kamera untuk SIMBAR di pengaturan browser, lalu coba lagi.'));

    return () => {
      stopped = true;
      cancelAnimationFrame(frame);
      stream?.getTracks().forEach((track) => track.stop());
    };
  }, [onFound]);

  return <div className="scanner" role="dialog" aria-modal="true" aria-label="Pindai label aset">
    <div className="scanner-box">
      <div className="scanner-head">
        <strong><i className="fas fa-qrcode" aria-hidden="true" /> Pindai label aset</strong>
        <button className="secondary" onClick={onClose}>Tutup</button>
      </div>
      {problem ? <p className="notice" role="alert">{problem}</p> : <>
        <div className="scanner-view">
          <video ref={video} playsInline muted />
          <span className="scanner-frame" aria-hidden="true" />
        </div>
        <p className="muted">{hint || 'Arahkan kamera ke QR pada label. Aset langsung terbuka begitu terbaca.'}</p>
      </>}
    </div>
  </div>;
}
