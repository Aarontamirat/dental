import { lazy, Suspense, useState } from 'react';
import { ImagePlus, Plus, Trash2 } from 'lucide-react';
import { useRemote } from './api';
import { asArray, asObject, asText, label, type Field, type Options, type Errors, type Pagination, type RecordData } from './types';
import { Loading, Modal, Pager } from './components';
const RichEditor = lazy(() => import('./RichEditor').then(module => ({ default: module.RichEditor })));

export function FieldInput({ field, value, onChange, options, errors = {} }: { field: Field; value: unknown; onChange: (value: unknown) => void; options: Options; errors?: Errors }) {
  const [name, title, type, required, choices] = field;
  const id = `field-${name}`;
  const [picker, setPicker] = useState(false);
  const error = errors[name]?.join(' ');
  const common = { id, required: required ?? false, 'aria-invalid': !!error, 'aria-describedby': error ? `${id}-error` : undefined };
  let input;
  if (type === 'boolean') return <label className="admin-toggle"><input type="checkbox" checked={!!value} onChange={e => onChange(e.target.checked)} /><span className="toggle-track" /><span>{title}</span>{error && <small>{error}</small>}</label>;
  if (type === 'richtext') input = <Suspense fallback={<Loading />}><RichEditor id={id} value={asText(value)} onChange={onChange} /></Suspense>;
  else if (type === 'textarea' || type === 'lines') input = <textarea {...common} rows={type === 'lines' ? 4 : 3} value={type === 'lines' ? asArray(value).map(asText).join('\n') : asText(value)} onChange={e => onChange(type === 'lines' ? e.target.value.split('\n') : e.target.value)} />;
  else if (type === 'select' || type === 'relation') input = <select {...common} value={asText(value)} onChange={e => onChange(type === 'relation' ? e.target.value ? Number(e.target.value) : null : e.target.value)}><option value="">Select an option</option>{type === 'relation' ? (options[asText(choices)] ?? []).map(o => <option value={o.id} key={o.id}>{o.title}</option>) : (choices as string[]).map(o => <option value={o} key={o}>{label(o)}</option>)}</select>;
  else if (type === 'multirelation' || type === 'multiselect') {
    const selected = asArray(value);
    const entries = type === 'multirelation' ? (options[asText(choices)] ?? []).map(o => ({ value: o.id, title: o.title })) : (choices as string[]).map(o => ({ value: o, title: label(o) }));
    input = <div className="checkbox-options">{entries.length === 0 && <small>Add related content first.</small>}{entries.map(o => <label key={o.value}><input type="checkbox" checked={selected.includes(o.value)} onChange={e => onChange(e.target.checked ? [...selected, o.value] : selected.filter(v => v !== o.value))} />{o.title}</label>)}</div>;
  } else if (type === 'image') input = <div className="image-field">{value ? <img src={asText(value)} alt="Selected image preview" /> : <div className="image-placeholder"><ImagePlus size={25} /><span>No image selected</span></div>}<div><button type="button" className="admin-button secondary" onClick={() => setPicker(true)}>Choose from media</button>{!!value && <button type="button" className="admin-button subtle" onClick={() => onChange(null)}>Remove</button>}</div>{picker && <MediaPicker onPick={url => { onChange(url); setPicker(false); }} onClose={() => setPicker(false)} />}</div>;
  else if (['faq', 'links', 'hours', 'cards', 'stats'].includes(type)) input = <Repeater id={id} name={name} type={type} value={value} onChange={onChange} errors={errors} />;
  else if (type === 'color') input = <div className="color-field"><input type="color" {...common} value={asText(value) || '#176d67'} onChange={e => onChange(e.target.value)} /><input aria-label={`${title} hex value`} value={asText(value)} onChange={e => onChange(e.target.value)} pattern="#[0-9a-fA-F]{6}" /></div>;
  else input = <input {...common} type={type === 'number' ? 'number' : type === 'datetime' ? 'datetime-local' : ['email','tel','url','time','password'].includes(type) ? type : 'text'} value={type === 'datetime' && value ? asText(value).replace(' ', 'T').slice(0,16) : asText(value)} min={type === 'number' ? 0 : undefined} step={type === 'number' ? 'any' : undefined} onChange={e => onChange(type === 'number' ? e.target.value === '' ? null : Number(e.target.value) : e.target.value)} />;
  return <div className={`admin-field field-type-${type}`}><label htmlFor={id}>{title}{required ? <span> *</span> : null}</label>{input}{error && <small className="field-error" id={`${id}-error`}>{error}</small>}</div>;
}
function Repeater({ id, name, type, value, onChange, errors }: { id: string; name: string; type: string; value: unknown; onChange: (value: unknown) => void; errors: Errors }) {
  const rows = asArray(value).map(asObject);
  const fields = type === 'faq' ? ['question', 'answer'] : type === 'links' ? ['label', 'url'] : type === 'cards' ? ['icon', 'title', 'description'] : type === 'stats' ? ['label', 'value'] : ['day', 'open', 'close'];
  const update = (index: number, key: string, next: unknown) => onChange(rows.map((row, i) => i === index ? { ...row, [key]: next } : row));
  const add = () => {
    const row: Record<string, unknown> = Object.fromEntries(fields.map(key => [key, '']));
    if (type === 'hours') Object.assign(row, { day: ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'].find(day => !rows.some(r => r.day === day)) ?? 'Monday', open: '08:00', close: '18:00', closed: false });
    if (type === 'cards') row.icon = 'heart';
    if (type === 'stats') row.verified = false;
    onChange([...rows, row]);
  };
  return <div className="repeater" id={id}>
    {type === 'stats' && <small>Only verified statistics appear on the website. Verify every figure with the clinic before enabling it.</small>}
    {rows.map((row, index) => <div className={`repeater-row repeater-${type}`} key={index}>
      {fields.map(key => {
        const error = errors[`${name}.${index}.${key}`]?.join(' ');
        const choices = key === 'day' ? ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] : key === 'icon' ? ['heart','shield','smile','scan','tooth','sparkles'] : null;
        return <label key={key}><span>{label(key)}</span>{choices ? <select value={asText(row[key])} onChange={e => update(index, key, e.target.value)}>{choices.map(choice => <option key={choice} value={choice}>{label(choice)}</option>)}</select> : ['answer','description'].includes(key) ? <textarea value={asText(row[key])} onChange={e => update(index, key, e.target.value)} /> : <input type={['open','close'].includes(key) ? 'time' : key === 'url' ? 'url' : 'text'} value={asText(row[key])} disabled={type === 'hours' && !!row.closed && ['open','close'].includes(key)} onChange={e => update(index, key, e.target.value)} />}{error && <small className="field-error">{error}</small>}</label>;
      })}
      {['hours','stats'].includes(type) && <label className="closed-checkbox"><input type="checkbox" checked={!!row[type === 'hours' ? 'closed' : 'verified']} onChange={e => update(index, type === 'hours' ? 'closed' : 'verified', e.target.checked)} />{type === 'hours' ? 'Closed' : 'Verified'}</label>}
      <button type="button" aria-label={`Remove ${name} row ${index + 1}`} onClick={() => onChange(rows.filter((_, i) => i !== index))}><Trash2 size={16} /></button>
    </div>)}
    <button type="button" className="admin-button subtle" disabled={rows.length >= (type === 'hours' ? 7 : 10)} onClick={add}><Plus size={15} />Add {type === 'faq' ? 'question' : type === 'links' ? 'link' : type === 'cards' ? 'trust message' : type === 'stats' ? 'statistic' : 'day'}</button>
  </div>;
}
function MediaPicker({ onPick, onClose }: { onPick: (url: string) => void; onClose: () => void }) {
  const [page, setPage] = useState(1); const [q, setQ] = useState('');
  const { data, loading, error } = useRemote<Pagination>(`/media?page=${page}&q=${encodeURIComponent(q)}`);
  return <Modal title="Choose an image" onClose={onClose} wide><input type="search" placeholder="Search media…" aria-label="Search media" value={q} onChange={e => { setQ(e.target.value); setPage(1); }} />{loading ? <Loading /> : error ? <p>{error}</p> : <><div className="media-picker-grid">{data?.data.map((m: RecordData) => <button type="button" key={m.id} onClick={() => onPick(asText(m.url))}><img src={asText(m.url)} alt={asText(m.alt)} /><span>{asText(m.title)}</span></button>)}</div>{data?.data.length === 0 && <p className="form-note">Upload an image in the media library first.</p>}{data && <Pager page={data} onPage={setPage} />}</>}</Modal>;
}
