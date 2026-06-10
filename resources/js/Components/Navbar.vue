<template>
  <nav class="bg-gradient-to-r from-blue-700 to-blue-900 text-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <div class="flex items-center space-x-2">
          <div class="bg-white rounded-full w-10 h-10 flex items-center justify-center">
            <span class="font-bold text-blue-900">FICCT</span>
          </div>
          <span class="hidden sm:inline font-bold text-lg">Gestión Académica</span>
        </div>

        <!-- Menu Desktop -->
        <div class="hidden md:flex space-x-8">
          <a href="#inicio" class="hover:text-blue-200 transition">Inicio</a>
          <a href="#carreras" class="hover:text-blue-200 transition">Carreras</a>
          <a href="#proceso" class="hover:text-blue-200 transition">Proceso</a>
          <a href="#contacto" class="hover:text-blue-200 transition">Contacto</a>
        </div>

        <!-- Buttons -->
        <div class="flex items-center space-x-4">
          <button class="hidden sm:inline px-4 py-2 bg-yellow-400 text-blue-900 rounded hover:bg-yellow-300 transition font-semibold">
            <Link href="/postularse/ingresar">Ingresar</Link>
          </button>

          <!-- Menu Hamburguesa -->
          <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-blue-600 transition">
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Menu Mobile -->
      <transition name="slide">
        <div v-if="mobileMenuOpen" class="md:hidden pb-4 space-y-2">
          <a href="#inicio" class="block px-4 py-2 hover:bg-blue-600 rounded transition">Inicio</a>
          <a href="#carreras" class="block px-4 py-2 hover:bg-blue-600 rounded transition">Carreras</a>
          <a href="#proceso" class="block px-4 py-2 hover:bg-blue-600 rounded transition">Proceso</a>
          <a href="#contacto" class="block px-4 py-2 hover:bg-blue-600 rounded transition">Contacto</a>
          <div class="pt-2 space-y-2">
            <Link href="/postularse/ingresar" class="block px-4 py-2 bg-yellow-400 text-blue-900 rounded text-center font-semibold">
              Ingresar
            </Link>
          </div>
        </div>
      </transition>
    </div>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
})

const mobileMenuOpen = ref(false)
</script>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
