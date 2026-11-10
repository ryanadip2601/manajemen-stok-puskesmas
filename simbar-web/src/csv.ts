// Downloads rows as a UTF-8 CSV with BOM so Excel opens Indonesian text correctly (like the Laravel exports).
export function downloadCsv(filename: string, header: string[], rows: (string | number | null | undefined)[][]) {
  const cell = (value: string | number | null | undefined) => {
    const text = value === null || value === undefined ? '' : String(value);
    // Prefix formula-looking cells so a spreadsheet never evaluates them.
    const safe = /^[=+@\t\r]|^-[^\d\s]/.test(text) ? "'" + text : text;
    return /[",\n\r]/.test(safe) ? '"' + safe.replace(/"/g, '""') + '"' : safe;
  };
  const body = [header, ...rows].map((row) => row.map(cell).join(',')).join('\r\n');
  const url = URL.createObjectURL(new Blob(['﻿' + body], { type: 'text/csv;charset=utf-8' }));
  const link = Object.assign(document.createElement('a'), { href: url, download: filename });
  document.body.append(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export const stamp = () => new Date(Date.now() + 7 * 3600_000).toISOString().slice(0, 19).replace('T', '_').replace(/:/g, '');
