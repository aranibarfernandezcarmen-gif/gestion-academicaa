<template>
  <section id="proceso" class="py-20 lg:py-32 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">
          Proceso de <span class="text-blue-600">Admisión</span>
        </h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Sigue estos pasos para completar tu postulación
        </p>
      </div>

      <!-- Timeline -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <div v-for="(step, index) in pasos" :key="index" class="relative">
          <!-- Conectores -->
          <div v-if="index < 3" class="hidden lg:block absolute top-12 -right-16 w-16 h-1 bg-gradient-to-r from-blue-400 to-transparent"></div>

          <!-- Card -->
          <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 text-center hover:shadow-lg transition">
            <!-- Número -->
            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">
              {{ index + 1 }}
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ step.titulo }}</h3>
            <p class="text-gray-700 text-sm leading-relaxed">{{ step.descripcion }}</p>
          </div>
        </div>
      </div>

      <!-- Requisitos -->
      <div class="mt-16 bg-gray-50 rounded-xl p-8">
        <h3 class="text-2xl font-bold text-gray-900 mb-6">Documentos Requeridos</h3>
        <div class="grid md:grid-cols-2 gap-6">
          <div v-for="doc in documentos" :key="doc" class="flex items-start">
            <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-gray-700">{{ doc }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
const pasos = [
  {
    titulo: 'Crear Cuenta',
    descripcion: 'Regístrate con tus datos personales'
  },
  {
    titulo: 'Seleccionar Carrera',
    descripcion: 'Elige tu carrera preferida'
  },
  {
    titulo: 'Subir Documentos',
    descripcion: 'Adjunta los archivos requeridos'
  },
  {
    titulo: 'Confirmar Postulación',
    descripcion: 'Revisa y envía tu solicitud'
  }
]

const documentos = [
  'Cédula de Identidad',
  'Certificado de Educación Secundaria',
  'Título de Bachiller',
  'Comprobante de Pago',
  'Foto 4x4',
  'Otros requisitos especiales'
]
</script>
