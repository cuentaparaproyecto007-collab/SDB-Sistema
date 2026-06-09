<template>
  <div class="gestion-container animate__animated animate__fadeIn">
    <div class="row-layout">
      
      <aside class="form-sidebar">
        <div class="card shadow-soft">
          <div class="card-header" :class="{ 'bg-orange': isEditing }">
            <i class="icon">{{ isEditing ? '✏️' : '🚘' }}</i> {{ isEditing ? 'Editar Vehículo' : 'Registrar Vehículo' }}
          </div>
          <form @submit.prevent="guardarVehiculo" class="form-body">
            
            <div class="form-group">
              <label>Número de Placa</label>
              <input v-model="form.placa" type="text" placeholder="Ej: 2587-UYT" required :disabled="loading">
            </div>
            
            <div class="form-group">
              <label>Marca</label>
              <input v-model="form.marca" type="text" placeholder="Ej: Toyota" required :disabled="loading">
            </div>

            <div class="form-group">
              <label>Modelo</label>
              <input v-model="form.modelo" type="text" placeholder="Ej: Hilux" required :disabled="loading">
            </div>

            <div class="form-group">
              <label>Tipo de Unidad</label>
              <select v-model="form.tipo" required :disabled="loading">
                <option value="Camioneta">Camioneta</option>
                <option value="Sedán">Sedán</option>
                <option value="Compacto">Compacto</option>
                <option value="Bus">Bus</option>
                <option value="Otro">Otro</option>
              </select>
            </div>

            <div class="form-group">
              <label>Sensor Telemetría IoT Asignado</label>
              <select v-model="form.sensor_id" :disabled="loading">
                <option :value="null">-- Ninguno (Sin hardware) --</option>
                <option v-if="isEditing && sensorActualAlEditar" :value="sensorActualAlEditar.id">
                  📌 {{ sensorActualAlEditar.codigo }} ({{ sensorActualAlEditar.modelo }}) - Asignado a este carro
                </option>
                <option v-for="s in sensoresDisponibles" :key="s.id" :value="s.id">
                  📟 {{ s.codigo }} ({{ s.modelo }})
                </option>
              </select>
              <small class="hint-success" v-if="sensoresDisponibles.length > 0">
                🟢 Hay {{ sensoresDisponibles.length }} sensores libres en almacén.
              </small>
            </div>

            <div class="actions btn-group-vertical">
              <button type="submit" class="btn-primary full-width" :class="{ 'btn-orange': isEditing }" :disabled="loading">
                <i class="icon">💾</i> {{ loading ? 'Procesando...' : (isEditing ? 'Actualizar Unidad' : 'Dar de Alta Vehículo') }}
              </button>
              <button v-if="isEditing" type="button" @click="cancelarEdicion" class="btn-secondary full-width mt-2" :disabled="loading">
                Cancelar Edición
              </button>
            </div>
          </form>
        </div>
      </aside>

      <main class="table-content">
        <div class="card border-none">
          <div class="card-header bg-dark">
            <div class="header-flex">
              <span><i class="icon">📋</i> Flota de Vehículos de Control Vial</span>
              
              <div class="global-export-actions">
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="🔍 Buscar placa, marca, tipo..." 
                  class="search-table-input"
                  :disabled="loading"
                >

                <button type="button" @click="exportarFlotaGeneralPdf" class="btn-global-export btn-pdf-color" :disabled="exportingGeneral">
                  📄 Exportar PDF
                </button>
                <button type="button" @click="exportarFlotaGeneralExcel" class="btn-global-export btn-excel-color" :disabled="exportingGeneral">
                  📊 Exportar Excel
                </button>
                <span class="count-badge">{{ vehiculosFiltrados.length }} Filtrados</span>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Placa</th>
                  <th>Especificaciones</th>
                  <th>Tipo</th>
                  <th>Hardware Acoplado</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in vehiculosFiltrados" :key="v.id" class="table-row" :class="{ 'row-editing': editingId === v.id }">
                  <td><span class="vehicle-plate"> {{ v.placa }}</span></td>
                  <td><b>{{ v.marca }}</b> {{ v.modelo }}</td>
                  <td><span class="badge-type">{{ v.tipo }}</span></td>
                  <td>
                    <div class="hardware-tag" :class="v.sensor ? 'has-hardware' : 'no-hardware'">
                      {{ v.sensor ? `📟 ${v.sensor.codigo}` : '❌ Sin Hardware' }}
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="btn-actions-group">
                      <button type="button" @click="abrirModalInspeccion(v)" class="btn-action btn-view" title="Ver Baches Detectados">👁️</button>
                      <button :disabled="loading" @click="activarEdicion(v)" class="btn-action btn-edit" title="Editar Unidad">✏️</button>
                      <button :disabled="loading" @click="eliminarVehiculo(v.id, v.placa)" class="btn-action btn-delete" title="Dar de baja">🗑️</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="vehiculosFiltrados.length === 0 && !loading">
                  <td colspan="5" class="empty-state">
                    No se encontraron vehículos que coincidan con el criterio de búsqueda.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

    </div>

    <div v-if="showModal" class="modal-overlay animate__animated animate__fadeIn">
      <div class="modal-content animate__animated animate__zoomIn">
        <div class="modal-header">
          <h3>🔍 Auditoría de Campo — Placa [ {{ vehiculoSeleccionado?.placa }} ]</h3>
          <button type="button" @click="cerrarModal" class="close-modal-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <div class="vehiculo-summary">
            <p><b>Unidad Móvil:</b> {{ vehiculoSeleccionado?.marca }} {{ vehiculoSeleccionado?.modelo }} ({{ vehiculoSeleccionado?.tipo }})</p>
            <p><b>Hardware IoT:</b> <span class="badge-type">{{ vehiculoSeleccionado?.sensor ? vehiculoSeleccionado.sensor.codigo : 'Ninguno asignado' }}</span></p>
          </div>

          <div class="header-flex-modal">
            <h4>Baches Escaneados Automáticamente</h4>
            <button type="button" @click="descargarPdfReporte" class="btn-pdf" :disabled="downloadingPdf">
              📄 {{ downloadingPdf ? 'Generando PDF...' : 'Exportar Reporte PDF' }}
            </button>
          </div>

          <div class="modal-table-wrapper">
            <table class="modal-table">
              <thead>
                <tr>
                  <th>Fecha de Captura</th>
                  <th>Coordenadas (Lat, Lng)</th>
                  <th>Gravedad</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in bachesAsociados" :key="b.id">
                  <td>{{ formatFecha(b.created_at) }}</td>
                  <td class="geo-code">{{ b.latitud }}, {{ b.longitud }}</td>
                  <td>
                    <span :class="['badge-pill', getBadgeGravedad(b.estado)]">{{ b.estado }}</span>
                  </td>
                </tr>
                <tr v-if="bachesAsociados.length === 0">
                  <td colspan="3" class="empty-state text-center">
                    Este vehículo no registra baches detectados de forma automatizada por sensor.
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
// 🔥 MODIFICADO: Se añade 'computed' para el motor de filtros reactivo
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const vehiculos = ref([]);
const sensoresDisponibles = ref([]);
const loading = ref(false);
const token = localStorage.getItem('token');

