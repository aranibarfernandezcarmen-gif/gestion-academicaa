<template>
  <div class="min-h-screen bg-slate-100 flex flex-col">
    <div class="bg-gradient-to-r from-purple-700 to-purple-900 text-white py-10 shadow-lg sticky top-0 z-20">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex-1">
            <h1 class="text-2xl sm:text-3xl font-bold leading-tight">CU20 - Bitácora de Operaciones</h1>
            <p class="mt-2 text-slate-200 text-sm sm:text-base">Registro detallado de todas las operaciones del sistema</p>
          </div>
          <button @click="volver" type="button" class="w-full sm:w-auto px-6 py-3 bg-white text-purple-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Cerrar Sesión
          </button>
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto">
      <div class="max-w-7xl mx-auto px-4 py-12">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8 mb-8 sticky top-[100px] z-10">
          <h2 class="text-2xl font-bold mb-6">Filtros</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Usuario</label>
              <select v-model="filtros.usuario_id" @change="aplicarFiltros" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:outline-none">
                <option value="">-- Todos --</option>
                <option v-for="usuario in usuarios" :key="usuario.id" :value="usuario.id">
                  {{ usuario.nombre }} {{ usuario.apellido }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Acción</label>
              <input
                v-model="filtros.accion"
                @keyup.enter="aplicarFiltros"
                type="text"
                placeholder="Ej: Iniciar sesión, Crear..."
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Desde</label>
              <input
                v-model="filtros.fecha_desde"
                @change="aplicarFiltros"
                type="date"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:outline-none text-sm"
              />
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Hasta</label>
              <input
                v-model="filtros.fecha_hasta"
                @change="aplicarFiltros"
                type="date"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-purple-500 focus:outline-none text-sm"
              />
            </div>
          </div>

          <div class="flex gap-4">
            <button @click="limpiarFiltros" type="button" class="inline-flex items-center justify-center rounded-full bg-slate-400 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-500">
              Limpiar Filtros
            </button>
          </div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-bold">Registro de Operaciones</h2>
            <p class="text-slate-600">Total: {{ bitacora.total }} registros</p>
          </div>

          <div v-if="bitacora.data.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-600 text-center">
            No hay registros en la bitácora.
          </div>

          <div v-else ref="logTable" class="overflow-x-auto overflow-y-auto max-h-[550px] border border-slate-200 rounded-2xl">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-slate-50 sticky top-0 z-10">
                <tr>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Código</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Persona</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Rol</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Acción</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Hora</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">IP Origen</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="registro in bitacora.data" :key="registro.codigo" class="hover:bg-slate-50">
                  <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ registro.codigo }}</td>
                  <td class="px-4 py-3 text-sm text-slate-700">
                    <span v-if="registro.nombre">{{ registro.nombre }} {{ registro.apellido }}</span>
                    <span v-else class="text-slate-500">Sistema</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-slate-700 uppercase">{{ registro.rol }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span :class="getAccionBadge(registro.accion)">
                      {{ registro.accion }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-slate-700">{{ formatearFecha(registro.fecha_hora) }}</td>
                  <td class="px-4 py-3 text-sm text-slate-700">{{ formatearHora(registro.fecha_hora) }}</td>
                  <td class="px-4 py-3 text-sm text-slate-700">{{ registro.ip_origen || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="bitacora.data.length > 0" class="mt-16 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="text-sm text-slate-600">
              Mostrando {{ bitacora.from }} a {{ bitacora.to }} de {{ bitacora.total }} registros
            </div>
            <div class="flex flex-wrap gap-2">
              <button
                v-if="bitacora.current_page > 1"
                @click="irAPagina(bitacora.current_page - 1)"
                class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50"
              >
                Anterior
              </button>
              <button
                v-for="page in generarPaginas()"
                :key="page"
                @click="irAPagina(page)"
                :class="[
                  'px-3 py-2 rounded-lg font-semibold',
                  page === bitacora.current_page
                    ? 'bg-purple-600 text-white'
                    : 'border border-slate-300 hover:bg-slate-50'
                ]"
              >
                {{ page }}
              </button>
              <button
                v-if="bitacora.current_page < bitacora.last_page"
                @click="irAPagina(bitacora.current_page + 1)"
                class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50"
              >
                Siguiente
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onBeforeUnmount, onMounted } from 'vue';

const props = defineProps({
  bitacora: Object,
  usuarios: Array,
  filtros: Object,
});

const filtros = reactive({
  usuario_id: props.filtros?.usuario_id || '',
  accion: props.filtros?.accion || '',
  fecha_desde: props.filtros?.fecha_desde || '',
  fecha_hasta: props.filtros?.fecha_hasta || '',
});

const bitacora = reactive({
  data: props.bitacora.data || [],
  current_page: props.bitacora.current_page || 1,
  last_page: props.bitacora.last_page || 1,
  from: props.bitacora.from || 0,
  to: props.bitacora.to || 0,
  total: props.bitacora.total || 0,
});

const logTable = ref(null);
let refreshTimer = null;

const buildStreamUrl = (page = bitacora.current_page) => {
  const params = new URLSearchParams();
  if (filtros.usuario_id) params.append('usuario_id', filtros.usuario_id);
  if (filtros.accion) params.append('accion', filtros.accion);
  if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
  if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);
  params.append('page', page);
  return `/cu20/auditoria/stream?${params.toString()}`;
};

