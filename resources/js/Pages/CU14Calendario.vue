<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-700 to-cyan-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU14 - Calendario Académico (CUP)</h1>
          <p class="mt-1 text-cyan-200">Gestiona las gestiones académicas y planifica actividades en el calendario.</p>
        </div>
        <button @click="volver"
          class="px-4 py-2 bg-white text-cyan-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- ===== GESTIONES ACADÉMICAS ===== -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold">Gestiones Académicas</h2>
          <button @click="abrirNuevaGestion"
            class="px-4 py-2 bg-cyan-600 text-white rounded-xl font-semibold text-sm hover:bg-cyan-700 transition">
            + Nueva Gestión
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Año</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Gestión</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha Inicio</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha Fin</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="g in gestionesLocal" :key="g.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono text-slate-500">{{ g.codigo }}</td>
                <td class="px-4 py-3 font-bold text-cyan-700">{{ g.anio }}</td>
                <td class="px-4 py-3 text-slate-700">{{ g.gestion }}</td>
                <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ g.fecha_inicio ? formatFecha(g.fecha_inicio) : '—' }}</td>
                <td class="px-4 py-3 text-slate-600 font-mono text-xs">{{ g.fecha_fin ? formatFecha(g.fecha_fin) : '—' }}</td>
                <td class="px-4 py-3 flex gap-2">
                  <button @click="abrirEditarGestion(g)"
                    class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-lg hover:bg-yellow-200 transition">
                    Editar
                  </button>
                  <button @click="eliminarGestion(g.codigo)"
                    class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition">
                    Eliminar
                  </button>
                </td>
              </tr>
              <tr v-if="gestionesLocal.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-slate-400">No hay gestiones registradas.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ===== CALENDARIO FÍSICO ===== -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold">Calendario de Actividades</h2>
          <div class="flex items-center gap-3">
            <!-- Leyenda de colores -->
            <div class="flex items-center gap-2 flex-wrap">
              <span v-for="c in coloresDisponibles" :key="c.valor"
                class="flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full"
                :style="{ backgroundColor: c.valor + '22', color: c.valor, border: '1.5px solid ' + c.valor }">
                <span class="w-2 h-2 rounded-full inline-block" :style="{ backgroundColor: c.valor }"></span>
                {{ c.label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Navegación mes -->
        <div class="flex items-center justify-between mb-4">
          <button @click="mesAnterior"
            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg transition">
            ‹
          </button>
          <h3 class="text-xl font-bold text-slate-800 capitalize">{{ nombreMes }} {{ anioCalendario }}</h3>
          <button @click="mesSiguiente"
            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-lg transition">
            ›
          </button>
        </div>

        <!-- Grid del calendario -->
        <div class="grid grid-cols-7 gap-1">
          <!-- Cabecera días -->
          <div v-for="d in diasSemana" :key="d"
            class="text-center text-xs font-bold text-slate-500 py-2 uppercase tracking-wide">
            {{ d }}
          </div>

          <!-- Celdas vacías antes del primer día -->
          <div v-for="_ in primerDiaSemana" :key="'v' + _" class="h-24"></div>

          <!-- Días del mes -->
          <div
            v-for="dia in diasDelMes"
            :key="dia"
            @click="abrirDia(dia)"
            :class="[
              'h-24 rounded-xl border cursor-pointer transition-all overflow-hidden flex flex-col',
              esHoy(dia) ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-white hover:bg-slate-50',
            ]">
            <!-- Número día -->
            <div class="flex items-center justify-between px-2 pt-1.5">
              <span :class="[
                'text-sm font-bold',
                esHoy(dia) ? 'text-cyan-700 bg-cyan-200 w-6 h-6 rounded-full flex items-center justify-center text-xs' : 'text-slate-700'
              ]">{{ dia }}</span>
              <span v-if="actividadesDia(dia).length > 0"
                class="text-xs text-slate-400 font-mono">+{{ actividadesDia(dia).length }}</span>
            </div>

            <!-- Puntos/etiquetas de actividades -->
            <div class="flex flex-col gap-0.5 px-1 pt-0.5 overflow-hidden">
              <div
                v-for="act in actividadesDia(dia).slice(0, 3)"
                :key="act.id"
                class="text-xs rounded px-1 py-0.5 truncate font-semibold leading-tight"
                :style="{ backgroundColor: act.color + '33', color: act.color, borderLeft: '3px solid ' + act.color }">
                {{ act.titulo }}
              </div>
              <div v-if="actividadesDia(dia).length > 3"
                class="text-xs text-slate-400 pl-1">
                +{{ actividadesDia(dia).length - 3 }} más
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== PANEL DE DETALLES ===== -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-2">
          Actividades — <span class="text-cyan-700 capitalize">{{ nombreMes }} {{ anioCalendario }}</span>
        </h2>
        <p class="text-slate-500 text-sm mb-6">
          {{ actividadesMes.length }} actividad{{ actividadesMes.length !== 1 ? 'es' : '' }} registrada{{ actividadesMes.length !== 1 ? 's' : '' }} este mes.
        </p>

        <div v-if="actividadesMes.length === 0" class="text-center py-10 text-slate-400">
          No hay actividades registradas para este mes. Haz clic en un día del calendario para agregar una.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="act in actividadesMes"
            :key="act.id"
            class="flex items-start gap-4 p-4 rounded-2xl border transition hover:shadow-sm"
            :style="{ borderLeftColor: act.color, borderLeftWidth: '5px', backgroundColor: act.color + '0d' }">
            <!-- Indicador color -->
            <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center font-bold text-white text-sm"
              :style="{ backgroundColor: act.color }">
              {{ new Date(act.fecha + 'T00:00:00').getDate() }}
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-800">{{ act.titulo }}</span>
                <span class="text-xs font-mono text-slate-500">{{ formatFechaLarga(act.fecha) }}</span>
                <span v-if="act.gestion_academica_id"
                  class="text-xs px-2 py-0.5 rounded-full font-semibold"
                  :style="{ backgroundColor: act.color + '22', color: act.color }">
                  Gestión {{ gestionNombre(act.gestion_academica_id) }}
                </span>
              </div>
              <p v-if="act.descripcion" class="text-sm text-slate-600 mt-1">{{ act.descripcion }}</p>
            </div>
            <!-- Acciones -->
            <div class="flex gap-2 flex-shrink-0">
              <button @click="abrirEditarActividad(act)"
                class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-lg hover:bg-yellow-200 transition">
                Editar
              </button>
              <button @click="eliminarActividad(act.id)"
                class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition">
                Eliminar
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- ===== MODAL GESTIÓN ===== -->
    <div v-if="modalGestion" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4">
      <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl">
        <h3 class="text-xl font-bold mb-6">{{ editandoGestion ? 'Editar' : 'Nueva' }} Gestión Académica</h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Año</label>
            <input v-model.number="formGestion.anio" type="number" min="2020" max="2100" placeholder="2026"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre de Gestión</label>
            <input v-model="formGestion.gestion" type="text" placeholder="Gestión 1 - 2026"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha Inicio</label>
              <input v-model="formGestion.fecha_inicio" type="date"
                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha Fin</label>
              <input v-model="formGestion.fecha_fin" type="date"
                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
            </div>
          </div>
        </div>
        <div class="mt-6 flex gap-3 justify-end">
          <button @click="cerrarModalGestion"
            class="px-5 py-2 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-100 text-sm font-semibold">
            Cancelar
          </button>
          <button @click="guardarGestion" :disabled="guardando"
            class="px-5 py-2 bg-cyan-600 text-white rounded-xl font-semibold text-sm hover:bg-cyan-700 disabled:opacity-50 transition">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ACTIVIDAD ===== -->
    <div v-if="modalActividad" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4">
      <div class="w-full max-w-lg bg-white rounded-3xl p-8 shadow-2xl">
        <h3 class="text-xl font-bold mb-1">{{ editandoActividad ? 'Editar' : 'Nueva' }} Actividad</h3>
        <p class="text-sm text-slate-500 mb-6">
          {{ formActividad.fecha ? formatFechaLarga(formActividad.fecha) : '' }}
        </p>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha</label>
            <input v-model="formActividad.fecha" type="date"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Título</label>
            <input v-model="formActividad.titulo" type="text" placeholder="Ej: Inicio de clases, Examen final..."
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Descripción (opcional)</label>
            <textarea v-model="formActividad.descripcion" rows="2" placeholder="Detalle de la actividad..."
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500 resize-none"></textarea>
          </div>
          <!-- Selector de color -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Color</label>
            <div class="flex gap-3 flex-wrap">
              <button
                v-for="c in coloresDisponibles" :key="c.valor"
                @click="formActividad.color = c.valor"
                :title="c.label"
                :class="[
                  'w-8 h-8 rounded-full border-2 transition-all',
                  formActividad.color === c.valor ? 'border-slate-900 scale-110 shadow-md' : 'border-transparent'
                ]"
                :style="{ backgroundColor: c.valor }">
              </button>
            </div>
            <p class="text-xs text-slate-500 mt-1">Seleccionado:
              <span class="font-semibold" :style="{ color: formActividad.color }">
                {{ coloresDisponibles.find(c => c.valor === formActividad.color)?.label || formActividad.color }}
              </span>
            </p>
          </div>
          <!-- Gestión relacionada -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Gestión (opcional)</label>
            <select v-model="formActividad.gestion_academica_id"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-cyan-500">
              <option :value="null">— Sin gestión —</option>
              <option v-for="g in gestionesLocal" :key="g.codigo" :value="g.codigo">
                {{ g.anio }} - {{ g.gestion }}
              </option>
            </select>
          </div>
        </div>
        <div class="mt-6 flex gap-3 justify-end">
          <button @click="cerrarModalActividad"
            class="px-5 py-2 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-100 text-sm font-semibold">
            Cancelar
          </button>
          <button @click="guardarActividad" :disabled="guardando"
            class="px-5 py-2 text-white rounded-xl font-semibold text-sm disabled:opacity-50 transition"
            :style="{ backgroundColor: formActividad.color }">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  gestiones:   Array,
  actividades: Array,
});

