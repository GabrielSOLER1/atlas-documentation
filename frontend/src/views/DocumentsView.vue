<script setup>
import { computed, onMounted, ref } from 'vue'
import Icon from '../components/Icon.vue'
import { useRoute } from 'vue-router'
import { api, formatDate, session } from '../api'
const route = useRoute()
const category = computed(() => route.query.categorie || '')
const favorites = computed(() => route.path === '/favoris')
const documents = ref([])
const query = ref('')
const sort = ref('recent')
const error = ref('')
const loading = ref(true)
const pending = ref(new Set())
const filtered = computed(() => {
  const search = query.value.trim().toLocaleLowerCase('fr')
  return documents.value.filter(doc => (!favorites.value || doc.favorite) && (!category.value || doc.category === category.value) && `${doc.title} ${doc.summary} ${doc.category}`.toLocaleLowerCase('fr').includes(search))
    .sort((a, b) => sort.value === 'title' ? a.title.localeCompare(b.title, 'fr') : new Date(b.updated_at) - new Date(a.updated_at) || a.id - b.id)
})
onMounted(async () => {
  try { documents.value = await api('/documents') }
  catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
async function toggleFavorite(doc) {
  pending.value.add(doc.id); error.value = ''
  try { const result = await api(`/documents/${doc.id}/favorite`, { method: doc.favorite ? 'DELETE' : 'PUT' }); doc.favorite = result.favorite }
  catch (failure) { error.value = failure.message }
  finally { pending.value.delete(doc.id) }
}
</script>
<template><section class="page-enter">
  <div class="library-hero"><div class="hero-copy"><p class="eyebrow">{{ favorites ? 'VOTRE SÉLECTION PERSONNELLE' : 'LE SAVOIR DE VOTRE ÉQUIPE' }}</p><h1>{{ favorites ? 'L’essentiel, à portée de main' : 'Vos outils. Les bonnes réponses' }}<span class="title-dot">.</span></h1><p class="lead">{{ favorites ? 'Les guides que vous avez gardés pour les retrouver rapidement.' : 'Des procédures claires pour avancer sereinement. Retrouvez ce dont vous avez besoin, au même endroit.' }}</p><div class="hero-meta"><span><Icon :name="favorites ? 'bookmark' : 'file'" :size="15" />{{ loading ? '…' : favorites ? documents.filter(doc => doc.favorite).length : documents.length }} {{ favorites ? 'guides favoris' : 'guides disponibles' }}</span><span class="hero-meta-divider"></span><span><Icon name="shield" :size="15" />Documentation interne</span></div></div><div class="hero-art" aria-hidden="true"><div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div><div class="art-paper paper-back"></div><div class="art-paper paper-front"><span class="art-book"><Icon :name="favorites ? 'bookmark' : 'book'" :size="28" /></span><div class="art-line long"></div><div class="art-line"></div><div class="art-line short"></div><div class="art-check"><Icon name="check" :size="17" /></div></div></div></div>
  <div class="library-actions"><RouterLink v-if="session.user?.admin" to="/procedures/nouveau" class="button primary"><Icon name="plus" :size="16" />Créer une documentation</RouterLink><RouterLink v-if="category" to="/" class="button subtle">{{ category }} · Effacer le filtre</RouterLink></div>
  <div class="list-toolbar"><div><p class="eyebrow">À CONSULTER</p><h2>{{ favorites ? 'Mes favoris' : category || 'La bibliothèque Atlas' }} <span class="count-badge">{{ filtered.length }}</span></h2></div><div class="library-filters"><div class="search"><label class="sr-only" for="search">Rechercher une procédure</label><Icon name="search" :size="18" /><input id="search" v-model="query" type="search" placeholder="Rechercher une procédure…"></div><label class="sr-only" for="sort">Trier les guides</label><select id="sort" v-model="sort"><option value="recent">Mis à jour récemment</option><option value="title">Ordre alphabétique</option></select></div></div>
  <p v-if="error" class="notice error" role="alert">{{ error }}</p><p v-if="loading" class="loading-state" role="status">Chargement de la bibliothèque…</p>
  <div v-else class="document-grid"><article v-for="doc in filtered" :key="doc.id" class="panel document-card"><div class="card-top"><span class="document-icon"><Icon :name="doc.category === 'Assistance' ? 'shield' : 'book'" :size="24" /></span><span class="badge">{{ doc.category }}</span><button class="icon-button favorite-button" :class="{ saved: doc.favorite }" :aria-label="`${doc.favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'} : ${doc.title}`" :aria-pressed="doc.favorite" :disabled="pending.has(doc.id)" @click="toggleFavorite(doc)"><Icon name="bookmark" :size="18" /></button></div><span class="card-type">GUIDE PRATIQUE</span><RouterLink :to="`/procedures/${doc.id}`" class="card-open"><h2>{{ doc.title }}</h2><p>{{ doc.summary }}</p></RouterLink><div class="card-bottom"><span><Icon name="clock" :size="13" /><small>{{ formatDate(doc.updated_at) }}</small></span><span class="card-arrow"><Icon name="arrow" :size="18" /></span></div></article><div v-if="filtered.length === 0" class="panel empty-state"><span class="empty-icon"><Icon :name="favorites ? 'bookmark' : 'search'" :size="28" /></span><h2>{{ favorites && !query ? 'Gardez vos guides essentiels ici' : 'Aucune procédure trouvée' }}</h2><p>{{ favorites && !query ? 'Cliquez sur le signet d’un guide pour l’ajouter à votre sélection.' : 'Essayez un autre mot-clé pour retrouver votre guide.' }}</p><RouterLink v-if="favorites" to="/" class="button subtle">Explorer la bibliothèque</RouterLink><button v-if="query" class="button subtle" @click="query = ''">Effacer la recherche</button></div></div>
  <div class="library-note"><Icon name="info" :size="18" /><p>{{ session.user?.admin ? 'Une procédure à actualiser ? Ouvrez le guide pour le modifier.' : 'Une information manque ? Votre équipe informatique est là pour vous aider.' }}</p></div>
</section></template>
