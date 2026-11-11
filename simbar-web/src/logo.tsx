// The SIMBAR mark: the same three stacked boxes as the app icon, centred in its tile at any size.
export function LogoMark() {
  return <svg className="logo-mark" viewBox="116 104 280 248" aria-hidden="true" focusable="false">
    <g fill="#ffffff" stroke="#0f766e" strokeWidth="12" strokeLinejoin="round">
      <rect x="196" y="120" width="120" height="104" rx="14" />
      <rect x="132" y="232" width="120" height="104" rx="14" />
      <rect x="260" y="232" width="120" height="104" rx="14" />
    </g>
    <g stroke="#0f766e" strokeWidth="12" strokeLinecap="round">
      <line x1="256" y1="120" x2="256" y2="160" />
      <line x1="192" y1="232" x2="192" y2="272" />
      <line x1="320" y1="232" x2="320" y2="272" />
    </g>
  </svg>;
}

export const BRAND_FULL = 'Sistem Manajemen Barang';
