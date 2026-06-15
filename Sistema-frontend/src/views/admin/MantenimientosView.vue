<template>
  <div class="taller-view-wrapper">
    
    <div class="print-only-header">
      <div class="print-header-content">
        <div class="print-meta">
          <h2>SISTEMA DE GESTIÓN VIAL | S.D.B.</h2>
          <h1>HISTORIAL OFICIAL DE MANTENIMIENTOS DE LA FLOTA</h1>
          <p><strong>Fecha de Emisión:</strong> {{ obtenerFechaActual() }}</p>
          <p><strong>Generado por:</strong> Administrador del Sistema</p>
        </div>
      </div>
      <hr class="print-divider">
    </div>

    <div class="taller-grid">
      
      <div class="taller-col-form no-print">
        <div class="sdb-card">
          <div class="sdb-card-header header-dark">
            <h5 class="header-title">
              <i class="bi bi-tools me-2 text-success-sdb"></i> 
              {{ editando ? 'Modificar Órden Técnica' : 'Registrar Mantenimiento de Activo' }}
            </h5>
          </div>
          <div class="sdb-card-body">
            <form @submit.prevent="guardarMantenimiento" class="sdb-form">
              
              <div class="sdb-form-group">
                <label class="sdb-label">Vehículo de la Flota</label>
                <select v-model="form.vehiculo_id" class="sdb-select" required>
                  <option value="" disabled>-- Seleccione el coche afectado --</option>
                  <option v-for="veh in listaVehiculos" :key="veh.id" :value="veh.id">
                    🚗 {{ veh.placa }} - {{ veh.modelo || 'Móvil SDB' }}
                  </option>
                </select>
              </div>

              <div class="sdb-form-group">
                <label class="sdb-label">Técnico Asignado</label>
                <select v-model="form.personal_taller_id" class="sdb-select" required>
                  <option value="" disabled>-- Seleccione el mecánico --</option>
                  <option v-for="tec in listaTecnicos" :key="tec.id" :value="tec.id">
                    👨‍🔧 {{ tec.nombres }} {{ tec.apellido_paterno }} ({{ tec.rubro }}) - {{ tec.estado }}
                  </option>
                </select>
              </div>

              <div class="sdb-form-group">
                <label class="sdb-label fw-bold">Tipo de Mantenimiento</label>
                <select v-model="form.tipo" class="sdb-select border-highlight" required>
                  <option value="" disabled>-- Seleccione el Tipo --</option>
                  <option value="Mecánico">🛠️ Mecánico (Amortiguadores / Daño por Bache)</option>
                  <option value="Electrónico">📟 Electrónico (Calibración / Sensores IoT)</option>
                </select>
              </div>

              <div class="sdb-form-group">
                <label class="sdb-label">Detalle Técnico de la Reparación</label>
                <textarea 
                  v-model="form.descripcion" 
                  rows="4" 
                  class="sdb-textarea" 
                  placeholder="Describa el trabajo realizado detalladamente..." 
                  required
                ></textarea>
              </div>

              <div class="button-container">
                <button type="submit" class="btn-sdb btn-success-sdb">
                  <i class="bi" :class="editando ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
                  {{ editando ? ' Actualizar Órden' : ' Guardar Registro' }}
                </button>
                
                <button v-if="editando" type="button" @click="cancelarEdicion" class="btn-sdb btn-secondary-sdb">
                  Cancelar Edición
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="taller-col-table">
        <div class="sdb-card">
          <div class="sdb-card-header header-light d-flex justify-content-between align-items-center">
            <h5 class="header-title text-dark">
              <i class="bi bi-journal-text me-2 text-muted"></i> Historial de Mantenimientos
            </h5>
            <button @click="exportarReportePDF" class="btn-pdf-export no-print">
              <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
            </button>
          </div>
          <div class="sdb-card-body p-0">
            <div class="sdb-table-responsive">
              <table class="sdb-custom-table">
                <thead>
                  <tr>
                    <th>Vehículo</th>
                    <th>Técnico Responsable</th>
                    <th>Clasificación</th>
                    <th>Trabajo Realizado</th>
                    <th>Fecha de Soporte</th>
                    <th class="text-center no-print">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="listaMantenimientos.length === 0">
                    <td colspan="6" class="sdb-no-data">
                      <i class="bi bi-info-circle me-2"></i> No se han registrado órdenes de mantenimiento aún.
                    </td>
                  </tr>
                  
                  <tr v-for="mant in listaMantenimientos" :key="mant.id">
                    <td class="text-bold text-dark-slate">
                      <span class="car-tag">🚗 {{ mant.vehiculo ? mant.vehiculo.placa : 'S/V' }}</span>
                    </td>
                    <td>
                      <span class="tech-name">
                        {{ mant.tecnico ? `${mant.tecnico.nombres} ${mant.tecnico.apellido_paterno}` : 'Técnico Eliminado' }}
                      </span>
                    </td>
                    <td>
                      <span v-if="mant.tipo === 'Mecánico'" class="sdb-badge badge-blue">
                        Mecánico
                      </span>
                      <span v-else class="sdb-badge badge-orange">
                        Electrónico
                      </span>
                    </td>
                    <td class="desc-cell" :title="mant.descripcion">
                      {{ mant.descripcion }}
                    </td>
                    <td class="date-cell">
                      {{ formatearFecha(mant.created_at) }}
                    </td>
                   <td class="text-center no-print">
                        <div class="sdb-actions">
                            <button @click="finalizarMantenimiento(mant.id)" class="action-btn btn-check" title="Finalizar Mantenimiento y Restaurar Salud">
                            <i class="bi bi-check-circle-fill"></i>
                            </button>

                            <button @click="cargarDatosEditar(mant)" class="action-btn btn-edit" title="Editar">
                            <i class="bi bi-pencil"></i>
                            </button>
                            <button @click="eliminarMantenimiento(mant.id)" class="action-btn btn-delete" title="Eliminar">
                            <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const API_MANTENIMIENTOS = 'https://sdb-sistema-production.up.railway.app/api/mantenimientos';
