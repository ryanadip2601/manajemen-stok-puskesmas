import { createClient } from '@supabase/supabase-js';

const url = import.meta.env.VITE_SUPABASE_URL;
const publishableKey = import.meta.env.VITE_SUPABASE_PUBLISHABLE_KEY;

export const client = url && publishableKey ? createClient(url, publishableKey, {
  auth: { storage: window.sessionStorage, detectSessionInUrl: true },
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
