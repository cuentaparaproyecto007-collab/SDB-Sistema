<template>
  <div v-if="isBackendDown" class="error-overlay animate__animated animate__fadeIn">
    <section class="page_500">
      <div class="container-error">
        <div class="four_zero_four_bg">
          <h1 class="text-code">500</h1>
        </div>
        
        <div class="contant_box_500">
          <h3 class="error-title">⚠️ Conexión Interrumpida</h3>
          <p class="error-msg">El servidor central del Sistema S.D.B. (Laravel) no responde en este momento.</p>
          
          <button @click="reintentarConexion" class="btn-retry">
            🔄 Reintentar Conexión
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { isBackendDown, checkServerAgain } from '../services/networkState';

const reintentarConexion = () => {
  // Desactiva el bloqueo y recarga la página para verificar el backend
  checkServerAgain();
};
</script>

<style scoped>
/* Bloqueo total de pantalla elegante */
.error-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: #ffffff;
  z-index: 999999; /* Asegura estar por encima del mapa y modales */
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.page_500 { 
  background: #fff; 
  text-align: center;
  padding: 20px;
}

.container-error {
  max-width: 600px;
  margin: 0 auto;
}

/* El GIF animado exacto que querías incluir */
.four_zero_four_bg {
  background-image: url(https://cdn.dribbble.com/users/285475/screenshots/2083086/dribbble_1.gif);
  height: 380px;
  background-position: center;
  background-repeat: no-repeat;
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.text-code {
  font-size: 90px;
  font-weight: 900;
  color: #2c3e50;
  margin: 0;
  letter-spacing: -2px;
}

.contant_box_500 { 
  margin-top: -30px; 
}

.error-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #e74c3c;
  margin-bottom: 12px;
}

.error-msg {
  color: #7f8c8d;
  font-size: 1.05rem;
  margin-bottom: 30px;
}

/* Botón interactivo con sutiles efectos de elevación */
.btn-retry {
  color: #ffffff;
  padding: 14px 35px;
  background: #34495e;
  border: none;
  border-radius: 30px;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(52, 73, 94, 0.25);
  transition: all 0.25s ease;
}

.btn-retry:hover {
  background: #42b983; /* Cambia al verde característico de tu sistema */
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(66, 185, 131, 0.35);
}

.btn-retry:active {
  transform: translateY(0);
}
</style>