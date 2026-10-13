<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { session } from '../api'
import { library, loadLibrary, normalize, searchDocuments, ui } from '../library'
import Icon from './Icon.vue'
const router = useRouter()
const dialog = ref(null)
const input = ref(null)
const query = ref('')
const selected = ref(0)
const actions = computed(() => [
  { title: 'Bibliothèque', to: '/', icon: 'book' }, { title: 'Mes favoris', to: '/favoris', icon: 'bookmark' },
  { title: 'Catégories', to: '/categories', icon: 'layers' }, { title: 'Mon profil', to: '/profil', icon: 'users' },
  { title: 'Aide & prise en main', to: '/aide', icon: 'help' },
  ...(session.user?.admin ? [{ title: 'Créer une documentation', to: '/procedures/nouveau', icon: 'plus' }, { title: 'Roadmap administrateur', to: '/roadmap', icon: 'route' }, { title: 'Comptes & accès', to: '/comptes', icon: 'users' }, { title: 'Journal d’activité', to: '/activite', icon: 'activity' }] : []),
])
const results = computed(() => {
  const docs = searchDocuments(library.documents, query.value).slice(0, query.value ? 7 : 4).map(doc => ({ title: doc.title, subtitle: doc.category, icon: 'file', to: `/procedures/${doc.id}`, kind: 'Guide' }))
  const pages = actions.value.filter(item => normalize(item.title).includes(normalize(query.value))).slice(0, query.value ? 4 : 5).map(item => ({ ...item, subtitle: 'Aller à cette page', kind: 'Page' }))
  return query.value ? [...docs, ...pages] : [...pages, ...docs]
})
function close() { ui.searchOpen = false }
async function choose(index) { const item = results.value[index]; if (!item) return; close(); await router.push(item.to) }
function move(offset) {
  if (!results.value.length) return
  selected.value = (selected.value + offset + results.value.length) % results.value.length
  nextTick(() => window.document.getElementById(`command-${selected.value}`)?.scrollIntoView({ block: 'nearest' }))
}
watch(query, () => { selected.value = 0 })
watch(() => ui.searchOpen, async open => {
  if (open) {
    query.value = ''; selected.value = 0; await nextTick()
    dialog.value?.showModal(); input.value?.focus()
    loadLibrary().catch(() => {})
  } else dialog.value?.close()
}, { immediate: true })
function shortcut(event) {
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); ui.searchOpen = !ui.searchOpen }
  else if (event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey && !event.target?.closest?.('input,textarea,select,[contenteditable="true"]')) { event.preventDefault(); ui.searchOpen = true }
}
onMounted(() => window.addEventListener('keydown', shortcut))
onBeforeUnmount(() => { window.removeEventListener('keydown', shortcut); dialog.value?.close() })
</script>
<template><Teleport to="body"><dialog ref="dialog" class="command-dialog" aria-labelledby="command-title" @cancel.prevent="close" @click="event => { if (event.target === dialog) close() }"><div class="command-panel">
  <h2 id="command-title" class="sr-only">Rechercher dans Atlas</h2><div class="command-input"><Icon name="search" :size="22" /><input ref="input" v-model="query" maxlength="160" role="combobox" aria-label="Rechercher un guide ou une page" aria-autocomplete="list" aria-expanded="true" aria-controls="command-results" :aria-activedescendant="results.length ? `command-${selected}` : undefined" placeholder="Un guide, un sujet, une page…" autocomplete="off" @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter.prevent="choose(selected)"><button class="command-escape" aria-label="Fermer la recherche" @click="close">esc</button></div>
  <div class="command-results" id="command-results" role="listbox" aria-label="Résultats de recherche"><p class="command-label">{{ query ? 'RÉSULTATS' : 'ALLER À L’ESSENTIEL' }}</p><p v-if="library.loading && !library.ready" class="muted command-feedback" role="status">Recherche dans la bibliothèque…</p><p v-if="library.error" class="command-feedback muted" role="alert">{{ library.error }}</p><button v-for="(item, index) in results" :id="`command-${index}`" :key="item.to" type="button" role="option" :aria-selected="selected === index" :tabindex="-1" class="command-result" :class="{ selected: selected === index }" @mousemove="selected = index" @click="choose(index)"><span class="command-icon"><Icon :name="item.icon" :size="19" /></span><span><strong>{{ item.title }}</strong><small>{{ item.subtitle }}</small></span><span class="command-kind">{{ item.kind }}</span><Icon name="arrow" :size="16" /></button><div v-if="!results.length && library.ready" class="command-empty"><Icon name="search" :size="28" /><strong>Aucun résultat pour « {{ query }} »</strong><span>Essayez un autre mot ou le nom d’une catégorie.</span></div></div>
  <div class="command-footer"><span><kbd>↑</kbd><kbd>↓</kbd> parcourir <kbd>↵</kbd> ouvrir</span><span>Recherche interne</span></div>
</div></dialog></Teleport></template>
