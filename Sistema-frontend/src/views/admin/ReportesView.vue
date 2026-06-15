<template>
  <div class="reportes-container">
    <div class="header-box no-print">
      <h2>📊 Generador de Reportes e Inteligencia Vial</h2>
      <p>Módulo de auditoría corporativa y extracción de datos consolidados mediante consultas relacionales.</p>
    </div>

    <div class="filters-card no-print">
      <div class="filter-row">
        <div class="filter-group-main">
          <label>📁 Tipo de Reporte Gerencial:</label>
          <select v-model="tipoReporte" @change="cargarReporte" class="form-control">
            <option value="criticidad">1. Criticidad y Análisis Geográfico (Baches + Zonas)</option>
            <option value="cuadrillas">2. Rendimiento Operativo de Cuadrillas</option>
            <option value="almacen">3. Logística de Almacén y Consumo de Insumos</option>
            <option value="telemetria">4. Telemetría e Impactos de la Flota (IoT)</option>
            <option v-if="userRole === 'Administrador'" value="5">5. Auditoría de Usuarios y Roles de Seguridad</option>
          </select>
        </div>

        <div class="filter-group" v-if="tipoReporte !== 'usuarios'">
          <label>📅 Desde:</label>
          <input type="date" v-model="fechaInicio" :max="maxFecha" @change="validarFechas" class="form-control">
        </div>

        <div class="filter-group" v-if="tipoReporte !== 'usuarios'">
          <label>📅 Hasta:</label>
          <input type="date" v-model="fechaFin" :max="maxFecha" @change="validarFechas" class="form-control">
        </div>

        <div class="filter-group btn-align">
          <label class="hide-on-mobile">&nbsp;</label>
          <div class="buttons-inline">
            <button @click="cargarReporte" class="btn-buscar" :disabled="cargando">
              <span v-if="cargando">🔄...</span>
              <span v-else>🔍 Buscar</span>
            </button>
            <button @click="limpiarFiltros" class="btn-limpiar" :disabled="cargando">
              🧹 Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="results-card mt-4">
      
      <div class="export-toolbar no-print" v-if="reportData && reportData.length > 0">
        <div class="export-title">📌 Opciones de Exportación Institucional:</div>
        <div class="export-buttons">
          <button @click="exportarPDF" class="btn-export pdf">📄 Exportar a PDF Oficial</button>
          <button @click="exportarExcel" class="btn-export excel">🟢 Exportar a Excel (CSV)</button>
        </div>
      </div>

      <div v-if="cargando" class="state-message no-print">
        <div class="spinner"></div>
        <p>Ejecutando consultas relacionales en PostgreSQL...</p>
      </div>

      <div v-else-if="!reportData || reportData.length === 0" class="state-message empty no-print">
        🍃 No hay registros cargados para este rango de fechas.
      </div>

      <div v-else class="table-responsive" id="printable-table-area">
        
        <div class="print-only-header">
          <h2>GOBIERNO AUTÓNOMO MUNICIPAL</h2>
          <h3>SISTEMA DE DETECCIÓN DE BACHES (S.D.B.)</h3>
          <p class="print-meta">
            <strong>Reporte:</strong> {{ obtenerNombreReporte() }} <br>
            <strong>Filtro Temporal:</strong> {{ tipoReporte !== 'usuarios' ? 'Del ' + formatearFechaSimple(fechaInicio) + ' al ' + formatearFechaSimple(fechaFin) : 'Histórico Completo' }} <br>
            <strong>Generado el:</strong> {{ formatearFecha(new Date()) }}
          </p>
          <hr>
        </div>

        <table class="report-table">
          <thead>
            <tr v-if="tipoReporte === 'criticidad'">
              <th>Macrodistrito / Zona</th>
              <th>Distrito</th>
              <th>Severidad</th>
              <th>Estado Actual</th>
              <th>Profundidad</th>
              <th>Fecha Modificación</th>
            </tr>
            <tr v-if="tipoReporte === 'cuadrillas'">
              <th>Cuadrilla Asignada</th>
              <th>Estado de Obra</th>
              <th>Volumen Inyectado</th>
              <th>Fecha Reparación</th>
            </tr>
            <tr v-if="tipoReporte === 'almacen'">
              <th>Insumo Vial</th>
              <th>Cantidad Consumida</th>
              <th>Stock Actual</th>
              <th>Unidad</th>
              <th>Fecha Consumo</th>
            </tr>
            <tr v-if="tipoReporte === 'telemetria'">
              <th>Placa Vehículo</th>
              <th>Marca / Modelo</th>
              <th>Impacto</th>
              <th>Coordenadas Geográficas</th>
              <th>Fecha Telemetría</th>
            </tr>
            <tr v-if="tipoReporte === 'usuarios'">
              <th>ID</th>
              <th>Funcionario Municipal</th>
              <th>Correo Institucional</th>
              <th>Contacto Celular</th>
              <th>Rol Asignado</th>
              <th>Fecha Registro</th>
            </tr>
          </thead>
          
          <tbody>
            <tr v-for="(row, index) in reportData" :key="index">
              
              <template v-if="tipoReporte === 'criticidad'">
                <td>📍 {{ row.zona }}</td>
                <td>{{ row.distrito }}</td>
                <td><span class="badge" :class="row.severidad">{{ row.severidad }}</span></td>
                <td><span class="status-pill" :class="row.estado">{{ row.estado }}</span></td>
                <td style="font-weight: bold;">{{ row.profundidad_cm ? row.profundidad_cm + ' cm' : '---' }}</td>
                <td class="text-muted">{{ formatearFecha(row.fecha) }}</td>
              </template>

              <template v-if="tipoReporte === 'cuadrillas'">
                <td style="font-weight: bold; color: #2c3e50;">👥 {{ row.cuadrilla }}</td>
                <td><span class="status-pill Reparado">{{ row.estado }}</span></td>
                <td style="font-weight: bold; color: #16a34a;">{{ parseFloat(row.volumen_m3).toFixed(4) }} m³</td>
                <td class="text-muted">{{ formatearFecha(row.fecha_reparacion) }}</td>
              </template>

              <template v-if="tipoReporte === 'almacen'">
                <td style="font-weight: bold;">⚫ {{ row.material }}</td>
                <td style="font-weight: bold; color: #dc2626;">{{ parseFloat(row.volumen_inyectado).toFixed(4) }} m³</td>
                <td><span style="font-weight: bold;">{{ parseFloat(row.stock_actual).toFixed(2) }}</span></td>
                <td><span class="badge-unit">{{ row.unidad_medida }}</span></td>
                <td class="text-muted">{{ formatearFecha(row.fecha_consumo) }}</td>
              </template>

              <template v-if="tipoReporte === 'telemetria'">
                <td style="font-weight: bold; color: #2563eb;">🚗 {{ row.placa }}</td>
                <td>{{ row.marca }} - {{ row.modelo }}</td>
                <td><span class="badge" :class="row.severidad">{{ row.severidad }}</span></td>
                <td style="font-family: monospace; font-size: 0.82rem;">{{ row.latitud }}, {{ row.longitud }}</td>
                <td class="text-muted">{{ formatearFecha(row.fecha_deteccion) }}</td>
              </template>

              <template v-if="tipoReporte === 'usuarios'">
                <td>#{{ row.usuario_id }}</td>
                <td style="font-weight: bold;">👨‍💼 {{ row.nombre_completo }}</td>
                <td style="color: #2563eb;">{{ row.email }}</td>
                <td>{{ row.celular || 'No asignado' }}</td>
                <td><span class="badge-role">{{ row.rol_asignado }}</span></td>
                <td class="text-muted">{{ formatearFecha(row.fecha_registro) }}</td>
              </template>

            </tr>
          </tbody>
        </table>

        <div class="print-only-footer">
          <div class="signature-row">
            <div class="signature-box">
              <hr>
              <p>Firma Encargado de Cuadrilla</p>
            </div>
            <div class="signature-box">
              <hr>
              <p>Firma Director de Infraestructura Vial</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const tipoReporte = ref('criticidad');
