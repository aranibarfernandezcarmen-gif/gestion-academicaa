<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-rose-700 to-rose-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU17 - Evaluación de Desempeño</h1>
          <p class="mt-1 text-rose-200">Estadísticas y métricas de rendimiento académico del CUP.</p>
        </div>
        <a :href="`/postularse/entrada?registro=${registro}&role=${role}`"
          class="px-4 py-2 bg-white text-rose-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </a>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Panel de Evaluaciones -->
      <div class="rounded-3xl border border-violet-200 bg-violet-50 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="font-bold text-violet-900 text-lg">Panel de Evaluaciones</p>
            <p class="text-violet-700 text-sm mt-1">Crea formularios de evaluación para docentes y postulantes, y consulta los resultados.</p>
          </div>
          <a :href="`/evaluaciones?registro=${registro}&role=${role}`"
            class="px-5 py-2.5 bg-violet-600 text-white font-semibold rounded-xl hover:bg-violet-700 transition text-sm shadow">
            Abrir Panel
          </a>
        </div>
      </div>

      <!-- Resumen actual (en tiempo real) -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-blue-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Total Calificaciones</p>
          <p class="text-3xl font-bold text-blue-600">{{ resumenActual.total_calificaciones ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-slate-400">
          <p class="text-slate-500 text-xs font-semibold uppercase">Con Promedio</p>
          <p class="text-3xl font-bold text-slate-700">{{ resumenActual.con_promedio ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-green-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Aprobados</p>
          <p class="text-3xl font-bold text-green-600">{{ resumenActual.aprobados ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-red-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Reprobados</p>
          <p class="text-3xl font-bold text-red-600">{{ resumenActual.reprobados ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-amber-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Promedio General</p>
          <p class="text-3xl font-bold text-amber-600">{{ resumenActual.promedio_general ?? '—' }}</p>
        </div>
      </div>

      <!-- Por materia -->
      <section v-if="porMateria.length > 0" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Rendimiento por Materia</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Sigla</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Total</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Aprobados</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Reprobados</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Promedio</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">% Aprobación</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="m in porMateria" :key="m.sigla" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono font-semibold text-rose-700">{{ m.sigla }}</td>
                <td class="px-4 py-3 text-slate-700">{{ m.nombre_materia }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ m.total }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">{{ m.aprobados }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">{{ m.reprobados }}</span>
                </td>
                <td class="px-4 py-3 text-center font-semibold text-slate-900">{{ m.promedio }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="porcentajeBadge(m.aprobados, m.total)" class="px-2 py-1 rounded-full text-xs font-semibold">
                    {{ m.total > 0 ? Math.round((m.aprobados / m.total) * 100) : 0 }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Calcular y guardar estadísticas -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4 mb-6">
          <div class="flex-1">
            <h2 class="text-2xl font-bold">Guardar Estadísticas del Período</h2>
            <p class="text-slate-500 text-sm mt-1">Calcula y registra un snapshot de las estadísticas actuales.</p>
          </div>
          <div class="flex gap-3 items-end">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">Período</label>
              <input v-model="periodo" type="text" placeholder="Ej: 2026-1"
                class="border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-rose-500 text-sm w-36" />
            </div>
            <button @click="calcular" :disabled="calculando"
              class="px-5 py-2.5 bg-rose-600 text-white rounded-xl font-semibold text-sm hover:bg-rose-700 disabled:opacity-50 transition">
              {{ calculando ? 'Calculando...' : 'Calcular y Guardar' }}
            </button>
          </div>
        </div>

        <!-- Historial estadísticas guardadas -->
        <div v-if="estadisticasLocal.length === 0" class="text-center py-8 text-slate-400">No hay estadísticas guardadas aún.</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Período</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Carrera</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Inscritos</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Aprobados</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Reprobados</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Grupos</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Promedio</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">% Aprob.</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Calculado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="e in estadisticasLocal" :key="e.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-rose-700">{{ e.periodo_academico }}</td>
                <td class="px-4 py-3 text-slate-700">{{ e.carrera_nombre }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ e.total_inscritos }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">{{ e.total_aprobados }}</span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">{{ e.total_reprobados }}</span>
                </td>
                <td class="px-4 py-3 text-center text-slate-700">{{ e.total_grupos_habilitados }}</td>
                <td class="px-4 py-3 text-center font-semibold text-slate-900">{{ e.promedio_ponderado }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="porcentajeBadge(e.total_aprobados, e.total_inscritos)"
                    class="px-2 py-1 rounded-full text-xs font-semibold">{{ e.porcentaje_aprobacion }}%</span>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">{{ e.fecha_calculo?.slice(0,10) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  estadisticas:  Array,
  resumenActual: Object,
  porMateria:    Array,
  registro:      String,
  role:          String,
});

const estadisticasLocal = ref([...props.estadisticas]);
const calculando = ref(false);
const periodo    = ref(`${new Date().getFullYear()}-1`);

const porcentajeBadge = (aprobados, total) => {
  const pct = total > 0 ? (aprobados / total) * 100 : 0;
  if (pct >= 70) return 'bg-green-100 text-green-700';
  if (pct >= 50) return 'bg-yellow-100 text-yellow-700';
  return 'bg-red-100 text-red-700';
};

const calcular = async () => {
  calculando.value = true;
  try {
    await axios.post('/cu17/calcular', { periodo: periodo.value });
    window.location.reload();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    calculando.value = false;
  }
};
</script>
