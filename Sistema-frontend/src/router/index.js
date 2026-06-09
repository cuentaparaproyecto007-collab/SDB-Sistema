import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../LoginView.vue'
import MainLayout from '../layouts/MainLayout.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },
    {
      path: '/',
      component: MainLayout,
      redirect: '/dashboard', 
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('../views/admin/DashboardView.vue'),
          meta: { requiresAuth: true } // Todos los integrantes autenticados ven el inicio
        },
        {
          path: 'mapa',
          name: 'mapa',
          component: () => import('../views/MapaView.vue'),
          meta: { requiresAuth: true }
        },

        // --- GRUPO GESTIÓN DE CAMPO (OPERACIONES VIAL) ---
        {
          path: 'obreros', 
          name: 'obreros-registro',
          component: () => import('../views/admin/RegistroObrerosView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Jefe de Cuadrilla'] }
        },
        {
          path: 'cuadrillas',
          name: 'cuadrillas-gestion',
          component: () => import('../views/admin/GestionCuadrillasView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Jefe de Cuadrilla'] }
        },
        // -------------------------------------------------------

        // --- GRUPO TELEMETRÍA E HARDWARE IOT ---
        {
          path: 'vehiculos',
          name: 'vehiculos-gestion',
          component: () => import('../views/admin/GestionVehiculosView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT'] }
        },
        {
          path: 'sensores',
          name: 'sensores-gestion',
          component: () => import('../views/admin/GestionSensoresView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT'] }
        },
        // --------------------------------------------------

        // --- GRUPO MANTENIMIENTO Y SOPORTE DE TALLER ---
        {
          path: 'gestion-taller',
          name: 'admin.taller', 
          component: () => import('../views/admin/GestionTallerView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT'] }
        },
        {
          path: '/mantenimientos',
          name: 'admin.mantenimientos',
          component: () => import('../views/admin/MantenimientosView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT'] }
        },
        // --------------------------------------------------

        // --- RUTA RAÍZ INDEPENDIENTE PARA EL LABORATORIO SIMULADOR IOT ---
        {
          path: 'simulador',
          name: 'laboratorio-simulador',
          component: () => import('../views/admin/SimuladorView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT'] }
        },
        // --------------------------------------------------------------------------

        // --- GRUPO GESTIÓN DE USUARIOS (SUBMENÚ ADMIN) ---
        {
          path: 'usuarios', 
          name: 'usuarios-lista',
          component: () => import('../views/admin/GestionUsuarios.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador'] }
        },
        {
          path: 'roles', 
          name: 'usuarios-roles',
          component: () => import('../views/admin/GestionRoles.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador'] }
        },
        // -------------------------------------------

        // --- GESTIÓN PAPELERA CENTRALIZADA DEL SISTEMA ---
        {
          path: 'papelera',
          name: 'admin-papelera',
          component: () => import('../views/admin/PapeleraView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador'] }
        },
        // ------------------------------------------------------------

        {
          path: 'dispositivos',
          name: 'dispositivos',
          component: () => import('../views/DispositivosView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'reportes',
          name: 'reportes',
          component: () => import('../views/admin/ReportesView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Analista Vial'] }
        },
        {
          path: '/materiales',
          name: 'materiales',
          component: () => import('../views/admin/MaterialesView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Encargado de almacén', 'Técnico', 'Jefe de Cuadrilla'] }
        },
        {
          path: '/salud-flota',
          name: 'salud-flota',
          component: () => import('../views/admin/SaludFlotaView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Técnico', 'Técnico de Flota e IoT', 'Analista Vial'] }
        },
        {
          path: '/baches-historial',
          name: 'baches-historial',
          component: () => import('../views/admin/HistorialView.vue'),
          meta: { requiresAuth: true }
        },
        {
          path: 'reportes',
          name: 'reportes',
          component: () => import('../views/admin/ReportesView.vue'),
          meta: { requiresAuth: true, rolesPermitidos: ['Administrador', 'Analista Vial'] }
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/login'
    }
  ]
})

// ==========================================================================
// 🛡️ GUARDIÁN DE NAVEGACIÓN GLOBAL (Verificación de Token y Roles Reales)
// ==========================================================================
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const userRole = localStorage.getItem('role'); // Lee: 'Administrador', 'Encargado de almacén', etc.

  // 1. Si la ruta requiere autenticación y no hay token, se va directo al Login
  if (to.meta.requiresAuth && !token) {
    return next('/login');
  }

  // 2. Si la ruta restringe roles y el rol del usuario actual no está en la lista de permitidos
  if (to.meta.rolesPermitidos && !to.meta.rolesPermitidos.includes(userRole)) {
    alert(`Acceso denegado. Tu rol de [${userRole || 'Sin Rol'}] no tiene autorización para ingresar a este módulo.`);
    return next('/dashboard'); // Redirección automática al inicio común seguro
  }

  next();
});

export default router