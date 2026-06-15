<template>
  <div class="simulator-page-container animate__animated animate__fadeIn">
    
    <div class="dashboard-header-tech">
      <h3 class="title-tech">⚡ Laboratorio Técnico IoT</h3>
      <p class="text-subtitle">Entorno de simulación de red para el envío de telemetría acelerométrica en tiempo real.</p>
    </div>

    <div class="simulator-grid-layout">
      
      <div class="control-side">
        
        <div class="card-tech">
          <h5 class="card-title-tech">📡 Parámetros del Hardware</h5>
          <hr class="divider-tech">

          <div class="form-group-tech">
            <label class="label-tech">CÓDIGO DE HARDWARE (SENSOR)</label>
            <input v-model="payload.sensor_codigo" type="text" class="input-tech" placeholder="Ej: SNS-001">
            <small class="help-tech">Debe coincidir con un sensor activo registrado en tu base de datos.</small>
          </div>

          <div class="form-group-tech">
            <label class="label-tech">📍 PRESETS GEOGRÁFICOS DE PRUEBA (LA PAZ)</label>
            <select @change="cargarPreset" class="input-tech select-tech">
              <option value="">-- Seleccionar punto de impacto --</option>
              <option v-for="(p, index) in presetsLaPaz" :key="index" :value="index">
                {{ p.nombre }}
              </option>
            </select>
          </div>

          <div class="coordinates-row">
            <div class="form-group-tech flex-1">
              <label class="label-tech">LATITUD</label>
              <input v-model.number="payload.latitud" type="number" step="any" class="input-tech">
            </div>
            <div class="form-group-tech flex-1">
              <label class="label-tech">LONGITUD</label>
              <input v-model.number="payload.longitud" type="number" step="any" class="input-tech">
            </div>
          </div>
        </div>

        <div class="card-tech shadow-pulse">
          <h5 class="card-title-tech">🚀 Inyección de Impactos Viales</h5>
          <p class="text-subtitle mb-3">Emite una ráfaga de datos por protocolo HTTP POST simulando el bache físico.</p>
          <hr class="divider-tech">

          <div class="buttons-stack">
            <button @click="dispararSimulacion(1.5)" :disabled="loading" class="btn-trigger btn-leve">
              <span>🟢 Simular Impacto Leve</span>
              <span class="badge-g">1.5 G</span>
            </button>

            <button @click="dispararSimulacion(3.2)" :disabled="loading" class="btn-trigger btn-moderado">
              <span>🟡 Simular Impacto Moderado</span>
              <span class="badge-g">3.2 G</span>
            </button>

            <button @click="dispararSimulacion(5.4)" :disabled="loading" class="btn-trigger btn-critico">
              <span>🔴 Simular Impacto Crítico</span>
              <span class="badge-g">5.4 G</span>
            </button>
          </div>
        </div>

      </div>

      <div class="console-side">
        <div class="card-tech console-card d-flex flex-column">
          <h5 class="card-title-tech text-cyan">🖥️ Monitor de Datos del Servidor (Response)</h5>
          <hr class="divider-tech">

          <div class="terminal-box">
            <div v-if="loading" class="terminal-loading">
              <div class="spinner-border-custom"></div>
              <span>Procesando ráfaga de telemetría vial...</span>
            </div>

            <div v-else-if="respuestaServidor" class="terminal-content">
              <div class="status-line success-line">✔️ HTTP/1.1 201 Created</div>
              <pre class="json-render">{{ JSON.stringify(respuestaServidor, null, 2) }}</pre>
            </div>

            <div v-else-if="errorServidor" class="terminal-content">
              <div class="status-line error-line">❌ Error en la Transmisión de Red</div>
              <pre class="json-render text-danger-tech">{{ JSON.stringify(errorServidor, null, 2) }}</pre>
            </div>

            <div v-else class="terminal-placeholder">
              <p class="console-icon">📟</p>
              <p class="main-placeholder-text">Esperando recepción de tramas IoT...</p>
              <small class="text-muted-tech">Configura los parámetros a la izquierda y ejecuta un disparo de fuerza.</small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const loading = ref(false);
const respuestaServidor = ref(null);
const errorServidor = ref(null);

const payload = ref({
  sensor_codigo: 'SNS-001', 
  latitud: -16.5042,
  longitud: -68.1305,
  fuerza_g: 0
});

const presetsLaPaz = [
  { nombre: 'Av. Arce (Plaza Bolivia)', lat: -16.5042, lng: -68.1305 },
  { nombre: 'Av. Mariscal Santa Cruz (El Prado)', lat: -16.4994, lng: -68.1352 },
  { nombre: 'Calacoto (Calle 21)', lat: -16.5415, lng: -68.0872 },
  { nombre: 'Sopocachi (Plaza Abaroa)', lat: -16.5098, lng: -68.1256 }
];

const cargarPreset = (event) => {
  const index = event.target.value;
  if (index !== "") {
    payload.value.latitud = presetsLaPaz[index].lat;
    payload.value.longitud = presetsLaPaz[index].lng;
  }
};

