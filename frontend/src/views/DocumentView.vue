<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Icon from '../components/Icon.vue'
import DocumentBody from '../components/DocumentBody.vue'
import { api, formatDate, session } from '../api'
import { parseSections, readingMinutes } from '../document'
const route = useRoute()
const procedure = ref(null)
const error = ref('')
const sections = computed(() => procedure.value?.blocks ? procedure.value.blocks.map((block, index) => ({ ...block, id: `section-${index + 1}` })) : parseSections(procedure.value?.content || ''))
const minutes = computed(() => readingMinutes(procedure.value?.content || ''))
const titledSections = computed(() => sections.value.filter(section => section.title))
const activeSection = ref('')
onMounted(async () => { try { procedure.value = await api(`/documents/${route.params.id}`) } catch (failure) { error.value = failure.message } })
function goToSection(id) {
  activeSection.value = id
  const element = window.document.getElementById(id)
  element?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' })
  element?.focus({ preventScroll: true })
}
</script>
<template><section class="document-page page-enter"><RouterLink to="/" class="back-link"><Icon name="back" :size="16" />Retour à la bibliothèque</RouterLink><p v-if="error" class="notice error" role="alert">{{ error }}</p><template v-if="procedure">
<header class="document-cover"><div class="detail-heading"><div class="document-label"><span class="badge">{{ procedure.category }}</span><span>GUIDE PRATIQUE</span></div><RouterLink v-if="session.user?.admin" :to="`/procedures/${procedure.id}/modifier`" class="button subtle"><Icon name="edit" :size="15" />Modifier</RouterLink></div><h1>{{ procedure.title }}</h1><p class="lead">{{ procedure.summary }}</p><div class="document-meta"><span><Icon name="clock" :size="15" />{{ minutes }} min de lecture estimée</span><span class="meta-dot"></span><span>Mis à jour le {{ formatDate(procedure.updated_at) }}</span></div></header>
        <div class="reading-layout"><article class="panel reading-paper" aria-label="Contenu de la procédure"><div class="article-intro"><span class="article-kicker">LE GUIDE, ÉTAPE PAR ÉTAPE</span><span v-if="titledSections.length" class="muted">{{ titledSections.length }} sections</span></div><DocumentBody :content="procedure.content" :blocks="procedure.blocks" :active-section="activeSection" /><div class="article-end"><span class="end-mark"><Icon name="check" :size="17" /></span><p>Vous êtes arrivé au bout du guide.<br><strong>Gardez-le à portée de main si besoin.</strong></p><RouterLink to="/" class="article-return" aria-label="Retour à la bibliothèque"><Icon name="arrow" :size="18" /></RouterLink></div></article>
          <aside class="reading-aside"><nav v-if="titledSections.length" class="panel toc" aria-label="Sommaire de la procédure"><p class="eyebrow">DANS CE GUIDE</p><ol><li v-for="(section, index) in titledSections" :key="section.id"><button :class="{ 'toc-active': activeSection === section.id }" @click="goToSection(section.id)"><span>{{ section.number || String(index + 1).padStart(2, '0') }}</span>{{ section.title }}</button></li></ol></nav><div class="reader-help"><span class="help-icon"><Icon name="info" :size="20" /></span><h3>Un point à éclaircir ?</h3><p>Votre équipe informatique peut vous accompagner dans cette procédure.</p></div></aside></div></template><div v-else-if="!error" class="loading-state" role="status">Ouverture du guide…</div></section></template>
