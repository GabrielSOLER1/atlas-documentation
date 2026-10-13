import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import LoginView from './views/LoginView.vue'
const DocumentsView = () => import('./views/DocumentsView.vue')
const DocumentView = () => import('./views/DocumentView.vue')
const DocumentEditorView = () => import('./views/DocumentEditorView.vue')
import NotFoundView from './views/NotFoundView.vue'
const UsersView = () => import('./views/UsersView.vue')
const CategoriesView = () => import('./views/CategoriesView.vue')
const InformationView = () => import('./views/InformationView.vue')
const ActivityView = () => import('./views/ActivityView.vue')
const ProfileView = () => import('./views/ProfileView.vue')
const RoadmapView = () => import('./views/RoadmapView.vue')
import { session, refreshSession } from './api'
import { ui } from './library'
import './style.css'
import './experience.css'

const router = createRouter({
  history: createWebHashHistory(),
  scrollBehavior: (to, from, saved) => to.path === from.path ? false : saved || { top: 0 },
  routes: [
    { path: '/connexion', component: LoginView, meta: { public: true } },
    { path: '/', component: DocumentsView },
    { path: '/categories', component: CategoriesView },
    { path: '/favoris', component: DocumentsView },
    { path: '/profil', component: ProfileView },
    { path: '/roadmap', component: RoadmapView, meta: { admin: true } },
    { path: '/aide', component: InformationView, meta: { public: true } },
    { path: '/confidentialite', component: InformationView, meta: { public: true } },
    { path: '/comptes', component: UsersView, meta: { admin: true } },
    { path: '/activite', component: ActivityView, meta: { admin: true } },
    { path: '/procedures/nouveau', component: DocumentEditorView, meta: { admin: true } },
    { path: '/procedures/:id', name: 'document', component: DocumentView },
    { path: '/procedures/:id/modifier', name: 'edit', component: DocumentEditorView, meta: { admin: true } },
    { path: '/acces-refuse', component: NotFoundView, props: { forbidden: true } },
    { path: '/:pathMatch(.*)*', component: NotFoundView, meta: { public: true } },
  ],
})
router.beforeEach(async (to, from) => {
  ui.navigationBusy = to.path !== from.path
  if (!session.ready || (session.user && to.path !== from.path)) await refreshSession()
  if (session.error) return true
  if (!to.meta.public && !session.user) return { path: '/connexion', query: { retour: to.fullPath } }
  if (to.meta.admin && !session.user?.admin) return '/acces-refuse'
  if (to.path === '/connexion' && session.user) return '/'
})
router.afterEach(to => {
  ui.navigationBusy = false
  const titles = { '/': 'Bibliothèque', '/connexion': 'Connexion', '/profil': 'Mon profil', '/favoris': 'Mes favoris', '/roadmap': 'Roadmap administrateur', '/comptes': 'Comptes & accès', '/activite': 'Journal d’activité', '/categories': 'Catégories', '/aide': 'Aide', '/confidentialite': 'Confidentialité & RGPD' }
  document.title = `${titles[to.path] || (to.path.startsWith('/procedures/') ? 'Documentation' : 'Page indisponible')} · Atlas Documentation`
})
router.onError(() => { ui.navigationBusy = false })
createApp(App).use(router).mount('#app')
