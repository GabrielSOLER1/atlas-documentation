import { reactive } from 'vue'

export const session = reactive({ user: null, loginToken: '', csrfToken: '', ready: false, error: '', flash: '' })

export async function api(path, options = {}) {
  const headers = { ...options.headers }
  if (options.body && !(options.body instanceof URLSearchParams)) headers['Content-Type'] = 'application/json'
  if (options.method && options.method !== 'GET') headers['X-CSRF-Token'] = session.csrfToken
  let response
  try {
    response = await fetch(`/api${path}`, { ...options, headers, credentials: 'same-origin' })
  } catch {
    throw new Error('Le portail est injoignable. Vérifiez votre connexion puis réessayez.')
  }
  const data = await response.json().catch(() => ({ error: 'Le serveur a renvoyé une réponse inattendue.' }))
  if (!response.ok) {
    if (response.status === 401) session.user = null
    const error = new Error(data.error || 'Cette action a échoué.')
    error.status = response.status
    throw error
  }
  return data
}

export async function refreshSession() {
  try {
    Object.assign(session, await api('/session'), { ready: true, error: '' })
  } catch (error) {
    if (error.status === 401) {
      try { Object.assign(session, await api('/session'), { ready: true, error: '' }); return } catch {}
    }
    session.error = error.message
    session.ready = false
  }
}

export function formatDate(value) {
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(value))
}
