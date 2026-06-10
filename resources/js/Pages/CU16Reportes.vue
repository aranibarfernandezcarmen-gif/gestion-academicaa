<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-emerald-700 to-emerald-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold">CU16 - Gestión de Reportes</h1>
          <p class="mt-1 text-emerald-200">Genera y consulta reportes del proceso académico.</p>
        </div>
        <button @click="volver" class="px-4 py-2 bg-white text-emerald-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">Volver</button>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-8">

      <!-- Tarjetas resumen -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-slate-400">
          <p class="text-slate-500 text-xs font-semibold uppercase">Total reportes</p>
          <p class="text-3xl font-bold text-slate-900">{{ estadisticas.total }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-green-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Generados</p>
          <p class="text-3xl font-bold text-green-600">{{ estadisticas.generado }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-blue-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Enviados</p>
          <p class="text-3xl font-bold text-blue-600">{{ estadisticas.enviado }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow p-5 border-l-4 border-yellow-500">
          <p class="text-slate-500 text-xs font-semibold uppercase">Archivados</p>
          <p class="text-3xl font-bold text-yellow-600">{{ estadisticas.archivado }}</p>
        </div>
      </div>

      <!-- Generar nuevo reporte -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Generar Nuevo Reporte</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Tipo de Reporte</label>
            <select v-model="form.tipo_reporte" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500">
              <option value="">-- Seleccionar tipo --</option>
              <option value="Calificaciones">Calificaciones</option>
              <option value="Postulantes">Postulantes</option>
              <option value="Grupos">Grupos</option>
              <option value="Asistencia">Asistencia</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Formato</label>
            <select v-model="form.formato" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500">
              <option value="PDF">PDF</option>
              <option value="Excel">Excel</option>
              <option value="CSV">CSV</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">Descripción (opcional)</label>
            <input v-model="form.descripcion" type="text" placeholder="Breve descripción..."
              class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500" />
          </div>
        </div>
        <button @click="generarReporte" :disabled="guardando || !form.tipo_reporte"
          class="px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold text-sm hover:bg-emerald-700 disabled:opacity-50 transition flex items-center gap-2">
          <span v-if="guardando" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          {{ guardando ? 'Generando...' : '⬇ Exportar y Descargar' }}
        </button>
      </section>

      <!-- Historial de reportes -->
      <section class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <h2 class="text-2xl font-bold mb-6">Historial de Reportes</h2>
        <div v-if="reportesLocal.length === 0" class="text-center py-10 text-slate-400">No hay reportes generados.</div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Cód.</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Tipo</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Fecha</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Formato</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Registros</th>
                <th class="px-4 py-3 text-center font-semibold text-slate-700">Estado</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Generado por</th>
                <th class="px-4 py-3 text-left font-semibold text-slate-700">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-for="r in reportesLocal" :key="r.codigo" class="hover:bg-slate-50">
                <td class="px-4 py-3 font-mono text-slate-600">{{ r.codigo }}</td>
                <td class="px-4 py-3 font-semibold text-slate-900">{{ r.tipo_reporte }}</td>
                <td class="px-4 py-3 text-slate-600">{{ r.fecha_generacion }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">{{ r.formato }}</span>
                </td>
                <td class="px-4 py-3 text-center text-slate-700">{{ r.cantidad_registros }}</td>
                <td class="px-4 py-3 text-center">
                  <span :class="estadoBadge(r.estado)" class="px-2 py-1 rounded-full text-xs font-semibold">{{ r.estado }}</span>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">{{ r.generado_por }}</td>
                <td class="px-4 py-3">
                  <button @click="eliminar(r.codigo)" class="px-3 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-lg hover:bg-red-200 transition">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({
  reportes:     Array,
  estadisticas: Object,
});

const reportesLocal = ref([...props.reportes]);
const guardando     = ref(false);
const form = reactive({ tipo_reporte: '', formato: 'PDF', descripcion: '' });

const estadoBadge = (estado) => {
  if (estado === 'generado')  return 'bg-green-100 text-green-700';
  if (estado === 'enviado')   return 'bg-blue-100 text-blue-700';
  if (estado === 'visto')     return 'bg-slate-100 text-slate-600';
  if (estado === 'archivado') return 'bg-yellow-100 text-yellow-700';
  return 'bg-slate-100 text-slate-600';
};

const volver = () => {
  const params   = new URLSearchParams(window.location.search);
  const registro = params.get('registro');
  const role     = params.get('role');
  if (registro && role) {
    window.location.href = `/postularse/entrada?registro=${registro}&role=${role}`;
  } else {
    window.history.back();
  }
};

const generarReporte = async () => {
  if (!form.tipo_reporte) return;
  guardando.value = true;
  try {
    // Descarga binaria con axios (funciona para PDF, Excel y CSV)
    const response = await axios.post('/cu16/exportar', { ...form, id_persona: 1 }, {
      responseType: 'blob',
    });

    // Crear enlace de descarga
    const contentDisposition = response.headers['content-disposition'] || '';
    const match = contentDisposition.match(/filename="?([^"]+)"?/);
    const filename = match ? match[1] : 'reporte.' + form.formato.toLowerCase();

    const url  = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href  = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    // Recargar historial después de breve pausa
    setTimeout(() => window.location.reload(), 800);
  } catch (e) {
    if (e.response?.data instanceof Blob) {
      const text = await e.response.data.text();
      try { alert('Error: ' + JSON.parse(text).message); } catch { alert('Error al generar el reporte.'); }
    } else {
      alert('Error: ' + (e.response?.data?.message || e.message));
    }
  } finally {
    guardando.value = false;
  }
};

const eliminar = async (codigo) => {
  if (!confirm('¿Eliminar este reporte?')) return;
  try {
    await axios.delete(`/cu16/reportes/${codigo}`);
    reportesLocal.value = reportesLocal.value.filter(r => r.codigo !== codigo);
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message));
  }
};
</script>
