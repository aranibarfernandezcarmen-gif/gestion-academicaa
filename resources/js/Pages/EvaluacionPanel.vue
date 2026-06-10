<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-violet-700 to-violet-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">Panel de Evaluaciones</h1>
          <p class="mt-1 text-violet-200">Crea formularios y consulta los resultados enviados por docentes y postulantes.</p>
        </div>
        <a :href="`/cu17/desempeno?registro=${registro}&role=${role}`"
          class="px-4 py-2 bg-white text-violet-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </a>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Crear nuevo formulario -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-slate-800">Nuevo Formulario</h2>
          <button @click="mostrarCrear = !mostrarCrear"
            class="px-4 py-2 text-sm font-semibold rounded-xl transition"
            :class="mostrarCrear ? 'bg-slate-200 text-slate-700' : 'bg-violet-600 text-white hover:bg-violet-700'">
            {{ mostrarCrear ? 'Cancelar' : '+ Crear Formulario' }}
          </button>
        </div>

        <div v-if="mostrarCrear" class="space-y-5">
          <div class="grid md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">Título del formulario</label>
              <input v-model="nuevoForm.titulo" type="text" placeholder="Ej: Evaluación del Docente — 2026"
                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">Tipo</label>
              <select v-model="nuevoForm.tipo"
                class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500">
                <option value="postulante_a_docente">Postulante evalúa a su Docente</option>
                <option value="docente_a_curso">Docente evalúa su Curso</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Descripción (opcional)</label>
            <textarea v-model="nuevoForm.descripcion" rows="2" placeholder="Instrucciones o contexto para el evaluador..."
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-500 resize-none"></textarea>
          </div>

          <!-- Preguntas -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-semibold text-slate-700">Preguntas (escala 1 al 5)</label>
              <button @click="agregarPregunta" class="px-3 py-1.5 text-xs bg-violet-100 text-violet-700 font-semibold rounded-lg hover:bg-violet-200 transition">
                + Agregar pregunta
              </button>
            </div>
            <div v-for="(p, idx) in nuevoForm.preguntas" :key="idx" class="flex gap-2 mb-2">
              <span class="flex-shrink-0 w-7 h-9 flex items-center justify-center text-slate-400 font-bold text-sm">{{ idx + 1 }}</span>
              <input v-model="p.texto" type="text" :placeholder="`Pregunta ${idx + 1}`"
                class="flex-1 border border-slate-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-violet-500" />
              <button @click="quitarPregunta(idx)"
                class="px-2 py-1 text-red-400 hover:text-red-600 transition text-lg font-bold" title="Eliminar">×</button>
            </div>
            <p v-if="nuevoForm.preguntas.length === 0" class="text-sm text-slate-400 italic mt-1">Agrega al menos una pregunta.</p>
          </div>

          <div class="flex justify-end">
            <button @click="guardarFormulario" :disabled="guardando"
              class="px-6 py-2.5 bg-violet-600 text-white rounded-xl font-semibold text-sm hover:bg-violet-700 disabled:opacity-50 transition">
              {{ guardando ? 'Guardando...' : 'Guardar Formulario' }}
            </button>
          </div>
        </div>
      </section>

      <!-- Listado de formularios -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Formularios Creados</h2>

        <div v-if="formulariosLocal.length === 0" class="text-center py-10 text-slate-400">
          No hay formularios creados aún.
        </div>

        <div v-else class="space-y-4">
          <div v-for="f in formulariosLocal" :key="f.id"
            class="border border-slate-200 rounded-2xl p-5 hover:border-violet-200 transition">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-1">
                  <span class="text-lg font-bold text-slate-800">{{ f.titulo }}</span>
                  <span :class="tipoBadge(f.tipo)" class="px-2 py-0.5 rounded-full text-xs font-semibold">
                    {{ f.tipo === 'postulante_a_docente' ? 'Postulante → Docente' : 'Docente → Curso' }}
                  </span>
                  <span :class="f.activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
                    class="px-2 py-0.5 rounded-full text-xs font-semibold">
                    {{ f.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
                <p v-if="f.descripcion" class="text-sm text-slate-500 mb-2">{{ f.descripcion }}</p>
                <p class="text-sm text-slate-500">
                  <span class="font-semibold">{{ f.preguntas.length }}</span> preguntas &nbsp;·&nbsp;
                  <span class="font-semibold">{{ f.total_respuestas }}</span> respuestas recibidas
                </p>
              </div>

              <div class="flex flex-wrap gap-2">
                <button @click="toggleActivo(f)"
                  class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                  :class="f.activo ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-green-100 text-green-700 hover:bg-green-200'">
                  {{ f.activo ? 'Desactivar' : 'Activar' }}
                </button>
                <a :href="`/evaluaciones/${f.id}/resultados?registro=${registro}&role=${role}`"
                  class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-violet-100 text-violet-700 hover:bg-violet-200 transition">
                  Ver Resultados
                </a>
                <button @click="eliminarFormulario(f)"
                  class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-red-100 text-red-600 hover:bg-red-200 transition">
                  Eliminar
                </button>
              </div>
            </div>

            <!-- Preview preguntas -->
            <div v-if="f.preguntas.length > 0" class="mt-3 border-t border-slate-100 pt-3">
              <p class="text-xs font-semibold text-slate-400 uppercase mb-2">Vista previa de preguntas</p>
              <ol class="space-y-1">
                <li v-for="(p, i) in f.preguntas" :key="i" class="text-sm text-slate-600">
                  <span class="font-semibold text-slate-400 mr-1">{{ i + 1 }}.</span>{{ p.texto }}
                </li>
              </ol>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  formularios: Array,
  registro:    String,
  role:        String,
});

