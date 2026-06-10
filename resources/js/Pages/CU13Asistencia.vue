<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-orange-600 to-orange-800 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU13 - Gestión de Asistencia</h1>
          <p class="mt-1 text-orange-200">Registra y consulta la asistencia de postulantes por sesión.</p>
        </div>
        <div class="flex items-center gap-5">
          <!-- Reloj Bolivia -->
          <div class="text-right">
            <p class="text-orange-300 text-xs font-semibold uppercase tracking-wide">Hora Bolivia (UTC-4)</p>
            <p class="text-white text-xl font-bold font-mono leading-tight">{{ horaBolivia }}</p>
            <p class="text-orange-200 text-xs font-mono">{{ fechaHoyBolivia }}</p>
          </div>
          <button @click="volver"
            class="px-4 py-2 bg-white text-orange-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Formulario de registro -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Registrar Asistencia</h2>

        <!-- Fila 1: Fecha/Hora + Grupo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha · Hora Bolivia</label>
            <div class="flex items-center gap-3 border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5">
              <span class="font-semibold text-slate-800">{{ fechaHoyBolivia }}</span>
              <span class="text-slate-300">|</span>
              <span class="font-mono font-bold text-orange-600 text-base">{{ horaBolivia }}</span>
              <span class="ml-auto text-xs text-slate-400 font-mono">UTC-4</span>
            </div>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Grupo</label>
            <select v-model="grupoSeleccionado"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-orange-500">
              <option :value="null">-- Seleccionar grupo --</option>
              <option v-for="g in grupos" :key="g.codigo" :value="g.codigo">
                {{ g.nombre_grupo }} · {{ g.nombre_materia }}
              </option>
            </select>
          </div>
        </div>

        <!-- Fila 2: Docente (readonly) + Postulante + Estado -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Docente</label>
            <input :value="docenteNombre || '—'"
              readonly disabled
              class="w-full border border-slate-200 bg-slate-50 rounded-xl px-4 py-2.5 text-slate-600 font-semibold cursor-not-allowed" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Postulante</label>
            <select v-model="form.registro_postulante"
              :disabled="!grupoSeleccionado"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-orange-500 disabled:bg-slate-50 disabled:text-slate-400 disabled:cursor-not-allowed">
              <option :value="null">-- Seleccionar postulante --</option>
              <option v-for="p in postulantesFiltrados" :key="p.id" :value="p.id">
                {{ p.registro }} - {{ p.nombre_completo }}
              </option>
            </select>
            <p v-if="grupoSeleccionado && postulantesFiltrados.length === 0"
              class="text-xs text-amber-600 mt-1 font-semibold">
              Todos los postulantes ya tienen asistencia registrada hoy.
            </p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
            <select v-model="form.estado"
              :disabled="!grupoSeleccionado"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-orange-500 disabled:bg-slate-50 disabled:cursor-not-allowed">
              <option value="Presente">Presente</option>
              <option value="Ausente">Ausente</option>
              <option value="Justificado">Justificado</option>
            </select>
          </div>
        </div>

        <button @click="registrarAsistencia"
          :disabled="guardando || !form.registro_postulante || !form.codigo_docente"
          class="px-6 py-2.5 bg-orange-600 text-white rounded-xl font-semibold text-sm hover:bg-orange-700 disabled:opacity-50 transition">
          {{ guardando ? 'Registrando...' : 'Registrar Asistencia' }}
        </button>
      </section>

      <!-- Historial de asistencias -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">
          Historial de Asistencia
          <span class="text-base font-normal text-slate-500">({{ asistenciasLocal.length }} registros)</span>
        </h2>

        <div v-if="asistenciasLocal.length === 0" class="text-center py-10 text-slate-400">
          No hay registros de asistencia.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Docente</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Registro</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Postulante</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Estado</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="a in asistenciasLocal" :key="a.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ formatFecha(a.fecha) }}</td>
                <td class="px-4 py-3 text-slate-700">{{ a.docente_nombre }}</td>
                <td class="px-4 py-3 font-mono text-xs text-blue-700 font-semibold">{{ a.registro }}</td>
                <td class="px-4 py-3 text-slate-700">{{ a.postulante_nombre }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="estadoBadge(a.estado)"
                    class="px-2 py-1 rounded-full text-xs font-semibold">{{ a.estado }}</span>
                </td>
                <td class="px-4 py-3">
                  <button @click="eliminar(a.codigo)"
                    class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition">
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  grupos:      Array,
  asistencias: Array,
  postulantes: Array,
});