const fechaInicio = ref('');
const fechaFin = ref('');
const reportData = ref(null);
const cargando = ref(false);

const maxFecha = new Date().toISOString().split('T')[0];
const token = localStorage.getItem('token');
const config = { headers: { 'Authorization': `Bearer ${token}` } };

const limpiarFiltros = () => {
  tipoReporte.value = 'criticidad';
  establecerFechasPorDefecto();
  cargarReporte();
};

const establecerFechasPorDefecto = () => {
  fechaInicio.value = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
  fechaFin.value = new Date().toISOString().split('T')[0];
};

const validarFechas = () => {
  if (fechaInicio.value > maxFecha) fechaInicio.value = maxFecha;
  if (fechaFin.value > maxFecha) fechaFin.value = maxFecha;
  
  if (fechaInicio.value > fechaFin.value) {
    alert("⚠️ Ajuste automático: La fecha de inicio no puede ser posterior a la fecha de fin.");
    fechaInicio.value = fechaFin.value;
  }
};

const cargarReporte = async () => {
  if (tipoReporte.value !== 'usuarios') validarFechas();
  cargando.value = true;
  reportData.value = null;

  try {
    const url = `https://sdb-sistema-production.up.railway.app/api/reportes-datos?tipo=${tipoReporte.value}&fecha_inicio=${fechaInicio.value}&fecha_fin=${fechaFin.value}`;
    const res = await axios.get(url, config);
    if (res.data.res) {
      reportData.value = res.data.datos;
    }
  } catch (error) {
    console.error("Error crítico al extraer la auditoría relacional:", error);
    alert("Hubo un conflicto al procesar el reporte estructurado.");
  } finally {
    cargando.value = false;
  }
};

