<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU08 - Asignación de Cupos por Carrera</h1>
          <p class="mt-1 text-blue-200">Asigna postulantes a sus carreras según promedio, de mayor a menor.</p>
        </div>
        <button @click="volver" type="button"
          class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Cupos por Carrera -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Cupos por Carrera</h2>

        <!-- Formulario añadir cupo -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <select v-model="form.gestion_codigo" class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:border-blue-500">
            <option value="">-- Gestión --</option>
            <option v-for="g in gestiones" :key="g.codigo" :value="g.codigo">{{ g.gestion }} - {{ g.anio }}</option>
          </select>
          <select v-model="form.carrera_codigo" class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:border-blue-500">
            <option value="">-- Carrera --</option>
            <option v-for="c in carreras" :key="c.codigo" :value="c.codigo">{{ c.sigla }} - {{ c.nombre_carrera }}</option>
          </select>
          <input type="number" v-model.number="form.cupo_maximo" placeholder="Cupo máximo"
            class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:border-blue-500" />
          <input type="number" v-model.number="form.cupos_disponibles" placeholder="Cupos disponibles"
            class="rounded-xl border border-slate-300 px-3 py-2 focus:outline-none focus:border-blue-500" />
        </div>
        <div class="flex gap-2 mb-6">
          <button @click="guardarCupo" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700">Añadir cupo</button>
          <button @click="limpiarFormulario" class="px-4 py-2 bg-slate-400 text-white rounded-xl text-sm font-semibold hover:bg-slate-500">Limpiar</button>
        </div>

        <div v-if="cuposLocal.length === 0" class="text-slate-500 text-sm">No hay cupos configurados.</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Sigla</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Carrera</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Cupo Máx.</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Disponibles</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Gestión</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
              <tr v-for="c in cuposLocal" :key="c.cupo_codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-bold text-blue-700">{{ c.sigla }}</td>
                <td class="px-4 py-3 text-slate-700">{{ c.nombre_carrera }}</td>
                <td class="px-4 py-3 text-center font-semibold">{{ c.cupo_maximo }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="c.cupos_disponibles === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                    class="px-2 py-1 rounded-lg text-xs font-bold">
                    {{ c.cupos_disponibles }}
                  </span>
                </td>
                <td class="px-4 py-3 text-slate-600">{{ c.gestion }} - {{ c.anio }}</td>
                <td class="px-4 py-3 text-center">
                  <button @click="confirmDelete(c)" class="text-red-600 hover:text-red-800 text-xs font-semibold">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Ejecutar Asignación -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold">Ejecutar Asignación</h2>
            <p class="text-slate-500 text-sm mt-1">
              Procesa los postulantes con pago completado, del mayor al menor promedio.
              Asigna a 1ra opción primero; si no hay cupo, a 2da opción.
            </p>
          </div>
          <button @click="ejecutarAsignacion" :disabled="loading"
            class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 disabled:opacity-50 transition whitespace-nowrap">
            {{ loading ? 'Ejecutando...' : 'Ejecutar asignación' }}
          </button>
        </div>

        <!-- Resultados -->
        <div v-if="results">
          <!-- Resumen estadístico -->
          <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-center">
              <p class="text-slate-500 text-xs font-semibold uppercase">Procesados</p>
              <p class="text-3xl font-bold text-slate-800 mt-1">{{ results.summary.procesados }}</p>
            </div>
            <div class="bg-green-50 rounded-2xl p-4 border border-green-200 text-center">
              <p class="text-green-600 text-xs font-semibold uppercase">Asignados</p>
              <p class="text-3xl font-bold text-green-700 mt-1">{{ results.summary.asignados }}</p>
            </div>
            <div class="bg-red-50 rounded-2xl p-4 border border-red-200 text-center">
              <p class="text-red-600 text-xs font-semibold uppercase">Sin Cupo</p>
              <p class="text-3xl font-bold text-red-700 mt-1">{{ results.summary.sin_cupo }}</p>
            </div>
          </div>

          <!-- Tabla de detalles -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
              <thead class="bg-slate-50">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">#</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">Registro</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">Nombre</th>
                  <th class="px-4 py-3 text-center font-semibold text-slate-700">Promedio</th>
                  <th class="px-4 py-3 text-center font-semibold text-slate-700">Opción</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">Resultado</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-slate-200">
                <tr v-for="(d, i) in results.details" :key="d.registro"
                  :class="d.opcion === '—' ? 'bg-red-50/40' : i % 2 === 0 ? '' : 'bg-slate-50/40'"
                  class="hover:bg-blue-50/30">
                  <td class="px-4 py-2.5 text-slate-500 text-xs">{{ i + 1 }}</td>
                  <td class="px-4 py-2.5 font-mono font-semibold text-blue-700">{{ d.registro }}</td>
                  <td class="px-4 py-2.5 text-slate-700">{{ d.nombre }}</td>
                  <td class="px-4 py-2.5 text-center">
                    <span v-if="d.promedio" class="font-bold text-indigo-700">{{ Number(d.promedio).toFixed(2) }}</span>
                    <span v-else class="text-slate-300">—</span>
                  </td>
                  <td class="px-4 py-2.5 text-center">
                    <span v-if="d.opcion === '1ra'" class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">1ra</span>
                    <span v-else-if="d.opcion === '2da'" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">2da</span>
                    <span v-else class="text-slate-400 text-xs">—</span>
                  </td>
                  <td class="px-4 py-2.5">
                    <span :class="d.opcion === '—' ? 'text-red-600 font-semibold' : 'text-green-700 font-semibold'"
                      class="text-xs">{{ d.resultado }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- Estudiantes Ingresados - 4 tablas por carrera -->
      <section>
        <h2 class="text-2xl font-bold mb-6 px-1">Estudiantes Ingresados <span class="text-base font-normal text-slate-500">(solo los asignados)</span></h2>

        <div v-if="ingresadosLocal.length === 0" class="bg-white rounded-3xl shadow border border-slate-200 p-10 text-center text-slate-400">
          No hay estudiantes asignados todavía.
        </div>

        <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
          <div v-for="carrera in carreras" :key="carrera.codigo"
            class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">

            <!-- Cabecera de carrera -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 flex items-center justify-between">
              <div>
                <p class="text-blue-200 text-xs font-semibold uppercase tracking-wide">{{ carrera.sigla }}</p>
                <h3 class="text-white text-lg font-bold">{{ carrera.nombre_carrera }}</h3>
              </div>
              <div class="text-right">
                <p class="text-blue-200 text-xs">Asignados</p>
                <p class="text-white text-2xl font-bold">{{ estudiantesPorCarrera(carrera.codigo).length }}</p>
                <p class="text-blue-200 text-xs">/ {{ cupoDeCarrera(carrera.codigo)?.cupo_maximo ?? '—' }}</p>
              </div>
            </div>

            <!-- Barra de progreso -->
            <div class="h-1.5 bg-slate-200">
              <div class="h-1.5 bg-blue-500 transition-all"
                :style="{ width: porcentajeOcupacion(carrera.codigo) + '%' }"></div>
            </div>

            <!-- Tabla de estudiantes -->
            <div v-if="estudiantesPorCarrera(carrera.codigo).length === 0"
              class="px-6 py-8 text-center text-slate-400 text-sm">
              Sin estudiantes asignados en esta carrera.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500">#</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500">Registro</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500">CI</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-500">Nombre</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500">Promedio</th>
                    <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-500">Opción</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(est, i) in estudiantesPorCarrera(carrera.codigo)" :key="est.id"
                    class="hover:bg-slate-50">
                    <td class="px-4 py-2.5 text-xs text-slate-400 font-mono">{{ i + 1 }}</td>
                    <td class="px-4 py-2.5 font-mono text-xs font-bold text-blue-700">{{ est.registro }}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-600 font-mono">{{ est.ci }}</td>
                    <td class="px-4 py-2.5 text-xs text-slate-700 whitespace-nowrap">{{ est.nombre }} {{ est.apellido }}</td>
                    <td class="px-4 py-2.5 text-center">
                      <span v-if="est.promedio_final != null"
                        class="font-bold text-indigo-700 text-xs">{{ Number(est.promedio_final).toFixed(2) }}</span>
                      <span v-else class="text-slate-300 text-xs">—</span>
                    </td>
                    <td class="px-4 py-2.5 text-center">
                      <span v-if="est.opcion === '1ra'"
                        class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">1ra</span>
                      <span v-else
                        class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">2da</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  cupos:      Array,
  carreras:   Array,
  gestiones:  Array,
  ingresados: Array,
});

