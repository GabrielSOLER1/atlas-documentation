<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'
import { parseSections } from '../document'
const props = defineProps({ content: { type: String, default: '' }, blocks: { type: Array, default: null }, activeSection: { type: String, default: '' } })
const sections = computed(() => {
  let number = 0
  const source = props.blocks || parseSections(props.content).map(section => ({ type: section.title ? 'section' : 'text', title: section.title, text: section.paragraphs.join('\n\n') }))
  return source.map((block, index) => ({ ...block, id: `section-${index + 1}`, number: block.type === 'section' ? String(++number).padStart(2, '0') : null }))
})
defineExpose({ sections })
</script>
<template><div class="document-body"><section v-for="block in sections" :id="block.id" :key="block.id" tabindex="-1" class="reading-section" :class="[{ 'section-active': activeSection === block.id }, `block-${block.type}`]"><div v-if="block.title" class="step-heading"><span v-if="block.number" class="step-number">{{ block.number }}</span><span v-else-if="block.type === 'note'" class="section-mark"><Icon name="info" :size="18" /></span><h2>{{ block.title }}</h2></div><template v-if="block.type === 'checklist'"><ul class="reader-checklist"><li v-for="(item, index) in block.items" :key="index"><Icon name="check" :size="17" /><span>{{ item }}</span></li></ul></template><pre v-else-if="block.type === 'code'" class="reader-code"><code>{{ block.text }}</code></pre><div v-else class="step-body" :class="{ 'has-heading': block.type === 'section' && block.title }"><p v-for="(paragraph, index) in (block.text || '').split(/\n\s*\n/).filter(Boolean)" :key="index">{{ paragraph }}</p></div></section></div></template>
