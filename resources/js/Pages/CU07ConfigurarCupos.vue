<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold">CU07 - Configurar Cupos por Carrera</h1>
            <p class="mt-2 text-slate-200">Establece los cupos máximos de admisión para cada carrera en cada gestión académica</p>
          </div>
          <button @click="volver" type="button" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-12">
      <!-- Formulario -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6">Formulario de Configuración</h2>

        <!-- Gestión -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Gestión Académica</label>
            <select v-model="formulario.gestion_codigo" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
              <option value="">-- Selecciona gestión --</option>
              <option v-for="g in gestiones" :key="g.codigo" :value="g.codigo">
                {{ g.gestion }} ({{ g.anio }})
              </option>
            </select>
            <p v-if="errors.gestion_codigo" class="mt-2 text-sm text-red-600">{{ errors.gestion_codigo }}</p>
          </div>
        </div>

        <!-- Carrera y Datos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Carrera</label>
            <select v-model="formulario.carrera_codigo"
              :disabled="!formulario.gestion_codigo"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none disabled:bg-slate-100 disabled:cursor-not-allowed disabled:text-slate-400">
              <option value="">{{ formulario.gestion_codigo ? (carrerasFiltradas.length ? '-- Selecciona carrera --' : 'Todas las carreras ya tienen cupo') : '-- Primero selecciona una gestión --' }}</option>
              <option v-for="carrera in carrerasFiltradas" :key="carrera.codigo" :value="carrera.codigo">
                {{ carrera.sigla }} - {{ carrera.nombre_carrera }}
              </option>
            </select>
            <p v-if="errors.carrera_codigo" class="mt-2 text-sm text-red-600">{{ errors.carrera_codigo }}</p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Cupo Máximo</label>
            <input 
              v-model.number="formulario.cupo_maximo" 
              type="number" 
              min="1"
              placeholder="Ej: 70"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
            />
            <p v-if="errors.cupo_maximo" class="mt-2 text-sm text-red-600">{{ errors.cupo_maximo }}</p>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex gap-4">
          <button @click="guardarCupo" type="button" class="inline-flex items-center justify-center rounded-full bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
            Guardar
          </button>
          <button @click="limpiarFormulario" type="button" class="inline-flex items-center justify-center rounded-full bg-slate-400 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-500">
            Limpiar
          </button>
        </div>

        <!-- Mensaje de éxito -->
        <div v-if="successMessage" class="mt-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-900">
          {{ successMessage }}
        </div>
      </div>

      <!-- Tabla de Cupos -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Cupos Configurados</h2>
          <p class="text-slate-600">Tabla de cupos por carrera, gestión y año</p>
        </div>

        <div v-if="cupos.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-600 text-center">
          No hay cupos configurados todavía.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Código</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Sigla</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Nombre Carrera</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Facultad</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Cupo Máximo</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Cupos Disponibles</th>
                <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Gestión</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Año</th>
                <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="cupo in cupos" :key="cupo.cupo_codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 text-sm font-semibold text-slate-900">{{ cupo.codigo }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ cupo.sigla }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ cupo.nombre_carrera }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ cupo.facultad_sigla }}</td>
                <td class="px-4 py-3 text-sm text-center text-slate-700 font-semibold">{{ cupo.cupo_maximo }}</td>
                <td class="px-4 py-3 text-sm text-center text-slate-700 font-semibold">{{ cupo.cupos_disponibles }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ cupo.gestion }}</td>
                <td class="px-4 py-3 text-sm text-center text-slate-700">{{ cupo.anio }}</td>
                <td class="px-4 py-3 text-center text-sm space-x-2">
                  <button @click="mostrarAceptados(cupo)" class="px-2 py-1 bg-green-100 text-green-700 rounded-lg font-semibold hover:bg-green-200 transition">Mostrar</button>
                  <button @click="editarCupo(cupo)" class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg font-semibold hover:bg-blue-200 transition">Editar</button>
                  <button @click="confirmDelete(cupo)" class="px-2 py-1 bg-red-100 text-red-700 rounded-lg font-semibold hover:bg-red-200 transition">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal de Edición -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full mx-4 p-8">
          <h3 class="text-2xl font-bold mb-6">Editar Cupo</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Gestión</label>
              <select v-model="editFormulario.gestion_codigo" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
                <option v-for="g in gestiones" :key="g.codigo" :value="g.codigo">
                  {{ g.gestion }} ({{ g.anio }})
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Cupo Máximo</label>
              <input
                v-model.number="editFormulario.cupo_maximo"
                type="number"
                min="1"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="flex gap-4">
            <button @click="actualizarCupo" class="flex-1 rounded-full bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
              Guardar cambios
            </button>
            <button @click="showEditModal = false" class="flex-1 rounded-full bg-slate-400 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-500 transition">
              Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Postulantes Aceptados -->
      <div v-if="showAceptadosModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl mx-4 overflow-hidden">
          <div class="bg-gradient-to-r from-green-600 to-green-800 px-8 py-5 flex items-center justify-between">
            <div>
              <h3 class="text-xl font-bold text-white">Postulantes Aceptados — {{ cupoActual?.sigla }}</h3>
              <p class="text-green-200 text-sm mt-1">{{ cupoActual?.nombre_carrera }} · Promedio ≥ 60 · ordenado de mayor a menor</p>
            </div>
            <button @click="showAceptadosModal = false" class="text-white/70 hover:text-white text-2xl font-bold leading-none">×</button>
          </div>

          <div class="p-6 max-h-[70vh] overflow-y-auto">
            <div v-if="cargandoAceptados" class="flex justify-center py-12">
              <div class="w-8 h-8 border-4 border-green-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div v-else-if="aceptados.length === 0" class="text-center py-12 text-slate-400">
              No hay postulantes aprobados para esta carrera todavía.
            </div>
            <div v-else class="overflow-x-auto">
              <p class="text-sm text-slate-500 mb-3">{{ aceptados.length }} postulante(s) aprobado(s)</p>
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Código</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">CI</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Nombre Completo</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Carrera Ingresada</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-700">Promedio</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                  <tr v-for="(p, i) in aceptados" :key="p.registro" class="hover:bg-green-50">
                    <td class="px-4 py-3 text-slate-400 font-mono text-xs">{{ i + 1 }}</td>
                    <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ p.registro }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ p.ci }}</td>
                    <td class="px-4 py-3 font-semibold text-slate-800">{{ p.nombre_completo }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ p.carrera_ingresada }}</td>
                    <td class="px-4 py-3 text-center">
                      <span class="px-3 py-1 rounded-full text-xs font-bold"
                        :class="p.promedio >= 80 ? 'bg-green-100 text-green-700' : p.promedio >= 70 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                        {{ p.promedio }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="px-8 py-4 border-t border-slate-200 flex justify-end">
            <button @click="showAceptadosModal = false" class="px-6 py-2 rounded-full bg-slate-400 text-white font-semibold hover:bg-slate-500 transition text-sm">Cerrar</button>
          </div>
        </div>
      </div>

      <!-- Modal de Confirmación de Eliminar -->
      <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full mx-4 p-8">
          <h3 class="text-2xl font-bold mb-4">Confirmar eliminación</h3>
          <p class="text-slate-600 mb-6">¿Estás seguro de que deseas eliminar el cupo para <strong>{{ deleteTarget?.sigla }} ({{ deleteTarget?.nombre_carrera }})</strong>?</p>
          
          <div class="flex gap-4">
            <button @click="eliminarCupo" class="flex-1 rounded-full bg-red-600 px-4 py-3 text-sm font-semibold text-white hover:bg-red-700 transition">
              Eliminar
            </button>
            <button @click="showDeleteModal = false" class="flex-1 rounded-full bg-slate-400 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-500 transition">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  carreras:  Array,
  cupos:     Array,
  gestiones: Array,
});

