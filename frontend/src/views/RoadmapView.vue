<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'
import { api } from '../api'
import Icon from '../components/Icon.vue'
const data = ref(null)
const error = ref('')
const notice = ref('')
const selected = ref('besoin')
const busy = ref(false)
const detail = computed(() => data.value?.nodes.find(node => node.id === selected.value))
const nodes = computed(() => [...(data.value?.nodes || [])].sort((a, b) => a.y - b.y || Number(b.main) - Number(a.main)))
const mainNodes = computed(() => nodes.value.filter(node => node.main))
const percent = computed(() => data.value ? Math.round(data.value.completed.length / data.value.nodes.length * 100) : 0)
onMounted(async () => { try { data.value = await api('/roadmap') } catch (failure) { error.value = failure.message } })
async function choose(id) {
  selected.value = id; notice.value = ''; await nextTick()
  const panel = window.document.getElementById('roadmap-detail')
  panel?.focus({ preventScroll: true })
  if (window.matchMedia('(max-width: 1200px)').matches) panel?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' })
}
async function toggle() {
  busy.value = true; error.value = ''; notice.value = ''
  const current = data.value.completed
  const completed = current.includes(selected.value) ? current.filter(id => id !== selected.value) : [...current, selected.value]
  try { const result = await api('/roadmap', { method: 'PATCH', body: JSON.stringify({ completed }) }); data.value.completed = result.completed; notice.value = 'Votre progression a été enregistrée.' }
  catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
</script>
<template><section class="page-enter roadmap-page"><div class="roadmap-heading"><div><p class="eyebrow">LE PARCOURS ADMINISTRATEUR</p><h1>De l’idée au guide.<br>Du guide au suivi<span class="title-dot">.</span></h1><p class="lead">Une seule roadmap pour publier une documentation utile et administrer Atlas avec méthode.</p></div><div v-if="data" class="panel roadmap-progress"><span>Votre parcours</span><strong>{{ data.completed.length }}<small> / {{ data.nodes.length }}</small></strong><progress :value="percent" max="100" aria-label="Progression personnelle">{{ percent }} %</progress><p>{{ percent }} % des étapes cochées</p></div></div>
  <p v-if="error" role="alert" class="notice error">{{ error }}</p><p v-if="notice" role="status" class="notice success">{{ notice }}</p><p v-if="!data && !error" role="status" class="loading-state">Ouverture de votre parcours…</p>
  <div v-if="data" class="roadmap-layout"><div class="panel roadmap-map"><div class="roadmap-legend"><span><i class="legend-main"></i>Chemin principal</span><span><i class="legend-side"></i>Points de contrôle</span><span><i class="legend-done"></i>Étape cochée</span></div><div class="roadmap-canvas" role="group" aria-label="Étapes du parcours administrateur"><svg class="roadmap-lines" viewBox="0 0 900 900" preserveAspectRatio="none" aria-hidden="true"><path d="M450 70V820" class="roadmap-trunk"/><path v-for="node in nodes.filter(n => !n.main)" :key="node.id" :d="`M450 ${node.y}H${node.x}`" class="roadmap-branch"/><circle v-for="node in mainNodes" :key="node.id" cx="450" :cy="node.y" r="5"/></svg>
    <button v-for="node in nodes" :key="node.id" class="roadmap-node" :class="{ main: node.main, selected: selected === node.id, done: data.completed.includes(node.id) }" :style="{ '--node-x': `${node.x / 9}%`, '--node-y': `${node.y / 9}%` }" :aria-pressed="selected === node.id" :aria-controls="'roadmap-detail'" @click="choose(node.id)"><span class="node-phase">{{ node.main ? `ÉTAPE ${node.phase}` : 'POINT DE CONTRÔLE' }}<Icon v-if="data.completed.includes(node.id)" name="check" :size="14" /></span><strong>{{ node.title }}</strong><small>{{ node.subtitle }}</small></button>
  </div><div class="roadmap-finish"><Icon name="shield" :size="20" /><p>Une documentation claire.<br><strong>Un portail entretenu.</strong></p></div></div>
  <aside id="roadmap-detail" tabindex="-1" class="panel roadmap-detail" aria-label="Détail de l’étape sélectionnée" aria-live="polite"><template v-if="detail"><div class="roadmap-detail-top"><span class="badge">Étape {{ detail.phase }} · {{ detail.main ? 'Chemin principal' : 'Point de contrôle' }}</span><Icon :name="data.completed.includes(detail.id) ? 'check' : 'route'" :size="22" /></div><h2>{{ detail.title }}</h2><p class="lead">{{ detail.subtitle }}</p><ol class="roadmap-instructions"><li v-for="(step, index) in detail.steps" :key="index"><span>{{ index + 1 }}</span><p>{{ step }}</p></li></ol><pre v-if="detail.code" class="reader-code"><code>{{ detail.code }}</code></pre><RouterLink :to="detail.link" class="button primary full-width">{{ detail.actionLabel }}<Icon name="arrow" :size="16" /></RouterLink><button class="button subtle full-width roadmap-complete" :aria-pressed="data.completed.includes(detail.id)" :disabled="busy" @click="toggle"><Icon name="check" :size="16" />{{ data.completed.includes(detail.id) ? 'Décocher cette étape' : 'Marquer cette étape comme faite' }}</button><p class="field-hint">Vos coches constituent un repère personnel et restent associées à votre compte.</p></template></aside></div>
</section></template>
