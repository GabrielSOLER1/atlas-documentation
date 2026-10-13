<script setup>
import { computed, onMounted, ref } from 'vue'
import Icon from '../components/Icon.vue'
import { api } from '../api'
const data = ref(null)
const days = ref('7')
const type = ref('all')
const busy = ref(false)
const error = ref('')
const types = { login_success: 'Connexion réussie', login_failure: 'Connexion refusée', logout: 'Déconnexion', user_created: 'Compte créé', user_updated: 'Compte mis à jour', document_created: 'Documentation créée', document_updated: 'Documentation modifiée', profile_updated: 'Profil personnalisé' }
const series = computed(() => {
  const byDate = Object.fromEntries((data.value?.series || []).map(point => [point.date, point]))
  const values = []
  for (let offset = Number(days.value) - 1; offset >= 0; offset--) {
    const date = new Date(); date.setDate(date.getDate() - offset)
    const key = new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Paris', year: 'numeric', month: '2-digit', day: '2-digit' }).format(date)
    values.push(byDate[key] || { date: key, success: 0, failure: 0 })
  }
  return values
})
const max = computed(() => Math.max(1, ...series.value.map(point => Number(point.success) + Number(point.failure))))
function eventTime(value) { return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) }
async function load() {
  busy.value = true; error.value = ''
  try { data.value = await api(`/activity?days=${days.value}&type=${type.value}`) }
  catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
onMounted(load)
</script>
<template><section class="page-enter"><div class="page-heading"><div><p class="eyebrow">ADMINISTRATION · JOURNAL LOCAL</p><h1>L’activité, en clair<span class="title-dot">.</span></h1><p class="lead">Les connexions et les actions qui font vivre le portail.</p></div><button class="button subtle" :disabled="busy" @click="load"><Icon name="activity" :size="16" />Actualiser</button></div><p v-if="error" role="alert" class="notice error">{{ error }}</p><div class="activity-filters"><label>Période<select v-model="days" @change="load"><option value="7">7 derniers jours</option><option value="30">30 derniers jours</option></select></label><label>Événements<select v-model="type" @change="load"><option value="all">Tous les événements</option><option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option></select></label></div><p v-if="busy" role="status" class="loading-state">Lecture du journal…</p><template v-if="data"><div class="account-stats"><div><span class="stat-icon"><Icon name="check" /></span><div><strong>{{ data.summary.connections }}</strong><span>Connexions réussies</span></div></div><div><span class="stat-icon warning-icon"><Icon name="shield" /></span><div><strong>{{ data.summary.failures }}</strong><span>Tentatives refusées</span></div></div><div><span class="stat-icon"><Icon name="users" /></span><div><strong>{{ data.summary.members }}</strong><span>Membres connectés</span></div></div></div><div class="panel activity-chart"><div class="panel-heading"><h2>Connexions par jour</h2><span class="muted">{{ days }} jours</span></div><svg viewBox="0 0 700 130" role="img" aria-label="Histogramme des connexions et des tentatives refusées"><g v-for="(point, index) in series" :key="point.date"><title>{{ point.date }} : {{ point.success }} connexions, {{ point.failure }} échecs</title><rect :x="15 + index * (670 / series.length)" :y="110 - Number(point.success) / max * 85" :width="Math.max(4, 670 / series.length - 6)" :height="Math.max(1, Number(point.success) / max * 85)" rx="3" fill="#7266c9"/><rect :x="15 + index * (670 / series.length)" :y="110 - (Number(point.success) + Number(point.failure)) / max * 85" :width="Math.max(4, 670 / series.length - 6)" :height="Number(point.failure) / max * 85" rx="3" fill="#c995a2"/></g></svg><div class="chart-legend"><span>Connexions réussies</span><span>Tentatives refusées</span></div></div><div class="panel table-scroll"><table class="users-table event-table"><thead><tr><th>Date</th><th>Événement</th><th>Membre / identifiant</th><th>Adresse réseau</th></tr></thead><tbody><tr v-for="event in data.events" :key="event.id"><td>{{ eventTime(event.created_at) }}</td><td><span class="event-type" :class="{ 'event-failure': event.type === 'login_failure' }">{{ types[event.type] }}</span><small v-if="event.target_id" class="event-target">Référence {{ event.target_id }}</small></td><td><strong>{{ event.actor_name || (event.type === 'login_failure' ? 'Identifiant fourni' : 'Non identifié') }}</strong><small class="event-target">{{ event.actor_email || '—' }}</small></td><td>{{ event.client_ip || '—' }}</td></tr><tr v-if="!data.events.length"><td colspan="4" class="empty-state">Aucun événement sur cette période.</td></tr></tbody></table></div><p class="field-hint">200 événements récents maximum · conservation 30 jours. Une tentative refusée ne confirme pas l’identité du membre.</p></template></section></template>
