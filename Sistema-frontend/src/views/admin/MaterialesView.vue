<template>
  <div class="inventory-container">
    <div class="header-box d-flex justify-content-between align-items-center">
      <div>
        <h2 v-if="userRole === 'Técnico' || userRole === 'Jefe de Cuadrilla'">🚚 Monitoreo de Insumos en Tránsito</h2>
        <h2 v-else>📦 Almacén Central de Insumos Viales</h2>
        
        <p v-if="userRole === 'Técnico'">Logística y saldos actuales cargados en los camiones de las cuadrillas.</p>
        <p v-else-if="userRole === 'Jefe de Cuadrilla'">Saldos actuales de insumos viales cargados en el camión de su equipo.</p>
        <p v-else>Control logístico y existencias físicas de la Alcaldía en metros cúbicos (m³)</p>
      </div>
      <div v-if="userRole !== 'Técnico' && userRole !== 'Jefe de Cuadrilla'" class="d-flex gap-2">
        <button @click="mostrarFormDespacho = !mostrarFormDespacho" class="btn-dispatch">
          🚚 Despachar a Cuadrilla
        </button>
        <button @click="mostrarFormNuevo = !mostrarFormNuevo" class="btn-primary">
          ➕ Registrar Nuevo Material
        </button>
      </div>
    </div>

    <div v-if="mostrarFormDespacho" class="form-new-material form-dispatch-border shadow-sm animated fadeIn">
      <div class="form-header">
        <h4>🚚 Entrega de Material y Carga de Camión</h4>
        <p>Asigne la cantidad de insumo en metros cúbicos que saldrá en tránsito con la cuadrilla.</p>
      </div>
      <div class="form-body-grid-dispatch">
        <div class="form-group-custom">
          <label>Seleccionar Cuadrilla de Destino</label>
          <select v-model="formularioDespacho.cuadrilla_id" class="form-select-custom">
            <option value="" disabled>-- Seleccione una Cuadrilla Activa --</option>
            <option v-for="cua in cuadrillas" :key="cua.id" :value="cua.id">
              {{ cua.nombre || `Cuadrilla N° ${cua.id}` }}
            </option>
          </select>
        </div>
        <div class="form-group-custom">
          <label>Insumo Vial a Despachar</label>
          <select v-model="formularioDespacho.material_id" class="form-select-custom">
            <option value="" disabled>-- Seleccione el Insumo --</option>
            <option v-for="mat in materiales" :key="mat.id" :value="mat.id">
              {{ mat.nombre }} (Disp: {{ parseFloat(mat.stock_actual).toFixed(2) }} m³)
            </option>
          </select>
        </div>
        <div class="form-group-custom">
          <label>Volumen a Entregar (m³)</label>
          <input type="number" step="0.01" v-model="formularioDespacho.cantidad" placeholder="0.00">
        </div>
        <div class="form-actions-inline">
          <button @click="ejecutarDespacho" class="btn-save-custom bg-blue-btn">🚀 Confirmar Despacho</button>
        </div>
      </div>
    </div>

    <div v-if="mostrarFormNuevo" class="form-new-material shadow-sm animated fadeIn">
      <div class="form-header">
        <h4>🛠️ Alta de Nuevo Insumo Vial</h4>
        <p>Complete los datos para incorporar stock al inventario general.</p>
      </div>
      <div class="form-body-grid">
        <div class="form-group-custom">
          <label>Nombre del Material</label>
          <input type="text" v-model="nuevoMaterial.nombre" placeholder="Ej. Asfalto Frío Bituminoso">
        </div>
        <div class="form-group-custom">
          <label>Stock Inicial Disponible (m³)</label>
          <input type="number" step="0.01" v-model="nuevoMaterial.stock_actual" placeholder="0.0000">
        </div>
        <div class="form-actions-inline">
          <button @click="crearMaterial" class="btn-save-custom">💾 Registrar Insumo</button>
        </div>
      </div>
    </div>

    <div v-if="userRole === 'Técnico' || userRole === 'Jefe de Cuadrilla' || despachosActivos.length > 0" class="monitoring-box shadow-sm mb-4 animated fadeIn">
      <div class="monitoring-header">
        <h4>{{ userRole === 'Jefe de Cuadrilla' ? '🚚 Estado de Carga de mi Camión (Hoy)' : '🚚 Personal en Campo y Material en Tránsito (Hoy)' }}</h4>
        <p>{{ userRole === 'Jefe de Cuadrilla' ? 'Monitoreo de saldos de asfalto e insumos viales disponibles en ruta.' : 'Saldos actuales cargados en los camiones. El Técnico supervisa el material asignado a cada cuadrilla.' }}</p>
      </div>

      <table v-if="despachosActivos.length > 0" class="table-monitoring">
        <thead>
          <tr>
            <th>Cuadrilla</th>
            <th>Material Asignado</th>
            <th>Despachado Mañana</th>
            <th>Saldo Actual en Camión</th>
            <th v-if="userRole !== 'Técnico' && userRole !== 'Jefe de Cuadrilla'">Acción de Cierre</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="desp in despachosActivos" :key="desp.id">
            <td class="fw-bold text-dark">{{ desp.cuadrilla?.nombre || `Cuadrilla N° ${desp.cuadrilla_id}` }}</td>
            <td><span class="badge-material">⚫ {{ desp.material?.nombre }}</span></td>
            <td>{{ parseFloat(desp.cantidad_despachada).toFixed(2) }} m³</td>
            <td>
              <b class="text-primary-balance">{{ parseFloat(desp.cantidad_actual).toFixed(2) }} m³</b>
            </td>
            <td v-if="userRole !== 'Técnico' && userRole !== 'Jefe de Cuadrilla'">
              <button @click="ejecutarCierreJornada(desp)" class="btn-close-jornada">
                🌙 Cerrar Jornada
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-trucks-alert">
        <i class="bi bi-info-circle-fill"></i>
        <span>No se registran cuadrillas operando con carga de asfalto en tránsito para la jornada de hoy.</span>
      </div>
    </div>

    <div v-if="userRole !== 'Técnico' && userRole !== 'Jefe de Cuadrilla'" class="grid-cards">
      <div v-for="mat in materiales" :key="mat.id" class="mat-card" :class="{ 'low-stock': mat.stock_actual < 20 }">
        <div class="card-icon">
          {{ mat.nombre.includes('Asfalto') ? '⚫' : '🧱' }}
        </div>
        <div class="card-info">
          <h3>{{ mat.nombre }}</h3>
          <p class="stock-display">
            <span>{{ parseFloat(mat.stock_actual).toFixed(2) }}</span> m³
          </p>
          <span class="badge" :class="mat.stock_actual < 20 ? 'badge-danger' : 'badge-success'">
            {{ mat.stock_actual < 20 ? '⚠️ Stock Crítico' : '✅ Stock Óptimo' }}
          </span>
        </div>
        <div class="card-actions-row">
          <button @click="abrirModificar(mat)" class="btn-action-card btn-edit-style">✏️ Editar</button>
          <button @click="eliminarMaterial(mat.id, mat.nombre)" class="btn-action-card btn-delete-style">🗑️ Eliminar</button>
        </div>
      </div>
    </div>

    <div v-if="mostrarModalEditar" class="custom-modal-overlay animated fadeIn">
      <div class="custom-modal-box shadow-lg">
        <div class="modal-header-custom">
          <h3>✏️ Modificar Insumo Vial</h3>
          <p>Ajuste de parámetros y reabastecimiento logístico</p>
        </div>
        <div class="modal-body-custom">
          <div class="form-group-custom mb-3">
            <label>Nombre Comercial del Material</label>
            <input type="text" v-model="materialSeleccionado.nombre">
          </div>
          <div class="form-group-custom mb-3">
            <label>Existencias en Almacén (m³)</label>
            <input type="number" step="0.0001" v-model="materialSeleccionado.stock_actual">
          </div>
        </div>
        <div class="modal-footer-custom">
          <button @click="mostrarModalEditar = false" class="btn-modal-cancel">Cancelar</button>
          <button @click="guardarCambiosMaterial" class="btn-modal-save">💾 Guardar Cambios</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import L from 'leaflet';
