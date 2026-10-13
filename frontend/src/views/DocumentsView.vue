<script setup>
import { computed, onMounted, ref } from 'vue'
import Icon from '../components/Icon.vue'
import { useRoute } from 'vue-router'
import { api, formatDate, session } from '../api'

const route = useRoute()
const category = computed(() => route.query.categorie || '')
const documents = ref([])
const query = ref('')
const error = ref('')
const loading = ref(true)
const filtered = computed(() => {
  const search = query.value.trim().toLocaleLowerCase('fr')
  return documents.value.filter(document => (!category.value || document.category === category.value) && `${document.title} ${document.summary} ${document.category}`.toLocaleLowerCase('fr').includes(search))
})
onMounted(async () => {
  try { documents.value = await api('/documents') }
  catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
</script>

<template>
  <section class="page-enter">
    <div class="library-hero"><div class="hero-copy"><p class="eyebrow">LE SAVOIR DE VOTRE ÉQUIPE</p><h1>Vos outils.<br>Les bonnes réponses<span class="title-dot">.</span></h1><p class="lead">Des procédures claires pour avancer sereinement.<br>Retrouvez ce dont vous avez besoin, au même endroit.</p><div class="hero-meta"><span><Icon name="file" :size="15" />{{ loading ? '…' : documents.length }} guides disponibles</span><span class="hero-meta-divider"></span><span><Icon name="shield" :size="15" />Documentation interne</span></div></div><div class="hero-art" aria-hidden="true"><div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div><div class="art-paper paper-back"></div><div class="art-paper paper-front"><span class="art-book"><Icon name="book" :size="28" /></span><div class="art-line long"></div><div class="art-line"></div><div class="art-line short"></div><div class="art-check"><Icon name="check" :size="17" /></div></div></div></div>
    <div class="library-actions"><RouterLink v-if="session.user?.admin" to="/procedures/nouveau" class="button primary"><Icon name="plus" :size="16" />Créer une documentation</RouterLink><RouterLink v-if="category" to="/" class="button subtle">{{ category }} · Effacer le filtre</RouterLink></div><div class="list-toolbar"><div><p class="eyebrow">À CONSULTER</p><h2>La bibliothèque Atlas <span class="count-badge">{{ documents.length }}</span></h2></div><div class="search"><label class="sr-only" for="search">Rechercher une procédure</label><Icon name="search" :size="18" /><input id="search" v-model="query" type="search" placeholder="Rechercher une procédure…"></div></div>
    <p v-if="error" class="notice error" role="alert">{{ error }}</p><p v-else-if="loading" class="loading-state" role="status">Chargement de la bibliothèque…</p>
    <div v-else class="document-grid"><RouterLink v-for="(document, index) in filtered" :key="document.id" :to="`/procedures/${document.id}`" class="panel document-card"><div class="card-top"><span class="document-icon"><Icon :name="document.category === 'Assistance' ? 'shield' : 'book'" :size="24" /></span><span class="badge">{{ document.category }}</span></div><span class="card-type">GUIDE PRATIQUE</span><h2>{{ document.title }}</h2><p>{{ document.summary }}</p><div class="card-bottom"><span><Icon name="clock" :size="13" /><small>{{ formatDate(document.updated_at) }}</small></span><span class="card-arrow"><Icon name="arrow" :size="18" /></span></div></RouterLink><div v-if="filtered.length === 0" class="panel empty-state"><span class="empty-icon"><Icon name="search" :size="28" /></span><h2>Aucune procédure trouvée</h2><p>Essayez un autre mot-clé pour retrouver votre guide.</p><button v-if="query" class="button subtle" @click="query = ''">Effacer la recherche</button></div></div>
    <div class="library-note"><Icon name="info" :size="18" /><p>{{ session.user?.admin ? 'Une procédure à actualiser ? Ouvrez le guide pour le modifier.' : 'Une information manque ? Votre équipe informatique est là pour vous aider.' }}</p></div>
  </section>
</template>
