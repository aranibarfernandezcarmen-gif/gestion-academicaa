<template>
  <div class="min-h-screen bg-slate-100">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-6 shadow-lg">
      <div class="max-w-6xl mx-auto px-4">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold">CU02 - Gestionar Usuarios y Roles</h1>
            <p class="text-blue-200 mt-2">Registrar cuentas, gestionar permisos y administrar usuarios</p>
          </div>
          <button
            @click="volver"
            class="px-4 py-2 bg-white text-blue-800 font-semibold rounded-lg shadow hover:bg-slate-100 transition"
          >
            Volver
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Botones principales -->
      <div class="flex gap-4 mb-8">
        <button
          @click="activeSection = 'registrar'"
          :class="[
            'px-6 py-3 rounded-lg font-semibold transition-all',
            activeSection === 'registrar'
              ? 'bg-blue-600 text-white shadow-lg'
              : 'bg-white text-slate-900 border border-slate-200 hover:shadow-md'
          ]"
        >
          Registrar Cuentas
        </button>
        <button
          @click="activeSection = 'permisos'"
          :class="[
            'px-6 py-3 rounded-lg font-semibold transition-all',
            activeSection === 'permisos'
              ? 'bg-blue-600 text-white shadow-lg'
              : 'bg-white text-slate-900 border border-slate-200 hover:shadow-md'
          ]"
        >
          Asignar Permisos
        </button>
      </div>

      <!-- Sección Registrar Cuentas -->
      <div v-if="activeSection === 'registrar'" class="bg-white rounded-lg shadow-lg p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6">Registrar Nueva Cuenta</h2>

        <form @submit.prevent="guardarCuenta" class="space-y-6">
          <!-- Rol -->
          <div>
            <label class="block text-sm font-semibold text-slate-900 mb-2">Asignar ROL *</label>
            <select
              v-model="formulario.rol"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">-- Seleccione un rol --</option>
              <option value="docente">Docente</option>
              <option value="administrativo">Administrativo</option>
              <option value="coordinador">Coordinador</option>
            </select>
          </div>

          <!-- Datos Personales -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">NOMBRE *</label>
              <input
                v-model="formulario.nombre"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">APELLIDOS *</label>
              <input
                v-model="formulario.apellido"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">CI *</label>
              <input
                v-model="formulario.ci"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">FECHA NACIMIENTO *</label>
              <input
                v-model="formulario.fecha_nacimiento"
                type="date"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">SEXO *</label>
              <select
                v-model="formulario.sexo"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              >
                <option value="">-- Seleccione --</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">TELÉFONO</label>
              <input
                v-model="formulario.telefono"
                type="tel"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">EMAIL *</label>
              <input
                v-model="formulario.email"
                type="email"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">CIUDAD *</label>
              <input
                v-model="formulario.ciudad"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-900 mb-2">DIRECCIÓN *</label>
              <input
                v-model="formulario.direccion"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                required
              />
            </div>
          </div>

          <!-- Campos específicos para Docente -->
          <template v-if="formulario.rol === 'docente'">
            <div class="border-t-2 pt-6">
              <h3 class="text-lg font-semibold mb-4 text-blue-600">Datos Docente</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">PROFESIÓN</label>
                  <input
                    v-model="formulario.especialidad"
                    type="text"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">ÁREA</label>
                  <select
                    v-model="formulario.area"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  >
                    <option value="">-- Seleccione un área --</option>
                    <option value="Matematicas">Matemáticas</option>
                    <option value="Fisica">Física</option>
                    <option value="Computacion">Computación</option>
                    <option value="Ingles">Inglés</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">MAESTRÍA</label>
                  <input
                    v-model="formulario.maestria"
                    type="text"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">Cod. DIPLOMADO ED. SUPERIOR</label>
                  <input
                    v-model="formulario.diplomado"
                    type="text"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>
            </div>
          </template>

          <!-- Campos específicos para Administrativo -->
          <template v-if="formulario.rol === 'administrativo'">
            <div class="border-t-2 pt-6">
              <h3 class="text-lg font-semibold mb-4 text-blue-600">Datos Administrativo</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">PROFESIÓN</label>
                  <input
                    v-model="formulario.profesion"
                    type="text"
                    placeholder="Ej: Administrador, Secretario"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">NRO. TÍTULO</label>
                  <input
                    v-model="formulario.nro_titulo"
                    type="text"
                    placeholder="Ej: 12345-ADM"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <div class="mt-6">
                <h4 class="text-base font-semibold text-slate-900 mb-3">Horario de Trabajo</h4>
                
                <!-- Días de trabajo con checkboxes -->
                <div class="mb-4">
                  <label class="block text-sm font-semibold text-slate-900 mb-3">DÍAS DE TRABAJO</label>
                  <div class="grid grid-cols-2 gap-3">
                    <div v-for="dia in ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']" :key="dia" class="flex items-center">
                      <input
                        :id="'dia-' + dia"
                        :value="dia"
                        v-model="formulario.dias_trabajo"
                        type="checkbox"
                        class="w-4 h-4 cursor-pointer accent-blue-600"
                      />
                      <label :for="'dia-' + dia" class="ml-2 text-sm text-slate-900 cursor-pointer">{{ dia }}</label>
                    </div>
                  </div>
                </div>
                
                <!-- Hora de entrada y salida -->
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">HORA DE ENTRADA</label>
                    <input
                      v-model="formulario.hora_entrada"
                      type="time"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    />
                  </div>

                  <div>
                    <label class="block text-sm font-semibold text-slate-900 mb-2">HORA DE SALIDA</label>
                    <input
                      v-model="formulario.hora_salida"
                      type="time"
                      class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    />
                  </div>
                </div>

                <!-- Vista previa del horario -->
                <div v-if="formulario.dias_trabajo.length > 0" class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                  <p class="text-sm text-slate-900">
                    <strong>Resumen:</strong> {{ formulario.dias_trabajo.join(', ') }} | 
                    De {{ formulario.hora_entrada || '--:--' }} a {{ formulario.hora_salida || '--:--' }}
                  </p>
                </div>
              </div>
            </div>
          </template>

          <!-- Campos específicos para Coordinador -->
          <template v-if="formulario.rol === 'coordinador'">
            <div class="border-t-2 pt-6">
              <h3 class="text-lg font-semibold mb-4 text-blue-600">Datos Coordinador</h3>
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">PROFESIÓN</label>
                  <input
                    v-model="formulario.profesion"
                    type="text"
                    placeholder="Ej: Coordinador Académico"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">NRO. TÍTULO</label>
                  <input
                    v-model="formulario.nro_titulo"
                    type="text"
                    placeholder="Ej: 12345-COORD"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <h3 class="text-lg font-semibold mb-4 text-blue-600">Horario de Trabajo</h3>
              
              <!-- Días de trabajo con checkboxes -->
              <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-900 mb-3">DÍAS DE TRABAJO</label>
                <div class="grid grid-cols-2 gap-3">
                  <div v-for="dia in ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']" :key="dia" class="flex items-center">
                    <input
                      :id="'dia-coord-' + dia"
                      :value="dia"
                      v-model="formulario.dias_trabajo"
                      type="checkbox"
                      class="w-4 h-4 cursor-pointer accent-blue-600"
                    />
                    <label :for="'dia-coord-' + dia" class="ml-2 text-sm text-slate-900 cursor-pointer">{{ dia }}</label>
                  </div>
                </div>
              </div>
              
              <!-- Hora de entrada y salida -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">HORA DE ENTRADA</label>
                  <input
                    v-model="formulario.hora_entrada"
                    type="time"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>

                <div>
                  <label class="block text-sm font-semibold text-slate-900 mb-2">HORA DE SALIDA</label>
                  <input
                    v-model="formulario.hora_salida"
                    type="time"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                  />
                </div>
              </div>

              <!-- Vista previa del horario -->
              <div v-if="formulario.dias_trabajo.length > 0" class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-slate-900">
                  <strong>Resumen:</strong> {{ formulario.dias_trabajo.join(', ') }} | 
                  De {{ formulario.hora_entrada || '--:--' }} a {{ formulario.hora_salida || '--:--' }}
                </p>
              </div>
            </div>
          </template>

          <!-- Botón Guardar -->
          <div class="flex justify-end pt-6">
            <button
              type="submit"
              class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition"
            >
              GUARDAR
            </button>
          </div>
        </form>

        <!-- Ver Personas por Tipo -->
        <div class="mt-12 pt-8 border-t-2">
          <h3 class="text-lg font-semibold mb-4">Ver Todas las Personas</h3>
          <div class="flex gap-3 flex-wrap">
            <button
              v-for="tipo in ['docente', 'administrativo', 'coordinador', 'postulante']"
              :key="tipo"
              @click="cargarPersonas(tipo)"
              :class="[
                'px-4 py-2 rounded-lg font-semibold transition',
                personasActuales.tipo === tipo
                  ? 'bg-blue-600 text-white'
                  : 'bg-slate-200 text-slate-900 hover:bg-slate-300'
              ]"
            >
              {{ tipo.toUpperCase() }}
            </button>
          </div>

          <div v-if="editarModo" class="mt-6 p-6 bg-slate-50 rounded-2xl border border-slate-200">
            <h4 class="text-lg font-semibold mb-4">Editar Persona</h4>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Nombre</label>
                <input v-model="personaEdit.nombre" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Apellido</label>
                <input v-model="personaEdit.apellido" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">CI</label>
                <input v-model="personaEdit.ci" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Email</label>
                <input v-model="personaEdit.email" type="email" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Teléfono</label>
                <input v-model="personaEdit.telefono" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-900 mb-2">Ciudad</label>
                <input v-model="personaEdit.ciudad" type="text" class="w-full px-4 py-2 border border-slate-300 rounded-lg" />
              </div>
            </div>
            <div class="mt-6 flex items-center gap-3">
              <button @click="actualizarPersona" class="px-5 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Guardar</button>
              <button @click="cancelarEdicion" class="px-5 py-2 bg-slate-200 text-slate-900 rounded-lg hover:bg-slate-300">Cancelar</button>
            </div>
          </div>

          <div v-if="personasActuales.mensaje && personasActuales.lista.length === 0" class="mt-6 p-4 rounded-xl bg-yellow-50 border border-yellow-200 text-yellow-700">
            {{ personasActuales.mensaje }}
          </div>

          <div v-if="personasActuales.lista.length > 0" class="mt-6 overflow-x-auto">
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-slate-200">
                  <!-- Docentes -->
                  <template v-if="personasActuales.tipo === 'docente'">
                    <th class="border px-4 py-2 text-left">Registro</th>
                    <th class="border px-4 py-2 text-left">Nombre</th>
                    <th class="border px-4 py-2 text-left">CI</th>
                    <th class="border px-4 py-2 text-left">Correo Electrónico</th>
                    <th class="border px-4 py-2 text-left">Área</th>
                    <th class="border px-4 py-2 text-left">Acciones</th>
                  </template>
                  <!-- Otros tipos (Administrativo, Coordinador, Postulante) -->
                  <template v-else>
                    <th class="border px-4 py-2 text-left">Registro</th>
                    <th class="border px-4 py-2 text-left">Nombre</th>
                    <th class="border px-4 py-2 text-left">CI</th>
                    <th class="border px-4 py-2 text-left">Email</th>
                    <th class="border px-4 py-2 text-left">Acciones</th>
                  </template>
                </tr>
              </thead>
              <tbody>
                <!-- Docentes -->
                <tr v-if="personasActuales.tipo === 'docente'" v-for="persona in personasActuales.lista" :key="persona.id" class="border-b hover:bg-slate-50">
                  <td class="border px-4 py-2">{{ persona.codigo }}</td>
                  <td class="border px-4 py-2">{{ persona.nombre }} {{ persona.apellido }}</td>
                  <td class="border px-4 py-2">{{ persona.ci }}</td>
                  <td class="border px-4 py-2">{{ persona.correo_electronico }}</td>
                  <td class="border px-4 py-2">{{ persona.area || '-' }}</td>
                  <td class="border px-4 py-2 space-x-2">
                    <button
                      @click="editarPersona(personasActuales.tipo, persona)"
                      class="px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600"
                    >
                      EDITAR
                    </button>
                    <button
                      @click="eliminarPersona(personasActuales.tipo, persona.id)"
                      class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                    >
                      ELIMINAR
                    </button>
                  </td>
                </tr>
                <!-- Otros tipos (Administrativo, Coordinador, Postulante) -->
                <tr v-else v-for="persona in personasActuales.lista" :key="persona.id" class="border-b hover:bg-slate-50">
                  <td class="border px-4 py-2 font-semibold text-blue-700">{{ persona.codigo || persona.registro }}</td>
                  <td class="border px-4 py-2">{{ persona.nombre }} {{ persona.apellido }}</td>
                  <td class="border px-4 py-2">{{ persona.ci }}</td>
                  <td class="border px-4 py-2">{{ persona.correo_electronico }}</td>
                  <td class="border px-4 py-2 space-x-2">
                    <button
                      @click="editarPersona(personasActuales.tipo, persona)"
                      class="px-3 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600"
                    >
                      EDITAR
                    </button>
                    <button
                      @click="eliminarPersona(personasActuales.tipo, persona.id)"
                      class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
                    >
                      ELIMINAR
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Sección Asignar Permisos -->
      <div v-if="activeSection === 'permisos'" class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">Asignar Permisos a Grupos</h2>

        <div class="grid grid-cols-2 gap-4 mb-8">
          <button
            v-for="grupo in grupos"
            :key="grupo.codigo"
            @click="seleccionarGrupo(grupo)"
            :class="[
              'p-4 rounded-lg font-semibold transition border-2',
              grupoSeleccionado?.codigo === grupo.codigo
                ? 'bg-blue-100 border-blue-600 text-blue-900'
                : 'bg-slate-50 border-slate-200 text-slate-900 hover:border-blue-300'
            ]"
          >
            {{ grupo.nombre_grupo }}
          </button>
        </div>

        <form v-if="grupoSeleccionado" @submit.prevent="guardarPermisos" class="space-y-6">
          <h3 class="text-lg font-semibold">
            ASIGNA AL GRUPO <span class="text-blue-600">{{ grupoSeleccionado.nombre_grupo }}</span> TENER PERMISO DE:
          </h3>

          <div class="space-y-4">
            <div v-for="cu in listaCUs" :key="cu.codigo" class="flex items-start">
              <input
                v-model="permisosSeleccionados"
                :value="cu.codigo"
                type="checkbox"
                class="mt-1 w-4 h-4 cursor-pointer"
              />
              <label class="ml-3 text-slate-900 font-medium cursor-pointer">
                {{ cu.codigo }} - {{ cu.descripcion }}
              </label>
            </div>
          </div>

          <div class="flex justify-end pt-6">
            <button
              type="submit"
              class="px-8 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition"
            >
              GUARDAR PERMISOS
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const axios = window.axios;

