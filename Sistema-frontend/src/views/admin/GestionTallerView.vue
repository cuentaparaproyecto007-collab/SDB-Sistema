<template>
  <div class="taller-view-wrapper">
    
    <!-- 📄 ENCABEZADO EXCLUSIVO PARA LA IMPRESIÓN DEL REPORTE PDF (Oculto en pantalla) -->
    <div class="print-only-header">
      <div class="print-header-content">
        <div class="print-meta">
          <h2>SISTEMA DE GESTIÓN VIAL | S.D.B.</h2>
          <h1>REPORTE OFICIAL - PERSONAL TÉCNICO DE TALLER</h1>
          <p><strong>Fecha de Emisión:</strong> {{ obtenerFechaActual() }}</p>
          <p><strong>Generado por:</strong> Administrador del Sistema</p>
        </div>
      </div>
      <hr class="print-divider">
    </div>

    <div class="taller-grid">
      
      <!-- COLUMNA IZQUIERDA: FORMULARIO DE REGISTRO / EDICIÓN (Se oculta al exportar PDF) -->
      <div class="taller-col-form no-print">
        <div class="sdb-card">
          <div class="sdb-card-header header-dark">
            <h5 class="header-title">
              <i class="bi bi-wrench me-2 text-success-sdb"></i> 
              {{ editando ? 'Modificar Registro Técnico' : 'Registro de Personal de Taller' }}
            </h5>
          </div>
          <div class="sdb-card-body">
            <form @submit.prevent="guardarTecnico" class="sdb-form">
              
              <!-- Nombres -->
              <div class="sdb-form-group">
                <label class="sdb-label">Nombres</label>
                <input 
                  v-model="form.nombres" 
                  type="text" 
                  class="sdb-control" 
                  placeholder="Ej: Juan Alberto" 
                  required
                >
              </div>

              <!-- Apellidos (Fila Dividida) -->
              <div class="sdb-row">
                <div class="sdb-form-group flex-1">
                  <label class="sdb-label">Apellido Paterno</label>
                  <input 
                    v-model="form.apellido_paterno" 
                    type="text" 
                    class="sdb-control" 
                    placeholder="Ej: Apaza" 
                    required
                  >
                </div>
                <div class="sdb-form-group flex-1">
                  <label class="sdb-label">Apellido Materno</label>
                  <input 
                    v-model="form.apellido_materno" 
                    type="text" 
                    class="sdb-control" 
                    placeholder="Ej: Mamani"
                  >
                </div>
              </div>

              <!-- C.I. Con Desplegable de Departamentos y Género -->
              <div class="sdb-row">
                <div class="sdb-form-group flex-1">
                  <label class="sdb-label">C.I. (Documento)</label>
                  <div class="sdb-input-group">
                    <input 
                      v-model="form.ci_numero" 
                      type="text" 
                      class="sdb-control input-ci" 
                      placeholder="1234567" 
                      required
                    >
                    <select v-model="form.ci_expedido" class="sdb-select select-exp" required>
                      <option value="LP">LP</option>
                      <option value="CB">CB</option>
                      <option value="SC">SC</option>
                      <option value="OR">OR</option>
                      <option value="PT">PT</option>
                      <option value="CH">CH</option>
                      <option value="TJ">TJ</option>
                      <option value="BE">BE</option>
                      <option value="PD">PD</option>
                    </select>
                  </div>
                </div>
                
                <div class="sdb-form-group flex-1">
                  <label class="sdb-label">Género</label>
                  <select v-model="form.genero" class="sdb-select" required>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                    <option value="Otro">Otro</option>
                  </select>
                </div>
              </div>

              <!-- Celular (Limitado a 8 números) -->
              <div class="sdb-form-group">
                <label class="sdb-label">Número de Celular</label>
                <input 
                  v-model="form.celular" 
                  type="text" 
                  class="sdb-control" 
                  placeholder="Ej: 77712345" 
                  maxlength="8"
                  @input="filtrarCelular"
                  required
                >
                <small class="sdb-help-text">Debe contener exactamente 8 dígitos numéricos.</small>
              </div>

              <!-- Dirección Completa -->
              <div class="sdb-form-group">
                <label class="sdb-label">Dirección Completa</label>
                <input 
                  v-model="form.direccion" 
                  type="text" 
                  class="sdb-control" 
                  placeholder="Ej: Av. Arce, Edif. Los Pinos Nro 123" 
                  required
                >
              </div>

              <!-- Especialidad (Rubro Técnico) -->
              <div class="sdb-form-group">
                <label class="sdb-label fw-bold">Especialidad (Rubro)</label>
                <select v-model="form.rubro" class="sdb-select border-highlight" required>
                  <option value="" disabled>-- Seleccione un Rubro --</option>
                  <option value="Mecánica">🚗 Mecánica Automotriz (Suspensión)</option>
                  <option value="Electrónica">📟 Electrónica Digital (Sensores IoT)</option>
                </select>
              </div>

              <!-- Estado -->
              <div v-if="editando" class="sdb-form-group">
                <label class="sdb-label fw-bold">Estado Operativo</label>
                <select v-model="form.estado" class="sdb-select border-blue" required>
                  <option value="Disponible">Disponible</option>
                  <option value="Ocupado">Ocupado (En Reparación)</option>
                  <option value="Baja">Baja Laboral</option>
                </select>
              </div>

              <!-- Botones de Acción -->
              <div class="button-container">
                <button type="submit" class="btn-sdb btn-success-sdb">
                  <i class="bi" :class="editando ? 'bi-pencil-square' : 'bi-person-plus-fill'"></i>
                  {{ editando ? ' Actualizar Datos Técnico' : ' Registrar Técnico' }}
                </button>
                
                <button v-if="editando" type="button" @click="cancelarEdicion" class="btn-sdb btn-secondary-sdb">
                  Cancelar Edición
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA: TABLA DE CONTROL DEL PERSONAL -->
      <div class="taller-col-table">
        <div class="sdb-card">
          <div class="sdb-card-header header-light d-flex justify-content-between align-items-center">
            <h5 class="header-title text-dark">
              <i class="bi bi-people-fill me-2 text-muted"></i> Personal Técnico en Sistema
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
                    <th>Técnico</th>
                    <th>C.I.</th>
                    <th>Contacto / Ubicación</th>
                    <th>Especialidad</th>
                    <th>Estado</th>
                    <th class="text-center no-print">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="listaPersonal.length === 0">
                    <td colspan="6" class="sdb-no-data">
                      <i class="bi bi-info-circle me-2"></i> No se encontraron técnicos registrados.
                    </td>
                  </tr>
                  
                  <tr v-for="tec in listaPersonal" :key="tec.id">
                    <td class="text-bold text-dark-slate">
                      {{ tec.nombres }} {{ tec.apellido_paterno }}
                    </td>
                    <td>
                      <span class="sdb-ci-badge">{{ tec.ci }}</span>
                    </td>
                    <td>
                      <span class="sdb-phone"><i class="bi bi-telephone me-1 text-muted"></i> {{ tec.celular }}</span>
                      <small class="sdb-address text-truncate" :title="tec.direccion">
                        <i class="bi bi-geo-alt me-1"></i> {{ tec.direccion }}
                      </small>
                    </td>
                    <td>
                      <span v-if="tec.rubro === 'Mecánica'" class="sdb-badge badge-blue">
                        <i class="bi bi-wrench me-1"></i> Mecánica
                      </span>
                      <span v-else class="sdb-badge badge-orange">
                        <i class="bi bi-cpu me-1"></i> Electrónica
                      </span>
                    </td>
                    <td>
                      <!-- SELECTOR INTERACTIVO INLINE -->
                      <select 
                        v-model="tec.estado" 
                        @change="cambiarEstadoInline(tec)"
                        class="sdb-status-select no-print"
                        :class="{
                          'status-available': tec.estado === 'Disponible',
                          'status-busy': tec.estado === 'Ocupado',
                          'status-leave': tec.estado === 'Baja'
                        }"
                      >
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupado">Ocupado</option>
                        <option value="Baja">Baja</option>
                      </select>
                      <!-- Texto plano para la versión impresa del PDF -->
                      <span class="print-status-text">{{ tec.estado }}</span>
                    </td>
                    <td class="text-center no-print">
                      <div class="sdb-actions">
                        <button @click="cargarDatosEditar(tec)" class="action-btn btn-edit" title="Editar">
                          <i class="bi bi-pencil"></i>
                        </button>
                        <button @click="eliminarTecnico(tec.id)" class="action-btn btn-delete" title="Eliminar">
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

