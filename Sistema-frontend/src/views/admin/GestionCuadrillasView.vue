<template>
  <div class="gestion-container animate__animated animate__fadeIn">
    <div class="row-layout">
      
      <!-- 🏘️ SIDEBAR DE CREACIÓN/EDICIÓN: Ahora visible tanto para Administrador como para Técnico -->
      <aside v-if="user && ['administrador', 'técnico'].includes(user.role?.nombre?.toLowerCase())" class="form-sidebar">
        <div class="card shadow-soft">
          <div class="card-header" :class="{ 'bg-orange': isEditing }">
            <i class="icon">{{ isEditing ? '✏️' : '🏘️' }}</i> {{ isEditing ? 'Editar Cuadrilla' : 'Crear Nueva Cuadrilla' }}
          </div>
          <form @submit.prevent="guardarCuadrilla" class="form-body">
            <div class="form-group">
              <label>Nombre de la Cuadrilla</label>
              <input 
                v-model="form.nombre" 
                type="text" 
                placeholder="Ej: Cuadrilla Central-01" 
                required
                :disabled="loading"
              >
            </div>
            
            <div class="form-group">
              <label>Jefe de Cuadrilla Autorizado</label>
              <select v-model="form.jefe_id" required :disabled="loading">
                <option :value="null" disabled>-- Seleccionar Jefe --</option>
                <option v-for="u in usuariosJefes" :key="u.id" :value="u.id">
                  {{ u.name }} ({{ u.ci || 'Sin CI' }})
                </option>
              </select>
              <small class="hint-error" v-if="usuariosJefes.length === 0 && !isEditing">
                ⚠️ No hay personal con rol "JEFE DE CUADRILLA" disponible.
              </small>
            </div>

            <div class="actions btn-group-vertical">
              <button type="submit" class="btn-primary full-width" :class="{ 'btn-orange': isEditing }" :disabled="loading || (!isEditing && usuariosJefes.length === 0)">
                <i class="icon">💾</i> {{ loading ? 'Procesando...' : (isEditing ? 'Actualizar Equipo' : 'Registrar Equipo') }}
              </button>
              <button v-if="isEditing" type="button" @click="cancelarEdicion" class="btn-secondary full-width mt-2" :disabled="loading">
                Cancelar Edición
              </button>
            </div>
          </form>
        </div>
      </aside>

      <!-- 📋 CONTENEDOR DE LA TABLA: Ajusta su ancho si el usuario es Admin o Técnico para dar espacio al formulario -->
      <main class="table-content" :class="{ 'w-100': !user || !['administrador', 'técnico'].includes(user.role?.nombre?.toLowerCase()) }">
        <div class="card border-none">
          <div class="card-header bg-dark">
            <div class="header-flex">
              <span><i class="icon">📋</i> Equipos de Trabajo Activos</span>
              <span class="count-badge">{{ filteredCuadrillas.length }} de {{ cuadrillas.length }} Cuadrillas</span>
            </div>
          </div>

          <div class="toolbar-table">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="🔍 Buscar por ID, nombre de equipo o jefe responsable..." 
              class="search-input"
            >
            <button 
              type="button"
              @click="exportarExcel" 
              class="btn-excel" 
              :disabled="filteredCuadrillas.length === 0"
              title="Exportar cuadrillas filtradas a Excel"
            >
              🟢 Exportar Excel
            </button>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Nombre del Equipo</th>
                  <th>Jefe Responsable</th>
                  <th class="text-center">Personal</th>
                  <!-- 🔥 ACCIONES: Visible para el Técnico -->
                  <th v-if="user && ['administrador', 'técnico'].includes(user.role?.nombre?.toLowerCase())" class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in filteredCuadrillas" :key="c.id" class="table-row" :class="{ 'row-editing': editingId === c.id }">
                  <td class="text-muted">#{{ c.id }}</td>
                  <td><span class="team-name">{{ c.nombre }}</span></td>
                  <td>
                    <div class="jefe-info">
                      <i class="icon-small">👤</i> {{ c.jefe?.name || 'No asignado' }}
                    </div>
                  </td>
                  <td class="text-center">
                    <span :class="['badge-pill', c.obreros_count < 3 ? 'pill-warn' : 'pill-success']">
                      {{ c.obreros_count }} obreros
                    </span>
                  </td>
                  <!-- 🔥 ACCIONES CRUD: Ahora operables por el Técnico -->
                  <td v-if="user && ['administrador', 'técnico'].includes(user.role?.nombre?.toLowerCase())" class="text-center">
                    <div class="btn-actions-group">
                      <button :disabled="loading" @click="verCuadrilla(c.id)" class="btn-action btn-view" title="Ver integrantes">👁️</button>
                      <button :disabled="loading" @click="activarEdicion(c)" class="btn-action btn-edit" title="Editar datos">✏️</button>
                      <button :disabled="loading" @click="eliminarCuadrilla(c.id, c.nombre)" class="btn-action btn-delete" title="Eliminar cuadrilla">🗑️</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredCuadrillas.length === 0 && !loading">
                  <!-- 🔥 COLSPAN DINÁMICO: Se adapta según las columnas visibles -->
                  <td :colspan="user && ['administrador', 'técnico'].includes(user.role?.nombre?.toLowerCase()) ? 5 : 4" class="empty-state">
                    No se encontraron cuadrillas que coincidan con la búsqueda.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

    </div>

    <!-- Modal de integrantes asignados -->
    <div v-if="modalDetalle" class="modal-overlay animate__animated animate__fadeIn">
      <div class="modal-card animate__animated animate__zoomIn">
        <div class="modal-header">
          <h5>📋 Personal Asignado a la Cuadrilla</h5>
          <button @click="modalDetalle = false" class="close-btn">&times;</button>
        </div>
        <div class="modal-body" v-if="cuadrillaSeleccionada">
          <div class="detail-summary">
            <p><strong>Equipo:</strong> {{ cuadrillaSeleccionada.nombre }}</p>
            <p><strong>Jefe a Cargo:</strong> {{ cuadrillaSeleccionada.jefe?.name || 'No asignado' }}</p>
          </div>

          <div class="modal-report-actions">
            <button @click="exportarPdfIndividual(cuadrillaSeleccionada.id)" class="btn-modal-action btn-pdf" :disabled="loading">
              📄 Imprimir PDF
            </button>
            <button @click="exportarExcelIndividual(cuadrillaSeleccionada)" class="btn-modal-action btn-excel-item" :disabled="loading">
              📊 Descargar Excel
            </button>
          </div>

          <h6>Lista de Obreros Disponibles:</h6>
          <ul class="obreros-list">
            <li v-for="obrero in cuadrillaSeleccionada.obreros" :key="obrero.id" class="obrero-card-item">
              <div class="obrero-profile-info">
                <span class="obrero-avatar-icon">👷‍♂️</span>
                <div class="obrero-text-details">
                  <span class="obrero-full-name">
                    {{ obrero.nombres }} {{ obrero.apellido_paterno }} {{ obrero.apellido_materno || '' }}
                  </span>
                  <span class="obrero-ci-badge">
                    🔑 CI: <strong>{{ obrero.ci }}</strong>
                  </span>
                </div>
              </div>
            </li>
            <li v-if="!cuadrillaSeleccionada.obreros || cuadrillaSeleccionada.obreros.length === 0" class="empty-obreros-state">
              No hay obreros cargados en este equipo todavía.
            </li>
          </ul>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const user = ref(null);
