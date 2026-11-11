import { useEffect, useState } from 'react';
import { remember, remembered } from './effects';
import { rpc } from './supabase';

// Installable app (PWA), push notifications on this device and the number on the app icon.

const VAPID = (import.meta.env.VITE_VAPID_PUBLIC_KEY as string | undefined)?.trim();
type InstallPrompt = Event & { prompt: () => Promise<void>; userChoice: Promise<{ outcome: string }> };
type BadgeNavigator = Navigator & { setAppBadge?: (n: number) => Promise<void>; clearAppBadge?: () => Promise<void>; standalone?: boolean };
const nav = navigator as BadgeNavigator;

export const pushSupported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && !!VAPID;
export const isIos = () => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
export const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || nav.standalone === true;

// Chrome offers installation once; keep the event so the "Instal aplikasi" button can use it later.
let installPrompt: InstallPrompt | null = null;
const installListeners = new Set<() => void>();
window.addEventListener('beforeinstallprompt', (event) => {
  event.preventDefault();
  installPrompt = event as InstallPrompt;
  installListeners.forEach((listener) => listener());
});
window.addEventListener('appinstalled', () => {
  installPrompt = null;
  installListeners.forEach((listener) => listener());
});

export function registerServiceWorker() {
  if (!('serviceWorker' in navigator) || import.meta.env.DEV) return;
  window.addEventListener('load', () => { navigator.serviceWorker.register('/sw.js').catch(() => {}); });
}

export function setBadge(count: number) {
  const done = count > 0 ? nav.setAppBadge?.(count) : nav.clearAppBadge?.();
  done?.catch(() => {});
}

function keyBytes(base64: string) {
  const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
  return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
}

async function currentSubscription() {
  if (!pushSupported()) return null;
  const registration = await navigator.serviceWorker.getRegistration();
  return (await registration?.pushManager.getSubscription()) ?? null;
}

async function link(subscription: PushSubscription) {
  const json = subscription.toJSON();
  const badge = await rpc<number>('simbar_push_subscribe', {
    sub_endpoint: json.endpoint, sub_p256dh: json.keys?.p256dh, sub_auth: json.keys?.auth, agent: navigator.userAgent,
  });
  setBadge(badge);
}

// Signing out stops notifications for that person on this device.
export async function forgetThisDevice() {
  try {
    const subscription = await currentSubscription();
    if (subscription) {
      await rpc('simbar_push_unsubscribe', { sub_endpoint: subscription.endpoint }).catch(() => {});
      await subscription.unsubscribe();
    }
  } catch { /* nothing to forget */ }
  setBadge(0);
}

type DeviceState = { permission: NotificationPermission | 'unsupported'; subscribed: boolean; installable: boolean; busy: boolean; error: string };

export function useDeviceApp() {
  const [state, setState] = useState<DeviceState>({
    permission: pushSupported() ? Notification.permission : 'unsupported', subscribed: false, installable: !!installPrompt, busy: false, error: '',
  });
  useEffect(() => {
    let active = true;
    const installable = () => active && setState((s) => ({ ...s, installable: !!installPrompt }));
    installListeners.add(installable);
    // Re-link an existing subscription to whoever is signed in now (a shared laptop may change hands).
    currentSubscription().then(async (subscription) => {
      if (!active) return;
      setState((s) => ({ ...s, subscribed: !!subscription }));
      if (subscription && Notification.permission === 'granted') await link(subscription).catch(() => {});
    }).catch(() => {});
    return () => { active = false; installListeners.delete(installable); };
  }, []);

  async function enable() {
    setState((s) => ({ ...s, busy: true, error: '' }));
    try {
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        setState((s) => ({ ...s, busy: false, permission,
          error: permission === 'denied' ? 'Notifikasi diblokir. Izinkan lewat pengaturan situs di browser, lalu coba lagi.' : '' }));
        return;
      }
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription()
        ?? await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(VAPID!) });
      await link(subscription);
      setState((s) => ({ ...s, busy: false, permission, subscribed: true }));
    } catch (failure) {
      setState((s) => ({ ...s, busy: false, error: failure instanceof Error && failure.message.length < 200 ? failure.message : 'Notifikasi belum bisa diaktifkan.' }));
    }
  }

  async function disable() {
    setState((s) => ({ ...s, busy: true, error: '' }));
    await forgetThisDevice();
    setState((s) => ({ ...s, busy: false, subscribed: false }));
  }

  async function install() {
    if (!installPrompt) return;
    await installPrompt.prompt();
    await installPrompt.userChoice.catch(() => null);
    installPrompt = null;
    setState((s) => ({ ...s, installable: false }));
  }

  return { ...state, enable, disable, install };
}

