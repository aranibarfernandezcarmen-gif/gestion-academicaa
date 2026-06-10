﻿<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU12 - Programación de Carga Horaria Docente</h1>
          <p class="mt-1 text-indigo-200">Asigna docentes a grupos y programa su carga horaria.</p>
        </div>
        <button @click="volver" class="px-4 py-2 bg-white text-indigo-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">Volver</button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Panel 1: Docentes y Carga Horaria -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Docentes y Carga Horaria</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Registro</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">CI</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Docente</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Area</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Horas Asignadas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="d in props.docentes" :key="d.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono text-indigo-700 font-semibold">{{ d.registro }}</td>
                <td class="px-4 py-3 font-mono text-slate-600">{{ d.ci }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">{{ d.nombre }} {{ d.apellido }}</td>
                <td class="px-4 py-3 text-slate-600">{{ d.area || '—' }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="d.horas_asignadas > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'"
                        class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ d.horas_asignadas }} hrs
                  </span>
                </td>
              </tr>
              <tr v-if="!props.docentes.length">
                <td colspan="5" class="px-4 py-6 text-center text-slate-400">No hay docentes registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Panel 2: Asignar Docente a Grupo -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Asignar Docente a Grupo</h2>

        <!-- Fila 1: Grupo y Docente -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <!-- Grupo -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Grupo</label>
            <select v-model="form.grupo_codigo"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500">
              <option :value="null">-- Seleccionar grupo --</option>
              <option v-for="g in gruposSinDocente" :key="g.codigo" :value="g.codigo">
                {{ g.nombre_grupo }} ({{ g.sigla }})
              </option>
            </select>
          </div>

          <!-- Docente — se habilita solo cuando hay grupo seleccionado, filtrado por área -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">
              Docente
              <span v-if="form.grupo_codigo && docentesFiltrados.length === 0"
                class="ml-2 text-xs font-normal text-amber-600">
                (sin docentes con área compatible)
              </span>
            </label>
            <select v-model="form.docente_id"
              :disabled="!form.grupo_codigo"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed transition">
              <option :value="null">
                {{ !form.grupo_codigo ? '-- Seleccione un grupo primero --' : '-- Sin docente --' }}
              </option>
              <option v-for="d in docentesFiltrados" :key="d.id" :value="d.id">
                {{ d.nombre }} {{ d.apellido }} · {{ d.area }}
              </option>
            </select>
          </div>
        </div>

        <!-- Fila 2: Días (multi-checkbox), Hora inicio, Hora fin, Horas Semanales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">

          <!-- Días dropdown con checkboxes -->
          <div class="relative" ref="diasMenuRef">
            <label class="block text-sm font-semibold text-slate-700 mb-1">Días</label>
            <button type="button" @click.stop="showDias = !showDias"
              class="w-full border border-slate-300 bg-white rounded-xl px-4 py-2.5 text-left focus:outline-none focus:border-indigo-500 flex justify-between items-center">
              <span class="text-sm truncate"
                :class="form.dias.length ? 'text-slate-900' : 'text-slate-400'">
                {{ form.dias.length ? form.dias.join(', ') : '-- Seleccionar --' }}
              </span>
              <svg class="w-4 h-4 text-slate-400 flex-shrink-0 ml-1 transition-transform duration-200"
                :class="showDias ? 'rotate-180' : ''"
                viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd" />
              </svg>
            </button>

            <div v-if="showDias"
              class="absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl py-2">
              <label v-for="dia in diasOpciones" :key="dia"
                class="flex items-center gap-3 px-4 py-2 cursor-pointer hover:bg-indigo-50 transition select-none">
                <input type="checkbox" :value="dia" v-model="form.dias"
                  class="w-4 h-4 accent-indigo-600 rounded" />
                <span class="text-sm text-slate-700">{{ dia }}</span>
              </label>
            </div>
          </div>

          <!-- Hora Inicio -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Hora Inicio</label>
            <input v-model="form.hora_inicio" type="time"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500" />
          </div>

          <!-- Hora Fin -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Hora Fin</label>
            <input v-model="form.hora_fin" type="time"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-500" />
          </div>

          <!-- Horas Semanales (calculado automáticamente) -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Horas Semanales</label>
            <div class="w-full border border-indigo-200 bg-indigo-50 rounded-xl px-4 py-2.5 text-center font-bold text-indigo-700">
              {{ horasSemanales }} hrs
            </div>
          </div>
        </div>

        <button @click="guardarAsignacion"
          :disabled="guardando || !canGuardar"
          class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold text-sm hover:bg-indigo-700 disabled:opacity-50 transition">
          {{ guardando ? 'Guardando...' : 'Guardar Asignación' }}
        </button>

        <!-- Panel inferior: Asignaciones registradas -->
        <div class="mt-10 overflow-x-auto">
          <h3 class="text-lg font-bold mb-4 text-slate-800">Asignaciones Registradas</h3>
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Grupo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Docente Asignado</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Hora Inicio</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Hora Fin</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="g in props.grupos" :key="g.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-indigo-700">{{ g.nombre_grupo }}</td>
                <td class="px-4 py-3 text-slate-700">{{ g.sigla }} - {{ g.nombre_materia }}</td>
                <td class="px-4 py-3">
                  <span v-if="g.docente_nombre"
                    class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                    {{ g.docente_nombre }}
                  </span>
                  <span v-else class="text-slate-400 text-xs">Sin docente</span>
                </td>
                <td class="px-4 py-3 text-center font-mono text-slate-600">
                  {{ g.hora_inicio ? g.hora_inicio.slice(0, 5) : '—' }}
                </td>
                <td class="px-4 py-3 text-center font-mono text-slate-600">
                  {{ g.hora_fin ? g.hora_fin.slice(0, 5) : '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                  <button v-if="g.codigo_docente" @click="eliminarAsignacion(g.codigo)"
                    class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold hover:bg-red-200 transition">
                    Eliminar
                  </button>
                  <span v-else class="text-slate-300 text-xs">—</span>
                </td>
              </tr>
              <tr v-if="!props.grupos.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-400">No hay grupos registrados.</td>
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
  docentes: Array,
  grupos:   Array,
});

