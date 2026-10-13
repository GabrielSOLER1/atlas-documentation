<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import Icon from '../components/Icon.vue'
import DocumentBody from '../components/DocumentBody.vue'
import { api, session } from '../api'
import { invalidateLibrary, notify } from '../library'
import { readDraft, saveDraft, removeDraft } from '../drafts'
import { parseSections } from '../document'
const route = useRoute()
const router = useRouter()
const editing = computed(() => !!route.params.id)
const error = ref('')
const busy = ref(false)
const loading = ref(true)
const preview = ref(false)
const dirty = ref(false)
const saved = ref(false)
const dragged = ref(null)
const editorForm = ref(null)
const draft = ref(null)
const draftStatus = ref('')
const version = ref(null)
const draftId = route.params.id || 'new'
let draftTimer
let serial = 0
function blockKey() { return `block-${++serial}` }
const form = ref({ title: '', category: '', summary: '', blocks: [] })
const palette = [{ type: 'section', label: 'Étape', icon: 'file' }, { type: 'text', label: 'Paragraphe', icon: 'book' }, { type: 'note', label: 'À retenir', icon: 'info' }, { type: 'checklist', label: 'Checklist', icon: 'check' }, { type: 'code', label: 'Commande', icon: 'code' }]
const publishBlocks = computed(() => form.value.blocks.map(({ type, title, text, itemsText }) => type === 'checklist' ? { type, title, items: (itemsText || '').split('\n').map(item => item.trim()).filter(Boolean) } : { type, title, text }))
const characterCount = computed(() => Array.from(publishBlocks.value.map(block => (block.title ? `## ${block.title.trim()}\n` : '') + (block.type === 'checklist' ? block.items.join('\n') : block.text || '')).join('\n\n')).length)