const formulario = reactive({
  gestion_codigo: '',
  carrera_codigo: '',
  cupo_maximo: null,
});

const editFormulario = reactive({
  cupo_codigo:    null,
  gestion_codigo: '',
  cupo_maximo:    null,
});

const errors = reactive({});
const successMessage   = ref('');
const showEditModal    = ref(false);
const showDeleteModal  = ref(false);
const deleteTarget     = ref(null);
const showAceptadosModal = ref(false);
const aceptados        = ref([]);
const cargandoAceptados = ref(false);
const cupoActual       = ref(null);

// Carreras ya configuradas para la gestión seleccionada → excluirlas del select
const carrerasFiltradas = computed(() => {
  if (!formulario.gestion_codigo) return [];
  const yaUsadas = props.cupos
    .filter(c => c.gestion_codigo == formulario.gestion_codigo)
    .map(c => c.codigo); // carrera.codigo
  return props.carreras.filter(c => !yaUsadas.includes(c.codigo));
});

// Al cambiar gestión, limpiar carrera seleccionada
watch(() => formulario.gestion_codigo, () => {
  formulario.carrera_codigo = '';
});

const guardarCupo = async () => {
  successMessage.value = '';
  Object.assign(errors, {});

  if (!formulario.gestion_codigo) {
    errors.gestion_codigo = 'Debes seleccionar una gestión.';
    return;
  }

  if (!formulario.carrera_codigo) {
    errors.carrera_codigo = 'Debes seleccionar una carrera.';
    return;
  }

  if (!formulario.cupo_maximo || formulario.cupo_maximo < 1) {
    errors.cupo_maximo = 'El cupo máximo debe ser mayor a 0.';
    return;
  }

  try {
    await axios.post('/cu07/configurar-cupos', {
      gestion_codigo:    Number(formulario.gestion_codigo),
      carrera_id:        formulario.carrera_codigo,
      cupo_maximo:       formulario.cupo_maximo,
      cupos_disponibles: formulario.cupo_maximo,
    });

    successMessage.value = 'Cupo guardado correctamente.';
    limpiarFormulario();

    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (error) {
    if (error.response?.status === 422 && error.response.data.errors) {
      Object.assign(errors, error.response.data.errors);
      return;
    }
    alert('Error: ' + (error.response?.data?.message || error.message));
  }
};

const limpiarFormulario = () => {
  formulario.gestion_codigo = '';
  formulario.carrera_codigo = '';
  formulario.cupo_maximo    = null;
  Object.assign(errors, {});
};

const editarCupo = (cupo) => {
  editFormulario.cupo_codigo    = cupo.cupo_codigo;
  editFormulario.gestion_codigo = cupo.gestion_codigo;
  editFormulario.cupo_maximo    = cupo.cupo_maximo;
  showEditModal.value = true;
};

const actualizarCupo = async () => {
  try {
    await axios.put(`/cu07/configurar-cupos/${editFormulario.cupo_codigo}`, {
      gestion_codigo: Number(editFormulario.gestion_codigo),
      cupo_maximo:    editFormulario.cupo_maximo,
    });

    successMessage.value = 'Cupo actualizado correctamente.';
    showEditModal.value = false;

    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message));
  }
};