// Control de Estados CRUD
const isEditing = ref(false);
const editingId = ref(null);
const sensorActualAlEditar = ref(null);
const form = ref({ placa: '', marca: '', modelo: '', tipo: 'Camioneta', sensor_id: null });

// Estados del Modal y descargas
const showModal = ref(false);
const downloadingPdf = ref(false);
const exportingGeneral = ref(false);
const vehiculoSeleccionado = ref(null);
const bachesAsociados = ref([]);

// 🔥 NUEVO: Variable reactiva para almacenar la caja de texto de búsqueda
const searchQuery = ref('');

const cargarDatosFlota = async () => {
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const resV = await axios.get('http://localhost:8000/api/vehiculos', config);
    vehiculos.value = resV.data;

    const resS = await axios.get('http://localhost:8000/api/sensores-disponibles', config);
    sensoresDisponibles.value = resS.data;
  } catch (e) {
    console.error("Error crítico de sincronización de la flota:", e);
  } finally {
    loading.value = false;
  }
};

// 🔥 NUEVO: Filtro inteligente automatizado en tiempo de ejecución
const vehiculosFiltrados = computed(() => {
  if (!searchQuery.value.trim()) {
    return vehiculos.value;
  }
  const query = searchQuery.value.toLowerCase().trim();
  return vehiculos.value.filter(v => {
    return v.placa.toLowerCase().includes(query) ||
           v.marca.toLowerCase().includes(query) ||
           v.modelo.toLowerCase().includes(query) ||
           v.tipo.toLowerCase().includes(query);
  });
});