import axios from 'axios';

const userRole = ref(localStorage.getItem('role'));

const materiales = ref([]);
const cuadrillas = ref([]); 
const despachosActivos = ref([]); 
const mostrarFormNuevo = ref(false);
const mostrarFormDespacho = ref(false); 
const nuevoMaterial = ref({ nombre: '', stock_actual: '' });

const formularioDespacho = ref({
  cuadrilla_id: '',
  material_id: '',
  cantidad: ''
});

const mostrarModalEditar = ref(false);
const materialSeleccionado = ref({ id: null, nombre: '', stock_actual: 0 });

const token = localStorage.getItem('token');
const config = { headers: { 'Authorization': `Bearer ${token}` } };

const cargarInventario = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/materiales', config);
    materiales.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (error) {
    console.error("Error al conectar con el inventario S.D.B.:", error);
  }
};

const cargarCuadrillas = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/cuadrillas', config);
    cuadrillas.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (error) {
    console.error("Error al recuperar las cuadrillas viales:", error);
  }
};

const cargarDespachosActivos = async () => {
  try {
    const res = await axios.get('http://localhost:8000/api/cuadrillas-material/activos', config);
    despachosActivos.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (error) {
    console.error("Error al cargar monitoreo de camiones:", error);
  }
};

const crearMaterial = async () => {
  if (!nuevoMaterial.value.nombre || nuevoMaterial.value.stock_actual === '') {
    return alert("⚠️ Ingrese un nombre válido y un stock inicial mayor o igual a cero.");
  }
  try {
    await axios.post('http://localhost:8000/api/materiales', nuevoMaterial.value, config);
    alert("🎉 Insumo vial dado de alta en el inventario general.");
    nuevoMaterial.value = { nombre: '', stock_actual: '' };
    mostrarFormNuevo.value = false;
    await cargarInventario();
  } catch (error) {
    alert(error.response?.data?.message || "Error al registrar el material.");
  }
};

const ejecutarDespacho = async () => {
  const d = formularioDespacho.value;
  if (!d.cuadrilla_id || !d.material_id || !d.cantidad || d.cantidad <= 0) {
    return alert("⚠️ Seleccione una cuadrilla, un tipo de material y defina una cantidad válida.");
  }
  try {
    const respuesta = await axios.post('http://localhost:8000/api/cuadrillas-material/despachar', {
      cuadrilla_id: d.cuadrilla_id,
      material_id: d.material_id,
      cantidad: d.cantidad
    }, config);

    alert("🎉 " + respuesta.data.message);
    formularioDespacho.value = { cuadrilla_id: '', material_id: '', cantidad: '' };
    mostrarFormDespacho.value = false;
    
    await cargarInventario();
    await cargarDespachosActivos();
  } catch (error) {
    alert(error.response?.data?.message || "Error al procesar el despacho.");
  }
};

const ejecutarCierreJornada = async (despacho) => {
  const nombreCuadrilla = despacho.cuadrilla?.nombre || `Cuadrilla N° ${despacho.cuadrilla_id}`;
  if (!confirm(`¿Confirmar cierre de jornada para [${nombreCuadrilla}]? El saldo sobrante de ${despacho.cantidad_actual} m³ retornará al inventario central.`)) return;

  try {
    const respuesta = await axios.post('http://localhost:8000/api/cuadrillas-material/cerrar-jornada', {
      cuadrilla_id: despacho.cuadrilla_id,
      material_id: despacho.material_id
    }, config);

    alert("🌙 " + respuesta.data.message);
    
    await cargarInventario();
    await cargarDespachosActivos();
  } catch (error) {
    alert(error.response?.data?.message || "Error al cerrar la jornada.");
  }
};

const abrirModificar = (material) => {
  materialSeleccionado.value = { ...material };
  mostrarModalEditar.value = true;
};

const guardarCambiosMaterial = async () => {
  const m = materialSeleccionado.value;
  if (!m.nombre || m.stock_actual < 0) return alert("Valores no válidos.");
  try {
    await axios.put(`http://localhost:8000/api/materiales/${m.id}`, {
      nombre: m.nombre,
      stock_actual: m.stock_actual
    }, config);
    alert("🎉 Insumo actualizado y auditado con éxito.");
    mostrarModalEditar.value = false;
    await cargarInventario();
  } catch (error) {
    alert(error.response?.data?.message || "Error al actualizar.");
  }
};

const eliminarMaterial = async (id, nombre) => {
  if (!confirm(`¿Está seguro de remover '${nombre}' del inventario? Esta acción será auditada.`)) return;
  try {
    const res = await axios.delete(`http://localhost:8000/api/materiales/${id}`, config);
    alert("🎉 " + res.data.message);
    await cargarInventario();
  } catch (error) {
    alert(error.response?.data?.message || "No se pudo procesar la baja.");
  }
};

onMounted(() => {
  // 💡 ESCUDO PROTECTOR DE ROLES: Si es Jefe de Cuadrilla, evitamos descargar las listas globales
  // administrativas de la alcaldía para saltarnos los rebotes 403 Forbidden.
  if (userRole.value !== 'Jefe de Cuadrilla') {
    cargarInventario();
    cargarCuadrillas();
  }
  cargarDespachosActivos(); 
});
</script>

<style scoped>
.inventory-container { padding: 25px; font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f8fafc; min-height: 90vh; }
.header-box { margin-bottom: 25px; background: #2c3e50; color: white; padding: 20px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
.header-box h2 { margin: 0; font-size: 1.6rem; font-weight: 600; }
.header-box p { margin: 5px 0 0 0; opacity: 0.8; font-size: 0.95rem; }

.btn-primary-custom { background: #1abc9c; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s ease; }
.btn-primary-custom:hover { background: #16a085; transform: translateY(-1px); }

.btn-dispatch-custom { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s ease; }
.btn-dispatch-custom:hover { background: #2980b9; transform: translateY(-1px); }
.form-dispatch-border { border-left: 6px solid #3498db !important; }
.bg-blue-btn { background: #3498db !important; }
.bg-blue-btn:hover { background: #2980b9 !important; }

.form-body-grid-dispatch { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 16px; align-items: flex-end; }
.form-select-custom { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #334155; outline: none; background: #f8fafc; transition: all 0.2s ease; width: 100%; height: 44px; cursor: pointer; }
.form-select-custom:focus { border-color: #3498db; background: white; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15); }

.monitoring-box { background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; border-left: 6px solid #f39c12; }
.monitoring-header h4 { margin: 0; color: #2c3e50; font-size: 1.2rem; font-weight: 600; }
.monitoring-header p { margin: 4px 0 15px 0; font-size: 0.88rem; color: #64748b; }

.table-monitoring { width: 100%; border-collapse: collapse; margin-top: 10px; text-align: left; }
.table-monitoring th { background: #f8fafc; color: #475569; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px; border-bottom: 2px solid #e2e8f0; font-weight: 700; }
.table-monitoring td { padding: 14px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; vertical-align: middle; }
.badge-material { background: #f1f5f9; padding: 6px 12px; border-radius: 6px; font-weight: 600; color: #334155; font-size: 0.85rem; }
.text-primary-balance { color: #2980b9; font-size: 1.05rem; }

.btn-close-jornada { background: #f39c12; color: white; border: none; padding: 8px 14px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.85rem; }
.btn-close-jornada:hover { background: #d35400; transform: translateY(-1px); }

.empty-trucks-alert { background-color: #ffffff; border: 2px dashed #cbd5e1; color: #64748b; padding: 35px; text-align: center; border-radius: 8px; font-weight: 500; display: flex; flex-direction: column; gap: 8px; align-items: center; justify-content: center; margin-top: 10px; }
.empty-trucks-alert i { font-size: 2.2rem; color: #94a3b8; }

.form-new-material { background: white; border-radius: 12px; padding: 24px; margin-bottom: 25px; border: 1px solid #e2e8f0; border-left: 6px solid #1abc9c; }
.form-header h4 { margin: 0; color: #2c3e50; font-size: 1.25rem; font-weight: 600; }
.form-header p { margin: 4px 0 15px 0; font-size: 0.88rem; color: #64748b; }
.form-body-grid { display: grid; grid-template-columns: 2fr 1fr auto; gap: 16px; align-items: flex-end; }

.form-group-custom { display: flex; flex-direction: column; gap: 6px; width: 100%; }
.form-group-custom label { font-size: 0.85rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
.form-group-custom input { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #334155; outline: none; background: #f8fafc; transition: all 0.2s ease; height: 44px; }
.form-group-custom input:focus { border-color: #1abc9c; background: white; box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.15); }

.btn-save-custom { background: #2ecc71; color: white; border: none; padding: 11px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; height: 44px; }
.btn-save-custom:hover { background: #27ae60; }

.grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
.mat-card { background: white; border-radius: 12px; padding: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; border-left: 6px solid #2ecc71; border-top: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; transition: all 0.2s ease; }
.mat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
.mat-card.low-stock { border-left-color: #e74c3c; background: #fffdfd; }

.card-icon { font-size: 2.2rem; margin-bottom: 8px; }
.card-info h3 { margin: 0; color: #2c3e50; font-size: 1.3rem; font-weight: 600; }
.stock-display { font-size: 2.1rem; font-weight: 700; margin: 6px 0; color: #34495e; letter-spacing: -0.5px; }
.stock-display span { color: #2c3e50; }

.badge { padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: bold; display: inline-block; width: max-content; }
.badge-success { background: #e8f8f5; color: #2ecc71; }
.badge-danger { background: #fdedec; color: #e74c3c; }

.card-actions-row { margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; gap: 10px; }
.btn-action-card { flex: 1; padding: 9px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.88rem; text-align: center; }
.btn-edit-style { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.btn-edit-style:hover { background: #e2e8f0; color: #1e293b; }
.btn-delete-style { background: #fee2e2; color: #ef4444; }
.btn-delete-style:hover { background: #fca5a5; }

.custom-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.45); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 3000; }
.custom-modal-box { background: white; padding: 28px; border-radius: 16px; width: 100%; max-width: 460px; border: 1px solid #e2e8f0; }
.modal-header-custom { margin-bottom: 20px; }
.modal-header-custom h3 { margin: 0; color: #1e293b; font-size: 1.4rem; font-weight: 600; }
.modal-header-custom p { margin: 4px 0 0 0; font-size: 0.88rem; color: #64748b; }
.modal-body-custom { padding: 4px 0; }

.modal-footer-custom { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.btn-modal-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
.btn-modal-cancel:hover { background: #e2e8f0; color: #1e293b; }
.btn-modal-save { background: #1abc9c; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
.btn-modal-save:hover { background: #16a085; }

.animated { animation-duration: 0.25s; animation-fill-mode: both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.fadeIn { animation-name: fadeIn; }
</style>