const confirmDelete = (cupo) => {
  deleteTarget.value = cupo;
  showDeleteModal.value = true;
};

const eliminarCupo = async () => {
  try {
    await axios.delete(`/cu07/configurar-cupos/${deleteTarget.value.cupo_codigo}`);

    successMessage.value = 'Cupo eliminado correctamente.';
    showDeleteModal.value = false;

    setTimeout(() => {
      window.location.reload();
    }, 1500);
  } catch (error) {
    alert('Error: ' + (error.response?.data?.message || error.message));
  }
};

const mostrarAceptados = async (cupo) => {
  cupoActual.value = cupo;
  aceptados.value  = [];
  cargandoAceptados.value = true;
  showAceptadosModal.value = true;
  try {
    const res = await axios.get(`/cu07/cupo/${cupo.cupo_codigo}/aceptados`);
    const data = res.data;
    // Respuesta nueva: { postulantes, cupos_disponibles } (compatible con array antiguo)
    aceptados.value = Array.isArray(data) ? data : (data.postulantes || []);
    if (!Array.isArray(data) && data.cupos_disponibles !== undefined && data.cupos_disponibles !== null) {
      // Refrescar en vivo el cupo disponible en la tabla
      cupo.cupos_disponibles = data.cupos_disponibles;
      cupoActual.value = { ...cupo };
    }
  } catch (e) {
    alert('Error al cargar postulantes: ' + (e.response?.data?.message || e.message));
    showAceptadosModal.value = false;
  } finally {
    cargandoAceptados.value = false;
  }
};

const volver = () => {
  window.history.back();
};
</script>
