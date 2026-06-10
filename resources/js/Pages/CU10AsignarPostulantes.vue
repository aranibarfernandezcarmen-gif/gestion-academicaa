<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU10 - Asignar Postulantes a Grupos</h1>
          <p class="mt-2 text-slate-200">Asignación en bloque: cada postulante recibe un grupo por materia.</p>
        </div>
        <button @click="volver" type="button" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 space-y-8">

      <!-- Criterio de Asignación -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-4">Criterio de Asignación</h2>
        <div class="flex flex-col sm:flex-row gap-6 mb-6">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" v-model="criterio" value="registro_asc" class="w-4 h-4 accent-blue-600" />
            <span class="font-medium text-slate-700">Por número de registro (A-Z)</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="radio" v-model="criterio" value="fecha_inscripcion_asc" class="w-4 h-4 accent-blue-600" />
            <span class="font-medium text-slate-700">Por fecha de inscripción (más antigua primero)</span>
          </label>
        </div>
        <button
          @click="ejecutarAsignacion"
          :disabled="ejecutando"
          class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          {{ ejecutando ? 'Ejecutando...' : 'Ejecutar Asignación' }}
        </button>
      </section>

      <!-- Grupos actuales -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Grupos actuales</h2>

        <!-- Tabs de materias -->
        <div class="flex flex-wrap gap-3 mb-6">
          <button
            v-for="materia in materias"
            :key="materia.sigla"
            @click="materiaActivaGrupos = materia.sigla"
            :class="materiaActivaGrupos === materia.sigla
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            class="px-5 py-2 rounded-xl font-semibold text-sm transition"
          >
            {{ materia.nombre_materia }}
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Capacidad</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Inscritos</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Cupo Libre</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="grupo in gruposFiltrados" :key="grupo.codigo">
                <td class="px-4 py-3 font-mono font-bold text-blue-700">{{ extraerCodigo(grupo.nombre_grupo) }}</td>
                <td class="px-4 py-3 font-mono text-sm text-slate-700">{{ grupo.sigla_materia }}-{{ grupo.nombre_materia }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ grupo.capacidad_maxima }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="grupo.inscritos > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                        class="px-2 py-1 rounded-lg text-xs font-semibold">
                    {{ grupo.inscritos }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span :class="grupo.cupo_libre === 0 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                        class="px-2 py-1 rounded-lg text-xs font-semibold">
                    {{ grupo.cupo_libre }}
                  </span>
                </td>
              </tr>
              <tr v-if="gruposFiltrados.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-400">
                  No hay grupos para esta materia. Ejecute CU09 para generarlos.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Postulantes -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold">Postulantes con pago completado</h2>
            <p class="text-slate-500 text-sm mt-1">Total: {{ postulantesLocal.length }}</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 font-semibold text-slate-700 whitespace-nowrap">Registro</th>
                <th class="px-4 py-3 font-semibold text-slate-700 whitespace-nowrap">Nombre</th>
                <template v-for="materia in materias" :key="materia.sigla">
                  <th class="px-3 py-3 font-semibold text-slate-700 text-center whitespace-nowrap">
                    Cód. {{ materia.sigla }}
                  </th>
                  <th class="px-3 py-3 font-semibold text-slate-700 whitespace-nowrap">
                    {{ materia.nombre_materia }}
                  </th>
                </template>
                <th class="px-4 py-3 font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="p in postulantesLocal" :key="p.id">
                <td class="px-4 py-3 font-mono font-semibold text-blue-700 whitespace-nowrap">{{ p.registro }}</td>
                <td class="px-4 py-3 text-slate-800 whitespace-nowrap">{{ p.nombre }} {{ p.apellido }}</td>
                <template v-for="m in materias" :key="m.sigla">
                  <td class="px-3 py-3 text-center whitespace-nowrap">
                    <span v-if="getGrupo(p, m.sigla)"
                          class="font-mono font-bold text-green-700">
                      {{ extraerCodigo(getGrupo(p, m.sigla).nombre_grupo) }}
                    </span>
                    <span v-else class="text-slate-300">—</span>
                  </td>
                  <td class="px-3 py-3 text-slate-500 text-xs whitespace-nowrap">{{ m.sigla }}</td>
                </template>
                <td class="px-4 py-3 whitespace-nowrap space-x-2">
                  <button @click="abrirEditar(p)" class="rounded-lg bg-yellow-500 px-3 py-1.5 text-white text-xs font-semibold hover:bg-yellow-600 transition">Editar</button>
                  <button @click="eliminarAsignacion(p)" class="rounded-lg bg-red-600 px-3 py-1.5 text-white text-xs font-semibold hover:bg-red-700 transition">Eliminar</button>
                </td>
              </tr>
              <tr v-if="postulantesLocal.length === 0">
                <td :colspan="3 + materias.length * 2" class="px-4 py-8 text-center text-slate-400">
                  No hay postulantes con pago completado.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Modal Editar Asignación -->
    <div v-if="editModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4 py-6">
      <div class="w-full max-w-lg rounded-3xl bg-white p-8 shadow-2xl">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <h3 class="text-xl font-bold">Editar asignación</h3>
            <p class="text-slate-500 text-sm mt-1">Postulante: <span class="font-mono font-semibold text-blue-700">{{ editPostulante?.registro }}</span></p>
          </div>
          <button @click="editModal = false" class="text-slate-400 hover:text-slate-900 text-2xl leading-none">✕</button>
        </div>

        <div class="space-y-4">
          <div v-for="materia in materias" :key="materia.sigla">
            <label class="block text-sm font-semibold text-slate-700 mb-1">
              {{ materia.nombre_materia }} ({{ materia.sigla }})
            </label>
            <select
              v-model="editGrupos[materia.sigla]"
              class="w-full rounded-xl border border-slate-300 px-4 py-2.5 focus:border-blue-500 focus:outline-none text-sm"
              size="1"
            >
              <option :value="null">— Sin asignar —</option>
              <option
                v-for="g in gruposPorMateria(materia.sigla)"
                :key="g.codigo"
                :value="g.codigo"
              >
                {{ extraerCodigo(g.nombre_grupo) }} — {{ g.nombre_grupo }} (Cupo: {{ g.cupo_libre }})
              </option>
            </select>
          </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
          <button @click="editModal = false" class="rounded-xl border border-slate-300 px-5 py-2.5 text-slate-700 hover:bg-slate-100 text-sm font-semibold">Cancelar</button>
          <button @click="guardarEdicion" :disabled="guardando" class="rounded-xl bg-blue-600 px-5 py-2.5 text-white hover:bg-blue-700 text-sm font-semibold disabled:opacity-50">
            {{ guardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  materias:    Array,
  grupos:      Array,
  postulantes: Array,
});

const criterio            = ref('registro_asc');
const ejecutando          = ref(false);
const materiaActivaGrupos = ref(props.materias?.[0]?.sigla ?? null);

const gruposLocal       = ref([...props.grupos]);
const postulantesLocal  = ref(props.postulantes.map(p => ({ ...p, grupos: [...p.grupos] })));

const editModal      = ref(false);
const editPostulante = ref(null);
const editGrupos     = reactive({});
const guardando      = ref(false);

const extraerCodigo = (nombreGrupo) => nombreGrupo?.split('-')[0] ?? '—';

const gruposFiltrados = computed(() =>
  gruposLocal.value.filter(g => g.sigla_materia === materiaActivaGrupos.value)
);

const gruposPorMateria = (sigla) =>
  gruposLocal.value.filter(g => g.sigla_materia === sigla);

const getGrupo = (postulante, sigla) =>
  postulante.grupos?.find(g => g.sigla === sigla && g.grupo_codigo !== null) ?? null;

const volver = () => window.history.back();

const ejecutarAsignacion = async () => {
  if (!confirm(`¿Ejecutar asignación en bloque para todos los postulantes con pago completado?\nCriterio: ${criterio.value === 'registro_asc' ? 'Registro A-Z' : 'Fecha de inscripción'}`)) return;

  ejecutando.value = true;
  try {
    const res = await axios.post('/cu10/asignar-postulantes', { criterio: criterio.value });
    alert(`${res.data.message}\nAsignados: ${res.data.asignados}\nSin cupo: ${res.data.sin_cupo}`);
    window.location.reload();
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message));
  } finally {
    ejecutando.value = false;
  }
};

