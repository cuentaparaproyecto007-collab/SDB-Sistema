<template>
  <div class="gestion-container animate__animated animate__fadeIn">
    
    <div v-if="sugerencia && !isEditing" class="alert-banner animate__animated animate__fadeIn">
      <div class="alert-content">
        <span class="icon">💡</span>
        <p><b>Sugerencia de Balanceo:</b> {{ sugerencia.mensaje }}</p>
      </div>
      <button class="btn-action-banner" @click="form.cuadrilla_id = sugerencia.id">Usar esta</button>
    </div>

    <div class="row-layout">
      
      <aside class="form-sidebar">
        <div class="card shadow-soft">
          <div class="card-header" :class="{ 'bg-orange': isEditing }">
            <i class="icon">{{ isEditing ? '✏️' : '👷' }}</i> {{ isEditing ? 'Editar Obrero' : 'Registro Oficial de Obreros' }}
          </div>
          
          <form @submit.prevent="guardarObrero" class="form-body">
            <div class="form-group">
              <label>Nombres</label>
              <input v-model="form.nombres" type="text" placeholder="Ej: Juan Alberto" required :disabled="loading">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Apellido Paterno</label>
                <input v-model="form.apellido_paterno" type="text" placeholder="Primer Apellido" required :disabled="loading">
              </div>
              <div class="form-group">
                <label>Apellido Materno</label>
                <input v-model="form.apellido_materno" type="text" placeholder="Segundo Apellido" :disabled="loading">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>C.I. (Carnet de Identidad)</label>
                <input v-model="form.ci" type="text" placeholder="1234567 LP" required :disabled="loading">
              </div>
              <div class="form-group">
                <label>Género</label>
                <select v-model="form.genero" required :disabled="loading">
                  <option value="Masculino">Masculino</option>
                  <option value="Femenino">Femenino</option>
                  <option value="Otro">Otro</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Especialidad</label>
              <input v-model="form.especialidad" type="text" placeholder="Ej: Chofer, Albañil, Operario" required :disabled="loading">
            </div>

            <div class="form-group">
              <label>Dirección de Domicilio</label>
              <textarea v-model="form.direccion" rows="2" placeholder="Zona, Calle y Nro de casa" :disabled="loading"></textarea>
            </div>

            <div class="form-group">
              <label>Asignar a Cuadrilla</label>
              <select v-model="form.cuadrilla_id" :disabled="loading">
                <option :value="null">-- Seleccionar Cuadrilla (Opcional) --</option>
                <option v-for="c in cuadrillas" :key="c.id" :value="c.id">
                  {{ c.nombre }} ({{ c.obreros_count }} miembros)
                </option>
              </select>
            </div>

            <div class="actions btn-group-vertical">
              <button type="submit" class="btn-save full-width" :class="{ 'btn-orange': isEditing }" :disabled="loading">
                <i class="icon">💾</i> {{ loading ? 'Procesando...' : (isEditing ? 'Actualizar Datos' : 'Registrar Obrero') }}
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
              <span><i class="icon">📋</i> Personal de Campo Activo</span>
              <span class="count-badge">{{ filteredObreros.length }} de {{ obreros.length }} Obreros</span>
            </div>
          </div>
          
          <div class="toolbar-table">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="🔍 Buscar por CI, nombre, especialidad o cuadrilla..." 
              class="search-input"
            >
            <div class="export-buttons">
              <button 
                @click="exportarExcel" 
                class="btn-excel" 
                :disabled="filteredObreros.length === 0"
                title="Exportar a Excel"
              >
                🟢 Excel
              </button>
              <button 
                @click="exportarPDF" 
                class="btn-pdf" 
                :disabled="filteredObreros.length === 0"
                title="Generar Reporte PDF Oficial"
              >
                🔴 PDF
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>C.I.</th>
                  <th>Nombre Completo</th>
                  <th>Especialidad</th>
                  <th>Cuadrilla</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in filteredObreros" :key="o.id" class="table-row" :class="{ 'row-editing': editingId === o.id }">
                  <td><span class="badge-ci">🔑 {{ o.ci }}</span></td>
                  <td><span class="team-name">{{ o.nombres }} {{ o.apellido_paterno }} {{ o.apellido_materno || '' }}</span></td>
                  <td><span class="badge-spec">🛠️ {{ o.especialidad }}</span></td>
                  <td>
                    <span v-if="o.cuadrilla" class="text-team">🚧 {{ o.cuadrilla.nombre }}</span>
                    <span v-else class="text-muted italic">Sin asignar</span>
                  </td>
                  <td class="text-center">
                    <div class="btn-actions-group">
                      <button :disabled="loading" @click="verObrero(o.id)" class="btn-action btn-view" title="Ver perfil completo">👁️</button>
                      <button :disabled="loading" @click="activarEdicion(o)" class="btn-action btn-edit" title="Editar datos">✏️</button>
                      <button :disabled="loading" @click="eliminarObrero(o.id, `${o.nombres} ${o.apellido_paterno}`)" class="btn-action btn-delete" title="Dar de baja obrero">🗑️</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredObreros.length === 0 && !loading">
                  <td colspan="5" class="empty-state">
                    No se encontraron obreros que coincidan con la búsqueda.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

    </div>

    <div v-if="modalDetalle" class="modal-overlay animate__animated animate__fadeIn">
      <div class="modal-card animate__animated animate__zoomIn">
        <div class="modal-header">
          <h5>📋 Expediente Técnico del Personal</h5>
          <button @click="modalDetalle = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" v-if="obreroSeleccionado">
          <div class="profile-card-header">
            <span class="profile-avatar">👷‍♂️</span>
            <h4>{{ obreroSeleccionado.nombres }} {{ obreroSeleccionado.apellido_paterno }} {{ obreroSeleccionado.apellido_materno || '' }}</h4>
            <span class="badge-spec-modal">{{ obreroSeleccionado.especialidad }}</span>
          </div>
          <hr class="divider">
          <div class="detail-grid">
            <p><strong>Documento de Identidad:</strong> <span class="text-dark-blue">{{ obreroSeleccionado.ci }}</span></p>
            <p><strong>Género registrado:</strong> {{ obreroSeleccionado.genero }}</p>
            <p><strong>Equipo Asignado:</strong> {{ obreroSeleccionado.cuadrilla?.nombre || 'Ninguno (Disponible)' }}</p>
            <p><strong>Dirección de Domicilio:</strong></p>
            <p class="address-box">{{ obreroSeleccionado.direccion || 'No se registró domicilio.' }}</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const loading = ref(false);
