<template>
  <div class="layout-wrapper">
    <aside class="sidebar">
      <div class="sidebar-header">
        <img src="/logoSDB.png" alt="S.D.B. Logo" class="brand-logo">
      </div>

      <nav class="sidebar-nav">
        <div class="menu-section-tag">Monitoreo</div>

        <router-link
          v-if="userRole !== 'Técnico' && userRole !== 'Técnico de Flota e IoT' && userRole !== 'Jefe de Cuadrilla'&& userRole !== 'Encargado de almacén'"
          to="/dashboard"
          class="nav-item"
          active-class="active"
        >
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-grid-1x2-fill item-icon"></i>
            <span class="menu-text">Panel Central</span>
          </div>
        </router-link>
        
        <router-link 
          v-if="can('mapa_de_baches') || userRole === 'Técnico' || userRole === 'Jefe de Cuadrilla'" 
          to="/mapa" 
          class="nav-item" 
          active-class="active"
        >
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-geo-alt-fill item-icon"></i>
            <span class="menu-text">Mapa de Baches</span>
          </div>
        </router-link>

        <div v-if="can('gestion_usuarios') || can('gestion_roles')" class="menu-section-tag">Seguridad</div>

        <div v-if="can('gestion_usuarios') || can('gestion_roles')" class="nav-dropdown">
          <div 
            @click="toggleUsersMenu" 
            :class="['nav-item dropdown-toggle', isUsersOpen ? 'active-parent' : '']"
          >
            <div class="d-flex align-items-center">
              <i class="bi bi-people-fill item-icon"></i> Gestión de Usuarios
            </div>
            <span class="arrow">{{ isUsersOpen ? '▼' : '▶' }}</span>
          </div>

          <div v-if="isUsersOpen" class="submenu animate__animated animate__fadeIn">
            <router-link v-if="can('gestion_usuarios')" to="/usuarios" class="nav-item sub-link" active-class="active">
              <i class="bi bi-person-badge-fill item-icon-sub"></i> ... Lista de Personal
            </router-link>
            <!-- <router-link v-if="can('gestion_roles')" to="/roles" class="nav-item sub-link" active-class="active">
              <i class="bi bi-shield-lock-fill item-icon-sub"></i> Roles y Permisos
            </router-link> -->
          </div>
        </div>

        <div class="menu-section-tag" v-if="userRole !== 'Técnico de Flota e IoT' && userRole !== 'Analista Vial'">OPERACIONES E INSUMOS</div>

        <router-link
          v-if="userRole !== 'Técnico de Flota e IoT' && userRole !== 'Analista Vial'"
          :to="{ name: 'materiales' }"
          class="nav-item"
          active-class="active"
        >
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-box-seam item-icon"></i>
            <span class="menu-text">Inventario Materiales</span>
          </div>
        </router-link>

        <div v-if="can('gestionar_cuadrillas') || can('gestionar_obreros') || userRole === 'Técnico' || userRole === 'Jefe de Cuadrilla'" class="nav-dropdown">
          <div 
            @click="toggleCampoMenu" 
            :class="['nav-item dropdown-toggle', isCampoOpen ? 'active-parent' : '']"
          >
            <div class="d-flex align-items-center">
              <i class="bi bi-cone-striped item-icon"></i> Operaciones Vial
            </div>
            <span class="arrow">{{ isCampoOpen ? '▼' : '▶' }}</span>
          </div>

          <div v-if="isCampoOpen" class="submenu animate__animated animate__fadeIn">
            <router-link v-if="can('gestionar_cuadrillas') || userRole === 'Técnico'" to="/cuadrillas" class="nav-item sub-link" active-class="active">
              <i class="bi bi-person-workspace item-icon-sub"></i> Gestión de Cuadrillas
            </router-link>

            <!-- 💡 Aquí agregamos el operador OR para habilitar al Jefe de Cuadrilla -->
            <router-link v-if="can('gestionar_obreros') || userRole === 'Técnico' || userRole === 'Jefe de Cuadrilla'" to="/obreros" class="nav-item sub-link" active-class="active">
              <i class="bi bi-tools item-icon-sub"></i> Registro de Obreros
            </router-link>
          </div>
        </div>

        <div class="menu-section-tag">Ingeniería e IoT</div>

        <div v-if="can('gestionar_vehiculos') || can('gestionar_sensores') || userRole === 'Técnico de Flota e IoT'" class="nav-dropdown">
          <div 
            @click="toggleFlotaMenu" 
            :class="['nav-item dropdown-toggle', isFlotaOpen ? 'active-parent' : '']"
          >
            <div class="d-flex align-items-center">
              <i class="bi bi-cpu-fill item-icon"></i> Flota e IoT
            </div>
            <span class="arrow">{{ isFlotaOpen ? '▼' : '▶' }}</span>
          </div>

          <div v-if="isFlotaOpen" class="submenu animate__animated animate__fadeIn">
            <router-link v-if="can('gestionar_vehiculos') || userRole === 'Técnico de Flota e IoT'" to="/vehiculos" class="nav-item sub-link" active-class="active">
              <i class="bi bi-car-front-fill item-icon-sub"></i> Control de Vehículos
            </router-link>
            <router-link v-if="can('gestionar_sensores') || userRole === 'Técnico de Flota e IoT'" to="/sensores" class="nav-item sub-link" active-class="active">
              <i class="bi bi-router-fill item-icon-sub"></i> Inventario de Sensores
            </router-link>
          </div>
        </div>

        <router-link v-if="userRole === 'Administrador'" to="/simulador" class="nav-item simulator-link" active-class="active">
          <div class="d-flex align-items-center">
            <i class="bi bi-lightning-charge-fill item-icon"></i> Laboratorio IoT (Simulador)
          </div>
        </router-link>

        <!-- CORRECCIÓN: Ocultado para el Técnico, exclusivo del Administrador Global -->
        <router-link v-if="userRole === 'Administrador' || userRole === 'Técnico de Flota e IoT'" to="/salud-flota" class="nav-item" active-class="active">
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-heart-pulse-fill item-icon"></i>
            <span class="menu-text">Salud de la Flota</span>
          </div>
        </router-link>

        <router-link 
          v-if="userRole !== 'Técnico de Flota e IoT' && userRole !== 'Encargado de almacén'" 
          to="/baches-historial" 
          class="nav-item" 
          active-class="active"
        >
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-journal-text item-icon"></i>
            <span class="menu-text">Historial de Obras</span>
          </div>
        </router-link>

        <router-link v-if="userRole === 'Administrador' || userRole === 'Técnico de Flota e IoT'" to="/gestion-taller" class="nav-item" active-class="active">
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-wrench item-icon"></i>
            <span class="menu-text">Personal de Taller</span>
          </div>
        </router-link>

        <router-link v-if="userRole === 'Administrador' || userRole === 'Técnico de Flota e IoT'" to="/mantenimientos" class="nav-item" active-class="active">
          <div class="d-flex align-items-center item-menu">
            <i class="bi bi-tools item-icon"></i>
            <span class="menu-text">Gestión Mantenimientos</span>
          </div>
        </router-link>

        <!-- ✅ SECCIÓN DE REPORTES: EXCLUSIVA PARA ADMINISTRADOR Y ANALISTA VIAL -->
        <div v-if="userRole === 'Administrador' || userRole === 'Analista Vial'" class="menu-section-tag">Reportabilidad</div>

        <router-link v-if="userRole === 'Administrador' || userRole === 'Analista Vial'" to="/reportes" class="nav-item" active-class="active">
          <div class="d-flex align-items-center">
            <i class="bi bi-file-earmark-pdf-fill item-icon"></i>
            <span class="menu-text">Reportes y PDF</span>
          </div>
        </router-link>

        <router-link v-if="userRole === 'Administrador'" to="/papelera" class="nav-item admin-trash-link" active-class="active">
          <div class="d-flex align-items-center">
            <i class="bi bi-trash3-fill item-icon"></i> Papelera del Sistema
          </div>
        </router-link>

      </nav>

      <div class="sidebar-footer">
        <div class="user-profile-card" v-if="userName">
          <span class="profile-label">Funcionario Activo</span>
          <div class="profile-name" :title="userName">
            <i class="bi bi-person-circle me-1 text-success"></i> {{ userName }}
          </div>
        </div>
        <button @click="handleLogout" class="btn-logout-modern" :disabled="isLoggingOut">
          {{ isLoggingOut ? 'Cerrando...' : '🔒 Cerrar Sesión' }}
        </button>
      </div>
    </aside>

    <main class="main-content">
      <header class="content-header">
        <p>Bienvenido al Sistema de Gestión Vial | <span class="role-badge">{{ userRole }}</span></p>
      </header>
      <section class="content-body">
        <router-view />
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const isLoggingOut = ref(false);
const isUsersOpen = ref(false);
const isCampoOpen = ref(false);
const isFlotaOpen = ref(false); 

