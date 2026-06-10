<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-700 to-violet-900 text-white py-6 shadow-lg">
      <div class="max-w-3xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold">{{ formulario.titulo }}</h1>
          <p class="mt-1 text-violet-200 text-sm">Formulario de evaluación — CUP</p>
        </div>
        <a :href="`/postularse/entrada?registro=${registro}&role=${role}`"
          class="px-4 py-2 bg-white text-violet-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition text-sm">
          Volver al Dashboard
        </a>
      </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-10">

      <!-- Ya fue enviado -->
      <div v-if="yaEnvio" class="bg-white rounded-3xl shadow-xl border border-green-200 p-10 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h2 class="text-2xl font-bold text-green-700 mb-2">¡Evaluación ya enviada!</h2>
        <p class="text-slate-500">Ya completaste este formulario anteriormente. Gracias por tu participación.</p>
      </div>

      <!-- Sin grupo asignado -->
      <div v-else-if="sinGrupo" class="bg-white rounded-3xl shadow-xl border border-amber-200 p-10 text-center">
        <div class="text-6xl mb-4">⚠️</div>
        <h2 class="text-2xl font-bold text-amber-700 mb-2">Sin grupo asignado</h2>
        <p class="text-slate-500">Aún no tienes un grupo o docente asignado. Esta evaluación estará disponible una vez que seas asignado.</p>
      </div>

      <!-- Formulario -->
      <div v-else class="space-y-6">

        <!-- Info contexto (docente o curso) -->
        <div v-if="contexto" class="bg-violet-50 border border-violet-200 rounded-2xl p-5">
          <p class="text-xs font-semibold text-violet-500 uppercase mb-1">
            {{ contexto.tipo === 'docente' ? 'Estás evaluando al Docente' : 'Estás evaluando el Curso' }}
          </p>
          <p class="text-xl font-bold text-violet-900">{{ contexto.nombre }}</p>
          <p class="text-sm text-violet-600 mt-0.5">{{ contexto.detalle }}</p>
        </div>

        <!-- Descripción del formulario -->
        <div v-if="formulario.descripcion" class="bg-white rounded-2xl border border-slate-200 p-5">
          <p class="text-sm text-slate-600">{{ formulario.descripcion }}</p>
        </div>

        <!-- Preguntas -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8 space-y-8">
          <h2 class="text-xl font-bold text-slate-800">Responde cada pregunta con una puntuación del 1 al 5</h2>
          <p class="text-sm text-slate-500 -mt-4">1 = Muy malo &nbsp; 2 = Malo &nbsp; 3 = Regular &nbsp; 4 = Bueno &nbsp; 5 = Excelente</p>

          <div v-for="(pregunta, idx) in formulario.preguntas" :key="pregunta.id" class="border-b border-slate-100 pb-6 last:border-0 last:pb-0">
            <p class="font-semibold text-slate-800 mb-3">
              <span class="text-violet-400 mr-2">{{ idx + 1 }}.</span>{{ pregunta.texto }}
            </p>
            <div class="flex gap-3 flex-wrap">
              <button
                v-for="n in [1, 2, 3, 4, 5]" :key="n"
                @click="setPuntuacion(pregunta.id, n)"
                class="w-12 h-12 rounded-xl font-bold text-lg border-2 transition"
                :class="getRespuesta(pregunta.id) === n
                  ? 'border-violet-600 bg-violet-600 text-white shadow-md scale-110'
                  : 'border-slate-300 text-slate-500 hover:border-violet-400 hover:text-violet-600'">
                {{ n }}
              </button>
              <span v-if="getRespuesta(pregunta.id)" class="self-center text-sm font-semibold"
                :class="etiquetaColor(getRespuesta(pregunta.id))">
                {{ etiquetas[getRespuesta(pregunta.id)] }}
              </span>
            </div>
          </div>

          <!-- Enviar -->
          <div class="pt-2">
            <p v-if="errorEnvio" class="text-red-600 text-sm mb-3">{{ errorEnvio }}</p>
            <button @click="enviar" :disabled="enviando"
              class="w-full py-3 bg-violet-600 text-white font-bold rounded-2xl hover:bg-violet-700 disabled:opacity-50 transition text-base">
              {{ enviando ? 'Enviando...' : 'Enviar Evaluación' }}
            </button>
          </div>
        </div>

        <!-- Éxito post-envío -->
        <div v-if="enviado" class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
          <p class="text-2xl font-bold text-green-700 mb-1">¡Gracias! Tu evaluación fue enviada.</p>
          <p class="text-slate-500 text-sm">Tu opinión ayuda a mejorar la calidad del proceso académico.</p>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  formulario: Object,
  contexto:   Object,
  yaEnvio:    Boolean,
  sinGrupo:   Boolean,
  registro:   String,
  role:       String,
});

const respuestas = reactive({}); // { pregunta_id: puntuacion }
const enviando   = ref(false);
const enviado    = ref(false);
const errorEnvio = ref('');

const etiquetas = { 1: 'Muy malo', 2: 'Malo', 3: 'Regular', 4: 'Bueno', 5: 'Excelente' };

function etiquetaColor(n) {
  if (n <= 2) return 'text-red-500';
  if (n === 3) return 'text-amber-500';
  return 'text-green-600';
}

function setPuntuacion(preguntaId, valor) {
  respuestas[preguntaId] = valor;
}

function getRespuesta(preguntaId) {
  return respuestas[preguntaId] ?? null;
}

async function enviar() {
  errorEnvio.value = '';

  // Verificar que todas las preguntas tienen respuesta
  const sinResponder = props.formulario.preguntas.filter(p => !respuestas[p.id]);
  if (sinResponder.length > 0) {
    errorEnvio.value = `Faltan ${sinResponder.length} pregunta(s) por responder.`;
    return;
  }

  const respuestasArr = props.formulario.preguntas.map(p => ({
    pregunta_id:    p.id,
    texto_pregunta: p.texto,
    puntuacion:     respuestas[p.id],
  }));

  enviando.value = true;
  try {
    await axios.post(`/evaluaciones/${props.formulario.id}/responder`, {
      registro:   props.registro,
      role:       props.role,
      respuestas: respuestasArr,
      id_docente: props.contexto?.id_docente ?? null,
      id_grupo:   props.contexto?.id_grupo   ?? null,
    });
    enviado.value = true;
  } catch (e) {
    errorEnvio.value = e.response?.data?.message || 'Error al enviar. Intenta nuevamente.';
  } finally {
    enviando.value = false;
  }
}
</script>
