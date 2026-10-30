import { useEffect, useState } from 'react';
import type { Errors } from './types';

export class ApiError extends Error {
  constructor(message: string, public errors: Errors = {}, public status = 0) { super(message); }
}
export async function api<T>(path: string, method = 'GET', data?: unknown, signal?: AbortSignal): Promise<T> {
  const multipart = data instanceof globalThis.FormData;
  const response = await fetch(`/admin/api${path}`, {
    method, credentials: 'same-origin', signal,
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '', ...(!multipart ? { 'Content-Type': 'application/json' } : {}) },
    body: data === undefined ? undefined : multipart ? data : JSON.stringify(data),
  });
  const result = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message = [401, 419].includes(response.status) ? 'Your session expired. Reload the page and sign in again.' : result.message ?? 'The request could not be completed. Please try again.';
    throw new ApiError(message, result.errors ?? {}, response.status);
  }
  return result as T;
}
export function useRemote<T>(path: string, refresh = 0) {
  const key = `${path}:${refresh}`;
  const [state, setState] = useState<{ data: T | null; key: string; error: string }>({ data: null, key: '', error: '' });
  useEffect(() => {
    const controller = new AbortController();
    api<T>(path, 'GET', undefined, controller.signal).then(data => setState({ data, key, error: '' })).catch(error => {
      if (error.name !== 'AbortError') setState({ data: null, key, error: error.message });
    });
    return () => controller.abort();
  }, [path, key]);
  return { data: state.data, loading: state.key !== key, error: state.key === key ? state.error : '' };
}