const dispararSimulacion = async (fuerza) => {
  loading.value = true;
  respuestaServidor.value = null;
  errorServidor.value = null;
  payload.value.fuerza_g = fuerza;

  try {
    const response = await axios.post('https://sdb-sistema-production.up.railway.app/api/telemetria/detectar', payload.value);
    respuestaServidor.value = response.data;
  } catch (error) {
    console.error("Error en la telemetría:", error);
    errorServidor.value = error.response?.data || { message: 'No se pudo conectar con el servidor backend.' };
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.simulator-page-container { width: 100%; max-width: 1200px; margin: 0 auto; }
.dashboard-header-tech { margin-bottom: 25px; }
.title-tech { color: #2c3e50; font-weight: 800; letter-spacing: -0.5px; margin: 0; font-size: 1.6rem; }
.text-subtitle { color: #7f8c8d; font-size: 0.9rem; margin-top: 4px; }

/* 🍱 ESTRUCTURA GRID FLEX SPLIT SCREEN BILATERAL */
.simulator-grid-layout {
  display: flex;
  gap: 30px;
  width: 100%;
  align-items: flex-start;
}

.control-side {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.console-side {
  flex: 1;
  height: 100%;
}

/* Tarjetas */
.card-tech {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}
.console-card {
  height: 590px; /* Equilibra la altura exacta del panel izquierdo */
}
.card-title-tech { color: #34495e; font-weight: 700; font-size: 1.05rem; margin: 0; }
.text-cyan { color: #16a085 !important; }
.divider-tech { border: 0; border-top: 1px solid #edf2f7; margin: 12px 0 18px 0; }

/* Inputs y Selects */
.form-group-tech { margin-bottom: 18px; display: flex; flex-direction: column; }
.label-tech { font-size: 0.75rem; font-weight: 700; color: #718096; letter-spacing: 0.05rem; margin-bottom: 6px; text-align: left; }
.input-tech {
  width: 100%;
  height: 46px;
  padding: 10px 14px;
  border: 2px solid #cbd5e1;
  border-radius: 10px;
  font-weight: 500;
  color: #334155;
  outline: none;
  transition: all 0.2s ease;
  background: #ffffff;
}
.select-tech { cursor: pointer; }
.input-tech:focus { border-color: #42b983; box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.15); }
.help-tech { color: #94a3b8; font-size: 0.72rem; margin-top: 5px; display: block; text-align: left; }

/* Fila de coordenadas */
.coordinates-row { display: flex; gap: 15px; width: 100%; }
.flex-1 { flex: 1; }

/* Botones de Impacto */
.buttons-stack { display: flex; flex-direction: column; gap: 12px; }
.btn-trigger {
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.95rem;
}
.btn-trigger:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 5px 12px rgba(0,0,0,0.08); }
.btn-trigger:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-leve { background-color: rgba(46, 204, 113, 0.1); color: #27ae60; }
.btn-leve:hover:not(:disabled) { background-color: #2ecc71; color: white; }
.btn-moderado { background-color: rgba(241, 196, 15, 0.1); color: #d35400; }
.btn-moderado:hover:not(:disabled) { background-color: #f1c40f; color: white; }
.btn-critico { background-color: rgba(231, 76, 60, 0.1); color: #c0392b; }
.btn-critico:hover:not(:disabled) { background-color: #e74c3c; color: white; }

.badge-g { background: rgba(0, 0, 0, 0.05); padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 800; }
.btn-trigger:hover .badge-g { background: rgba(255, 255, 255, 0.2); }

/* 🖥️ TERMINAL CONSOLA DARK AUTOMATIZADA */
.terminal-box {
  background: #0f172a;
  border-radius: 12px;
  padding: 20px;
  font-family: 'Courier New', Courier, monospace;
  overflow-y: auto;
  flex-grow: 1;
  height: 100%;
  max-height: 490px;
  border: 1px solid #1e293b;
}
.terminal-placeholder {
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #64748b;
  text-align: center;
  padding-top: 80px;
}
.console-icon { font-size: 2.8rem; margin: 0 0 10px 0; }
.main-placeholder-text { font-weight: 700; font-size: 0.95rem; color: #94a3b8; margin-bottom: 5px; }
.text-muted-tech { color: #475569; font-size: 0.8rem; }

.terminal-loading { color: #38bdf8; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 15px; font-weight: bold; padding-top: 100px; }
.spinner-border-custom { width: 30px; height: 30px; border: 3px solid rgba(56, 189, 248, 0.2); border-top-color: #38bdf8; border-radius: 50%; animation: spin 0.8s linear infinite; }

.status-line { font-weight: bold; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
.success-line { color: #34d399; }
.error-line { color: #f87171; }
.json-render { color: #38bdf8; font-size: 0.85rem; margin: 0; white-space: pre-wrap; word-wrap: break-word; text-align: left; line-height: 1.5; }
.text-danger-tech { color: #f87171 !important; }

@keyframes spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 992px) {
  .simulator-grid-layout { flex-direction: column; }
  .console-card { height: 400px; }
}
</style>