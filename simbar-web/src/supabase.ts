import { createClient } from '@supabase/supabase-js';

const url = import.meta.env.VITE_SUPABASE_URL;
const publishableKey = import.meta.env.VITE_SUPABASE_PUBLISHABLE_KEY;

// "Ingat saya": when on (the default) the login is kept in localStorage and survives closing the app;
// when off it lives in sessionStorage and ends when the app or browser is closed.
const REMEMBER_KEY = 'simbar-ingat';
const safe = <T>(run: () => T, fallback: T) => { try { return run(); } catch { return fallback; } };
export const getRemember = () => safe(() => window.localStorage.getItem(REMEMBER_KEY) !== '0', true);
export const setRemember = (on: boolean) => safe(() => window.localStorage.setItem(REMEMBER_KEY, on ? '1' : '0'), undefined);

const sessionStore = {
  getItem: (key: string) => safe(() => window.sessionStorage.getItem(key) ?? window.localStorage.getItem(key), null),
  setItem: (key: string, value: string) => safe(() => {
    const [keep, drop] = getRemember() ? [window.localStorage, window.sessionStorage] : [window.sessionStorage, window.localStorage];
    keep.setItem(key, value);
    drop.removeItem(key);
  }, undefined),
  removeItem: (key: string) => safe(() => { window.localStorage.removeItem(key); window.sessionStorage.removeItem(key); }, undefined),
};

export const client = url && publishableKey ? createClient(url, publishableKey, {
  auth: { storage: sessionStore, persistSession: true, detectSessionInUrl: true },
}) : null;

// Calls a SIMBAR database function as the signed-in user; the function enforces the role.
export async function rpc<T>(name: string, args: Record<string, unknown> = {}): Promise<T> {
  if (!client) throw new Error('Koneksi SIMBAR belum dikonfigurasi.');
  const { data, error } = await client.rpc(name, args);
  if (error) {
    if (/not approved or linked/.test(error.message)) throw new Error('Akun ini belum diaktifkan untuk SIMBAR.');
    // Messages raised by the SIMBAR functions are already written for the user.
    throw new Error(/^[A-Z][^\n]{0,200}$/.test(error.message) && !/^(JWT|permission|Could not)/.test(error.message)
      ? error.message : 'Data SIMBAR belum bisa disimpan. Coba lagi.');
  }
  return data as T;
}
