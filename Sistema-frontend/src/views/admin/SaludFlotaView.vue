<template>
  <div class="health-container">
    <div class="header-box">
      <h2>📊 Telemetría y Salud Mecánica de la Flota</h2>
      <p>Diagnóstico predictivo de los sistemas de suspensión basado en la fuerza y frecuencia de los impactos detectados por los sensores IoT viales.</p>
    </div>

    <div class="grid-flota">
      <div 
        v-for="veh in flota" 
        :key="veh.id" 
        class="veh-card" 
        :class="'card-' + obtenerClaseEstado(veh.estado_salud)"
      >
        <div class="card-header-veh d-flex justify-content-between align-items-center">
          <div>
            <span class="type-badge">{{ veh.tipo }}</span>
            <h3>🚗 {{ veh.marca }} {{ veh.modelo }}</h3>
          </div>
          <div class="plate-box">{{ veh.placa }}</div>
        </div>

        <div class="card-body-veh mt-3">
          
          <div class="stats-row mb-3">
            <div class="stat-item">
              <span class="stat-label">Impactos Totales</span>
              <span class="stat-val text-dark">{{ veh.total_baches }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Impactos Críticos (Alta G)</span>
              <span class="stat-val text-danger">💥 {{ veh.baches_criticos }}</span>
            </div>
          </div>

          <div class="health-section">
            <div class="d-flex justify-content-between mb-1 font-weight-bold">
              <span class="text-secondary">Vida Útil de Suspensión:</span>
              <span :class="'text-' + obtenerClaseEstado(veh.estado_salud)">
                {{ parseFloat(veh.salud_suspension).toFixed(1) }}%
              </span>
            </div>
            <div class="progress-bar-bg">
              <div 
                class="progress-bar-fill" 
                :class="'bg-' + obtenerClaseEstado(veh.estado_salud)" 
                :style="{ width: veh.salud_suspension + '%' }"
              ></div>
            </div>
          </div>

          <div class="recommendation-box mt-3" :class="'box-' + obtenerClaseEstado(veh.estado_salud)">
            <p class="m-0">{{ veh.recommendation || veh.recomendacion }}</p>
          </div>

          <button @click="abrirHistorial(veh)" class="btn-history-trigger mt-3">
            <i class="bi bi-journal-medical me-2"></i> Ver Historial de Reparaciones
          </button>
          
        </div>
      </div>
    </div>

    <div v-if="mostrarModal" class="sdb-modal-overlay" @click.self="cerrarModal">
      <div class="sdb-modal-card">
        <div class="sdb-modal-header">
          <div>
            <h5>📋 Historial de Soporte Técnico</h5>
            <h4>Unidad: {{ vehiculoSeleccionado.marca }} {{ vehiculoSeleccionado.modelo }} [{{ vehiculoSeleccionado.placa }}]</h4>
          </div>
          
          <div class="d-flex align-items-center">
            <button 
              v-if="!cargandoHistorial && historialReparaciones.length > 0" 
              @click="generarHistorialPDF(vehiculoSeleccionado.id)" 
              class="btn-pdf-modal me-3"
            >
              <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
            </button>
            <button class="btn-close-modal" @click="cerrarModal"><i class="bi bi-x-lg"></i></button>
          </div>

        </div>
        <div class="sdb-modal-body">
          <div v-if="cargandoHistorial" class="modal-loading">
            <i class="bi bi-arrow-clockwise spin-icon"></i> Consultando base de datos relacional...
          </div>
          <div v-else-if="historialReparaciones.length === 0" class="modal-no-data">
            <i class="bi bi-info-circle me-2"></i> Este vehículo no registra ingresos previos al taller mecánico.
          </div>
          <div v-else class="table-container-modal">
            <table class="modal-table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Especialidad</th>
                  <th>Trabajo Realizado</th>
                  <th>Encargado</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in historialReparaciones" :key="item.id">
                  <td class="text-nowrap font-weight-bold text-dark">{{ item.fecha }}</td>
                  <td>
                    <span class="badge-tipo" :class="item.tipo === 'Mecánico' ? 'tipo-mec' : 'tipo-elec'">
                      {{ item.tipo }}
                    </span>
                  </td>
                  <td class="desc-text">{{ item.descripcion }}</td>
                  <td class="text-muted">{{ item.tecnico }}</td>
                  <td>
                    <span class="status-pill" :class="item.estado === 'En Curso' ? 'pill-progress' : 'pill-done'">
                      {{ item.estado }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const flota = ref([]);
const token = localStorage.getItem('token');
const config = { headers: { 'Authorization': `Bearer ${token}` } };

// Estados para el control del Modal
const mostrarModal = ref(false);
const cargandoHistorial = ref(false);
const vehiculoSeleccionado = ref(null);
const historialReparaciones = ref([]);

const cargarDiagnosticos = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/vehiculos-salud-flota', config);
    flota.value = res.data;
  } catch (error) {
    console.error("Error al recopilar la analítica de suspensión IoT:", error);
  }
};