const activeSection = ref('registrar');
const grupoSeleccionado = ref(null);
const grupos = ref(page.props.grupos || []);
const permisosSeleccionados = ref([]);
const editarModo = ref(false);
const personaEdit = reactive({
  tipo: '',
  id: null,
  nombre: '',
  apellido: '',
  ci: '',
  email: '',
  telefono: '',
  direccion: '',
  ciudad: '',
});

const formulario = reactive({
  rol: '',
  nombre: '',
  apellido: '',
  ci: '',
  fecha_nacimiento: '',
  sexo: '',
  telefono: '',
  email: '',
  ciudad: '',
  direccion: '',
  colegio: '',
  titulo_bachiller: '',
  especialidad: '',
  area: '',
  maestria: '',
  diplomado: '',
  horario_trabajo: '',
  profesion: '',
  nro_titulo: '',
  dias_trabajo: [],
  hora_entrada: '',
  hora_salida: '',
});

const personasActuales = reactive({
  tipo: null,
  lista: [],
  mensaje: '',
});

const listaCUs = [
  { codigo: 'CU01', descripcion: 'Iniciar Sesión' },
  { codigo: 'CU02', descripcion: 'Gestionar Usuarios y Roles' },
  { codigo: 'CU03', descripcion: 'Gestionar Postulantes' },
  { codigo: 'CU04', descripcion: 'Registrar Pagos y Validación' },
  { codigo: 'CU05', descripcion: 'Registrar Calificaciones por Materia' },
  { codigo: 'CU06', descripcion: 'Calcular Promedio y Estado' },
  { codigo: 'CU07', descripcion: 'Configurar Cupos por Carrera' },
  { codigo: 'CU08', descripcion: 'Asignar Cupos por Primera y Segunda Opción' },
  { codigo: 'CU09', descripcion: 'Calcular Cantidad de Grupos Habilitados' },
  { codigo: 'CU10', descripcion: 'Asignar Postulantes a Grupos' },
  { codigo: 'CU15', descripcion: 'Importación Masiva de Datos' },
  { codigo: 'CU20', descripcion: 'Auditoría de Operaciones mediante Bitácora' },
];

