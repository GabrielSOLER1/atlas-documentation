import { watch } from 'vue'
import { session } from './api.js'
const prefix = 'atlas-draft:'
const lifespan = 2 * 60 * 60 * 1000
const types = new Set(['section', 'text', 'note', 'checklist', 'code'])
function key(id) { return `${prefix}${session.user?.id}:${id}` }
export function cleanDrafts(owner = null) {
  try {
    for (let index = sessionStorage.length - 1; index >= 0; index--) {
      const name = sessionStorage.key(index)
      if (name.startsWith(prefix) && (owner === null || !name.startsWith(`${prefix}${owner}:`))) sessionStorage.removeItem(name)
    }
  } catch {}
}
watch(() => `${session.user?.id || ''}:${session.user?.admin || false}`, () => cleanDrafts(session.user?.admin ? session.user.id : null), { flush: 'sync' })
export function validDraft(draft) {
  const form = draft?.form
  return !!form && Number.isFinite(draft.at) && draft.at <= Date.now() && Date.now() - draft.at < lifespan &&
    typeof form.title === 'string' && form.title.length <= 150 && typeof form.category === 'string' && form.category.length <= 60 &&
    typeof form.summary === 'string' && form.summary.length <= 300 && Array.isArray(form.blocks) && form.blocks.length <= 30 &&
    form.blocks.every(block => types.has(block.type) && ['title', 'text', 'itemsText'].every(field => typeof block[field] === 'string' && block[field].length <= 10000)) &&
    (draft.version === null || typeof draft.version === 'string' && draft.version.length <= 64)
}
export function readDraft(id) {
  if (!session.user?.admin) return null
  try {
    const draft = JSON.parse(sessionStorage.getItem(key(id)))
    if (validDraft(draft)) return draft
    sessionStorage.removeItem(key(id))
  } catch { removeDraft(id) }
  return null
}
export function saveDraft(id, form, version) {
  if (!session.user?.admin) return false
  try {
    const draft = JSON.stringify({ at: Date.now(), version, form })
    if (draft.length > 100000) return false
    sessionStorage.setItem(key(id), draft)
    return true
  } catch { return false }
}
export function removeDraft(id) { try { sessionStorage.removeItem(key(id)) } catch {} }
