<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-teal-700 to-teal-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU11 - Asignación de Recursos Físicos</h1>
          <p class="mt-1 text-teal-200">Asigna aulas y horarios a los grupos del CUP.</p>
        </div>
        <button @click="volver" class="px-4 py-2 bg-white text-teal-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">Volver</button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Grupos y Recursos Asignados</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Grupo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Módulo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Aula</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Horario</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="grupo in gruposLocal" :key="grupo.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-blue-700">{{ grupo.nombre_grupo }}</td>
                <td class="px-4 py-3 text-slate-700">{{ grupo.sigla }}-{{ grupo.nombre_materia }}</td>
                <td class="px-4 py-3">
                  <span v-if="grupo.numero_aula"
                    class="px-2 py-1 bg-slate-100 text-slate-700 rounded-lg text-xs font-semibold">
                    236
                  </span>
                  <span v-else class="text-slate-300 text-xs">—</span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="grupo.numero_aula"
                    class="px-2 py-1 bg-teal-100 text-teal-700 rounded-lg text-xs font-semibold">
                    {{ grupo.numero_aula }}
                  </span>
                  <span v-else class="text-slate-400 text-xs">Sin aula</span>
                </td>
                <td class="px-4 py-3">
                  <span v-if="grupo.dia"
                    class="px-2 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-semibold">
                    {{ grupo.dia }} {{ grupo.hora_inicio?.slice(0,5) }}-{{ grupo.hora_fin?.slice(0,5) }}
                  </span>
                  <span v-else class="text-slate-400 text-xs">Sin horario</span>
                </td>
                <td class="px-4 py-3">
                  <button @click="abrirEditar(grupo)"
                    class="px-3 py-1.5 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700 transition">
                    Asignar
                  </button>
                </td>
              </tr>
              <tr v-if="!gruposLocal.length">
                <td colspan="6" class="px-4 py-6 text-center text-slate-400">No hay grupos registrados.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Modal -->
    <div v-if="modalAbierto" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4">
      <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl">
        <h3 class="text-xl font-bold mb-6">Asignar recursos a
          <span class="text-teal-700">{{ grupoEditando?.nombre_grupo }}</span>
        </h3>

        <div class="space-y-4">
          <!-- Módulo (fijo, no editable) -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Módulo</label>
            <input type="text" value="236" disabled
              class="w-full border border-slate-200 bg-slate-100 rounded-xl px-4 py-2.5 text-slate-500 font-semibold cursor-not-allowed" />
            <p class="text-xs text-slate-400 mt-1">El módulo es fijo para todas las aulas del CUP.</p>
          </div>

          <!-- Aula (editable) -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Aula</label>
            <input v-model="form.aula_numero" type="text" placeholder="Ej: 12"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-teal-500" />
            <p class="text-xs text-slate-400 mt-1">Ingresa el número de aula.</p>
          </div>

          <!-- Horario -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Horario</label>
            <select v-model="form.codigo_horario"
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-teal-500">
              <option :value="null">-- Sin horario --</option>
              <option v-for="h in horarios" :key="h.codigo" :value="h.codigo">
                {{ h.dia }} · {{ h.hora_inicio?.slice(0,5) }} - {{ h.hora_fin?.slice(0,5) }}
              </option>
            </select>
          </div>
        </div>

        <div class="mt-6 flex gap-3 justify-end">
          <button @click="modalAbierto = false"
            class="px-5 py-2 border border-slate-300 rounded-xl text-slate-700 hover:bg-slate-100 text-sm font-semibold">
            Cancelar
          </button>
          <button @click="guardarRecursos" :disabled="guardando"
            class="px-5 py-2 bg-teal-600 text-white rounded-xl font-semibold text-sm hover:bg-teal-700 disabled:opacity-50 transition">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  grupos:   Array,
  aulas:    Array,
  horarios: Array,
});

const gruposLocal   = ref([...props.grupos]);
const modalAbierto  = ref(false);
const guardando     = ref(false);
const grupoEditando = ref(null);

const form = reactive({ aula_numero: '', codigo_horario: null });

const volver = () => window.history.back();

const abrirEditar = (grupo) => {
  grupoEditando.value   = grupo;
  form.aula_numero      = grupo.numero_aula ?? '';
  form.codigo_horario   = grupo.codigo_horario ?? null;
  modalAbierto.value    = true;
};

const guardarRecursos = async () => {
  guardando.value = true;
  try {
    await axios.patch(`/cu11/grupo/${grupoEditando.value.codigo}`, form);

    const horarioObj = props.horarios.find(h => h.codigo === form.codigo_horario);
    const idx = gruposLocal.value.findIndex(g => g.codigo === grupoEditando.value.codigo);
    if (idx !== -1) {
      gruposLocal.value[idx] = {
        ...gruposLocal.value[idx],
        numero_aula:    form.aula_numero || null,
        codigo_horario: form.codigo_horario,
        dia:            horarioObj?.dia ?? null,
        hora_inicio:    horarioObj?.hora_inicio ?? null,
        hora_fin:       horarioObj?.hora_fin ?? null,
      };
    }
    modalAbierto.value = false;
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
};
</script>
