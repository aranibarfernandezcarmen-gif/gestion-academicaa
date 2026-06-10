<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU05 - Registrar Calificaciones</h1>
          <p class="mt-2 text-slate-200">Selecciona materia, grupo y estudiante para registrar notas parciales.</p>
        </div>
        <button @click="volver" type="button" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
          Volver
        </button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Formulario -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Registrar Notas</h2>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 mb-6">

          <!-- 1. Materia -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Materia</label>
            <select v-model="materiaSeleccionada" @change="onMateriaChange"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
              <option :value="null">-- Selecciona una materia --</option>
              <option v-for="m in materias" :key="m.codigo" :value="m.codigo">
                {{ m.sigla }} - {{ m.nombre_materia }}
              </option>
            </select>
            <p v-if="errors.materia" class="mt-1 text-sm text-red-600">{{ errors.materia }}</p>
          </div>

          <!-- 2. Grupo (habilitado tras elegir materia) -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Grupo</label>
            <select v-model="grupoSeleccionado" @change="onGrupoChange"
                    :disabled="!materiaSeleccionada"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400">
              <option :value="null">-- Selecciona un grupo --</option>
              <option v-for="g in gruposFiltrados" :key="g.codigo" :value="g.codigo">
                {{ g.nombre_grupo }}
              </option>
            </select>
            <p v-if="errors.grupo" class="mt-1 text-sm text-red-600">{{ errors.grupo }}</p>
          </div>

          <!-- 3. Docente designado (read-only) -->
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Docente Designado</label>
            <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600 text-sm min-h-[48px] flex items-center">
              {{ grupoObj?.docente_nombre ?? 'Se asignará al seleccionar el grupo' }}
            </div>
          </div>

          <!-- 4. Estudiante (habilitado tras elegir grupo) -->
          <div class="lg:col-span-3">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Estudiante</label>
            <select v-model="estudianteSeleccionado" @change="onEstudianteChange"
                    :disabled="!grupoSeleccionado"
                    class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400"
                    size="1">
              <option :value="null">-- Selecciona un estudiante --</option>
              <option v-for="p in estudiantesFiltrados" :key="p.id" :value="p.id">
                {{ p.registro }} — {{ p.nombre }} {{ p.apellido }} (CI: {{ p.ci }})
                {{ notasPendientes(p) }}
              </option>
            </select>
            <p v-if="grupoSeleccionado && estudiantesFiltrados.length === 0"
               class="mt-1 text-sm text-green-600">Todos los estudiantes de este grupo tienen sus 3 notas completas.</p>
            <p v-if="errors.estudiante" class="mt-1 text-sm text-red-600">{{ errors.estudiante }}</p>
          </div>
        </div>

        <!-- Notas -->
        <div class="grid gap-6 md:grid-cols-3 mb-6">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota Parcial 1</label>
            <input v-model.number="notas.nota1"
                   type="number" min="0" max="100" placeholder="0 – 100"
                   :disabled="nota1Bloqueada"
                   :class="nota1Bloqueada ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
            <p v-if="nota1Bloqueada" class="mt-1 text-xs text-slate-400">Ya registrada — no editable</p>
            <p v-if="errors.nota1" class="mt-1 text-sm text-red-600">{{ errors.nota1 }}</p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota Parcial 2</label>
            <input v-model.number="notas.nota2"
                   type="number" min="0" max="100" placeholder="0 – 100"
                   :disabled="nota2Bloqueada"
                   :class="nota2Bloqueada ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
            <p v-if="nota2Bloqueada" class="mt-1 text-xs text-slate-400">Ya registrada — no editable</p>
            <p v-if="errors.nota2" class="mt-1 text-sm text-red-600">{{ errors.nota2 }}</p>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota Parcial 3</label>
            <input v-model.number="notas.nota3"
                   type="number" min="0" max="100" placeholder="0 – 100"
                   :disabled="nota3Bloqueada"
                   :class="nota3Bloqueada ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : ''"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
            <p v-if="nota3Bloqueada" class="mt-1 text-xs text-slate-400">Ya registrada — no editable</p>
            <p v-if="errors.nota3" class="mt-1 text-sm text-red-600">{{ errors.nota3 }}</p>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <p class="text-sm text-slate-500">Las notas deben estar entre 0 y 100. Se guardan de forma incremental.</p>
          <button @click="guardar" :disabled="guardando || !estudianteSeleccionado"
                  class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl shadow hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
            {{ guardando ? 'Guardando...' : 'Guardar Calificaciones' }}
          </button>
        </div>

        <div v-if="successMsg" class="mt-4 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
          {{ successMsg }}
        </div>
      </section>

      <!-- Panel: Calificaciones Registradas -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="mb-6">
          <h2 class="text-2xl font-bold">Calificaciones Registradas</h2>
          <p class="text-slate-500 text-sm mt-1">Estudiantes con las 3 notas completas.</p>
        </div>

        <div v-if="calificacionesLocal.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-500 text-center">
          No hay calificaciones completas registradas aún.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 font-semibold text-slate-700">Registro</th>
                <th class="px-4 py-3 font-semibold text-slate-700">CI</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Nombre</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Grupo</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Materia</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Docente</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Nota 1</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Nota 2</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Nota 3</th>
                <th class="px-4 py-3 font-semibold text-slate-700 text-center">Promedio</th>
                <th class="px-4 py-3 font-semibold text-slate-700">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="c in calificacionesLocal" :key="c.id" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono text-xs font-semibold text-blue-700">{{ c.registro }}</td>
                <td class="px-4 py-3 text-slate-700">{{ c.ci }}</td>
                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ c.nombre }} {{ c.apellido }}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ c.nombre_grupo }}</td>
                <td class="px-4 py-3 text-slate-700">{{ c.nombre_materia }}</td>
                <td class="px-4 py-3 text-slate-600 text-xs">{{ c.docente_nombre }}</td>
                <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ c.nota1 }}</td>
                <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ c.nota2 }}</td>
                <td class="px-4 py-3 text-center font-semibold text-slate-800">{{ c.nota3 }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="(c.promedio ?? 0) >= 51 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                        class="px-2 py-1 rounded-lg text-xs font-bold">
                    {{ c.promedio != null ? Number(c.promedio).toFixed(2) : '—' }}
                  </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap space-x-2">
                  <button @click="abrirEditar(c)" class="rounded-lg bg-yellow-500 px-3 py-1.5 text-white text-xs font-semibold hover:bg-yellow-600 transition">Editar</button>
                  <button @click="eliminar(c)" class="rounded-lg bg-red-600 px-3 py-1.5 text-white text-xs font-semibold hover:bg-red-700 transition">Borrar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <!-- Modal: Editar calificación -->
    <div v-if="editModal" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4 py-6">
      <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div>
            <h3 class="text-xl font-bold">Editar Calificación</h3>
            <p class="text-slate-500 text-sm mt-1">
              {{ editData.nombre }} {{ editData.apellido }} — {{ editData.nombre_grupo }}
            </p>
          </div>
          <button @click="editModal = false" class="text-slate-400 hover:text-slate-900 text-2xl">✕</button>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-6">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 1</label>
            <input v-model.number="editNotas.nota1" type="number" min="0" max="100"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 2</label>
            <input v-model.number="editNotas.nota2" type="number" min="0" max="100"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 3</label>
            <input v-model.number="editNotas.nota3" type="number" min="0" max="100"
                   class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none" />
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button @click="editModal = false" class="rounded-xl border border-slate-300 px-5 py-2.5 text-slate-700 hover:bg-slate-100 text-sm font-semibold">Cancelar</button>
          <button @click="guardarEdicion" :disabled="editGuardando"
                  class="rounded-xl bg-blue-600 px-5 py-2.5 text-white hover:bg-blue-700 text-sm font-semibold disabled:opacity-50">
            {{ editGuardando ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  materias:       Array,
  grupos:         Array,
  postulantes:    Array,
  calificaciones: Array,
});

// --- Estado del formulario ---
const materiaSeleccionada  = ref(null);
const grupoSeleccionado    = ref(null);
const estudianteSeleccionado = ref(null);
const guardando            = ref(false);
const successMsg           = ref('');

const notas = reactive({ nota1: null, nota2: null, nota3: null });
const errors = reactive({ materia: '', grupo: '', estudiante: '', nota1: '', nota2: '', nota3: '' });

// --- Estado reactivo ---
const postulantesLocal   = ref(props.postulantes.map(p => ({ ...p })));
const calificacionesLocal = ref(props.calificaciones.map(c => ({ ...c })));

// --- Computed ---
const gruposFiltrados = computed(() =>
  props.grupos.filter(g => g.codigo_materia === materiaSeleccionada.value)
);

const grupoObj = computed(() =>
  props.grupos.find(g => g.codigo === grupoSeleccionado.value) ?? null
);

const estudiantesFiltrados = computed(() =>
  postulantesLocal.value.filter(p => p.grupo_codigo === grupoSeleccionado.value)
);

const estudianteActual = computed(() =>
  estudiantesFiltrados.value.find(p => p.id === estudianteSeleccionado.value) ?? null
);

const nota1Bloqueada = computed(() => estudianteActual.value?.nota1 != null);
const nota2Bloqueada = computed(() => estudianteActual.value?.nota2 != null);
const nota3Bloqueada = computed(() => estudianteActual.value?.nota3 != null);

// --- Helpers ---
const notasPendientes = (p) => {
  const faltan = [p.nota1, p.nota2, p.nota3].filter(n => n == null).length;
  return faltan === 3 ? '(sin notas)' : `(falta ${faltan} nota${faltan > 1 ? 's' : ''})`;
};

const volver = () => window.history.back();

// --- Watchers ---
const onMateriaChange = () => {
  grupoSeleccionado.value    = null;
  estudianteSeleccionado.value = null;
  resetNotas();
};

const onGrupoChange = () => {
  estudianteSeleccionado.value = null;
  resetNotas();
};

const onEstudianteChange = () => {
  resetNotas();
  const est = estudianteActual.value;
  if (est) {
    notas.nota1 = est.nota1;
    notas.nota2 = est.nota2;
    notas.nota3 = est.nota3;
  }
};

const resetNotas = () => {
  notas.nota1 = null;
  notas.nota2 = null;
  notas.nota3 = null;
  errors.nota1 = '';
  errors.nota2 = '';
  errors.nota3 = '';
  successMsg.value = '';
};

// Convierte "" / NaN / undefined a null para enviar al backend
const toNota = (v) => {
  if (v === null || v === undefined || v === '' || (typeof v === 'number' && isNaN(v))) return null;
  const n = parseInt(v);
  return isNaN(n) ? null : n;
};

// --- Guardar ---
const guardar = async () => {
  Object.keys(errors).forEach(k => errors[k] = '');

  if (!materiaSeleccionada.value)    { errors.materia    = 'Selecciona una materia.';    return; }
  if (!grupoSeleccionado.value)      { errors.grupo      = 'Selecciona un grupo.';       return; }
  if (!estudianteSeleccionado.value) { errors.estudiante = 'Selecciona un estudiante.';  return; }

  const n1 = nota1Bloqueada.value ? null : toNota(notas.nota1);
  const n2 = nota2Bloqueada.value ? null : toNota(notas.nota2);
  const n3 = nota3Bloqueada.value ? null : toNota(notas.nota3);

  if (n1 === null && n2 === null && n3 === null) {
    errors.nota1 = 'Ingresa al menos una nota nueva (0–100).';
    return;
  }

  guardando.value = true;
  try {
    const res = await axios.post('/cu05/registrar-calificaciones', {
      grupo_codigo:  grupoSeleccionado.value,
      postulante_id: estudianteSeleccionado.value,
      nota1: n1,
      nota2: n2,
      nota3: n3,
    });

    const { completo, calificacion, postulante_id, grupo_codigo } = res.data;

    if (completo) {
      // Remover del scroll de estudiantes
      postulantesLocal.value = postulantesLocal.value.filter(
        p => !(p.id === postulante_id && p.grupo_codigo === grupo_codigo)
      );
      // Agregar al panel de calificaciones
      calificacionesLocal.value.push(calificacion);
      successMsg.value = 'Notas completas registradas. El estudiante pasó al panel de Calificaciones Registradas.';
      estudianteSeleccionado.value = null;
      resetNotas();
    } else {
      // Actualizar notas parciales en el scroll
      const idx = postulantesLocal.value.findIndex(
        p => p.id === postulante_id && p.grupo_codigo === grupo_codigo
      );
      if (idx !== -1) {
        postulantesLocal.value[idx] = {
          ...postulantesLocal.value[idx],
          calificacion_id: calificacion.id,
          nota1: calificacion.nota1,
          nota2: calificacion.nota2,
          nota3: calificacion.nota3,
        };
      }
      // Refrescar el campo de notas en la vista
      onEstudianteChange();
      successMsg.value = res.data.message;
    }
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  } finally {
    guardando.value = false;
  }
};

// --- Editar calificación completa ---
const editModal    = ref(false);
const editData     = ref(null);
const editNotas    = reactive({ nota1: null, nota2: null, nota3: null });
const editGuardando = ref(false);

const abrirEditar = (c) => {
  editData.value  = c;
  editNotas.nota1 = c.nota1;
  editNotas.nota2 = c.nota2;
  editNotas.nota3 = c.nota3;
  editModal.value = true;
};

const guardarEdicion = async () => {
  editGuardando.value = true;
  try {
    const res = await axios.patch(`/cu05/calificacion/${editData.value.id}`, {
      nota1: editNotas.nota1,
      nota2: editNotas.nota2,
      nota3: editNotas.nota3,
    });
    const idx = calificacionesLocal.value.findIndex(c => c.id === editData.value.id);
    if (idx !== -1) calificacionesLocal.value[idx] = res.data.calificacion;
    editModal.value = false;
    alert('Calificación actualizada correctamente.');
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  } finally {
    editGuardando.value = false;
  }
};

// --- Eliminar ---
const eliminar = async (c) => {
  if (!confirm(`¿Eliminar las calificaciones de ${c.nombre} ${c.apellido} en ${c.nombre_grupo}?`)) return;
  try {
    await axios.delete(`/cu05/calificacion/${c.id}`);
    calificacionesLocal.value = calificacionesLocal.value.filter(x => x.id !== c.id);
    alert('Calificación eliminada.');
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  }
};
</script>
