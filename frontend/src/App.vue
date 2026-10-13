<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Icon from './components/Icon.vue'
import Logo from './components/Logo.vue'
import Avatar from './components/Avatar.vue'
const CommandPalette = () => import('./components/CommandPalette.vue')
import { defineAsyncComponent } from 'vue'
const SearchPalette = defineAsyncComponent(CommandPalette)
import { api, session, refreshSession } from './api'
import { library, searchShortcut, ui } from './library'
import './drafts'
const router = useRouter()
const route = useRoute()
const busy = ref(false)
const menuOpen = ref(false)
const mobile = ref(false)
const menuTrigger = ref(null)
const sidebar = ref(null)
let media
let previousOverflow = ''
const rail = ref(false)
const error = ref('')
const portal = computed(() => !!session.user && route.path !== '/connexion')
const navigation = [{ path: '/', label: 'Bibliothèque', icon: 'book' }, { path: '/favoris', label: 'Mes favoris', icon: 'bookmark' }, { path: '/categories', label: 'Catégories', icon: 'layers' }]
const administration = [{ path: '/roadmap', label: 'Roadmap', icon: 'route' }, { path: '/comptes', label: 'Comptes & accès', icon: 'users' }, { path: '/activite', label: 'Activité', icon: 'activity' }]
const titles = { '/': 'Bibliothèque', '/favoris': 'Mes favoris', '/categories': 'Catégories', '/profil': 'Mon profil', '/roadmap': 'Roadmap administrateur', '/comptes': 'Comptes & accès', '/activite': 'Journal d’activité', '/aide': 'Aide & prise en main', '/confidentialite': 'Confidentialité', '/procedures/nouveau': 'Créer une documentation' }
const pageTitle = computed(() => titles[route.path] || (route.path.endsWith('/modifier') ? 'Modifier un guide' : route.path.startsWith('/procedures/') ? 'Lecture d’un guide' : 'Page indisponible'))
const favoriteCount = computed(() => library.documents.filter(doc => doc.favorite).length)
function active(path) { return path === '/' ? route.path === '/' || /^\/procedures\/\d/.test(route.path) : route.path === path }
function focusMain() { window.document.getElementById('main-content')?.focus() }
watch(() => route.path, async () => { menuOpen.value = false; ui.focusReading = false; error.value = ''; await nextTick(); focusMain() })
watch(() => session.user, (current, previous) => { if (!current && previous && !route.meta.public) router.replace('/connexion') })
async function logout() {
  busy.value = true; error.value = ''
  try { await api('/logout', { method: 'POST' }); session.user = null; session.flash = ''; await refreshSession(); await router.push('/connexion') }
  catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
async function retry() { await refreshSession(); if (!session.error) await router.replace(session.user ? '/' : '/connexion') }
function escape(event) {
  if (event.key === 'Escape') menuOpen.value = false
  if (event.key !== 'Tab' || !mobile.value || !menuOpen.value) return
  const elements = [...sidebar.value.querySelectorAll('a[href], button:not([disabled])')].filter(element => element.getClientRects().length)
  const first = elements[0], last = elements.at(-1)
  if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus() }
  else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus() }
}
function mobileChange(event) { mobile.value = event.matches; menuOpen.value = false }
watch(menuOpen, async open => {
  if (open && mobile.value) {
    previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    await nextTick(); sidebar.value?.querySelector('a')?.focus()
  } else {
    document.body.style.overflow = previousOverflow
    if (mobile.value) { await nextTick(); menuTrigger.value?.focus() }
  }
})
function focusCheck() { if (session.user && window.document.visibilityState === 'visible') refreshSession() }
onMounted(() => { media = window.matchMedia('(max-width: 760px)'); mobile.value = media.matches; media.addEventListener('change', mobileChange); window.addEventListener('keydown', escape); window.document.addEventListener('visibilitychange', focusCheck) })
onBeforeUnmount(() => { media?.removeEventListener('change', mobileChange); document.body.style.overflow = previousOverflow; window.removeEventListener('keydown', escape); window.document.removeEventListener('visibilitychange', focusCheck) })
</script>
<template><a class="skip-link" href="#main-content" @click.prevent="focusMain">Aller au contenu</a><div :class="[portal ? 'shell' : 'public-shell', { 'reading-large': session.user?.readingSize === 'large', 'menu-open': menuOpen, 'nav-rail': rail, 'focus-reading': ui.focusReading }]"><div v-if="portal && menuOpen" class="nav-backdrop" @click="menuOpen = false"></div>
<aside v-if="portal" ref="sidebar" class="sidebar" :inert="mobile && !menuOpen"><div class="sidebar-brand"><RouterLink to="/" class="brand" aria-label="Atlas Documentation, accueil"><Logo /></RouterLink><button class="icon-button mobile-menu" aria-label="Fermer la navigation" @click="menuOpen = false"><Icon name="close" /></button></div><button class="sidebar-search" @click="ui.searchOpen = true" aria-label="Rechercher dans Atlas"><Icon name="search" :size="17" /><span>Rechercher</span><kbd>{{ searchShortcut }}</kbd></button><nav id="portal-navigation" aria-label="Navigation principale"><div class="sidebar-section"><p class="sidebar-label">DOCUMENTATION</p><RouterLink v-for="item in navigation" :key="item.path" :to="item.path" class="nav-link" :class="{ 'nav-active': active(item.path) }" :title="item.label" :aria-label="item.label"><Icon :name="item.icon" :size="20" /><span>{{ item.label }}</span><small v-if="item.path === '/favoris' && favoriteCount" class="nav-count">{{ favoriteCount }}</small></RouterLink></div><div v-if="session.user.admin" class="sidebar-section"><p class="sidebar-label">ADMINISTRATION</p><RouterLink v-for="item in administration" :key="item.path" :to="item.path" class="nav-link" :title="item.label" :aria-label="item.label"><Icon :name="item.icon" :size="20" /><span>{{ item.label }}</span></RouterLink><RouterLink to="/procedures/nouveau" class="nav-create" title="Créer une documentation"><Icon name="plus" :size="18" /><span>Nouveau guide</span></RouterLink></div></nav><div class="sidebar-bottom"><RouterLink to="/aide" class="nav-link" title="Aide & prise en main"><Icon name="help" :size="19" /><span>Aide & prise en main</span></RouterLink><RouterLink to="/confidentialite" class="nav-link" title="Confidentialité & RGPD"><Icon name="shield" :size="19" /><span>Confidentialité</span></RouterLink><div class="sidebar-status"><span class="status-dot"></span><span>Espace interne</span><button class="icon-button rail-toggle" :aria-label="rail ? 'Déployer la navigation' : 'Réduire la navigation'" :aria-pressed="rail" @click="rail = !rail"><Icon name="panel" :size="16" /></button></div></div></aside>
<div class="workspace" :inert="mobile && menuOpen"><header v-if="portal" class="topbar"><div class="topbar-location"><button class="icon-button mobile-menu" ref="menuTrigger" aria-label="Ouvrir la navigation" :aria-expanded="menuOpen" aria-controls="portal-navigation" @click="menuOpen = !menuOpen"><Icon name="menu" :size="21" /></button><div class="breadcrumb"><span>Atlas</span><Icon name="chevron" :size="12" /><strong>{{ pageTitle }}</strong></div></div><div class="topbar-actions"><button class="topbar-search" aria-label="Rechercher un guide ou une page" @click="ui.searchOpen = true"><Icon name="search" :size="17" /><span>Rechercher</span><kbd>{{ searchShortcut }}</kbd></button><RouterLink to="/profil" class="profile-link" aria-label="Ouvrir mon profil"><Avatar :user="session.user" /><span class="account-name">{{ session.user.name }}<small>{{ session.user.admin ? 'Administrateur' : 'Collaborateur' }}</small></span></RouterLink><button class="icon-button logout-button" :disabled="busy" aria-label="Déconnexion" title="Déconnexion" @click="logout"><Icon name="logout" :size="18" /></button></div><div v-if="ui.navigationBusy" class="route-progress" aria-label="Chargement de la page" role="status"></div></header>
<header v-else class="public-header"><RouterLink to="/connexion" aria-label="Atlas, connexion"><Logo /></RouterLink><span class="secure-label"><span class="status-dot"></span>Portail interne</span></header>
<main id="main-content" tabindex="-1" :class="{ 'public-main': !portal }"><p v-if="error" role="alert" class="notice error">{{ error }}</p><div v-if="session.error" class="panel empty-state"><span class="empty-icon"><Icon name="refresh" :size="28" /></span><h1>Retrouvons votre espace</h1><p role="alert">{{ session.error }}</p><button class="button primary" @click="retry">Réessayer</button></div><template v-else><p v-if="session.flash && portal" role="status" class="notice success"><Icon name="check" :size="17" />{{ session.flash }}<button class="icon-button dismiss" aria-label="Fermer le message" @click="session.flash = ''"><Icon name="close" :size="15" /></button></p><RouterView v-slot="{ Component }"><Transition name="route" mode="out-in"><component :is="Component" :key="route.path" /></Transition></RouterView></template></main>
<footer><span class="footer-brand"><Logo compact />Atlas Documentation</span><div class="footer-links"><RouterLink to="/aide">Aide</RouterLink><RouterLink to="/confidentialite">Confidentialité</RouterLink></div></footer></div><SearchPalette v-if="portal" /><Transition name="toast"><div v-if="ui.toast" role="status" class="toast-message"><span><Icon name="check" :size="17" /></span>{{ ui.toast }}<button class="icon-button" aria-label="Fermer la notification" @click="ui.toast = null"><Icon name="close" :size="15" /></button></div></Transition></div></template>
