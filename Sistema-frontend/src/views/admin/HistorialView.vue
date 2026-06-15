<template>
  <div class="history-container">
    <div class="header-box">
      <h2>📜 Historial de Reparaciones Viales</h2>
      <p>Registro auditable de obras concluidas, cubicación de insumos y asignación de personal.</p>
      
      <div class="filter-bar mt-3">
        <div class="filter-group">
          <label>Desde:</label>
          <input type="date" v-model="fechaInicio" :max="maxFecha" class="form-control form-control-sm">
        </div>
        <div class="filter-group">
          <label>Hasta:</label>
          <input type="date" v-model="fechaFin" :max="maxFecha" class="form-control form-control-sm">
        </div>
        
        <div class="action-buttons-group">
          <button @click="cargarHistorial" class="btn-custom btn-search">🔍 Filtrar Historial</button>
          <button @click="limpiarFiltros" class="btn-custom btn-clear">🧹 Limpiar</button>
          <button @click="exportarPDF" class="btn-custom btn-pdf">📄 Exportar PDF</button>
          <button @click="exportarExcel" class="btn-custom btn-excel">🟢 Exportar Excel</button>
        </div>
      </div>
    </div>

    <div class="card shadow-sm table-responsive-box">
      <table class="custom-table">
        <thead>
          <tr>
            <th>Fecha Reparación</th>
            <th>Ubicación / Zona</th>
            <th>Severidad Inicial</th>
            <th>Cuadrilla Ejecutora</th>
            <th>Material Utilizado</th>
            <th>Volumen Inyectado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="obra in historial" :key="obra.id">
            <td class="fw-bold text-secondary">{{ formatearFecha(obra.updated_at) }}</td>
            <td>
              <div class="zone-text">📍 {{ obra.zona || 'Centro' }}</div>
              <small class="text-muted">ID Bache: #{{ obra.id }}</small>
            </td>
            <td>
              <span class="badge-severidad" :class="'badge-' + obra.severidad.toLowerCase()">
                {{ obra.severidad }}
              </span>
            </td>
            <td>
              <div class="crew-box">
                👥 {{ obra.cuadrilla ? obra.cuadrilla.nombre : 'Cuadrilla Externa / General' }}
              </div>
            </td>
            <td>⚫ {{ obra.material ? obra.material.nombre : 'No especificado' }}</td>
            <td class="fw-bold text-dark">
              {{ formatearVolumen(obra.volumen_m3) }} m³
            </td>
          </tr>
          <tr v-if="historial.length === 0">
            <td colspan="6" class="text-center py-4 text-muted font-italic">
              No se encontraron registros de reparaciones para el rango de fechas seleccionado.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const historial = ref([]);
const fechaInicio = ref('');
const fechaFin = ref('');

// 🔥 OBTENER FECHA DE HOY EN FORMATO YYYY-MM-DD PARA BLOQUEAR DÍAS FUTUROS
const obtenerFechaHoy = () => {
  const hoy = new Date();
  const yyyy = hoy.getFullYear();
  const mm = String(hoy.getMonth() + 1).padStart(2, '0');
  const dd = String(hoy.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
};
const maxFecha = ref(obtenerFechaHoy());

const token = localStorage.getItem('token');
const config = { headers: { 'Authorization': `Bearer ${token}` } };

const cargarHistorial = async () => {
  try {
    const params = {
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value
    };
    const res = await axios.get('https://sdb-sistema-production.up.railway.app/api/baches-historial', { ...config, params });
    historial.value = res.data;
  } catch (error) {
    console.error("Error al recuperar el historial vial:", error);
  }
};

// 🔥 NUEVO: Función para descargar PDF aplicando filtros activos
const exportarPDF = async () => {
  try {
    const res = await axios.get('https://sdb-sistema-production.up.railway.app/api/baches-historial-pdf', {
      ...config,
      params: { fecha_inicio: fechaInicio.value, fecha_fin: fechaFin.value },
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_historial_SDB_${obtenerFechaHoy()}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    alert("❌ Error al procesar la exportación a PDF.");
  }
};

// 🔥 NUEVO: Función para descargar Excel aplicando filtros activos
const exportarExcel = async () => {
  try {
    const res = await axios.get('https://sdb-sistema-production.up.railway.app/api/baches-historial-excel', {
      ...config,
      params: { fecha_inicio: fechaInicio.value, fecha_fin: fechaFin.value },
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([res.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `historial_reparaciones_${obtenerFechaHoy()}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    alert("❌ Error al procesar la exportación a Excel.");
  }
};

const formatearVolumen = (valor) => {
  if (!valor || parseFloat(valor) === 0) return '0';
  return parseFloat(parseFloat(valor).toFixed(4));
};

const limpiarFiltros = () => {
  fechaInicio.value = '';
  fechaFin.value = '';
  cargarHistorial();
};

const formatearFecha = (stringFecha) => {
  if (!stringFecha) return '';
  const fecha = new Date(stringFecha);
  return fecha.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(cargarHistorial);
</script>

<style scoped>
.history-container { padding: 25px; font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f8fafc; min-height: 90vh; }
.header-box { background: #2c3e50; color: white; padding: 22px 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.header-box h2 { margin: 0; font-size: 1.6rem; font-weight: 600; }
.header-box p { margin: 5px 0 0 0; opacity: 0.8; font-size: 0.95rem; }

/* BARRA DE FILTROS Y BOTONES */
.filter-bar { display: flex; gap: 15px; align-items: flex-end; background: rgba(255, 255, 255, 0.08); padding: 15px 18px; border-radius: 8px; margin-top: 15px; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 4px; }
.filter-group label { font-size: 0.8rem; font-weight: bold; text-transform: uppercase; color: #cbd5e1; }
.filter-group input { border-radius: 6px; border: 1px solid #cbd5e1; padding: 5px 10px; font-size: 0.9rem; min-width: 140px; outline: none; }

.action-buttons-group { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-custom { border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: all 0.2s ease; font-size: 0.9rem; height: 36px; }

.btn-search { background: #1abc9c; color: white; }
.btn-search:hover { background: #16a085; }
.btn-clear { background: #7f8c8d; color: white; }
.btn-clear:hover { background: #95a5a6; }
.btn-pdf { background: #e74c3c; color: white; }
.btn-pdf:hover { background: #c0392b; transform: translateY(-1px); }
.btn-excel { background: #2ecc71; color: white; }
.btn-excel:hover { background: #27ae60; transform: translateY(-1px); }

/* TABLA PERSONALIZADA */
.table-responsive-box { background: white; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
.custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.95rem; }
.custom-table th { background: #f8fafc; color: #475569; padding: 14px 18px; font-weight: 700; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.5px; }
.custom-table td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
.custom-table tr:hover { background-color: #f8fafc; }

.zone-text { font-weight: 600; color: #1e293b; }
.crew-box { font-weight: 500; color: #2c3e50; }

.badge-severidad { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; display: inline-block; }
.badge-alta { background: #fdedec; color: #e74c3c; }
.badge-media { background: #fef9e7; color: #f39c12; }
.badge-baja { background: #e8f8f5; color: #2ecc71; }
</style>