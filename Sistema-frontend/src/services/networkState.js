import { ref } from 'vue';

// 🚩 Bandera global: 'true' si el servidor se cae, 'false' si opera con normalidad
export const isBackendDown = ref(false);

/**
 * Función para intentar restablecer la conexión
 * Al llamarla, apaga el banner de error e intenta recargar la vista actual
 */
export const checkServerAgain = () => {
  isBackendDown.value = false;
  
  // Forzamos una recarga limpia del navegador para volver a disparar 
  // las peticiones iniciales del ciclo de vida 'onMounted'
  window.location.reload();
};