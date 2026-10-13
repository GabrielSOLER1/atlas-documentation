<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Icon from './components/Icon.vue'
import Logo from './components/Logo.vue'
import Avatar from './components/Avatar.vue'
import { api, session, refreshSession } from './api'
const router = useRouter()
const route = useRoute()
const busy = ref(false)
const menuOpen = ref(false)
watch(() => route.fullPath, () => { menuOpen.value = false })
const error = ref('')
const portal = computed(() => !!session.user && route.path !== '/connexion')
const navigation = [{ path: '/', label: 'Bibliothèque', icon: 'book' }, { path: '/categories', label: 'Catégories', icon: 'layers' }, { path: '/favoris', label: 'Mes favoris', icon: 'bookmark' }, { path: '/aide', label: 'Aide & prise en main', icon: 'help' }, { path: '/confidentialite', label: 'Confidentialité & RGPD', icon: 'shield' }]
const administration = [{ path: '/roadmap', label: 'Roadmap administrateur', icon: 'route' }, { path: '/procedures/nouveau', label: 'Créer une documentation', icon: 'plus' }, { path: '/comptes', label: 'Comptes & accès', icon: 'users' }, { path: '/activite', label: 'Journal d’activité', icon: 'activity' }]
function active(path) { return path === '/' ? route.path === '/' || /^\/procedures\/\d/.test(route.path) : route.path === path }
function focusMain() { window.document.getElementById('main-content')?.focus() }
watch(() => session.user, (current, previous) => { if (!current && previous && !route.meta.public) router.replace('/connexion') })
async function logout() {
  busy.value = true
  error.value = ''
  try { await api('/logout', { method: 'POST' }); session.user = null; session.flash = ''; await refreshSession(); await router.push('/connexion') }
  catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
async function retry() { await refreshSession(); if (!session.error) await router.replace(session.user ? '/' : '/connexion') }
</script>
<template><a class="skip-link" href="#main-content" @click.prevent="focusMain">Aller au contenu</a><div :class="[portal ? 'shell' : 'public-shell', { 'reading-large': session.user?.readingSize === 'large', 'menu-open': menuOpen }]"><aside v-if="portal" class="sidebar"><RouterLink to="/" class="brand" aria-label="Atlas, accueil"><Logo /></RouterLink><button class="mobile-menu icon-button" aria-label="Navigation" :aria-expanded="menuOpen" aria-controls="portal-navigation portal-administration" @click="menuOpen = !menuOpen"><Icon :name="menuOpen ? 'close' : 'menu'" /></button><div id="portal-navigation" class="sidebar-section"><p class="sidebar-label">VOTRE ESPACE</p><RouterLink v-for="item in navigation" :key="item.path" :to="item.path" class="nav-link" :class="{ 'nav-active': active(item.path) }"><Icon :name="item.icon" :size="18" /><span>{{ item.label }}</span></RouterLink></div><div v-if="session.user.admin" id="portal-administration" class="sidebar-section"><p class="sidebar-label">ADMINISTRATION</p><RouterLink v-for="item in administration" :key="item.path" :to="item.path" class="nav-link" :class="{ 'nav-active': active(item.path) }"><Icon :name="item.icon" :size="18" /><span>{{ item.label }}</span></RouterLink></div><div class="sidebar-bottom"><div class="sidebar-note"><Icon name="shield" :size="21" /><p>Le savoir de l’équipe.<br><strong>À portée de main.</strong></p></div><div class="sidebar-status"><span class="status-dot"></span>Espace interne Atlas</div></div></aside><div class="workspace"><header v-if="portal" class="topbar"><div class="breadcrumb"><span>Atlas Documentation</span><Icon name="chevron" :size="12" /><strong>{{ route.meta.admin || route.path === '/comptes' ? 'Administration' : 'Documentation IT' }}</strong></div><div class="account"><RouterLink to="/profil" class="profile-link" aria-label="Ouvrir mon profil"><Avatar :user="session.user" /><span class="account-name">{{ session.user.name }}<small>{{ session.user.admin ? 'Administrateur' : 'Utilisateur' }}</small></span></RouterLink><button class="icon-button logout-button" :disabled="busy" aria-label="Déconnexion" title="Déconnexion" @click="logout"><Icon name="logout" :size="18" /></button></div></header><header v-else class="public-header"><RouterLink to="/connexion" aria-label="Atlas, connexion"><Logo /></RouterLink><span class="secure-label"><Icon name="shield" :size="15" />Portail interne</span></header><main id="main-content" tabindex="-1" :class="{ 'public-main': !portal }"><p v-if="error" role="alert" class="notice error">{{ error }}</p><div v-if="session.error" class="panel empty-state"><h1>Le portail est indisponible</h1><p role="alert">{{ session.error }}</p><button class="button primary" @click="retry">Réessayer</button></div><template v-else><p v-if="session.flash && portal" role="status" class="notice success"><Icon name="check" :size="17" />{{ session.flash }}<button class="icon-button dismiss" aria-label="Fermer le message" @click="session.flash = ''"><Icon name="close" :size="15" /></button></p><RouterView :key="$route.fullPath" /></template></main><footer><span class="footer-brand"><Logo compact />Atlas Documentation</span><div class="footer-links"><RouterLink to="/aide">Aide</RouterLink><RouterLink to="/confidentialite">Confidentialité & RGPD</RouterLink></div></footer></div></div></template>