// Settings card (Profil page) and a dismissible prompt on the dashboard.
export function DeviceApp({ compact = false }: { compact?: boolean }) {
  const device = useDeviceApp();
  const [hidden, setHidden] = useState(() => compact && remembered('simbar-push-hint') === 'hidden');
  const ios = isIos(), standalone = isStandalone();
  const ready = device.permission === 'granted' && device.subscribed;
  if (compact && (hidden || ready || device.permission === 'unsupported' && !(ios && !standalone))) return null;

  return <section className={'panel device-app' + (compact ? ' compact' : '')}>
    <div className="device-head">
      <span className="device-icon" aria-hidden="true"><i className={'fas ' + (ready ? 'fa-bell' : 'fa-bell-slash')} /></span>
      <div>
        <h2>{compact ? 'Dapatkan notifikasi di perangkat ini' : 'Aplikasi & notifikasi'}</h2>
        <p>{ready ? 'Notifikasi aktif di perangkat ini. Anda akan diberi tahu saat ada pengajuan baru atau status pengajuan berubah.'
          : 'Pasang SIMBAR sebagai aplikasi dan aktifkan notifikasi, supaya permintaan, pengambilan, usulan, dan pemeliharaan langsung terlihat, lengkap dengan angka di ikon aplikasi.'}</p>
      </div>
      {compact && <button className="icon-button" aria-label="Sembunyikan" onClick={() => { remember('simbar-push-hint', 'hidden'); setHidden(true); }}>
        <i className="fas fa-xmark" aria-hidden="true" /></button>}
    </div>
    {ios && !standalone ? <p className="device-steps">
      Di iPhone/iPad: buka di <b>Safari</b>, ketuk <i className="fas fa-arrow-up-from-bracket" aria-label="Bagikan" />, pilih <b>Tambah ke Layar Utama</b>,
      lalu buka SIMBAR dari ikon di layar utama dan aktifkan notifikasi dari sana (iOS 16.4 atau lebih baru).
    </p> : device.permission === 'unsupported' ? <p className="device-steps">Browser ini belum mendukung notifikasi. Gunakan Chrome atau Edge terbaru.</p>
    : <div className="form-actions">
      {!ready && <button className="primary" disabled={device.busy} onClick={device.enable}>
        <i className="fas fa-bell" aria-hidden="true" /> {device.busy ? 'Mengaktifkan…' : 'Aktifkan notifikasi'}</button>}
      {ready && !compact && <button className="secondary" disabled={device.busy} onClick={device.disable}>
        <i className="fas fa-bell-slash" aria-hidden="true" /> Matikan notifikasi di perangkat ini</button>}
      {device.installable && <button className="secondary" onClick={device.install}>
        <i className="fas fa-download" aria-hidden="true" /> Instal aplikasi</button>}
    </div>}
    {device.error && <p className="notice" role="alert">{device.error}</p>}
    {!compact && !ios && !device.installable && !standalone && <p className="device-steps">
      Untuk memasang: di Chrome/Edge buka menu <b>⋮</b> lalu pilih <b>Instal SIMBAR</b> (laptop) atau <b>Tambahkan ke layar utama</b> (Android).
    </p>}
  </section>;
}
