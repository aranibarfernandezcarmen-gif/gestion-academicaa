<template>
  <transition name="modal">
    <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-96 overflow-y-auto">
        <!-- Header -->
        <div :class="['p-6 text-white', colorClass]">
          <div class="flex justify-between items-start">
            <div>
              <span class="inline-block bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-semibold mb-2">
                {{ carrera.sigla }}
              </span>
              <h2 class="text-3xl font-bold">{{ carrera.nombre }}</h2>
            </div>
            <button @click="closeModal" class="text-white hover:text-gray-200 transition">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6 space-y-6">
          <!-- Misión -->
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.243 3.03a1 1 0 01.727.427l.5.866a1 1 0 001.285.387l.766-.441a1 1 0 011.48.849l.096.863a1 1 0 001.061.947h.986a1 1 0 001.061-.947l.096-.863a1 1 0 011.48-.849l.766.441a1 1 0 001.285-.387l.5-.866a1 1 0 01.727-.427h1a1 1 0 01.707.293l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 00-.293.707v1a1 1 0 001.414 1.414l.707-.707a1 1 0 011.414 0l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 00-.293.707v1a1 1 0 001.414 1.414l.707-.707a1 1 0 011.414 0l.707.707a1 1 0 01-1.414 1.414l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 001.414 1.414l.707-.707a1 1 0 011.414 0l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 00-.293.707v1a1 1 0 00.293.707l.707.707a1 1 0 000-1.414l-.707-.707a1 1 0 01-.293-.707v-1a1 1 0 000-1.414l.707-.707a1 1 0 011.414 0l.707-.707a1 1 0 010-1.414l-.707-.707a1 1 0 000-1.414l.707-.707a1 1 0 000-1.414l-.707.707a1 1 0 01-1.414 0l-.707.707a1 1 0 00-1.414 0l-.707-.707a1 1 0 010-1.414l.707-.707a1 1 0 001.414 0l.707.707a1 1 0 001.414 0l.707-.707a1 1 0 10-1.414-1.414l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 10-1.414 1.414l.707.707a1 1 0 00.293.707v1a1 1 0 01-.293.707l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 001.414 0l.707-.707a1 1 0 010-1.414l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 010-1.414l.707-.707a1 1 0 001.414 0l.707.707a1 1 0 001.414 0l.707-.707a1 1 0 010-1.414l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 001.414 0l.707-.707a1 1 0 010-1.414l-.707.707a1 1 0 00-1.414 0l-.707-.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 001.414 0l.707.707a1 1 0 001.414 0l.707-.707a1 1 0 010-1.414l-.707-.707a1 1 0 01-.293-.707v-1a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-.707-.293h-1a1 1 0 00-.707.293l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-.707-.293h-1a1 1 0 00-.707.293l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-1.414 0l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-1.414 0l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 001.414 1.414l.707-.707a1 1 0 011.414 0l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 01-1.414 1.414l.707.707a1 1 0 000 1.414l.707.707a1 1 0 000 1.414l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-1.414 0l.707.707a1 1 0 010 1.414l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 01-1.414 1.414l.707.707a1 1 0 000 1.414l.707.707a1 1 0 000 1.414l-.707.707a1 1 0 01-1.414 0l-.707-.707a1 1 0 00-1.414 0l.707.707a1 1 0 010 1.414" clip-rule="evenodd"></path>
              </svg>
              Misión
            </h3>
            <p class="text-gray-700">{{ carrera.mision }}</p>
          </div>

          <!-- Visión -->
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L9 5.414V17a1 1 0 102 0V5.414l6.293 6.293a1 1 0 001.414-1.414l-7-7z"></path>
              </svg>
              Visión
            </h3>
            <p class="text-gray-700">{{ carrera.vision }}</p>
          </div>

          <!-- Campo Laboral -->
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-2 flex items-center">
              <svg class="w-5 h-5 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                <path d="M5 3a2 2 0 00-2 2v2H2a1 1 0 000 2h.586l1.707 7.707A2 2 0 009.414 17h1.172a2 2 0 001.897-1.293l1.707-7.707H18a1 1 0 000-2h-1V5a2 2 0 00-2-2H5zm3 4h4v10H8V7z"></path>
              </svg>
              Campo Laboral
            </h3>
            <p class="text-gray-700">{{ carrera.campoLaboral }}</p>
          </div>

          <!-- Director -->
          <div class="bg-blue-50 rounded-lg p-4">
            <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
              </svg>
              Director de Carrera
            </h3>
            <p class="font-semibold text-gray-900">{{ carrera.director }}</p>
            <p class="text-sm text-gray-600">{{ carrera.directorTitulo }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 border-t">
          <button @click="closeModal" class="px-6 py-2 bg-gray-200 text-gray-900 rounded-lg font-semibold hover:bg-gray-300 transition">
            Cerrar
          </button>
          <button class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition">
            <a href="/postularse">Postularse</a>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
defineProps({
  isOpen: Boolean,
  carrera: {
    type: Object,
    default: () => ({})
  },
  colorClass: String
})

const emit = defineEmits(['close'])

const closeModal = () => {
  emit('close')
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div, .modal-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-enter-from > div {
  transform: scale(0.9);
}

.modal-leave-to > div {
  transform: scale(0.9);
}
</style>