const cuadrillas = ref([]);
const obreros = ref([]);
const sugerencia = ref(null);
const token = localStorage.getItem('token');
const searchQuery = ref('');

const isEditing = ref(false);
const editingId = ref(null);
const modalDetalle = ref(false);
const obreroSeleccionado = ref(null);

const form = ref({ nombres: '', apellido_paterno: '', apellido_materno: '', ci: '', genero: 'Masculino', direccion: '', especialidad: '', cuadrilla_id: null });

const filteredObreros = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return obreros.value;
  return obreros.value.filter(o => {
    const nombreCompleto = `${o.nombres} ${o.apellido_paterno} ${o.apellido_materno || ''}`.toLowerCase();
    const ci = (o.ci || '').toLowerCase();
    const especialidad = (o.especialidad || '').toLowerCase();
    const cuadrilla = o.cuadrilla ? o.cuadrilla.nombre.toLowerCase() : 'sin asignar';
    return nombreCompleto.includes(query) || ci.includes(query) || especialidad.includes(query) || cuadrilla.includes(query);
  });
});

const cargarDatos = async () => {
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const resC = await axios.get('http://localhost:8000/api/cuadrillas', config);
    cuadrillas.value = resC.data;
    const resO = await axios.get('http://localhost:8000/api/obreros', config);
    obreros.value = resO.data;
    const resS = await axios.get('http://localhost:8000/api/cuadrillas/sugerencia', config);
    if (resS.data.sugerencia) { sugerencia.value = { id: resS.data.sugerencia.id, mensaje: resS.data.mensaje }; }
    else { sugerencia.value = null; }
  } catch (e) { console.error(e); }
};