const API_URL = 'http://localhost:8000/api/personal-taller';

const editando = ref(false);
const tecnicoIdSeleccionado = ref(null);
const listaPersonal = ref([]);

const form = ref({
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  ci_numero: '',    
  ci_expedido: 'LP', 
  genero: 'Masculino',
  celular: '',
  direccion: '',
  rubro: '',
  estado: 'Disponible'
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

const filtrarCelular = (event) => {
  form.value.celular = event.target.value.replace(/\D/g, '');
};

const obtenerFechaActual = () => {
  const opciones = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
  return new Date().toLocaleDateString('es-BO', opciones);
};

const exportarReportePDF = () => {
  window.print();
};

const resetFormulario = () => {
  editando.value = false;
  tecnicoIdSeleccionado.value = null;
  form.value = {
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    ci_numero: '',
    ci_expedido: 'LP',
    genero: 'Masculino',
    celular: '',
    direccion: '',
    rubro: '',
    estado: 'Disponible'
  };
};

const obtenerPersonal = async () => {
  try {
    const response = await axios.get(API_URL, obtenerCabecerasDeSeguridad());
    listaPersonal.value = response.data;
  } catch (error) {
    console.error("Error al obtener la lista de técnicos:", error);
  }
};

const cambiarEstadoInline = async (tec) => {
  try {
    const response = await axios.put(`${API_URL}/${tec.id}`, tec, obtenerCabecerasDeSeguridad());
    if (response.data.res) {
      console.log(`Estado modificado inline con éxito.`);
    }
  } catch (error) {
    console.error("Error al cambiar el estado inline:", error);
    alert("No se pudo actualizar el estado operativo.");
    obtenerPersonal(); 
  }
};

const guardarTecnico = async () => {
  if (form.value.celular.length !== 8) {
    alert("El número de celular es inválido. Debe ingresar exactamente 8 dígitos.");
    return;
  }

  try {
    const payload = {
      ...form.value,
      ci: `${form.value.ci_numero} ${form.value.ci_expedido}`
    };

    if (editando.value) {
      const response = await axios.put(`${API_URL}/${tecnicoIdSeleccionado.value}`, payload, obtenerCabecerasDeSeguridad());
      if (response.data.res) {
        alert("Los datos del técnico se actualizaron correctamente.");
      }
    } else {
      const response = await axios.post(API_URL, payload, obtenerCabecerasDeSeguridad());
      if (response.data.res) {
        alert("Técnico registrado de forma exitosa en el taller.");
      }
    }
    resetFormulario();
    obtenerPersonal(); 
  } catch (error) {
    console.error("Error en la operación:", error);
    if (error.response && error.response.data.message) {
      alert("Error: " + error.response.data.message);
    } else {
      alert("Hubo un error al procesar la solicitud.");
    }
  }
};

const cargarDatosEditar = (tec) => {
  editando.value = true;
  tecnicoIdSeleccionado.value = tec.id;
  form.value = { ...tec };
  
  if (tec.ci) {
    const partes = tec.ci.split(' ');
    form.value.ci_numero = partes[0] || '';
    form.value.ci_expedido = partes[1] || 'LP';
  }
};

const cancelarEdicion = () => {
  resetFormulario();
};

const eliminarTecnico = async (id) => {
  if (confirm("¿Está seguro de enviar a este técnico a la papelera de reciclaje?")) {
    try {
      const response = await axios.delete(`${API_URL}/${id}`, obtenerCabecerasDeSeguridad());
      if (response.data.res) {
        alert("El técnico fue trasladado a la papelera correctamente.");
        obtenerPersonal(); 
      }
    } catch (error) {
      console.error("Error al eliminar técnico:", error);
      alert("No se pudo eliminar el registro.");
    }
  }
};

onMounted(() => {
  obtenerPersonal();
});
</script>

<style scoped>
/* ==========================================================================
   🎨 MOTOR DE ESTILOS PROPIOS EN PANTALLA (SCREEN)
   ========================================================================== */
.taller-view-wrapper { width: 100%; box-sizing: border-box; }
.taller-grid { display: flex; flex-wrap: wrap; gap: 24px; width: 100%; }
.taller-col-form { flex: 1 1 390px; }
.taller-col-table { flex: 1.5 1 550px; }

.sdb-card { background: #ffffff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0; overflow: hidden; width: 100%; }
.sdb-card-header { padding: 16px 20px; display: flex; align-items: center; }
.header-dark { background: #1e293b; color: #ffffff; border-bottom: 3px solid #42b983; }
.header-light { background: #f8fafc; color: #334155; border-bottom: 1px solid #e2e8f0; }
.header-title { margin: 0; font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; }

.btn-pdf-export { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; border: none; padding: 7px 14px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(231, 76, 60, 0.15); transition: all 0.2s ease; margin-left: auto; }
.btn-pdf-export:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(231, 76, 60, 0.3); background: linear-gradient(135deg, #ff6b6b 0%, #e74c3c 100%); }

.text-success-sdb { color: #42b983; }
.text-dark { color: #334155; }
.sdb-card-body { padding: 20px; }
.p-0 { padding: 0 !important; }

.sdb-form { display: flex; flex-direction: column; gap: 14px; }
.sdb-row { display: flex; gap: 14px; width: 100%; }
.sdb-form-group { display: flex; flex-direction: column; gap: 6px; }
.flex-1 { flex: 1; }
.sdb-label { font-size: 0.85rem; font-weight: 600; color: #475569; }

.sdb-control, .sdb-select { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.92rem; color: #1e293b; background-color: #ffffff; transition: all 0.2s ease-in-out; width: 100%; box-sizing: border-box; }
.sdb-control:focus, .sdb-select:focus { outline: none; border-color: #42b983; box-shadow: 0 0 0 3px rgba(66, 185, 131, 0.2); }

.sdb-input-group { display: flex; width: 100%; }
.sdb-input-group .input-ci { border-top-right-radius: 0; border-bottom-right-radius: 0; flex: 1; }
.sdb-input-group .select-exp { border-top-left-radius: 0; border-bottom-left-radius: 0; width: 85px; border-left: none; }

.sdb-help-text { font-size: 0.78rem; color: #64748b; margin-top: 2px; }
.border-highlight { border-color: #42b983 !important; color: #2e855c; font-weight: 600; }
.border-blue { border-color: #3b82f6 !important; color: #1d4ed8; font-weight: 600; }

.button-container { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.btn-sdb { width: 100%; padding: 11px; border: none; border-radius: 6px; font-size: 0.92rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.2s ease; color: #ffffff; }
.btn-success-sdb { background: linear-gradient(135deg, #42b983 0%, #2e855c 100%); box-shadow: 0 4px 12px rgba(66, 185, 131, 0.2); }
.btn-success-sdb:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(66, 185, 131, 0.35); }
.btn-secondary-sdb { background: #64748b; }
.btn-secondary-sdb:hover { background: #475569; }

.sdb-table-responsive { width: 100%; overflow-x: auto; }
.sdb-custom-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left; }
.sdb-custom-table th { background-color: #f8fafc; color: #475569; font-weight: 600; padding: 14px 16px; border-bottom: 1px solid #e2e8f0; }
.sdb-custom-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.sdb-custom-table tbody tr:hover { background-color: #f8fafc; }

.text-bold { font-weight: 600; }
.text-dark-slate { color: #0f172a !important; }
.text-center { text-align: center; }
.sdb-no-data { text-align: center; padding: 35px !important; color: #94a3b8; font-style: italic; }

.sdb-phone { display: block; font-weight: 500; font-size: 0.88rem; color: #334155; }
.sdb-address { display: block; font-size: 0.78rem; color: #64748b; max-width: 160px; }
.sdb-ci-badge { background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; color: #475569; border: 1px solid #e2e8f0; }

.sdb-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
.badge-blue { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.badge-orange { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }

.sdb-status-select { border: none; border-radius: 4px; padding: 6px 10px; font-size: 0.76rem; font-weight: 700; text-transform: uppercase; cursor: pointer; outline: none; text-align: center; transition: all 0.15s ease-in-out; }
.sdb-status-select.status-available { background: #ecfdf5; color: #047857; }
.sdb-status-select.status-busy { background: #fef2f2; color: #b91c1c; }
.sdb-status-select.status-leave { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

.sdb-actions { display: flex; gap: 6px; justify-content: center; }
.action-btn { background: #ffffff; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 4px; cursor: pointer; transition: all 0.15s ease; font-size: 0.88rem; }
.action-btn.btn-edit { color: #3b82f6; }
.action-btn.btn-edit:hover { background: #3b82f6; color: #ffffff; border-color: #3b82f6; }
.action-btn.btn-delete { color: #ef4444; }
.action-btn.btn-delete:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }

/* Ocultar en pantalla los textos planos y cabeceras de reporte */
.print-only-header, .print-status-text { display: none; }
</style>

<!-- 🔥 NUEVO BLOQUE DE ESTILO GLOBAL (SIN SCOPED) PARA CONTROLAR EL MOTOR DE IMPRESIÓN DEL NAVEGADOR 🔥 -->
<style>
@media print {
  /* 1. Ocultar de forma absoluta la barra de navegación (Sidebar), el encabezado superior de la app y el formulario */
  .sidebar, 
  .content-header, 
  .taller-col-form, 
  .no-print, 
  .sdb-actions,
  .btn-pdf-export {
    display: none !important;
  }
  
  /* 2. Romper las cajas flexibles y desbordamientos (overflow) del Layout principal para expandir la hoja */
  .layout-wrapper, 
  .main-content, 
  .content-body {
    display: block !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
    padding: 0 !important;
    margin: 0 !important;
    background: #ffffff !important;
  }
  
  /* 3. Hacer visible el encabezado institucional oficial en la impresión */
  .print-only-header {
    display: block !important;
    margin-bottom: 25px !important;
    font-family: 'Segoe UI', system-ui, sans-serif !important;
  }
  .print-header-content {
    display: flex !important;
    align-items: center !important;
    gap: 20px !important;
  }
  .print-meta h2 { font-size: 0.9rem !important; color: #475569 !important; margin: 0 0 4px 0 !important; letter-spacing: 0.5px !important; }
  .print-meta h1 { font-size: 1.4rem !important; color: #0f172a !important; margin: 0 0 10px 0 !important; font-weight: 800 !important; }
  .print-meta p { font-size: 0.85rem !important; color: #334155 !important; margin: 2px 0 !important; }
  .print-divider { border: none !important; border-top: 2px solid #1e293b !important; margin-top: 15px !important; display: block !important; }

  /* 4. Expandir la sección de la tabla al 100% de la página imprimible */
  .taller-view-wrapper { padding: 0 !important; margin: 0 !important; }
  .taller-grid { display: block !important; width: 100% !important; }
  .taller-col-table { width: 100% !important; max-width: 100% !important; }
  .sdb-card { border: none !important; box-shadow: none !important; overflow: visible !important; }
  
  /* 5. Re-estilizar la tabla con bordes planos aptos para formato físico impreso */
  .sdb-custom-table { width: 100% !important; border: 1px solid #cbd5e1 !important; border-collapse: collapse !important; }
  .sdb-custom-table th { background-color: #f1f5f9 !important; color: #0f172a !important; border-bottom: 2px solid #cbd5e1 !important; padding: 10px 12px !important; font-weight: 700 !important; }
  .sdb-custom-table td { padding: 10px 12px !important; border-bottom: 1px solid #e2e8f0 !important; color: #000000 !important; }
  
  /* 6. Ocultar los controles de selección inline y activar el texto plano del estado */
  .sdb-status-select { display: none !important; }
  .print-status-text { display: inline-block !important; font-weight: 700 !important; font-size: 0.85rem !important; text-transform: uppercase !important; color: #000000 !important; }
  
  /* Adaptación de insignias en escala de grises / bordes finos para PDF */
  .sdb-badge, .sdb-ci-badge { border: 1px solid #94a3b8 !important; background: transparent !important; color: #000000 !important; display: inline-flex !important; }
}
</style>