const abrirHistorial = async (veh) => {
  vehiculoSeleccionado.value = veh;
  mostrarModal.value = true;
  cargandoHistorial.value = true;
  try {
    const res = await axios.get(`http://localhost:8000/api/vehiculos/${veh.id}/historial-mantenimientos`, config);
    historialReparaciones.value = res.data.historial; 
  } catch (error) {
    console.error("Error al recuperar expediente clínico del vehículo:", error);
  } finally {
    cargandoHistorial.value = false;
  }
};

// 🔥 NUEVA FUNCIÓN: Descarga binaria segura mediante Blobs con cabeceras Auth
const generarHistorialPDF = async (id) => {
  try {
    const response = await axios.get(`http://localhost:8000/api/vehiculos/${id}/exportar-historial-pdf`, {
      headers: { 'Authorization': `Bearer ${token}` },
      responseType: 'blob' // 👈 Indispensable para descarga de archivos binarios
    });
    
    // Convertimos el flujo binario a un enlace descargable en caliente
    const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = blobUrl;
    link.setAttribute('download', `expediente_clinico_${vehiculoSeleccionado.value.placa}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(blobUrl);
  } catch (error) {
    console.error("Error crítico al compilar el PDF institucional:", error);
    alert("Hubo un inconveniente al generar el documento PDF.");
  }
};

const cerrarModal = () => {
  mostrarModal.value = false;
  vehiculoSeleccionado.value = null;
  historialReparaciones.value = [];
};

const obtenerClaseEstado = (estado) => {
  if (estado === 'Óptimo') return 'optimo';
  if (estado === 'Preventivo') return 'preventivo';
  if (estado === 'Crítico') return 'critico';
  return 'optimo';
};

onMounted(cargarDiagnosticos);
</script>

<style scoped>
/* ESTILOS DE LA COPA BASE */
.health-container { padding: 25px; font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f8fafc; min-height: 90vh; }
.header-box { margin-bottom: 25px; background: #1e293b; color: white; padding: 20px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.header-box h2 { margin: 0; font-size: 1.6rem; font-weight: 600; }
.header-box p { margin: 5px 0 0 0; opacity: 0.8; font-size: 0.95rem; line-height: 1.45; }

.grid-flota { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; }
.veh-card { background: white; border-radius: 14px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; border-top: 5px solid #2ecc71; display: flex; flex-direction: column; justify-content: space-between; }

.card-optimo { border-top-color: #2ecc71; }
.card-preventivo { border-top-color: #f1c40f; }
.card-critico { border-top-color: #e74c3c; background: #fffdfd; }

.card-header-veh h3 { margin: 3px 0 0 0; color: #1e293b; font-size: 1.25rem; font-weight: 600; }
.type-badge { font-size: 0.72rem; text-transform: uppercase; font-weight: bold; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; color: #475569; }
.plate-box { background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-weight: bold; color: #334155; font-family: monospace; font-size: 1rem; }

.stats-row { display: flex; gap: 12px; }
.stat-item { flex: 1; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #f1f5f9; text-align: center; }
.stat-label { display: block; font-size: 0.78rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
.stat-val { font-size: 1.3rem; font-weight: bold; }

.progress-bar-bg { background: #e2e8f0; height: 10px; border-radius: 20px; overflow: hidden; margin-top: 5px; }
.progress-bar-fill { height: 100%; transition: width 0.4s ease; }

.bg-optimo { background: #2ecc71; }
.bg-preventivo { background: #f1c40f; }
.bg-critico { background: #e74c3c; }

.text-optimo { color: #2ecc71; font-weight: bold; }
.text-preventivo { color: #f39c12; font-weight: bold; }
.text-critico { color: #e74c3c; font-weight: bold; }

.recommendation-box { padding: 12px 16px; border-radius: 8px; font-size: 0.88rem; line-height: 1.45; }
.box-optimo { background: #e8f8f5; color: #1e8449; border-left: 4px solid #2ecc71; }
.box-preventivo { background: #fef9e7; color: #7d6608; border-left: 4px solid #f1c40f; }
.box-critico { background: #fdedec; color: #78281f; border-left: 4px solid #e74c3c; }

.btn-history-trigger { width: 100%; background: #ffffff; border: 1px solid #cbd5e1; padding: 10px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s ease-in-out; display: flex; align-items: center; justify-content: center; }
.btn-history-trigger:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }

/* ==========================================================================
   🎨 ESTILOS DEL MODAL PREMIUM DEL ENTORNO S.D.B.
   ========================================================================== */
.sdb-modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.45); display: flex; align-items: center; justify-content: center; z-index: 1050; backdrop-filter: blur(2px); }
.sdb-modal-card { background: #ffffff; width: 90%; max-width: 800px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); overflow: hidden; display: flex; flex-direction: column; animation: modalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1); }

.sdb-modal-header { padding: 18px 24px; background: #1e293b; color: #ffffff; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #42b983; }
.sdb-modal-header h5 { margin: 0; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8; font-weight: 700; }
.sdb-modal-header h4 { margin: 4px 0 0 0; font-size: 1.2rem; font-weight: 700; }

/* 🔥 NUEVO: Estilo del botón PDF interno del modal */
.btn-pdf-modal { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; border: none; padding: 6px 14px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.2); transition: all 0.15s ease; }
.btn-pdf-modal:hover { transform: translateY(-1px); background: linear-gradient(135deg, #ff6b6b 0%, #e74c3c 100%); box-shadow: 0 6px 14px rgba(231, 76, 60, 0.35); }

.btn-close-modal { background: transparent; border: none; color: #ffffff; font-size: 1.2rem; cursor: pointer; opacity: 0.7; transition: opacity 0.15s; }
.btn-close-modal:hover { opacity: 1; }

.sdb-modal-body { padding: 24px; max-height: 70vh; overflow-y: auto; }
.modal-loading { text-align: center; padding: 40px; color: #64748b; font-weight: 500; }
.spin-icon { display: inline-block; animation: spin 1s linear infinite; margin-right: 8px; font-size: 1.1rem; }
.modal-no-data { text-align: center; padding: 45px; color: #94a3b8; font-style: italic; }

.table-container-modal { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.modal-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
.modal-table th { background: #f8fafc; padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; }
.modal-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
.modal-table tbody tr:last-child td { border-bottom: none; }

.desc-text { color: #334155; line-height: 1.4; font-size: 0.85rem; max-width: 320px; }
.text-nowrap { white-space: nowrap; }

.badge-tipo { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
.tipo-mec { background: #eff6ff; color: #1e40af; }
.tipo-elec { background: #fffbeb; color: #b45309; }

.status-pill { padding: 4px 8px; border-radius: 20px; font-size: 0.74rem; font-weight: 700; }
.pill-progress { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
.pill-done { background: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }

@keyframes modalFadeIn { from { opacity: 0; transform: scale(0.97); } to { opacity: 1; transform: scale(1); } }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>