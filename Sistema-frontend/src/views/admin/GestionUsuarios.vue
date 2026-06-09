<template>
  <div class="container-fluid mt-4 animate__animated animate__fadeIn">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <h2 class="fw-bold text-dark mb-0">👥 Gestión de Personal</h2>
        <p class="text-muted mb-0">Administración de accesos al sistema S.D.B.</p>
      </div>

      <div class="tab-container shadow-sm">
        <button 
          @click="currentTab = 'lista'" 
          :class="['tab-btn', currentTab === 'lista' ? 'active-tab' : '']">
          📋 Lista de Personal
        </button>
        <button 
          @click="currentTab = 'registro'" 
          :class="['tab-btn', currentTab === 'registro' ? 'active-tab-success' : '']">
          {{ isEditing ? '✏️ Modo Edición' : '➕ Nuevo Registro' }}
        </button>
      </div>
    </div>

    <div v-if="currentTab === 'lista'" class="animate__animated animate__fadeIn">
        <div class="d-flex gap-3 mb-3 align-items-center flex-wrap flex-md-nowrap">
          <div class="search-bar shadow-sm flex-grow-1 mb-0">
            <input v-model="searchQuery" type="text" class="form-control border-0 ps-4" placeholder="🔍 Buscar por nombre, C.I. o correo...">
          </div>
          <div class="export-buttons">
            <button 
              @click="exportarExcel" 
              class="btn-excel" 
              :disabled="filteredUsers.length === 0"
              title="Exportar a Excel"
            >
              🟢 Excel
            </button>
            <button 
              @click="exportarPDF" 
              class="btn-pdf" 
              :disabled="filteredUsers.length === 0"
              title="Generar Reporte PDF Oficial"
            >
              🔴 PDF
            </button>
          </div>
        </div>

        <div class="card shadow shadow-soft border-0 overflow-hidden">
          <div class="table-responsive">
            <table class="table align-middle mb-0 custom-table">
              <thead>
                <tr>
                  <th class="ps-4">PERSONAL / DIRECCIÓN</th>
                  <th>C.I.</th>
                  <th>ROL ASIGNADO</th>
                  <th>CONTACTO</th> <th class="text-center pe-4">ACCIONES</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredUsers" :key="user.id" class="user-row">
                  <td class="ps-4">
                    <div class="fw-bold text-dark">{{ user.name }}</div>
                    <small class="text-muted d-block">{{ user.direccion || 'Sin dirección registrada' }}</small>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ user.ci }}</span>
                  </td>
                  <td>
                    <span :class="['role-badge', user.role?.nombre === 'Administrador' ? 'badge-admin' : 'badge-tech']">
                      {{ user.role?.nombre || 'Técnico' }}
                    </span>
                  </td>
                  
                  <td>
                    <div class="small fw-bold text-dark">{{ user.email }}</div>
                    <a v-if="user.celular" 
                      :href="'https://wa.me/591' + user.celular" 
                      target="_blank" 
                      class="whatsapp-btn mt-1 d-inline-flex align-items-center gap-1 text-decoration-none shadow-sm"
                      title="Enviar mensaje por WhatsApp">
                      <span class="badge rounded-pill bg-success">
                        🟢 WhatsApp: {{ user.celular }}
                      </span>
                    </a>
                    <small v-else class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                      🚫 Sin celular registrado
                    </small>
                  </td>

                  <td class="text-center pe-4">
                    <div class="action-group d-flex justify-content-center gap-2">
                      <button @click="handleEdit(user)" class="btn-action edit" title="Editar">
                        ✏️ <span>Editar</span>
                      </button>
                      <button @click="deleteUser(user.id)" class="btn-action delete" title="Borrar" :disabled="user.id === currentUserId">
                        🗑️ <span>Borrar</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="filteredUsers.length === 0">
                  <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros.</td>
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
            {{ isEditing ? '✏️ Actualizar Información de Personal' : '📝 Registro de Nuevo Integrante' }}
          </h4>

          <form @submit.prevent="saveUser" autocomplete="off">
            <div class="row g-4">
              
              <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">NOMBRES</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">👤</span>
                  <input v-model="form.nombres" type="text" class="form-control border-start-0 ps-0" 
                    placeholder="Ej. Juan" required autocomplete="off">
                </div>
              </div>
              
              <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">APELLIDO PATERNO</label>
                <input v-model="form.apellido_paterno" type="text" class="form-control custom-input-plain" 
                  placeholder="Ej. Perez" required autocomplete="off">
              </div>
              
              <div class="col-md-4">
                <label class="form-label fw-bold small text-secondary">APELLIDO MATERNO</label>
                <input v-model="form.apellido_materno" type="text" class="form-control custom-input-plain" 
                  placeholder="Ej. Quispe" required autocomplete="off">
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">CARNET DE IDENTIDAD (C.I.)</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">🆔</span>
                  <input v-model="form.ci" type="text" class="form-control border-start-0 ps-0" 
                    placeholder="Ej. 1234567 LP" required autocomplete="off">
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">CORREO INSTITUCIONAL</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">📧</span>
                  <input v-model="form.email" type="email" class="form-control border-start-0 ps-0" 
                    placeholder="correo@unifranz.edu.bo" required autocomplete="off">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">NÚMERO DE CELULAR</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">📱</span>
                  <input 
                    v-model="form.celular" 
                    @input="validatePhone" 
                    type="text" 
                    class="form-control border-start-0 ps-0" 
                    placeholder="Ej. 70012345" 
                    maxlength="8" 
                    autocomplete="off"
                    required
                  >
                </div>
              </div>

              <div class="col-md-12">
                <label class="form-label fw-bold small text-secondary">DIRECCIÓN DE DOMICILIO</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">🏠</span>
                  <input v-model="form.direccion" type="text" class="form-control border-start-0 ps-0" 
                    placeholder="Zona, Calle, Nro de casa" autocomplete="off">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">
                  {{ isEditing ? 'NUEVA CONTRASEÑA (OPCIONAL)' : 'CONTRASEÑA TEMPORAL' }}
                </label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">🔒</span>
                  <input v-model="form.password" type="password" class="form-control border-start-0 ps-0" 
                    :placeholder="isEditing ? 'Dejar vacío para mantener' : 'Mínimo 8 caracteres'" 
                    :required="!isEditing" autocomplete="new-password">
                </div>
              </div>
              
              <div class="col-md-6">
                <label class="form-label fw-bold small text-secondary">ROL EN EL SISTEMA</label>
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted">🛡️</span>
                  <select v-model="form.role_id" class="form-select border-start-0 ps-0" required>
                    <option value="" disabled>Seleccione un rol...</option>
                    <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.nombre }}</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="mt-5 d-flex gap-3 justify-content-end">
              <button type="button" class="btn btn-cancel shadow-sm" @click="cancelEdit">Cancelar</button>
              <button type="submit" class="btn btn-save shadow-sm" :disabled="loading">
                {{ loading ? '⏳ Guardando...' : (isEditing ? 'Actualizar Usuario' : 'Registrar Personal') }}
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
const searchQuery = ref('');
const users = ref([]);
const roles = ref([]);
const loading = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);
const currentUserId = ref(null);

