import assert from 'node:assert/strict'
import { session, refreshSession } from './src/api.js'
import { library, loadLibrary, searchDocuments, rememberReading } from './src/library.js'
const values = new Map()
globalThis.sessionStorage = { get length() { return values.size }, key: index => [...values.keys()][index], getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) }
const { saveDraft, readDraft } = await import('./src/drafts.js')
let requests = 0, deliver
const documents = [{ id: 1, title: 'Sécurité du poste', category: 'Informatique', summary: 'Protéger les accès.' }]
globalThis.fetch = () => { requests++; return new Promise(resolve => { deliver = () => resolve({ ok: true, json: async () => documents }) }) }
session.user = { id: 1, admin: true }
const first = loadLibrary(), concurrent = loadLibrary()
assert.equal(requests, 1)
deliver(); await Promise.all([first, concurrent])
await loadLibrary(); assert.equal(requests, 1)
assert.equal(searchDocuments(library.documents, 'SECURITE acces').length, 1)
assert.equal(searchDocuments(library.documents, 'sécurité impossible').length, 0)
rememberReading(documents[0], 'section-1', 50)
assert.equal(library.recent.length, 1)
const form = { title: 'Brouillon', category: 'Aide', summary: 'Résumé', blocks: [{ type: 'section', title: 'Repère', text: 'Contenu', itemsText: '' }] }
assert.equal(saveDraft('new', form, null), true)
assert.equal(readDraft('new').form.title, 'Brouillon')
const pending = loadLibrary(true)
session.user = null
assert.equal(library.documents.length, 0)
assert.equal(library.recent.length, 0)
assert.equal(values.size, 0)
session.user = { id: 2, admin: true }
deliver(); await pending
assert.equal(library.documents.length, 0, 'A previous account response must not populate a new session')
assert.equal(readDraft('new'), null)
values.set('atlas-draft:2:new', JSON.stringify({ at: Date.now() - 3 * 60 * 60 * 1000, form, version: null }))
assert.equal(readDraft('new'), null)
values.set('atlas-draft:2:new', '{invalid-json')
assert.equal(readDraft('new'), null)
assert.equal(saveDraft('new', form, null), true)
session.user = { id: 2, admin: false }
assert.equal(values.size, 0)
assert.equal(saveDraft('new', form, null), false)
const replies = []
globalThis.fetch = () => new Promise(resolve => replies.push(data => resolve({ ok: true, json: async () => data })))
const previousSession = refreshSession(), latestSession = refreshSession()
replies[1]({ user: null, csrfToken: 'latest', loginToken: 'latest' }); await latestSession
replies[0]({ user: { id: 1, admin: true }, csrfToken: 'outdated', loginToken: 'outdated' }); await previousSession
assert.equal(session.user, null)
assert.equal(session.csrfToken, 'latest')
console.log('Shared requests, accent search, session isolation, draft recovery and expiry passed.')
