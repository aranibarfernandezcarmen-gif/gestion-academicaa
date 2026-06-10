<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center py-16 px-4">
    <div class="max-w-3xl w-full bg-white rounded-3xl shadow-2xl border border-slate-200 p-10">
      <div class="flex items-center gap-4 mb-8">
        <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png" alt="Escudo FICCT" class="w-20 h-20 rounded-full bg-white p-2 shadow" />
        <div>
          <h1 class="text-2xl font-bold text-blue-900">¡Inscripción exitosa!</h1>
          <p class="text-sm text-slate-600">Cursos preuniversitarios CUP</p>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6">
          <p class="text-slate-700">{{ message }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="rounded-3xl border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Tu número de registro</p>
            <p class="mt-2 text-xl font-semibold text-blue-900">{{ registro }}</p>
          </div>
          <div class="rounded-3xl border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Tu CI para iniciar sesión</p>
            <p class="mt-2 text-xl font-semibold text-blue-900">{{ ci }}</p>
          </div>
        </div>

        <div class="rounded-3xl border border-orange-200 bg-orange-50 p-6">
          <p class="font-semibold text-orange-900">📧 Atención...</p>
          <p class="mt-2 text-sm text-slate-700">Se enviara un correo electrónico con tus credenciales de acceso, posteriormente al completar el proceso de pago...</p>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:justify-end">
          <Link href="/postularse/ingresar" class="px-6 py-3 bg-yellow-400 text-blue-900 rounded-xl font-semibold text-center">Ingresar</Link>
          <Link href="/" class="px-6 py-3 border border-slate-300 rounded-xl text-slate-700 text-center">Volver al inicio</Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  message: String,
  registro: String,
  ci: String,
});

// Si los datos no vienen en props, los lee de sessionStorage (para pago físico)
const message = computed(() => {
  return props.message || sessionStorage.getItem('success') || 'Tu inscripción ha sido procesada exitosamente.';
});

// Ignora las cadenas literales 'undefined'/'null' guardadas por error en sessionStorage
const cleanStore = (key) => {
  const v = sessionStorage.getItem(key);
  return (v && v !== 'undefined' && v !== 'null') ? v : '';
};

const registro = computed(() => {
  return props.registro || cleanStore('registro') || 'N/A';
});

const ci = computed(() => {
  return props.ci || cleanStore('ci') || 'N/A';
});
</script>
