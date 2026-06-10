<template>
  <section id="carreras" class="py-20 lg:py-32 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-16">
        <h2 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-4">
          Nuestras <span class="text-blue-600">Carreras</span>
        </h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">
          Elige entre nuestras especialidades en Ingeniería y amplía tus oportunidades profesionales
        </p>
      </div>

      <!-- Grid de Carreras -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        <CarreraCard
          v-for="carrera in carreras"
          :key="carrera.sigla"
          :nombre="carrera.nombre"
          :descripcion="carrera.descripcion"
          :sigla="carrera.sigla"
          :color="carrera.color"
          :logo="carrera.logo"
          @open-modal="abrirModal(carrera)"
        />
      </div>

      <!-- CTA -->
      <div class="mt-16 max-w-xl mx-auto text-center">
        <transition name="fade" mode="out-in">
          <!-- Mensaje de éxito -->
          <div v-if="enviado" key="enviado" class="flex flex-col items-center gap-3 py-4">
            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <p class="text-lg font-semibold text-green-600">Mensaje Enviado</p>
          </div>

          <!-- Formulario de contacto -->
          <form v-else-if="mostrarFormulario" key="formulario" @submit.prevent="enviarMensaje" class="text-left bg-gray-50 border border-gray-200 rounded-xl p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
              <input
                v-model="form.correo"
                type="email"
                required
                placeholder="tu@correo.com"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Asunto</label>
              <input
                v-model="form.asunto"
                type="text"
                required
                placeholder="Asunto de tu mensaje"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
              <textarea
                v-model="form.descripcion"
                required
                rows="4"
                placeholder="Escribe tu mensaje..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none"
              ></textarea>
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex gap-3 justify-end">
              <button
                type="button"
                @click="cancelarFormulario"
                :disabled="enviando"
                class="px-6 py-2 rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 transition disabled:opacity-50"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="enviando"
                class="px-6 py-2 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50"
              >
                {{ enviando ? 'Enviando...' : 'Enviar' }}
              </button>
            </div>
          </form>

          <!-- Botón inicial -->
          <div v-else key="boton">
            <p class="text-gray-600 mb-4">¿Tienes dudas sobre alguna carrera?</p>
            <button
              @click="mostrarFormulario = true"
              class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition"
            >
              Contactar a Coordinadores
            </button>
          </div>
        </transition>
      </div>
    </div>

    <!-- Modal -->
    <CarreraModal
      :isOpen="modalAbierto"
      :carrera="carreraSeleccionada"
      :colorClass="colorClaseModal"
      @close="cerrarModal"
    />
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import CarreraCard from './CarreraCard.vue'
import CarreraModal from './CarreraModal.vue'

const modalAbierto = ref(false)
const carreraSeleccionada = ref({})

const mostrarFormulario = ref(false)
const enviando = ref(false)
const enviado = ref(false)
const error = ref('')

const form = ref({
  correo: '',
  asunto: '',
  descripcion: ''
})

const cancelarFormulario = () => {
  mostrarFormulario.value = false
  error.value = ''
  form.value = { correo: '', asunto: '', descripcion: '' }
}

const enviarMensaje = async () => {
  enviando.value = true
  error.value = ''
  try {
    await axios.post('/contactar-coordinadores', form.value)
    mostrarFormulario.value = false
    form.value = { correo: '', asunto: '', descripcion: '' }
    enviado.value = true
    setTimeout(() => {
      enviado.value = false
    }, 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo enviar el mensaje. Intente nuevamente.'
  } finally {
    enviando.value = false
  }
}

const colorClases = {
  'blue': 'bg-gradient-to-r from-blue-600 to-blue-700',
  'purple': 'bg-gradient-to-r from-purple-600 to-purple-700',
  'green': 'bg-gradient-to-r from-green-600 to-green-700',
  'red': 'bg-gradient-to-r from-red-600 to-red-700'
}

const colorClaseModal = computed(() => colorClases[carreraSeleccionada.value.color] || colorClases['blue'])

const carreras = [
  {
    nombre: 'Ingeniería Informática',
    descripcion: 'Desarrolla software, sistemas y aplicaciones',
    sigla: 'INF',
    logo: '/images/carreras/informatica.png',
    color: 'blue',
    mision: 'Formar profesionales en Ingeniería Informática competentes, innovadores y comprometidos con el desarrollo tecnológico del país, capaces de diseñar, desarrollar e implementar soluciones informáticos que contribuyan al progreso de la sociedad.',
    vision: 'Ser un programa académico de excelencia reconocido a nivel nacional e internacional por la calidad de su enseñanza, investigación y aporte al desarrollo tecnológico.',
    campoLaboral: 'Desarrollo de software, administración de sistemas, ingeniería de datos, ciberseguridad, desarrollo web, consultoría tecnológica, startups tecnológicas, instituciones financieras y gobierno.',
    director: 'MSc. Ing. José Junior Villagómez Melgar',
    directorTitulo: 'Magister en Sistemas de Información'
  },
  {
    nombre: 'Ingeniería en Sistemas',
    descripcion: 'Gestiona infraestructura tecnológica',
    sigla: 'SIS',
    logo: '/images/carreras/sistemas.png',
    color: 'purple',
    mision: 'Preparar profesionales capaces de analizar, diseñar e implementar soluciones integrales de sistemas, enfocados en la optimización de recursos y la transformación digital de las organizaciones.',
    vision: 'Liderar la formación de ingenieros en sistemas que contribuyan a la transformación digital empresarial y gubernamental del país.',
    campoLaboral: 'Gestión de infraestructura IT, administración de redes, cloud computing, análisis de sistemas, consultoría empresarial, banca y seguros, sector público.',
    director: 'MSc. Ing. Leonardo Vargas Peña',
    directorTitulo: 'Doctor en Ingeniería de Sistemas'
  },
  {
    nombre: 'Ingeniería en Redes y Telecomunicaciones',
    descripcion: 'Especialízate en conectividad global',
    sigla: 'RED',
    logo: '/images/carreras/redes.png',
    color: 'green',
    mision: 'Formar especialistas en redes y telecomunicaciones con capacidad de diseñar, implementar y mantener infraestructuras de comunicación confiables y seguras.',
    vision: 'Ser referente en la región en la formación de profesionales en redes y telecomunicaciones.',
    campoLaboral: 'Operadores de telecomunicaciones, diseño de redes, administración de infraestructura, seguridad de redes, consultoría en comunicaciones, instituciones educativas.',
    director: 'MSc. Ing. Jorge Rosales',
    directorTitulo: 'Ingeniero en Telecomunicaciones, Especialista en Redes'
  },
  {
    nombre: 'Robótica',
    descripcion: 'Diseña y controla sistemas automatizados',
    sigla: 'ROB',
    logo: '/images/carreras/robotica.png',
    color: 'red',
    mision: 'Capacitar profesionales en robótica e inteligencia artificial para el diseño y control de sistemas automatizados que mejoren procesos industriales y de investigación.',
    vision: 'Consolidarse como un programa líder en formación de expertos en robótica e inteligencia artificial.',
    campoLaboral: 'Automatización industrial, manufactura, robótica médica, investigación en IA, drones, sistemas de control automático, industria 4.0.',
    director: 'MSc. Ing. José Junior Villagómez Melgar',
    directorTitulo: 'Especialista en Robótica e Inteligencia Artificial'
  }
]

const abrirModal = (carrera) => {
  carreraSeleccionada.value = carrera
  modalAbierto.value = true
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden'
}

const cerrarModal = () => {
  modalAbierto.value = false
  // Restaurar scroll del body
  document.body.style.overflow = 'auto'
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