const userRole = ref(localStorage.getItem('role'));
const userName = ref(localStorage.getItem('userName'));
const userPermissions = ref([]);

onMounted(() => {
  const storedPerms = localStorage.getItem('userPermissions');
  if (storedPerms) {
    try {
      userPermissions.value = JSON.parse(storedPerms);
    } catch (e) {
      console.error("Error al parsear permisos", e);
      userPermissions.value = [];
    }
  }
});

const can = (permissionSlug) => {
  if (userRole.value === 'Administrador') return true;
  return userPermissions.value.includes(permissionSlug);
};

const toggleUsersMenu = () => {
  isUsersOpen.value = !isUsersOpen.value;
  if (isUsersOpen.value) {
    isCampoOpen.value = false;
    isFlotaOpen.value = false; 
  }
};

const toggleCampoMenu = () => {
  isCampoOpen.value = !isCampoOpen.value;
  if (isCampoOpen.value) {
    isUsersOpen.value = false;
    isFlotaOpen.value = false; 
  }
};

const toggleFlotaMenu = () => {
  isFlotaOpen.value = !isFlotaOpen.value;
  if (isFlotaOpen.value) {
    isUsersOpen.value = false;
    isCampoOpen.value = false; 
  }
};

const handleLogout = async () => {
  if (!confirm("¿Deseas cerrar sesión?")) return;
  isLoggingOut.value = true;
  try {
    const token = localStorage.getItem('token');
    if (token) {
      await axios.post('http://localhost:8000/api/logout', {}, {
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
    }
  } catch (error) {
    console.error("Error al cerrar sesión:", error);
  } finally {
    localStorage.clear(); 
    isLoggingOut.value = false;
    router.push('/login');
  }
};
</script>

<style scoped>
.layout-wrapper { display: flex; height: 100vh; background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; overflow: hidden; }

.sidebar { 
  width: 275px; 
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); 
  color: #94a3b8; 
  display: flex; 
  flex-direction: column; 
  box-shadow: 4px 0 25px rgba(0,0,0,0.15);
  z-index: 10;
}

.sidebar-header { 
  padding: 22px 15px; 
  text-align: center; 
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(15, 23, 42, 0.4); 
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-logo {
  max-width: 92%;
  height: auto;
  max-height: 115px; 
  object-fit: contain;
  border-radius: 8px;
  transition: all 0.3s ease-in-out; 
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.5)) 
          drop-shadow(0 0 10px rgba(66, 185, 131, 0.6)); 
}

