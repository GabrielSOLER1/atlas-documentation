import { reactive } from 'vue'

export const session = reactive({ user: null, loginToken: '', csrfToken: '', ready: false, error: '', flash: '' })

export async function api(path, options = {}) {
  const identity = session.user?.id
  const token = session.csrfToken
  const headers = { ...options.headers }
  if (options.body && !(options.body instanceof URLSearchParams)) headers['Content-Type'] = 'application/json'
  if (options.method && options.method !== 'GET') headers['X-CSRF-Token'] = session.csrfToken
  const controller = new AbortController()
  const abort = () => controller.abort()
  options.signal?.addEventListener('abort', abort, { once: true })
  if (options.signal?.aborted) abort()
  const timeout = setTimeout(abort, 15000)
  try {
    const response = await fetch(`/api${path}`, { ...options, headers, credentials: 'same-origin', signal: controller.signal })
    let data
    try { data = await response.json() }
    catch (error) {
      if (controller.signal.aborted) throw error
      throw new Error('Le serveur a renvoyé une réponse inattendue.')
    }
    if (!response.ok) {
      if (response.status === 401 && session.user?.id === identity && session.csrfToken === token) session.user = null
      const error = new Error(data.error || 'Cette action a échoué.')
      error.status = response.status
      throw error
    }
    return data
  } catch (failure) {
    if (controller.signal.aborted) throw new Error('La réponse prend trop de temps. Réessayez dans un instant.')
    if (failure instanceof TypeError) throw new Error('Le portail est injoignable. Vérifiez votre connexion puis réessayez.')
    throw failure
  } finally {
    clearTimeout(timeout)
    options.signal?.removeEventListener('abort', abort)
  }
}

let sessionRequest = 0
export async function refreshSession() {
  const revision = ++sessionRequest
  try {
    const data = await api('/session')
    if (revision === sessionRequest) Object.assign(session, data, { ready: true, error: '' })
  } catch (error) {
    if (revision !== sessionRequest) return
    if (error.status === 401) {
      try {
        const data = await api('/session')
        if (revision === sessionRequest) Object.assign(session, data, { ready: true, error: '' })
        return
      } catch {}
    }
    if (revision === sessionRequest) { session.error = error.message; session.ready = false }
  }
}

const dateFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' })
export function formatDate(value) { return dateFormatter.format(new Date(value)) }