// EXPORTACIÓN EXCEL
const exportarExcel = () => {
  let csvContent = "\uFEFF";
  csvContent += "Carnet de Identidad;Nombres;Apellido Paterno;Apellido Materno;Género;Especialidad Técnica;Dirección de Domicilio;Cuadrilla Asignada\n";
  filteredObreros.value.forEach(o => {
    const cuadrillaNombre = o.cuadrilla ? o.cuadrilla.nombre : 'Sin Asignar';
    const direccionLimpia = (o.direccion || '').replace(/[\r\n]+/g, " "); 
    csvContent += `${o.ci};${o.nombres};${o.apellido_paterno};${o.apellido_materno || ''};${o.genero};${o.especialidad};${direccionLimpia};${cuadrillaNombre}\n`;
  });
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute("download", `SDB_Reporte_Obreros_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// EXPORTACIÓN PDF
const exportarPDF = () => {
  const ventanaPrint = window.open('', '_blank');
  const fechaHoy = new Date().toLocaleString('es-BO', { timeZone: 'America/La_Paz' });
  
  let filasHtml = '';
  filteredObreros.value.forEach(o => {
    filasHtml += `
      <tr>
        <td style="font-weight: bold; color: #1a365d;">${o.ci}</td>
        <td>${o.nombres} ${o.apellido_paterno} ${o.apellido_materno || ''}</td>
        <td><span style="background: #f7fafc; padding: 2px 6px; border: 1px solid #e2e8f0; border-radius: 4px;">${o.especialidad}</span></td>
        <td style="color: #2b6cb0; font-weight: 500;">${o.cuadrilla ? o.cuadrilla.nombre : '<i>Sin Asignar</i>'}</td>
      </tr>
    `;
  });

  ventanaPrint.document.write(`
    <html>
      <head>
        <title>SDB - Reporte del Personal de Campo</title>
        <style>
          body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; margin: 40px; line-height: 1.4; }
          .header-table { width: 100%; border-collapse: collapse; border-bottom: 3px solid #42b983; padding-bottom: 12px; margin-bottom: 25px; }
          .title-system { font-size: 1.6rem; font-weight: 800; color: #2c3e50; letter-spacing: -0.5px; }
          .subtitle-report { font-size: 1rem; color: #718096; text-transform: uppercase; margin-top: 4px; font-weight: 600; }
          .meta-info { font-size: 0.82rem; color: #a0aec0; text-align: right; vertical-align: bottom; }
          .summary-box { background: #f7fafc; border-left: 4px solid #3182ce; padding: 12px 18px; border-radius: 0 6px 6px 0; margin-bottom: 25px; font-size: 0.9rem; }
          .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          .main-table th { background: #2d3748; color: #ffffff; padding: 12px 14px; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; text-align: left; }
          .main-table td { padding: 12px 14px; border-bottom: 1px solid #edf2f7; font-size: 0.9rem; color: #2d3748; }
          .main-table tr:nth-child(even) td { background-color: #fcfcfc; }
          .footer-note { text-align: center; margin-top: 50px; font-size: 0.78rem; color: #cbd5e0; border-top: 1px dashed #e2e8f0; padding-top: 15px; }
        </style>
      </head>
      <body>
        <table class="header-table">
          <tr>
            <td>
              <div class="title-system">SISTEMA S.D.B.</div>
              <div class="subtitle-report">Reporte Oficial - Personal de Campo Activo</div>
            </td>
            <td class="meta-info">
              <div>Generado el: <b>${fechaHoy}</b></div>
              <div>Filtro activo: <b>${searchQuery.value || 'Ninguno (Todos)'}</b></div>
            </td>
          </tr>
        </table>
        
        <div class="summary-box">
          Este reporte certifica los datos del personal operativo registrado en el sistema de gestión vial baches. Actualmente se listan <b>${filteredObreros.value.length} obreros activos</b> bajo los criterios seleccionados.
        </div>

        <table class="main-table">
          <thead>
            <tr>
              <th style="width: 20%;">C.I. / Documento</th>
              <th style="width: 40%;">Nombre Completo</th>
              <th style="width: 20%;">Especialidad Técnica</th>
              <th style="width: 20%;">Cuadrilla Asignada</th>
            </tr>
          </thead>
          <tbody>
            ${filasHtml}
          </tbody>
        </table>

        <div class="footer-note">
          © Sistema S.D.B. - Software de Gestión de Infraestructura Vial Urbana. Documento Auditado de Carácter Interno.
        </div>
      </body>
    </html>
  `);

  ventanaPrint.document.close();
  setTimeout(() => { ventanaPrint.print(); }, 350);
};

const guardarObrero = async () => {
  loading.value = true;
  const config = { headers: { Authorization: `Bearer ${token}` } };
  try {
    if (isEditing.value) {
      await axios.put(`http://localhost:8000/api/obreros/${editingId.value}`, form.value, config);
      alert("Datos del obrero actualizados con éxito.");
      cancelarEdicion();
    } else {
      await axios.post('http://localhost:8000/api/obreros', form.value, config);
      alert("Obrero registrado y auditado correctamente");
      resetForm();
    }
    await cargarDatos();
  } catch (e) { alert(e.response?.data?.message || "Error al procesar la operación."); }
  finally { loading.value = false; }
};

const activarEdicion = (obrero) => {
  isEditing.value = true;
  editingId.value = obrero.id;
  form.value = { nombres: obrero.nombres, apellido_paterno: obrero.apellido_paterno, apellido_materno: obrero.apellido_materno, ci: obrero.ci, genero: obrero.genero, direccion: obrero.direccion, especialidad: obrero.especialidad, cuadrilla_id: obrero.cuadrilla_id };
};

const cancelarEdicion = () => { isEditing.value = false; editingId.value = null; resetForm(); };
const verObrero = async (id) => {
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.get(`http://localhost:8000/api/obreros/${id}`, config);
    obreroSeleccionado.value = res.data;
    modalDetalle.value = true;
  } catch (e) { alert("No se pudo obtener el expediente del trabajador."); }
  finally { loading.value = false; }
};

const eliminarObrero = async (id, nombreCompleto) => {
  const seguro = confirm(`¿Está seguro que desea dar de baja al obrero "${nombreCompleto}"? Su historial se conservará intacto.`);
  if (!seguro) return;
  
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.delete(`http://localhost:8000/api/obreros/${id}`, config);
    
    alert(res.data.message || "Obrero dado de baja con éxito.");
    
    if (editingId.value === id) cancelarEdicion();
    obreros.value = obreros.value.filter(item => item.id !== id);
    await cargarDatos(); 
  } catch (e) { 
    alert(e.response?.data?.message || "Error al procesar la baja del trabajador."); 
  } finally { 
    loading.value = false; 
  }
};

const resetForm = () => { form.value = { nombres: '', apellido_paterno: '', apellido_materno: '', ci: '', genero: 'Masculino', direccion: '', especialidad: '', cuadrilla_id: null }; };
onMounted(cargarDatos);
</script>

<style scoped>
.gestion-container { padding: 5px; }
.row-layout { display: flex; gap: 20px; flex-wrap: wrap; }
.form-sidebar { flex: 1; min-width: 320px; }
.table-content { flex: 2; min-width: 500px; transition: all 0.3s ease; }

/* 🔥 REINCORPORADO: ESTILOS DE LA NUEVA BARRA DE HERRAMIENTAS DE CONTROL */
.toolbar-table { display: flex; gap: 12px; padding: 15px 20px; background: #fafafa; border-bottom: 1px solid #edf2f7; align-items: center; justify-content: space-between; }
.search-input { flex: 1; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 0.9rem; }
.search-input:focus { border-color: #42b983; background: white; outline: none; }
.export-buttons { display: flex; gap: 8px; }

.btn-excel { background: #217346; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; cursor: pointer; transition: 0.2s; }
.btn-excel:hover:not(:disabled) { background: #154c2e; }

.btn-pdf { background: #e74c3c; color: white; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; cursor: pointer; transition: 0.2s; }
.btn-pdf:hover:not(:disabled) { background: #c0392b; transform: translateY(-1px); }
.btn-pdf:disabled, .btn-excel:disabled { background: #cbd5e0; cursor: not-allowed; transform: none; }

/* 🔥 SOLUCIÓN: CLASES DEL BANNER DE SUGERENCIA RESTAURADAS */
.alert-banner { background: #e3f2fd; border-left: 5px solid #2196f3; padding: 15px; margin-bottom: 25px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; width: 100%; }
.alert-content { display: flex; align-items: center; gap: 12px; }
.alert-content p { margin: 0; color: #0d47a1; font-size: 0.95rem; }
.btn-action-banner { background: #2196f3; color: white; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s; }
.btn-action-banner:hover { background: #1e88e5; }

.card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #edf2f7; }
.card-header { background: #2c3e50; color: #42b983; padding: 18px 25px; font-weight: 700; border-bottom: 4px solid #42b983; font-size: 1.1rem; }
.bg-dark { background: #34495e; border-bottom: 4px solid #42b983; }
.bg-orange { background: #d69e2e !important; color: white !important; border-bottom: 4px solid #b7791f !important; }
.header-flex { display: flex; justify-content: space-between; align-items: center; }
.count-badge { background: rgba(66, 185, 131, 0.2); color: #42b983; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; }
.form-body { padding: 25px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-group { margin-bottom: 18px; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 8px; font-weight: 600; color: #4a5568; font-size: 0.95rem; }
input, select, textarea { padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; color: #2d3748; font-family: inherit; }
input:focus, select:focus, textarea:focus { border-color: #42b983; outline: none; background: #f0fff4; }
.btn-save { background: #42b983; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-save:hover:not(:disabled) { background: #3aa373; transform: translateY(-2px); }
.btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.full-width { width: 100%; }
.mt-2 { margin-top: 8px; }
.table-responsive { padding: 0; }
.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { background: #f8fafc; text-align: left; padding: 15px 20px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.custom-table td { padding: 16px 20px; border-bottom: 1px solid #edf2f7; color: #2d3748; vertical-align: middle; font-size: 0.95rem; }
.table-row:hover { background-color: #f7fafc; }
.row-editing { background-color: #fefcbf !important; border-left: 4px solid #dd6b20; }
.team-name { font-weight: 700; color: #2c3e50; }
.text-team { color: #3182ce; font-weight: 600; }
.btn-actions-group { display: flex; gap: 6px; justify-content: center; }
.btn-action { border: none; background: none; font-size: 1rem; padding: 6px 10px; cursor: pointer; border-radius: 6px; transition: all 0.2s ease; }
.btn-action:hover:not(:disabled) { transform: scale(1.15); }
.btn-view:hover:not(:disabled) { background: #e2e8f0; }
.btn-edit:hover:not(:disabled) { background: #feebc8; }
.btn-delete:hover:not(:disabled) { background: #fed7d7; }
.badge-ci { background: #ebf8ff; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
.badge-spec { background: #f7fafc; color: #4a5568; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0; }
.empty-state { padding: 50px !important; color: #a0aec0; font-style: italic; text-align: center; }
.text-center { text-align: center; }
.text-muted { color: #a0aec0; font-size: 0.85rem; }
.italic { font-style: italic; }
.icon { margin-right: 8px; font-style: normal; }

/* MODAL DETALLES */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); display: flex; justify-content: center; align-items: center; z-index: 2000; }
.modal-card { background: white; border-radius: 12px; width: 420px; max-width: 90%; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
.modal-header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-header h5 { margin: 0; font-size: 1.05rem; font-weight: 600; color: #42b983; }
.close-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }
.modal-body { padding: 25px; text-align: center; }
.profile-card-header { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.profile-avatar { font-size: 3.5rem; }
.profile-card-header h4 { margin: 5px 0 0 0; color: #2c3e50; font-size: 1.25rem; }
.badge-spec-modal { background: #e6fffa; color: #234e52; border: 1px solid #b2f5ea; padding: 4px 14px; border-radius: 99px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
.divider { margin: 20px 0; border: 0; border-top: 1px solid #edf2f7; }
.detail-grid { text-align: left; }
.detail-grid p { margin: 8px 0; font-size: 0.95rem; color: #4a5568; }
.text-dark-blue { color: #2b6cb0; font-weight: 700; }
.address-box { background: #f7fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #edf2f7; color: #718096 !important; font-size: 0.88rem !important; font-style: italic; }

@media (max-width: 1024px) { .row-layout { flex-direction: column; } }
</style>