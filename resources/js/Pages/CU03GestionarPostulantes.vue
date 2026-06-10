<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold">CU03 - Gestionar Postulantes</h1>
            <p class="text-blue-200 mt-2">Gestiona postulantes con CRUD completo y validación de datos únicos.</p>
          </div>
          <button @click="volver" type="button" class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition">
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-10 space-y-10">

      <!-- Alerta de rechazo -->
      <div v-if="alertaRechazo" class="bg-red-50 border border-red-300 rounded-2xl p-6 flex gap-4">
        <div class="text-red-500 text-2xl">⚠</div>
        <div class="flex-1">
          <p class="font-bold text-red-800 text-lg">Postulante registrado con estado RECHAZADO</p>
          <p class="text-red-700 text-sm mt-1">Registro: <strong>{{ alertaRechazo.registro }}</strong> · Se envió un correo al postulante con las observaciones.</p>
          <ul class="mt-3 space-y-1">
            <li v-for="obs in alertaRechazo.observaciones" :key="obs" class="text-red-700 text-sm flex gap-2">
              <span>•</span><span>{{ obs }}</span>
            </li>
          </ul>
        </div>
        <button @click="alertaRechazo = null" class="text-red-400 hover:text-red-600 text-xl font-bold self-start">×</button>
      </div>

      <!-- Formulario nuevo postulante -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <h2 class="text-2xl font-bold">Registrar Postulante</h2>
          <button @click="cargarPostulantes" class="rounded-full border border-slate-300 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100 transition">
            Actualizar lista
          </button>
        </div>

        <!-- Selector tipo de postulante -->
        <div class="flex flex-wrap gap-2 mb-6">
          <button type="button" @click="cambiarTipo('nuevo')"
            :class="tipoPostulante === 'nuevo' ? 'bg-blue-600 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            class="px-5 py-2 rounded-xl font-semibold text-sm transition">
            + Postulante Nuevo
          </button>
          <button type="button" @click="cambiarTipo('antiguo')"
            :class="tipoPostulante === 'antiguo' ? 'bg-purple-600 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            class="px-5 py-2 rounded-xl font-semibold text-sm transition">
            ↩ Re-postulante (período anterior)
          </button>
        </div>

        <!-- Búsqueda de postulante antiguo -->
        <div v-if="tipoPostulante === 'antiguo'" class="mb-6 p-5 bg-purple-50 border border-purple-200 rounded-2xl">
          <p class="text-sm font-semibold text-purple-800 mb-1">Ingresa el número de registro del período anterior:</p>
          <p class="text-xs text-purple-600 mb-3">Solo aplica para postulantes que <strong>reprobaron</strong> en una gestión anterior (promedio menor a 60 en alguna materia).</p>
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
            <span>Datos cargados — Registro <strong>{{ datosCargados.registro }}</strong>: {{ datosCargados.nombreCompleto }}. Actualiza la información de contacto y las opciones de carrera si es necesario.</span>
          </div>
        </div>

        <form @submit.prevent="guardarPostulante" class="grid gap-6 lg:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">CI *
              <span v-if="tipoPostulante === 'nuevo'" class="text-xs text-slate-500 font-normal">(debe ser único)</span>
              <span v-else class="text-xs text-purple-600 font-normal">(del período anterior)</span>
            </label>
            <input v-model="nuevo.ci" type="text"
              :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.ci" class="mt-1 text-sm text-red-600">{{ errors.ci }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Nombre *</label>
            <input v-model="nuevo.nombre" type="text"
              :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Apellido *</label>
            <input v-model="nuevo.apellido" type="text"
              :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.apellido" class="mt-1 text-sm text-red-600">{{ errors.apellido }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Fecha de Nacimiento *</label>
            <input v-model="nuevo.fecha_nacimiento" type="date"
              :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.fecha_nacimiento" class="mt-1 text-sm text-red-600">{{ errors.fecha_nacimiento }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Sexo *</label>
            <select v-model="nuevo.sexo"
              :disabled="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl">
              <option value="">-- Seleccione --</option>
              <option value="M">Masculino</option>
              <option value="F">Femenino</option>
            </select>
            <p v-if="errors.sexo" class="mt-1 text-sm text-red-600">{{ errors.sexo }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Teléfono</label>
            <input v-model="nuevo.telefono" type="tel" class="w-full px-4 py-3 border border-slate-300 rounded-xl" placeholder="Solo dígitos" />
            <p v-if="errors.telefono" class="mt-1 text-sm text-red-600">{{ errors.telefono }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Email *</label>
            <input v-model="nuevo.correo_electronico" type="email" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.correo_electronico" class="mt-1 text-sm text-red-600">{{ errors.correo_electronico }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Ciudad *</label>
            <input v-model="nuevo.ciudad" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.ciudad" class="mt-1 text-sm text-red-600">{{ errors.ciudad }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Dirección *</label>
            <input v-model="nuevo.direccion" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.direccion" class="mt-1 text-sm text-red-600">{{ errors.direccion }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Colegio de Procedencia *</label>
            <input v-model="nuevo.colegio_procedencia" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
            <p v-if="errors.colegio_procedencia" class="mt-1 text-sm text-red-600">{{ errors.colegio_procedencia }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Nro. Título Bachiller *
              <span v-if="tipoPostulante === 'nuevo'" class="text-xs text-slate-500 font-normal">(debe ser único)</span>
              <span v-else class="text-xs text-purple-600 font-normal">(del período anterior)</span>
            </label>
            <input v-model="nuevo.titulo_bachiller" type="text"
              :readonly="tipoPostulante === 'antiguo' && !!datosCargados"
              :class="tipoPostulante === 'antiguo' && datosCargados ? 'bg-slate-100 cursor-not-allowed' : ''"
              class="w-full px-4 py-3 border border-slate-300 rounded-xl" placeholder="Ej: TB-2024-001" />
            <p v-if="errors.titulo_bachiller" class="mt-1 text-sm text-red-600">{{ errors.titulo_bachiller }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">1ra Opción de Carrera *</label>
            <select v-model="nuevo.carrera_primera_opcion_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
              <option value="">-- Seleccione --</option>
              <option v-for="carrera in carreras" :key="carrera.codigo" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
            </select>
            <p v-if="errors.carrera_primera_opcion_id" class="mt-1 text-sm text-red-600">{{ errors.carrera_primera_opcion_id }}</p>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">2da Opción de Carrera * <span class="text-xs text-slate-500 font-normal">(obligatoria, distinta a 1ra)</span></label>
            <select v-model="nuevo.carrera_segunda_opcion_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
              <option value="">-- Seleccione --</option>
              <option v-for="carrera in carreras" :key="`segunda-${carrera.codigo}`" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
            </select>
            <p v-if="errors.carrera_segunda_opcion_id" class="mt-1 text-sm text-red-600">{{ errors.carrera_segunda_opcion_id }}</p>
          </div>

          <div class="lg:col-span-2 flex justify-end gap-3 pt-3">
            <button type="submit" :disabled="guardando" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 disabled:opacity-50 transition flex items-center gap-2">
              <span v-if="guardando" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ guardando ? 'Registrando...' : (tipoPostulante === 'antiguo' ? 'Re-registrar Postulante' : 'Registrar Postulante') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Buscador -->
      <div class="bg-white rounded-2xl shadow border border-slate-200 px-6 py-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="busqueda" type="text" placeholder="Buscar por nombre, apellido, CI o registro..."
          class="flex-1 outline-none text-sm text-slate-700 placeholder-slate-400" />
        <button v-if="busqueda" @click="busqueda = ''" class="text-slate-400 hover:text-slate-600 text-xs font-semibold">Limpiar</button>
      </div>

      <!-- Postulantes Registrados (Pagado) -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold text-green-600">Postulantes Registrados</h2>
            <p class="text-slate-600">Postulantes que completaron el pago.</p>
          </div>
        </div>
        <div v-if="registradosFiltrados.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-600">
          {{ busqueda ? 'Sin resultados para "' + busqueda + '".' : 'No hay postulantes registrados.' }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-slate-100 text-slate-700">
                <th class="px-4 py-3 border">Registro</th>
                <th class="px-4 py-3 border">CI</th>
                <th class="px-4 py-3 border">Nombre Completo</th>
                <th class="px-4 py-3 border">Título Bach.</th>
                <th class="px-4 py-3 border">1ra Opción</th>
                <th class="px-4 py-3 border">2da Opción</th>
                <th class="px-4 py-3 border">Estado</th>
                <th class="px-4 py-3 border">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in registradosFiltrados" :key="p.id" class="border-b hover:bg-slate-50">
                <td class="px-4 py-3 border font-mono">{{ p.registro }}</td>
                <td class="px-4 py-3 border">{{ p.ci }}</td>
                <td class="px-4 py-3 border">{{ p.nombre }} {{ p.apellido }}</td>
                <td class="px-4 py-3 border">{{ p.titulo_bachiller }}</td>
                <td class="px-4 py-3 border">{{ p.primera_opcion || '-' }}</td>
                <td class="px-4 py-3 border">{{ p.segunda_opcion || '-' }}</td>
                <td class="px-4 py-3 border"><span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">{{ p.estado_pago }}</span></td>
                <td class="px-4 py-3 border space-x-2">
                  <button @click="editarPostulante(p)" class="px-3 py-1.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-xs font-semibold">Editar</button>
                  <button @click="eliminarPostulante(p.id)" class="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 text-xs font-semibold">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Postulantes Pendientes -->
      <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold text-amber-600">Postulantes Pendientes de Pago</h2>
            <p class="text-slate-600">Postulantes que aún no completaron el pago.</p>
          </div>
        </div>
        <div v-if="pendientesFiltrados.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-600">
          {{ busqueda ? 'Sin resultados para "' + busqueda + '".' : 'No hay postulantes pendientes de pago.' }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-amber-50 text-slate-700">
                <th class="px-4 py-3 border">Registro</th>
                <th class="px-4 py-3 border">CI</th>
                <th class="px-4 py-3 border">Nombre Completo</th>
                <th class="px-4 py-3 border">Título Bach.</th>
                <th class="px-4 py-3 border">1ra Opción</th>
                <th class="px-4 py-3 border">2da Opción</th>
                <th class="px-4 py-3 border">Estado Pago</th>
                <th class="px-4 py-3 border">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in pendientesFiltrados" :key="p.id" class="border-b hover:bg-amber-50">
                <td class="px-4 py-3 border font-mono">{{ p.registro }}</td>
                <td class="px-4 py-3 border">{{ p.ci }}</td>
                <td class="px-4 py-3 border">{{ p.nombre }} {{ p.apellido }}</td>
                <td class="px-4 py-3 border">{{ p.titulo_bachiller }}</td>
                <td class="px-4 py-3 border">{{ p.primera_opcion || '-' }}</td>
                <td class="px-4 py-3 border">{{ p.segunda_opcion || '-' }}</td>
                <td class="px-4 py-3 border"><span class="px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs font-semibold">{{ p.estado_pago }}</span></td>
                <td class="px-4 py-3 border space-x-2">
                  <button @click="editarPostulante(p)" class="px-3 py-1.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-xs font-semibold">Editar</button>
                  <button @click="eliminarPostulante(p.id)" class="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 text-xs font-semibold">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Postulantes Rechazados -->
      <div class="bg-white rounded-3xl shadow-xl border border-red-200 p-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-bold text-red-600">Postulantes Rechazados</h2>
            <p class="text-slate-600">Postulantes con datos duplicados o inconsistencias. Se les notificó por correo.</p>
          </div>
          <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">{{ rechazadosFiltrados.length }} registros</span>
        </div>
        <div v-if="rechazadosFiltrados.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-8 text-slate-600">
          {{ busqueda ? 'Sin resultados para "' + busqueda + '".' : 'No hay postulantes rechazados.' }}
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-sm">
            <thead>
              <tr class="bg-red-50 text-slate-700">
                <th class="px-4 py-3 border">Registro</th>
                <th class="px-4 py-3 border">CI</th>
                <th class="px-4 py-3 border">Nombre Completo</th>
                <th class="px-4 py-3 border">Título Bach.</th>
                <th class="px-4 py-3 border">1ra Opción</th>
                <th class="px-4 py-3 border">2da Opción</th>
                <th class="px-4 py-3 border">Correo</th>
                <th class="px-4 py-3 border">Observaciones</th>
                <th class="px-4 py-3 border">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in rechazadosFiltrados" :key="p.id" class="border-b hover:bg-red-50 bg-red-50/30">
                <td class="px-4 py-3 border font-mono text-red-700 font-bold">{{ p.registro }}</td>
                <td class="px-4 py-3 border">{{ p.ci }}</td>
                <td class="px-4 py-3 border font-semibold">{{ p.nombre }} {{ p.apellido }}</td>
                <td class="px-4 py-3 border">{{ p.titulo_bachiller }}</td>
                <td class="px-4 py-3 border">{{ p.primera_opcion || '-' }}</td>
                <td class="px-4 py-3 border">{{ p.segunda_opcion || '-' }}</td>
                <td class="px-4 py-3 border text-xs text-slate-600">{{ p.correo_electronico }}</td>
                <td class="px-4 py-3 border max-w-xs">
                  <div class="text-xs text-red-700 space-y-1">
                    <p v-for="obs in (p.observaciones_rechazo || '').split(' | ')" :key="obs" class="flex gap-1">
                      <span class="shrink-0">•</span><span>{{ obs }}</span>
                    </p>
                  </div>
                </td>
                <td class="px-4 py-3 border space-x-2">
                  <button @click="editarPostulante(p)" class="px-3 py-1.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 text-xs font-semibold">Editar</button>
                  <button @click="eliminarPostulante(p.id)" class="px-3 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 text-xs font-semibold">Eliminar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Modal Editar -->
    <div v-if="editMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4 py-6">
      <div class="w-full max-w-3xl rounded-3xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
        <div class="bg-blue-600 px-6 py-4 text-white">
          <div class="flex items-center justify-between gap-4">
            <div>
              <h2 class="text-2xl font-bold">Editar Postulante</h2>
              <p class="text-slate-100 text-sm">Solo puedes modificar dirección, teléfono, colegio y opciones.</p>
            </div>
            <button @click="cancelarEdicion" class="rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition">Cerrar</button>
          </div>
        </div>
        <div class="max-h-[calc(100vh-6rem)] overflow-y-auto p-8 bg-white">
          <div class="mt-2 grid gap-6 lg:grid-cols-2">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Registro</label>
              <input type="text" :value="postulanteEdit.registro" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-100" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">CI</label>
              <input type="text" :value="postulanteEdit.ci" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-100" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Nombre</label>
              <input type="text" :value="postulanteEdit.nombre" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-100" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Apellido</label>
              <input type="text" :value="postulanteEdit.apellido" readonly class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-slate-100" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Dirección *</label>
              <input v-model="postulanteEdit.direccion" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
              <p v-if="errors.direccion" class="mt-1 text-sm text-red-600">{{ errors.direccion }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Teléfono</label>
              <input v-model="postulanteEdit.telefono" type="tel" class="w-full px-4 py-3 border border-slate-300 rounded-xl" placeholder="Solo dígitos" />
              <p v-if="errors.telefono" class="mt-1 text-sm text-red-600">{{ errors.telefono }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Email *</label>
              <input v-model="postulanteEdit.correo_electronico" type="email" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
              <p v-if="errors.correo_electronico" class="mt-1 text-sm text-red-600">{{ errors.correo_electronico }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Ciudad *</label>
              <input v-model="postulanteEdit.ciudad" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
              <p v-if="errors.ciudad" class="mt-1 text-sm text-red-600">{{ errors.ciudad }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Colegio *</label>
              <input v-model="postulanteEdit.colegio_procedencia" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
              <p v-if="errors.colegio_procedencia" class="mt-1 text-sm text-red-600">{{ errors.colegio_procedencia }}</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">Nro. Título Bachiller *</label>
              <input v-model="postulanteEdit.titulo_bachiller" type="text" class="w-full px-4 py-3 border border-slate-300 rounded-xl" />
              <p v-if="errors.titulo_bachiller" class="mt-1 text-sm text-red-600">{{ errors.titulo_bachiller }}</p>
            </div>
            <div class="lg:col-span-2">
              <label class="block text-sm font-semibold text-slate-900 mb-2">1ra Opción de Carrera *</label>
              <select v-model="postulanteEdit.carrera_primera_opcion_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
                <option value="">-- Seleccione --</option>
                <option v-for="carrera in carreras" :key="`edit1-${carrera.codigo}`" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
              </select>
              <p v-if="errors.carrera_primera_opcion_id" class="mt-1 text-sm text-red-600">{{ errors.carrera_primera_opcion_id }}</p>
            </div>
            <div class="lg:col-span-2">
              <label class="block text-sm font-semibold text-slate-900 mb-2">2da Opción de Carrera</label>
              <select v-model="postulanteEdit.carrera_segunda_opcion_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl">
                <option value="">-- Ninguna --</option>
                <option v-for="carrera in carreras" :key="`edit2-${carrera.codigo}`" :value="carrera.codigo">{{ carrera.nombre_carrera }}</option>
              </select>
              <p v-if="errors.carrera_segunda_opcion_id" class="mt-1 text-sm text-red-600">{{ errors.carrera_segunda_opcion_id }}</p>
            </div>
          </div>
          <div class="mt-8 flex justify-end gap-3">
            <button @click="cancelarEdicion" type="button" class="px-6 py-3 bg-slate-200 text-slate-900 rounded-xl hover:bg-slate-300">Cancelar</button>
            <button @click="guardarEdicion" type="button" class="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700">Guardar Cambios</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  carreras: Array,
});

const postulantesRegistrados = ref([]);
const postulantesPendientes  = ref([]);
const postulantesRechazados  = ref([]);
const busqueda  = ref('');
const editMode  = ref(false);
const guardando = ref(false);
const alertaRechazo = ref(null);

// Re-postulación
const tipoPostulante    = ref('nuevo'); // 'nuevo' | 'antiguo'
const registroBusqueda  = ref('');
const buscandoAntiguo   = ref(false);
const datosCargados     = ref(null);   // { registro, nombreCompleto } | null
const idPersonaAnterior = ref(null);

const filtrar = (lista) => {
  const q = busqueda.value.trim().toLowerCase();
  if (!q) return lista;
  return lista.filter(p =>
    (p.registro  || '').toLowerCase().includes(q) ||
    (p.ci        || '').toLowerCase().includes(q) ||
    (p.nombre    || '').toLowerCase().includes(q) ||
    (p.apellido  || '').toLowerCase().includes(q)
  );
};

const registradosFiltrados = computed(() => filtrar(postulantesRegistrados.value));
const pendientesFiltrados  = computed(() => filtrar(postulantesPendientes.value));
const rechazadosFiltrados  = computed(() => filtrar(postulantesRechazados.value));

const errors = reactive({});

const nuevo = reactive({
  ci: '', nombre: '', apellido: '', fecha_nacimiento: '', sexo: '',
  direccion: '', telefono: '', correo_electronico: '', ciudad: '',
  colegio_procedencia: '', titulo_bachiller: '',
  carrera_primera_opcion_id: '', carrera_segunda_opcion_id: '',
});

const postulanteEdit = reactive({
  id: null, registro: '', ci: '', nombre: '', apellido: '',
  direccion: '', telefono: '', correo_electronico: '', ciudad: '',
  colegio_procedencia: '', titulo_bachiller: '',
  carrera_primera_opcion_id: '', carrera_segunda_opcion_id: '',
});

const limpiarFormulario = () => {
  Object.assign(nuevo, {
    ci: '', nombre: '', apellido: '', fecha_nacimiento: '', sexo: '',
    direccion: '', telefono: '', correo_electronico: '', ciudad: '',
    colegio_procedencia: '', titulo_bachiller: '',
    carrera_primera_opcion_id: '', carrera_segunda_opcion_id: '',
  });
  datosCargados.value     = null;
  idPersonaAnterior.value = null;
  registroBusqueda.value  = '';
};

const cambiarTipo = (tipo) => {
  tipoPostulante.value = tipo;
  limpiarFormulario();
  limpiarErrores();
};

const buscarPostulanteAntiguo = async () => {
  const reg = registroBusqueda.value.trim().toUpperCase();
  if (!reg) { alert('Ingresa un número de registro.'); return; }
  buscandoAntiguo.value   = true;
  datosCargados.value     = null;
  idPersonaAnterior.value = null;
  try {
    const res = await axios.get('/api/postulante/buscar?registro=' + reg);
    const p   = res.data;
    Object.assign(nuevo, {
      ci:                        p.ci,
      nombre:                    p.nombre,
      apellido:                  p.apellido,
      fecha_nacimiento:          p.fecha_nacimiento,
      sexo:                      p.sexo,
      direccion:                 p.direccion             || '',
      telefono:                  p.telefono              || '',
      correo_electronico:        p.correo_electronico    || '',
      ciudad:                    p.ciudad                || '',
      colegio_procedencia:       p.colegio_procedencia   || '',
      titulo_bachiller:          p.titulo_bachiller       || '',
      carrera_primera_opcion_id: p.carrera_primera_opcion_id || '',
      carrera_segunda_opcion_id: p.carrera_segunda_opcion_id || '',
    });
    idPersonaAnterior.value = p.id_persona;
    datosCargados.value     = { registro: p.registro, nombreCompleto: p.nombre + ' ' + p.apellido };
  } catch (e) {
    const msg = e.response?.data?.error || 'No se encontró el registro.';
    alert('Error: ' + msg);
  } finally {
    buscandoAntiguo.value = false;
  }
};

const limpiarErrores = () => {
  Object.keys(errors).forEach(k => { errors[k] = ''; });
};

const validarPostulante = (datos, esEdicion = false) => {
  limpiarErrores();
  if (!esEdicion && !datos.ci?.trim())             errors.ci = 'El CI es requerido.';
  if (!esEdicion && datos.ci?.length > 20)         errors.ci = 'El CI no puede exceder 20 caracteres.';
  if (!datos.nombre?.trim())                       errors.nombre = 'El nombre es requerido.';
  if (!datos.apellido?.trim())                     errors.apellido = 'El apellido es requerido.';
  if (!esEdicion && !datos.fecha_nacimiento)       errors.fecha_nacimiento = 'La fecha de nacimiento es requerida.';
  if (!esEdicion && !datos.sexo)                   errors.sexo = 'El sexo es requerido.';
  if (!datos.direccion?.trim())                    errors.direccion = 'La dirección es requerida.';
  if (!datos.correo_electronico?.trim())           errors.correo_electronico = 'El correo es requerido.';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(datos.correo_electronico))
                                                   errors.correo_electronico = 'Correo con formato inválido.';
  if (!datos.ciudad?.trim())                       errors.ciudad = 'La ciudad es requerida.';
  if (!datos.colegio_procedencia?.trim())          errors.colegio_procedencia = 'El colegio es requerido.';
  if (!datos.titulo_bachiller?.trim())             errors.titulo_bachiller = 'El Nro. Título Bachiller es requerido.';
  if (!datos.carrera_primera_opcion_id)            errors.carrera_primera_opcion_id = 'Debe seleccionar la 1ra opción.';
  if (!esEdicion && !datos.carrera_segunda_opcion_id)
                                                   errors.carrera_segunda_opcion_id = 'Debe seleccionar la 2da opción.';
  if (!esEdicion && datos.carrera_primera_opcion_id && datos.carrera_segunda_opcion_id &&
      datos.carrera_primera_opcion_id == datos.carrera_segunda_opcion_id)
                                                   errors.carrera_segunda_opcion_id = 'La 2da opción debe ser diferente a la 1ra.';
  if (datos.telefono && !/^\d{7,10}$/.test(datos.telefono))
                                                   errors.telefono = 'El teléfono debe tener entre 7 y 10 dígitos.';
  return Object.values(errors).every(v => !v);
};

const cargarPostulantes = async () => {
  try {
    const [r1, r2, r3] = await Promise.all([
      axios.get('/cu03/postulantes?estado=Pagado'),
      axios.get('/cu03/postulantes?estado=Pendiente'),
      axios.get('/cu03/postulantes?estado=Rechazado'),
    ]);
    postulantesRegistrados.value = r1.data;
    postulantesPendientes.value  = r2.data;
    postulantesRechazados.value  = r3.data;
  } catch (e) {
    console.error('Error cargando postulantes:', e);
  }
};

const guardarPostulante = async () => {
  if (!validarPostulante(nuevo)) return;
  guardando.value = true;
  alertaRechazo.value = null;
  try {
    const res = await axios.post('/cu03/postulantes', {
      ...nuevo,
      es_repostulacion:    tipoPostulante.value === 'antiguo',
      id_persona_anterior: idPersonaAnterior.value,
    });
    if (res.data.rechazado) {
      alertaRechazo.value = {
        registro: res.data.registro,
        observaciones: res.data.observaciones,
      };
    } else {
      alert('Postulante registrado correctamente. Registro: ' + res.data.registro);
    }
    limpiarFormulario();
    await cargarPostulantes();
  } catch (e) {
    if (e.response?.status === 422 && e.response.data.errors) {
      Object.assign(errors, e.response.data.errors);
      return;
    }
    alert('Error al registrar postulante: ' + (e.response?.data?.message || e.message));
  } finally {
    guardando.value = false;
  }
};

const editarPostulante = (p) => {
  limpiarErrores();
  editMode.value = true;
  Object.assign(postulanteEdit, {
    id: p.id, registro: p.registro, ci: p.ci,
    nombre: p.nombre, apellido: p.apellido,
    direccion: p.direccion || '', telefono: p.telefono || '',
    correo_electronico: p.correo_electronico || '',
    ciudad: p.ciudad || '', colegio_procedencia: p.colegio_procedencia || '',
    titulo_bachiller: p.titulo_bachiller || '',
    carrera_primera_opcion_id: p.carrera_primera_opcion_id || '',
    carrera_segunda_opcion_id: p.carrera_segunda_opcion_id || '',
  });
};

const cancelarEdicion = () => {
  editMode.value = false;
  Object.assign(postulanteEdit, {
    id: null, registro: '', ci: '', nombre: '', apellido: '',
    direccion: '', telefono: '', correo_electronico: '', ciudad: '',
    colegio_procedencia: '', titulo_bachiller: '',
    carrera_primera_opcion_id: '', carrera_segunda_opcion_id: '',
  });
  limpiarErrores();
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

const guardarEdicion = async () => {
  if (!validarPostulante(postulanteEdit, true)) return;
  try {
    await axios.patch(`/cu03/postulante/${postulanteEdit.id}`, {
      direccion: postulanteEdit.direccion,
      telefono: postulanteEdit.telefono,
      correo_electronico: postulanteEdit.correo_electronico,
      ciudad: postulanteEdit.ciudad,
      colegio_procedencia: postulanteEdit.colegio_procedencia,
      titulo_bachiller: postulanteEdit.titulo_bachiller,
      carrera_primera_opcion_id: postulanteEdit.carrera_primera_opcion_id,
      carrera_segunda_opcion_id: postulanteEdit.carrera_segunda_opcion_id,
    });
    alert('Postulante actualizado correctamente.');
    cancelarEdicion();
    await cargarPostulantes();
  } catch (e) {
    if (e.response?.status === 422 && e.response.data.errors) {
      Object.assign(errors, e.response.data.errors);
      return;
    }
    alert('Error al actualizar postulante: ' + (e.response?.data?.message || e.message));
  }
};

const eliminarPostulante = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar este postulante?')) return;
  try {
    await axios.delete(`/cu03/postulante/${id}`);
    alert('Postulante eliminado correctamente.');
    await cargarPostulantes();
  } catch (e) {
    alert('Error al eliminar postulante: ' + (e.response?.data?.message || e.message));
  }
};

onMounted(() => { cargarPostulantes(); });
</script>
