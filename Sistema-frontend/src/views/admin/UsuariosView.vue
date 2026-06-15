<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

// 1. Datos del formulario
const form = ref({ 
  name: '', 
  email: '', 
  password: '', 
  role_id: '' 
});

const usuarios = ref([]);
const cargando = ref(false);

// Función auxiliar para obtener el token (mantiene el código limpio)
const getAuthHeaders = () => {
  const token = localStorage.getItem('token');
  return {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  };
};

// 2. Función para guardar (Envío a la API de Laravel)
const guardarUsuario = async () => {
  if (!form.value.role_id) {
    alert("Por favor, selecciona un rol.");
    return;
  }
  
  cargando.value = true;
  try {
    // IMPORTANTE: Pasamos los headers como tercer argumento
    await axios.post(
      'https://sdb-sistema-production.up.railway.app/api/users', 
      form.value, 
      getAuthHeaders()
    );
    
    alert('✅ Personal registrado con éxito en el sistema S.D.B.');
    
    // Limpiar formulario manualmente tras el éxito
    form.value = { name: '', email: '', password: '', role_id: '' };
    obtenerUsuarios();
  } catch (error) {
    console.error(error);
    if (error.response && error.response.status === 401) {
        alert('❌ Sesión expirada. Por favor, vuelve a iniciar sesión.');
    } else {
        alert('❌ Error: El correo ya existe o hay un problema con el servidor.');
    }
  } finally {
    cargando.value = false;
  }
};

// 3. Función para listar usuarios (Conexión al backend)
const obtenerUsuarios = async () => {
  try {
    // IMPORTANTE: Pasamos los headers como segundo argumento en GET
    const res = await axios.get('https://sdb-sistema-production.up.railway.app/api/users', getAuthHeaders());
    usuarios.value = res.data;
  } catch (error) {
    console.error("Error al cargar la lista de usuarios:", error);
    if (error.response && error.response.status === 401) {
        console.warn("No autorizado. Redirigiendo o verificando token...");
    }
  }
};

onMounted(obtenerUsuarios);
</script>

<template>
  <div class="gestion-container">
    <div class="header-section">
      <h2><i class="bi bi-people-fill"></i> Gestión de Usuarios</h2>
      <p>Panel de control administrativo para el registro de técnicos y personal.</p>
    </div>

    <div class="main-content">
      <div class="form-card">
        <h3>Registrar Nuevo Usuario</h3>
        
        <form @submit.prevent="guardarUsuario" autocomplete="off" id="form-new-user">
          
          <input type="text" style="display:none" aria-hidden="true">
          <input type="password" style="display:none" aria-hidden="true">

          <div class="input-group">
            <label>Nombre Completo</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="Nombre y Apellido" 
              required
              autocomplete="off"
            >
          </div>

          <div class="input-group">
            <label>Correo</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="ejemplo@unifranz.edu.bo" 
              required
              autocomplete="new-password" 
            >
          </div>

          <div class="input-group">
            <label>Contraseña</label>
            <input 
              v-model="form.password" 
              type="password" 
              placeholder="Mínimo 8 caracteres" 
              required
              autocomplete="new-password"
            >
          </div>

          <div class="input-group">
            <label>Asignar Rol</label>
            <select v-model="form.role_id" required>
              <option value="" disabled>Seleccionar rol...</option>
              <option value="1">Administrador (Control Total)</option>
              <option value="2">Técnico (Verificación de campo)</option>
              <option value="3">Jefe de Cuadrilla (Reparaciones)</option>
              <option value="4">Analista Vial (Estadísticas/Planificación)</option>
            </select>
          </div>

          <button type="submit" class="btn-save" :disabled="cargando">
            <span v-if="cargando">Procesando...</span>
            <span v-else>Registrar en Base de Datos</span>
          </button>
        </form>
      </div>

      <div class="table-card">
        <h3>Personal Registrado</h3>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Rol</th>
                <th>Email</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in usuarios" :key="user.id">
                <td><strong>{{ user.name }}</strong></td>
                <td>
                  <span :class="user.role_id == 1 ? 'badge admin' : 'badge tecnico'">
                    {{ user.role_id == 1 ? 'Admin' : 'Técnico' }}
                  </span>
                </td>
                <td>{{ user.email }}</td>
                <td><span class="status-active">● Activo</span></td>
              </tr>
              <tr v-if="usuarios.length === 0">
                <td colspan="4" style="text-align: center; color: #999; padding: 20px;">
                  No hay usuarios registrados en la base de datos.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.gestion-container { padding: 20px; color: #333; }
.header-section h2 { margin-bottom: 5px; color: #2c3e50; font-weight: bold; }
.header-section p { color: #7f8c8d; }

.main-content {
  display: grid;
  grid-template-columns: 350px 1fr;
  gap: 25px;
  margin-top: 25px;
}

.form-card, .table-card {
  background: white;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

h3 {
  font-size: 1.1rem;
  margin-bottom: 20px;
  border-bottom: 2px solid #f1f1f1;
  padding-bottom: 12px;
  color: #34495e;
}

.input-group { margin-bottom: 18px; }
.input-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  font-size: 0.85rem;
  color: #555;
}

.input-group input, .input-group select {
  width: 100%;
  padding: 12px;
  border: 1px solid #dcdde1;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.3s;
}

.input-group input:focus {
  outline: none;
  border-color: #27ae60;
}

.btn-save {
  width: 100%;
  padding: 14px;
  background: #27ae60;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s;
  margin-top: 10px;
}

.btn-save:hover:not(:disabled) { background: #219150; }
.btn-save:disabled { background: #95a5a6; cursor: not-allowed; }

.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f1f1; }
th { background: #f8f9fa; color: #7f8c8d; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }

.badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 800;
  text-transform: uppercase;
}

.admin { background: #e3f2fd; color: #1976d2; }
.tecnico { background: #fff3e0; color: #f57c00; }
.status-active { color: #27ae60; font-size: 0.85rem; font-weight: bold; }

@media (max-width: 1100px) {
  .main-content { grid-template-columns: 1fr; }
}
</style>