const exportarFlotaGeneralPdf = async () => {
  exportingGeneral.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` }, responseType: 'blob' };
    const res = await axios.get('http://localhost:8000/api/vehiculos-exportar-general-pdf', config);
    
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'reporte_general_flota_vehicular.pdf');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (e) {
    alert("Error al generar el reporte general en PDF.");
  } finally {
    exportingGeneral.value = false;
  }
};

const exportarFlotaGeneralExcel = async () => {
  exportingGeneral.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` }, responseType: 'blob' };
    const res = await axios.get('http://localhost:8000/api/vehiculos-exportar-general-excel', config);
    
    const blob = new Blob([res.data], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `inventario_general_flota_${new Date().toLocaleDateString('es-BO').replace(/\//g, '_')}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (e) {
    alert("Error al generar la sábana de datos en Excel.");
  } finally {
    exportingGeneral.value = false;
  }
};

const abrirModalInspeccion = async (vehiculo) => {
  vehiculoSeleccionado.value = vehiculo;
  showModal.value = true;
  bachesAsociados.value = [];
  
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.get(`http://localhost:8000/api/vehiculos/${vehiculo.id}/baches`, config);
    bachesAsociados.value = res.data.baches;
  } catch (e) {
    console.error("Error al traer auditoría de baches:", e);
  }
};

const cerrarModal = () => {
  showModal.value = false;
  vehiculoSeleccionado.value = null;
  bachesAsociados.value = [];
};

const descargarPdfReporte = async () => {
  if (!vehiculoSeleccionado.value) return;
  downloadingPdf.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` }, responseType: 'blob' };
    const res = await axios.get(`http://localhost:8000/api/vehiculos/${vehiculoSeleccionado.value.id}/exportar-pdf`, config);
    
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_telemetria_placa_${vehiculoSeleccionado.value.placa}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (e) {
    alert("Error al procesar la exportación del PDF institucional.");
  } finally {
    downloadingPdf.value = false;
  }
};

const formatFecha = (cadenaFecha) => {
  if (!cadenaFecha) return '';
  const f = new Date(cadenaFecha);
  return f.toLocaleString('es-BO');
};

const getBadgeGravedad = (estado) => {
  if (estado === 'Crítico' || estado === 'Critico') return 'pill-danger';
  if (estado === 'Moderado') return 'pill-warn';
  return 'pill-info';
};

const guardarVehiculo = async () => {
  if (!form.value.placa || !form.value.marca || !form.value.modelo) return;
  loading.value = true;
  const config = { headers: { Authorization: `Bearer ${token}` } };
  try {
    if (isEditing.value) {
      await axios.put(`http://localhost:8000/api/vehiculos/${editingId.value}`, form.value, config);
      alert("Unidad móvil actualizada correctamente.");
      cancelarEdicion();
    } else {
      await axios.post('http://localhost:8000/api/vehiculos', form.value, config);
      alert(`Vehículo Placa '${form.value.placa}' registrado con éxito.`);
      form.value = { placa: '', marca: '', modelo: '', tipo: 'Camioneta', sensor_id: null };
    }
    await cargarDatosFlota(); 
  } catch (e) {
    alert(e.response?.data?.message || "Error al procesar el registro.");
  } finally {
    loading.value = false;
  }
};