function add(type) {
  if (form.value.blocks.length >= 30) return
  form.value.blocks.push({ key: blockKey(), type, title: '', text: '', itemsText: '' })
  focusBlock(form.value.blocks.at(-1).key)
  dirty.value = true
}
function focusBlock(key) { nextTick(() => document.getElementById(`block-title-${key}`)?.focus()) }
function duplicate(index) {
  if (form.value.blocks.length >= 30) return
  const block = { ...form.value.blocks[index], key: blockKey() }
  form.value.blocks.splice(index + 1, 0, block); dirty.value = true; focusBlock(block.key)
}
function remove(index) {
  form.value.blocks.splice(index, 1); dirty.value = true
  const block = form.value.blocks[Math.min(index, form.value.blocks.length - 1)]
  if (block) focusBlock(block.key)
  else nextTick(() => document.getElementById('doc-title')?.focus())
}
function move(index, offset) {
  const target = index + offset
  if (target < 0 || target >= form.value.blocks.length) return
  const item = form.value.blocks.splice(index, 1)[0]
  form.value.blocks.splice(target, 0, item)
  focusBlock(item.key)
  dirty.value = true
}
function startDrag(event, index) {
  dragged.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/plain', String(index))
}
function drop(index) {
  if (dragged.value === null || dragged.value === index) return
  const item = form.value.blocks.splice(dragged.value, 1)[0]
  form.value.blocks.splice(index, 0, item)
  dragged.value = null
  focusBlock(item.key)
  dirty.value = true
}
onMounted(async () => {
  if (!session.user?.admin) { error.value = 'Accès réservé aux administrateurs.'; loading.value = false; return }
  try {
    if (editing.value) {
      const doc = await api(`/documents/${route.params.id}`)
      version.value = doc.updated_at
      const blocks = doc.blocks || parseSections(doc.content).map(section => ({ type: section.title ? 'section' : 'text', title: section.title, text: section.paragraphs.join('\n\n') }))
      form.value = { title: doc.title, category: doc.category, summary: doc.summary, blocks: blocks.map(block => ({ ...block, key: blockKey(), text: block.text || '', title: block.title || '', itemsText: block.items?.join('\n') || '' })) }
    } else { add('section'); nextTick(() => document.getElementById('doc-title')?.focus()) }
    dirty.value = false
    draft.value = readDraft(draftId)
  } catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
function keepDraft() {
  if (!dirty.value || saved.value || loading.value || draft.value) return true
  const preserved = saveDraft(draftId, form.value, version.value)
  draftStatus.value = preserved ? 'Brouillon conservé dans cet onglet' : 'Brouillon non sauvegardé : gardez cet onglet ouvert'
  return preserved
}
watch(form, () => { if (!loading.value) dirty.value = true; clearTimeout(draftTimer); draftTimer = setTimeout(keepDraft, 500) }, { deep: true, flush: 'sync' })
function restoreDraft() {
  form.value = { ...draft.value.form, blocks: draft.value.form.blocks.map(block => ({ ...block, key: blockKey() })) }
  version.value = draft.value.version; draft.value = null; dirty.value = true; keepDraft()
  nextTick(() => document.getElementById('doc-title')?.focus())
}
function discardDraft() { removeDraft(draftId); draft.value = null; draftStatus.value = '' }
function beforeUnload(event) { if (dirty.value && !saved.value && !keepDraft()) { event.preventDefault(); event.returnValue = '' } }
function shortcut(event) { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') { event.preventDefault(); if (!loading.value) publish() } }
onMounted(() => { window.addEventListener('beforeunload', beforeUnload); window.addEventListener('keydown', shortcut) })
onBeforeUnmount(() => { clearTimeout(draftTimer); keepDraft(); window.removeEventListener('beforeunload', beforeUnload); window.removeEventListener('keydown', shortcut) })
onBeforeRouteLeave(() => {
  if (!dirty.value || saved.value || !session.user?.admin) return true
  if (keepDraft()) { notify('Brouillon conservé dans cet onglet. Vous pourrez reprendre la rédaction.'); return true }
  return window.confirm('Le brouillon n’a pas pu être conservé. Quitter sans enregistrer ?')
})
async function publish() {
  if (busy.value || loading.value || draft.value) return
  busy.value = true
  preview.value = false
  await nextTick()
  if (!editorForm.value?.reportValidity()) { busy.value = false; return }
  if (characterCount.value > 10000) { error.value = 'Votre guide dépasse 10 000 caractères. Raccourcissez son contenu avant de publier.'; busy.value = false; return }
  error.value = ''
  try {
    const data = await api(editing.value ? `/documents/${route.params.id}` : '/documents', { method: editing.value ? 'PUT' : 'POST', body: JSON.stringify({ title: form.value.title, category: form.value.category, summary: form.value.summary, blocks: publishBlocks.value, ...(editing.value ? { expectedUpdatedAt: version.value } : {}) }) })
    saved.value = true
    clearTimeout(draftTimer); removeDraft(draftId); invalidateLibrary()
    session.flash = editing.value ? 'Votre documentation a été mise à jour.' : 'Votre documentation a été publiée.'
    await router.push(`/procedures/${editing.value ? route.params.id : data.id}`)
  } catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
</script>
<template><section class="editor-page page-enter"><RouterLink to="/" class="back-link"><Icon name="back" :size="16" />Retour à la bibliothèque</RouterLink><div class="page-heading"><div><p class="eyebrow">ATELIER DOCUMENTAIRE</p><h1>{{ editing ? 'Faire évoluer le guide' : 'Un savoir à partager' }}<span class="title-dot">.</span></h1><p class="lead">Assemblez des blocs simples. Prévisualisez. Publiez.</p></div><div class="editor-toggle"><button :class="{ selected: !preview }" @click="preview = false"><Icon name="edit" :size="15" />Édition</button><button :class="{ selected: preview }" @click="preview = true"><Icon name="eye" :size="15" />Aperçu</button></div></div><p v-if="error" class="notice error" role="alert">{{ error }}</p><div v-if="draft" class="draft-recovery" role="status"><Icon name="clock" :size="22" /><div><strong>Votre brouillon vous attend</strong><p>Récupérez la version conservée dans cet onglet, avant de reprendre la rédaction.</p></div><button class="button subtle compact" @click="discardDraft">Ignorer</button><button class="button primary compact" @click="restoreDraft">Reprendre</button></div><p v-if="loading" role="status" class="loading-state">Ouverture de l’éditeur…</p><form ref="editorForm" v-else-if="session.user?.admin" :inert="!!draft" @submit.prevent="publish" @input="dirty = true"><div v-show="!preview"><div class="panel editor-metadata"><div class="edit-grid"><div><label for="doc-title">Titre de la documentation</label><input id="doc-title" v-model="form.title" required maxlength="150"></div><div><label for="doc-category">Catégorie</label><input id="doc-category" v-model="form.category" required maxlength="60"></div></div><label for="doc-summary">Résumé</label><textarea id="doc-summary" v-model="form.summary" required maxlength="300" rows="2"></textarea></div><div class="editor-palette"><span>Ajouter un bloc</span><button v-for="item in palette" :key="item.type" type="button" class="button subtle compact" :disabled="form.blocks.length >= 30" @click="add(item.type)"><Icon :name="item.icon" :size="15" />{{ item.label }}</button></div><div class="editor-blocks"><section v-for="(block, index) in form.blocks" :key="block.key" class="panel editor-block" @dragover.prevent @drop.prevent="drop(index)"><div class="editor-block-heading"><span class="drag-handle" draggable="true" aria-label="Déplacer le bloc par glisser-déposer" @dragstart="startDrag($event, index)" @dragend="dragged = null"><Icon name="grip" :size="16" /></span><span class="block-label">{{ String(index + 1).padStart(2, '0') }} · {{ palette.find(item => item.type === block.type)?.label }}</span><div class="block-actions"><button type="button" class="icon-button" :disabled="index === 0" :aria-label="`Monter le bloc ${index + 1}`" @click="move(index, -1)"><Icon name="up" :size="16" /></button><button type="button" class="icon-button" :disabled="index === form.blocks.length - 1" :aria-label="`Descendre le bloc ${index + 1}`" @click="move(index, 1)"><Icon name="down" :size="16" /></button><button type="button" class="icon-button" :disabled="form.blocks.length >= 30" :aria-label="`Dupliquer le bloc ${index + 1}`" @click="duplicate(index)"><Icon name="layers" :size="16" /></button><button type="button" class="icon-button" :aria-label="`Supprimer le bloc ${index + 1}`" @click="remove(index)"><Icon name="trash" :size="16" /></button></div></div><label :for="`block-title-${block.key}`">{{ block.type === 'section' ? 'Titre de l’étape' : 'Titre du bloc (facultatif)' }}</label><input :id="`block-title-${block.key}`" v-model="block.title" :required="block.type === 'section'" maxlength="150"><label :for="`block-text-${block.key}`">{{ block.type === 'checklist' ? 'Éléments à vérifier — un par ligne' : block.type === 'code' ? 'Commande ou extrait de code' : 'Contenu' }}</label><textarea v-if="block.type === 'checklist'" :id="`block-text-${block.key}`" v-model="block.itemsText" rows="4" required maxlength="10000"></textarea><textarea v-else :id="`block-text-${block.key}`" v-model="block.text" :class="{ 'content-editor': block.type === 'code' }" rows="4" required maxlength="5000"></textarea></section><div v-if="form.blocks.length === 0" class="panel empty-state"><h2>Votre page attend son premier bloc.</h2><p>Ajoutez une étape, une note ou une checklist.</p></div></div></div><div v-if="preview" class="editor-preview"><header class="document-cover"><span class="badge">{{ form.category || 'Catégorie' }}</span><h1>{{ form.title || 'Le titre de votre documentation' }}</h1><p class="lead">{{ form.summary || 'Le résumé apparaîtra ici.' }}</p></header><article class="panel reading-paper"><DocumentBody :blocks="publishBlocks" /></article><p class="field-hint">Cet aperçu n’est pas encore publié.</p></div><div class="editor-bottom"><div class="editor-save-status"><strong>{{ draftStatus || (dirty ? 'Modifications non publiées' : 'Prêt à rédiger') }}</strong><span class="muted">{{ form.blocks.length }} / 30 blocs · {{ characterCount }} / 10 000 caractères</span></div><button class="button primary" :disabled="busy || !form.blocks.length" @click="preview = false"><Icon name="check" :size="16" />{{ busy ? 'Publication…' : editing ? 'Enregistrer le guide' : 'Publier la documentation' }}</button></div></form></section></template>