.brand-logo:hover {
  transform: scale(1.03);
  filter: drop-shadow(0 6px 14px rgba(0, 0, 0, 0.6)) 
          drop-shadow(0 0 18px rgba(66, 185, 131, 0.95)); 
}

.sidebar-nav { flex: 1; padding: 15px 0; overflow-y: auto; overflow-x: hidden !important; scrollbar-width: thin; scrollbar-color: #334155 transparent; }
.sidebar-nav::-webkit-scrollbar { width: 5px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.sidebar-nav::-webkit-scrollbar-thumb:hover { background: #475569; }

.menu-section-tag { font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1.5px; padding: 18px 25px 6px 25px; }

.nav-item { display: block; padding: 11px 25px; color: #cbd5e1; text-decoration: none; transition: all 0.25s ease-in-out; cursor: pointer; border-left: 4px solid transparent; }
.nav-item :deep(.item-menu), .nav-item .menu-text { color: #cbd5e1 !important; } 

.item-icon { margin-right: 12px; font-size: 1.1rem; width: 20px; display: inline-block; text-align: center; vertical-align: middle; transition: color 0.2s; }
.item-icon-sub { margin-right: 8px; font-size: 0.95rem; width: 18px; display: inline-block; text-align: center; vertical-align: middle; }

.nav-item:hover { background-color: rgba(255, 255, 255, 0.03); color: #ffffff; transform: translateX(5px); }
.nav-item:hover .item-icon, .nav-item:hover .item-icon-sub { color: #ffffff !important; }
.nav-item:hover :deep(.item-menu), .nav-item:hover .menu-text { color: #ffffff !important; }

.nav-item.active, .active-parent { 
  background: linear-gradient(90deg, rgba(66, 185, 131, 0.1) 0%, transparent 100%); 
  border-left: 4px solid #42b983 !important; 
  font-weight: 600;
}
.nav-item.active .item-icon, .nav-item.active .item-icon-sub, .active-parent .item-icon { color: #42b983 !important; filter: drop-shadow(0 0 4px rgba(66, 185, 131, 0.4)); }
.nav-item.active :deep(.item-menu), .nav-item.active .menu-text, .active-parent { color: #42b983 !important; }

.dropdown-toggle { display: flex; justify-content: space-between; align-items: center; }
.arrow { font-size: 0.65rem; opacity: 0.5; transition: transform 0.2s; color: #94a3b8 !important; }
.active-parent .arrow { opacity: 0.9; color: #42b983 !important; }

.submenu { background-color: rgba(15, 23, 42, 0.4); margin: 2px 0; border-radius: 4px; }
.sub-link { padding-left: 45px !important; font-size: 0.88rem; opacity: 0.9; }

.simulator-link.active {
  background: linear-gradient(90deg, rgba(241, 196, 15, 0.1) 0%, transparent 100%);
  border-left: 4px solid #f1c40f !important;
}
.simulator-link.active .item-icon, .simulator-link.active { color: #f1c40f !important; }

.admin-trash-link:hover, .admin-trash-link.active {
  background: linear-gradient(90deg, rgba(231, 76, 60, 0.1) 0%, transparent 100%);
  border-left: 4px solid #e74c3c !important;
}
.admin-trash-link.active .item-icon, .admin-trash-link.active { color: #e74c3c !important; }

.sidebar-footer { padding: 20px; background: rgba(15, 23, 42, 0.5); border-top: 1px solid rgba(255, 255, 255, 0.04); }
.user-profile-card { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; display: flex; flex-direction: column; gap: 2px; }
.profile-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: #64748b; font-weight: bold; }
.profile-name { font-size: 0.85rem; color: #e2e8f0; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.btn-logout-modern { 
  width: 100%; 
  padding: 10px; 
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); 
  border: none; 
  color: white; 
  border-radius: 6px; 
  font-size: 0.88rem; 
  font-weight: 600; 
  cursor: pointer; 
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.15); 
  transition: all 0.2s ease; 
}
.btn-logout-modern:hover:not(:disabled) { background: linear-gradient(135deg, #ff6b6b 0%, #e74c3c 100%); box-shadow: 0 6px 15px rgba(231, 76, 60, 0.25); transform: translateY(-1px); }
.btn-logout-modern:disabled { background: #475569; color: #94a3b8; cursor: not-allowed; box-shadow: none; }

.main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background-color: #f8fafc; }
.content-header { background: white; padding: 16px 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-bottom: 1px solid #e2e8f0; }
.content-header p { margin: 0; color: #475569; font-size: 0.92rem; font-weight: 500; }
.role-badge { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; margin-left: 4px; display: inline-block; vertical-align: middle; }
.content-body { padding: 30px; flex: 1; }
</style>