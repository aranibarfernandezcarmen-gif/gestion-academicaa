<template>
  <footer id="contacto" class="bg-gray-900 text-white py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8 mb-12">
        <!-- Sobre FICCT -->
        <div>
          <h3 class="text-xl font-bold mb-4">FICCT</h3>
          <p class="text-gray-400 text-sm leading-relaxed">
            Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones. Formando profesionales de excelencia.
          </p>
        </div>

        <!-- Enlaces Rápidos -->
        <div>
          <h3 class="text-lg font-bold mb-4">Enlaces Rápidos</h3>
          <ul class="space-y-2 text-sm text-gray-400">
            <li><a href="#inicio" class="hover:text-white transition">Inicio</a></li>
            <li><a href="#carreras" class="hover:text-white transition">Carreras</a></li>
            <li><a href="#proceso" class="hover:text-white transition">Proceso</a></li>
            <li><a href="#" class="hover:text-white transition">Contacto</a></li>
          </ul>
        </div>

        <!-- Información -->
        <div>
          <h3 class="text-lg font-bold mb-4">Información</h3>
          <div class="space-y-3 text-sm text-gray-400">
            <div class="flex items-start">
              <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.821l.074.5c.149.998.33 2.01.5 3.02.063.405-.127.782-.502 1.06l-1.497 1.175c-.447-.999-1.395-2.491-1.395-2.491s.656-1.159 1.313-2.42B12.126 6.5m0 0l.5-.5"></path>
              </svg>
              <span>+591 2 123 4567</span>
            </div>
            <div class="flex items-start">
              <svg class="w-5 h-5 mr-2 mt-0.5 text-blue-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
              </svg>
              <span>info@ficct.edu.bo</span>
            </div>
          </div>
        </div>

        <!-- Redes Sociales -->
        <div>
          <h3 class="text-lg font-bold mb-4">Síguenos</h3>
          <div class="flex space-x-4">
            <a href="#" class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center hover:bg-blue-700 transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path>
              </svg>
            </a>
            <a href="#" class="w-10 h-10 bg-blue-400 rounded-full flex items-center justify-center hover:bg-blue-500 transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2s9 5 20 5a9.5 9.5 0 00-9-5.5c4.75 2.25 7-7 7-7a10.6 10.6 0 01-9-5.5z"></path>
              </svg>
            </a>
            <a href="#" class="w-10 h-10 bg-pink-600 rounded-full flex items-center justify-center hover:bg-pink-700 transition">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                <path d="M16 11.37A4 4 0 1112.63 8" stroke="white" stroke-width="2" fill="none"></path>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Separador -->
      <div class="border-t border-gray-800 pt-8">
        <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-400">
          <p>&copy; 2026 FICCT. Todos los derechos reservados.</p>
          <div class="flex space-x-6 mt-4 md:mt-0">
            <a href="#" class="hover:text-white transition">Privacidad</a>
            <a href="#" class="hover:text-white transition">Términos</a>
            <a href="#" class="hover:text-white transition">Cookies</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
