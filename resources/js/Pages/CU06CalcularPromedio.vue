<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU06 - Calcular Promedio y Estado</h1>
          <p class="mt-1 text-blue-200">Resultados finales por estudiante · Umbral de aprobación: 60 puntos por examen</p>
        </div>
        <button @click="volver" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 space-y-8">

      <!-- Estadísticas -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
          <p class="text-slate-500 text-sm font-semibold">Total Calificados</p>
          <p class="text-4xl font-bold text-slate-900 mt-1">{{ estadisticas.total_calificados }}</p>
          <p class="text-xs text-slate-400 mt-1">12 exámenes completos</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
          <p class="text-slate-500 text-sm font-semibold">Aprobados</p>
          <p class="text-4xl font-bold text-green-600 mt-1">{{ estadisticas.total_aprobados }}</p>
          <p class="text-xs text-slate-400 mt-1">Todas las notas ≥ 60</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
          <p class="text-slate-500 text-sm font-semibold">Reprobados</p>
          <p class="text-4xl font-bold text-red-600 mt-1">{{ estadisticas.total_reprobados }}</p>
          <p class="text-xs text-slate-400 mt-1">Al menos 1 nota &lt; 60</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
          <p class="text-slate-500 text-sm font-semibold">Promedio General</p>
          <p class="text-4xl font-bold text-purple-600 mt-1">
            {{ estadisticas.promedio_general != null ? estadisticas.promedio_general.toFixed(2) : '—' }}
          </p>
          <p class="text-xs text-slate-400 mt-1">Promedio de promedios finales</p>
        </div>
      </div>

      <!-- Panel de resultados por estudiante -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-slate-800">Resultados por Estudiante</h2>
          <p class="text-slate-500 text-sm mt-1">
            Promedio por materia = (Nota1 + Nota2 + Nota3) / 3 · Promedio final = promedio de las 4 materias
          </p>
        </div>

        <div v-if="!resultados.length" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-center text-slate-500">
          No hay calificaciones por materia registradas todavía.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700 whitespace-nowrap">Registro</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700 whitespace-nowrap">CI</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700 whitespace-nowrap">Nombre</th>
                <th v-for="g in grupos" :key="g.sigla"
                  class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-nowrap">
                  Prom. {{ g.sigla }}
                </th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-nowrap">Promedio Final</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-nowrap">Estado</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700 whitespace-nowrap">Fecha Aprobación</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="r in resultados" :key="r.id"
                class="hover:bg-slate-50"
                :class="r.estado === 'APROBADO' ? 'bg-green-50/30' : r.estado === 'REPROBADO' ? 'bg-red-50/30' : ''">

                <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ r.registro }}</td>
                <td class="px-4 py-3 font-mono text-slate-600">{{ r.ci }}</td>
                <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">{{ r.nombre }}</td>

                <!-- Nota promedio por materia -->
                <td v-for="g in grupos" :key="g.sigla" class="px-4 py-3 text-center">
                  <span v-if="r['prom_' + g.sigla] != null"
                    :class="r['prom_' + g.sigla] >= 60 ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'">
                    {{ r['prom_' + g.sigla].toFixed(2) }}
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </td>

                <!-- Promedio final -->
                <td class="px-4 py-3 text-center">
                  <span v-if="r.promedio_final != null"
                    class="font-bold text-base"
                    :class="r.promedio_final >= 60 ? 'text-indigo-700' : 'text-red-600'">
                    {{ r.promedio_final.toFixed(2) }}
                  </span>
                  <span v-else class="text-slate-300 text-xs">Incompleto</span>
                </td>

                <!-- Estado -->
                <td class="px-4 py-3 text-center">
                  <span v-if="r.estado === 'APROBADO'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-200">
                    ✓ APROBADO
                  </span>
                  <span v-else-if="r.estado === 'REPROBADO'"
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-200">
                    ✗ REPROBADO
                  </span>
                  <span v-else
                    class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    ⏳ PENDIENTE
                  </span>
                </td>

                <!-- Fecha aprobación -->
                <td class="px-4 py-3 text-center text-sm">
                  <span v-if="r.fecha_aprobacion" class="text-green-700 font-semibold">
                    {{ formatFecha(r.fecha_aprobacion) }}
                  </span>
                  <span v-else class="text-slate-300">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
defineProps({
  resultados:   Array,
  grupos:       Array,
  estadisticas: Object,
});

const volver = () => window.history.back();

const formatFecha = (fecha) => {
  if (!fecha) return '—';
  const [y, m, d] = fecha.split('T')[0].split('-');
  return `${d}/${m}/${y}`;
};
</script>