const formulariosLocal = ref(props.formularios.map(f => ({ ...f })));
const mostrarCrear = ref(false);
const guardando    = ref(false);

const nuevoForm = reactive({
  titulo:      '',
  tipo:        'postulante_a_docente',
  descripcion: '',
  preguntas:   [{ texto: '' }],
});

function agregarPregunta() {
  nuevoForm.preguntas.push({ texto: '' });
}

function quitarPregunta(idx) {
  nuevoForm.preguntas.splice(idx, 1);
}

function tipoBadge(tipo) {
  return tipo === 'postulante_a_docente'
    ? 'bg-blue-100 text-blue-700'
    : 'bg-teal-100 text-teal-700';
}

async function guardarFormulario() {
  if (!nuevoForm.titulo.trim()) {
    alert('Escribe un título para el formulario.');
    return;
  }
  const pregsFiltradas = nuevoForm.preguntas.filter(p => p.texto.trim());
  if (pregsFiltradas.length === 0) {
    alert('Agrega al menos una pregunta.');
    return;
  }

  guardando.value = true;
  try {
    const res = await axios.post('/evaluaciones', {
      titulo:      nuevoForm.titulo,
      tipo:        nuevoForm.tipo,
      descripcion: nuevoForm.descripcion,
      preguntas:   pregsFiltradas,
    });

    // Agregar al listado local
    formulariosLocal.value.unshift({
      id:               res.data.id,
      titulo:           nuevoForm.titulo,
      tipo:             nuevoForm.tipo,
      descripcion:      nuevoForm.descripcion,
      activo:           false,
      preguntas:        pregsFiltradas.map((p, i) => ({ id: i + 1, texto: p.texto })),
      total_respuestas: 0,
    });

    // Resetear
    nuevoForm.titulo = '';
    nuevoForm.descripcion = '';
    nuevoForm.tipo = 'postulante_a_docente';
    nuevoForm.preguntas = [{ texto: '' }];
    mostrarCrear.value = false;
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
}

async function toggleActivo(f) {
  try {
    const res = await axios.patch(`/evaluaciones/${f.id}/toggle`);
    f.activo = res.data.activo;
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
}

async function eliminarFormulario(f) {
  if (!confirm(`¿Eliminar el formulario "${f.titulo}"? Esta acción no se puede deshacer.`)) return;
  try {
    await axios.delete(`/evaluaciones/${f.id}`);
    const idx = formulariosLocal.value.indexOf(f);
    if (idx !== -1) formulariosLocal.value.splice(idx, 1);
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
}
</script>
