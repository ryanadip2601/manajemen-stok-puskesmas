import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { ItemThumb } from './media';

// A searchable replacement for the long "Pilih barang" lists: type part of a name, code or jenis barang,
// see stock and satuan beside every match, grouped by jenis barang. Works inside forms (name + required)
// and as a controlled field (value + onChange).
export type PickItem = { id: number; name: string; code?: string; stock?: number; unit?: string; category?: string; note?: string; photo?: string | null };

const number = new Intl.NumberFormat('id-ID');
const fold = (text: string) => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

export function ItemPicker({ items, value, defaultValue = '', onChange, name, required, placeholder = 'Ketik nama, kode atau jenis barang…',
  allLabel, disabledIds, label = 'Barang' }: {
  items: PickItem[]; value?: string; defaultValue?: string; onChange?: (id: string) => void; name?: string; required?: boolean;
  placeholder?: string; allLabel?: string; disabledIds?: Set<string>; label?: string;
}) {
  const [own, setOwn] = useState(defaultValue);
  const selected = value ?? own;
  const current = items.find((item) => String(item.id) === selected);
  const [query, setQuery] = useState('');
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(0);
  const listId = useId();
  const box = useRef<HTMLDivElement>(null);

  const matches = useMemo(() => {
    const words = fold(query).split(/\s+/).filter(Boolean);
    const found = items.filter((item) => {
      const text = fold(`${item.name} ${item.code ?? ''} ${item.category ?? ''}`);
      return words.every((word) => text.includes(word));
    });
    found.sort((a, b) => (a.category ?? '').localeCompare(b.category ?? '') || a.name.localeCompare(b.name));
    return found.slice(0, 60);
  }, [items, query]);
  const options: (PickItem | null)[] = allLabel && !query ? [null, ...matches] : matches;

  useEffect(() => {
    if (!open) return;
    const close = (event: MouseEvent) => { if (!box.current?.contains(event.target as Node)) setOpen(false); };
    document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, [open]);
  useEffect(() => { setActive(0); }, [query, open]);

  function choose(item: PickItem | null) {
    if (item && disabledIds?.has(String(item.id)) && String(item.id) !== selected) return;
    const id = item ? String(item.id) : '';
    if (value === undefined) setOwn(id);
    onChange?.(id);
    setQuery('');
    setOpen(false);
  }

  function key(event: React.KeyboardEvent<HTMLInputElement>) {
    if (event.key === 'ArrowDown') { event.preventDefault(); setOpen(true); setActive((i) => Math.min(i + 1, options.length - 1)); }
    else if (event.key === 'ArrowUp') { event.preventDefault(); setActive((i) => Math.max(i - 1, 0)); }
    else if (event.key === 'Enter' && open) { event.preventDefault(); if (options[active] !== undefined) choose(options[active]); }
    else if (event.key === 'Escape') { setOpen(false); setQuery(''); }
  }

  const shown = open ? query : current ? `${current.name}${current.code ? ' (' + current.code + ')' : ''}` : allLabel && !selected ? allLabel : '';
  let lastCategory: string | undefined;
  return <div className="item-picker" ref={box}>
    {current && !open && current.photo !== undefined ? <span className="item-picker-icon photo"><ItemThumb url={current.photo} name={current.name} size={26} zoom={false} /></span>
      : <i className="fas fa-magnifying-glass item-picker-icon" aria-hidden="true" />}
    <input type="text" role="combobox" aria-expanded={open} aria-controls={listId} aria-autocomplete="list" aria-label={label}
      aria-activedescendant={open && options.length ? `${listId}-${active}` : undefined}
      value={shown} placeholder={placeholder} autoComplete="off"
      onFocus={() => { setOpen(true); setQuery(''); }} onChange={(event) => { setQuery(event.target.value); setOpen(true); }} onKeyDown={key} />
    {current && current.stock !== undefined && !open && <span className="item-picker-stock">stok {number.format(current.stock)} {current.unit}</span>}
    {/* Carries the value into the form and lets the browser enforce "required". */}
    <input className="item-picker-value" tabIndex={-1} aria-hidden="true" name={name} required={required} value={selected} onChange={() => {}} />
    {open && <ul className="item-picker-list" id={listId} role="listbox" aria-label={label}>
      {!options.length && <li className="item-picker-empty">Tidak ada barang yang cocok.</li>}
      {options.map((item, index) => {
        const heading = item?.category && item.category !== lastCategory ? item.category : null;
        if (item) lastCategory = item.category;
        const disabled = !!item && !!disabledIds?.has(String(item.id)) && String(item.id) !== selected;
        return [heading && <li key={'h' + heading} className="item-picker-group" role="presentation">{heading}</li>,
          <li key={item ? item.id : 'all'} id={`${listId}-${index}`} role="option" aria-selected={item ? String(item.id) === selected : !selected}
            aria-disabled={disabled} className={'item-picker-option' + (index === active ? ' active' : '') + (disabled ? ' disabled' : '')}
            onMouseDown={(event) => { event.preventDefault(); choose(item); }} onMouseEnter={() => setActive(index)}>
            {item ? <>{item.photo !== undefined && <ItemThumb url={item.photo} name={item.name} size={34} zoom={false} />}<span className="item-picker-text"><span className="item-picker-name">{item.name}</span>
              <span className="item-picker-meta">{[item.code, item.stock !== undefined ? `stok ${number.format(item.stock)} ${item.unit ?? ''}` : null, item.note]
                .filter(Boolean).join(' · ')}{disabled ? ' · sudah dipilih' : ''}</span></span></> : <span className="item-picker-name">{allLabel}</span>}
          </li>];
      })}
    </ul>}
  </div>;
}