const gestionesLocal   = ref([...props.gestiones]);
const actividadesLocal = ref([...props.actividades]);

// --- Calendario ---
const hoy          = new Date();
const mesCalendario = ref(hoy.getMonth());     // 0-11
const anioCalendario = ref(hoy.getFullYear());

const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const diasSemana = ['LU','MA','MI','JU','VI','SA','DO'];

const nombreMes = computed(() => meses[mesCalendario.value]);

const diasDelMes = computed(() => {
  return new Date(anioCalendario.value, mesCalendario.value + 1, 0).getDate();
});

// Día de la semana del primer día (0=LU...6=DO, ajustado desde JS donde 0=DO)
const primerDiaSemana = computed(() => {
  const d = new Date(anioCalendario.value, mesCalendario.value, 1).getDay();
  return d === 0 ? 6 : d - 1; // convertir a LU=0
});

const mesAnterior = () => {
  if (mesCalendario.value === 0) { mesCalendario.value = 11; anioCalendario.value--; }
  else mesCalendario.value--;
};

const mesSiguiente = () => {
  if (mesCalendario.value === 11) { mesCalendario.value = 0; anioCalendario.value++; }
  else mesCalendario.value++;
};

const esHoy = (dia) => {
  return dia === hoy.getDate()
    && mesCalendario.value === hoy.getMonth()
    && anioCalendario.value === hoy.getFullYear();
};