const cuadrillas = ref([]);
const usuariosJefes = ref([]);
const loading = ref(false);
const token = localStorage.getItem('token');

const isEditing = ref(false);
const editingId = ref(null);
const modalDetalle = ref(false);
const cuadrillaSeleccionada = ref(null);

const form = ref({ nombre: '', jefe_id: null });
const searchQuery = ref('');

const filteredCuadrillas = computed(() => {
  const query = searchQuery.value.toLowerCase().trim();
  if (!query) return cuadrillas.value;

  return cuadrillas.value.filter(c => {
    const id = c.id.toString();
    const nombreEquipo = (c.nombre || '').toLowerCase();
    const jefeNombre = c.jefe ? c.jefe.name.toLowerCase() : 'no asignado';

    return id.includes(query) || nombreEquipo.includes(query) || jefeNombre.includes(query);
  });
});

const cargarDatos = async () => {
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    
    const resUser = await axios.get('http://localhost:8000/api/user', config);
    user.value = resUser.data;
    
    const resC = await axios.get('http://localhost:8000/api/cuadrillas', config);
    cuadrillas.value = resC.data;
    
    // 🔥 REFACTORIZADO: Permitimos que el Técnico también descargue la lista de Jefes de Cuadrilla para armar los equipos
    const rangoLimpio = user.value.role?.nombre?.toLowerCase();
    if (rangoLimpio === 'administrador' || rangoLimpio === 'técnico') {
      const resU = await axios.get('http://localhost:8000/api/users', config);
      usuariosJefes.value = resU.data.filter(u => 
        u.role && (u.role.nombre.toLowerCase() === 'jefe de cuadrilla')
      );
    }

  } catch (e) {
    console.error("Error crítico de sincronización del sistema:", e);
  } finally {
    loading.value = false;
  }
};