const form = ref({
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  ci: '',
  direccion: '',
  celular: '',
  email: '',
  password: '',
  role_id: ''
});

const resetForm = () => {
  form.value = { nombres: '', apellido_paterno: '', apellido_materno: '', ci: '', direccion: '', celular: '', email: '', password: '', role_id: '' };
  isEditing.value = false;
  editingUserId.value = null;
};

watch(currentTab, (newTab) => { if (newTab === 'lista') resetForm(); });

const filteredUsers = computed(() => {
  if (!searchQuery.value) return users.value;
  const query = searchQuery.value.toLowerCase();
  return users.value.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.ci.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query)
  );
});

const getHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

const fetchData = async () => {
  try {
    const [u, r] = await Promise.all([
      axios.get('http://localhost:8000/api/users', getHeaders()),
      axios.get('http://localhost:8000/api/roles', getHeaders())
    ]);
    users.value = u.data;
    roles.value = r.data;
    const userData = localStorage.getItem('user');
    if (userData) currentUserId.value = JSON.parse(userData).id;
  } catch (e) { console.error(e); }
};

// 🔥 NUEVO: EXPORTAR CUENTAS DE USUARIO A EXCEL (CSV NATIVO UTF-8 LATAM)
const exportarExcel = () => {
  let csvContent = "\uFEFF"; 
  csvContent += "ID;Nombre Completo;Carnet de Identidad;Rol Asignado;Correo Institucional;Celular;Dirección de Domicilio\n";

  filteredUsers.value.forEach(u => {
    const rol = u.role?.nombre || 'Técnico';
    const direccionLimpia = (u.direccion || 'Sin dirección').replace(/[\r\n]+/g, " ");
    csvContent += `${u.id};${u.name};${u.ci};${rol};${u.email};${u.celular || 'Sin Registro'};${direccionLimpia}\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  const fecha = new Date().toISOString().slice(0, 10);

  link.setAttribute("href", url);
  link.setAttribute("download", `SDB_Reporte_Usuarios_${fecha}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// 🔥 NUEVO: EXPORTAR CUENTAS DE USUARIO A DOCUMENTO PDF INSTITUTIONAL
const exportarPDF = () => {
  const ventanaPrint = window.open('', '_blank');
  const fechaHoy = new Date().toLocaleString('es-BO', { timeZone: 'America/La_ Paz' });
  
  let filasHtml = '';
  filteredUsers.value.forEach(u => {
    const rol = u.role?.nombre || 'Técnico';
    filasHtml += `
      <tr>
        <td style="color: #718096; font-family: monospace;">#${u.id}</td>
        <td style="font-weight: bold; color: #2d3748;">${u.name}</td>
        <td><span style="background: #f8fafc; padding: 2px 6px; border: 1px solid #e2e8f0; border-radius: 4px;">${u.ci}</span></td>
        <td><b>${rol}</b></td>
        <td style="color: #4a5568;">${u.email}</td>
        <td>${u.celular || '<i>Sin celular</i>'}</td>
      </tr>
    `;
  });

  ventanaPrint.document.write(`
    <html>
      <head>
        <title>SDB - Cuentas de Acceso y Personal</title>
        <style>
          body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2d3748; margin: 40px; line-height: 1.4; }
          .header-table { width: 100%; border-collapse: collapse; border-bottom: 3px solid #212529; padding-bottom: 12px; margin-bottom: 25px; }
          .title-system { font-size: 1.6rem; font-weight: 800; color: #2c3e50; letter-spacing: -0.5px; }
          .subtitle-report { font-size: 1rem; color: #718096; text-transform: uppercase; margin-top: 4px; font-weight: 600; }
          .meta-info { font-size: 0.82rem; color: #a0aec0; text-align: right; vertical-align: bottom; }
          .summary-box { background: #f8fafc; border-left: 4px solid #198754; padding: 12px 18px; border-radius: 0 6px 6px 0; margin-bottom: 25px; font-size: 0.9rem; }
          .main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
          .main-table th { background: #212529; color: #ffffff; padding: 12px 14px; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; text-align: left; }
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
              <div class="subtitle-report">Reporte de Credenciales y Personal de Planta</div>
            </td>
            <td class="meta-info">
              <div>Generado el: <b>${fechaHoy}</b></div>
              <div>Filtro activo: <b>${searchQuery.value || 'Ninguno (Todos)'}</b></div>
            </td>
          </tr>
        </table>
        
        <div class="summary-box">
          Este reporte contiene la nómina oficial de personal y perfiles con accesos autorizados al motor del Sistema S.D.B. Actualmente se detallan <b>${filteredUsers.value.length} credenciales habilitadas</b> según las directrices seleccionadas.
        </div>

        <table class="main-table">
          <thead>
            <tr>
              <th style="width: 10%;">ID</th>
              <th style="width: 30%;">Nombre Completo</th>
              <th style="width: 15%;">C.I. / R.U.</th>
              <th style="width: 15%;">Rol Asignado</th>
              <th style="width: 20%;">Correo Electrónico</th>
              <th style="width: 10%;">Contacto</th>
            </tr>
          </thead>
          <tbody>
            ${filasHtml}
          </tbody>
        </table>

        <div class="footer-note">
          © Sistema S.D.B. - Control de Infraestructura y Seguridad Vial. Documento de Uso Administrativo e Interno.
        </div>
      </body>
    </html>
  `);

  ventanaPrint.document.close();
  setTimeout(() => { ventanaPrint.print(); }, 350);
};

const handleEdit = (user) => {
  isEditing.value = true;
  editingUserId.value = user.id;
  form.value = { 
    nombres: user.nombres, 
    apellido_paterno: user.apellido_paterno, 
    apellido_materno: user.apellido_materno,
    ci: user.ci,
    direccion: user.direccion,
    celular: user.celular,
    email: user.email, 
    role_id: user.role_id, 
    password: '' 
  };
  currentTab.value = 'registro';
};

const cancelEdit = () => { resetForm(); currentTab.value = 'lista'; };

const saveUser = async () => {
  loading.value = true;
  const url = isEditing.value ? `http://localhost:8000/api/users/${editingUserId.value}` : 'http://localhost:8000/api/users';
  const method = isEditing.value ? 'put' : 'post';

  try {
    const response = await axios[method](url, form.value, getHeaders());
    alert("✅ " + (response.data.message || "Operación exitosa"));
    await fetchData();
    cancelEdit();
  } catch (e) {
    alert("❌ " + (e.response?.data?.message || "Error en el servidor"));
  } finally { loading.value = false; }
};

const deleteUser = async (id) => {
  if (id === currentUserId.value) return alert("🚫 No puedes eliminarte a ti mismo.");
  if (!confirm("¿Deseas eliminar a este usuario permanentemente?")) return;
  try {
    await axios.delete(`http://localhost:8000/api/users/${id}`, getHeaders());
    users.value = users.value.filter(u => u.id !== id);
  } catch (e) { alert("Error al eliminar"); }
};

const validatePhone = (e) => {
  form.value.celular = e.target.value.replace(/\D/g, '');
};

onMounted(fetchData);
</script>

<style scoped>
.card { border-radius: 15px; border: none; }
.tab-container { display: flex; background: #fff; padding: 5px; border-radius: 12px; border: 1px solid #e0e0e0; }
.tab-btn { border: none; background: transparent; padding: 10px 24px; border-radius: 10px; font-weight: 600; color: #6c757d; transition: 0.3s; }
.active-tab { background: #212529; color: white; }
.active-tab-success { background: #198754; color: white; }

/* 🔥 NUEVOS ESTILOS PARA LA BARRA DE EXPORTACIONES SIMÉTRICA */
.export-buttons { display: flex; gap: 8px; }
.btn-excel { background: #217346; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-weight: bold; font-size: 0.9rem; cursor: pointer; transition: 0.2s; }
.btn-excel:hover:not(:disabled) { background: #154c2e; transform: translateY(-1px); }
.btn-pdf { background: #e74c3c; color: white; border: none; padding: 10px 18px; border-radius: 10px; font-weight: bold; font-size: 0.9rem; cursor: pointer; transition: 0.2s; }
.btn-pdf:hover:not(:disabled) { background: #c0392b; transform: translateY(-1px); }
.btn-excel:disabled, .btn-pdf:disabled { background: #cbd5e0; color: #718096; cursor: not-allowed; transform: none; box-shadow: none; }

.input-group { border-radius: 10px; overflow: hidden; transition: 0.2s; border: 1px solid #dee2e6; }
.input-group:focus-within { border-color: #198754; box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.15); }
.input-group-text { border: none; font-size: 1.1rem; }
.form-control, .form-select { border: none; background-color: #f8f9fa; padding: 12px; font-size: 0.95rem; }
.form-control:focus, .form-select:focus { box-shadow: none; background-color: #fff; }
.custom-input-plain { border-radius: 10px; border: 1px solid #dee2e6; background-color: #f8f9fa; padding: 12px; }

.custom-table thead th { background-color: #f8f9fa; font-size: 0.75rem; text-transform: uppercase; padding: 18px; }
.user-row { border-bottom: 8px solid #f8f9fa; background: white; transition: 0.2s; }
.user-row:hover { background-color: #f1f8f5; transform: scale(1.002); }

.role-badge { padding: 6px 14px; border-radius: 30px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
.badge-admin { background: #e7f1ff; color: #0d6efd; }
.badge-tech { background: #fff4e5; color: #fd7e14; }

.btn-save { background: #198754; color: white; border: none; border-radius: 12px; font-weight: bold; padding: 14px 40px; }
.btn-cancel { background: #f8f9fa; border: 1px solid #ddd; border-radius: 12px; padding: 14px 40px; color: #6c757d; }
.btn-action { border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; }
.edit { background: #eef2ff; color: #4f46e5; }
.delete { background: #fff1f2; color: #e11d48; }

.whatsapp-btn { transition: transform 0.2s ease, opacity 0.2s ease; }
.whatsapp-btn:hover { transform: translateY(-1px); opacity: 0.9; }
.whatsapp-btn .badge { font-size: 0.7rem; padding: 5px 10px; font-weight: 600; border: 1px solid rgba(255, 255, 255, 0.2); }
</style>