<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-800 text-white py-16 px-4">
    <!-- Login Form -->
    <div v-if="!showRecoveryModal" class="max-w-3xl mx-auto bg-white/95 rounded-3xl shadow-2xl overflow-hidden">
      <div class="grid grid-cols-1 lg:grid-cols-2">
        <div class="p-10 bg-blue-900 text-white">
          <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png" alt="Escudo FICCT" class="w-24 h-24 rounded-full border border-white/20 mb-8" />
          <h1 class="text-3xl font-bold mb-3">Ingreso de usuario</h1>
          <p class="text-slate-200">Selecciona tu rol y completa los datos. Los postulantes ingresan con registro, los demás roles ingresan con su identificación.</p>
        </div>

        <div class="p-10 bg-white text-slate-900">
          <div class="flex justify-between items-center mb-8">
            <div>
              <h2 class="text-xl font-bold">Login CUP</h2>
              <p class="text-sm text-slate-500">Registra tu ingreso por rol</p>
            </div>
            <Link href="/" class="text-sm text-blue-700 hover:underline">Volver</Link>
          </div>

          <form @submit.prevent="submit" class="space-y-5">
            <!-- Rol Selection -->
            <div>
              <label class="block text-sm font-medium">Seleccione su rol</label>
              <select v-model="form.role" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white text-slate-900">
                <option value="">Selecciona un rol</option>
                <option v-for="(label, key) in roles" :key="key" :value="key">{{ label }}</option>
              </select>
              <span v-if="errors.role" class="text-red-600 text-sm">{{ errors.role }}</span>
            </div>

            <!-- Registro Field for roles that use it -->
            <div v-if="usesRegistro">
              <label class="block text-sm font-medium">Registro</label>
              <input
                v-model="form.registro"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 text-slate-900"
                :placeholder="placeholderText"
              />
              <span v-if="errors.registro" class="text-red-600 text-sm">{{ errors.registro }}</span>
            </div>

            <!-- CI Field for roles that don't use registro (e.g. Decano) -->
            <div v-else-if="form.role && !usesRegistro">
              <label class="block text-sm font-medium">CI</label>
              <input
                v-model="form.ci"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 text-slate-900"
              />
              <span v-if="errors.ci" class="text-red-600 text-sm">{{ errors.ci }}</span>
            </div>

            <!-- Password Field -->
            <div v-if="form.role">
              <label class="block text-sm font-medium">Contraseña</label>
              <input
                v-model="form.password"
                type="password"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 text-slate-900"
              />
              <span v-if="errors.password" class="text-red-600 text-sm">{{ errors.password }}</span>
              <span v-if="errors.ci && isPostulante" class="text-red-600 text-sm">{{ errors.ci }}</span>
              
              <!-- Forgot Password Link -->
              <div class="mt-3">
                <button
                  type="button"
                  @click="showRecoveryModal = true"
                  class="text-sm text-blue-700 hover:text-blue-900 hover:underline"
                >
                  ¿Olvidaste la contraseña?
                </button>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-4">
              <button type="submit" class="px-6 py-3 bg-yellow-400 text-blue-900 rounded-xl font-semibold hover:bg-yellow-500 transition">Ingresar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Password Recovery Modal -->
    <div v-else class="max-w-2xl mx-auto bg-white/95 rounded-3xl shadow-2xl overflow-hidden">
      <div class="p-10 bg-white text-slate-900">
        <div class="flex justify-between items-center mb-8">
          <h2 class="text-2xl font-bold">Recuperar Contraseña</h2>
          <button @click="closeRecoveryModal" class="text-2xl font-bold text-slate-400 hover:text-slate-900">×</button>
        </div>

        <!-- Step 1: Email Entry -->
        <div v-if="recoveryStep === 1" class="space-y-5">
          <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
            <p class="text-sm text-blue-900">Ingresa tu rol, registro y correo electrónico asociados a tu cuenta para recibir un código de verificación.</p>
          </div>

          <form @submit.prevent="requestRecoveryCode" class="space-y-5">
            <div>
              <label class="block text-sm font-medium">Selecciona tu Rol</label>
              <select
                v-model="recoveryForm.role"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option value="">Selecciona tu rol</option>
                <option value="postulante">Postulante</option>
                <option value="docente">Docente</option>
                <option value="administrativo">Administrativo</option>
                <option value="coordinador">Coordinador</option>
                <option value="decano">Decano</option>
              </select>
              <span v-if="recoveryError && !recoveryForm.role" class="text-red-600 text-sm">{{ recoveryError }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium">Tu Registro/Código</label>
              <input
                v-model="recoveryForm.registro"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500"
                placeholder="Ej: DOC086, P001, ADM001"
              />
              <span v-if="recoveryError && !recoveryForm.registro" class="text-red-600 text-sm">{{ recoveryError }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium">Correo Electrónico</label>
              <input
                v-model="recoveryForm.email"
                type="email"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500"
                placeholder="tu@correo.com"
              />
              <span v-if="recoveryError && recoveryForm.email" class="text-red-600 text-sm">{{ recoveryError }}</span>
            </div>

            <div class="flex gap-3 justify-end">
              <button
                type="button"
                @click="closeRecoveryModal"
                class="px-6 py-3 bg-slate-200 text-slate-900 rounded-xl font-semibold hover:bg-slate-300 transition"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="isRecoveryLoading"
                class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition disabled:opacity-50"
              >
                {{ isRecoveryLoading ? 'Enviando...' : 'Enviar Código de Verificación' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Step 2: Code Verification -->
        <div v-else-if="recoveryStep === 2" class="space-y-5">
          <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
            <p class="text-sm text-green-900">Se ha enviado un código de 6 dígitos al correo electrónico {{ recoveryForm.email }}. Por favor, ingresa el código.</p>
          </div>

          <form @submit.prevent="verifyRecoveryCode" class="space-y-5">
            <div>
              <label class="block text-sm font-medium">Código de Verificación</label>
              <input
                v-model="recoveryForm.verification_code"
                type="text"
                maxlength="6"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-center text-2xl font-bold tracking-widest focus:ring-2 focus:ring-blue-500"
                placeholder="000000"
              />
              <span v-if="recoveryError" class="text-red-600 text-sm">{{ recoveryError }}</span>
            </div>

            <div class="flex gap-3 justify-end">
              <button
                type="button"
                @click="recoveryStep = 1; recoveryError = '';"
                class="px-6 py-3 bg-slate-200 text-slate-900 rounded-xl font-semibold hover:bg-slate-300 transition"
              >
                Volver
              </button>
              <button
                type="submit"
                :disabled="isRecoveryLoading"
                class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition disabled:opacity-50"
              >
                {{ isRecoveryLoading ? 'Verificando...' : 'Verificar Código' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Step 3: Password Reset -->
        <div v-else-if="recoveryStep === 3" class="space-y-5">
          <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
            <p class="text-sm text-green-900">Código verificado. Ahora puedes establecer una nueva contraseña.</p>
          </div>

          <form @submit.prevent="resetPasswordSubmit" class="space-y-5">
            <div>
              <label class="block text-sm font-medium">Nueva Contraseña</label>
              <input
                v-model="recoveryForm.new_password"
                type="password"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500"
                placeholder="Mínimo 4 caracteres"
              />
              <span v-if="recoveryError" class="text-red-600 text-sm">{{ recoveryError }}</span>
            </div>

            <div>
              <label class="block text-sm font-medium">Confirmar Contraseña</label>
              <input
                v-model="recoveryForm.confirm_password"
                type="password"
                class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-500"
                placeholder="Confirma tu contraseña"
              />
            </div>

            <div class="flex gap-3 justify-end">
              <button
                type="button"
                @click="recoveryStep = 2; recoveryError = '';"
                class="px-6 py-3 bg-slate-200 text-slate-900 rounded-xl font-semibold hover:bg-slate-300 transition"
              >
                Volver
              </button>
              <button
                type="submit"
                :disabled="isRecoveryLoading"
                class="px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition disabled:opacity-50"
              >
                {{ isRecoveryLoading ? 'Restableciendo...' : 'Restablecer Contraseña' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

defineProps({
  roles: Object,
  errors: Object,
});

const form = useForm({
  registro: '',
  ci: '',
  password: '',
  role: '',
});

// Recovery Modal State
const showRecoveryModal = ref(false);
const recoveryStep = ref(1);
const isRecoveryLoading = ref(false);
const recoveryError = ref('');

const recoveryForm = ref({
  role: '',
  registro: '',
  email: '',
  verification_code: '',
  reset_token: '',
  new_password: '',
  confirm_password: '',
});

const isPostulante = computed(() => form.role === 'postulante');
const isDocente = computed(() => form.role === 'docente');
const isAdministrativo = computed(() => form.role === 'administrativo');
const isCoordinador = computed(() => form.role === 'coordinador');
const isDecano = computed(() => form.role === 'decano');
const usesRegistro = computed(() => isPostulante.value || isDocente.value || isAdministrativo.value || isCoordinador.value || isDecano.value);

const placeholderText = computed(() => {
  if (isPostulante.value) return 'Ej: P001';
  if (isDocente.value) return 'Ej: DOC001';
  if (isAdministrativo.value) return 'Ej: ADM001';
  if (isCoordinador.value) return 'Ej: COO001';
  if (isDecano.value) return 'Ej: DEC001';
  return 'Ingrese su registro';
});

watch(
  () => form.role,
  (role) => {
    if (['postulante', 'docente', 'administrativo', 'coordinador'].includes(role)) {
      form.password = '';
    } else {
      form.registro = '';
      form.ci = '';
      form.password = '';
    }
  }
);

const submit = async () => {
  // Si el rol utiliza registro, buscamos el CI correspondiente antes de enviar el login
  if (usesRegistro.value) {
    try {
      const response = await axios.get(`/api/${form.role}-ci/${form.registro}`);
      if (response.data.ci) {
        form.ci = response.data.ci;
        form.post(route('postulacion.login.submit'));
      } else {
        form.errors.registro = `No se encontró el registro para el rol ${form.role}`;
      }
    } catch (error) {
      form.errors.registro = `Error al validar el registro de ${form.role}`;
    }
    return;
  }

  // Para otros roles: usar CI como está
  form.post(route('postulacion.login.submit'));
};

// Password Recovery Methods
const closeRecoveryModal = () => {
  showRecoveryModal.value = false;
  recoveryStep.value = 1;
  recoveryError.value = '';
  recoveryForm.value = {
    role: '',
    registro: '',
    email: '',
    verification_code: '',
    reset_token: '',
    new_password: '',
    confirm_password: '',
  };
};

const requestRecoveryCode = async () => {
  if (!recoveryForm.value.role) {
    recoveryError.value = 'Por favor, selecciona tu rol';
    return;
  }
  if (!recoveryForm.value.registro) {
    recoveryError.value = 'Por favor, ingresa tu registro';
    return;
  }
  if (!recoveryForm.value.email) {
    recoveryError.value = 'Por favor, ingresa tu correo electrónico';
    return;
  }

  isRecoveryLoading.value = true;
  recoveryError.value = '';

  try {
    await axios.post(route('postulacion.requestPasswordRecovery'), {
      role: recoveryForm.value.role,
      registro: recoveryForm.value.registro,
      email: recoveryForm.value.email,
    });

    recoveryStep.value = 2;
  } catch (error) {
    if (error.response?.data?.error) {
      recoveryError.value = error.response.data.error;
    } else {
      recoveryError.value = 'Error al enviar el código. Por favor, intenta nuevamente.';
    }
  } finally {
    isRecoveryLoading.value = false;
  }
};

const verifyRecoveryCode = async () => {
  if (!recoveryForm.value.verification_code || recoveryForm.value.verification_code.length !== 6) {
    recoveryError.value = 'Por favor, ingresa un código válido de 6 dígitos';
    return;
  }

  isRecoveryLoading.value = true;
  recoveryError.value = '';

  try {
    const response = await axios.post(route('postulacion.verifyRecoveryCode'), {
      email: recoveryForm.value.email,
      verification_code: recoveryForm.value.verification_code,
    });

    recoveryForm.value.reset_token = response.data.reset_token;
    recoveryStep.value = 3;
  } catch (error) {
    if (error.response?.data?.error) {
      recoveryError.value = error.response.data.error;
    } else {
      recoveryError.value = 'Error al verificar el código. Por favor, intenta nuevamente.';
    }
  } finally {
    isRecoveryLoading.value = false;
  }
};

const resetPasswordSubmit = async () => {
  if (!recoveryForm.value.new_password) {
    recoveryError.value = 'Por favor, ingresa una nueva contraseña';
    return;
  }

  if (recoveryForm.value.new_password.length < 4) {
    recoveryError.value = 'La contraseña debe tener mínimo 4 caracteres';
    return;
  }

  if (recoveryForm.value.new_password !== recoveryForm.value.confirm_password) {
    recoveryError.value = 'Las contraseñas no coinciden';
    return;
  }

  isRecoveryLoading.value = true;
  recoveryError.value = '';

  try {
    const response = await axios.post(route('postulacion.resetPassword'), {
      reset_token: recoveryForm.value.reset_token,
      new_password: recoveryForm.value.new_password,
      confirm_password: recoveryForm.value.confirm_password,
    });

    alert('Contraseña restablecida exitosamente. Por favor, inicia sesión con tu nueva contraseña.');
    closeRecoveryModal();
  } catch (error) {
    if (error.response?.data?.error) {
      recoveryError.value = error.response.data.error;
    } else {
      recoveryError.value = 'Error al restablecer la contraseña. Por favor, intenta nuevamente.';
    }
  } finally {
    isRecoveryLoading.value = false;
  }
};
</script>
