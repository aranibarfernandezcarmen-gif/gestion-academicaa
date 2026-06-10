<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-purple-700 to-purple-900 text-white py-6 shadow-lg">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold">CU15 - Importación Masiva de Datos</h1>
            <p class="text-purple-200 mt-2">Importa postulantes, docentes y datos de forma masiva desde CSV/Excel</p>
          </div>
          <button @click="volver" type="button" class="px-4 py-2 bg-white text-purple-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 space-y-10">
      <!-- Sección de Carga -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Nueva Importación</h2>
        
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-sm text-blue-800">
            <strong>Formato esperado:</strong> CSV o Excel con encabezados. 
            <button @click="descargarPlantilla" class="text-blue-600 font-semibold hover:underline ml-2">
              Descargar plantilla
            </button>
          </p>
        </div>

        <form @submit.prevent="iniciarImportacion" class="space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Tipo de Datos *</label>
              <select v-model="formulario.tipo_datos" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
                <option value="">Seleccionar tipo...</option>
                <option value="Postulantes">Postulantes</option>
              </select>
              <p v-if="errors.tipo_datos" class="mt-1 text-sm text-red-600">{{ errors.tipo_datos }}</p>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Archivo *</label>
              <input 
                @change="onArchivoSeleccionado"
                type="file" 
                accept=".csv,.xlsx,.json"
                class="w-full px-4 py-3 border border-slate-300 rounded-xl" 
              />
              <p v-if="errors.archivo" class="mt-1 text-sm text-red-600">{{ errors.archivo }}</p>
              <p v-if="archivoSeleccionado" class="mt-2 text-sm text-green-600">✓ {{ archivoSeleccionado.name }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Descripción</label>
            <textarea v-model="formulario.descripcion" class="w-full px-4 py-3 border border-slate-300 rounded-xl" rows="3" placeholder="Notas sobre esta importación..."></textarea>
          </div>

          <div class="flex gap-3">
            <button type="submit" :disabled="cargando" class="px-6 py-3 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 transition disabled:opacity-50">
              {{ cargando ? 'Procesando...' : 'Iniciar Importación' }}
            </button>
            <button type="button" @click="limpiarFormulario" class="px-6 py-3 bg-slate-300 text-slate-900 font-semibold rounded-lg hover:bg-slate-400 transition">
              Limpiar
            </button>
          </div>
        </form>
      </div>

      <!-- Historial de Importaciones -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold">Historial de Importaciones</h2>
          <button @click="cargarHistorial" class="px-3 py-1 bg-slate-200 text-slate-900 rounded hover:bg-slate-300 text-sm">
            Actualizar
          </button>
        </div>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Tipo</label>
            <select v-model="filtrosHistorial.tipo_datos" @change="cargarHistorial" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
              <option value="">Todos</option>
              <option value="Postulantes">Postulantes</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Estado</label>
            <select v-model="filtrosHistorial.estado" @change="cargarHistorial" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
              <option value="">Todos</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Procesando">Procesando</option>
              <option value="Completado">Completado</option>
              <option value="Completado con errores">Con errores</option>
              <option value="Error fatal">Error fatal</option>
              <option value="Cancelado">Cancelado</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">ID</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Tipo</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Archivo</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Total</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Exitosos</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Fallidos</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Estado</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Fecha</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="importacion in historial" :key="importacion.id" class="border-b border-slate-200 hover:bg-slate-50">
                <td class="px-6 py-4 font-mono text-slate-900">#{{ importacion.id }}</td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-semibold">
                    {{ importacion.tipo_datos }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-600 truncate max-w-xs" :title="importacion.nombre_archivo">
                  {{ importacion.nombre_archivo }}
                </td>
                <td class="px-6 py-4 font-semibold text-slate-900">{{ importacion.total_registros }}</td>
                <td class="px-6 py-4 font-semibold text-green-600">{{ importacion.registros_exitosos }}</td>
                <td class="px-6 py-4 font-semibold text-red-600">{{ importacion.registros_fallidos }}</td>
                <td class="px-6 py-4">
                  <span :class="getEstadoBadge(importacion.estado)" class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ importacion.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-600 text-xs">{{ formatDate(importacion.created_at) }}</td>
                <td class="px-6 py-4 space-x-2 flex">
                  <button v-if="importacion.estado === 'Pendiente'" @click="procesarImportacion(importacion.id)" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                    Procesar
                  </button>
                  <button @click="verDetalles(importacion)" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                    Detalles
                  </button>
                  <button v-if="importacion.estado === 'Pendiente'" @click="cancelarImportacion(importacion.id)" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                    Cancelar
                  </button>
                </td>
              </tr>
              <tr v-if="historial.length === 0">
                <td colspan="9" class="px-6 py-8 text-center text-slate-500">No hay importaciones registradas</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal de Detalles -->
      <div v-if="mostrarModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
          <div class="sticky top-0 bg-slate-50 border-b border-slate-200 p-6 flex justify-between items-center">
            <h3 class="text-xl font-bold">Detalles de Importación</h3>
            <button @click="mostrarModal = false" class="text-2xl font-bold text-slate-500 hover:text-slate-700">&times;</button>
          </div>

          <div class="p-6 space-y-4">
            <div v-if="detallesImportacion" class="space-y-3">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-slate-600">ID</p>
                  <p class="font-semibold">{{ detallesImportacion.id }}</p>
                </div>
                <div>
                  <p class="text-sm text-slate-600">Tipo</p>
                  <p class="font-semibold">{{ detallesImportacion.tipo_datos }}</p>
                </div>
                <div>
                  <p class="text-sm text-slate-600">Total Registros</p>
                  <p class="font-semibold">{{ detallesImportacion.total_registros }}</p>
                </div>
                <div>
                  <p class="text-sm text-slate-600">Exitosos</p>
                  <p class="font-semibold text-green-600">{{ detallesImportacion.registros_exitosos }}</p>
                </div>
              </div>

              <div v-if="detallesImportacion.resumen" class="mt-6 p-4 bg-slate-50 rounded-lg">
                <p class="text-sm font-semibold text-slate-900 mb-2">Resumen:</p>
                <p v-for="(valor, clave) in detallesImportacion.resumen" :key="clave" class="text-sm text-slate-600">
                  {{ clave }}: {{ valor }}
                </p>
              </div>

              <div v-if="detallesImportacion.errores && detallesImportacion.errores.length > 0" class="mt-6 p-4 bg-red-50 rounded-lg">
                <p class="text-sm font-semibold text-red-900 mb-2">Errores:</p>
                <ul class="space-y-1">
                  <li v-for="(error, i) in detallesImportacion.errores.slice(0, 10)" :key="i" class="text-xs text-red-700">
                    • {{ error }}
                  </li>
                  <li v-if="detallesImportacion.errores.length > 10" class="text-xs text-red-600 italic">
                    ... y {{ detallesImportacion.errores.length - 10 }} errores más
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  registro: String,
  role: String,
})

