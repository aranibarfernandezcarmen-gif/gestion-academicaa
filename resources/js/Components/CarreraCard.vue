<template>
  <div class="group bg-white rounded-xl shadow-lg hover:shadow-2xl transition transform hover:-translate-y-2 overflow-hidden">
    <!-- Header con color -->
    <div :class="['h-32 flex items-center justify-center text-white', colorClass]">
      <div class="relative w-full h-full overflow-hidden">
        <img
          v-if="logo"
          :src="logo"
          :alt="`${nombre} logo`"
          class="h-full w-full object-cover"
        />
        <span
          v-else
          v-html="careerIcon"
          class="absolute inset-0 m-auto h-16 w-16"
        ></span>
      </div>
    </div>

    <!-- Contenido -->
    <div class="p-6">
      <h3 class="text-xl font-bold text-gray-900 mb-2 text-center">{{ nombre }}</h3>
      <p class="text-gray-600 text-sm mb-4 text-center">{{ descripcion }}</p>

      <!-- Info -->
      <div class="mb-6">
        <div class="flex items-center justify-center text-sm text-gray-700">
          <span class="font-semibold mr-2">Sigla:</span>
          <span class="bg-blue-100 text-blue-900 px-3 py-1 rounded-full text-xs font-bold">{{ sigla }}</span>
        </div>
      </div>

      <!-- Button -->
      <button @click="$emit('open-modal')" class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition">
        Más Información
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  nombre: String,
  descripcion: String,
  sigla: String,
  logo: String,
  color: {
    type: String,
    default: 'blue'
  }
})

const colorClasses = {
  'blue': 'bg-gradient-to-r from-blue-600 to-blue-700',
  'purple': 'bg-gradient-to-r from-purple-600 to-purple-700',
  'green': 'bg-gradient-to-r from-green-600 to-green-700',
  'red': 'bg-gradient-to-r from-red-600 to-red-700'
}

const iconMap = {
  INF: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="14" rx="2" ry="2"/><path d="M8 20h8"/><path d="M8 12h8"/></svg>`,
  SIS: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16"/><path d="M12 4v16"/><circle cx="12" cy="12" r="3"/></svg>`,
  RED: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 3v2"/><path d="M12 19v2"/><path d="M4.93 4.93l1.41 1.41"/><path d="M17.66 17.66l1.41 1.41"/><path d="M3 12h2"/><path d="M19 12h2"/><path d="M4.93 19.07l1.41-1.41"/><path d="M17.66 6.34l1.41-1.41"/></svg>`,
  ROB: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="8" width="12" height="10" rx="2" ry="2"/><path d="M9 8V6a3 3 0 016 0v2"/><path d="M10 14h4"/><path d="M10 17h4"/></svg>`
}

const colorClass = computed(() => colorClasses[props.color] || colorClasses['blue'])
const careerIcon = computed(() => iconMap[props.sigla] || iconMap.INF)
</script>
