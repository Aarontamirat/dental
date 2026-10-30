import { useEffect, useRef } from 'react';
import { ArrowLeft, ArrowRight, Check, LoaderCircle, X } from 'lucide-react';
import type { Errors, Pagination } from './types';

export function Loading() { return <div className="admin-loading" role="status"><LoaderCircle className="spin" size={22} /> Loading your workspace…</div>; }
export function ErrorNotice({ error }: { error: string }) { return error ? <div className="admin-error" role="alert">{error}</div> : null; }
export function Empty({ title = 'Nothing here yet', description = 'Add your first item to get started.' }: { title?: string; description?: string }) { return <div className="admin-empty"><span>✦</span><h2>{title}</h2><p>{description}</p></div>; }
export function Status({ value }: { value: unknown }) { return <span className={`status-badge status-${String(value)}`}>{String(value ?? 'draft').replaceAll('_', ' ')}</span>; }
export function Pager({ page, onPage }: { page: Pagination; onPage: (page: number) => void }) {
  return <div className="admin-pagination"><span>{page.total} items · Page {page.current_page} of {page.last_page}</span><div><button aria-label="Previous page" disabled={page.current_page === 1} onClick={() => onPage(page.current_page - 1)}><ArrowLeft size={16} /></button><button aria-label="Next page" disabled={page.current_page === page.last_page} onClick={() => onPage(page.current_page + 1)}><ArrowRight size={16} /></button></div></div>;
}
export function Modal({ title, children, onClose, wide = false }: { title: string; children: React.ReactNode; onClose: () => void; wide?: boolean }) {
  const ref = useRef<HTMLDialogElement>(null);
  useEffect(() => {
    const dialog = ref.current;
    const previousFocus = document.activeElement;
    dialog?.showModal();
    return () => { dialog?.close(); if (previousFocus instanceof HTMLElement) previousFocus.focus(); };
  }, []);
  return <dialog ref={ref} className={`admin-modal ${wide ? 'wide' : ''}`} onKeyDownCapture={event => { if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); onClose(); } }} onCancel={event => { event.preventDefault(); onClose(); }}><div className="modal-header"><h2>{title}</h2><button type="button" aria-label="Close dialog" onClick={onClose}><X size={20} /></button></div><div className="modal-body">{children}</div></dialog>;
}
export function Toast({ message, clear }: { message: string; clear: () => void }) {
  useEffect(() => { if (!message) return; const timeout = window.setTimeout(clear, 4500); return () => window.clearTimeout(timeout); }, [message, clear]);
  return message ? <div className="admin-toast" role="status"><Check size={18} />{message}<button aria-label="Dismiss notification" onClick={clear}><X size={16} /></button></div> : null;
}
export function FormErrors({ errors, message }: { errors: Errors; message: string }) {
  return message ? <div className="admin-error" role="alert"><strong>{message}</strong>{Object.keys(errors).length > 0 && <ul>{Object.values(errors).flat().map((error, i) => <li key={i}>{error}</li>)}</ul>}</div> : null;
}
export function SaveButton({ busy, text = 'Save changes' }: { busy: boolean; text?: string }) { return <button className="admin-button" type="submit" disabled={busy}>{busy ? <LoaderCircle className="spin" size={16} /> : <Check size={16} />}{busy ? 'Saving…' : text}</button>; }
