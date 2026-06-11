<template>
  <div>

    <!-- ================================================================ -->
    <!--  PORTAL POSTULANTE — estilo Moodle                               -->
    <!-- ================================================================ -->
    <div v-if="role === 'Postulante'" class="min-h-screen bg-gray-100">

      <!-- ── Barra de navegación superior ── -->
      <nav class="bg-[#1d2d4f] text-white shadow-lg print:hidden">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

          <!-- Izquierda: Marca / Logo -->
          <div class="flex items-center gap-3 flex-shrink-0">
            <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png"
              alt="FICCT" class="w-9 h-9 rounded-full bg-white p-1 flex-shrink-0" />
            <div class="hidden sm:block leading-tight">
              <p class="text-[10px] text-blue-300 font-semibold uppercase tracking-wider leading-none">FICCT — UAGRM</p>
              <p class="text-sm font-bold">Cursos Preuniversitarios</p>
            </div>
          </div>

          <!-- Centro: Vínculos de navegación -->
          <div class="flex items-center gap-1">
            <button @click="activePage = 'cursos'"
              :class="activePage === 'cursos' ? 'bg-white/20 text-white' : 'text-blue-200 hover:bg-white/10 hover:text-white'"
              class="px-4 py-2 rounded text-sm font-semibold transition">
              Mis Cursos
            </button>
            <button @click="activePage = 'boleta'"
              :class="activePage === 'boleta' ? 'bg-white/20 text-white' : 'text-blue-200 hover:bg-white/10 hover:text-white'"
              class="px-4 py-2 rounded text-sm font-semibold transition">
              Boleta de Inscripción
            </button>
            <!-- Accesos a CU otorgados por CU02 -->
            <a v-for="acc in accesosOtorgados" :key="acc.codigo"
              :href="obtenerRuta(0, acc.codigo)" :title="acc.nombre"
              class="px-4 py-2 rounded text-sm font-semibold text-blue-200 hover:bg-white/10 hover:text-white transition whitespace-nowrap">
              {{ acc.codigo }}
            </a>
          </div>

          <!-- Derecha: notificaciones + usuario + cerrar sesión -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <div v-if="evaluacionesPendientes.length > 0" class="relative">
              <button @click="activePage = 'cursos'"
                class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-base"
                title="Evaluaciones pendientes">
                🔔
              </button>
              <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full text-[10px] font-bold flex items-center justify-center leading-none">
                {{ evaluacionesPendientes.length }}
              </span>
            </div>
            <div class="text-right hidden md:block leading-tight">
              <p class="text-[10px] text-blue-300 leading-none">{{ registro }}</p>
              <p class="text-xs font-semibold">{{ displayName }}</p>
            </div>
            <button @click="cerrarSesion"
              class="px-3 py-1.5 bg-red-500 hover:bg-red-600 rounded text-xs font-semibold transition whitespace-nowrap">
              Cerrar Sesión
            </button>
          </div>
        </div>
      </nav>

      <!-- ═══ MIS CURSOS ═══ -->
      <div v-if="activePage === 'cursos'" class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-0.5">Mis cursos</h1>
        <p class="text-gray-500 text-sm mb-7">Vista general de curso</p>

        <!-- Sin nada asignado -->
        <div v-if="!data?.grupos?.length && !otrasCalificaciones.length"
          class="bg-white rounded-2xl shadow-md p-12 text-center">
          <div class="text-5xl mb-4">📚</div>
          <h2 class="text-xl font-bold text-gray-600 mb-2">Sin curso asignado</h2>
          <p class="text-gray-400 max-w-sm mx-auto">
            Aún no tienes un grupo asignado. Cuando el administrativo te asigne a un grupo, aquí verás tu materia.
          </p>
        </div>

        <!-- Grilla de tarjetas de cursos -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

          <!-- Tarjetas de cursos activos (uno por materia asignada) -->
          <div v-for="(grupo, idx) in (data?.grupos || [])" :key="grupo.codigo"
            class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-shadow cursor-default">
            <div class="h-36 relative"
              :class="idx === 0 ? 'bg-gradient-to-br from-violet-500 to-indigo-700' : cardGradients[idx % cardGradients.length]">
              <div class="absolute inset-0 flex flex-col justify-end p-4">
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">{{ grupo.sigla }}</p>
                <h3 class="text-white text-base font-bold leading-tight mt-0.5">{{ grupo.nombre_grupo }}</h3>
              </div>
              <span class="absolute top-3 right-3 bg-green-400 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full">
                Activo
              </span>
            </div>
            <div class="p-4">
              <p class="text-sm font-semibold text-gray-800">{{ grupo.nombre_materia }}</p>
              <p class="text-xs text-gray-400 mt-0.5 mb-3 leading-tight">
                {{ data.carrera_asignada || data.carrera_primera || 'Sin carrera asignada' }}
              </p>
              <div class="flex items-center gap-2 mb-2">
                <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs flex-shrink-0">
                  {{ (grupo.docente_nombre || '?').charAt(0).toUpperCase() }}
                </div>
                <p class="text-xs text-gray-600 truncate">
                  {{ grupo.docente_nombre }} {{ grupo.docente_apellido }}
                </p>
              </div>
              <div v-if="grupo.dia" class="flex items-center gap-1.5 text-xs text-gray-400 mb-3">
                <span>📅</span>
                <span>{{ grupo.dia }} · {{ (grupo.hora_inicio || '').slice(0,5) }} – {{ (grupo.hora_fin || '').slice(0,5) }}</span>
              </div>
              <div class="border-t border-gray-100 pt-3 mt-1 flex flex-wrap gap-2">
                <div v-if="data.estado_asignacion" class="flex items-center gap-1.5 text-xs">
                  <span class="text-gray-400 font-semibold uppercase tracking-wide text-[10px]">Estado:</span>
                  <span :class="{
                    'bg-green-100 text-green-700': data.estado_asignacion === 'Asignado',
                    'bg-blue-100 text-blue-700': data.estado_asignacion === 'Pendiente',
                    'bg-red-100 text-red-700': data.estado_asignacion === 'Rechazado',
                  }" class="px-2 py-0.5 rounded-full font-semibold text-[11px]">
                    {{ data.estado_asignacion }}
                  </span>
                </div>
                <div v-if="data.carrera_asignada || data.carrera_primera" class="flex items-center gap-1.5 text-xs text-gray-500">
                  <span class="text-gray-400 font-semibold uppercase tracking-wide text-[10px]">Carrera:</span>
                  <span class="font-medium truncate max-w-[140px]">{{ data.carrera_asignada || data.carrera_primera }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tarjetas de calificaciones históricas (otras materias) -->
          <div v-for="(cal, idx) in otrasCalificaciones" :key="cal.id"
            class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-shadow cursor-default">
            <div class="h-36 relative" :class="cardGradients[idx % cardGradients.length]">
              <div class="absolute inset-0 flex flex-col justify-end p-4">
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">{{ cal.sigla }}</p>
                <h3 class="text-white text-base font-bold leading-tight mt-0.5">{{ cal.nombre_materia || 'Materia' }}</h3>
              </div>
              <span v-if="cal.estado"
                :class="cal.estado === 'Aprobado' ? 'bg-green-400' : 'bg-red-400'"
                class="absolute top-3 right-3 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full">
                {{ cal.estado }}
              </span>
            </div>
            <div class="p-4">
              <p class="text-sm font-semibold text-gray-800">{{ cal.nombre_materia || 'Materia' }}</p>
              <p class="text-xs font-mono text-gray-400 mt-0.5 mb-3">{{ cal.sigla }}</p>
              <div class="border-t border-gray-100 pt-3">
                <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Cursado anteriormente</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Evaluaciones pendientes (sección integrada en Mis Cursos) -->
        <div v-if="evaluacionesPendientes.length > 0" class="mt-8">
          <h2 class="text-base font-bold text-gray-700 mb-3">Evaluaciones Pendientes</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a v-for="ev in evaluacionesPendientes" :key="ev.id"
              :href="`/evaluaciones/${ev.id}/responder?registro=${registro}&role=${role}`"
              class="bg-violet-50 border border-violet-200 rounded-xl p-4 flex items-center justify-between hover:bg-violet-100 hover:border-violet-300 transition">
              <div>
                <p class="font-semibold text-violet-800 text-sm">{{ ev.titulo }}</p>
                <p class="text-xs text-violet-500 mt-0.5">
                  {{ ev.tipo === 'postulante_a_docente' ? 'Evalúa a tu docente' : 'Evalúa tu curso' }}
                </p>
              </div>
              <span class="text-violet-600 font-bold text-sm flex-shrink-0 ml-3">Responder →</span>
            </a>
          </div>
        </div>
      </div>

      <!-- ═══ BOLETA DE INSCRIPCIÓN ═══ -->
      <div v-else-if="activePage === 'boleta'" class="max-w-3xl mx-auto px-4 py-8">

        <div class="flex justify-end mb-5 print:hidden">
          <button @click="imprimirBoleta"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-sm font-semibold shadow transition">
            🖨️ Imprimir Boleta
          </button>
        </div>

        <div id="boleta-cup" class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-200 print:shadow-none print:rounded-none">

          <!-- Encabezado institucional -->
          <div class="bg-gradient-to-r from-blue-800 to-blue-900 text-white px-8 py-6">
            <div class="flex items-center gap-5">
              <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png"
                class="w-16 h-16 bg-white rounded-full p-1.5 flex-shrink-0" alt="FICCT" />
              <div>
                <p class="text-[10px] font-semibold text-blue-200 uppercase tracking-widest leading-none">Universidad Autónoma Gabriel René Moreno</p>
                <p class="text-base font-bold mt-1 leading-tight">Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones</p>
                <p class="text-sm text-blue-300 font-semibold mt-1">CURSOS PREUNIVERSITARIOS (CUP)</p>
              </div>
            </div>
          </div>

          <!-- Título del documento -->
          <div class="border-b border-gray-200 py-5 text-center">
            <h2 class="text-2xl font-bold text-gray-800 uppercase tracking-widest">Boleta de Inscripción</h2>
            <p class="text-gray-400 text-xs mt-1">
              Gestión {{ data?.inscripcion?.anio || '—' }}
              <template v-if="data?.inscripcion?.gestion"> · {{ data.inscripcion.gestion }}</template>
            </p>
          </div>

          <!-- Cuerpo del documento -->
          <div class="px-8 py-6 space-y-6">

            <!-- Datos personales -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-blue-700 mb-3 border-b border-blue-100 pb-1">Datos Personales</h3>
              <div class="grid grid-cols-2 gap-x-10 gap-y-3 text-sm">
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Registro</p>
                  <p class="font-bold text-gray-800 font-mono">{{ registro }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">CI</p>
                  <p class="font-bold text-gray-800">{{ data?.persona?.ci }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Nombres y Apellidos</p>
                  <p class="font-bold text-gray-800 text-base">{{ data?.persona?.nombre }} {{ data?.persona?.apellido }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Fecha de Nacimiento</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.fecha_nacimiento }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Ciudad</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.ciudad }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Correo Electrónico</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.correo_electronico || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Teléfono</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.telefono || 'N/A' }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Colegio de Procedencia</p>
                  <p class="font-semibold text-gray-700">{{ data?.colegio_procedencia }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Título de Bachiller</p>
                  <p class="font-semibold text-gray-700">{{ data?.titulo_bachiller }}</p>
                </div>
              </div>
            </section>

            <!-- Opciones de carrera -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-blue-700 mb-3 border-b border-blue-100 pb-1">Opciones de Carrera</h3>
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-3">
                  <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">1ª</span>
                  <span class="font-semibold text-gray-700">{{ data?.carrera_primera || 'No especificado' }}</span>
                </div>
                <div v-if="data?.carrera_segunda" class="flex items-center gap-3">
                  <span class="w-7 h-7 bg-blue-400 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">2ª</span>
                  <span class="font-semibold text-gray-700">{{ data.carrera_segunda }}</span>
                </div>
                <div v-if="data?.carrera_asignada" class="flex items-center gap-3 mt-2">
                  <span class="w-7 h-7 bg-green-600 text-white rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">✓</span>
                  <span class="font-bold text-green-700">Carrera Asignada: {{ data.carrera_asignada }}</span>
                </div>
                <div v-if="data?.estado_asignacion" class="mt-2">
                  <span :class="{
                    'bg-green-100 text-green-700 border-green-200': data.estado_asignacion === 'Asignado',
                    'bg-blue-100 text-blue-700 border-blue-200': data.estado_asignacion === 'Pendiente',
                    'bg-red-100 text-red-700 border-red-200': data.estado_asignacion === 'Rechazado',
                    'bg-gray-100 text-gray-600 border-gray-200': !['Asignado','Pendiente','Rechazado'].includes(data.estado_asignacion)
                  }" class="inline-flex items-center border px-3 py-1 rounded-full text-xs font-bold">
                    Estado: {{ data.estado_asignacion }}
                  </span>
                </div>
              </div>
            </section>

            <!-- Grupo académico -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-blue-700 mb-3 border-b border-blue-100 pb-1">Grupos Académicos</h3>
              <div v-if="data?.grupos?.length" class="space-y-4">
                <div v-for="grupo in data.grupos" :key="grupo.codigo" class="grid grid-cols-2 gap-x-10 gap-y-3 text-sm border border-gray-100 rounded-xl p-3">
                  <div>
                    <p class="text-[10px] text-gray-400 uppercase font-semibold">Grupo</p>
                    <p class="font-bold text-gray-800">{{ grupo.nombre_grupo }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] text-gray-400 uppercase font-semibold">Materia</p>
                    <p class="font-semibold text-gray-700">{{ grupo.sigla }} — {{ grupo.nombre_materia }}</p>
                  </div>
                  <div>
                    <p class="text-[10px] text-gray-400 uppercase font-semibold">Docente</p>
                    <p class="font-semibold text-gray-700">{{ grupo.docente_nombre }} {{ grupo.docente_apellido }}</p>
                  </div>
                  <div v-if="grupo.dia">
                    <p class="text-[10px] text-gray-400 uppercase font-semibold">Horario</p>
                    <p class="font-semibold text-gray-700">
                      {{ grupo.dia }} · {{ (grupo.hora_inicio || '').slice(0,5) }} – {{ (grupo.hora_fin || '').slice(0,5) }}
                    </p>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400 italic">Sin grupo asignado todavía.</p>
            </section>

            <!-- Estado de pago -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-blue-700 mb-3 border-b border-blue-100 pb-1">Estado de Pago</h3>
              <div class="flex flex-wrap items-center gap-6 text-sm">
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold mb-1">Estado</p>
                  <span :class="data?.inscripcion?.estado_pago === 'Pagado'
                    ? 'bg-green-100 text-green-700 border-green-200'
                    : 'bg-amber-100 text-amber-700 border-amber-200'"
                    class="border px-3 py-1 rounded-full text-xs font-bold">
                    {{ data?.inscripcion?.estado_pago || 'N/A' }}
                  </span>
                </div>
                <div v-if="data?.inscripcion?.monto">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Monto</p>
                  <p class="font-bold text-gray-800">Bs. {{ data.inscripcion.monto }}</p>
                </div>
                <div v-if="data?.inscripcion?.comprobante">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Comprobante</p>
                  <p class="font-bold text-gray-800 font-mono text-xs">{{ data.inscripcion.comprobante }}</p>
                </div>
                <div v-if="data?.inscripcion?.fecha_inscripcion">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Fecha de Inscripción</p>
                  <p class="font-semibold text-gray-700">{{ data.inscripcion.fecha_inscripcion }}</p>
                </div>
              </div>
            </section>

          </div>

          <!-- Pie del documento con firmas -->
          <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
            <p class="text-[10px] text-gray-400 text-center mb-8 uppercase tracking-widest">
              Documento generado por el Sistema de Gestión Académica — FICCT UAGRM
            </p>
            <div class="grid grid-cols-3 gap-8">
              <div class="text-center">
                <div class="border-t-2 border-gray-300 pt-3">
                  <p class="text-xs text-gray-500 font-semibold">Firma del Postulante</p>
                  <p class="text-[10px] text-gray-400 mt-0.5">{{ data?.persona?.nombre }} {{ data?.persona?.apellido }}</p>
                </div>
              </div>
              <div class="text-center">
                <div class="border-t-2 border-gray-300 pt-3">
                  <p class="text-xs text-gray-500 font-semibold">Sello de la Institución</p>
                </div>
              </div>
              <div class="text-center">
                <div class="border-t-2 border-gray-300 pt-3">
                  <p class="text-xs text-gray-500 font-semibold">Vo.Bo. Administrativo</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ================================================================ -->
    <!--  PORTAL DOCENTE — estilo Moodle                                  -->
    <!-- ================================================================ -->
    <div v-else-if="role === 'Docente'" class="min-h-screen bg-gray-100">

      <!-- ── Barra de navegación superior ── -->
      <nav class="bg-[#134e2a] text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

          <!-- Izquierda: Marca / Logo -->
          <div class="flex items-center gap-3 flex-shrink-0">
            <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png"
              alt="FICCT" class="w-9 h-9 rounded-full bg-white p-1 flex-shrink-0" />
            <div class="hidden sm:block leading-tight">
              <p class="text-[10px] text-green-300 font-semibold uppercase tracking-wider leading-none">FICCT — UAGRM</p>
              <p class="text-sm font-bold">Cursos Preuniversitarios</p>
            </div>
          </div>

          <!-- Centro: Vínculos de navegación -->
          <div class="flex items-center gap-1">
            <button @click="activePage = 'grupos'"
              :class="activePage === 'grupos' ? 'bg-white/20 text-white' : 'text-green-200 hover:bg-white/10 hover:text-white'"
              class="px-4 py-2 rounded text-sm font-semibold transition">
              Mis Grupos
            </button>
            <button @click="activePage = 'calificaciones'"
              :class="activePage === 'calificaciones' ? 'bg-white/20 text-white' : 'text-green-200 hover:bg-white/10 hover:text-white'"
              class="px-4 py-2 rounded text-sm font-semibold transition">
              Calificaciones
            </button>
            <button @click="activePage = 'perfil'"
              :class="activePage === 'perfil' ? 'bg-white/20 text-white' : 'text-green-200 hover:bg-white/10 hover:text-white'"
              class="px-4 py-2 rounded text-sm font-semibold transition">
              Mi Perfil
            </button>
            <!-- Accesos a CU otorgados por CU02 -->
            <a v-for="acc in accesosOtorgados" :key="acc.codigo"
              :href="obtenerRuta(0, acc.codigo)" :title="acc.nombre"
              class="px-4 py-2 rounded text-sm font-semibold text-green-200 hover:bg-white/10 hover:text-white transition whitespace-nowrap">
              {{ acc.codigo }}
            </a>
          </div>

          <!-- Derecha: notificaciones + usuario + cerrar sesión -->
          <div class="flex items-center gap-2 flex-shrink-0">
            <div v-if="evaluacionesPendientes.length > 0" class="relative">
              <button @click="activePage = 'grupos'"
                class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition text-base"
                title="Evaluaciones pendientes">
                🔔
              </button>
              <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full text-[10px] font-bold flex items-center justify-center leading-none">
                {{ evaluacionesPendientes.length }}
              </span>
            </div>
            <div class="text-right hidden md:block leading-tight">
              <p class="text-[10px] text-green-300 leading-none">{{ registro }}</p>
              <p class="text-xs font-semibold">{{ displayName }}</p>
            </div>
            <button @click="cerrarSesion"
              class="px-3 py-1.5 bg-red-500 hover:bg-red-600 rounded text-xs font-semibold transition whitespace-nowrap">
              Cerrar Sesión
            </button>
          </div>
        </div>
      </nav>

      <!-- ═══ MIS GRUPOS ═══ -->
      <div v-if="activePage === 'grupos'" class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-0.5">Mis grupos</h1>
        <p class="text-gray-500 text-sm mb-7">Grupos académicos asignados a tu cargo</p>

        <!-- Sin grupos asignados -->
        <div v-if="!data?.grupos?.length && !evaluacionesPendientes.length"
          class="bg-white rounded-2xl shadow-md p-12 text-center">
          <div class="text-5xl mb-4">📋</div>
          <h2 class="text-xl font-bold text-gray-600 mb-2">Sin grupos asignados</h2>
          <p class="text-gray-400 max-w-sm mx-auto">
            Aún no tienes grupos asignados. Cuando el coordinador te asigne a un grupo, aquí verás tus cursos.
          </p>
        </div>

        <!-- Grilla de tarjetas de grupos -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div v-for="(grupo, idx) in (data?.grupos || [])" :key="grupo.codigo"
            class="bg-white rounded-2xl shadow-md overflow-hidden hover:shadow-xl transition-shadow cursor-default">
            <div class="h-36 relative" :class="docenteCardGradients[idx % docenteCardGradients.length]">
              <div class="absolute inset-0 flex flex-col justify-end p-4">
                <p class="text-white/70 text-xs font-bold uppercase tracking-wider">{{ grupo.sigla }}</p>
                <h3 class="text-white text-base font-bold leading-tight mt-0.5">{{ grupo.nombre_grupo }}</h3>
              </div>
              <span class="absolute top-3 right-3 bg-green-400 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full">
                Activo
              </span>
            </div>
            <div class="p-4">
              <p class="text-sm font-semibold text-gray-800">{{ grupo.nombre_materia || 'Sin materia' }}</p>
              <p class="text-xs text-gray-400 font-mono mt-0.5 mb-3">{{ grupo.sigla }}</p>
              <div v-if="grupo.dia" class="flex items-center gap-1.5 text-xs text-gray-500 mb-2">
                <span>📅</span>
                <span>{{ grupo.dia }} · {{ (grupo.hora_inicio || '').slice(0,5) }} – {{ (grupo.hora_fin || '').slice(0,5) }}</span>
              </div>
              <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-3">
                <span>👥</span>
                <span>{{ grupo.total_estudiantes }} estudiante{{ grupo.total_estudiantes != 1 ? 's' : '' }}</span>
              </div>
              <div class="border-t border-gray-100 pt-3">
                <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Grupo activo</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Evaluaciones pendientes -->
        <div v-if="evaluacionesPendientes.length > 0" class="mt-8">
          <h2 class="text-base font-bold text-gray-700 mb-3">Evaluaciones Pendientes</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a v-for="ev in evaluacionesPendientes" :key="ev.id"
              :href="`/evaluaciones/${ev.id}/responder?registro=${registro}&role=${role}`"
              class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between hover:bg-emerald-100 hover:border-emerald-300 transition">
              <div>
                <p class="font-semibold text-emerald-800 text-sm">{{ ev.titulo }}</p>
                <p class="text-xs text-emerald-500 mt-0.5">
                  {{ ev.tipo === 'postulante_a_docente' ? 'Evaluación recibida de estudiantes' : 'Evalúa tu curso' }}
                </p>
              </div>
              <span class="text-emerald-600 font-bold text-sm flex-shrink-0 ml-3">Ver →</span>
            </a>
          </div>
        </div>
      </div>

      <!-- ═══ CALIFICACIONES (CU05) ═══ -->
      <div v-else-if="activePage === 'calificaciones'" class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-0.5">Registrar Calificaciones</h1>
        <p class="text-gray-500 text-sm mb-7">Registra las notas parciales de tus estudiantes por grupo.</p>

        <!-- Sin grupos asignados -->
        <div v-if="!data?.grupos?.length" class="bg-white rounded-2xl shadow-md p-12 text-center">
          <div class="text-5xl mb-4">📝</div>
          <h2 class="text-xl font-bold text-gray-600 mb-2">Sin grupos asignados</h2>
          <p class="text-gray-400 max-w-sm mx-auto">
            Aún no tienes grupos asignados, por lo que no puedes registrar calificaciones.
          </p>
        </div>

        <template v-else>
          <!-- Formulario -->
          <section class="bg-white rounded-2xl shadow-md p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Registrar Notas</h2>

            <div class="grid gap-4 md:grid-cols-2 mb-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Grupo</label>
                <select v-model="grupoSelDocente" @change="onGrupoDocenteChange"
                        class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-emerald-500 focus:outline-none">
                  <option :value="null">-- Selecciona un grupo --</option>
                  <option v-for="g in data.grupos" :key="g.codigo" :value="g.codigo">
                    {{ g.sigla }} - {{ g.nombre_materia }} ({{ g.nombre_grupo }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Estudiante</label>
                <select v-model="estudianteSelDocente" @change="onEstudianteDocenteChange"
                        :disabled="!grupoSelDocente"
                        class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-emerald-500 focus:outline-none disabled:bg-gray-50 disabled:text-gray-400">
                  <option :value="null">-- Selecciona un estudiante --</option>
                  <option v-for="p in estudiantesDocenteFiltrados" :key="p.id" :value="p.id">
                    {{ p.registro }} — {{ p.nombre }} {{ p.apellido }} {{ notasPendientesDocente(p) }}
                  </option>
                </select>
                <p v-if="grupoSelDocente && estudiantesDocenteFiltrados.length === 0" class="mt-1 text-sm text-green-600">
                  Todos los estudiantes de este grupo tienen sus 3 notas completas.
                </p>
              </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3 mb-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nota Parcial 1</label>
                <input v-model.number="notasDocente.nota1" type="number" min="0" max="100" placeholder="0 – 100"
                       :disabled="nota1BloqueadaDocente"
                       :class="nota1BloqueadaDocente ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''"
                       class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-emerald-500 focus:outline-none" />
                <p v-if="nota1BloqueadaDocente" class="mt-1 text-xs text-gray-400">Ya registrada — no editable</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nota Parcial 2</label>
                <input v-model.number="notasDocente.nota2" type="number" min="0" max="100" placeholder="0 – 100"
                       :disabled="nota2BloqueadaDocente"
                       :class="nota2BloqueadaDocente ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''"
                       class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-emerald-500 focus:outline-none" />
                <p v-if="nota2BloqueadaDocente" class="mt-1 text-xs text-gray-400">Ya registrada — no editable</p>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nota Parcial 3</label>
                <input v-model.number="notasDocente.nota3" type="number" min="0" max="100" placeholder="0 – 100"
                       :disabled="nota3BloqueadaDocente"
                       :class="nota3BloqueadaDocente ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''"
                       class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-emerald-500 focus:outline-none" />
                <p v-if="nota3BloqueadaDocente" class="mt-1 text-xs text-gray-400">Ya registrada — no editable</p>
              </div>
            </div>

            <p v-if="errorDocente" class="text-sm text-red-600 mb-3">{{ errorDocente }}</p>

            <div class="flex justify-end">
              <button @click="guardarNotaDocente" :disabled="guardandoDocente || !estudianteSelDocente"
                      class="px-6 py-2.5 bg-emerald-600 text-white font-semibold rounded-xl shadow hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                {{ guardandoDocente ? 'Guardando...' : 'Guardar Calificaciones' }}
              </button>
            </div>

            <div v-if="successDocente" class="mt-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
              {{ successDocente }}
            </div>
          </section>

          <!-- Tabla de calificaciones registradas -->
          <section class="bg-white rounded-2xl shadow-md p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-1">Calificaciones Registradas</h2>
            <p class="text-gray-400 text-sm mb-4">Estudiantes con las 3 notas completas en tus grupos.</p>

            <div v-if="calificacionesDocenteLocal.length === 0" class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-gray-500 text-center">
              No hay calificaciones completas registradas aún.
            </div>

            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 text-sm text-left">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 font-semibold text-gray-700">Registro</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Estudiante</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Grupo</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Materia</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Nota 1</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Nota 2</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Nota 3</th>
                    <th class="px-4 py-3 font-semibold text-gray-700 text-center">Promedio</th>
                    <th class="px-4 py-3 font-semibold text-gray-700">Acción</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="c in calificacionesDocenteLocal" :key="c.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs font-semibold text-emerald-700">{{ c.registro }}</td>
                    <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ c.nombre }} {{ c.apellido }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ c.nombre_grupo }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ c.nombre_materia }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ c.nota1 }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ c.nota2 }}</td>
                    <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ c.nota3 }}</td>
                    <td class="px-4 py-3 text-center">
                      <span :class="(c.promedio ?? 0) >= 51 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            class="px-2 py-1 rounded-lg text-xs font-bold">
                        {{ c.promedio != null ? Number(c.promedio).toFixed(2) : '—' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap space-x-2">
                      <button @click="abrirEditarDocente(c)" class="rounded-lg bg-yellow-500 px-3 py-1.5 text-white text-xs font-semibold hover:bg-yellow-600 transition">Editar</button>
                      <button @click="eliminarCalificacionDocente(c)" class="rounded-lg bg-red-600 px-3 py-1.5 text-white text-xs font-semibold hover:bg-red-700 transition">Borrar</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </template>
      </div>

      <!-- ═══ MI PERFIL ═══ -->
      <div v-else-if="activePage === 'perfil'" class="max-w-3xl mx-auto px-4 py-8">

        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-200">

          <!-- Encabezado del perfil -->
          <div class="bg-gradient-to-r from-teal-700 to-emerald-900 text-white px-8 py-6">
            <div class="flex items-center gap-5">
              <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-3xl font-bold flex-shrink-0">
                {{ (data?.persona?.nombre || 'D').charAt(0).toUpperCase() }}
              </div>
              <div>
                <p class="text-[10px] font-semibold text-green-200 uppercase tracking-widest leading-none">Docente — CUP FICCT</p>
                <p class="text-xl font-bold mt-1">{{ data?.persona?.nombre }} {{ data?.persona?.apellido }}</p>
                <p class="text-sm text-green-300 font-semibold mt-0.5">Registro: {{ registro }}</p>
              </div>
            </div>
          </div>

          <!-- Cuerpo del perfil -->
          <div class="px-8 py-6 space-y-6">

            <!-- Datos personales -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 mb-3 border-b border-emerald-100 pb-1">Datos Personales</h3>
              <div class="grid grid-cols-2 gap-x-10 gap-y-3 text-sm">
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Registro</p>
                  <p class="font-bold text-gray-800 font-mono">{{ registro }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">CI</p>
                  <p class="font-bold text-gray-800">{{ data?.persona?.ci }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Nombres y Apellidos</p>
                  <p class="font-bold text-gray-800 text-base">{{ data?.persona?.nombre }} {{ data?.persona?.apellido }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Correo Electrónico</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.correo_electronico || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Teléfono</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.telefono || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Ciudad</p>
                  <p class="font-semibold text-gray-700">{{ data?.persona?.ciudad || 'N/A' }}</p>
                </div>
              </div>
            </section>

            <!-- Datos académicos -->
            <section>
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 mb-3 border-b border-emerald-100 pb-1">Datos Académicos</h3>
              <div class="grid grid-cols-2 gap-x-10 gap-y-3 text-sm">
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Especialidad</p>
                  <p class="font-semibold text-gray-700">{{ data?.especialidad || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Área Profesional</p>
                  <p class="font-semibold text-gray-700">{{ data?.profesional_area || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Maestría</p>
                  <p class="font-semibold text-gray-700">{{ data?.maestria || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Diplomado en Educación Superior</p>
                  <p class="font-semibold text-gray-700">{{ data?.diplomado_educacion_superior || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-semibold">Grupos Asignados</p>
                  <p class="font-bold text-gray-800">{{ data?.grupos?.length ?? data?.cantidad_grupos_asignados ?? 0 }}</p>
                </div>
              </div>
            </section>

            <!-- Resumen de grupos -->
            <section v-if="data?.grupos?.length">
              <h3 class="text-[10px] font-bold uppercase tracking-widest text-emerald-700 mb-3 border-b border-emerald-100 pb-1">Grupos a Cargo</h3>
              <div class="space-y-2">
                <div v-for="g in data.grupos" :key="g.codigo"
                  class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-2.5 text-sm">
                  <div>
                    <span class="font-bold text-gray-800">{{ g.nombre_grupo }}</span>
                    <span class="text-gray-400 mx-2">·</span>
                    <span class="text-gray-600">{{ g.nombre_materia }}</span>
                  </div>
                  <span class="text-xs text-gray-400 font-mono">{{ g.sigla }}</span>
                </div>
              </div>
            </section>

          </div>

          <!-- Pie del perfil -->
          <div class="px-8 py-4 bg-gray-50 border-t border-gray-200">
            <p class="text-[10px] text-gray-400 text-center uppercase tracking-widest">
              Sistema de Gestión Académica — FICCT UAGRM
            </p>
          </div>
        </div>
      </div>

      <!-- Modal: Editar calificación (CU05) -->
      <div v-if="editModalDocente" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/50 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
          <div class="flex items-center justify-between gap-4 mb-6">
            <div>
              <h3 class="text-xl font-bold">Editar Calificación</h3>
              <p class="text-slate-500 text-sm mt-1">
                {{ editDataDocente?.nombre }} {{ editDataDocente?.apellido }} — {{ editDataDocente?.nombre_grupo }}
              </p>
            </div>
            <button @click="editModalDocente = false" class="text-slate-400 hover:text-slate-900 text-2xl">✕</button>
          </div>

          <div class="grid grid-cols-3 gap-4 mb-6">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 1</label>
              <input v-model.number="editNotasDocente.nota1" type="number" min="0" max="100"
                     class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 2</label>
              <input v-model.number="editNotasDocente.nota2" type="number" min="0" max="100"
                     class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Nota 3</label>
              <input v-model.number="editNotasDocente.nota3" type="number" min="0" max="100"
                     class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" />
            </div>
          </div>

          <div class="flex justify-end gap-3">
            <button @click="editModalDocente = false" class="rounded-xl border border-slate-300 px-5 py-2.5 text-slate-700 hover:bg-slate-100 text-sm font-semibold">Cancelar</button>
            <button @click="guardarEdicionDocente" :disabled="editGuardandoDocente"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-white hover:bg-emerald-700 text-sm font-semibold disabled:opacity-50">
              {{ editGuardandoDocente ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- ================================================================ -->
    <!--  OTROS ROLES — layout original con P1-P5                         -->
    <!-- ================================================================ -->
    <div v-else class="min-h-screen bg-slate-100">
      <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-8 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 flex flex-col lg:flex-row items-center justify-between gap-6">
          <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 w-full lg:w-auto">
            <img src="https://www.ficct.uagrm.edu.bo:3000/uploads/faculty/Escudo_FICCT.png" alt="Escudo FICCT" class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-white p-2 flex-shrink-0" />
            <div class="text-center sm:text-left">
              <p class="text-xs sm:text-sm uppercase tracking-widest leading-tight font-semibold">FACULTAD DE INGENIERÍA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES</p>
              <p class="text-xl sm:text-2xl font-bold mt-1">CURSOS PREUNIVERSITARIOS (CUP)</p>
            </div>
          </div>
          <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 w-full lg:w-auto justify-center lg:justify-end">
            <div class="text-center">
              <p class="text-xs sm:text-sm text-blue-200">Rol</p>
              <p class="text-lg sm:text-xl font-semibold">{{ role }}</p>
            </div>
            <button
              @click="cerrarSesion"
              class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-colors whitespace-nowrap text-sm sm:text-base"
            >
              Cerrar Sesión
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-4 mt-8">
        <nav class="bg-transparent flex justify-center">
          <div class="flex flex-wrap justify-center items-start gap-2 sm:gap-3 bg-transparent px-2 py-4 rounded-md">
            <template v-for="(label, i) in ['P1','P2','P3','P4','P5']" :key="label">
              <div class="relative" @mouseenter="onButtonEnter(i)" @mouseleave="onButtonLeave(i)" @click="onButtonClick(i)">
                <button class="px-3 py-2 sm:px-4 text-sm sm:text-base bg-white border border-slate-200 rounded-md shadow-sm hover:shadow-md font-semibold whitespace-nowrap">
                  {{ label }}
                </button>

                <div v-if="isOpen(i)" @mouseenter="onMenuEnter(i)" @mouseleave="onMenuLeave(i)" class="absolute left-0 mt-2 w-48 sm:w-64 bg-white border border-slate-200 rounded-md shadow-lg z-50">
                  <ul>
                    <li v-for="(opt, idx) in filteredMenus[i]" :key="idx" class="px-4 py-2 hover:bg-slate-100">
                      <a
                        :href="obtenerRuta(i, opt.codigo)"
                        class="block text-sm text-slate-700 font-medium"
                        tabindex="0"
                      >
                        {{ opt.nombre }}
                      </a>
                    </li>
                    <li v-if="filteredMenus[i].length === 0" class="px-4 py-2 text-sm text-slate-500">(No hay opciones)</li>
                  </ul>
                </div>
              </div>
            </template>
          </div>
        </nav>
      </div>

      <div class="max-w-5xl mx-auto px-4 py-12">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 p-8">
          <h2 class="text-3xl font-bold text-slate-900 mb-4">Bienvenido {{ displayName }}</h2>
          <p class="text-slate-600 mb-6">Ya puedes continuar con tu proceso según tu rol.</p>

          <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 p-6 bg-slate-50">
              <p class="text-sm text-slate-500">Registro</p>
              <p class="mt-2 text-xl font-semibold text-slate-900">{{ registro }}</p>
            </div>
            <div class="rounded-3xl border border-slate-200 p-6 bg-slate-50">
              <p class="text-sm text-slate-500">Rol</p>
              <p class="mt-2 text-xl font-semibold text-slate-900">{{ role }}</p>
            </div>
          </div>

          <div class="mt-8 rounded-3xl border border-blue-200 bg-blue-50 p-6">
            <p class="font-semibold text-blue-900">Siguiente paso</p>
            <p class="mt-2 text-slate-600">Tu sesión ha sido validada. Aquí puedes agregar luego la lógica para mostrar cursos, documentos o paneles según el rol seleccionado.</p>
          </div>

          <!-- Evaluaciones pendientes para Docente -->
          <div v-if="role === 'Docente' && evaluacionesPendientes.length > 0"
            class="mt-8 rounded-3xl border border-violet-200 bg-violet-50 p-6">
            <p class="font-bold text-violet-900 text-lg mb-1">Evaluaciones Pendientes</p>
            <p class="text-violet-600 text-sm mb-4">Tienes {{ evaluacionesPendientes.length }} evaluación(es) por completar.</p>
            <div class="space-y-2">
              <a v-for="ev in evaluacionesPendientes" :key="ev.id"
                :href="`/evaluaciones/${ev.id}/responder?registro=${registro}&role=${role}`"
                class="flex items-center justify-between bg-white border border-violet-200 rounded-2xl px-5 py-3 hover:border-violet-400 hover:shadow-sm transition">
                <div>
                  <p class="font-semibold text-slate-800 text-sm">{{ ev.titulo }}</p>
                  <p class="text-xs text-slate-400 mt-0.5">
                    {{ ev.tipo === 'postulante_a_docente' ? 'Evalúa a tu docente' : 'Evalúa tu curso' }}
                  </p>
                </div>
                <span class="text-violet-600 font-bold text-sm">Responder →</span>
              </a>
            </div>
          </div>

          <div class="mt-8 bg-white border border-slate-200 rounded-2xl p-6">
            <div class="flex items-start justify-between mb-4">
              <h3 class="text-xl font-semibold">Mi perfil</h3>
              <div>
                <button type="button" @click="editMode = !editMode" class="px-3 py-1 bg-blue-600 text-white rounded">
                  {{ editMode ? 'Cancelar' : 'Editar' }}
                </button>
              </div>
            </div>
            <form @submit.prevent="guardarPerfil">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-slate-600">Registro</label>
                  <input class="w-full mt-1 p-2 border rounded" :value="props.registro || ''" disabled />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Nombre</label>
                  <input class="w-full mt-1 p-2 border rounded" :value="data?.persona?.nombre || ''" disabled />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Apellido</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.apellido" :disabled="!isFieldEnabled('apellido')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">CI</label>
                  <input class="w-full mt-1 p-2 border rounded" :value="data?.persona?.ci || ''" disabled />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Fecha de nacimiento</label>
                  <input class="w-full mt-1 p-2 border rounded" :value="data?.persona?.fecha_nacimiento || ''" disabled />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Teléfono</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.telefono" :disabled="!isFieldEnabled('telefono')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Correo electrónico</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.email" :disabled="!isFieldEnabled('email')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Dirección</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.direccion" :disabled="!isFieldEnabled('direccion')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Ciudad</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.ciudad" :disabled="!isFieldEnabled('ciudad')" />
                </div>
              </div>

              <!-- Campos específicos para Docente -->
              <div v-if="role === 'Docente'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-slate-600">Especialidad</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.especialidad" :disabled="!isFieldEnabled('especialidad')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Área</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.area" :disabled="!isFieldEnabled('area')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Maestría</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.maestria" :disabled="!isFieldEnabled('maestria')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Diplomado</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.diplomado" :disabled="!isFieldEnabled('diplomado')" />
                </div>
              </div>

              <!-- Campos específicos para Administrativo -->
              <div v-if="role === 'Administrativo'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-slate-600">Profesión</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.profesion" :disabled="!isFieldEnabled('profesion')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Nro. Título</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.nro_titulo" :disabled="!isFieldEnabled('nro_titulo')" />
                </div>
              </div>

              <!-- Campos específicos para Coordinador -->
              <div v-if="role === 'Coordinador'" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm text-slate-600">Profesión</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.profesion" :disabled="!isFieldEnabled('profesion')" />
                </div>
                <div>
                  <label class="text-sm text-slate-600">Nro. Título</label>
                  <input class="w-full mt-1 p-2 border rounded" v-model="form.nro_titulo" :disabled="!isFieldEnabled('nro_titulo')" />
                </div>
              </div>

              <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, reactive, ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import useSessionValidation from '@/Composables/useSessionValidation';

const props = defineProps({
  role: String,
  registro: String,
  data: Object,
  allowedCus: Array,
});

const { redirectToLogin } = useSessionValidation();

const evaluacionesPendientes = ref([]);
const activePage = ref(props.role === 'Docente' ? 'grupos' : 'cursos');

onMounted(async () => {
  window.addEventListener('storage', handleStorageChange);

  if (props.role === 'Postulante' || props.role === 'Docente') {
    try {
      const res = await axios.get(
        `/api/evaluaciones/pendientes?registro=${props.registro}&role=${props.role}`
      );
      evaluacionesPendientes.value = res.data;
    } catch {
      // silencioso
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('storage', handleStorageChange);
});

function handleStorageChange(event) {
  if (event.key === 'app_session_token' && event.newValue === null) {
    redirectToLogin('Sesión cerrada en otra pestaña');
  }
}

// ── Computed para el portal Postulante ──────────────────────────────
const otrasCalificaciones = computed(() => {
  const cals = props.data?.calificaciones;
  const grupos = props.data?.grupos;
  if (!cals?.length) return [];
  if (!grupos?.length) return cals;
  const siglaActivas = new Set(grupos.map(g => g.sigla).filter(Boolean));
  return cals.filter(c => !siglaActivas.has(c.sigla));
});

const cardGradients = [
  'bg-gradient-to-br from-teal-500 to-cyan-700',
  'bg-gradient-to-br from-amber-500 to-orange-700',
  'bg-gradient-to-br from-rose-500 to-pink-700',
  'bg-gradient-to-br from-emerald-500 to-green-700',
  'bg-gradient-to-br from-sky-500 to-blue-700',
];

const docenteCardGradients = [
  'bg-gradient-to-br from-teal-500 to-emerald-700',
  'bg-gradient-to-br from-cyan-500 to-teal-700',
  'bg-gradient-to-br from-green-500 to-teal-700',
  'bg-gradient-to-br from-emerald-500 to-green-700',
  'bg-gradient-to-br from-teal-600 to-cyan-800',
];

function imprimirBoleta() {
  window.print();
}
// ────────────────────────────────────────────────────────────────────

const displayName = computed(() => {
  if (props.data?.persona) {
    return `${props.data.persona.nombre} ${props.data.persona.apellido}`;
  }
  return props.role === 'Docente' ? `Docente ${props.registro}` : 'Usuario registrado';
});

// Menus for P1..P5 (indexes 0..4)
const menus = [
  [
    { codigo: 'CU01', nombre: 'CU01-Gestionar Inicio y Cierre de Sesión' },
    { codigo: 'CU02', nombre: 'CU02-Gestionar Usuarios y Roles' },
    { codigo: 'CU18', nombre: 'CU18-Auditoría de Operaciones mediante Bitácora' },
  ],
  [
    { codigo: 'CU03', nombre: 'CU03-Gestionar Postulantes' },
    { codigo: 'CU04', nombre: 'CU04-Seguimiento de Pagos y Validación' },
    { codigo: 'CU07', nombre: 'CU07-Configurar Cupos por Carrera' },
    { codigo: 'CU08', nombre: 'CU08-Asignar Cupos por Primera y Segunda Opción' },
    { codigo: 'CU10', nombre: 'CU10-Asignar Postulantes a Grupos' },
  ],
  [
    { codigo: 'CU05', nombre: 'CU05-Registrar Calificaciones por Materia' },
    { codigo: 'CU06', nombre: 'CU06-Calcular Promedio y Estado' },
    { codigo: 'CU09', nombre: 'CU09-Calcular Cantidad de Grupos Habilitados' },
    { codigo: 'CU14', nombre: 'CU14-Gestión de Calendario Académico (CUP)' },
  ],
  [
    { codigo: 'CU11', nombre: 'CU11-Asignación de Recursos Físicos' },
    { codigo: 'CU12', nombre: 'CU12-Programación de Carga Horaria Docente' },
    { codigo: 'CU13', nombre: 'CU13-Gestión de Asistencia' },
  ],
  [
    { codigo: 'CU15', nombre: 'CU15-Importación Masiva de Datos (CSV/Excel)' },
    { codigo: 'CU16', nombre: 'CU16-Gestión de Reportes' },
    { codigo: 'CU17', nombre: 'CU17-Evaluación de Desempeño' },
  ],
];

const filteredMenus = computed(() => {
  if (props.allowedCus === undefined || props.allowedCus === null) {
    return menus;
  }
  return menus.map(panel => panel.filter(item => props.allowedCus.includes(item.codigo)));
});

// CU otorgados al usuario (vía CU02), excepto CU01 (login). Se muestran como accesos
// en las vistas de Postulante y Docente, que no tienen el menú completo de CU.
const accesosOtorgados = computed(() => {
  if (!props.allowedCus) return [];
  return filteredMenus.value.flat().filter(item => item.codigo !== 'CU01');
});

const rutasCU = {
  CU01: '#cerrar-sesion',
  CU02: '/cu02/usuarios-roles',
  CU03: '/cu03/gestionar-postulantes',
  CU04: '/cu04/pagos-validacion',
  CU05: '/cu05/registrar-calificaciones',
  CU06: '/cu06/calcular-promedio',
  CU07: '/cu07/configurar-cupos',
  CU08: '/cu08/asignar-cupos',
  CU09: '/cu09/calcular-grupos',
  CU10: '/cu10/asignar-postulantes',
  CU11: '/cu11/recursos-fisicos',
  CU12: '/cu12/carga-horaria',
  CU13: '/cu13/asistencia',
  CU14: '/cu14/calendario',
  CU15: '/cu15/importacion-masiva',
  CU16: '/cu16/reportes',
  CU17: '/cu17/desempeno',
  CU18: '/cu18/auditoria',
};

function obtenerRuta(_indexPanel, codigoCU) {
  if (codigoCU === 'CU01') return '/postularse/logout';
  const baseRuta = rutasCU[codigoCU] || '#';
  if (props.registro && props.role) {
    return `${baseRuta}?registro=${props.registro}&role=${props.role}`;
  }
  return baseRuta;
}

const hover = reactive({
  button: [false, false, false, false, false],
  menu: [false, false, false, false, false],
});
const closeTimers = [null, null, null, null, null];

function clearClose(i) {
  if (closeTimers[i]) { clearTimeout(closeTimers[i]); closeTimers[i] = null; }
}
function scheduleClose(i) {
  clearClose(i);
  closeTimers[i] = setTimeout(() => {
    hover.button[i] = false;
    hover.menu[i] = false;
    closeTimers[i] = null;
  }, 180);
}
function onButtonEnter(i) {
  clearClose(i);
  for (let k = 0; k < hover.button.length; k++) {
    if (k !== i) { hover.button[k] = false; hover.menu[k] = false; }
  }
  hover.button[i] = true;
}
function onButtonLeave(i) { scheduleClose(i); }
function onMenuEnter(i) { clearClose(i); hover.menu[i] = true; }
function onMenuLeave(i) { scheduleClose(i); }
function isOpen(i) { return hover.button[i] || hover.menu[i]; }
function isMobileDevice() { return window.innerWidth < 768; }
function onButtonClick(i) {
  if (!isMobileDevice()) return;
  for (let k = 0; k < hover.button.length; k++) {
    if (k !== i) { hover.button[k] = false; hover.menu[k] = false; clearClose(k); }
  }
  clearClose(i);
  hover.button[i] = true;
  hover.menu[i] = true;
  closeTimers[i] = setTimeout(() => {
    hover.button[i] = false; hover.menu[i] = false; closeTimers[i] = null;
  }, 5000);
}

const editMode = ref(false);
const nonEditableByRole = {
  Postulante: ['nombre','apellido','ci','fecha_nacimiento','titulo_bachiller','registro','horario_trabajo','hora_inicio','hora_fin','dias'],
  Administrativo: ['nombre','apellido','ci','fecha_nacimiento','titulo_bachiller','registro','profesion','nro_titulo','horario_trabajo','hora_inicio','hora_fin','dias'],
  Coordinador: ['nombre','apellido','ci','fecha_nacimiento','titulo_bachiller','registro','profesion','nro_titulo','horario_trabajo','hora_inicio','hora_fin','dias'],
  Docente: ['nombre','apellido','ci','fecha_nacimiento','titulo_bachiller','registro','especialidad','area','maestria','diplomado','grupos_asignados','horario_trabajo','hora_inicio','hora_fin','dias'],
  Decano: ['nombre','apellido','ci','fecha_nacimiento','titulo_bachiller','registro','especialidad','area','maestria','diplomado','grupos_asignados','horario_trabajo','hora_inicio','hora_fin','dias'],
};

const form = reactive({
  apellido: props.data?.persona?.apellido || '',
  telefono: props.data?.persona?.telefono || '',
  email: props.data?.persona?.correo_electronico || '',
  direccion: props.data?.persona?.direccion || '',
  ciudad: props.data?.persona?.ciudad || '',
  colegio_procedencia: props.data?.colegio_procedencia || '',
  especialidad: props.data?.especialidad || '',
  area: props.data?.area || props.data?.profesional_area || '',
  maestria: props.data?.maestria || '',
  diplomado: props.data?.diplomado || props.data?.diplomado_educacion_superior || '',
  hora_inicio: props.data?.hora_inicio || '',
  hora_fin: props.data?.hora_fin || '',
  dias: props.data?.dias || '',
  horario_trabajo: props.data?.horario_trabajo || '',
  profesion: props.data?.profesion || '',
  nro_titulo: props.data?.nro_titulo || '',
  registro: props.registro || '',
});

function isFieldEnabled(field) {
  const roleKey = props.role || '';
  const nonEditable = nonEditableByRole[roleKey] || [];
  if (!editMode.value) return false;
  return !nonEditable.includes(field);
}

async function guardarPerfil() {
  const payload = {};
  for (const key in form) {
    if (isFieldEnabled(key)) payload[key] = form[key];
  }
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const resp = await axios.post('/postularse/actualizar-perfil', payload, {
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    alert(resp.data.message || 'Guardado');
    window.location.reload();
  } catch (e) {
    if (e.response?.status === 419) {
      try {
        await new Promise(resolve => setTimeout(resolve, 200));
        const freshCsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const retryResp = await axios.post('/postularse/actualizar-perfil', payload, {
          headers: { 'X-CSRF-TOKEN': freshCsrfToken }
        });
        alert(retryResp.data.message || 'Guardado');
        window.location.reload();
        return;
      } catch {}
    }
    alert('Error al guardar perfil');
  }
}

function cerrarSesion() {
  localStorage.removeItem('app_session_token');
  const keys = Object.keys(localStorage);
  keys.forEach(key => {
    if (key.startsWith('app_session_token_')) localStorage.removeItem(key);
  });
  window.location.href = '/postularse/logout';
}

// ── CU05: Registrar Calificaciones (Docente) ──────────────────────────
const grupoSelDocente = ref(null);
const estudianteSelDocente = ref(null);
const guardandoDocente = ref(false);
const successDocente = ref('');
const errorDocente = ref('');

const notasDocente = reactive({ nota1: null, nota2: null, nota3: null });

const postulantesPendientesDocenteLocal = ref((props.data?.postulantesPendientes || []).map(p => ({ ...p })));
const calificacionesDocenteLocal = ref((props.data?.calificacionesRegistradas || []).map(c => ({ ...c })));

const estudiantesDocenteFiltrados = computed(() =>
  postulantesPendientesDocenteLocal.value.filter(p => p.grupo_codigo === grupoSelDocente.value)
);

const estudianteActualDocente = computed(() =>
  estudiantesDocenteFiltrados.value.find(p => p.id === estudianteSelDocente.value) ?? null
);

const nota1BloqueadaDocente = computed(() => estudianteActualDocente.value?.nota1 != null);
const nota2BloqueadaDocente = computed(() => estudianteActualDocente.value?.nota2 != null);
const nota3BloqueadaDocente = computed(() => estudianteActualDocente.value?.nota3 != null);

const notasPendientesDocente = (p) => {
  const faltan = [p.nota1, p.nota2, p.nota3].filter(n => n == null).length;
  return faltan === 3 ? '(sin notas)' : `(falta ${faltan} nota${faltan > 1 ? 's' : ''})`;
};

const onGrupoDocenteChange = () => {
  estudianteSelDocente.value = null;
  resetNotasDocente();
};

const onEstudianteDocenteChange = () => {
  resetNotasDocente();
  const est = estudianteActualDocente.value;
  if (est) {
    notasDocente.nota1 = est.nota1;
    notasDocente.nota2 = est.nota2;
    notasDocente.nota3 = est.nota3;
  }
};

const resetNotasDocente = () => {
  notasDocente.nota1 = null;
  notasDocente.nota2 = null;
  notasDocente.nota3 = null;
  errorDocente.value = '';
  successDocente.value = '';
};

const toNotaDocente = (v) => {
  if (v === null || v === undefined || v === '' || (typeof v === 'number' && isNaN(v))) return null;
  const n = parseInt(v);
  return isNaN(n) ? null : n;
};

const guardarNotaDocente = async () => {
  errorDocente.value = '';
  successDocente.value = '';

  if (!grupoSelDocente.value) { errorDocente.value = 'Selecciona un grupo.'; return; }
  if (!estudianteSelDocente.value) { errorDocente.value = 'Selecciona un estudiante.'; return; }

  const n1 = nota1BloqueadaDocente.value ? null : toNotaDocente(notasDocente.nota1);
  const n2 = nota2BloqueadaDocente.value ? null : toNotaDocente(notasDocente.nota2);
  const n3 = nota3BloqueadaDocente.value ? null : toNotaDocente(notasDocente.nota3);

  if (n1 === null && n2 === null && n3 === null) {
    errorDocente.value = 'Ingresa al menos una nota nueva (0–100).';
    return;
  }

  guardandoDocente.value = true;
  try {
    const res = await axios.post('/cu05/registrar-calificaciones', {
      grupo_codigo: grupoSelDocente.value,
      postulante_id: estudianteSelDocente.value,
      nota1: n1,
      nota2: n2,
      nota3: n3,
    });

    const { completo, calificacion, postulante_id, grupo_codigo } = res.data;

    if (completo) {
      postulantesPendientesDocenteLocal.value = postulantesPendientesDocenteLocal.value.filter(
        p => !(p.id === postulante_id && p.grupo_codigo === grupo_codigo)
      );
      calificacionesDocenteLocal.value.push(calificacion);
      successDocente.value = 'Notas completas registradas correctamente.';
      estudianteSelDocente.value = null;
      resetNotasDocente();
    } else {
      const idx = postulantesPendientesDocenteLocal.value.findIndex(
        p => p.id === postulante_id && p.grupo_codigo === grupo_codigo
      );
      if (idx !== -1) {
        postulantesPendientesDocenteLocal.value[idx] = {
          ...postulantesPendientesDocenteLocal.value[idx],
          calificacion_id: calificacion.id,
          nota1: calificacion.nota1,
          nota2: calificacion.nota2,
          nota3: calificacion.nota3,
        };
      }
      onEstudianteDocenteChange();
      successDocente.value = res.data.message;
    }
  } catch (err) {
    errorDocente.value = err.response?.data?.message || err.message;
  } finally {
    guardandoDocente.value = false;
  }
};

// Editar / eliminar calificación completa
const editModalDocente = ref(false);
const editDataDocente = ref(null);
const editNotasDocente = reactive({ nota1: null, nota2: null, nota3: null });
const editGuardandoDocente = ref(false);

const abrirEditarDocente = (c) => {
  editDataDocente.value = c;
  editNotasDocente.nota1 = c.nota1;
  editNotasDocente.nota2 = c.nota2;
  editNotasDocente.nota3 = c.nota3;
  editModalDocente.value = true;
};

const guardarEdicionDocente = async () => {
  editGuardandoDocente.value = true;
  try {
    const res = await axios.patch(`/cu05/calificacion/${editDataDocente.value.id}`, {
      nota1: editNotasDocente.nota1,
      nota2: editNotasDocente.nota2,
      nota3: editNotasDocente.nota3,
    });
    const idx = calificacionesDocenteLocal.value.findIndex(c => c.id === editDataDocente.value.id);
    if (idx !== -1) calificacionesDocenteLocal.value[idx] = res.data.calificacion;
    editModalDocente.value = false;
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  } finally {
    editGuardandoDocente.value = false;
  }
};

const eliminarCalificacionDocente = async (c) => {
  if (!confirm(`¿Eliminar las calificaciones de ${c.nombre} ${c.apellido} en ${c.nombre_grupo}?`)) return;
  try {
    await axios.delete(`/cu05/calificacion/${c.id}`);
    calificacionesDocenteLocal.value = calificacionesDocenteLocal.value.filter(x => x.id !== c.id);
  } catch (err) {
    alert('Error: ' + (err.response?.data?.message || err.message));
  }
};
</script>

<style>
@media print {
  .print\:hidden { display: none !important; }
  .print\:shadow-none { box-shadow: none !important; }
  .print\:rounded-none { border-radius: 0 !important; }
  body { background: white !important; }
}
</style>