const asistenciasLocal  = ref([...props.asistencias]);
const grupoSeleccionado = ref(null);
const docenteNombre     = ref('');
const guardando         = ref(false);

// --- Hora y fecha Bolivia ---
const getBoliviaDate = () =>
  new Date().toLocaleDateString('en-CA', { timeZone: 'America/La_Paz' });

const fechaHoyBolivia = ref(getBoliviaDate());
const horaBolivia     = ref('');

const form = reactive({
  fecha:               getBoliviaDate(),
  codigo_docente:      null,
  registro_postulante: null,
  estado:              'Presente',
});

let intervalo = null;

onMounted(() => {
  const tick = () => {
    const now = new Date();
    horaBolivia.value = now.toLocaleTimeString('es-BO', {
      timeZone: 'America/La_Paz',
      hour:     '2-digit',
      minute:   '2-digit',
      second:   '2-digit',
      hour12:   false,
    });
    // Si cambia la fecha (medianoche Bolivia) se reactualiza y el computed se limpia solo
    const nuevaFecha = getBoliviaDate();
    if (nuevaFecha !== fechaHoyBolivia.value) {
      fechaHoyBolivia.value = nuevaFecha;
      form.fecha = nuevaFecha;
    }
  };
  tick();
  intervalo = setInterval(tick, 1000);
});

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo);
});

// --- Cascade al seleccionar grupo ---
watch(grupoSeleccionado, (val) => {
  form.registro_postulante = null;
  if (val) {
    const g = props.grupos.find(g => g.codigo === val);
    form.codigo_docente = g?.codigo_docente ?? null;
    docenteNombre.value = g?.docente_nombre ?? 'Sin docente asignado';
  } else {
    form.codigo_docente = null;
    docenteNombre.value = '';
  }
});

// Postulantes que ya tienen asistencia HOY para el docente del grupo seleccionado
const yaRegistradosHoy = computed(() => {
  if (!grupoSeleccionado.value) return new Set();
  const g = props.grupos.find(g => g.codigo === grupoSeleccionado.value);
  if (!g || !g.codigo_docente) return new Set();
  return new Set(
    asistenciasLocal.value
      .filter(a => {
        const fechaA = (a.fecha || '').split('T')[0];
        return fechaA === fechaHoyBolivia.value && a.codigo_docente === g.codigo_docente;
      })
      .map(a => a.registro_postulante)
  );
});

// Postulantes del grupo, sin los ya registrados hoy
const postulantesFiltrados = computed(() => {
  if (!grupoSeleccionado.value) return [];
  return props.postulantes.filter(p =>
    p.grupo_codigo === grupoSeleccionado.value &&
    !yaRegistradosHoy.value.has(p.id)
  );
});

// --- Helpers de UI ---
const estadoBadge = (estado) => {
  if (estado === 'Presente')    return 'bg-green-100 text-green-700';
  if (estado === 'Ausente')     return 'bg-red-100 text-red-700';
  if (estado === 'Justificado') return 'bg-yellow-100 text-yellow-700';
  return 'bg-slate-100 text-slate-600';
};

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const [y, m, d] = (fecha || '').split('T')[0].split('-');
  return `${d}/${m}/${y}`;
};

const volver = () => {
  const params = new URLSearchParams(window.location.search);
  const registro = params.get('registro');
  const role     = params.get('role');
  if (registro && role) {
    window.location.href = `/postularse/entrada?registro=${registro}&role=${role}`;
  } else {
    window.history.back();
  }
};

// --- Acciones ---
const registrarAsistencia = async () => {
  if (!form.registro_postulante || !form.codigo_docente) return;
  guardando.value = true;
  try {
    await axios.post('/cu13/asistencia', form);
    window.location.reload();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
};

const eliminar = async (codigo) => {
  if (!confirm('¿Eliminar este registro de asistencia?')) return;
  try {
    await axios.delete(`/cu13/asistencia/${codigo}`);
    asistenciasLocal.value = asistenciasLocal.value.filter(a => a.codigo !== codigo);
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
};
</script>
