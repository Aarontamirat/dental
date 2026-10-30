import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { ArrowDown, ArrowUp, Check, Download, Eye, LoaderCircle, Monitor, RotateCcw, Smartphone, Sparkles, Tablet, Upload, Undo2 } from 'lucide-react';
import { api, ApiError, useRemote } from './api';
import { ErrorNotice, Loading, Modal } from './components';
import { label } from './types';
import '../../css/appearance.css';

type Value = string | number | boolean;
type Palette = Record<string, string>;
type Configuration = {
  version: number; preset: string; mode: string;
  colors: { light: Palette; dark: Palette };
  sections: Record<string, Record<string, string>>;
  typography: Record<string, Value>; layout: Record<string, Value>; buttons: Record<string, Value>;
  cards: Record<string, Value>; header: Record<string, Value>; footer: Record<string, Value>; motion: Record<string, Value>;
};
type Group = 'typography' | 'layout' | 'buttons' | 'cards' | 'header' | 'footer' | 'motion';
type Field = { label: string; type: string; default: Value; min?: number; max?: number; unit?: string; options?: string[] };
type Homepage = { key: string; enabled: boolean; sort_order: number }[];
type Draft = { configuration: Configuration; homepage: Homepage; revision: number };
type ValidatedDraft = Pick<Draft, 'configuration' | 'homepage'>;
type AppearanceData = Draft & {
  schema: Record<Group, Record<string, Field>>; defaults: Configuration; fonts: Record<string, string>;
  presets: Record<string, { name: string; configuration: Configuration }>;
  sections: Record<string, Record<string, string[]>>; warnings: string[];
};
const categories = [
  ['overview', 'Overview'], ['presets', 'Theme presets'], ['colors', 'Colors'], ['typography', 'Typography'],
  ['layout', 'Layout & spacing'], ['buttons', 'Buttons'], ['cards', 'Cards & inputs'], ['header', 'Navigation'],
  ['footer', 'Footer'], ['homepage', 'Homepage'], ['motion', 'Animations'], ['advanced', 'Backup & restore'],
];
const pages = [['/', 'Homepage'], ['/about', 'About'], ['/services', 'Services'], ['/doctors', 'Team'], ['/branches', 'Branches'], ['/appointments', 'Appointments'], ['/articles', 'Articles'], ['/gallery', 'Gallery'], ['/faq', 'FAQ'], ['/contact', 'Contact'], ['/testimonials', 'Testimonials'], ['/pages/privacy', 'Privacy']];
const descriptions: Record<string, string> = {
  clinical: 'Clear blue, crisp typography and calm clinical surfaces.', premium: 'Editorial serif headings, natural green and restrained warm accents.',
  minimal: 'Generous spacing, sharp edges and an understated centered composition.', family: 'Warm terracotta, soft corners and a welcoming reading rhythm.',
  technology: 'Indigo, elevated panels and an immersive opening.', elegant: 'A centered masthead, refined violet and classic typography.',
};
const fontNames: Record<string, string> = { 'dm-sans': 'DM Sans · modern', 'dm-serif': 'DM Serif Display · editorial', humanist: 'System humanist', classic: 'Georgia · classic', ethiopic: 'Noto Sans Ethiopic' };
const pairs = [
  ['text','background'], ['text','surface'], ['muted','background'], ['muted','surface'], ['heading','background'], ['heading','surface'], ['heading','card'],
  ['text','card'], ['muted','card'], ['primary','background'], ['primary','surface'], ['primary','card'], ['link','background'], ['link','surface'], ['link','card'],
  ['button_text','button'], ['button_text','button_hover'], ['nav_text','nav_background'], ['nav_text','sticky_background'],
  ['footer_text','footer_background'], ['footer_link','footer_background'], ['text','input_background'], ['muted','input_background'],
  ['success','surface'], ['warning','surface'], ['error','surface'], ['info','surface'], ['input_border','input_background'], ['input_border','background'], ['input_border','surface'],
];
function contrast(a: string, b: string): number {
  function luminance(hex: string) {
    const channels = [1,3,5].map(start => parseInt(hex.slice(start,start + 2),16) / 255).map(c => c <= .04045 ? c / 12.92 : ((c + .055) / 1.055) ** 2.4);
    return channels[0] * .2126 + channels[1] * .7152 + channels[2] * .0722;
  }
  const first = luminance(a), second = luminance(b);
  return (Math.max(first,second) + .05) / (Math.min(first,second) + .05);
}
function warningsFor(configuration: Configuration): string[] {
  return Object.entries(configuration.colors).flatMap(([mode,palette]) => pairs.flatMap(([foreground,background]) => {
    if (!/^#[\da-f]{6}$/i.test(palette[foreground]) || !/^#[\da-f]{6}$/i.test(palette[background])) return [];
    const ratio = contrast(palette[foreground],palette[background]), minimum = foreground === 'input_border' ? 3 : 4.5;
    return ratio < minimum ? [`${label(mode)}: ${label(foreground)} on ${label(background)} · ${ratio.toFixed(2)}:1 (minimum ${minimum}:1)`] : [];
  }));
}
export function Appearance({ userId, notify }: { userId: number; notify: (message: string) => void }) {
  const remote = useRemote<AppearanceData>('/appearance');
  return remote.loading ? <Loading/> : remote.error ? <ErrorNotice error={remote.error}/> : remote.data ? <AppearanceEditor initial={remote.data} userId={userId} notify={notify}/> : null;
}
function AppearanceEditor({ initial, userId, notify }: { initial: AppearanceData; userId: number; notify: (message: string) => void }) {
  const [published,setPublished] = useState<Draft>({ configuration: initial.configuration, homepage: initial.homepage, revision: initial.revision });
  const [configuration,setConfiguration] = useState(initial.configuration);
  const [homepage,setHomepage] = useState(initial.homepage);
  const [category,setCategory] = useState('overview');
  const [paletteMode,setPaletteMode] = useState<'light' | 'dark'>('light');
  const [viewport,setViewport] = useState('desktop');
  const [path,setPath] = useState('/');
  const [preview,setPreview] = useState<{ token: string; source: string } | null>(null);
  const [previewBusy,setPreviewBusy] = useState(false);
  const [previewError,setPreviewError] = useState('');
  const [busy,setBusy] = useState(false);
  const [error,setError] = useState('');
  const [conflict,setConflict] = useState(false);
  const draftKey = `clinic-appearance:${location.origin}:${userId}`;
  const [storedDraft] = useState(() => {
    try {
      const saved = sessionStorage.getItem(draftKey);
      const draft = saved ? JSON.parse(saved) as Draft : null;
      const valid = draft?.configuration?.version === 1 && Array.isArray(draft.homepage);
      return { draft: valid && JSON.stringify({ configuration: draft.configuration, homepage: draft.homepage }) !== JSON.stringify({ configuration: initial.configuration, homepage: initial.homepage }) ? draft : null, error: '' };
    } catch { return { draft: null, error: 'Draft recovery is unavailable in this browser. Keep this tab open until you publish.' }; }
  });
  const [storageError,setStorageError] = useState(storedDraft.error);
  const [recovery,setRecovery] = useState<Draft | null>(storedDraft.draft);
  const [confirm,setConfirm] = useState<'reset' | 'discard' | null>(null);
  const [previewRefresh,setPreviewRefresh] = useState(0);
  const form = useRef<HTMLFormElement>(null);
  const importInput = useRef<HTMLInputElement>(null);
  const stage = useRef<HTMLDivElement>(null);
  const [stageWidth,setStageWidth] = useState(600);
  const frame = useRef<HTMLIFrameElement>(null);
  const previewPosition = useRef(0);
  const source = JSON.stringify({ configuration, homepage });
  const dirty = source !== JSON.stringify({ configuration: published.configuration, homepage: published.homepage });
  const warnings = warningsFor(configuration);

  useEffect(() => {
    if (recovery) return;
    try {
      if (dirty) sessionStorage.setItem(draftKey,JSON.stringify({ configuration,homepage,revision:published.revision }));
      else sessionStorage.removeItem(draftKey);
    } catch { queueMicrotask(() => setStorageError('Draft recovery is unavailable in this browser. Keep this tab open until you publish.')); }
  }, [configuration,homepage,published.revision,dirty,draftKey,recovery]);
  useEffect(() => {
    const beforeUnload = (event: BeforeUnloadEvent) => { if (dirty) { event.preventDefault(); event.returnValue = ''; } };
    window.addEventListener('beforeunload',beforeUnload);
    return () => window.removeEventListener('beforeunload',beforeUnload);
  }, [dirty]);
  useEffect(() => {
    if (!stage.current) return;
    const observer = new ResizeObserver(entries => setStageWidth(entries[0].contentRect.width));
    observer.observe(stage.current);
    return () => observer.disconnect();
  }, []);
  useEffect(() => {
    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setPreviewBusy(true); setPreviewError('');
      try {
        const result = await api<{ token: string }>('/appearance/preview','POST',JSON.parse(source),controller.signal);
        if (!controller.signal.aborted) {
          previewPosition.current = frame.current?.contentWindow?.scrollY ?? 0;
          const currentAddress = frame.current?.contentWindow?.location;
          if (currentAddress?.origin === location.origin) {
            const currentUrl = new URL(currentAddress.href);
            currentUrl.searchParams.delete('appearance_preview');
            setPath(currentUrl.pathname + currentUrl.search);
          }
          setPreview({ token: result.token,source });
        }
      } catch (e) {
        if (!controller.signal.aborted) {
          const problem = e as ApiError;
          setPreviewError(Object.values(problem.errors ?? {}).flat().join(' ') || problem.message);
        }
      } finally { if (!controller.signal.aborted) setPreviewBusy(false); }
    }, 650);
    return () => { window.clearTimeout(timeout); controller.abort(); };
  }, [source,previewRefresh]);

  function update(group: Group, key: string, value: Value) {
    setConfiguration(current => ({ ...current,[group]: { ...current[group],[key]: value } }));
  }
  function updateColor(key: string, value: string) {
    setConfiguration(current => ({ ...current,colors: { ...current.colors,[paletteMode]: { ...current.colors[paletteMode],[key]: value } } }));
  }
  function updateSection(section: string, key: string, value: string) {
    setConfiguration(current => ({ ...current,sections: { ...current.sections,[section]: { ...current.sections[section],[key]: value } } }));
  }
  function choosePreset(key: string) {
    setConfiguration({ ...structuredClone(initial.presets[key].configuration),mode: configuration.mode });
    setError('');
  }
  function moveSection(index: number, direction: number) {
    setHomepage(current => {
      const next = [...current]; [next[index],next[index + direction]] = [next[index + direction],next[index]];
      return next.map((section,sort_order) => ({ ...section,sort_order }));
    });
  }
  async function publish() {
    if (!form.current?.reportValidity() || warnings.length) return;
    setBusy(true); setError(''); setConflict(false);
    try {
      const result = await api<Draft>('/appearance','PUT',{ configuration,homepage,revision: published.revision });
      setConfiguration(result.configuration);
      setHomepage(result.homepage);
      setPublished(result);
      notify('Appearance published. Your website now uses these settings.');
    } catch (e) {
      const problem = e as ApiError;
      setError(Object.values(problem.errors ?? {}).flat().join(' ') || problem.message);
      setConflict(problem.status === 409);
    } finally { setBusy(false); }
  }
  async function reloadPublished() {
    setBusy(true);
    try {
      const current = await api<AppearanceData>('/appearance');
      setPublished({ configuration: current.configuration,homepage: current.homepage,revision: current.revision });
      setConflict(false); setError('Published settings loaded. Your draft is still available; review it before publishing or discard it to use the published version.');
    } catch (e) { setError((e as Error).message); } finally { setBusy(false); }
  }
  async function recoverDraft() {
    if (!recovery) return;
    setBusy(true); setError('');
    try {
      const validated = await api<ValidatedDraft>('/appearance/preview','POST',{ configuration: recovery.configuration, homepage: recovery.homepage });
      setConfiguration(validated.configuration); setHomepage(validated.homepage); setRecovery(null);
    } catch (e) {
      setError(`The stored draft could not be recovered. ${(e as Error).message}`);
    } finally { setBusy(false); }
  }
  function exportConfiguration() {
    const blob = new Blob([JSON.stringify({ configuration,homepage },null,2)],{ type: 'application/json' });
    const url = URL.createObjectURL(blob), link = document.createElement('a');
    link.href = url; link.download = 'clinic-appearance.json'; link.click(); window.setTimeout(() => URL.revokeObjectURL(url),1000);
  }
  async function importConfiguration(file?: File) {
    if (!file) return;
    setBusy(true); setError('');
    try {
      if (file.size > 100000) throw new Error('Choose an appearance backup smaller than 100 KB.');
      const incoming = JSON.parse(await file.text()) as { configuration: Configuration; homepage?: Homepage };
      const next = { configuration: incoming.configuration,homepage: incoming.homepage ?? homepage };
      const validated = await api<ValidatedDraft>('/appearance/preview','POST',next);
      setConfiguration(validated.configuration); setHomepage(validated.homepage); notify('Backup imported into your draft. Review the preview before publishing.');
    } catch (e) { const problem = e as ApiError; setError(Object.values(problem.errors ?? {}).flat().join(' ') || problem.message || 'This backup is invalid.'); }
    finally { setBusy(false); if (importInput.current) importInput.current.value = ''; }
  }
  const sectionName = categories.find(([key]) => key === category)?.[1] ?? 'Appearance';
  function fieldIsApplicable(key: string): boolean {
    if (category !== 'footer') return true;
    if (key === 'columns') return configuration.footer.style === 'expanded';
    if (['contact', 'branches', 'social_style'].includes(key)) return configuration.footer.style !== 'minimal';
    return true;
  }
  const deviceWidth = viewport === 'mobile' ? 390 : viewport === 'tablet' ? 768 : 1280;
  const scale = Math.min(1,stageWidth / deviceWidth);
  const previewUrl = new URL(path, location.origin);
  if (preview) previewUrl.searchParams.set('appearance_preview', preview.token);
  return <div className="appearance-workspace">
    <div className="admin-page-title"><div><span className="admin-eyebrow">YOUR CLINIC, YOUR IDENTITY</span><h1>Appearance studio</h1><p>A considered design, with room to make it your own.</p></div><span className={`appearance-state ${dirty ? 'draft' : ''}`}><span/>{dirty ? 'Unpublished draft' : `Published · revision ${published.revision}`}</span></div>
    {storageError && <ErrorNotice error={storageError}/>}
    {recovery && <div className="appearance-recovery"><div><strong>A previous draft is available.</strong><p>{recovery.revision !== published.revision ? 'The website has changed since this draft. Review it carefully before publishing.' : 'Recover changes from your earlier visit to this tab.'}</p></div><button className="admin-button" disabled={busy} onClick={() => void recoverDraft()}>Recover draft</button><button className="admin-button secondary" onClick={() => { setRecovery(null);try { sessionStorage.removeItem(draftKey); } catch { setStorageError('Could not remove the stored draft.'); } }}>Dismiss draft</button></div>}
    <nav className="appearance-categories" aria-label="Appearance categories">{categories.map(([key,title]) => <button key={key} type="button" aria-current={category === key ? 'true' : undefined} className={category === key ? 'active' : ''} onClick={() => setCategory(key)}>{title}</button>)}</nav>
    <div className="appearance-grid">
      <form ref={form} className="appearance-controls admin-card" onSubmit={event => { event.preventDefault();void publish(); }}>
        <div className="appearance-panel-title"><span className="admin-eyebrow">DESIGN SETTINGS</span><h2>{sectionName}</h2></div>
        <fieldset disabled={busy} className="appearance-fields">
        {category === 'overview' && <>
          <div className="appearance-introduction"><Sparkles size={32}/><h3>Make a memorable first impression.</h3><p>Start with a complete visual preset, refine the details, then publish when the preview feels right for your clinic.</p></div>
          <label className="appearance-field"><span>Website color mode</span><select value={configuration.mode} onChange={event => setConfiguration(current => ({ ...current,mode: event.target.value }))}><option value="light">Light</option><option value="dark">Dark</option><option value="system">Follow visitor’s system preference</option></select><small>Light and dark palettes can be edited separately in Colors.</small></label>
          <div className="appearance-checklist"><p><Check size={18}/> Local English and Amharic fonts</p><p><Check size={18}/> Preview changes remain private</p><p><Check size={18}/> Reduced motion always respected</p></div>
          <button type="button" className="admin-button" onClick={() => setCategory('presets')}>Explore theme presets <ArrowUp size={16}/></button>
        </>}
        {category === 'presets' && <><p className="appearance-help">Selecting a preset replaces design tokens in your draft. Clinic content, section visibility and order are preserved.</p><div className="appearance-presets">{Object.entries(initial.presets).map(([key,preset]) => {
          const colors = preset.configuration.colors.light;
          return <button type="button" className={`appearance-preset ${configuration.preset === key ? 'selected' : ''}`} key={key} aria-pressed={configuration.preset === key} onClick={() => choosePreset(key)}>
            <span className={`preset-mini preset-${key}`} style={{ '--preset-primary': colors.primary,'--preset-surface': colors.surface,'--preset-background': colors.background,'--preset-heading': colors.heading,'--preset-radius': `${preset.configuration.buttons.radius}px`,fontFamily: initial.fonts[String(preset.configuration.typography.heading_font)] } as CSSProperties}><span className="preset-mini-nav">✦<i/><i/><i/></span><span className="preset-mini-body"><strong>Care for your<br/>everyday smile.</strong><span className="preset-mini-button"/><span className="preset-mini-photo"/></span><span className="preset-mini-cards"><i/><i/><i/></span></span>
            <strong>{preset.name}{configuration.preset === key && <Check size={16}/>}</strong><span>{descriptions[key]}</span><span className="preset-swatches">{['primary','secondary','accent','background'].map(color => <i key={color} style={{ background: colors[color] }}/>)}</span>
          </button>;
        })}</div></>}
        {category === 'colors' && <>
          <div className="appearance-mode-switch" aria-label="Palette to edit">{(['light','dark'] as const).map(mode => <button key={mode} type="button" aria-pressed={paletteMode === mode} className={paletteMode === mode ? 'active' : ''} onClick={() => setPaletteMode(mode)}>{label(mode)} palette</button>)}</div>
          <p className="appearance-help">Six-digit hex colors. The website uses the color mode selected in Overview. Contrast is checked for both palettes before publishing.</p>
          <div className="appearance-color-grid">{Object.entries(configuration.colors[paletteMode]).map(([key,value]) => <div className="appearance-color" key={key}><label htmlFor={`hex-${key}`}>{label(key)}</label><div><input type="color" aria-label={`${label(key)} color picker`} value={/^#[\da-f]{6}$/i.test(value) ? value : '#000000'} onChange={event => updateColor(key,event.target.value)}/><input id={`hex-${key}`} type="text" value={value} maxLength={7} pattern="#[0-9a-fA-F]{6}" required onChange={event => updateColor(key,event.target.value)} spellCheck={false}/><button type="button" aria-label={`Reset ${label(key)} color`} onClick={() => updateColor(key,initial.presets[configuration.preset].configuration.colors[paletteMode][key])}><RotateCcw size={15}/></button></div></div>)}</div>
        </>}
        {Object.keys(initial.schema).includes(category) && <>
          {category === 'typography' && <p className="appearance-help">Every font stack includes locally hosted Ethiopic coverage. Mobile body text stays at least 16px; input text never falls below 16px.</p>}
          <div className="appearance-field-grid">{Object.entries(initial.schema[category as Group]).filter(([key]) => fieldIsApplicable(key)).map(([key,field]) => <TokenField key={key} field={field} value={configuration[category as Group][key]} onChange={value => update(category as Group,key,value)}/>)}</div>
          {category === 'typography' && <div className="appearance-type-preview" style={{ fontFamily: initial.fonts[String(configuration.typography.body_font)],fontSize: `${configuration.typography.body_size}px`,lineHeight: Number(configuration.typography.line_height) }}><h3 style={{ fontFamily: initial.fonts[String(configuration.typography.heading_font)],fontWeight: String(configuration.typography.heading_weight) }}>A healthier smile begins here.</h3><p>Comfortable, considered care for every stage of life.</p><p lang="am">ለጤናማ ፈገግታዎ የጥርስ እንክብካቤ።</p><small>Supporting text · {String(configuration.typography.small_size)}px</small></div>}
        </>}
        {category === 'homepage' && <>
          <p className="appearance-help">Preview visibility, order and presentation together. Publishing updates the existing homepage records. Edit section text in <a href="#sections">Homepage sections</a>.</p>
          <div className="appearance-sections">{homepage.map((section,index) => <details key={section.key}><summary><span className="appearance-section-number">{String(index + 1).padStart(2,'0')}</span><strong>{label(section.key)}</strong><span>{section.enabled ? 'Visible' : 'Hidden'}</span></summary><div className="appearance-section-body"><div className="appearance-section-actions"><label className="appearance-check"><input type="checkbox" checked={section.enabled} onChange={event => setHomepage(current => current.map(row => row.key === section.key ? { ...row,enabled: event.target.checked } : row))}/>Show this section</label><button type="button" aria-label={`Move ${section.key} up`} disabled={index === 0} onClick={() => moveSection(index,-1)}><ArrowUp size={17}/></button><button type="button" aria-label={`Move ${section.key} down`} disabled={index === homepage.length - 1} onClick={() => moveSection(index,1)}><ArrowDown size={17}/></button></div><div className="appearance-field-grid">{Object.entries(initial.sections[section.key] ?? {}).filter(([key,options]) => options.length > 1 && !(section.key === 'hero' && configuration.sections.hero.layout === 'centered' && ['image','alignment'].includes(key))).map(([key,options]) => <label className="appearance-field" key={key}><span>{label(key)}</span><select aria-label={`${section.key} ${key}`} value={configuration.sections[section.key][key]} onChange={event => updateSection(section.key,key,event.target.value)}>{options.map(option => <option key={option} value={option}>{label(option)}</option>)}</select></label>)}</div></div></details>)}</div>
        </>}
        {category === 'advanced' && <><p className="appearance-help">Keep a portable backup of your current draft. Importing validates the file and previews it before publication. Backups contain design settings and homepage order, without private clinic configuration.</p><div className="appearance-backup-actions"><button type="button" className="admin-button secondary" onClick={exportConfiguration}><Download size={17}/>Export appearance</button><button type="button" className="admin-button secondary" onClick={() => importInput.current?.click()}><Upload size={17}/>Import backup</button><input ref={importInput} hidden type="file" accept="application/json,.json" onChange={event => void importConfiguration(event.target.files?.[0])}/><button type="button" className="admin-button secondary" onClick={() => setConfirm('reset')}><RotateCcw size={17}/>Restore design defaults</button></div><p className="appearance-help">Defaults replace the draft’s visual settings. Content and section visibility stay intact until you explicitly change them.</p></>}
        </fieldset>
        {!!warnings.length && <details className="appearance-warnings" open><summary>{warnings.length} contrast combinations need attention</summary><p>Improve these colors before publishing. You can still inspect your private preview.</p><ul>{warnings.map(warning => <li key={warning}>{warning}</li>)}</ul></details>}
        <ErrorNotice error={error}/>{conflict && <button type="button" className="admin-button secondary" onClick={() => void reloadPublished()}>Load current published settings</button>}
        <div className="appearance-publish"><div><strong>{dirty ? 'Your draft is ready to review.' : 'Your published design.'}</strong><small>{warnings.length ? 'Resolve contrast warnings to publish.' : 'Only Publish changes updates the public website.'}</small></div><button type="button" className="admin-button secondary" disabled={!dirty || busy} onClick={() => setConfirm('discard')}><Undo2 size={16}/>Discard</button><button className="admin-button" type="submit" disabled={!dirty || busy || !!warnings.length}>{busy ? <LoaderCircle size={17} className="spin"/> : <Check size={17}/>} {busy ? 'Publishing…' : 'Publish changes'}</button></div>
      </form>
      <aside className="appearance-preview-panel" aria-label="Private website preview">
        <div className="appearance-preview-toolbar"><span><Eye size={17}/>Private preview</span><div>{[['desktop',Monitor],['tablet',Tablet],['mobile',Smartphone]].map(([key,Icon]) => { const DeviceIcon = Icon as typeof Monitor;return <button type="button" key={String(key)} aria-label={`${label(String(key))} preview`} aria-pressed={viewport === key} onClick={() => setViewport(String(key))}><DeviceIcon size={18}/></button>; })}<button type="button" aria-label="Refresh preview" onClick={() => setPreviewRefresh(current => current + 1)}><RotateCcw size={17}/></button></div></div>
        <label className="appearance-preview-page"><span>Preview page</span><select value={path} onChange={event => { previewPosition.current = 0; setPath(event.target.value); }}>{!pages.some(([url]) => url === path) && <option value={path}>Current page: {path}</option>}{pages.map(([url,title]) => <option key={url} value={url}>{title}</option>)}</select></label>
        <div className="appearance-preview-status" role="status">{previewError ? 'Preview needs valid settings.' : previewBusy || preview?.source !== source ? 'Updating preview…' : 'Preview up to date · unpublished'}{(previewBusy || preview?.source !== source) && !previewError && <LoaderCircle size={14} className="spin"/>}</div>
        <ErrorNotice error={previewError}/>
        <div ref={stage} className={`appearance-preview-stage viewport-${viewport}`}>{preview ? <iframe ref={frame} title="Private appearance preview" style={{ width: deviceWidth,height: 640 / scale,transform: `scale(${scale})` }} src={previewUrl.href} onLoad={() => {
          frame.current?.contentWindow?.scrollTo({ top: previewPosition.current, behavior: 'instant' });
          if (frame.current?.contentDocument?.title.startsWith('410')) setPreviewError('Preview expired. Use Refresh preview to continue.');
        }}/> : <div className="appearance-preview-empty"><Monitor size={36}/><p>Your live website preview will appear here.</p></div>}</div>
        <p className="appearance-preview-footnote">Forms are disabled in this preview. Desktop: 1280px · tablet: 768px · mobile: 390px. Navigate inside the preview to inspect individual pages.</p>
      </aside>
    </div>
    {confirm && <Modal title={confirm === 'reset' ? 'Restore design defaults?' : 'Discard this draft?'} onClose={() => setConfirm(null)}><p>{confirm === 'reset' ? 'The default Premium Dental design will replace your draft. Review the preview, then publish to apply it.' : 'Return to the latest published appearance and homepage order. Unpublished edits in this tab will be removed.'}</p><div className="modal-footer"><button className="admin-button secondary" onClick={() => setConfirm(null)}>Keep editing</button><button className="admin-button" onClick={() => { if (confirm === 'reset') setConfiguration(structuredClone(initial.defaults));else { setConfiguration(published.configuration);setHomepage(published.homepage); }setError('');setConfirm(null);setRecovery(null); }}>{confirm === 'reset' ? 'Restore defaults to draft' : 'Discard draft'}</button></div></Modal>}
  </div>;
}
function TokenField({ field,value,onChange }: { field: Field; value: Value; onChange: (value: Value) => void }) {
  if (field.type === 'boolean') return <label className="appearance-check"><input type="checkbox" checked={Boolean(value)} onChange={event => onChange(event.target.checked)}/>{field.label}</label>;
  return <label className="appearance-field"><span>{field.label}{field.unit && <small>{field.unit}</small>}</span>{field.type === 'select' ? <select value={String(value)} onChange={event => onChange(event.target.value)}>{field.options?.map(option => <option key={option} value={option}>{fontNames[option] ?? label(option)}</option>)}</select> : <input type={field.type === 'number' ? 'number' : 'text'} value={value == null ? '' : String(value)} min={field.min} max={field.max} step={field.unit === 'em' ? .005 : field.unit === '×' ? .05 : 1} required={field.type === 'number'} maxLength={field.type === 'text' ? 200 : undefined} onChange={event => { if (field.type === 'number') { if (Number.isFinite(event.target.valueAsNumber)) onChange(event.target.valueAsNumber); }else onChange(event.target.value); }}/>}</label>;
}
