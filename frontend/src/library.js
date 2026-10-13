import { computed, reactive, watch } from 'vue'
import { api, session } from './api.js'

export const library = reactive({ documents: [], loading: false, ready: false, error: '', recent: [], updated: 0 })
let flight = null
let epoch = 0
export function resetLibrary() {
  epoch += 1; flight = null
  Object.assign(library, { documents: [], loading: false, ready: false, error: '', recent: [], updated: 0 })
}
watch(() => `${session.user?.id || ''}:${session.user?.admin || false}`, resetLibrary, { flush: 'sync' })
export async function loadLibrary(force = false) {
  if (!session.user) return []
  if (!force && library.ready && Date.now() - library.updated < 30000) return library.documents
  if (flight) return flight
  const version = epoch
  library.loading = true; library.error = ''
  const request = (async () => {
    try {
      const documents = await api('/documents')
      if (version === epoch && session.user) Object.assign(library, { documents, ready: true, updated: Date.now() })
      return version === epoch ? documents : []
    } catch (error) {
      if (version === epoch) library.error = error.message
      throw error
    } finally { if (version === epoch) { library.loading = false; flight = null } }
  })()
  flight = request
  return request
}
export function invalidateLibrary() { library.updated = 0 }
export function setFavorite(id, favorite) {
  const doc = library.documents.find(doc => doc.id === id)
  if (doc) doc.favorite = favorite
}
export function rememberReading(doc, section = '', percent = 0) {
  const current = library.recent[0]
  if (current?.id === doc.id && current.section === section && current.percent === percent && current.title === doc.title) return
  const entry = { id: doc.id, title: doc.title, category: doc.category, section, percent }
  library.recent = [entry, ...library.recent.filter(item => item.id !== doc.id)].slice(0, 5)
}
export function normalize(text) { return String(text).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('fr').trim() }
const searchIndex = new WeakMap()
export function searchDocuments(documents, query) {
  const tokens = normalize(query).split(/\s+/).filter(Boolean)
  return documents.filter(doc => {
    let indexed = searchIndex.get(doc)
    const source = `${doc.title} ${doc.summary} ${doc.category}`
    if (!indexed || indexed.source !== source) { indexed = { source, text: normalize(source) }; searchIndex.set(doc, indexed) }
    return tokens.every(token => indexed.text.includes(token))
  })
}
export const categories = computed(() => {
  const groups = new Map()
  for (const doc of library.documents) groups.set(doc.category, (groups.get(doc.category) || 0) + 1)
  return [...groups].sort(([a], [b]) => a.localeCompare(b, 'fr'))
})
export const ui = reactive({ searchOpen: false, focusReading: false, navigationBusy: false, toast: null })
let toastTimer
export function notify(message) { clearTimeout(toastTimer); ui.toast = message; toastTimer = setTimeout(() => { ui.toast = null }, 4500) }
watch(() => session.user?.id, () => { ui.searchOpen = false; ui.focusReading = false; ui.toast = null; clearTimeout(toastTimer) })

export const searchShortcut = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K'