const fechaString = (dia) => {
  const m = String(mesCalendario.value + 1).padStart(2, '0');
  const d = String(dia).padStart(2, '0');
  return `${anioCalendario.value}-${m}-${d}`;
};

const actividadesDia = (dia) => {
  const f = fechaString(dia);
  return actividadesLocal.value.filter(a => (a.fecha || '').slice(0, 10) === f);
};

const actividadesMes = computed(() => {
  const prefijo = `${anioCalendario.value}-${String(mesCalendario.value + 1).padStart(2, '0')}`;
  return actividadesLocal.value
    .filter(a => (a.fecha || '').slice(0, 7) === prefijo)
    .sort((a, b) => a.fecha.localeCompare(b.fecha));
});

// --- Colores ---
const coloresDisponibles = [
  { label: 'Azul',     valor: '#3b82f6' },
  { label: 'Cian',     valor: '#06b6d4' },
  { label: 'Verde',    valor: '#22c55e' },
  { label: 'Naranja',  valor: '#f97316' },
  { label: 'Rojo',     valor: '#ef4444' },
  { label: 'Violeta',  valor: '#8b5cf6' },
  { label: 'Rosa',     valor: '#ec4899' },
  { label: 'Amarillo', valor: '#eab308' },
];

// --- Helpers ---
const formatFecha = (f) => {
  if (!f) return '—';
  const [y, m, d] = (f || '').slice(0, 10).split('-');
  return `${d}/${m}/${y}`;
};

