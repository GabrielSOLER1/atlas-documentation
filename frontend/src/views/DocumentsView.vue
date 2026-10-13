<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Icon from '../components/Icon.vue'
import { api, formatDate, session } from '../api'
import { library, loadLibrary, notify, searchDocuments, searchShortcut, setFavorite, ui } from '../library'
const route = useRoute()
const router = useRouter()
const favorites = computed(() => route.path === '/favoris')
const query = ref(typeof route.query.q === 'string' ? route.query.q.slice(0, 160) : '')
const category = ref(typeof route.query.categorie === 'string' ? route.query.categorie : '')
const sort = ref(route.query.tri === 'titre' ? 'title' : 'recent')
const layout = ref('grid')
const pending = ref(new Set())
const error = ref('')
let queryTimer
const base = computed(() => library.documents.filter(doc => !favorites.value || doc.favorite))
const matches = computed(() => searchDocuments(base.value, query.value))
const facets = computed(() => {
  const map = new Map()
  for (const doc of matches.value) map.set(doc.category, (map.get(doc.category) || 0) + 1)
  return [...map].sort(([a], [b]) => a.localeCompare(b, 'fr'))
})
const filtered = computed(() => matches.value.filter(doc => !category.value || doc.category === category.value)
  .sort((a, b) => sort.value === 'title' ? a.title.localeCompare(b.title, 'fr') : new Date(b.updated_at) - new Date(a.updated_at) || a.id - b.id))
