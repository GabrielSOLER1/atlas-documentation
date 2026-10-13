<script setup>
import { ref } from 'vue'
import Icon from '../components/Icon.vue'
import Logo from '../components/Logo.vue'
import { useRouter } from 'vue-router'
import { api, session, refreshSession } from '../api'

const email = ref('')
const password = ref('')
const error = ref('')
const busy = ref(false)
const router = useRouter()

async function login() {
  error.value = ''
  busy.value = true
  try {
    const body = new URLSearchParams({ email: email.value, password: password.value, _csrf_token: session.loginToken })
    await api('/login', { method: 'POST', body })
    password.value = ''
    await refreshSession()
    if (!session.user) throw new Error('La session n’a pas pu être ouverte. Réessayez.')
    await router.push('/')
  } catch (failure) {
    error.value = failure.message
    await refreshSession()
  } finally { busy.value = false }
}
</script>

<template>
  <section class="login-layout standalone-login"><div class="login-brand-emblem"><Logo compact /></div>
    <div class="login-intro"><p class="eyebrow">VOTRE ESPACE IT</p><h1>Les bonnes procédures.<br>Au bon endroit.</h1><p class="lead">Retrouvez les guides et les informations utiles pour travailler sereinement avec vos outils informatiques.</p><div class="intro-note"><Icon name="book" :size="26" /><p>Une documentation commune,<br>maintenue par votre équipe informatique.</p></div></div>
    <form class="panel login-form" @submit.prevent="login">
      <p class="eyebrow">BIENVENUE SUR ATLAS</p><h2>Se connecter</h2><p class="muted">Utilisez votre compte pour consulter les procédures internes.</p>
      <label for="email">Adresse e-mail</label><input id="email" v-model="email" type="email" autocomplete="username" required maxlength="180">
      <label for="password">Mot de passe</label><input id="password" v-model="password" type="password" autocomplete="current-password" required maxlength="4096">
      <p v-if="error" class="notice error" role="alert">{{ error }}</p><button class="button primary full-width" :disabled="busy">{{ busy ? 'Connexion…' : 'Se connecter' }} <Icon name="arrow" :size="17" /></button>
      <p class="form-note">Votre accès est attribué par le service informatique. Les connexions sont journalisées pour protéger le portail.</p>
    </form>
  </section>
</template>
