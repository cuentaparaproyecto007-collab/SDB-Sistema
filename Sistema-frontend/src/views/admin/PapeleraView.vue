<template>
  <div class="papelera-container">
    <div class="page-header">
      <h2>🗑️ Papelera de Reciclaje Centralizada</h2>
      <p class="subtitle">Recupera registros dados de baja lógicamente para restaurar su operatividad en el sistema S.D.B.</p>
    </div>

    <div class="tabs-container">
      <button 
        :class="['tab-btn', activeTab === 'users' ? 'tab-active' : '']" 
        @click="activeTab = 'users'"
      >
        👥 Cuentas de Usuario ({{ trashedUsers.length }})
      </button>
      <button 
        :class="['tab-btn', activeTab === 'cuadrillas' ? 'tab-active' : '']" 
        @click="activeTab = 'cuadrillas'"
      >
        🚧 Cuadrillas Operativas ({{ trashedCuadrillas.length }})
      </button>
      <button 
        :class="['tab-btn', activeTab === 'obreros' ? 'tab-active' : '']" 
        @click="activeTab = 'obreros'"
      >
        👷 Personal de Campo ({{ trashedObreros.length }})
      </button>
    </div>

    <div v-if="activeTab === 'users'" class="tab-content">
      <div v-if="loadingUsers" class="loading-state">Cargando usuarios eliminados...</div>
      
      <div v-else-if="trashedUsers.length === 0" class="empty-state">
        <p>🎉 La papelera de usuarios está limpia. No hay cuentas inactivas.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th>Nombre Completo</th>
              <th>Carnet de Identidad</th>
              <th>Correo Electrónico</th>
              <th>Rol Anterior</th>
              <th>Fecha de Baja</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in trashedUsers" :key="user.id">
              <td class="font-bold">{{ user.nombres }} {{ user.apellido_paterno }} {{ user.apellido_materno || '' }}</td>
              <td><span class="badge-ci">🔑 {{ user.ci }}</span></td>
              <td>{{ user.email }}</td>
              <td><span class="badge-role">{{ user.role?.nombre || 'Sin Rol' }}</span></td>
              <td class="text-muted">{{ formatDate(user.deleted_at) }}</td>
              <td class="text-center">
                <button @click="restoreUser(user)" class="btn-restore" :disabled="processingId === user.id">
                  {{ processingId === user.id ? 'Restaurando...' : '↩️ Restaurar Cuenta' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="activeTab === 'cuadrillas'" class="tab-content">
      <div v-if="loadingCuadrillas" class="loading-state">Cargando cuadrillas eliminadas...</div>
      
      <div v-else-if="trashedCuadrillas.length === 0" class="empty-state">
        <p>🎉 No hay cuadrillas dadas de baja. Todos los equipos están operativos en el mapa.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre de la Cuadrilla</th>
              <th>Jefe de Cuadrilla Anterior</th>
              <th>Fecha de Baja</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="cuadrilla in trashedCuadrillas" :key="cuadrilla.id">
              <td>#{{ cuadrilla.id }}</td>
              <td class="font-bold text-danger">🚧 {{ cuadrilla.nombre }}</td>
              <td>
                <span v-if="cuadrilla.jefe">
                  👤 {{ cuadrilla.jefe.nombres }} {{ cuadrilla.jefe.apellido_paterno }}
                </span>
                <span v-else class="text-muted italic">Sin jefe asignado</span>
              </td>
              <td class="text-muted">{{ formatDate(cuadrilla.deleted_at) }}</td>
              <td class="text-center">
                <button @click="restoreCuadrilla(cuadrilla)" class="btn-restore btn-restore-cuadrilla" :disabled="processingId === cuadrilla.id">
                  {{ processingId === cuadrilla.id ? 'Restaurando...' : '↩️ Reactivar Equipo' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="activeTab === 'obreros'" class="tab-content">
      <div v-if="loadingObreros" class="loading-state">Cargando personal de campo eliminado...</div>
      
      <div v-else-if="trashedObreros.length === 0" class="empty-state">
        <p>🎉 No hay obreros dados de baja. Todo el personal técnico está activo.</p>
      </div>

      <div v-else class="table-responsive">
        <table class="custom-table">
          <thead>
            <tr>
              <th>Nombre Completo</th>
              <th>Carnet de Identidad</th>
              <th>Especialidad</th>
              <th>Cuadrilla Anterior</th>
              <th>Fecha de Baja</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="obrero in trashedObreros" :key="obrero.id">
              <td class="font-bold">👷‍♂️ {{ obrero.nombres }} {{ obrero.apellido_paterno }} {{ obrero.apellido_materno || '' }}</td>
              <td><span class="badge-ci">🔑 {{ obrero.ci }}</span></td>
              <td><span class="badge-spec">🛠️ {{ obrero.especialidad }}</span></td>
              <td>
                <span v-if="obrero.cuadrilla" class="text-team">🚧 {{ obrero.cuadrilla.nombre }}</span>
                <span v-else class="text-muted italic">Sin cuadrilla</span>
              </td>
              <td class="text-muted">{{ formatDate(obrero.deleted_at) }}</td>
              <td class="text-center">
                <button @click="restoreObrero(obrero)" class="btn-restore btn-restore-obrero" :disabled="processingId === obrero.id">
                  {{ processingId === obrero.id ? 'Restaurando...' : '↩️ Reactivar Personal' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('users');
const trashedUsers = ref([]);
const trashedCuadrillas = ref([]); 
const trashedObreros = ref([]); // 🔥 NUEVO: Dataset para el personal de campo

const loadingUsers = ref(false);
const loadingCuadrillas = ref(false); 
const loadingObreros = ref(false); // 🔥 NUEVO: Estado de carga para obreros
const processingId = ref(null);

const token = localStorage.getItem('token');
const config = {
  headers: { 'Authorization': `Bearer ${token}` }
};

// --- LOGICA DE USUARIOS ---
const fetchTrashedUsers = async () => {
  loadingUsers.value = true;
  try {
    const response = await axios.get('https://sdb-sistema-production.up.railway.app/api/users/trashed', config);
    trashedUsers.value = response.data;
  } catch (error) {
    console.error("Error al cargar papelera de usuarios:", error);
  } finally {
    loadingUsers.value = false;
  }
};

const restoreUser = async (user) => {
  if (!confirm(`¿Estás seguro de que deseas reactivar la cuenta de ${user.nombres}? Reanudará todos sus privilegios.`)) return;
  processingId.value = user.id;
  try {
    const response = await axios.post(`https://sdb-sistema-production.up.railway.app/api/users/${user.id}/restore`, {}, config);
    alert(response.data.message);
    await fetchTrashedUsers();
  } catch (error) {
    console.error("Error al restaurar usuario:", error);
    alert("Ocurrió un error al intentar restaurar la cuenta.");
  } finally {
    processingId.value = null;
  }
};

// --- LÓGICA DE CUADRILLAS ---
const fetchTrashedCuadrillas = async () => {
  loadingCuadrillas.value = true;
  try {
    const response = await axios.get('https://sdb-sistema-production.up.railway.app/api/cuadrillas/trashed', config);
    trashedCuadrillas.value = response.data;
  } catch (error) {
    console.error("Error al cargar papelera de cuadrillas:", error);
  } finally {
    loadingCuadrillas.value = false;
  }
};

const restoreCuadrilla = async (cuadrilla) => {
  if (!confirm(`¿Deseas restaurar la cuadrilla "${cuadrilla.nombre}"? Volverá a estar disponible para asignaciones de baches.`)) return;
  processingId.value = cuadrilla.id;
  try {
    const response = await axios.post(`https://sdb-sistema-production.up.railway.app/api/cuadrillas/${cuadrilla.id}/restore`, {}, config);
    alert(response.data.message);
    await fetchTrashedCuadrillas(); 
  } catch (error) {
    console.error("Error al restaurar cuadrilla:", error);
    alert("No se pudo restaurar la cuadrilla seleccionada.");
  } finally {
    processingId.value = null;
  }
};

// --- 🔥 NUEVA LÓGICA DE OBREROS ---
const fetchTrashedObreros = async () => {
  loadingObreros.value = true;
  try {
    const response = await axios.get('https://sdb-sistema-production.up.railway.app/api/obreros/trashed', config);
    trashedObreros.value = response.data;
  } catch (error) {
    console.error("Error al cargar papelera de obreros:", error);
  } finally {
    loadingObreros.value = false;
  }
};

const restoreObrero = async (obrero) => {
  const nombreObrero = `${obrero.nombres} ${obrero.apellido_paterno}`;
  if (!confirm(`¿Está seguro de reactivar a ${nombreObrero}? Volverá a figurar en las nóminas activas del sistema S.D.B.`)) return;
  
  processingId.value = obrero.id;
  try {
    const response = await axios.post(`https://sdb-sistema-production.up.railway.app/api/obreros/${obrero.id}/restore`, {}, config);
    alert(response.data.message || "Personal reactivado con éxito.");
    await fetchTrashedObreros(); // Refresca el arreglo local de la tabla
  } catch (error) {
    console.error("Error al restaurar obrero:", error);
    alert("Hubo un problema al intentar devolver la alta operativa al obrero.");
  } finally {
    processingId.value = null;
  }
};

// Formateador de fechas legible
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleString('es-BO', { timeZone: 'America/La_Paz' });
};

onMounted(() => {
  fetchTrashedUsers();
  fetchTrashedCuadrillas();
  fetchTrashedObreros(); // 🔥 Carga inicial de obreros inactivos
});
</script>

<style scoped>
.papelera-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.page-header h2 { margin: 0; color: #2c3e50; font-size: 1.6rem; }
.subtitle { color: #7f8c8d; font-size: 0.95rem; margin-top: 5px; }

/* Sistema de Pestañas */
.tabs-container { display: flex; gap: 10px; margin: 25px 0 15px 0; border-bottom: 2px solid #edf2f7; padding-bottom: 2px; }
.tab-btn { padding: 12px 20px; border: none; background: none; font-size: 0.95rem; font-weight: 600; color: #7f8c8d; cursor: pointer; transition: all 0.2s; border-radius: 6px 6px 0 0; }
.tab-btn:hover { color: #2c3e50; background: #f8fafc; }
.tab-active { color: #e74c3c !important; border-bottom: 3px solid #e74c3c; background: #fff5f5; }

/* Tablas y Contenedores */
.tab-content { padding-top: 15px; }
.table-responsive { width: 100%; overflow-x: auto; margin-top: 10px; }
.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem; }
.custom-table th { background: #f8fafc; color: #4a5568; padding: 14px 16px; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
.custom-table td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; color: #2d3748; vertical-align: middle; }
.font-bold { font-weight: 600; }
.text-danger { color: #e74c3c; }

/* Badges */
.badge-ci { background: #ebf8ff; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
.badge-role { background: #f0fff4; color: #38a169; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; }
.badge-spec { background: #f7fafc; color: #4a5568; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0; }
.text-team { color: #3182ce; font-weight: 600; }
.text-muted { color: #a0aec0; font-size: 0.85rem; }
.italic { font-style: italic; }

/* Estados */
.loading-state, .empty-state { text-align: center; padding: 40px; color: #7f8c8d; font-style: italic; background: #fafafa; border-radius: 8px; border: 1px dashed #e2e8f0; }

/* Botones */
.btn-restore { background: #42b983; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.2s; font-size: 0.85rem; }
.btn-restore:hover:not(:disabled) { background: #35495e; }
.btn-restore-cuadrilla { background: #3182ce; } 
.btn-restore-cuadrilla:hover:not(:disabled) { background: #2b6cb0; }
.btn-restore-obrero { background: #dd6b20; } /* Tonalidad naranja para diferenciar las acciones del personal de campo */
.btn-restore-obrero:hover:not(:disabled) { background: #c05621; }
.btn-restore:disabled { opacity: 0.6; cursor: not-allowed; }
.text-center { text-align: center; }
</style>