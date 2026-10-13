<script setup>
defineProps({ user: { type: Object, required: true }, large: Boolean })
</script>
<template><span class="profile-avatar" :class="[user.accent || 'indigo', { large }]">
  <img v-if="user.avatarUrl" :src="user.avatarUrl" alt="" width="256" height="256">
  <span v-else>{{ user.name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() }}</span>
</span></template>