const formulario = ref({
  tipo_datos: '',
  descripcion: '',
})

const filtrosHistorial = ref({
  tipo_datos: '',
  estado: '',
})

const archivoSeleccionado = ref(null)
const historial = ref([])
const mostrarModal = ref(false)
const detallesImportacion = ref(null)
const cargando = ref(false)
const errors = ref({})

onMounted(() => {
  cargarHistorial()
})

const onArchivoSeleccionado = (event) => {
  archivoSeleccionado.value = event.target.files[0]
}

const iniciarImportacion = async () => {
  if (!formulario.value.tipo_datos || !archivoSeleccionado.value) {
    errors.value.archivo = 'Selecciona tipo de datos y archivo'
    return
  }

  cargando.value = true
  const formData = new FormData()
  formData.append('archivo', archivoSeleccionado.value)
  formData.append('tipo_datos', formulario.value.tipo_datos)
  formData.append('descripcion', formulario.value.descripcion)

  try {
    // Obtener token CSRF
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      window.Laravel?.csrfToken ||
                      document.querySelector('input[name="_token"]')?.value

    const response = await fetch('/api/cu15/importacion', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
      },
      body: formData,
    })
    const data = await response.json()
    if (response.ok) {
      alert('Importación iniciada exitosamente')
      limpiarFormulario()
      cargarHistorial()
    } else {
      errors.value = data.errors || {}
      alert('Error: ' + (data.error || JSON.stringify(data.errors)))
    }
  } catch (error) {
    console.error('Error iniciando importación:', error)
    alert('Error al importar: ' + error.message)
  } finally {
    cargando.value = false
  }
}

const cargarHistorial = async () => {
  try {
    const params = new URLSearchParams(filtrosHistorial.value)
    const response = await fetch(`/api/cu15/historial?${params}`)
    const data = await response.json()
    historial.value = data.data || []
  } catch (error) {
    console.error('Error cargando historial:', error)
  }
}

const procesarImportacion = async (id) => {
  if (!confirm('¿Procesar esta importación?')) return

  try {
    const response = await fetch(`/api/cu15/importacion/${id}/procesar`, {
      method: 'POST',
    })
    if (response.ok) {
      alert('Importación procesada')
      cargarHistorial()
    }
  } catch (error) {
    console.error('Error procesando importación:', error)
  }
}

const cancelarImportacion = async (id) => {
  if (!confirm('¿Cancelar esta importación?')) return

  try {
    const response = await fetch(`/api/cu15/importacion/${id}/cancelar`, {
      method: 'POST',
    })
    if (response.ok) {
      alert('Importación cancelada')
      cargarHistorial()
    }
  } catch (error) {
    console.error('Error cancelando importación:', error)
  }
}

const verDetalles = (importacion) => {
  detallesImportacion.value = importacion
  mostrarModal.value = true
}

const descargarPlantilla = async () => {
  if (!formulario.value.tipo_datos) {
    alert('Selecciona un tipo de datos primero')
    return
  }
  window.location.href = `/api/cu15/plantilla?tipo=${formulario.value.tipo_datos}`
}

const limpiarFormulario = () => {
  formulario.value = { tipo_datos: '', descripcion: '' }
  archivoSeleccionado.value = null
  errors.value = {}
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('es-BO', {
    timeZone: 'America/La_Paz',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
}

const getEstadoBadge = (estado) => {
  const colors = {
    'Pendiente': 'bg-yellow-100 text-yellow-800',
    'Procesando': 'bg-blue-100 text-blue-800',
    'Completado': 'bg-green-100 text-green-800',
    'Completado con errores': 'bg-orange-100 text-orange-800',
    'Error fatal': 'bg-red-100 text-red-800',
    'Cancelado': 'bg-slate-100 text-slate-800',
  }
  return colors[estado] || 'bg-slate-100 text-slate-800'
}

const volver = () => {
  window.history.back();
};
</script>
