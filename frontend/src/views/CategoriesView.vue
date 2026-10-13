<script setup>
import { onMounted } from 'vue'
import { library, loadLibrary, categories } from '../library'
import Icon from '../components/Icon.vue'
onMounted(() => loadLibrary().catch(() => {}))
const icons = { 'Sécurité': 'shield', 'Assistance': 'help', 'Prise en main': 'spark', 'Bureautique': 'file', 'Vie d’équipe': 'users' }
</script>
<template><section class="categories-page"><span class="workspace-tag">EXPLORER PAR SUJET</span><h1>Chaque sujet,<br><span class="heading-soft">son point de départ.</span></h1><p class="lead">Les mêmes guides, organisés pour trouver plus vite votre réponse.</p><p v-if="library.error" role="alert" class="notice error">{{ library.error }}</p><div class="category-grid"><RouterLink v-for="[name, count] in categories" :key="name" :to="{ path: '/', query: { categorie: name } }" class="panel category-card"><div class="category-card-top"><span class="document-icon"><Icon :name="icons[name] || 'layers'" :size="25" /></span><span>{{ String(count).padStart(2, '0') }}</span></div><h2>{{ name }}</h2><p>{{ count }} guide{{ count > 1 ? 's' : '' }} pour vous accompagner.</p><span class="text-link">Explorer ce thème <Icon name="arrow" :size="16" /></span></RouterLink></div><p v-if="library.loading && !library.ready" role="status" class="loading-state">Chargement des catégories…</p></section></template>
