// Klient REST API - jedno miejsce, które dokleja token Bearer i obsługuje błędy API.
import { auth } from './auth'

export class ApiError extends Error {
  constructor(status, message, errors = {}) {
    super(message)
    this.status = status
    this.errors = errors // błędy walidacji (422): { pole: [komunikaty] }
  }
}

async function request(method, url, body) {
  const headers = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (auth.token) headers.Authorization = `Bearer ${auth.token}`

  let response
  try {
    response = await fetch(`/api${url}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(0, 'Brak połączenia z serwerem.')
  }

  if (response.status === 204) return null

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    // Wygasły / nieprawidłowy token -> wylogowanie i powrót do ekranu logowania.
    if (response.status === 401 && auth.token) {
      auth.clear()
      window.location.href = '/login'
    }
    throw new ApiError(response.status, data.message || `Błąd ${response.status}`, data.errors || {})
  }

  return data
}

export const api = {
  get: (url) => request('GET', url),
  post: (url, body = {}) => request('POST', url, body),
  patch: (url, body = {}) => request('PATCH', url, body),
  put: (url, body = {}) => request('PUT', url, body),
  delete: (url) => request('DELETE', url),
}

// Pierwszy komunikat walidacji danego pola (do wyświetlenia pod inputem).
export const fieldError = (error, field) => error?.errors?.[field]?.[0] ?? ''