const exportarPDF = () => {
  window.print();
};

/**
 * 🟢 EXPORTAR A EXCEL: Mapeo corregido (ID de bache removido de las columnas)
 */
const exportarExcel = () => {
  if (!reportData.value || reportData.value.length === 0) return;

  let headers = [];
  let csvContent = "";

  // Configurar Cabeceras limpias sin ID Bache
  if (tipoReporte.value === 'criticidad') headers = ["Macrodistrito_Zona", "Distrito", "Severidad", "Estado", "Profundidad_cm", "Fecha_Modificacion"];
  if (tipoReporte.value === 'cuadrillas') headers = ["Cuadrilla", "Estado_Obra", "Volumen_Inyectado_m3", "Fecha_Reparacion"];
  if (tipoReporte.value === 'almacen')    headers = ["Insumo_Vial", "Cantidad_Consumida_m3", "Stock_Actual_Almacen", "Unidad", "Fecha_Consumo"];
  if (tipoReporte.value === 'telemetria')  headers = ["Placa_Vehiculo", "Marca_Modelo", "Criticidad_Impacto", "Latitud", "Longitud", "Fecha_Telemetria"];
  if (tipoReporte.value === 'usuarios')   headers = ["ID_Usuario", "Funcionario", "Correo_Institucional", "Celular", "Rol_Asignado", "Fecha_Registro"];

  csvContent += headers.join(";") + "\r\n";

  // Mapear las filas limpias sin ID Bache
  reportData.value.forEach(row => {
    let rowData = [];
    if (tipoReporte.value === 'criticidad') {
      rowData = [row.zona, row.distrito, row.severidad, row.estado, row.profundidad_cm, row.fecha];
    } else if (tipoReporte.value === 'cuadrillas') {
      rowData = [row.cuadrilla, row.estado, row.volumen_m3, row.fecha_reparacion];
    } else if (tipoReporte.value === 'almacen') {
      rowData = [row.material, row.volumen_inyectado, row.stock_actual, row.unidad_medida, row.fecha_consumo];
    } else if (tipoReporte.value === 'telemetria') {
      rowData = [row.placa, `${row.marca} ${row.modelo}`, row.severidad, row.latitud, row.longitud, row.fecha_deteccion];
    } else if (tipoReporte.value === 'usuarios') {
      rowData = [row.usuario_id, row.nombre_completo, row.email, row.celular || 'S/N', row.rol_asignado, row.created_at];
    }
    
    const cleanRow = rowData.map(val => val !== null && val !== undefined ? String(val).replace(/;/g, ' ') : '');
    csvContent += cleanRow.join(";") + "\r\n";
  });

  const blob = new Blob([new Uint8Array([0xEF, 0xBB, 0xBF]), csvContent], { type: "text/csv;charset=utf-8;" });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  
  link.setAttribute("href", url);
  link.setAttribute("download", `Reporte_${tipoReporte.value}_${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

const obtenerNombreReporte = () => {
  if (tipoReporte.value === 'criticidad') return "Criticidad Vial y Análisis Geográfico";
  if (tipoReporte.value === 'cuadrillas') return "Rendimiento Operativo y Productivity de Cuadrillas";
  if (tipoReporte.value === 'almacen') return "Logística de Almacén y Control de Insumos";
  if (tipoReporte.value === 'telemetria') return "Telemetría IoT y Registro de Impactos de la Flota";
  if (tipoReporte.value === 'usuarios') return "Auditoría de Seguridad, Usuarios y Roles";
  return "Reporte General";
};

const formatearFecha = (stringFecha) => {
  if (!stringFecha) return '---';
  return new Date(stringFecha).toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
};

const formatearFechaSimple = (stringFecha) => {
  if (!stringFecha) return '';
  return new Date(stringFecha + 'T00:00:00').toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
};

onMounted(() => {
  establecerFechasPorDefecto();
  cargarReporte();
});
</script>

<style scoped>
.reportes-container { padding: 25px; background-color: #f8fafc; min-height: 90vh; font-family: 'Segoe UI', system-ui, sans-serif; }
.header-box { background: #2c3e50; color: white; padding: 22px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.header-box h2 { margin: 0; font-size: 1.55rem; font-weight: 600; }
.header-box p { margin: 6px 0 0 0; opacity: 0.8; font-size: 0.92rem; }

.filters-card { background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.filter-row { display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end; }

.filter-group-main { flex: 2; min-width: 280px; display: flex; flex-direction: column; gap: 6px; }
.filter-group { flex: 1; min-width: 150px; display: flex; flex-direction: column; gap: 6px; }
.filter-group label, .filter-group-main label { font-size: 0.85rem; font-weight: 600; color: #475569; }

.form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; color: #1e293b; outline: none; background-color: #fff; transition: border 0.2s; }
.form-control:focus { border-color: #3b82f6; }

.btn-align { flex: 1.5; min-width: 240px; display: flex; flex-direction: column; gap: 6px; }
.buttons-inline { display: flex; gap: 10px; width: 100%; }

.btn-buscar { flex: 1; background: #1e293b; color: white; padding: 11px; border: none; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: background 0.2s; }
.btn-buscar:hover { background: #0f172a; }
.btn-buscar:disabled { background: #94a3b8; cursor: not-allowed; }

.btn-limpiar { flex: 1; background: #f1f5f9; color: #475569; padding: 11px; border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; }
.btn-limpiar:hover { background: #e2e8f0; color: #1e293b; border-color: #94a3b8; }

.export-toolbar { display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 12px 18px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
.export-title { font-size: 0.88rem; font-weight: bold; color: #334155; }
.export-buttons { display: flex; gap: 10px; }
.btn-export { padding: 8px 16px; border: none; border-radius: 6px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: transform 0.1s, opacity 0.2s; }
.btn-export:active { transform: scale(0.98); }
.pdf { background: #ef4444; color: white; }
.pdf:hover { background: #dc2626; }
.excel { background: #16a34a; color: white; }
.excel:hover { background: #15803d; }

.results-card { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; min-height: 300px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.state-message { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 280px; color: #64748b; font-size: 0.95rem; text-align: center; gap: 15px; }
.empty { color: #94a3b8; font-style: italic; }

.table-responsive { width: 100%; overflow-x: auto; }
.report-table { width: 100%; border-collapse: collapse; text-align: left; }
.report-table th { background: #f1f5f9; color: #475569; padding: 14px 16px; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #cbd5e1; }
.report-table td { padding: 14px 16px; font-size: 0.88rem; color: #334155; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
.report-table tbody tr:hover { background-color: #f8fafc; }

.badge { padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; display: inline-block; }
.Alta { background: #fdedec; color: #e74c3c; }
.Media { background: #fef9e7; color: #f39c12; }
.Baja { background: #e8f8f5; color: #2ecc71; }

.status-pill { padding: 3px 8px; border-radius: 4px; font-size: 0.78rem; font-weight: 600; text-transform: uppercase; }
.Pendiente { background: #fee2e2; color: #991b1b; }
.Asignado, .En\ proceso { background: #fef9c3; color: #854d0e; }
.Reparado { background: #dcfce7; color: #166534; }

.badge-unit { background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
.badge-role { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.spinner { width: 35px; height: 35px; border: 4px solid #e2e8f0; border-top-color: #1e293b; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.print-only-header, .print-only-footer { display: none; }
@media (max-width: 768px) { .hide-on-mobile { display: none; } }
</style>

<style>
/* 🖨️ REGLAS DE IMPRESIÓN GLOBALES */
@media print {
  nav, aside, header, .no-print, button, .filters-card, .export-toolbar, 
  [class*="sidebar"], [class*="menu"], [class*="navbar"] { 
    display: none !important; 
    width: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
  }

  html, body { 
    background-color: #ffffff !important; 
    margin: 0 !important; 
    padding: 0 !important; 
    width: 100% !important;
  }

  .reportes-container { 
    position: absolute !important; 
    left: 0 !important; 
    top: 0 !important; 
    width: 100% !important; 
    margin: 0 !important; 
    padding: 0 !important; 
    background: #ffffff !important;
  }

  #printable-table-area { 
    width: 100% !important; 
    box-shadow: none !important; 
    border: none !important; 
    padding: 0 !important;
  }

  .print-only-header { 
    display: block !important; 
    margin-bottom: 25px; 
    text-align: center; 
    width: 100%;
  }
  .print-only-header h2 { margin: 0; font-size: 1.35rem; font-weight: bold; color: #1e293b; }
  .print-only-header h3 { margin: 4px 0 10px 0; font-size: 0.95rem; color: #475569; font-weight: 600; }
  
  .print-meta { 
    text-align: left; 
    font-size: 0.82rem; 
    color: #334155; 
    background: #f8fafc !important; 
    padding: 10px; 
    border-radius: 6px; 
    border: 1px solid #cbd5e1 !important;
    line-height: 1.5;
  }

  .report-table { width: 100% !important; border-collapse: collapse !important; }
  .report-table th { background-color: #e2e8f0 !important; color: #1e293b !important; border-bottom: 2px solid #94a3b8 !important; padding: 10px !important; font-size: 0.8rem !important; }
  .report-table td { border-bottom: 1px solid #e2e8f0 !important; padding: 10px !important; font-size: 0.82rem !important; color: #000000 !important; }

  .badge, .status-pill { background: transparent !important; color: #000000 !important; border: 1px solid #cbd5e1 !important; padding: 2px 6px !important; font-size: 0.75rem !important; }

  .print-only-footer { display: block !important; margin-top: 60px; page-break-inside: avoid; }
  .signature-row { display: flex; justify-content: space-between; gap: 50px; margin-top: 40px; }
  .signature-box { flex: 1; text-align: center; font-size: 0.8rem; color: #334155; }
  .signature-box hr { border: 0; border-top: 1px solid #475569; margin-bottom: 5px; }
}
</style>