const abrirEditar = (postulante) => {
  editPostulante.value = postulante;
  // Inicializar selects con grupos actuales
  props.materias.forEach(m => {
    const asig = getGrupo(postulante, m.sigla);
    editGrupos[m.sigla] = asig?.grupo_codigo ?? null;
  });
  editModal.value = true;
};

const guardarEdicion = async () => {
  guardando.value = true;
  const grupos = props.materias.map(m => editGrupos[m.sigla] ?? null);
  try {
    await axios.patch(`/cu10/postulante/${editPostulante.value.id}`, { grupos });
    alert('Asignación actualizada correctamente.');
    window.location.reload();
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message));
  } finally {
    guardando.value = false;
  }
};

const eliminarAsignacion = async (postulante) => {
  if (!confirm(`¿Eliminar todos los grupos asignados al postulante ${postulante.registro}?`)) return;
  try {
    await axios.delete(`/cu10/postulante/${postulante.id}`);
    // Limpiar localmente sin recargar
    const idx = postulantesLocal.value.findIndex(p => p.id === postulante.id);
    if (idx !== -1) {
      postulantesLocal.value[idx].grupos = props.materias.map(m => ({
        sigla: m.sigla, nombre_materia: m.nombre_materia,
        grupo_codigo: null, nombre_grupo: null,
      }));
    }
    alert('Asignación eliminada.');
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message));
  }
};
</script>
