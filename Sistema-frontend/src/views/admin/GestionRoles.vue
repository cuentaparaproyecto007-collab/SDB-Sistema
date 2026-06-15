<template>
  <div class="container-fluid mt-4 animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <h2 class="fw-bold text-dark mb-0">🛡️ Roles y Permisos</h2>
        <p class="text-muted mb-0">Configuración de niveles de acceso para el personal S.D.B.</p>
      </div>

      <div class="tab-container shadow-sm">
        <button 
          @click="currentTab = 'lista'" 
          :class="['tab-btn', currentTab === 'lista' ? 'active-tab' : '']">
          📋 Lista de Roles
        </button>
        <button 
          @click="currentTab = 'registro'" 
          :class="['tab-btn', currentTab === 'registro' ? 'active-tab-success' : '']">
          {{ isEditing ? '✏️ Modo Edición' : '➕ Nuevo Rol' }}
        </button>
      </div>
    </div>

    <div v-if="currentTab === 'lista'" class="animate__animated animate__fadeIn">
      <div class="search-bar mb-3 shadow-sm">
        <input v-model="searchQuery" type="text" class="form-control border-0 ps-4" placeholder="🔍 Buscar rol por nombre...">
      </div>

      <div class="card shadow border-0 overflow-hidden">
        <div class="table-responsive">
          <table class="table align-middle mb-0 custom-table">
            <thead>
              <tr>
                <th class="ps-4">NOMBRE DEL ROL</th>
                <th>DESCRIPCIÓN</th>
                <th>PERMISOS ACTIVOS</th>
                <th class="text-center pe-4">ACCIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="role in filteredRoles" :key="role.id" class="user-row">
                <td class="ps-4">
                  <div class="fw-bold text-dark">{{ role.nombre }}</div>
                </td>
                <td class="small text-muted">{{ role.descripcion }}</td>
                <td>
                  <div class="d-flex flex-wrap gap-1">
                    <span v-for="perm in role.permissions" :key="perm.id" class="badge bg-light text-dark border shadow-sm" style="font-size: 0.6rem; font-weight: 700;">
                      {{ perm.nombre }}
                    </span>
                    <span v-if="!role.permissions?.length" class="text-muted small">Sin accesos</span>
                  </div>
                </td>
                <td class="text-center pe-4">
                  <div class="action-group d-flex justify-content-center gap-2">
                    <button @click="handleEdit(role)" class="btn-action edit">✏️ <span>Editar</span></button>
                    <button @click="deleteRole(role.id)" class="btn-action delete" :disabled="role.id === 1">🗑️ <span>Borrar</span></button>
                  </div>
                </td>
              </tr>
              <tr v-if="roles.length === 0">
                <td colspan="4" class="text-center py-5 text-muted">Cargando roles o lista vacía...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="currentTab === 'registro'" class="animate__animated animate__fadeIn">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-md-5">
          <h4 class="fw-bold text-dark mb-4 border-bottom pb-3">
            {{ isEditing ? '✏️ Actualizar Permisos' : '📝 Registro de Nuevo Rol' }}
          </h4>

          <form @submit.prevent="saveRole">
            <div class="row g-4">
              <div class="col-md-12">
                <label class="form-label fw-bold small text-secondary">NOMBRE DEL ROL</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0">🛡️</span>
                  <input v-model="form.nombre" type="text" class="form-control border-start-0 ps-0" placeholder="Ej. Analista Vial" required>
                </div>
              </div>

              <div class="col-md-12">
                <label class="form-label fw-bold small text-secondary">DESCRIPCIÓN DE FUNCIONES</label>
                <textarea v-model="form.descripcion" class="form-control custom-input-plain" rows="2" placeholder="Describa brevemente las responsabilidades..." required></textarea>
              </div>

              <div class="col-md-12">
                <label class="form-label fw-bold small text-secondary mb-3 d-block">MARQUE LOS MÓDULOS DE ACCESO</label>
                <div class="permissions-matrix">
                  <div v-for="perm in allPermissions" :key="perm.id" class="perm-item shadow-sm">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center w-100 px-2">
                      <label class="form-check-label fw-bold small m-0 text-dark">{{ perm.nombre }}</label>
                      <input class="form-check-input" type="checkbox" :value="perm.id" v-model="form.permisos">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-5 d-flex gap-3 justify-content-end">
              <button type="button" class="btn btn-cancel shadow-sm" @click="cancelEdit">Cancelar</button>
              <button type="submit" class="btn btn-save shadow-sm" :disabled="loading || form.permisos.length === 0">
                {{ loading ? '⏳ Procesando...' : (isEditing ? 'Actualizar Rol' : 'Registrar Rol') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

const currentTab = ref('lista');
const roles = ref([]);
const allPermissions = ref([]);
const loading = ref(false);
const isEditing = ref(false);
const editingRoleId = ref(null);
const searchQuery = ref('');

const form = ref({ id: null, nombre: '', descripcion: '', permisos: [] });

const getHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

const fetchData = async () => {
  try {
    const [r, p] = await Promise.all([
      axios.get('https://sdb-sistema-production.up.railway.app/api/roles-permissions', getHeaders()),
      axios.get('https://sdb-sistema-production.up.railway.app/api/permissions', getHeaders())
    ]);
    roles.value = r.data;
    allPermissions.value = p.data;
  } catch (e) { console.error("Error al conectar con la API:", e); }
};

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value;
  return roles.value.filter(r => r.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const handleEdit = (role) => {
  isEditing.value = true;
  editingRoleId.value = role.id;
  form.value = { 
    id: role.id,
    nombre: role.nombre, 
    descripcion: role.descripcion, 
    permisos: role.permissions.map(p => p.id) 
  };
  currentTab.value = 'registro';
};

const cancelEdit = () => {
  form.value = { id: null, nombre: '', descripcion: '', permisos: [] };
  isEditing.value = false;
  currentTab.value = 'lista';
};

const saveRole = async () => {
  loading.value = true;
  try {
    await axios.post('https://sdb-sistema-production.up.railway.app/api/roles-permissions', form.value, getHeaders());
    await fetchData();
    cancelEdit();
    alert("✅ Operación realizada con éxito");
  } catch (e) { alert("❌ Error al guardar"); }
  finally { loading.value = false; }
};

onMounted(fetchData);
</script>

<style scoped>
/* COPIADO DE GESTIÓN DE PERSONAL PARA IGUALAR EL DISEÑO */
.card { border-radius: 15px; border: none; }
.tab-container { display: flex; background: #fff; padding: 5px; border-radius: 12px; border: 1px solid #e0e0e0; }
.tab-btn { border: none; background: transparent; padding: 10px 24px; border-radius: 10px; font-weight: 600; color: #6c757d; transition: 0.3s; }
.active-tab { background: #2c3e50; color: white; }
.active-tab-success { background: #198754; color: white; }

.input-group { border-radius: 10px; overflow: hidden; border: 1px solid #dee2e6; }
.form-control { border: none; background-color: #f8f9fa; padding: 12px; }
.custom-input-plain { border-radius: 10px; border: 1px solid #dee2e6; background-color: #f8f9fa; padding: 12px; }

.custom-table thead th { background-color: #f8f9fa; font-size: 0.75rem; text-transform: uppercase; padding: 18px; color: #666; }
.user-row { border-bottom: 8px solid #f8f9fa; background: white; }
.user-row:hover { background-color: #f1f8f5; }

.btn-save { background: #198754; color: white; border: none; border-radius: 12px; font-weight: bold; padding: 14px 40px; }
.btn-cancel { background: #f8f9fa; border: 1px solid #ddd; border-radius: 12px; padding: 14px 40px; color: #6c757d; }
.btn-action { border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
.edit { background: #eef2ff; color: #4f46e5; }
.delete { background: #fff1f2; color: #e11d48; }

/* ESTILO PARA LA MATRIZ DE PERMISOS */
.permissions-matrix {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
  background: #f8f9fa;
  padding: 20px;
  border-radius: 15px;
}
.perm-item {
  background: white;
  padding: 12px;
  border-radius: 10px;
  border: 1px solid #eee;
}
.form-check-input { width: 2.8em; height: 1.4em; cursor: pointer; }
.form-check-input:checked { background-color: #198754; border-color: #198754; }
</style>