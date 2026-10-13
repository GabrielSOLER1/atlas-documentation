<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { api, session } from '../api'
import Avatar from '../components/Avatar.vue'
import Icon from '../components/Icon.vue'

const form = reactive({ name: '', department: '', jobTitle: '', bio: '', readingSize: 'standard', accent: 'indigo' })
const loading = ref(true)
const busy = ref(false)
const imageBusy = ref(false)
const error = ref('')
const notice = ref('')
const preview = ref('')
const image = ref('')
onMounted(async () => {
  try { const user = await api('/profile'); session.user = user; for (const key of Object.keys(form)) form[key] = user[key] }
  catch (failure) { error.value = failure.message }
  finally { loading.value = false }
})
function clearPreview() { if (preview.value) URL.revokeObjectURL(preview.value); preview.value = ''; image.value = '' }
onBeforeUnmount(clearPreview)
async function choosePhoto(event) {
  error.value = ''; notice.value = ''; clearPreview()
  const file = event.target.files[0]
  event.target.value = ''
  if (!file) return
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 262144) {
    error.value = 'Choisissez une photo JPEG, PNG ou WebP de 256 Ko maximum.'; return
  }
  try {
    const data = await new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.onerror = reject; reader.readAsDataURL(file) })
    image.value = data.split(',')[1]
    preview.value = URL.createObjectURL(file)
  } catch { error.value = 'La photo ne peut pas être lue.' }
}
async function updatePhoto(remove = false) {
  imageBusy.value = true; error.value = ''; notice.value = ''
  try {
    session.user = await api('/profile/avatar', { method: remove ? 'DELETE' : 'PUT', ...(remove ? {} : { body: JSON.stringify({ image: image.value }) }) })
    clearPreview(); notice.value = remove ? 'Votre photo a été supprimée.' : 'Votre photo a été mise à jour.'
  } catch (failure) { error.value = failure.status === 400 ? 'Cette photo est invalide ou trop grande. Choisissez une autre image.' : failure.message }
  finally { imageBusy.value = false }
}
async function save() {
  busy.value = true; error.value = ''; notice.value = ''
  try { session.user = await api('/profile', { method: 'PUT', body: JSON.stringify(form) }); notice.value = 'Votre profil et vos préférences ont été enregistrés.' }
  catch (failure) { error.value = failure.message }
  finally { busy.value = false }
}
</script>
<template><section class="page-enter profile-page"><p class="eyebrow">UN ESPACE À VOTRE IMAGE</p><h1>Mon profil<span class="title-dot">.</span></h1><p class="lead">Personnalisez votre présentation et votre confort de lecture.</p>
  <p v-if="error" role="alert" class="notice error">{{ error }}</p><p v-if="notice" role="status" class="notice success">{{ notice }}</p>
  <p v-if="loading" role="status" class="loading-state">Chargement du profil…</p>
  <div v-else-if="session.user" class="profile-layout"><aside class="panel profile-summary"><div class="profile-photo-wrap"><img v-if="preview" :src="preview" alt="Aperçu de la nouvelle photo" class="profile-photo-preview"><Avatar v-else :user="{ ...session.user, accent: form.accent }" large /></div><h2>{{ session.user.name }}</h2><span class="badge">{{ session.user.admin ? 'Administrateur' : 'Collaborateur' }}</span><p class="muted">{{ session.user.email }}</p>
    <label class="button subtle photo-picker" for="profile-photo"><Icon name="camera" :size="16" />Choisir une photo</label><input id="profile-photo" class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" :disabled="imageBusy" @change="choosePhoto">
    <div v-if="preview" class="photo-actions"><button class="button primary full-width" :disabled="imageBusy" @click="updatePhoto()">{{ imageBusy ? 'Enregistrement…' : 'Enregistrer la photo' }}</button><button class="button subtle full-width" :disabled="imageBusy" @click="clearPreview">Annuler</button></div>
    <button v-else-if="session.user.avatarUrl" class="button subtle full-width" :disabled="imageBusy" @click="updatePhoto(true)">Retirer la photo</button><p class="field-hint">JPEG, PNG ou WebP · 256 Ko maximum.<br>Recadrage carré automatique ; métadonnées supprimées.</p>
    <div class="profile-private"><Icon name="shield" :size="18" /><p>Votre photo et vos préférences sont conservées dans Atlas. Vous pouvez supprimer les informations facultatives à tout moment.</p></div>
  </aside><form class="panel profile-form" @submit.prevent="save"><p class="eyebrow">PRÉSENTATION</p><h2>Vos informations</h2><div class="form-grid"><div><label for="profile-name">Nom affiché</label><input id="profile-name" v-model="form.name" required maxlength="100" autocomplete="name"></div><div><label for="profile-job">Fonction <span class="muted">· facultatif</span></label><input id="profile-job" v-model="form.jobTitle" maxlength="80" autocomplete="organization-title"></div><div><label for="profile-department">Service <span class="muted">· facultatif</span></label><input id="profile-department" v-model="form.department" maxlength="80"></div><div><label>Adresse du compte</label><p class="readonly-field">{{ session.user.email }}</p></div></div><label for="profile-bio">À propos <span class="muted">· facultatif</span></label><textarea id="profile-bio" v-model="form.bio" rows="3" maxlength="300"></textarea><p class="field-hint">{{ form.bio.length }}/300 caractères. Ces informations restent dans votre espace personnel.</p>
    <div class="profile-preferences"><p class="eyebrow">CONFORT DE LECTURE</p><h2>Votre façon de consulter</h2><label for="reading-size">Taille du texte des guides</label><select id="reading-size" v-model="form.readingSize"><option value="standard">Standard</option><option value="large">Plus grande</option></select><fieldset class="accent-options"><legend>Couleur de vos initiales</legend><label v-for="[value, label] in [['indigo', 'Indigo'], ['teal', 'Sauge'], ['slate', 'Ardoise']]" :key="value" :class="value"><input v-model="form.accent" type="radio" name="accent" :value="value"><span>{{ label }}</span></label></fieldset><p class="field-hint">Les préférences sont conservées lorsque vous changez de poste.</p></div><div class="profile-save"><button class="button primary" :disabled="busy">{{ busy ? 'Enregistrement…' : 'Enregistrer mon profil' }}<Icon name="check" :size="16" /></button></div>
  </form></div></section></template>