const activarEdicion = (vehiculo) => {
  isEditing.value = true;
  editingId.value = vehiculo.id;
  sensorActualAlEditar.value = vehiculo.sensor ? vehiculo.sensor : null;
  form.value = {
    placa: vehiculo.placa,
    marca: vehiculo.marca,
    modelo: vehiculo.modelo,
    tipo: vehiculo.tipo,
    sensor_id: vehiculo.sensor_id
  };
};

const cancelarEdicion = () => {
  isEditing.value = false;
  editingId.value = null;
  sensorActualAlEditar.value = null;
  form.value = { placa: '', marca: '', modelo: '', tipo: 'Camioneta', sensor_id: null };
};

const eliminarVehiculo = async (id, placa) => {
  const seguro = confirm(`¿Está seguro de dar de baja al vehículo municipal con Placa [${placa}]? El sensor acoplado se liberará automáticamente.`);
  if (!seguro) return;
  loading.value = true; 
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    await axios.delete(`http://localhost:8000/api/vehiculos/${id}`, config);
    alert("Vehículo eliminado de la flota.");
    if (editingId.value === id) cancelarEdicion();
    await cargarDatosFlota();
  } catch (e) {
    alert("Error al conectar con la base de datos.");
  } finally {
    loading.value = false; 
  }
};

onMounted(cargarDatosFlota);
</script>

<style scoped>
/* Estilos Homologados S.D.B */
.gestion-container { padding: 5px; }
.row-layout { display: flex; gap: 20px; flex-wrap: wrap; }

.form-sidebar { flex: 1; min-width: 320px; }
.table-content { flex: 2; min-width: 500px; }

