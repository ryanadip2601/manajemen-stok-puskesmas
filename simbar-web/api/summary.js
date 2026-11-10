import { error, headers, readPhoto, rpc } from './_shared.js';

export default { fetch: handler };

async function handler(request) {
  if (request.method !== 'GET') return error(405, 'Metode tidak didukung.');

  const query = new URL(request.url).searchParams;
  const kind = query.get('kind');
  const id = query.get('id');
  const list = query.get('list');
  const search = (query.get('q') ?? '').trim();
  const page = query.get('page') ?? '1';
  const photoRequested = kind !== null || id !== null;
  if (list !== null && (photoRequested || !['items', 'assets'].includes(list) ||
      search.length > 100 || !/^[1-9][0-9]{0,4}$/.test(page) || Number(page) > 10000)) {
    return error(400, 'Filter inventaris tidak valid.');
  }
  if (photoRequested && (!['asset', 'maintenance'].includes(kind) || !/^[1-9][0-9]{0,14}$/.test(id ?? ''))) {
    return error(400, 'Referensi foto tidak valid.');
  }

  try {
    const rpcName = list === null ? 'simbar_preview' : 'simbar_preview_inventory';
    let params = {};
    if (list !== null) params = { list_kind: list, search_text: search, page_number: Number(page) };
    if (photoRequested) params = { attachment_kind: kind, attachment_id: Number(id) };
    const { data, response } = await rpc(request, rpcName, params);
    if (response) return response;
    if (!photoRequested) return Response.json(data, { headers });
    if (!data) return error(404, 'Foto tidak ditemukan.');
    return await readPhoto(data);
  } catch {
    return error(502, 'Layanan SIMBAR belum bisa dihubungi.');
  }
}