const guardarCuenta = async () => {
  try {
    const response = await axios.post('/cu02/registrar-cuenta', formulario);
    
    if (response.data.credenciales) {
      // Mensaje según el rol
      const rolLabel = formulario.rol === 'administrativo' ? 'Administrativo' : 'Docente';
      alert(
        `${rolLabel} registrado correctamente\n\n` +
        `CREDENCIALES ENVIADAS AL CORREO:\n` +
        `${formulario.email}\n\n` +
        `Para iniciar sesión en http://127.0.0.1:8000/postularse/ingresar:\n` +
        `Registro: ${response.data.credenciales.registro}\n` +
        `CI: ${response.data.credenciales.ci}\n` +
        `Contraseña: ${response.data.credenciales.ci}\n` +
        `Rol: ${rolLabel}`
      );
    } else {
      alert('Cuenta registrada correctamente');
    }
    
    // Limpiar formulario
    Object.keys(formulario).forEach(key => {
      if (Array.isArray(formulario[key])) {
        formulario[key] = [];
      } else {
        formulario[key] = '';
      }
    });
  } catch (error) {
    alert('Error al registrar: ' + error.response?.data?.message || error.message);
  }
};

const cargarPersonas = async (tipo) => {
  try {
    const response = await axios.get(`/cu02/personas/${tipo}`);
    personasActuales.lista = response.data;
    personasActuales.tipo = tipo;
    personasActuales.mensaje = response.data.length === 0 ? 'No se encontraron registros para ' + tipo : '';
  } catch (error) {
    console.error('Error cargando personas:', error);
    personasActuales.lista = [];
    personasActuales.tipo = tipo;
    personasActuales.mensaje = 'No se pudo cargar la lista. Revisa la consola.';
  }
};

