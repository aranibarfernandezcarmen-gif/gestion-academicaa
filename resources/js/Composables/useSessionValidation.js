import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * Composable para validación de sesión en tiempo real
 * Valida cada 2 segundos si la sesión sigue siendo válida
 * Si detecta invalidación, redirige inmediatamente a /
 */
export function useSessionValidation() {
    const isSessionValid = ref(true);
    const isValidating = ref(false);
    let validationInterval = null;
    let abortController = null;

    /**
     * Validar sesión llamando al servidor
     */
    const validateSession = async () => {
        if (isValidating.value) return; // Evitar llamadas concurrentes

        isValidating.value = true;

        try {
            // Cancelar request anterior si existe
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            // Llamada GET sin caché a endpoint de validación
            const response = await axios.get('/api/validar-sesion', {
                signal: abortController.signal,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0',
                },
                timeout: 3000, // Timeout de 3 segundos
            });

            // Si recibimos 200, sesión es válida
            if (response.status === 200 && response.data.valid) {
                isSessionValid.value = true;
                return true;
            }

        } catch (error) {
            // Si error 401, 403, o redirección, sesión no válida
            if (error.response?.status === 401 || error.response?.status === 403) {
                console.warn('⚠️ Sesión invalidada detectada');
                isSessionValid.value = false;
                redirectToLogin('Sesión cerrada en otro dispositivo');
                return false;
            }

            // Si es error de red, ignorar (puede ser conexión lenta)
            if (error.code === 'ECONNABORTED' || error.code === 'ENOTFOUND') {
                console.debug('Red: Timeout de validación');
                return true;
            }

            // Otros errores
            console.debug('Error validando sesión:', error.message);
            return true;
        } finally {
            isValidating.value = false;
        }
    };

    /**
     * Redirigir al login con mensaje
     */
    const redirectToLogin = (message = 'Debe iniciar sesión') => {
        // Si ya hay un error en la URL o estamos en página pública, no redirigir
        const currentUrl = window.location.href;
        if (currentUrl.includes('error=') || currentUrl === '/' || currentUrl.endsWith('/?')) {
            console.debug('No redirigiendo - ya hay error o estamos en página pública');
            return;
        }

        // Limpiar intervalo
        stopValidation();

        // Redirigir inmediatamente
        window.location.href = `/?error=${encodeURIComponent(message)}`;
    };

    /**
     * Iniciar validación periódica cada 2 segundos
     */
    const startValidation = () => {
        if (validationInterval) return; // Ya está corriendo

        console.debug('🔄 Iniciando validación de sesión cada 2 segundos');

        // Validación inmediata al iniciar
        validateSession();

        // Luego cada 2 segundos
        validationInterval = setInterval(() => {
            validateSession();
        }, 2000);
    };

    /**
     * Detener validación periódica
     */
    const stopValidation = () => {
        if (validationInterval) {
            clearInterval(validationInterval);
            validationInterval = null;
            console.debug('⏹️ Validación de sesión detenida');
        }
    };

    /**
     * Hook de montaje - iniciar validación
     */
    onMounted(() => {
        startValidation();

        // También validar cuando la pestaña se vuelve visible
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                console.debug('👀 Pestaña visible, validando sesión...');
                validateSession();
            }
        });

        // Validar cuando vuelve del historial (pageshow)
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                console.debug('📜 Página restaurada del caché, validando sesión...');
                validateSession();
            }
        });
    });

    /**
     * Hook de desmontaje - limpiar intervalo
     */
    onUnmounted(() => {
        stopValidation();
    });

    return {
        isSessionValid,
        isValidating,
        validateSession,
        startValidation,
        stopValidation,
        redirectToLogin,
    };
}

export default useSessionValidation;
