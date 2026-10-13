<script setup>
defineProps({ name: { type: String, default: 'book' }, size: { type: Number, default: 20 } })
const paths = {
  grid: ['M3 3h7v7H3z', 'M14 3h7v7h-7z', 'M3 14h7v7H3z', 'M14 14h7v7h-7z'],
  list: ['M8 6h13', 'M8 12h13', 'M8 18h13', 'M3 6h.01', 'M3 12h.01', 'M3 18h.01'],
  focus: ['M8 3H3v5', 'M16 3h5v5', 'M3 16v5h5', 'M21 16v5h-5'],
  sun: ['M12 3v2', 'M12 19v2', 'M3 12h2', 'M19 12h2', 'M5 5l2 2', 'M17 17l2 2', 'M5 19l2-2', 'M17 7l2-2', 'M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
  moon: ['M21 13A9 9 0 0 1 11 3a9 9 0 1 0 10 10Z'],
  refresh: ['M20 8a9 9 0 1 0 1 6', 'M20 3v5h-5'],
  panel: ['M3 3h18v18H3z', 'M9 3v18'],
  spark: ['m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z'],
  bookmark: ['M5 3h14v18l-7-4-7 4z'],
  camera: ['M3 7h4l2-3h6l2 3h4v13H3z', 'M16 13a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
  menu: ['M3 6h18', 'M3 12h18', 'M3 18h18'],
  route: ['M6 5h.01', 'M18 19h.01', 'M6 8v4a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3', 'M9 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0', 'M21 19a3 3 0 1 1-6 0 3 3 0 0 1 6 0'],
  copy: ['M9 9h12v12H9z', 'M5 15H3V3h12v2'],
  print: ['M6 9V3h12v6', 'M6 17H3V9h18v8h-3', 'M6 14h12v7H6z'],
  layers: ['m12 3 10 6-10 6L2 9z', 'm2 14 10 6 10-6'],
  help: ['M9 9a3 3 0 0 1 6 0c0 2-3 2-3 5', 'M12 18h.01', 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0'],
  activity: ['M3 12h4l3-8 4 16 3-8h4'],
  code: ['m8 6-6 6 6 6', 'm16 6 6 6-6 6'],
  eye: ['M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12', 'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0'],
  up: ['m6 15 6-6 6 6'], down: ['m6 9 6 6 6-6'],
  trash: ['M3 6h18', 'M8 6V3h8v3', 'M5 6l1 15h12l1-15', 'M10 10v7', 'M14 10v7'],
  grip: ['M8 5h.01', 'M16 5h.01', 'M8 12h.01', 'M16 12h.01', 'M8 19h.01', 'M16 19h.01'],
  book: ['M4 4h6a3 3 0 0 1 3 3v14a4 4 0 0 0-4-3H4z', 'M20 4h-4a3 3 0 0 0-3 3v14a4 4 0 0 1 4-3h3z'],
  users: ['M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2', 'M16 3a4 4 0 0 1 0 8', 'M22 21v-2a4 4 0 0 0-3-3.87', 'M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0'],
  search: ['M21 21l-5-5', 'M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0'],
  arrow: ['M5 12h14', 'm13 6 6 6-6 6'],
  back: ['M19 12H5', 'm11 6-6 6 6 6'],
  logout: ['M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4', 'M9 12h12', 'm16 7 5 5-5 5'],
  edit: ['m16 3 5 5-12 12-6 1 1-6z', 'm14 5 5 5'],
  plus: ['M12 5v14', 'M5 12h14'],
  shield: ['m12 3 8 4v5c0 5-8 9-8 9s-8-4-8-9V7z', 'm8 12 3 3 5-6'],
  clock: ['M12 8v4l3 2', 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0'],
  file: ['M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z', 'M14 2v6h6', 'M8 13h8', 'M8 17h6'],
  chevron: ['m9 5 7 7-7 7'],
  check: ['m5 12 4 4L19 6'],
  close: ['m6 6 12 12', 'M6 18 18 6'],
  info: ['M12 11v6', 'M12 7h.01', 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0'],
}
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="icon"><path v-for="(path, index) in paths[name] || paths.book" :key="index" :d="path" /></svg>
</template>