const refreshBitacora = async () => {
  try {
    const response = await fetch(buildStreamUrl(bitacora.current_page), {
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      return;
    }

    const data = await response.json();
    Object.assign(bitacora, data);
  } catch (error) {
    console.error('Error actualizando la bitácora:', error);
  }
};

const scrollToBottom = () => {
  if (logTable.value) {
    logTable.value.scrollTop = logTable.value.scrollHeight;
  }
};

const aplicarFiltros = () => {
  const params = new URLSearchParams();
  if (filtros.usuario_id) params.append('usuario_id', filtros.usuario_id);
  if (filtros.accion) params.append('accion', filtros.accion);
  if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
  if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

  window.location.href = `/cu20/auditoria?${params.toString()}`;
};

const limpiarFiltros = () => {
  filtros.usuario_id = '';
  filtros.accion = '';
  filtros.fecha_desde = '';
  filtros.fecha_hasta = '';
  window.location.href = '/cu20/auditoria';
};

const irAPagina = (page) => {
  const params = new URLSearchParams();
  params.append('page', page);
  if (filtros.usuario_id) params.append('usuario_id', filtros.usuario_id);
  if (filtros.accion) params.append('accion', filtros.accion);
  if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
  if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);

  window.location.href = `/cu20/auditoria?${params.toString()}`;
};

const generarPaginas = () => {
  const paginas = [];
  const inicio = Math.max(1, bitacora.current_page - 2);
  const fin = Math.min(bitacora.last_page, bitacora.current_page + 2);

  for (let i = inicio; i <= fin; i++) {
    paginas.push(i);
  }

  return paginas;
};

const formatearFecha = (fechaHora) => {
  const fecha = new Date(fechaHora);
  return fecha.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
};

const formatearHora = (fechaHora) => {
  const fecha = new Date(fechaHora);
  return fecha.toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

const getAccionBadge = (accion) => {
  const accionLower = (accion || '').toLowerCase();
  
  if (accionLower.includes('login') || accionLower.includes('ingreso')) {
    return 'bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold';
  }
  if (accionLower.includes('logout') || accionLower.includes('salida')) {
    return 'bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-xs font-semibold';
  }
  if (accionLower.includes('crear') || accionLower.includes('insertar')) {
    return 'bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold';
  }
  if (accionLower.includes('editar') || accionLower.includes('actualizar')) {
    return 'bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold';
  }
  if (accionLower.includes('eliminar') || accionLower.includes('borrar')) {
    return 'bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold';
  }
  
  return 'bg-slate-100 text-slate-800 px-3 py-1 rounded-full text-xs font-semibold';
};

const volver = () => {
  window.history.back();
};

onMounted(() => {
  refreshBitacora();
  refreshTimer = setInterval(refreshBitacora, 1000);
});

onBeforeUnmount(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
  }
});
</script>

