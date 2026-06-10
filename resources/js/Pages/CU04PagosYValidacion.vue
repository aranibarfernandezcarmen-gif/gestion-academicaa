<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-green-700 to-green-900 text-white py-6 shadow-lg">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold">CU04 - Seguimiento de Pagos y Validación</h1>
            <p class="text-green-200 mt-2">Gestiona y valida pagos de postulantes de forma segura.</p>
          </div>
          <button @click="volver" type="button" class="px-4 py-2 bg-white text-green-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 space-y-10">
      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
          <p class="text-sm text-slate-600">Total de Pagos</p>
          <p class="text-3xl font-bold text-green-600">{{ estadisticas.total_pagos }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
          <p class="text-sm text-slate-600">Monto Total</p>
          <p class="text-3xl font-bold text-blue-600">Bs {{ parseFloat(estadisticas.monto_total || 0).toFixed(2) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-400">
          <p class="text-sm text-slate-600">Completados</p>
          <p class="text-3xl font-bold text-green-400">{{ estadisticas.pagos_completados }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
          <p class="text-sm text-slate-600">Pendientes</p>
          <p class="text-3xl font-bold text-yellow-600">{{ estadisticas.pagos_pendientes }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-red-500">
          <p class="text-sm text-slate-600">Rechazados</p>
          <p class="text-3xl font-bold text-red-600">{{ estadisticas.pagos_rechazados }}</p>
        </div>
      </div>

      <!-- Formulario Nuevo Pago -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Registrar Nuevo Pago</h2>
        <form @submit.prevent="guardarPago" class="grid gap-6 lg:grid-cols-3">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Postulante *</label>
            <select v-model="nuevoPago.id_postulante" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
              <option value="">Seleccionar postulante...</option>
              <option v-for="p in postulantes" :key="p.id" :value="p.id">
                {{ p.nombre }} {{ p.apellido }} ({{ p.registro }})
              </option>
            </select>
            <p v-if="errors.id_postulante" class="mt-1 text-sm text-red-600">{{ errors.id_postulante }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Monto (Bs) *</label>
            <input v-model.number="nuevoPago.monto" type="number" step="0.01" min="0.01" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.monto" class="mt-1 text-sm text-red-600">{{ errors.monto }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Fecha de Pago *</label>
            <input v-model="nuevoPago.fecha_pago" type="date" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.fecha_pago" class="mt-1 text-sm text-red-600">{{ errors.fecha_pago }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Hora de Pago (Bolivia)</label>
            <input v-model="horaActualBolivia" type="text" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Comprobante *</label>
            <input v-model="nuevoPago.comprobante" type="text" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50" placeholder="Auto-generado" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Método de Pago *</label>
            <input type="text" value="Efectivo" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50 font-semibold text-green-700" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Número de Transacción</label>
            <input v-model="nuevoPago.numero_transaccion" type="text" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-50" placeholder="Auto-generado" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Correo Electrónico del Postulante</label>
            <input v-model="nuevoPago.correo_electronico" type="email" class="w-full px-4 py-3 border border-slate-300 rounded-xl" placeholder="correo@ejemplo.com" readonly />
            <p v-if="errors.correo_electronico" class="mt-1 text-sm text-red-600">{{ errors.correo_electronico }}</p>
          </div>

          <div class="lg:col-span-3">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Descripción</label>
            <textarea v-model="nuevoPago.descripcion" class="w-full px-4 py-3 border border-slate-300 rounded-xl" rows="3" placeholder="Notas adicionales..."></textarea>
          </div>

          <div class="lg:col-span-3 flex gap-3">
            <button type="submit" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition">
              Guardar Pago
            </button>
            <button type="button" @click="limpiarFormulario" class="px-6 py-3 bg-slate-300 text-slate-900 font-semibold rounded-lg hover:bg-slate-400 transition">
              Limpiar
            </button>
          </div>
        </form>
      </div>

      <!-- Filtros y Búsqueda -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Filtrar Pagos</h2>
        <div class="grid gap-4 lg:grid-cols-5">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Estado</label>
            <select v-model="filtros.estado" @change="cargarPagos" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
              <option value="">Todos los estados</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Procesando">Procesando</option>
              <option value="Completado">Completado</option>
              <option value="Rechazado">Rechazado</option>
              <option value="Cancelado">Cancelado</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Postulante</label>
            <select v-model="filtros.id_postulante" @change="cargarPagos" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
              <option value="">Todos los postulantes</option>
              <option v-for="p in postulantes" :key="p.id" :value="p.id">
                {{ p.nombre }} {{ p.apellido }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Desde</label>
            <input v-model="filtros.fecha_inicio" @change="cargarPagos" type="date" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Hasta</label>
            <input v-model="filtros.fecha_fin" @change="cargarPagos" type="date" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
          </div>

          <div class="flex items-end">
            <button @click="descargarReporte" class="w-full px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
              Descargar Reporte
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de Pagos -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="p-8 border-b border-slate-200">
          <h2 class="text-2xl font-bold">Lista de Pagos</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
              <tr>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">ID</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Postulante</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">CI</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Monto</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Fecha</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Hora</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Estado</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Método</th>
                <th class="px-6 py-4 text-left font-semibold text-slate-900">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="pago in pagos" :key="pago.id" class="border-b border-slate-200 hover:bg-slate-50">
                <td class="px-6 py-4 font-mono text-slate-900">#{{ pago.id }}</td>
                <td class="px-6 py-4 text-slate-900">{{ pago.nombre }} {{ pago.apellido }}</td>
                <td class="px-6 py-4 font-mono text-slate-900">{{ pago.ci }}</td>
                <td class="px-6 py-4 font-semibold text-green-600">Bs {{ parseFloat(pago.monto || 0).toFixed(2) }}</td>
                <td class="px-6 py-4 text-slate-600">{{ formatDate(pago.fecha_pago) }}</td>
                <td class="px-6 py-4 text-slate-600 font-mono text-xs">{{ pago.hora_pago ? pago.hora_pago.slice(0,5) : '—' }}</td>
                <td class="px-6 py-4">
                  <span :class="getEstadoBadge(pago.estado)" class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ pago.estado }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-600">{{ pago.metodo_pago }}</td>
                <td class="px-6 py-4 space-x-2 flex">
                  <button @click="editarPago(pago)" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                    Editar
                  </button>
                  <button v-if="pago.estado === 'Pendiente'" @click="validarPago(pago.id, true)" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                    Validar
                  </button>
                  <button v-if="pago.estado !== 'Rechazado'" @click="validarPago(pago.id, false)" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                    Rechazar
                  </button>
                </td>
              </tr>
              <tr v-if="pagos.length === 0">
                <td colspan="9" class="px-6 py-8 text-center text-slate-500">No hay pagos registrados</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'

const props = defineProps({
  postulantes: Array,
  registro: String,
  role: String,
})

const pagos = ref([])
const estadisticas = ref({
  total_pagos: 0,
  monto_total: 0,
  pagos_completados: 0,
  pagos_pendientes: 0,
  pagos_rechazados: 0,
  promedio_pago: 0,
})

const generarComprobante = () => {
  return 'COMP-' + Math.random().toString(36).substring(2, 10).toUpperCase()
}

const generarNumeroTransaccion = () => {
  return 'TRX-' + Math.random().toString(36).substring(2, 12).toUpperCase()
}

const fechaBolivia = () => new Date().toLocaleDateString('en-CA', { timeZone: 'America/La_Paz' })

const nuevoPago = ref({
  id_postulante: '',
  monto: '',
  fecha_pago: fechaBolivia(),
  hora_pago: '09:00',
  comprobante: generarComprobante(),
  metodo_pago: 'Efectivo',
  numero_transaccion: generarNumeroTransaccion(),
  descripcion: '',
  correo_electronico: '',
})

const horaActualBolivia = ref('')

const filtros = ref({
  estado: '',
  id_postulante: '',
  fecha_inicio: '',
  fecha_fin: '',
  metodo_pago: '',
})

const errors = ref({})

onMounted(() => {
  cargarPagos()
  cargarEstadisticas()
  actualizarHoraBolivia()
  setInterval(actualizarHoraBolivia, 1000)
})

const cargarEstadisticas = async () => {
  try {
    const response = await fetch('/api/cu04/estadisticas')
    if (response.ok) {
      estadisticas.value = await response.json()
    }
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

const cargarPagos = async () => {
  try {
    const response = await fetch('/api/cu04/pagos?' + new URLSearchParams(filtros.value))
    const data = await response.json()
    pagos.value = data.pagos
    estadisticas.value = data.estadisticas
  } catch (error) {
    console.error('Error cargando pagos:', error)
  }
}

const guardarPago = async () => {
  try {
    const payload = {
      ...nuevoPago.value,
      id_postulante: nuevoPago.value.id_postulante ? parseInt(nuevoPago.value.id_postulante) : null,
      hora_pago: obtenerHoraBolivia(),
    }
    console.log('Payload a enviar:', payload)
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    const response = await fetch('/api/cu04/pagos', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify(payload),
    })
    const data = await response.json()
    if (response.ok) {
      let mensaje = 'Pago registrado exitosamente. Credenciales enviadas al correo del postulante.'
      
      // Si hay credenciales nuevas generadas, mostrarlas
      if (data.credenciales) {
        const cred = data.credenciales
        mensaje = `✅ PAGO REGISTRADO EXITOSAMENTE\n\n⚠️ CREDENCIALES GENERADAS:\n\nCódigo: ${cred.registro}\nContraseña: ${cred.password}\nCorreo: ${cred.email}\nURL: ${cred.url_acceso}\n\n✉️ Se han enviado también al correo del postulante.`
      }
      
      alert(mensaje)
      window.location.reload()
    } else {
      const errorMsg = data.errors ? JSON.stringify(data.errors) : data.error || 'No se pudo registrar el pago';
      alert('Error: ' + errorMsg)
    }
  } catch (error) {
    console.error('Error guardando pago:', error)
    alert('Error guardando pago: ' + error.message)
  }
}

const validarPago = async (id, validar) => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    const response = await fetch(`/api/cu04/pagos/${id}/validar`, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
      },
      body: JSON.stringify({ validar }),
    })
    if (response.ok) {
      alert(validar ? 'Pago validado' : 'Pago rechazado')
      cargarPagos()
    }
  } catch (error) {
    console.error('Error validando pago:', error)
  }
}

const descargarReporte = async () => {
  const params = new URLSearchParams(filtros.value)
  window.location.href = `/api/cu04/pagos/reporte?${params}`
}

const limpiarFormulario = () => {
  nuevoPago.value = {
    id_postulante: '',
    monto: '',
    fecha_pago: fechaBolivia(),
    hora_pago: '09:00',
    comprobante: generarComprobante(),
    metodo_pago: 'Efectivo',
    numero_transaccion: generarNumeroTransaccion(),
    descripcion: '',
    correo_electronico: '',
  }
  errors.value = {}
}

const formatDate = (date) => {
  if (!date) return '—'
  const parte = date.toString().split('T')[0]
  const [y, m, d] = parte.split('-')
  return `${d}/${m}/${y}`
}

const formatDateTime = (date, time) => {
  if (!date || !time) return 'N/A'
  return new Date(`${date}T${time}`).toLocaleString('es-BO')
}

const getEstadoBadge = (estado) => {
  const colors = {
    'Pendiente': 'bg-yellow-100 text-yellow-800',
    'Procesando': 'bg-blue-100 text-blue-800',
    'Completado': 'bg-green-100 text-green-800',
    'Rechazado': 'bg-red-100 text-red-800',
    'Cancelado': 'bg-slate-100 text-slate-800',
  }
  return colors[estado] || 'bg-slate-100 text-slate-800'
}

const volver = () => {
  window.history.back();
};

const editarPago = (pago) => {
  alert('Edición de pago: ' + pago.id)
}

const obtenerHoraBolivia = () => {
  const now = new Date()
  // Bolivia es UTC-4
  const boliviaTime = new Date(now.toLocaleString('en-US', { timeZone: 'America/La_Paz' }))
  const horas = String(boliviaTime.getHours()).padStart(2, '0')
  const minutos = String(boliviaTime.getMinutes()).padStart(2, '0')
  const segundos = String(boliviaTime.getSeconds()).padStart(2, '0')
  return `${horas}:${minutos}:${segundos}`
}

const actualizarHoraBolivia = () => {
  const now = new Date()
  const boliviaTime = new Date(now.toLocaleString('en-US', { timeZone: 'America/La_Paz' }))
  const horas = String(boliviaTime.getHours()).padStart(2, '0')
  const minutos = String(boliviaTime.getMinutes()).padStart(2, '0')
  const segundos = String(boliviaTime.getSeconds()).padStart(2, '0')
  horaActualBolivia.value = `${horas}:${minutos}:${segundos}`
}

watch(() => nuevoPago.value.id_postulante, (newPostulanteId) => {
  if (newPostulanteId) {
    const postulante = props.postulantes.find(p => p.id == newPostulanteId)
    if (postulante) {
      fetch(`/api/cu04/postulante/${newPostulanteId}/correo`)
        .then(r => r.json())
        .then(data => {
          if (data.correo_electronico) {
            nuevoPago.value.correo_electronico = data.correo_electronico
          }
        })
        .catch(err => console.error('Error cargando correo:', err))
    }
  }
})
</script>
