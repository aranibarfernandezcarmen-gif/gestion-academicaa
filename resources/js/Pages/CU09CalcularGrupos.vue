<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold">CU09 - Calcular Grupos</h1>
            <p class="mt-2 text-slate-200">Cálculo automático de grupos según postulantes con pago completado (capacidad: {{ capacidadPorGrupo }} por grupo).</p>
          </div>
          <button @click="volver" type="button" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12 space-y-8">

      <!-- Tarjetas de resumen -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
          <p class="text-slate-500 text-sm font-semibold">Total Inscritos (con pago)</p>
          <p class="text-3xl font-bold text-slate-900">{{ totalInscritos }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
          <p class="text-slate-500 text-sm font-semibold">Grupos Necesarios por Materia</p>
          <p class="text-3xl font-bold text-purple-600">{{ gruposNecesarios }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
          <p class="text-slate-500 text-sm font-semibold">Grupos Existentes (total)</p>
          <p class="text-3xl font-bold text-green-600">{{ gruposExistentesActuales }}</p>
          <p class="text-xs text-slate-400 mt-1">Necesarios: {{ gruposNecesariosTotales }} (4 materias × {{ gruposNecesarios }})</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-slate-500">
          <p class="text-slate-500 text-sm font-semibold">Capacidad por Grupo</p>
          <p class="text-3xl font-bold text-slate-900">{{ capacidadPorGrupo }}</p>
        </div>
      </div>

      <!-- Botón Calcular y Crear Grupos -->
      <div class="flex justify-end">
        <button
          @click="confirmarCrearGrupos"
          :disabled="creando"
          class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          {{ creando ? 'Creando grupos...' : '⚙ Calcular y Crear Grupos' }}
        </button>
      </div>

      <!-- Panel: Grupos necesarios por materia -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Grupos necesarios por materia</h2>
          <p class="text-slate-600 mt-1">
            Cuántos grupos son necesarios para cada materia en base a {{ capacidadPorGrupo }} estudiantes por grupo. Los inscritos por materia se actualizarán desde CU10.
          </p>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 font-semibold text-slate-700">Sigla</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Inscritos</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Grupos Necesarios</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Total de Grupos</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="materia in materias" :key="materia.codigo">
                <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ materia.sigla }}</td>
                <td class="px-4 py-3 text-slate-700">{{ materia.nombre_materia }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="materia.inscritos > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                        class="px-2 py-1 rounded-lg text-xs font-semibold">
                    {{ materia.inscritos }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-lg font-semibold">
                    {{ materia.grupos_necesarios }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg font-semibold">
                    {{ materia.grupos_actuales }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Panel: Grupos existentes -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Grupos existentes</h2>
          <p class="text-slate-600">Los inscritos se asignan desde CU10.</p>
        </div>

        <!-- Botones de filtro por materia -->
        <div class="flex flex-wrap gap-3 mb-6">
          <button
            v-for="materia in materias"
            :key="materia.sigla"
            @click="materiaActiva = materia.sigla"
            :class="materiaActiva === materia.sigla
              ? 'bg-blue-600 text-white shadow-md'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            class="px-5 py-2 rounded-xl font-semibold text-sm transition"
          >
            {{ materia.nombre_materia }}
          </button>
        </div>

        <!-- Tabla de grupos filtrados -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Nombre Grupo</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Capacidad</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Inscritos</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="grupo in gruposFiltrados" :key="grupo.codigo">
                <td class="px-4 py-3 font-mono font-bold text-blue-700 text-base">{{ extraerCodigo(grupo.nombre_grupo) }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">{{ grupo.nombre_grupo }}</td>
                <td class="px-4 py-3 text-center text-slate-700">{{ grupo.capacidad_maxima }}</td>
                <td class="px-4 py-3 font-mono text-sm text-slate-700">
                  {{ grupo.sigla_materia }}-{{ grupo.nombre_materia }}
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 rounded-lg text-xs font-semibold"
                    :class="grupo.inscritos > 0 ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
                    {{ grupo.inscritos }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <button @click="verInscritos(grupo)" class="rounded-lg bg-blue-600 px-4 py-1.5 text-white text-xs font-semibold hover:bg-blue-700 transition">
                    Ver
                  </button>
                </td>
              </tr>
              <tr v-if="gruposFiltrados.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                  No hay grupos para esta materia. Usa "Calcular y Crear Grupos" para generarlos.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Modal: Inscritos del grupo -->
    <div v-if="verModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4 py-6">
      <div class="w-full max-w-2xl rounded-3xl bg-white p-8 shadow-2xl flex flex-col" style="max-height: 80vh;">
        <div class="flex items-start justify-between gap-4 mb-6 flex-shrink-0">
          <div>
            <h3 class="text-2xl font-bold">{{ grupoSeleccionado?.nombre_grupo }}</h3>
            <p class="text-slate-500 text-sm mt-1">
              Capacidad: {{ grupoSeleccionado?.capacidad_maxima }} &nbsp;|&nbsp; Inscritos: {{ inscritosGrupo.length }}
            </p>
          </div>
          <button @click="verModal = false" class="text-slate-400 hover:text-slate-900 text-2xl leading-none mt-1">✕</button>
        </div>

        <div class="overflow-y-auto flex-1">
          <div v-if="cargandoInscritos" class="text-center py-12 text-slate-400">
            Cargando...
          </div>
          <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 sticky top-0">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Registro</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">CI</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Nombre</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Apellido</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="e in inscritosGrupo" :key="e.registro">
                <td class="px-4 py-2 font-mono text-xs text-blue-700 font-semibold">{{ e.registro }}</td>
                <td class="px-4 py-2 text-slate-700">{{ e.ci }}</td>
                <td class="px-4 py-2 text-slate-700">{{ e.nombre }}</td>
                <td class="px-4 py-2 text-slate-700">{{ e.apellido }}</td>
              </tr>
              <tr v-if="!cargandoInscritos && inscritosGrupo.length === 0">
                <td colspan="4" class="px-4 py-10 text-center text-slate-400">
                  No hay inscritos asignados a este grupo aún.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-6 flex justify-end flex-shrink-0">
          <button @click="verModal = false" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-semibold">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  totalInscritos:          Number,
  gruposNecesarios:        Number,
  gruposNecesariosTotales: Number,
  gruposExistentes:        Number,
  capacidadPorGrupo:       Number,
  materias:                Array,
  grupos:                  Array,
});

const gruposLocal              = ref([...props.grupos]);
const gruposExistentesActuales = ref(props.gruposExistentes);
const creando                  = ref(false);

const materiaActiva = ref(props.materias?.[0]?.sigla ?? null);

const gruposFiltrados = computed(() =>
  gruposLocal.value.filter(g => g.sigla_materia === materiaActiva.value)
);

const verModal          = ref(false);
const grupoSeleccionado = ref(null);
const inscritosGrupo    = ref([]);
const cargandoInscritos = ref(false);

const extraerCodigo = (nombreGrupo) => nombreGrupo.split('-')[0];

const volver = () => window.history.back();

const confirmarCrearGrupos = async () => {
  const msg = gruposLocal.value.length > 0
    ? `Se eliminarán los ${gruposLocal.value.length} grupos actuales y se crearán ${props.gruposNecesariosTotales} nuevos grupos (${props.gruposNecesarios} por materia). ¿Continuar?`
    : `Se crearán ${props.gruposNecesariosTotales} grupos (${props.gruposNecesarios} por materia). ¿Continuar?`;

  if (!confirm(msg)) return;

  creando.value = true;
  try {
    const res = await axios.post('/cu09/calcular-grupos');
    alert(res.data.message + `\nGrupos por materia: ${res.data.grupos_por_materia}\nTotal: ${res.data.total_grupos}`);
    window.location.reload();
  } catch (error) {
    alert('Error al crear grupos: ' + (error.response?.data?.message || error.message));
  } finally {
    creando.value = false;
  }
};

const verInscritos = async (grupo) => {
  grupoSeleccionado.value = grupo;
  inscritosGrupo.value    = [];
  cargandoInscritos.value = true;
  verModal.value          = true;
  try {
    const res = await axios.get(`/cu09/grupo/${grupo.codigo}/inscritos`);
    inscritosGrupo.value = res.data.estudiantes ?? [];
  } catch (error) {
    alert('Error al cargar inscritos: ' + (error.response?.data?.message || error.message));
    verModal.value = false;
  } finally {
    cargandoInscritos.value = false;
  }
};
</script>