const editarPersona = (tipo, persona) => {
  editarModo.value = true;
  personaEdit.tipo = tipo;
  personaEdit.id = persona.id;
  personaEdit.nombre = persona.nombre;
  personaEdit.apellido = persona.apellido;
  personaEdit.ci = persona.ci;
  personaEdit.email = persona.correo_electronico;
  personaEdit.telefono = persona.telefono || '';
  personaEdit.direccion = persona.direccion || '';
  personaEdit.ciudad = persona.ciudad || '';
};

const cancelarEdicion = () => {
  editarModo.value = false;
  personaEdit.tipo = '';
  personaEdit.id = null;
  personaEdit.nombre = '';
  personaEdit.apellido = '';
  personaEdit.ci = '';
  personaEdit.email = '';
  personaEdit.telefono = '';
  personaEdit.direccion = '';
  personaEdit.ciudad = '';
};

const actualizarPersona = async () => {
  const tipoActual = personaEdit.tipo;
  try {
    await axios.patch(`/cu02/persona/${tipoActual}/${personaEdit.id}`, {
      nombre: personaEdit.nombre,
      apellido: personaEdit.apellido,
      ci: personaEdit.ci,
      email: personaEdit.email,
      telefono: personaEdit.telefono,
      direccion: personaEdit.direccion,
      ciudad: personaEdit.ciudad,
    });
    alert('Persona actualizada correctamente');
    cancelarEdicion();
    await cargarPersonas(tipoActual);
  } catch (error) {
    alert('Error al actualizar persona: ' + (error.response?.data?.message || error.message));
  }
};

const eliminarPersona = async (tipo, personaId) => {
  if (confirm('¿Está seguro de que desea eliminar esta persona?')) {
    try {
      const response = await axios.delete(`/cu02/persona/${tipo}/${personaId}`);
      alert('Persona eliminada correctamente');
      await cargarPersonas(tipo);
    } catch (error) {
      const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message;
      console.error('Error completo:', error);
      alert('Error al eliminar: ' + errorMessage);
    }
  }
};

const seleccionarGrupo = (grupo) => {
  grupoSeleccionado.value = grupo;
  permisosSeleccionados.value = page.props.permisos?.[grupo.codigo] || [];
};

const guardarPermisos = async () => {
  try {
    const cus = listaCUs.filter(cu => permisosSeleccionados.value.includes(cu.codigo));
    await axios.post('/cu02/asignar-permisos', {
      grupo_rol_id: grupoSeleccionado.value.codigo,
      cus: cus,
    });
    alert('Permisos asignados correctamente');
  } catch (error) {
    alert('Error al asignar permisos: ' + error.message);
  }
};

const volver = () => {
  window.history.back();
};

// Cargar datos iniciales
// grupos ya vienen por props desde el servidor
</script>
