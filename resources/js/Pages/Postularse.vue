<template>
  <div class="min-h-screen bg-cover bg-center" style="background-image: url('https://static.vecteezy.com/system/resources/thumbnails/009/871/929/small/abstract-wave-blue-background-free-vector.jpg')">
    <div class="backdrop-blur-sm bg-white/80 min-h-screen">
      <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 mb-8">
          <div class="flex flex-col sm:flex-row items-center gap-4">
            <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png" alt="Escudo FICCT" class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-white p-2 shadow flex-shrink-0" />
            <div class="text-center sm:text-left">
              <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-blue-900 leading-tight">FACULTAD DE INGENIERÍA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES</h1>
              <p class="text-xs sm:text-sm text-blue-700 mt-1">Formulario de postulación a cursos preuniversitarios</p>
            </div>
          </div>

          <Link href="/" class="w-full sm:w-auto px-6 py-3 bg-blue-900 text-white text-center font-semibold rounded-lg shadow hover:bg-blue-700 transition whitespace-nowrap">
            Retroceder
          </Link>
        </div>

        <div class="bg-white/95 shadow-2xl rounded-3xl border border-slate-200 p-6 sm:p-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-blue-900 mb-4">Postúlate a CUP</h2>
          <p class="text-sm sm:text-base text-slate-600 mb-4">Completa tus datos para inscribirte en los cursos preuniversitarios. El pago es automático y no puedes modificar el monto.</p>

          <!-- Selector tipo de postulante -->
          <div class="flex flex-wrap gap-2 mb-5">
            <button type="button" @click="cambiarTipo('nuevo')"
              :class="tipoPostulante === 'nuevo' ? 'bg-blue-700 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
              class="px-5 py-2 rounded-xl font-semibold text-sm transition">
              + Postulante Nuevo
            </button>
            <button type="button" @click="cambiarTipo('antiguo')"
              :class="tipoPostulante === 'antiguo' ? 'bg-purple-600 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
              class="px-5 py-2 rounded-xl font-semibold text-sm transition">
              ↩ Ya me postulé antes (re-postulación)
            </button>
          </div>

          <!-- Búsqueda de postulante antiguo -->
          <div v-if="tipoPostulante === 'antiguo'" class="mb-6 p-5 bg-purple-50 border border-purple-200 rounded-2xl">
            <p class="text-sm font-semibold text-purple-800 mb-1">Ingresa tu número de registro del período anterior:</p>
            <p class="text-xs text-purple-600 mb-3">Solo aplica si <strong>reprobaste</strong> en una gestión anterior (promedio menor a 60 en alguna materia).</p>
            <div class="flex gap-3">
              <input v-model="registroBusqueda" type="text" placeholder="Ej: P001"
                class="flex-1 px-4 py-2 border border-purple-300 rounded-xl text-sm font-mono uppercase"
                @keyup.enter="buscarPostulanteAntiguo" />
              <button type="button" @click="buscarPostulanteAntiguo" :disabled="buscandoAntiguo"
                class="px-5 py-2 bg-purple-600 text-white rounded-xl text-sm font-semibold hover:bg-purple-700 disabled:opacity-50 transition">
                {{ buscandoAntiguo ? 'Buscando...' : 'Buscar' }}
              </button>
            </div>
            <div v-if="datosCargados" class="mt-3 flex items-center gap-2 text-green-800 bg-green-50 border border-green-200 rounded-xl px-4 py-2 text-sm font-medium">
              <span>✓</span>
              <span>Datos cargados — Registro <strong>{{ datosCargados.registro }}</strong>: {{ datosCargados.nombreCompleto }}. Puedes actualizar tu información de contacto y tus opciones de carrera.</span>
            </div>
          </div>

          <form class="space-y-6">
            <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-slate-700">Nombre</label>
                <input v-model="form.nombre" type="text"
                  :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.nombre" class="text-red-600 text-sm">{{ errors.nombre }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Apellido</label>
                <input v-model="form.apellido" type="text"
                  :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.apellido" class="text-red-600 text-sm">{{ errors.apellido }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">CI</label>
                <input v-model="form.ci" type="text"
                  :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.ci" class="text-red-600 text-sm">{{ errors.ci }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Fecha de Nacimiento</label>
                <input v-model="form.fecha_nacimiento" type="date"
                  :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.fecha_nacimiento" class="text-red-600 text-sm">{{ errors.fecha_nacimiento }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Sexo</label>
                <select v-model="form.sexo"
                  :disabled="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300">
                  <option value="">Selecciona</option>
                  <option value="M">M</option>
                  <option value="F">F</option>
                </select>
                <span v-if="errors.sexo" class="text-red-600 text-sm">{{ errors.sexo }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.direccion" class="text-red-600 text-sm">{{ errors.direccion }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Teléfono</label>
                <input v-model="form.telefono" type="text" placeholder="Opcional" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.telefono" class="text-red-600 text-sm">{{ errors.telefono }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input v-model="form.correo_electronico" type="email" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.correo_electronico" class="text-red-600 text-sm">{{ errors.correo_electronico }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Ciudad</label>
                <input v-model="form.ciudad" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.ciudad" class="text-red-600 text-sm">{{ errors.ciudad }}</span>
              </div>
            </div>

            <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-slate-700">Colegio</label>
                <input v-model="form.colegio_procedencia" type="text" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.colegio_procedencia" class="text-red-600 text-sm">{{ errors.colegio_procedencia }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Título Bachiller</label>
                <input v-model="form.titulo_bachiller" type="text" placeholder="Ej. 1234A"
                  :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
                  :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
                  class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300" />
                <span v-if="errors.titulo_bachiller" class="text-red-600 text-sm">{{ errors.titulo_bachiller }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Carrera Primera Opción</label>
                <select v-model="form.carrera_primera_opcion_id" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300">
                  <option value="">Selecciona una carrera</option>
                  <option v-for="carrera in carreras" :key="carrera.codigo" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
                </select>
                <span v-if="errors.carrera_primera_opcion_id" class="text-red-600 text-sm">{{ errors.carrera_primera_opcion_id }}</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700">Carrera Segunda Opción <span class="text-red-500">*</span></label>
                <select v-model="form.carrera_segunda_opcion_id" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-300">
                  <option value="">-- Selecciona una carrera --</option>
                  <option v-for="carrera in carreras" :key="`sec-${carrera.codigo}`" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
                </select>
                <span v-if="localErrors.carrera_segunda_opcion_id" class="text-red-600 text-sm">{{ localErrors.carrera_segunda_opcion_id }}</span>
                <span v-else-if="errors.carrera_segunda_opcion_id" class="text-red-600 text-sm">{{ errors.carrera_segunda_opcion_id }}</span>
              </div>
            </div>

            <div class="grid gap-4 sm:gap-6 grid-cols-1 sm:grid-cols-2 items-end">
              <div>
                <label class="block text-sm font-medium text-slate-700">Pago</label>
                <input value="150.00 BS" disabled class="mt-2 w-full rounded-xl border border-slate-300 bg-slate-100 px-4 py-3" />
              </div>
            </div>

            <div class="rounded-3xl border border-green-200 bg-green-50 p-6">
              <div class="flex flex-col items-center gap-4 text-center">
                <p class="text-green-900 font-semibold text-lg">Elige tu forma de pago</p>
                <p class="text-sm text-slate-700">Selecciona una opción para completar tu inscripción.</p>
                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto justify-center">
                  <button 
                    type="button"
                    @click="processPayPal"
                    :disabled="isProcessing"
                    class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold shadow hover:bg-blue-700 transition disabled:opacity-50"
                  >
                    {{ isProcessing ? 'Procesando...' : 'Pagar con PayPal' }}
                  </button>
                  <button 
                    type="button"
                    @click="processPhysicalPayment"
                    :disabled="isProcessing"
                    class="px-8 py-3 bg-orange-500 text-white rounded-xl font-bold shadow hover:bg-orange-600 transition disabled:opacity-50"
                  >
                    {{ isProcessing ? 'Procesando...' : 'Pagar Físicamente' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Alerta de rechazo -->
            <div v-if="rechazoInfo" class="rounded-3xl border border-red-300 bg-red-50 p-6">
              <p class="font-bold text-red-800 text-lg mb-2">⚠ Tu postulación fue registrada con estado RECHAZADO</p>
              <p class="text-red-700 text-sm mb-3">Se envió un correo a <strong>{{ form.correo_electronico }}</strong> con las observaciones. Por favor apersónate a la Facultad para regularizar tu situación.</p>
              <ul class="space-y-1">
                <li v-for="obs in rechazoInfo" :key="obs" class="text-red-700 text-sm flex gap-2">
                  <span>•</span><span>{{ obs }}</span>
                </li>
              </ul>
            </div>

            <div v-else class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
              <p class="font-semibold text-slate-800">Estado</p>
              <p class="mt-2 text-lg font-bold text-orange-700">Pendiente de Pago</p>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';

defineProps({
  carreras: Array,
  errors: Object,
});

const form = useForm({
  nombre: '',
  apellido: '',
  ci: '',
  fecha_nacimiento: '',
  sexo: '',
  direccion: '',
  telefono: '',
  correo_electronico: '',
  ciudad: '',
  colegio_procedencia: '',
  titulo_bachiller: '',
  carrera_primera_opcion_id: '',
  carrera_segunda_opcion_id: '',
});

const isProcessing = ref(false);
const rechazoInfo  = ref(null);
const localErrors  = reactive({});

// Re-postulación
const tipoPostulante    = ref('nuevo');
const registroBusqueda  = ref('');
const buscandoAntiguo   = ref(false);
const datosCargados     = ref(null);
const idPersonaAnterior = ref(null);

const cambiarTipo = (tipo) => {
  tipoPostulante.value    = tipo;
  datosCargados.value     = null;
  idPersonaAnterior.value = null;
  registroBusqueda.value  = '';
  rechazoInfo.value       = null;
  form.reset();
};

const buscarPostulanteAntiguo = async () => {
  const reg = registroBusqueda.value.trim().toUpperCase();
  if (!reg) { alert('Ingresa un número de registro.'); return; }
  buscandoAntiguo.value   = true;
  datosCargados.value     = null;
  idPersonaAnterior.value = null;
  try {
    const res  = await fetch('/api/postulante/buscar?registro=' + reg);
    const data = await res.json();
    if (!res.ok) { alert('Error: ' + (data.error || 'No se encontró el registro.')); return; }
    form.nombre                    = data.nombre;
    form.apellido                  = data.apellido;
    form.ci                        = data.ci;
    form.fecha_nacimiento          = data.fecha_nacimiento;
    form.sexo                      = data.sexo;
    form.direccion                 = data.direccion             || '';
    form.telefono                  = data.telefono              || '';
    form.correo_electronico        = data.correo_electronico    || '';
    form.ciudad                    = data.ciudad                || '';
    form.colegio_procedencia       = data.colegio_procedencia   || '';
    form.titulo_bachiller          = data.titulo_bachiller      || '';
    form.carrera_primera_opcion_id = data.carrera_primera_opcion_id || '';
    form.carrera_segunda_opcion_id = data.carrera_segunda_opcion_id || '';
    idPersonaAnterior.value = data.id_persona;
    datosCargados.value     = { registro: data.registro, nombreCompleto: data.nombre + ' ' + data.apellido };
  } catch (e) {
    alert('Error al buscar: ' + e.message);
  } finally {
    buscandoAntiguo.value = false;
  }
};

const validarCampos = () => {
  Object.keys(localErrors).forEach(k => { localErrors[k] = ''; });
  let ok = true;

  if (!form.nombre || !form.apellido || !form.ci || !form.correo_electronico ||
      !form.carrera_primera_opcion_id || !form.fecha_nacimiento ||
      !form.sexo || !form.direccion || !form.ciudad ||
      !form.colegio_procedencia || !form.titulo_bachiller) {
    alert('Por favor completa todos los campos requeridos.');
    return false;
  }

  if (!form.carrera_segunda_opcion_id) {
    localErrors.carrera_segunda_opcion_id = 'Debe seleccionar la 2da opción de carrera.';
    ok = false;
  } else if (form.carrera_primera_opcion_id == form.carrera_segunda_opcion_id) {
    localErrors.carrera_segunda_opcion_id = 'La 2da opción debe ser diferente a la 1ra.';
    ok = false;
  }

  return ok;
};

const buildPayload = () => ({
  nombre:                    form.nombre,
  apellido:                  form.apellido,
  ci:                        form.ci,
  fecha_nacimiento:          form.fecha_nacimiento,
  sexo:                      form.sexo,
  direccion:                 form.direccion,
  telefono:                  form.telefono,
  correo_electronico:        form.correo_electronico,
  ciudad:                    form.ciudad,
  colegio_procedencia:       form.colegio_procedencia,
  titulo_bachiller:          form.titulo_bachiller,
  carrera_primera_opcion_id: String(form.carrera_primera_opcion_id),
  carrera_segunda_opcion_id: String(form.carrera_segunda_opcion_id),
  es_repostulacion:          tipoPostulante.value === 'antiguo',
  id_persona_anterior:       idPersonaAnterior.value,
});

const processPayPal = async () => {
  if (!validarCampos()) return;
  rechazoInfo.value = null;
  isProcessing.value = true;

  try {
    const response = await fetch(route('payment.create'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify(buildPayload()),
    });

    const data = await response.json();

    if (data.rechazado) {
      rechazoInfo.value = data.observaciones;
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    } else if (data.success && data.approval_url) {
      window.location.href = data.approval_url;
    } else {
      alert('Error: ' + (data.error || 'Error al procesar el pago'));
    }
  } catch (error) {
    alert('Error al procesar el pago. Intenta nuevamente. ' + error.message);
  } finally {
    isProcessing.value = false;
  }
};

const processPhysicalPayment = async () => {
  if (!validarCampos()) return;
  rechazoInfo.value = null;
  isProcessing.value = true;

  try {
    const response = await fetch(route('payment.physical'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify(buildPayload()),
    });

    const data = await response.json();

    if (data.rechazado) {
      rechazoInfo.value = data.observaciones;
      window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
    } else if (data.success) {
      sessionStorage.setItem('success', 'Tu inscripción ha sido registrada. Tu pago está PENDIENTE DE VERIFICACIÓN. Una vez verificado, podrás acceder al sistema.');
      sessionStorage.setItem('registro', data.registro);
      sessionStorage.setItem('ci', data.ci);
      window.location.href = route('postulacion.success');
    } else {
      alert('Error: ' + (data.error || 'Error al procesar tu solicitud'));
    }
  } catch (error) {
    alert('Error al procesar tu solicitud. Intenta nuevamente. ' + error.message);
  } finally {
    isProcessing.value = false;
  }
};
</script>

<style scoped>
body {
  margin: 0;
}
</style>
