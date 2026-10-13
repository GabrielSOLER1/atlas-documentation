<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave, onBeforeRouteUpdate, useRoute } from 'vue-router'
import Icon from '../components/Icon.vue'
import DocumentBody from '../components/DocumentBody.vue'
import { api, formatDate, session } from '../api'
import { documentSections, readingMinutes } from '../document'
import { library, loadLibrary, notify, rememberReading, setFavorite, ui } from '../library'
const route = useRoute()
const procedure = ref(null)
const error = ref('')
const saving = ref(false)
const article = ref(null)
const progress = ref(0)
const activeSection = ref('')
const previousReading = ref(null)
const tone = ref('paper')
const fontSize = computed({ get: () => ui.readerSize ?? (session.user?.readingSize === 'large' ? 18 : 16), set: value => { ui.readerSize = value } })
const tocOpen = ref(false)
const sections = computed(() => documentSections(procedure.value?.content || '', procedure.value?.blocks || null))
const minutes = computed(() => readingMinutes(procedure.value?.content || ''))
const titledSections = computed(() => sections.value.filter(section => section.title))
const related = computed(() => library.documents.filter(doc => doc.id !== procedure.value?.id && doc.category === procedure.value?.category).slice(0, 3))
let observer
let frame = 0
let tracking = true
function calculateProgress() {
  frame = 0
  if (!tracking || !article.value || !procedure.value) return
  const rect = article.value.getBoundingClientRect()
  const distance = Math.max(1, rect.height - window.innerHeight + 130)
  progress.value = Math.min(100, Math.max(0, Math.round((130 - rect.top) / distance * 100)))
  rememberReading(procedure.value, activeSection.value, progress.value)
}
function onScroll() { if (!frame) frame = requestAnimationFrame(calculateProgress) }
async function open() {
  error.value = ''
  try {
    const guide = await api(`/documents/${route.params.id}`)
    if (!tracking) return
    procedure.value = guide
    window.document.title = `${procedure.value.title} · Atlas Documentation`
    previousReading.value = library.recent.find(doc => doc.id === procedure.value.id) || null
    rememberReading(procedure.value, previousReading.value?.section || '', previousReading.value?.percent || 0)
    loadLibrary().catch(() => {})
    await nextTick()
    if (!tracking) return
    observer?.disconnect()
    observer = new IntersectionObserver(entries => {
      const visible = entries.filter(entry => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top)
      if (tracking && visible.length) activeSection.value = visible[0].target.id
    }, { rootMargin: '-120px 0px -55% 0px', threshold: 0 })
    article.value?.querySelectorAll('.reading-section').forEach(section => observer.observe(section))
    calculateProgress()
  } catch (failure) { error.value = failure.message }
}
onMounted(() => {
  try { const saved = localStorage.getItem('atlas-reading-tone'); if (['paper', 'soft', 'night'].includes(saved)) tone.value = saved } catch {}
  open(); window.addEventListener('scroll', onScroll, { passive: true }); window.addEventListener('resize', onScroll)
})
function stopTracking() { tracking = false; observer?.disconnect(); cancelAnimationFrame(frame); window.removeEventListener('scroll', onScroll); window.removeEventListener('resize', onScroll) }
onBeforeRouteLeave(stopTracking)
onBeforeRouteUpdate((to, from) => { if (to.path !== from.path) stopTracking() })
onBeforeUnmount(() => { stopTracking(); ui.focusReading = false })
function goToSection(id) {
  tocOpen.value = false; activeSection.value = id
  const element = window.document.getElementById(id)
  element?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' })
  element?.focus({ preventScroll: true })
}
function changeTone(value) { tone.value = value; try { localStorage.setItem('atlas-reading-tone', value) } catch {} }
async function favorite() {
  saving.value = true; error.value = ''
  try { const result = await api(`/documents/${procedure.value.id}/favorite`, { method: procedure.value.favorite ? 'DELETE' : 'PUT' }); procedure.value.favorite = result.favorite; setFavorite(procedure.value.id, result.favorite); notify(result.favorite ? 'Guide ajouté à vos favoris' : 'Guide retiré de vos favoris') }
  catch (failure) { error.value = failure.message }
  finally { saving.value = false }
}
function top() { window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }) }
function printGuide() { window.print() }
</script>
<template><section class="document-page reader-page" :data-tone="tone" :style="{ '--reading-font': `${fontSize}px` }"><div class="reader-breadcrumb"><RouterLink to="/" class="back-link"><Icon name="back" :size="15" />Bibliothèque</RouterLink><template v-if="procedure"><Icon name="chevron" :size="12" /><RouterLink :to="{ path: '/', query: { categorie: procedure.category } }">{{ procedure.category }}</RouterLink></template></div>
<p v-if="error" class="notice error" role="alert">{{ error }}<button v-if="!procedure" class="button subtle compact" @click="open">Réessayer</button></p>
<template v-if="procedure"><header class="document-cover reader-cover"><div class="reader-cover-top"><span class="badge">{{ procedure.category }}</span><div class="document-actions"><button class="icon-button" :aria-label="procedure.favorite ? 'Retirer ce guide des favoris' : 'Garder ce guide en favori'" :aria-pressed="procedure.favorite" :disabled="saving" :class="{ saved: procedure.favorite }" @click="favorite"><Icon name="bookmark" :size="20" /></button><button class="icon-button print-guide" aria-label="Imprimer le guide" @click="printGuide"><Icon name="print" :size="19" /></button><RouterLink v-if="session.user?.admin" :to="`/procedures/${procedure.id}/modifier`" class="button subtle compact"><Icon name="edit" :size="15" />Modifier</RouterLink></div></div><span class="reader-eyebrow">LE GUIDE PRATIQUE</span><h1>{{ procedure.title }}</h1><p class="lead">{{ procedure.summary }}</p><div class="document-meta"><span><Icon name="clock" :size="15" />{{ minutes }} min de lecture</span><span class="meta-dot"></span><span>{{ titledSections.length }} repères</span><span class="meta-dot"></span><span>Actualisé le {{ formatDate(procedure.updated_at) }}</span></div><button v-if="previousReading?.section && previousReading.percent > 5 && previousReading.percent < 95" class="resume-reading" @click="goToSection(previousReading.section)"><Icon name="clock" :size="15" />Reprendre à votre dernier repère · {{ previousReading.percent }} %<Icon name="arrow" :size="15" /></button></header>
<div class="reader-toolbar"><div class="reader-progress-meta"><span class="reader-progress-ring">{{ progress }}<small>%</small></span><span>Votre lecture</span></div><div class="reader-controls"><div class="reading-size-control"><button aria-label="Réduire la taille du texte" :disabled="fontSize <= 16" @click="fontSize -= 2">A<span>−</span></button><span class="sr-only" role="status">Taille du texte : {{ fontSize }} pixels</span><button aria-label="Agrandir le texte" :disabled="fontSize >= 22" @click="fontSize += 2">A<span>+</span></button></div><div class="reading-tone-control" role="group" aria-label="Ambiance de lecture"><button v-for="[value, label, icon] in [['paper', 'Papier', 'sun'], ['soft', 'Doux', 'book'], ['night', 'Nuit', 'moon']]" :key="value" :title="label" :aria-label="`Ambiance ${label}`" :aria-pressed="tone === value" :class="{ selected: tone === value }" @click="changeTone(value)"><Icon :name="icon" :size="16" /></button></div><button class="button subtle compact reader-focus" :aria-label="ui.focusReading ? 'Quitter la lecture sans distraction' : 'Lire sans distraction'" :aria-pressed="ui.focusReading" @click="ui.focusReading = !ui.focusReading"><Icon name="focus" :size="16" /><span>{{ ui.focusReading ? 'Quitter la lecture' : 'Se concentrer' }}</span></button></div><div class="reader-progress-line" :style="{ width: `${progress}%` }"></div></div>
<div class="reading-layout"><article ref="article" class="panel reading-paper reader-surface" aria-label="Contenu de la procédure"><div class="article-intro"><span class="article-kicker">VOTRE PARCOURS, PAS À PAS</span><span>{{ minutes }} min pour l’essentiel</span></div><DocumentBody :content="procedure.content" :blocks="procedure.blocks" :active-section="activeSection" /><div class="article-end"><span class="end-mark"><Icon name="check" :size="20" /></span><div><h3>Vous avez tous les repères.</h3><p>Gardez ce guide en favori pour votre prochaine visite.</p></div><button class="icon-button" :aria-label="procedure.favorite ? 'Retirer ce guide des favoris' : 'Ajouter ce guide aux favoris'" :aria-pressed="procedure.favorite" :disabled="saving" @click="favorite"><Icon name="bookmark" :size="20" /></button></div></article>
<aside class="reading-aside"><nav v-if="titledSections.length" class="panel toc" aria-label="Sommaire de la procédure"><button class="mobile-toc-heading" :aria-expanded="tocOpen" @click="tocOpen = !tocOpen">Dans ce guide<Icon :name="tocOpen ? 'up' : 'down'" :size="16" /></button><p class="eyebrow desktop-toc-heading">DANS CE GUIDE</p><ol :class="{ 'toc-expanded': tocOpen }"><li v-for="section in titledSections" :key="section.id"><button :class="{ 'toc-active': activeSection === section.id }" :aria-current="activeSection === section.id ? 'step' : undefined" @click="goToSection(section.id)"><span>{{ section.number || '•' }}</span>{{ section.title }}</button></li></ol></nav><div class="reader-help"><span class="help-icon"><Icon name="help" :size="21" /></span><h3>Un point à éclaircir ?</h3><p>Retrouvez les repères pour contacter votre équipe informatique.</p><RouterLink to="/aide" class="text-link">Consulter l’aide<Icon name="arrow" :size="14" /></RouterLink></div></aside></div>
<div v-if="related.length" class="related-guides"><div><span class="eyebrow">POUR CONTINUER</span><h2>Dans le même thème</h2></div><div><RouterLink v-for="doc in related" :key="doc.id" :to="`/procedures/${doc.id}`"><Icon name="file" :size="19" /><span>{{ doc.title }}</span><Icon name="arrow" :size="16" /></RouterLink></div></div><Transition name="toast"><button v-if="progress > 15" class="reader-top" aria-label="Revenir au début du guide" @click="top"><Icon name="up" :size="19" /></button></Transition>
</template><div v-else-if="!error" class="reader-loading" role="status"><div class="skeleton-line"></div><div class="skeleton-line"></div><p>Votre guide arrive…</p></div></section></template>
