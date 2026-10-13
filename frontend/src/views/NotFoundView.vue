<script setup>
import Logo from '../components/Logo.vue'
import Icon from '../components/Icon.vue'
import { session } from '../api'
defineProps({ forbidden: Boolean })
</script>
<template><section class="custom-error page-enter"><Logo compact /><span class="error-code">{{ forbidden ? '403' : '404' }}</span><p class="eyebrow">{{ forbidden ? 'ACCÈS RÉSERVÉ' : 'UNE PAGE HORS DU GUIDE' }}</p><h1>{{ forbidden ? 'Cet espace est réservé aux administrateurs.' : 'Cette page ne fait pas partie du guide.' }}</h1><p class="lead">{{ forbidden ? 'Votre compte permet de consulter la documentation. Le service informatique peut adapter vos droits si nécessaire.' : 'L’adresse est introuvable. Retrouvez les informations disponibles depuis le portail.' }}</p><RouterLink :to="session.user ? '/' : '/connexion'" class="button primary">{{ session.user ? 'Revenir à la bibliothèque' : 'Revenir à la connexion' }}<Icon name="arrow" :size="16" /></RouterLink></section></template>