const API_VEHICULOS = 'https://sdb-sistema-production.up.railway.app/api/vehiculos';
const API_TECNICOS = 'https://sdb-sistema-production.up.railway.app/api/personal-taller';

const editando = ref(false);
const mantenimientoIdSeleccionado = ref(null);

const listaMantenimientos = ref([]);
const listaVehiculos = ref([]);
const listaTecnicos = ref([]);

const form = ref({
  vehiculo_id: '',
  personal_taller_id: '',
  descripcion: '',
  tipo: ''
});

const obtenerCabecerasDeSeguridad = () => {
  const token = localStorage.getItem('token');
  return {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  };
};

const obtenerFechaActual = () => {
  const opciones = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date().toLocaleDateString('es-BO', opciones);
};

const formatearFecha = (fechaStr) => {
  if (!fechaStr) return '';
  const fecha = new Date(fechaStr);
  return fecha.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const exportarReportePDF = () => {
  window.print();
};

const resetFormulario = () => {
  editando.value = false;
  mantenimientoIdSeleccionado.value = null;
  form.value = {
    vehiculo_id: '',
    personal_taller_id: '',
    descripcion: '',
    tipo: ''
  };
};

// Carga Inicial de Datos Cruzados de la Base de Datos
const cargarDatosIniciales = async () => {
  try {
    const headers = obtenerCabecerasDeSeguridad();
    const [resMant, resVeh, resTec] = await Promise.all([
      axios.get(API_MANTENIMIENTOS, headers),
      axios.get(API_VEHICULOS, headers),
      axios.get(API_TECNICOS, headers)
    ]);
    listaMantenimientos.value = resMant.data;
    listaVehiculos.value = resVeh.data;
    // Filtrar opcionalmente para mostrar solo técnicos activos o disponibles
    listaTecnicos.value = resTec.data;
  } catch (error) {
    console.error("Error al cargar los datos logísticos del SDB:", error);
  }
};

const guardarMantenimiento = async () => {
  try {
    const headers = obtenerCabecerasDeSeguridad();
    if (editando.value) {
      const response = await axios.put(`${API_MANTENIMIENTOS}/${mantenimientoIdSeleccionado.value}`, form.value, headers);
      if (response.data.res) {
        alert("El registro de mantenimiento se modificó de manera correcta.");
      }
    } else {
      const response = await axios.post(API_MANTENIMIENTOS, form.value, headers);
      if (response.data.res) {
        alert("Orden de mantenimiento guardada y técnico asignado.");
      }
    }
    resetFormulario();
    cargarDatosIniciales(); // Refrescar listas y estados
  } catch (error) {
    console.error("Error en operación de mantenimientos:", error);
    alert("No se pudo completar la operación. Verifique los datos obligatorios.");
  }
};

const cargarDatosEditar = (mant) => {
  editando.value = true;
  mantenimientoIdSeleccionado.value = mant.id;
  form.value = {
    vehiculo_id: mant.vehiculo_id,
    personal_taller_id: mant.personal_taller_id,
    descripcion: mant.descripcion,
    tipo: mant.tipo
  };
};

const cancelarEdicion = () => {
  resetFormulario();
};

const eliminarMantenimiento = async (id) => {
  if (confirm("¿Está seguro de enviar este registro de mantenimiento a la papelera?")) {
    try {
      const response = await axios.delete(`${API_MANTENIMIENTOS}/${id}`, obtenerCabecerasDeSeguridad());
      if (response.data.res) {
        alert("Registro enviado a la papelera con éxito.");
        cargarDatosIniciales();
      }
    } catch (error) {
      console.error("Error al eliminar mantenimiento:", error);
      alert("Hubo un percance al procesar la baja.");
    }
  }
};

const finalizarMantenimiento = async (id) => {
  if (confirm("¿Desea dar de alta este vehículo? Esto restablecerá la salud de su suspensión al 100% y liberará al técnico.")) {
    try {
      const response = await axios.put(`${API_MANTENIMIENTOS}/${id}/finalizar`, {}, obtenerCabecerasDeSeguridad());
      if (response.data.res) {
        alert(response.data.message);
        cargarDatosIniciales(); // Refresca la tabla al instante
      }
    } catch (error) {
      console.error("Error al concluir el mantenimiento:", error);
      alert("Hubo un problema al procesar la alta técnica.");
    }
  }
};

onMounted(() => {
  cargarDatosIniciales();
});
</script>

<style scoped>
/* ==========================================================================
   🎨 ESTILOS INDEPENDIENTES CORPORATIVOS S.D.B.
   ========================================================================== */
.taller-view-wrapper { width: 100%; box-sizing: border-box; }
.taller-grid { display: flex; flex-wrap: wrap; gap: 24px; width: 100%; }
.taller-col-form { flex: 1 1 390px; }
.taller-col-table { flex: 1.8 1 600px; }

.sdb-card { background: #ffffff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; overflow: hidden; width: 100%; }
.sdb-card-header { padding: 16px 20px; display: flex; align-items: center; }
.header-dark { background: #1e293b; color: #ffffff; border-bottom: 3px solid #42b983; }
.header-light { background: #f8fafc; color: #334155; border-bottom: 1px solid #e2e8f0; }
.header-title { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; }

.btn-pdf-export { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; border: none; padding: 7px 14px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.15); transition: all 0.2s ease; margin-left: auto; }
.btn-pdf-export:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(231, 76, 60, 0.3); background: linear-gradient(135deg, #ff6b6b 0%, #e74c3c 100%); }

.action-btn.btn-check { color: #2e855c; }
.action-btn.btn-check:hover { background: #42b983; color: #ffffff; border-color: #42b983; }

.text-success-sdb { color: #42b983; }
.text-dark { color: #334155; }
.sdb-card-body { padding: 20px; }
.p-0 { padding: 0 !important; }

.sdb-form { display: flex; flex-direction: column; gap: 14px; }
.sdb-form-group { display: flex; flex-direction: column; gap: 6px; }
.sdb-label { font-size: 0.85rem; font-weight: 600; color: #475569; }

.sdb-control, .sdb-select, .sdb-textarea { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; color: #1e293b; background-color: #ffffff; transition: all 0.2s ease-in-out; width: 100%; box-sizing: border-box; font-family: inherit; }
.sdb-control:focus, .sdb-select:focus, .sdb-textarea:focus { outline: none; border-color: #42b983; box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.2); }

.border-highlight { border-color: #42b983 !important; color: #2e855c; font-weight: 600; }
.border-blue { border-color: #3b82f6 !important; color: #1d4ed8; font-weight: 600; }

.button-container { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.btn-sdb { width: 100%; padding: 11px; border: none; border-radius: 6px; font-size: 0.92rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; color: #ffffff; }
.btn-success-sdb { background: linear-gradient(135deg, #42b983 0%, #2e855c 100%); box-shadow: 0 4px 12px rgba(66, 185, 131, 0.2); }
.btn-success-sdb:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(66, 185, 131, 0.35); }
.btn-secondary-sdb { background: #64748b; }

.sdb-table-responsive { width: 100%; overflow-x: auto; }
.sdb-custom-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left; table-layout: fixed; }
.sdb-custom-table th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
.sdb-custom-table th:nth-child(1) { width: 110px; }
.sdb-custom-table th:nth-child(2) { width: 150px; }
.sdb-custom-table th:nth-child(3) { width: 115px; }
.sdb-custom-table th:nth-child(4) { width: 220px; }
.sdb-custom-table th:nth-child(5) { width: 110px; }
.sdb-custom-table th:nth-child(6) { width: 90px; }

.sdb-custom-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sdb-custom-table tbody tr:hover { background-color: #f8fafc; }

.text-bold { font-weight: 600; }
.text-dark-slate { color: #0f172a !important; }
.sdb-no-data { text-align: center; padding: 35px !important; color: #94a3b8; font-style: italic; }

.car-tag { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 0.82rem; }
.tech-name { font-weight: 500; color: #334155; font-size: 0.88rem; }
.desc-cell { font-size: 0.85rem; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.date-cell { font-size: 0.85rem; font-weight: 500; color: #64748b; }

.sdb-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.badge-blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.badge-orange { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

.sdb-actions { display: flex; gap: 6px; justify-content: center; }
.action-btn { background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: all 0.15s ease; font-size: 0.88rem; }
.action-btn.btn-edit { color: #3b82f6; }
.action-btn.btn-edit:hover { background: #3b82f6; color: #ffffff; border-color: #3b82f6; }
.action-btn.btn-delete { color: #ef4444; }
.action-btn.btn-delete:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }

.print-only-header, .print-status-text { display: none; }
</style>

<style>
@media print {
  .sidebar, .content-header, .taller-col-form, .no-print, .sdb-actions, .btn-pdf-export { display: none !important; }
  .layout-wrapper, .main-content, .content-body { display: block !important; width: 100% !important; height: auto !important; overflow: visible !important; padding: 0 !important; margin: 0 !important; background: #ffffff !important; }
  .print-only-header { display: block !important; margin-bottom: 25px !important; font-family: 'Segoe UI', system-ui, sans-serif !important; }
  .print-header-content { display: flex !important; align-items: center !important; gap: 20px !important; }
  .print-meta h2 { font-size: 0.9rem !important; color: #475569 !important; margin: 0 0 4px 0 !important; letter-spacing: 0.5px !important; }
  .print-meta h1 { font-size: 1.4rem !important; color: #0f172a !important; margin: 0 0 10px 0 !important; font-weight: 800 !important; }
  .print-meta p { font-size: 0.85rem !important; color: #334155 !important; margin: 2px 0 !important; }
  .print-divider { border: none !important; border-top: 2px solid #1e293b !important; margin-top: 15px !important; display: block !important; }
  .taller-view-wrapper { padding: 0 !important; margin: 0 !important; }
  .taller-grid { display: block !important; width: 100% !important; }
  .taller-col-table { width: 100% !important; max-width: 100% !important; }
  .sdb-card { border: none !important; box-shadow: none !important; overflow: visible !important; }
  .sdb-custom-table { width: 100% !important; border: 1px solid #cbd5e1 !important; border-collapse: collapse !important; table-layout: auto !important; }
  .sdb-custom-table th { background-color: #f1f5f9 !important; color: #0f172a !important; border-bottom: 2px solid #cbd5e1 !important; padding: 10px 12px !important; font-weight: 700 !important; }
  .sdb-custom-table td { padding: 10px 12px !important; border-bottom: 1px solid #e2e8f0 !important; color: #000000 !important; white-space: normal !important; overflow: visible !important; text-overflow: clip !important; }
  .desc-cell { white-space: normal !important; overflow: visible !important; }
  .sdb-badge, .sdb-ci-badge, .car-tag { border: 1px solid #94a3b8 !important; background: transparent !important; color: #000000 !important; display: inline-flex !important; box-shadow: none !important; }
}
</style>