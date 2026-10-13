<script setup>
import { computed, onMounted, ref } from 'vue'
import { api } from '../api'
import Icon from '../components/Icon.vue'
const documents = ref([])
const error = ref('')
const categories = computed(() => {
  const groups = new Map()
  for (const doc of documents.value) groups.set(doc.category, (groups.get(doc.category) || 0) + 1)
  return [...groups].sort(([a], [b]) => a.localeCompare(b, 'fr'))
})
onMounted(async () => { try { documents.value = await api('/documents') } catch (failure) { error.value = failure.message } })
</script>
<template><section class="page-enter"><p class="eyebrow">PARCOURIR PAR THÈME</p><h1>Chaque sujet, son guide<span class="title-dot">.</span></h1><p class="lead">Explorez les catégories de votre bibliothèque interne.</p><p v-if="error" role="alert" class="notice error">{{ error }}</p><div class="category-grid"><RouterLink v-for="[name, count] in categories" :key="name" :to="{ path: '/', query: { categorie: name } }" class="panel category-card"><span class="document-icon"><Icon name="layers" :size="24" /></span><h2>{{ name }}</h2><p>{{ count }} {{ count > 1 ? 'documentations' : 'documentation' }}</p><span class="text-link">Explorer <Icon name="arrow" :size="16" /></span></RouterLink></div></section></template>
