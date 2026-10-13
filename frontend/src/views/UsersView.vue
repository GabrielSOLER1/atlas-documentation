<script setup>
import { computed, onMounted, ref } from 'vue'
import Icon from '../components/Icon.vue'
import { api, session, formatDate } from '../api'

const users = ref([])
const roles = ref({})
const loading = ref(true)
const error = ref('')
const success = ref('')
const busy = ref(null)
const createDialog = ref(null)
const statusDialog = ref(null)
const target = ref(null)
const creating = ref(false)
const createError = ref('')
const form = ref({ name: '', email: '', password: '', role: 'ROLE_USER' })
const activeCount = computed(() => users.value.filter(user => user.active).length)
const adminCount = computed(() => users.value.filter(user => user.active && user.role === 'ROLE_ADMIN').length)
const initials = name => name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase()

onMounted(async () => {
  if (!session.user?.admin) { error.value = 'Seul un administrateur peut gérer les comptes.'; loading.value = false; return }
  try { users.value = await api('/users'); roles.value = Object.fromEntries(users.value.map(user => [user.id, user.role])) }
  catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})

function openCreate() {
  form.value = { name: '', email: '', password: '', role: 'ROLE_USER' }
  createError.value = ''
  createDialog.value.showModal()
}

async function createUser() {
  creating.value = true
  createError.value = ''
  try {
    const user = await api('/users', { method: 'POST', body: JSON.stringify(form.value) })
    users.value.unshift(user)
    roles.value[user.id] = user.role
    success.value = `Le compte de ${user.name} a été créé.`
    form.value.password = ''
    createDialog.value.close()
  } catch (failure) { createError.value = failure.message }
  finally { creating.value = false }
}

async function updateUser(user, changes) {
  busy.value = user.id
  error.value = ''
  success.value = ''
  try {
    const updated = await api(`/users/${user.id}`, { method: 'PATCH', body: JSON.stringify(changes) })
    users.value = users.value.map(item => item.id === user.id ? updated : item)
    roles.value[user.id] = updated.role
    success.value = 'active' in changes ? `Le compte de ${user.name} a été ${updated.active ? 'réactivé' : 'désactivé'}.` : `Le rôle de ${user.name} a été mis à jour.`
    if (statusDialog.value.open) statusDialog.value.close()
  } catch (failure) { error.value = failure.message }
  finally { busy.value = null }
}

function confirmStatus(user) {
  error.value = ''
  target.value = user
  statusDialog.value.showModal()
}
</script>