const formatFechaLarga = (f) => {
  if (!f) return '';
  const date = new Date(f + 'T00:00:00');
  return date.toLocaleDateString('es-BO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
};

const gestionNombre = (id) => {
  const g = gestionesLocal.value.find(g => g.codigo === id);
  return g ? `${g.anio} - ${g.gestion}` : id;
};

// --- Estado modales ---
const modalGestion    = ref(false);
const modalActividad  = ref(false);
const editandoGestion  = ref(null);
const editandoActividad = ref(null);
const guardando       = ref(false);

const formGestion = reactive({ anio: new Date().getFullYear(), gestion: '', fecha_inicio: '', fecha_fin: '' });
const formActividad = reactive({ fecha: '', titulo: '', descripcion: '', color: '#3b82f6', gestion_academica_id: null });

// --- Gestiones ---
const abrirNuevaGestion = () => {
  editandoGestion.value = null;
  Object.assign(formGestion, { anio: new Date().getFullYear(), gestion: '', fecha_inicio: '', fecha_fin: '' });
  modalGestion.value = true;
};

const abrirEditarGestion = (g) => {
  editandoGestion.value = g.codigo;
  Object.assign(formGestion, {
    anio: g.anio,
    gestion: g.gestion,
    fecha_inicio: g.fecha_inicio ? g.fecha_inicio.slice(0, 10) : '',
    fecha_fin: g.fecha_fin ? g.fecha_fin.slice(0, 10) : '',
  });
  modalGestion.value = true;
};

const cerrarModalGestion = () => {
  modalGestion.value = false;
  editandoGestion.value = null;
};

const guardarGestion = async () => {
  if (!formGestion.anio || !formGestion.gestion) return;
  guardando.value = true;
  try {
    const payload = {
      anio: formGestion.anio,
      gestion: formGestion.gestion,
      fecha_inicio: formGestion.fecha_inicio || null,
      fecha_fin: formGestion.fecha_fin || null,
    };
    if (editandoGestion.value) {
      await axios.patch(`/cu14/gestion/${editandoGestion.value}`, payload);
      const idx = gestionesLocal.value.findIndex(g => g.codigo === editandoGestion.value);
      if (idx !== -1) Object.assign(gestionesLocal.value[idx], payload);
    } else {
      await axios.post('/cu14/gestion', payload);
      window.location.reload();
    }
    cerrarModalGestion();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
};

const eliminarGestion = async (codigo) => {
  if (!confirm('¿Eliminar esta gestión académica?')) return;
  try {
    await axios.delete(`/cu14/gestion/${codigo}`);
    gestionesLocal.value = gestionesLocal.value.filter(g => g.codigo !== codigo);
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
};

// --- Actividades ---
const abrirDia = (dia) => {
  editandoActividad.value = null;
  Object.assign(formActividad, {
    fecha: fechaString(dia),
    titulo: '',
    descripcion: '',
    color: '#3b82f6',
    gestion_academica_id: null,
  });
  modalActividad.value = true;
};

const abrirEditarActividad = (act) => {
  editandoActividad.value = act.id;
  Object.assign(formActividad, {
    fecha: (act.fecha || '').slice(0, 10),
    titulo: act.titulo,
    descripcion: act.descripcion || '',
    color: act.color || '#3b82f6',
    gestion_academica_id: act.gestion_academica_id,
  });
  modalActividad.value = true;
};

const cerrarModalActividad = () => {
  modalActividad.value = false;
  editandoActividad.value = null;
};

const guardarActividad = async () => {
  if (!formActividad.fecha || !formActividad.titulo) return;
  guardando.value = true;
  try {
    const payload = {
      fecha: formActividad.fecha,
      titulo: formActividad.titulo,
      descripcion: formActividad.descripcion || null,
      color: formActividad.color,
      gestion_academica_id: formActividad.gestion_academica_id || null,
    };
    if (editandoActividad.value) {
      const res = await axios.patch(`/cu14/actividad/${editandoActividad.value}`, payload);
      const idx = actividadesLocal.value.findIndex(a => a.id === editandoActividad.value);
      if (idx !== -1) actividadesLocal.value[idx] = res.data.actividad;
    } else {
      const res = await axios.post('/cu14/actividad', payload);
      actividadesLocal.value.push(res.data.actividad);
    }
    cerrarModalActividad();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
};

const eliminarActividad = async (id) => {
  if (!confirm('¿Eliminar esta actividad?')) return;
  try {
    await axios.delete(`/cu14/actividad/${id}`);
    actividadesLocal.value = actividadesLocal.value.filter(a => a.id !== id);
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
};

// --- Navegación ---
const volver = () => {
  const params = new URLSearchParams(window.location.search);
  const registro = params.get('registro');
  const role = params.get('role');
  if (registro && role) {
    window.location.href = `/postularse/entrada?registro=${registro}&role=${role}`;
  } else {
    window.history.back();
  }
};
</script>