const cuposLocal      = ref(props.cupos     || []);
const ingresadosLocal = ref(props.ingresados || []);
const results         = ref(null);
const loading         = ref(false);

const form = ref({
  gestion_codigo:    '',
  carrera_codigo:    '',
  cupo_maximo:       null,
  cupos_disponibles: null,
});

// --- Helpers de tablas ---
const estudiantesPorCarrera = (carreraId) =>
  ingresadosLocal.value.filter(p => p.carrera_asignada_id === carreraId);

const cupoDeCarrera = (carreraId) =>
  cuposLocal.value.find(c => c.carrera_id === carreraId);

const porcentajeOcupacion = (carreraId) => {
  const cupo  = cupoDeCarrera(carreraId);
  if (!cupo || cupo.cupo_maximo === 0) return 0;
  const asignados = estudiantesPorCarrera(carreraId).length;
  return Math.min(100, Math.round((asignados / cupo.cupo_maximo) * 100));
};

// --- Acciones ---
const ejecutarAsignacion = async () => {
  if (loading.value) return;
  if (!confirm('¿Ejecutar asignación de cupos? Los postulantes serán asignados de mayor a menor promedio.')) return;
  loading.value = true;
  try {
    const resp = await axios.post('/cu08/asignar-cupos');
    results.value         = resp.data;
    cuposLocal.value      = resp.data.cupos      || cuposLocal.value;
    ingresadosLocal.value = resp.data.ingresados  || ingresadosLocal.value;
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};

const guardarCupo = async () => {
  if (!form.value.gestion_codigo) { alert('Selecciona una gestión'); return; }
  if (!form.value.carrera_codigo)  { alert('Selecciona una carrera');  return; }
  try {
    await axios.post('/cu07/configurar-cupos', {
      gestion_codigo:    Number(form.value.gestion_codigo),
      anio:              new Date().getFullYear(),
      carrera_id:        form.value.carrera_codigo,
      cupo_maximo:       form.value.cupo_maximo,
      cupos_disponibles: form.value.cupos_disponibles,
    });
    window.location.reload();
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  }
};

const limpiarFormulario = () => {
  form.value = { gestion_codigo: '', carrera_codigo: '', cupo_maximo: null, cupos_disponibles: null };
};

const confirmDelete = (c) => {
  if (!confirm('¿Eliminar este cupo?')) return;
  eliminarCupo(c);
};

const eliminarCupo = async (c) => {
  try {
    await axios.delete(`/cu07/configurar-cupos/${c.cupo_codigo}`);
    window.location.reload();
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  }
};

const volver = () => {
  const params   = new URLSearchParams(window.location.search);
  const registro = params.get('registro');
  const role     = params.get('role');
  if (registro && role) {
    window.location.href = `/postularse/entrada?registro=${registro}&role=${role}`;
  } else {
    window.history.back();
  }
};
</script>