.card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #edf2f7; }
.card-header { background: #2c3e50; color: #42b983; padding: 18px 25px; font-weight: 700; border-bottom: 4px solid #42b983; font-size: 1.1rem; }
.bg-dark { background: #34495e; }
.bg-orange { background: #d69e2e !important; color: white !important; border-bottom: 4px solid #b7791f !important; }

.header-flex { display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 10px; }
.count-badge { background: rgba(66, 185, 131, 0.2); color: #42b983; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; margin-left: 5px; white-space: nowrap; }

.global-export-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.btn-global-export { border: none; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; cursor: pointer; color: white; transition: 0.2s ease; }
.btn-global-export:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
.btn-global-export:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-pdf-color { background-color: #e74c3c; }
.btn-pdf-color:hover { background-color: #c0392b; }
.btn-excel-color { background-color: #27ae60; }
.btn-excel-color:hover { background-color: #1e8449; }

/* 🔥 NUEVO: Estilos dedicados para el buscador superior */
.search-table-input {
  padding: 6px 12px;
  border: 2px solid #edf2f7;
  border-radius: 6px;
  font-size: 0.85rem;
  width: 200px;
  background: #f8fafc;
  color: #2d3748;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.search-table-input:focus {
  border-color: #42b983;
  background: white;
  outline: none;
  width: 250px; /* Sutil efecto expansivo de foco moderno */
  box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.15);
}

.form-body { padding: 25px; }
.form-group { margin-bottom: 22px; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 10px; font-weight: 600; color: #4a5568; font-size: 0.95rem; }

input, select { padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; color: #2d3748; }
input:focus, select:focus { border-color: #42b983; outline: none; background: #f0fff4; }

.hint-success { color: #2f855a; margin-top: 8px; font-size: 0.8rem; font-weight: 500; }

.btn-primary { background: #42b983; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
.btn-primary:hover:not(:disabled) { background: #3aa373; transform: translateY(-2px); }
.btn-orange { background: #dd6b20 !important; }
.btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.full-width { width: 100%; }
.mt-2 { margin-top: 8px; }

.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { background: #f8fafc; text-align: left; padding: 15px 20px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 0.75rem; text-transform: uppercase; }
.custom-table td { padding: 18px 20px; border-bottom: 1px solid #edf2f7; color: #2d3748; vertical-align: middle; }
.table-row:hover { background-color: #f7fafc; }
.row-editing { background-color: #fefcbf !important; border-left: 4px solid #dd6b20; }

.vehicle-plate { font-weight: 700; color: #2c3e50; font-family: monospace; font-size: 1.05rem; }
.badge-type { background: #e2e8f0; color: #4a5568; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }

.hardware-tag { padding: 5px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; display: inline-block; }
.has-hardware { background: #e2f8ff; color: #2b6cb0; border: 1px solid #bee3f8; }
.no-hardware { background: #fff5f5; color: #c53030; border: 1px solid #fed7d7; }

.btn-actions-group { display: flex; gap: 6px; justify-content: center; }
.btn-action { border: none; background: none; font-size: 1rem; padding: 6px 10px; cursor: pointer; border-radius: 6px; transition: 0.2s; }
.btn-action:hover:not(:disabled) { transform: scale(1.15); }
.btn-view:hover { background: #e2f8ff; }
.btn-edit:hover { background: #feebc8; }
.btn-delete:hover { background: #fed7d7; }

.badge-pill { padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; }
.pill-info { background: #e2f8ff; color: #2b6cb0; }
.pill-warn { background: #feebc8; color: #9c4221; }
.pill-danger { background: #fff5f5; color: #c53030; }

.empty-state { padding: 50px !important; color: #a0aec0; font-style: italic; text-align: center; }
.text-center { text-align: center; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 2000; padding: 20px; }
.modal-content { background: white; border-radius: 14px; width: 100%; max-width: 750px; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); overflow: hidden; display: flex; flex-direction: column; max-height: 85vh; }
.modal-header { background: #2c3e50; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 4px solid #42b983; }
.modal-header h3 { margin: 0; font-size: 1.15rem; color: #42b983; }
.close-modal-btn { background: none; border: none; color: white; font-size: 1.7rem; cursor: pointer; opacity: 0.7; transition: 0.2s; }
.close-modal-btn:hover { opacity: 1; transform: scale(1.1); }

.modal-body { padding: 25px; overflow-y: auto; }
.vehiculo-summary { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; flex-wrap: wrap; font-size: 0.95rem; color: #4a5568; }
.vehiculo-summary p { margin: 5px 0; }

.header-flex-modal { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid #edf2f7; padding-bottom: 10px; }
.header-flex-modal h4 { margin: 0; color: #2c3e50; font-size: 1rem; }
.btn-pdf { background: #34495e; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 0.85rem; }
.btn-pdf:hover:not(:disabled) { background: #2c3e50; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

.modal-table-wrapper { border: 1px solid #edf2f7; border-radius: 8px; overflow: hidden; }
.modal-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.modal-table th { background: #f8fafc; padding: 12px 15px; text-align: left; color: #718096; font-size: 0.75rem; text-transform: uppercase; border-bottom: 2px solid #edf2f7; }
.modal-table td { padding: 14px 15px; border-bottom: 1px solid #edf2f7; color: #2d3748; }
.geo-code { font-family: monospace; font-weight: 600; color: #4a5568; }

@media (max-width: 1024px) {
  .row-layout { flex-direction: column; }
  .header-flex { flex-direction: column; align-items: flex-start; }
  .global-export-actions { width: 100%; justify-content: flex-start; margin-top: 5px; }
  .search-table-input { width: 100%; }
  .search-table-input:focus { width: 100%; }
}
</style>