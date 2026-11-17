import { useEffect, useRef, useState, type FormEvent } from 'react';

// "Scan barcode": reads the factory barcode on a package with the phone camera. Uses the browser's own reader where
// it exists (Chrome on Android) and a bundled reader elsewhere (iPhone, desktop). The number can also be typed.
type Detector = { detect: (source: CanvasImageSource) => Promise<{ rawValue: string }[]> };
type DetectorClass = { new (options: { formats: string[] }): Detector; getSupportedFormats?: () => Promise<string[]> };
const FORMATS = ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39', 'itf', 'codabar', 'qr_code', 'data_matrix'];

async function nativeReader(): Promise<((canvas: HTMLCanvasElement) => Promise<string>) | null> {
  const Native = (window as unknown as { BarcodeDetector?: DetectorClass }).BarcodeDetector;
  if (!Native) return null;
  try {
    const supported = await Native.getSupportedFormats?.() ?? FORMATS;
    const formats = FORMATS.filter((format) => supported.includes(format));
    if (!formats.includes('ean_13')) return null;
    const detector = new Native({ formats });
    return async (canvas) => (await detector.detect(canvas))[0]?.rawValue ?? '';
  } catch {
    return null;
  }
}

async function bundledReader(): Promise<(canvas: HTMLCanvasElement) => Promise<string>> {
  const zxing = await import('@zxing/library');
  const reader = new zxing.MultiFormatReader();
  const hints = new Map<unknown, unknown>();
  hints.set(zxing.DecodeHintType.POSSIBLE_FORMATS, [zxing.BarcodeFormat.EAN_13, zxing.BarcodeFormat.EAN_8, zxing.BarcodeFormat.UPC_A,
    zxing.BarcodeFormat.UPC_E, zxing.BarcodeFormat.CODE_128, zxing.BarcodeFormat.CODE_39, zxing.BarcodeFormat.ITF, zxing.BarcodeFormat.CODABAR,
    zxing.BarcodeFormat.QR_CODE, zxing.BarcodeFormat.DATA_MATRIX]);
  hints.set(zxing.DecodeHintType.TRY_HARDER, true);
  reader.setHints(hints as Map<never, unknown>);
  return async (canvas) => {
    const context = canvas.getContext('2d', { willReadFrequently: true });
    if (!context) return '';
    const { data, width, height } = context.getImageData(0, 0, canvas.width, canvas.height);
    const grey = new Uint8ClampedArray(width * height);
    for (let i = 0, p = 0; i < grey.length; i += 1, p += 4) grey[i] = (data[p] * 77 + data[p + 1] * 150 + data[p + 2] * 29) >> 8;
    try {
      return reader.decodeWithState(new zxing.BinaryBitmap(new zxing.HybridBinarizer(new zxing.RGBLuminanceSource(grey, width, height)))).getText();
    } catch {
      return ''; // nothing readable in this frame
    } finally {
      reader.reset();
    }
  };
}

export function BarcodeScanner({ onFound, onClose }: { onFound: (code: string) => void; onClose: () => void }) {
  const video = useRef<HTMLVideoElement>(null);
  const [problem, setProblem] = useState('');

  useEffect(() => {
    let stream: MediaStream | null = null;
    let timer = 0;
    let stopped = false;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d', { willReadFrequently: true });
    let read: ((canvas: HTMLCanvasElement) => Promise<string>) | null = null;
    let last = '';

    async function scan() {
      if (stopped) return;
      const el = video.current;
      if (read && el && el.readyState >= 2 && context && el.videoWidth) {
        const scale = Math.min(1, 1280 / Math.max(el.videoWidth, el.videoHeight));
        canvas.width = Math.round(el.videoWidth * scale);
        canvas.height = Math.round(el.videoHeight * scale);
        context.drawImage(el, 0, 0, canvas.width, canvas.height);
        const code = (await read(canvas).catch(() => '')).trim();
        // The same value twice in a row, so a misread frame never adds the wrong barang.
        if (code.length >= 4 && code === last && !stopped) {
          navigator.vibrate?.(80);
          stopped = true;
          onFound(code);
          return;
        }
        last = code.length >= 4 ? code : last;
      }
      timer = window.setTimeout(scan, 120);
    }

    if (!navigator.mediaDevices?.getUserMedia) setProblem('Kamera tidak tersedia di perangkat atau browser ini. Ketik angka barcode di bawah.');
    else navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false,
    }).then(async (media) => {
      if (stopped) { media.getTracks().forEach((track) => track.stop()); return; }
      stream = media;
      if (video.current) {
        video.current.srcObject = media;
        video.current.play().catch(() => {});
      }
      read = await nativeReader() ?? await bundledReader();
      scan();
    }).catch(() => setProblem('Kamera belum bisa dipakai. Izinkan kamera untuk SIMBAR di pengaturan browser, atau ketik angka barcode di bawah.'));

    return () => {
      stopped = true;
      clearTimeout(timer);
      stream?.getTracks().forEach((track) => track.stop());
    };
  }, [onFound]);

  function typed(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const code = String(new FormData(event.currentTarget).get('code') ?? '').trim();
    if (code.length >= 4) onFound(code);
  }

  return <div className="scanner" role="dialog" aria-modal="true" aria-label="Scan barcode barang">
    <div className="scanner-box">
      <div className="scanner-head">
        <strong><i className="fas fa-barcode" aria-hidden="true" /> Scan barcode barang</strong>
        <button className="secondary" onClick={onClose}>Tutup</button>
      </div>
      {problem ? <p className="notice" role="alert">{problem}</p> : <>
        <div className="scanner-view">
          <video ref={video} playsInline muted />
          <span className="scanner-frame barcode" aria-hidden="true" />
        </div>
        <p className="muted">Arahkan kamera ke barcode di kemasan, usahakan terang dan tidak miring.</p>
      </>}
      <form className="scanner-typed" onSubmit={typed}>
        <label className="sr-only" htmlFor="barcode-typed">Angka barcode</label>
        <input id="barcode-typed" name="code" inputMode="numeric" autoComplete="off" minLength={4} maxLength={100} placeholder="Atau ketik angka barcode" />
        <button className="secondary" type="submit">Pakai</button>
      </form>
    </div>
  </div>;
}
