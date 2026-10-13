<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, useRoute, useRouter } from 'vue-router'
import Icon from '../components/Icon.vue'
import DocumentBody from '../components/DocumentBody.vue'
import { api, session } from '../api'
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
const form = ref({ title: '', category: '', summary: '', blocks: [] })
const palette = [{ type: 'section', label: 'Étape', icon: 'file' }, { type: 'text', label: 'Paragraphe', icon: 'book' }, { type: 'note', label: 'À retenir', icon: 'info' }, { type: 'checklist', label: 'Checklist', icon: 'check' }, { type: 'code', label: 'Commande', icon: 'code' }]
const publishBlocks = computed(() => form.value.blocks.map(({ type, title, text, itemsText }) => type === 'checklist' ? { type, title, items: (itemsText || '').split('\n').map(item => item.trim()).filter(Boolean) } : { type, title, text }))
const characterCount = computed(() => Array.from(publishBlocks.value.map(block => (block.title ? `## ${block.title.trim()}\n` : '') + (block.type === 'checklist' ? block.items.join('\n') : block.text || '')).join('\n\n')).length)

function add(type) {
  if (form.value.blocks.length >= 30) return
  form.value.blocks.push({ type, title: '', text: '', itemsText: '' })
  dirty.value = true
}
function move(index, offset) {
  const target = index + offset
  if (target < 0 || target >= form.value.blocks.length) return
  const item = form.value.blocks.splice(index, 1)[0]
  form.value.blocks.splice(target, 0, item)
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
  dirty.value = true
}
onMounted(async () => {
  if (!session.user?.admin) { error.value = 'Accès réservé aux administrateurs.'; loading.value = false; return }
  try {
    if (editing.value) {
      const doc = await api(`/documents/${route.params.id}`)
      const blocks = doc.blocks || parseSections(doc.content).map(section => ({ type: section.title ? 'section' : 'text', title: section.title, text: section.paragraphs.join('\n\n') }))
      form.value = { title: doc.title, category: doc.category, summary: doc.summary, blocks: blocks.map(block => ({ ...block, itemsText: block.items?.join('\n') || '' })) }
    } else add('section')
    dirty.value = false
  } catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
onBeforeRouteLeave(() => !dirty.value || saved.value || window.confirm('Quitter sans enregistrer cette documentation ?'))
async function publish() {
  preview.value = false
  await nextTick()
  if (!editorForm.value.reportValidity()) return
  if (characterCount.value > 10000) { error.value = 'Votre guide dépasse 10 000 caractères. Raccourcissez son contenu avant de publier.'; return }
  busy.value = true
  error.value = ''
  try {
    const data = await api(editing.value ? `/documents/${route.params.id}` : '/documents', { method: editing.value ? 'PUT' : 'POST', body: JSON.stringify({ title: form.value.title, category: form.value.category, summary: form.value.summary, blocks: publishBlocks.value }) })
    saved.value = true
    session.flash = editing.value ? 'Votre documentation a été mise à jour.' : 'Votre documentation a été publiée.'
    await router.push(`/procedures/${editing.value ? route.params.id : data.id}`)
  } catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
</script>
<template><section class="editor-page page-enter"><RouterLink to="/" class="back-link"><Icon name="back" :size="16" />Retour à la bibliothèque</RouterLink><div class="page-heading"><div><p class="eyebrow">ATELIER DOCUMENTAIRE</p><h1>{{ editing ? 'Faire évoluer le guide' : 'Un savoir à partager' }}<span class="title-dot">.</span></h1><p class="lead">Assemblez des blocs simples. Prévisualisez. Publiez.</p></div><div class="editor-toggle"><button :class="{ selected: !preview }" @click="preview = false"><Icon name="edit" :size="15" />Édition</button><button :class="{ selected: preview }" @click="preview = true"><Icon name="eye" :size="15" />Aperçu</button></div></div><p v-if="error" class="notice error" role="alert">{{ error }}</p><p v-if="loading" role="status" class="loading-state">Ouverture de l’éditeur…</p><form ref="editorForm" v-else-if="session.user?.admin" @submit.prevent="publish" @input="dirty = true"><div v-show="!preview"><div class="panel editor-metadata"><div class="edit-grid"><div><label for="doc-title">Titre de la documentation</label><input id="doc-title" v-model="form.title" required maxlength="150"></div><div><label for="doc-category">Catégorie</label><input id="doc-category" v-model="form.category" required maxlength="60"></div></div><label for="doc-summary">Résumé</label><textarea id="doc-summary" v-model="form.summary" required maxlength="300" rows="2"></textarea></div><div class="editor-palette"><span>Ajouter un bloc</span><button v-for="item in palette" :key="item.type" type="button" class="button subtle compact" :disabled="form.blocks.length >= 30" @click="add(item.type)"><Icon :name="item.icon" :size="15" />{{ item.label }}</button></div><div class="editor-blocks"><section v-for="(block, index) in form.blocks" :key="index" class="panel editor-block" @dragover.prevent @drop.prevent="drop(index)"><div class="editor-block-heading"><span class="drag-handle" draggable="true" aria-label="Déplacer le bloc par glisser-déposer" @dragstart="startDrag($event, index)" @dragend="dragged = null"><Icon name="grip" :size="16" /></span><span class="block-label">{{ String(index + 1).padStart(2, '0') }} · {{ palette.find(item => item.type === block.type)?.label }}</span><div class="block-actions"><button type="button" class="icon-button" :disabled="index === 0" :aria-label="`Monter le bloc ${index + 1}`" @click="move(index, -1)"><Icon name="up" :size="16" /></button><button type="button" class="icon-button" :disabled="index === form.blocks.length - 1" :aria-label="`Descendre le bloc ${index + 1}`" @click="move(index, 1)"><Icon name="down" :size="16" /></button><button type="button" class="icon-button" :aria-label="`Supprimer le bloc ${index + 1}`" @click="form.blocks.splice(index, 1); dirty = true"><Icon name="trash" :size="16" /></button></div></div><label :for="`block-title-${index}`">{{ block.type === 'section' ? 'Titre de l’étape' : 'Titre du bloc (facultatif)' }}</label><input :id="`block-title-${index}`" v-model="block.title" :required="block.type === 'section'" maxlength="150"><label :for="`block-text-${index}`">{{ block.type === 'checklist' ? 'Éléments à vérifier — un par ligne' : block.type === 'code' ? 'Commande ou extrait de code' : 'Contenu' }}</label><textarea v-if="block.type === 'checklist'" :id="`block-text-${index}`" v-model="block.itemsText" rows="4" required maxlength="10000"></textarea><textarea v-else :id="`block-text-${index}`" v-model="block.text" :class="{ 'content-editor': block.type === 'code' }" rows="4" required maxlength="5000"></textarea></section><div v-if="form.blocks.length === 0" class="panel empty-state"><h2>Votre page attend son premier bloc.</h2><p>Ajoutez une étape, une note ou une checklist.</p></div></div></div><div v-if="preview" class="editor-preview"><header class="document-cover"><span class="badge">{{ form.category || 'Catégorie' }}</span><h1>{{ form.title || 'Le titre de votre documentation' }}</h1><p class="lead">{{ form.summary || 'Le résumé apparaîtra ici.' }}</p></header><article class="panel reading-paper"><DocumentBody :blocks="publishBlocks" /></article><p class="field-hint">Cet aperçu n’est pas encore publié.</p></div><div class="editor-bottom"><span class="muted">{{ form.blocks.length }} / 30 blocs · {{ characterCount }} / 10 000 caractères</span><button class="button primary" :disabled="busy || !form.blocks.length" @click="preview = false"><Icon name="check" :size="16" />{{ busy ? 'Publication…' : editing ? 'Enregistrer le guide' : 'Publier la documentation' }}</button></div></form></section></template>