const recent = computed(() => library.recent.filter(item => library.documents.some(doc => doc.id === item.id)).slice(0, 3))
const totalFavorites = computed(() => library.documents.filter(doc => doc.favorite).length)
const featured = computed(() => library.documents.find(doc => doc.category === 'Prise en main') || library.documents[0])
watch([query, category, sort], () => {
  clearTimeout(queryTimer)
  queryTimer = setTimeout(() => {
    const params = { ...route.query }
    for (const key of ['q', 'categorie', 'tri']) delete params[key]
    if (query.value.trim()) params.q = query.value.trim()
    if (category.value) params.categorie = category.value
    if (sort.value === 'title') params.tri = 'titre'
    router.replace({ path: route.path, query: params })
  }, 180)
})
watch(() => route.query, params => {
  if ((params.q || '') !== query.value.trim()) query.value = typeof params.q === 'string' ? params.q.slice(0, 160) : ''
  category.value = typeof params.categorie === 'string' ? params.categorie : ''
  sort.value = params.tri === 'titre' ? 'title' : 'recent'
})
onMounted(() => { loadLibrary().catch(() => {}); try { layout.value = localStorage.getItem('atlas-layout') === 'list' ? 'list' : 'grid' } catch {} })
onBeforeUnmount(() => clearTimeout(queryTimer))
function setLayout(value) { layout.value = value; try { localStorage.setItem('atlas-layout', value) } catch {} }
function resetFilters() { query.value = ''; category.value = ''; sort.value = 'recent' }
async function favorite(doc) {
  pending.value.add(doc.id); error.value = ''
  try { const result = await api(`/documents/${doc.id}/favorite`, { method: doc.favorite ? 'DELETE' : 'PUT' }); setFavorite(doc.id, result.favorite); notify(result.favorite ? 'Guide ajouté à vos favoris' : 'Guide retiré de vos favoris') }
  catch (failure) { error.value = failure.message }
  finally { pending.value.delete(doc.id) }
}
</script>
<template><section class="library-page">
  <div class="library-introduction"><div class="library-welcome"><span class="workspace-tag"><span class="status-dot"></span>VOTRE BASE DE CONNAISSANCES</span><h1>{{ favorites ? 'Vos guides essentiels' : 'Le bon guide.' }}<br v-if="!favorites"><span v-if="!favorites" class="heading-soft">Au bon moment</span><span class="title-dot">.</span></h1><p>{{ favorites ? 'Une sélection personnelle, toujours à portée de main.' : 'Moins de temps à chercher. Plus de repères pour avancer.' }}</p><div class="welcome-actions"><span><Icon name="book" :size="16" /><strong>{{ library.ready ? library.documents.length : '—' }}</strong> guides</span><span><Icon name="bookmark" :size="16" /><strong>{{ totalFavorites }}</strong> favoris</span></div></div><RouterLink v-if="featured && !favorites" :to="`/procedures/${featured.id}`" class="discovery-card"><div class="discovery-top"><span class="eyebrow">UN BON POINT DE DÉPART</span><span class="discovery-symbol"><Icon name="spark" :size="25" /></span></div><span class="badge">{{ featured.category }}</span><h2>{{ featured.title }}</h2><p>{{ featured.summary }}</p><span class="discovery-bottom">Ouvrir le guide <span><Icon name="arrow" :size="18" /></span></span></RouterLink><div v-else-if="favorites" class="favorites-art" aria-hidden="true"><Icon name="bookmark" :size="80" /><span>Votre sélection.<br>Votre rythme.</span></div></div>
  <div v-if="recent.length && !favorites" class="recent-strip"><span class="recent-heading"><Icon name="clock" :size="16" />Reprendre</span><RouterLink v-for="item in recent" :key="item.id" :to="`/procedures/${item.id}`"><span>{{ item.title }}</span><small v-if="item.percent">{{ item.percent }} %</small><Icon name="arrow" :size="14" /></RouterLink></div>
  <div class="library-search-zone"><div class="library-search"><Icon name="search" :size="22" /><label for="library-query" class="sr-only">Rechercher dans la bibliothèque</label><input id="library-query" v-model="query" maxlength="160" type="search" autocomplete="off" placeholder="Que souhaitez-vous faire aujourd’hui ?"><button v-if="query" class="icon-button" aria-label="Effacer la recherche" @click="query = ''"><Icon name="close" :size="17" /></button><button v-else class="search-shortcut" aria-label="Ouvrir la recherche globale" @click="ui.searchOpen = true"><kbd>{{ searchShortcut }}</kbd></button></div><div class="category-chips" role="group" aria-label="Filtrer par catégorie"><button :aria-pressed="!category" :class="{ selected: !category }" @click="category = ''">Tous les thèmes <span>{{ matches.length }}</span></button><button v-for="[name, count] in facets" :key="name" :aria-pressed="category === name" :class="{ selected: category === name }" @click="category = category === name ? '' : name">{{ name }} <span>{{ count }}</span></button><button v-if="category && !facets.some(([name]) => name === category)" class="selected" @click="category = ''">{{ category }}<Icon name="close" :size="13" /></button></div></div>
  <div class="library-toolbar"><div><h2>{{ favorites ? 'Mes favoris' : 'Explorer les guides' }} <span>{{ filtered.length }}</span></h2><p v-if="query" class="result-context" role="status">{{ filtered.length }} résultat{{ filtered.length > 1 ? 's' : '' }} pour « {{ query }} »</p><p v-else class="result-context">Des réponses pratiques, organisées par sujet.</p></div><div class="collection-controls"><label class="sr-only" for="library-sort">Trier les guides</label><select id="library-sort" v-model="sort"><option value="recent">Les plus récents</option><option value="title">De A à Z</option></select><div class="view-switch" role="group" aria-label="Présentation des guides"><button aria-label="Afficher en cartes" :aria-pressed="layout === 'grid'" :class="{ selected: layout === 'grid' }" @click="setLayout('grid')"><Icon name="grid" :size="17" /></button><button aria-label="Afficher en liste" :aria-pressed="layout === 'list'" :class="{ selected: layout === 'list' }" @click="setLayout('list')"><Icon name="list" :size="19" /></button></div><button class="icon-button collection-refresh" aria-label="Actualiser la bibliothèque" :disabled="library.loading" @click="loadLibrary(true).catch(() => {})"><Icon name="refresh" :size="17" /></button></div></div>
  <p v-if="error || library.error" role="alert" class="notice error">{{ error || library.error }}<button class="button subtle compact" @click="loadLibrary(true).catch(() => {})">Réessayer</button></p>
  <div v-if="library.loading && !library.ready" class="document-grid library-skeleton" aria-label="Chargement des guides" aria-busy="true"><div v-for="index in 6" :key="index" class="panel skeleton-card"><div></div><span></span><span></span><span></span></div><span role="status" class="sr-only">Chargement de la bibliothèque</span></div>
  <div v-else-if="filtered.length" class="document-grid atlas-collection" :class="{ 'collection-list': layout === 'list' }"><article v-for="doc in filtered" :key="doc.id" class="panel document-card"><div class="card-top"><span class="document-icon"><Icon :name="doc.category === 'Sécurité' ? 'shield' : doc.category === 'Assistance' ? 'help' : doc.category === 'Vie d’équipe' ? 'users' : 'book'" :size="22" /></span><span class="badge">{{ doc.category }}</span><button class="icon-button favorite-button" :class="{ saved: doc.favorite }" :aria-label="`${doc.favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'} : ${doc.title}`" :aria-pressed="doc.favorite" :disabled="pending.has(doc.id)" @click="favorite(doc)"><Icon name="bookmark" :size="18" /></button></div><RouterLink :to="`/procedures/${doc.id}`" class="card-open"><h3>{{ doc.title }}</h3><p>{{ doc.summary }}</p></RouterLink><div class="card-bottom"><span>Mis à jour le {{ formatDate(doc.updated_at) }}</span><span class="card-arrow"><Icon name="arrow" :size="17" /></span></div></article></div>
  <div v-else-if="library.ready" class="panel collection-empty"><span><Icon :name="favorites && !query && !category ? 'bookmark' : 'search'" :size="31" /></span><h2>{{ favorites && !query && !category ? 'Gardez l’essentiel, ici.' : 'Changeons de piste.' }}</h2><p>{{ favorites && !query && !category ? 'Ajoutez un guide avec le signet. Votre sélection vous suivra sur les postes internes.' : 'Aucun guide ne correspond à ces filtres. Essayez un sujet plus large ou retirez une catégorie.' }}</p><button v-if="query || category" class="button primary" @click="resetFilters">Réinitialiser les filtres</button><RouterLink v-else to="/" class="button primary">Explorer la bibliothèque <Icon name="arrow" :size="16" /></RouterLink></div>
  <div v-if="session.user?.admin" class="library-contribute"><span class="contribute-icon"><Icon name="edit" :size="22" /></span><div><h3>Le prochain guide utile vient peut-être de vous.</h3><p>Une question récurrente ? Transformez-la en procédure claire.</p></div><RouterLink to="/procedures/nouveau" class="button subtle">Créer un guide<Icon name="plus" :size="16" /></RouterLink></div>
</section></template>
