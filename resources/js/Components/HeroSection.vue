<template>
  <section id="inicio" class="relative min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-blue-700 text-white pt-20">
    <!-- Fondo animado -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute inset-0 bg-cover bg-center transition duration-700" :style="{ backgroundImage: 'url(' + backgrounds[activeIndex] + ')' }"></div>
      <div class="absolute inset-0 bg-slate-950/50"></div>
    </div>

    <!-- Contenido -->
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-20 grid lg:grid-cols-2 gap-8 items-center lg:items-start">
      <!-- Texto -->
      <div class="relative text-center lg:text-left pt-32 lg:pt-12">
        <div class="absolute left-1/2 -top-6 -translate-x-1/2 -translate-y-1/2 h-48 w-48 sm:h-52 sm:w-52 lg:hidden">
          <img src="/images/carreras/ficct.png" alt="FICCT logo" class="h-full w-full object-contain opacity-95" />
        </div>
        <img src="/images/carreras/ficct.png" alt="FICCT logo" class="absolute inset-x-0 top-24 mx-auto h-72 w-72 object-contain opacity-10 pointer-events-none hidden lg:block" />
        <h1 class="relative text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6">
          Bienvenido a la <span class="text-yellow-300">FICCT</span>
        </h1>
        <p class="relative text-lg sm:text-xl text-blue-100 mb-8 leading-relaxed transition-all duration-700">
          {{ descriptionTexts[activeIndex] }}
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
          <button class="px-8 py-4 bg-yellow-400 text-blue-900 rounded-lg font-bold hover:bg-yellow-300 transition transform hover:scale-105">
            <Link href="/postularse">Postúlate</Link>
          </button>
          <button class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-bold hover:bg-white hover:text-blue-900 transition">
            <a href="#carreras">Conocer Carreras</a>
          </button>
        </div>
      </div>

    </div>

    <div class="hidden lg:flex absolute bottom-12 right-8 items-center space-x-2">
      <button
        v-for="(bg, idx) in backgrounds"
        :key="idx"
        @click="setBackground(idx)"
        :class="['h-10 w-10 rounded-full text-sm font-semibold transition', activeIndex === idx ? 'bg-white text-blue-900' : 'bg-white/10 text-white hover:bg-white/20']"
      >
        {{ idx + 1 }}
      </button>
    </div>

    <!-- Scroll indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 text-blue-200 animate-bounce">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
      </svg>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'

const backgrounds = [
  '/images/hero-backgrounds/bg-1.png',
  '/images/hero-backgrounds/bg-2.png',
  '/images/hero-backgrounds/bg-3.png',
  '/images/hero-backgrounds/bg-4.png',
  '/images/hero-backgrounds/bg-5.png'
]

const descriptionTexts = [
  'Accede al sistema de gestión académica y admisión de postulantes en el curso preuniversitario. Elige tu carrera y comienza tu camino hacia la excelencia.',
  'Descubre nuestras carreras de Informática, Sistemas, Redes y Robótica. Programas diseñados para prepararte como profesional competitivo.',
  'Solicita tu inscripción al curso preuniversitario y prepárate para ingresar a la facultad. Nuestro equipo de docentes te guiarán en cada paso.',
  'Forma parte de la comunidad FICCT. Accede a recursos académicos, mentorías y oportunidades de empleo en el sector tecnológico.',
  'El futuro de la tecnología comienza aquí. Únete a nuestros programas y desarrolla las habilidades del profesional del siglo XXI.'
]

const activeIndex = ref(0)
const intervalId = ref(null)

const setBackground = (index) => {
  activeIndex.value = index
}

onMounted(() => {
  intervalId.value = window.setInterval(() => {
    activeIndex.value = (activeIndex.value + 1) % backgrounds.length
  }, 5000)
})

onBeforeUnmount(() => {
  if (intervalId.value) {
    clearInterval(intervalId.value)
  }
})
</script>

<style scoped>
.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}
</style>