const guardando   = ref(false);
const showDias    = ref(false);
const diasMenuRef = ref(null);

const diasOpciones = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

const form = reactive({
  grupo_codigo: null,
  docente_id:   null,
  dias:         [],
  hora_inicio:  '',
  hora_fin:     '',
});

// Resetear docente al cambiar grupo
watch(() => form.grupo_codigo, () => {
  form.docente_id = null;
});

const normalizar = (s) =>
  (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
// Solo docentes cuya área coincide con la materia del grupo seleccionado
const docentesFiltrados = computed(() => {
  if (!form.grupo_codigo) return [];
  const grp = props.grupos.find(g => g.codigo === form.grupo_codigo);
  if (!grp) return [];
  const mn = normalizar(grp.nombre_materia);
  return props.docentes.filter(d => {
    const an = normalizar(d.area);
    return an === mn || an.includes(mn) || mn.includes(an);
  });
});

// Horas semanales = (duración diaria en horas) × cantidad de días seleccionados
const horasSemanales = computed(() => {
  if (!form.hora_inicio || !form.hora_fin || !form.dias.length) return 0;
  const [sh, sm] = form.hora_inicio.split(':').map(Number);
  const [eh, em] = form.hora_fin.split(':').map(Number);
  const diff = (eh * 60 + em) - (sh * 60 + sm);
  if (diff <= 0) return 0;
  return Math.round((diff / 60) * form.dias.length);
});

const gruposSinDocente = computed(() =>
  props.grupos.filter(g => !g.codigo_docente)
);

const canGuardar = computed(() =>
  !!form.grupo_codigo && form.dias.length > 0 && !!form.hora_inicio && !!form.hora_fin
);

const volver = () => window.history.back();

// Cerrar dropdown de días al hacer click fuera
const handleClickOutside = (e) => {
  if (diasMenuRef.value && !diasMenuRef.value.contains(e.target)) {
    showDias.value = false;
  }
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

const guardarAsignacion = async () => {
  if (!canGuardar.value) return;
  guardando.value = true;
  try {
    await axios.post('/cu12/asignar-docente', {
      grupo_codigo: form.grupo_codigo,
      docente_id:   form.docente_id,
      hora_inicio:  form.hora_inicio,
      hora_fin:     form.hora_fin,
      dia:          form.dias.join(', '),
    });
    window.location.reload();
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
    guardando.value = false;
  }
};

const eliminarAsignacion = async (grupoCodigo) => {
  if (!confirm('¿Eliminar la asignación de docente en este grupo?')) return;
  try {
    await axios.delete(`/cu12/asignacion/${grupoCodigo}`);
    window.location.reload();
  } catch (e) {
    alert('Error al eliminar: ' + (e.response?.data?.message || e.message));
  }
};
</script>