<template>
  <section class="users-page page-enter">
    <div class="page-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1>Comptes & accès<span class="title-dot">.</span></h1><p class="lead">Les bonnes personnes, avec les bons droits.</p></div><button v-if="session.user?.admin" class="button primary" @click="openCreate"><Icon name="plus" :size="17" /> Créer un compte</button></div>
    <p v-if="error && !statusDialog?.open" class="notice error" role="alert">{{ error }}</p><p v-if="success" class="notice success" role="status"><Icon name="check" :size="17" />{{ success }}</p>
    <template v-if="session.user?.admin">
      <div class="account-stats"><div><span class="stat-icon"><Icon name="users" /></span><div><strong>{{ loading ? '—' : users.length }}</strong><span>Comptes au total</span></div></div><div><span class="stat-icon green"><Icon name="check" /></span><div><strong>{{ loading ? '—' : activeCount }}</strong><span>Comptes actifs</span></div></div><div><span class="stat-icon"><Icon name="shield" /></span><div><strong>{{ loading ? '—' : adminCount }}</strong><span>Administrateurs actifs</span></div></div></div>
      <div class="panel users-panel"><div class="panel-heading"><h2>Équipe Atlas</h2><span class="muted">{{ users.length }} comptes</span></div><p v-if="loading" class="loading-state" role="status">Chargement des comptes…</p><div v-else class="table-scroll"><table class="users-table"><thead><tr><th scope="col">Membre</th><th scope="col">Rôle</th><th scope="col">Statut</th><th scope="col">Action</th></tr></thead><tbody><tr v-for="user in users" :key="user.id" :class="{ 'inactive-row': !user.active }"><td><div class="member-cell"><span class="avatar" :class="{ 'avatar-admin': user.role === 'ROLE_ADMIN' }">{{ initials(user.name) }}</span><div><strong>{{ user.name }} <span v-if="user.id === session.user.id" class="you-label">Vous</span></strong><span>{{ user.email }}</span><small>Créé le {{ formatDate(user.created_at) }}</small></div></div></td><td><div class="role-cell"><select v-model="roles[user.id]" :aria-label="`Rôle de ${user.name}`" :disabled="user.id === session.user.id || busy !== null"><option value="ROLE_USER">Utilisateur</option><option value="ROLE_ADMIN">Administrateur</option></select><button v-if="roles[user.id] !== user.role" class="button compact primary" :disabled="busy !== null" @click="updateUser(user, { role: roles[user.id] })">Enregistrer</button></div></td><td><span class="status-pill" :class="user.active ? 'is-active' : 'is-inactive'"><span></span>{{ user.active ? 'Actif' : 'Désactivé' }}</span></td><td><button class="button compact" :class="user.active ? 'danger-ghost' : 'subtle'" :disabled="user.id === session.user.id || busy !== null" :aria-label="`${user.active ? 'Désactiver' : 'Réactiver'} le compte de ${user.name}`" @click="confirmStatus(user)">{{ busy === user.id ? 'En cours…' : user.active ? 'Désactiver' : 'Réactiver' }}</button></td></tr></tbody></table></div></div>
      <div class="info-note"><Icon name="shield" :size="19" /><p>Votre compte administrateur reste actif. Une désactivation coupe l’accès dès la prochaine requête ; un changement de rôle nécessite une nouvelle connexion.</p></div>
    </template>
    <dialog ref="createDialog" class="account-dialog" @close="form.password = ''" @cancel="creating && $event.preventDefault()">
      <form @submit.prevent="createUser"><div class="dialog-heading"><span class="dialog-icon"><Icon name="users" :size="24" /></span><button type="button" class="icon-button" aria-label="Fermer" :disabled="creating" @click="createDialog.close()"><Icon name="close" /></button></div><p class="eyebrow">UN NOUVEAU MEMBRE</p><h2>Créer un compte</h2><p class="muted">Donnez accès à la documentation et choisissez les droits du membre.</p><label for="new-name">Nom complet</label><input id="new-name" v-model="form.name" autocomplete="off" autofocus required maxlength="100"><label for="new-email">Adresse e-mail</label><input id="new-email" v-model="form.email" type="email" autocomplete="off" required maxlength="180"><label for="new-password">Mot de passe initial</label><input id="new-password" v-model="form.password" type="password" autocomplete="new-password" required minlength="12" maxlength="72"><p class="field-hint">12 caractères minimum. Transmettez-le au membre par votre canal interne.</p><label for="new-role">Rôle</label><select id="new-role" v-model="form.role"><option value="ROLE_USER">Utilisateur — consulter les procédures</option><option value="ROLE_ADMIN">Administrateur — gérer les procédures et les comptes</option></select><p v-if="createError" role="alert" class="notice error">{{ createError }}</p><div class="form-actions"><button type="button" class="button subtle" :disabled="creating" @click="createDialog.close()">Annuler</button><button class="button primary" :disabled="creating">{{ creating ? 'Création…' : 'Créer le compte' }}<Icon name="arrow" :size="16" /></button></div></form>
    </dialog>
    <dialog ref="statusDialog" class="account-dialog small-dialog" @cancel="busy !== null && $event.preventDefault()"><template v-if="target"><span class="dialog-icon" :class="{ 'warning-icon': target.active }"><Icon name="shield" :size="25" /></span><h2>{{ target.active ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?' }}</h2><p class="muted"><strong>{{ target.name }}</strong>{{ target.active ? ' ne pourra plus se connecter ni poursuivre sa consultation. Ses données seront conservées.' : ' pourra à nouveau se connecter avec ses identifiants et son rôle actuel.' }}</p><p v-if="error" role="alert" class="notice error">{{ error }}</p><div class="form-actions"><button class="button subtle" :disabled="busy !== null" @click="statusDialog.close()">Annuler</button><button class="button" :class="target.active ? 'danger' : 'primary'" :disabled="busy !== null" @click="updateUser(target, { active: !target.active })">{{ busy !== null ? 'En cours…' : target.active ? 'Désactiver le compte' : 'Réactiver le compte' }}</button></div></template></dialog>
  </section>
</template>
