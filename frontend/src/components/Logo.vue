<script setup>
defineProps({ compact: Boolean })
const logoUrl = `${import.meta.env.BASE_URL}atlas-mark.svg`
</script>
<template><span class="atlas-logo"><img :src="logoUrl" width="42" height="42" alt="" aria-hidden="true"><span v-if="!compact" class="logo-wordmark">atlas<small>CONSEIL</small></span></span></template>
