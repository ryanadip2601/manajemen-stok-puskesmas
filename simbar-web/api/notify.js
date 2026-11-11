import { timingSafeEqual } from 'node:crypto';
import webpush from 'web-push';
import { error, headers } from './_shared.js';

export default { fetch: handler };

// POST /api/notify — called by the database (pg_net) when a request is created or changes status.
// Body: { messages: [{ subscription: { endpoint, keys: { p256dh, auth } }, payload: { title, body, url, tag, badge } }] }
async function handler(request) {
  if (request.method !== 'POST') return error(405, 'Metode tidak didukung.');
  const secret = process.env.PUSH_NOTIFY_SECRET?.trim();
  const publicKey = process.env.VITE_VAPID_PUBLIC_KEY?.trim();
  const privateKey = process.env.VAPID_PRIVATE_KEY?.trim();
  if (!secret || !publicKey || !privateKey) return error(503, 'Notifikasi belum dikonfigurasi.');

  const given = Buffer.from(request.headers.get('authorization') ?? '');
  const expected = Buffer.from('Bearer ' + secret);
  if (given.length !== expected.length || !timingSafeEqual(given, expected)) return error(401, 'Akses ditolak.');

  const body = await request.json().catch(() => null);
  const messages = Array.isArray(body?.messages) ? body.messages.slice(0, 200) : null;
  if (!messages) return error(400, 'Format notifikasi tidak valid.');

  webpush.setVapidDetails(process.env.VAPID_SUBJECT?.trim() || 'https://simbarkro.brey26.id', publicKey, privateKey);
  const gone = [];
  let sent = 0;
  await Promise.all(messages.map(async ({ subscription, payload }) => {
    if (typeof subscription?.endpoint !== 'string' || !subscription.endpoint.startsWith('https://')) return;
    try {
      await webpush.sendNotification(subscription, JSON.stringify(payload ?? {}), { TTL: 60 * 60 * 24, urgency: 'high', timeout: 8000 });
      sent++;
    } catch (failure) {
      // 404/410: the browser dropped this subscription (app uninstalled, permission withdrawn).
      if (failure?.statusCode === 404 || failure?.statusCode === 410) gone.push(subscription.endpoint);
    }
  }));

  if (gone.length) await forget(secret, gone).catch(() => {});
  return Response.json({ sent, gone: gone.length }, { headers });
}

async function forget(secret, endpoints) {
  const url = process.env.VITE_SUPABASE_URL;
  const key = process.env.VITE_SUPABASE_PUBLISHABLE_KEY;
  if (!url || !key) return;
  await fetch(new URL('/rest/v1/rpc/simbar_push_forget', url), {
    method: 'POST',
    headers: { apikey: key, 'Content-Type': 'application/json' },
    body: JSON.stringify({ notify_secret: secret, endpoints }),
    signal: AbortSignal.timeout(8000),
  });
}
