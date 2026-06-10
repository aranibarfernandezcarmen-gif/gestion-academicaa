<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-700 to-violet-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <p class="text-violet-300 text-sm font-semibold uppercase mb-1">Resultados de evaluación</p>
          <h1 class="text-2xl font-bold">{{ formulario.titulo }}</h1>
          <p class="text-violet-200 text-sm mt-1">
            {{ formulario.tipo === 'postulante_a_docente' ? 'Postulante evalúa al Docente' : 'Docente evalúa el Curso' }}
          </p>
        </div>
        <a :href="`/evaluaciones?registro=${registro}&role=${role}`"
          class="px-4 py-2 bg-white text-violet-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition text-sm">
          ← Volver al Panel
        </a>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Resumen general -->
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-violet-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Respuestas recibidas</p>
          <p class="text-4xl font-bold text-violet-600">{{ totalRespuestas }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-amber-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Promedio general</p>
          <p class="text-4xl font-bold text-amber-600">{{ promedioGeneral }}<span class="text-base font-normal text-slate-400"> / 5</span></p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-slate-400">
          <p class="text-slate-500 text-xs font-semibold uppercase">Preguntas</p>
          <p class="text-4xl font-bold text-slate-700">{{ formulario.preguntas.length }}</p>
        </div>
      </div>

      <!-- Sin respuestas -->
      <div v-if="totalRespuestas === 0" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-10 text-center text-slate-400">
        Aún no hay respuestas para este formulario.
      </div>

      <!-- Resultados por pregunta -->
      <section v-if="totalRespuestas > 0" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Resultados por Pregunta</h2>

        <div class="space-y-7">
          <div v-for="p in promediosPorPregunta" :key="p.pregunta_id" class="border border-slate-100 rounded-2xl p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
              <p class="font-semibold text-slate-800">
                <span class="text-violet-400 mr-1">{{ p.pregunta_id }}.</span>{{ p.texto }}
              </p>
              <span class="flex-shrink-0 text-2xl font-bold px-4 py-1 rounded-xl"
                :class="promedioColor(p.promedio)">
                {{ p.promedio }} / 5
              </span>
            </div>

            <!-- Barra de distribución -->
            <div class="space-y-2">
              <div v-for="n in [5, 4, 3, 2, 1]" :key="n" class="flex items-center gap-3">
                <span class="w-20 text-sm font-semibold text-slate-500 text-right">
                  {{ etiquetas[n] }}
                </span>
                <div class="flex-1 bg-slate-100 rounded-full h-5 overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-500"
                    :class="barColor(n)"
                    :style="{ width: pct(p.distribucion[n], p.total_respuestas) }"></div>
                </div>
                <span class="w-10 text-sm font-semibold text-slate-600 text-right">{{ p.distribucion[n] }}</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Docentes evaluados (para postulante_a_docente) -->
      <section v-if="formulario.tipo === 'postulante_a_docente' && docentesInfo.length > 0 && totalRespuestas > 0"
        class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Docentes Evaluados</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Docente</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Evaluaciones recibidas</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Promedio</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
              <tr v-for="d in docentesInfo" :key="d.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-800">{{ d.nombre }} {{ d.apellido }}</td>
                <td class="px-4 py-3 text-slate-500 font-mono">{{ d.codigo }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ conteoDocente(d.id) }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-3 py-1 rounded-full font-bold text-sm"
                    :class="promedioColor(promedioDocente(d.id))">
                    {{ promedioDocente(d.id) }} / 5
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Detalle de respuestas individuales -->
      <section v-if="totalRespuestas > 0" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Respuestas Individuales</h2>
        <div class="space-y-4">
          <div v-for="(e, idx) in enviadas" :key="e.id"
            class="border border-slate-100 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
              <p class="font-semibold text-slate-600 text-sm">
                #{{ idx + 1 }} &nbsp;·&nbsp;
                {{ e.tipo_evaluador }} {{ e.registro_evaluador }}
              </p>
              <p class="text-xs text-slate-400">{{ e.created_at?.slice(0, 10) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <span v-for="r in e.respuestas_parsed" :key="r.pregunta_id"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold border"
                :class="puntuacionBadge(r.puntuacion)">
                P{{ r.pregunta_id }}: {{ r.puntuacion }}/5
              </span>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  formulario:           Object,
  totalRespuestas:      Number,
  promedioGeneral:      Number,
  promediosPorPregunta: Array,
  enviadas:             Array,
  docentesInfo:         Array,
  registro:             String,
  role:                 String,
});

const etiquetas = { 1: 'Muy malo', 2: 'Malo', 3: 'Regular', 4: 'Bueno', 5: 'Excelente' };

function pct(count, total) {
  if (!total) return '0%';
  return Math.round((count / total) * 100) + '%';
}

function barColor(n) {
  if (n >= 4) return 'bg-green-500';
  if (n === 3) return 'bg-amber-400';
  return 'bg-red-400';
}

function promedioColor(prom) {
  if (prom >= 4) return 'bg-green-100 text-green-700';
  if (prom >= 3) return 'bg-amber-100 text-amber-700';
  return 'bg-red-100 text-red-700';
}

function puntuacionBadge(p) {
  if (p >= 4) return 'bg-green-50 text-green-700 border-green-200';
  if (p === 3) return 'bg-amber-50 text-amber-700 border-amber-200';
  return 'bg-red-50 text-red-700 border-red-200';
}

function conteoDocente(docenteId) {
  return props.enviadas.filter(e => e.id_docente_evaluado === docenteId).length;
}

function promedioDocente(docenteId) {
  const envs = props.enviadas.filter(e => e.id_docente_evaluado === docenteId);
  if (!envs.length) return 0;
  let suma = 0, total = 0;
  envs.forEach(e => {
    e.respuestas_parsed.forEach(r => {
      suma += r.puntuacion;
      total++;
    });
  });
  return total > 0 ? Math.round((suma / total) * 100) / 100 : 0;
}
</script>
