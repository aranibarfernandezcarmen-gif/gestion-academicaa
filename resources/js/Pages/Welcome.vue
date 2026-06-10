<template>
  <div class="bg-white">
    <!-- Navbar -->
    <Navbar :canLogin="canLogin" :canRegister="canRegister" />

    <!-- Hero Section -->
    <HeroSection />

    <!-- Carreras Section -->
    <CarrerasSection />

    <!-- Proceso Section -->
    <ProcesoSection />

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-blue-700 to-blue-900 text-white">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl font-bold mb-6">¿Listo para comenzar tu viaje?</h2>
        <p class="text-xl text-blue-100 mb-8">
          Postúlate ahora y sé parte de la comunidad FICCT
        </p>
        <button class="px-8 py-4 bg-yellow-400 text-blue-900 rounded-lg font-bold hover:bg-yellow-300 transition transform hover:scale-105 text-lg">
          <Link href="/postularse">Postulate</Link>
        </button>
      </div>
    </section>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Navbar.vue'
import HeroSection from '@/Components/HeroSection.vue'
import CarrerasSection from '@/Components/CarrerasSection.vue'
import ProcesoSection from '@/Components/ProcesoSection.vue'
import Footer from '@/Components/Footer.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  canLogin: Boolean,
  canRegister: Boolean,
  laravelVersion: String,
  phpVersion: String,
})
</script>

<style scoped>
html {
  scroll-behavior: smooth;
}
</style>