const exportarExcel = () => {
  let csvContent = "\uFEFF"; 
  csvContent += "ID de Cuadrilla;Nombre del Equipo de Trabajo;Jefe Responsable Asignado;Cantidad de Personal Activo\n";

  filteredCuadrillas.value.forEach(c => {
    const jefeResponsable = c.jefe ? c.jefe.name : 'No Asignado';
    csvContent += `#${c.id};${c.nombre};${jefeResponsable};${c.obreros_count || 0} obreros\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  const fecha = new Date().toISOString().slice(0, 10);

  link.setAttribute("href", url);
  link.setAttribute("download", `SDB_Reporte_Cuadrillas_${fecha}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const exportarPdfIndividual = async (id) => {
  loading.value = true;
  try {
    const config = { 
      headers: { Authorization: `Bearer ${token}` },
      responseType: 'blob' 
    };
    
    const res = await axios.get(`http://localhost:8000/api/cuadrillas/${id}/exportar-pdf`, config);
    
    const blob = new Blob([res.data], { type: 'application/pdf' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `SDB_Cuadrilla_N${id}_Personal.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  } catch (e) {
    console.error("Fallo al compilar el PDF de cuadrilla:", e);
    alert("No se pudo generar el reporte PDF. Verifica las rutas del servidor.");
  } finally {
    loading.value = false;
  }
};

const exportarExcelIndividual = (cuadrilla) => {
  if (!cuadrilla.obreros || cuadrilla.obreros.length === 0) {
    alert("Esta cuadrilla no registra personal obrero asignado.");
    return;
  }

  let csvContent = "\uFEFF"; 
  csvContent += `S.D.B. DETECCIÓN DE BACHES - REPORTE INTERNO DE PERSONAL\n`;
  csvContent += `NOMBRE DEL EQUIPO: ${cuadrilla.nombre.toUpperCase()}\n`;
  csvContent += `JEFE RESPONSABLE: ${cuadrilla.jefe?.name || 'No asignado'}\n\n`;
  csvContent += "NOMBRES;APELLIDO PATERNO;APELLIDO MATERNO;CÉDULA DE IDENTIDAD (CI)\n";

  cuadrilla.obreros.forEach(o => {
    const materno = o.apellido_materno || '';
    csvContent += `${o.nombres};${o.apellido_paterno};${materno};${o.ci}\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  const nombreLimpio = cuadrilla.nombre.replace(/\s+/g, '_');

  link.setAttribute("href", url);
  link.setAttribute("download", `Personal_Cuadrilla_${nombreLimpio}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
};

const guardarCuadrilla = async () => {
  if (!form.value.nombre || !form.value.jefe_id) return;
  
  loading.value = true;
  const config = { headers: { Authorization: `Bearer ${token}` } };
  
  try {
    if (isEditing.value) {
      await axios.put(`http://localhost:8000/api/cuadrillas/${editingId.value}`, form.value, config);
      alert("Cuadrilla modificada con éxito.");
      cancelarEdicion();
    } else {
      await axios.post('http://localhost:8000/api/cuadrillas', form.value, config);
      alert("Cuadrilla '" + form.value.nombre + "' creada exitosamente.");
      form.value = { nombre: '', jefe_id: null };
    }
    await cargarDatos(); 
  } catch (e) {
    alert(e.response?.data?.message || "Error al procesar la operación en el servidor.");
  } finally {
    loading.value = false;
  }
};

const activarEdicion = (cuadrilla) => {
  isEditing.value = true;
  editingId.value = cuadrilla.id;
  form.value = {
    nombre: cuadrilla.nombre,
    jefe_id: cuadrilla.jefe_id
  };
};

const cancelarEdicion = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = { nombre: '', jefe_id: null };
};

const verCuadrilla = async (id) => {
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.get(`http://localhost:8000/api/cuadrillas/${id}`, config);
    cuadrillaSeleccionada.value = res.data;
    modalDetalle.value = true;
  } catch (e) {
    alert("No se pudieron cargar los detalles del equipo seleccionado.");
  } finally {
    loading.value = false;
  }
};

const eliminarCuadrilla = async (id, nombre) => {
  const seguro = confirm(`¿Está seguro que desea dar de baja a la cuadrilla "${nombre}"?`);
  if (!seguro) return;

  loading.value = true; 
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.delete(`http://localhost:8000/api/cuadrillas/${id}`, config);
    alert(res.data.message || "Cuadrilla eliminada con éxito.");
    
    if (editingId.value === id) cancelarEdicion();
    cuadrillas.value = cuadrillas.value.filter(item => item.id !== id);
    
  } catch (e) {
    alert(e.response?.data?.message || "Error de comunicación con el motor de base de datos.");
    await cargarDatos(); 
  } finally {
    loading.value = false; 
  }
};

onMounted(cargarDatos);
</script>

<style scoped>
.gestion-container { padding: 5px; }
.row-layout { display: flex; gap: 20px; flex-wrap: wrap; }

.form-sidebar { flex: 1; min-width: 320px; }
.table-content { flex: 2; min-width: 500px; transition: all 0.3s ease; }
.w-100 { flex: 1 1 100% !important; width: 100%; }

.toolbar-table { display: flex; gap: 12px; padding: 15px 20px; background: #fafafa; border-bottom: 1px solid #edf2f7; align-items: center; }
.search-input { flex: 1; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 0.9rem; }
.search-input:focus { border-color: #42b983; background: white; outline: none; }
.btn-excel { background: #217346; color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; font-size: 0.9rem; cursor: pointer; transition: 0.2s; display: flex; align-items: center; }
.btn-excel:hover:not(:disabled) { background: #154c2e; transform: translateY(-1px); }
.btn-excel:disabled { background: #cbd5e0; cursor: not-allowed; transform: none; }

.card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #edf2f7; }
.card-header { background: #2c3e50; color: #42b983; padding: 18px 25px; font-weight: 700; border-bottom: 4px solid #42b983; font-size: 1.1rem; transition: background-color 0.3s; }
.bg-dark { background: #34495e; border-bottom: 4px solid #42b983; }
.bg-orange { background: #d69e2e !important; color: white !important; border-bottom: 4px solid #b7791f !important; }

.header-flex { display: flex; justify-content: space-between; align-items: center; }
.count-badge { background: rgba(66, 185, 131, 0.2); color: #42b983; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; }

.form-body { padding: 25px; }
.form-body :disabled { opacity: 0.7; cursor: not-allowed; }

.form-group { margin-bottom: 22px; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 10px; font-weight: 600; color: #4a5568; font-size: 0.95rem; }

input, select { padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; transition: all 0.2s ease; color: #2d3748; }
input:focus, select:focus { border-color: #42b983; outline: none; background: #f0fff4; }

.hint-error { color: #e53e3e; margin-top: 8px; font-size: 0.8rem; }

.btn-primary { background: #42b983; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 10px; }
.btn-primary:hover:not(:disabled) { background: #3aa373; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(66, 185, 131, 0.3); }
.btn-primary:disabled { background: #cbd5e0; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
.btn-orange { background: #dd6b20 !important; }
.btn-orange:hover:not(:disabled) { background: #c05621 !important; box-shadow: 0 4px 12px rgba(221, 107, 32, 0.3) !important; }
.btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-secondary:hover { background: #cbd5e0; }
.full-width { width: 100%; }
.mt-2 { margin-top: 8px; }

.table-responsive { padding: 0; }
.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { background: #f8fafc; text-align: left; padding: 15px 20px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; }
.custom-table td { padding: 18px 20px; border-bottom: 1px solid #edf2f7; color: #2d3748; vertical-align: middle; }
.table-row:hover { background-color: #f7fafc; }
.row-editing { background-color: #fefcbf !important; border-left: 4px solid #dd6b20; }

.team-name { font-weight: 700; color: #2c3e50; }
.jefe-info { display: flex; align-items: center; gap: 8px; font-size: 0.95rem; }

.btn-actions-group { display: flex; gap: 6px; justify-content: center; }
.btn-action { border: none; background: none; font-size: 1rem; padding: 6px 10px; cursor: pointer; border-radius: 6px; transition: all 0.2s ease; border: 1px solid transparent; }
.btn-action:hover:not(:disabled) { transform: scale(1.15); }
.btn-action:disabled { opacity: 0.4; cursor: not-allowed; }
.btn-view:hover:not(:disabled) { background: #e2e8f0; border-color: #cbd5e0; }
.btn-edit:hover:not(:disabled) { background: #feebc8; border-color: #fbd38d; }
.btn-delete:hover:not(:disabled) { background: #fed7d7; border-color: #feb2b2; }

.badge-pill { padding: 6px 14px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
.pill-success { background: #c6f6d5; color: #22543d; }
.pill-warn { background: #feebc8; color: #7b341e; }
.empty-state { padding: 50px !important; color: #a0aec0; font-style: italic; text-align: center; }
.text-center { text-align: center; }
.text-muted { color: #a0aec0; font-size: 0.85rem; }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.4); display: flex; justify-content: center; align-items: center; z-index: 2000; }
.modal-card { background: white; border-radius: 12px; width: 450px; max-width: 90%; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
.modal-header { background: #2c3e50; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-header h5 { margin: 0; font-size: 1.05rem; font-weight: 600; color: #42b983; }
.close-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; line-height: 1; }
.close-btn:hover { color: #e53e3e; }
.modal-body { padding: 20px; }
.detail-summary { background: #f7fafc; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #42b983; }
.detail-summary p { margin: 4px 0; font-size: 0.95rem; }
.modal-body h6 { margin: 15px 0 8px 0; color: #4a5568; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; }
.obreros-list { list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto; }
.obreros-list li { padding: 8px 0; border-bottom: 1px solid #edf2f7; font-size: 0.95rem; }
.obreros-list li:last-child { border-bottom: none; }

.modal-report-actions { display: flex; gap: 10px; margin: 15px 0; }
.btn-modal-action { flex: 1; padding: 10px; border: none; border-radius: 6px; font-weight: bold; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 5px; }
.btn-modal-action:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-pdf { background-color: #e53e3e; color: white; }
.btn-pdf:hover:not(:disabled) { background-color: #c53030; transform: translateY(-1px); }
.btn-excel-item { background-color: #217346; color: white; }
.btn-excel-item:hover:not(:disabled) { background-color: #154c2e; transform: translateY(-1px); }

@media (max-width: 1024px) {
  .row-layout { flex-direction: column; }
}
</style>
