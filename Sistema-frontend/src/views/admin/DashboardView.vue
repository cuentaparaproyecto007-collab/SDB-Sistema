<template>
  <div class="dashboard-container">
    <div class="welcome-box">
      <h2>📊 Panel de Control Central - S.D.B.</h2>
      <p>Sistema de Analítica Predictiva y Monitoreo Vial en Tiempo Real de la Flota Municipal.</p>
    </div>

    <div class="metrics-grid" v-if="stats">
      <div class="metric-card card-total">
        <div class="card-icon">🚨</div>
        <div class="card-info">
          <h3>{{ stats.contadores.total }}</h3>
          <p>Baches Detectados</p>
        </div>
      </div>
      <div class="metric-card card-pending">
        <div class="card-icon">⏳</div>
        <div class="card-info">
          <h3>{{ stats.contadores.pendientes }}</h3>
          <p>Casos Pendientes</p>
        </div>
      </div>
      <div class="metric-card card-process">
        <div class="card-icon">🛠️</div>
        <div class="card-info">
          <h3>{{ stats.contadores.en_proceso }}</h3>
          <p>En Mantenimiento</p>
        </div>
      </div>
      <div class="metric-card card-fixed">
        <div class="card-icon">✅</div>
        <div class="card-info">
          <h3>{{ stats.contadores.reparados }}</h3>
          <p>Vías Reparadas</p>
        </div>
      </div>
    </div>

    <div class="dashboard-layout mt-4" v-if="stats">
      <div class="panel-box charts-panel">
        <h4>⚠️ Análisis de Criticidad Vial</h4>
        <hr class="divider">
        
        <div class="chart-bar-group">
          <div class="bar-info"><span>Alta Prioridad (Peligro)</span> <b>{{ stats.severidad.alta }} baches</b></div>
          <div class="progress-bg"><div class="progress-bar bar-red" :style="{ width: calcularPorcentaje(stats.severidad.alta) + '%' }"></div></div>
        </div>

        <div class="chart-bar-group">
          <div class="bar-info"><span>Media Prioridad</span> <b>{{ stats.severidad.media }} baches</b></div>
          <div class="progress-bg"><div class="progress-bar bar-orange" :style="{ width: calcularPorcentaje(stats.severidad.media) + '%' }"></div></div>
        </div>

        <div class="chart-bar-group">
          <div class="bar-info"><span>Baja Prioridad</span> <b>{{ stats.severidad.baja }} baches</b></div>
          <div class="progress-bg"><div class="progress-bar bar-green" :style="{ width: calcularPorcentaje(stats.severidad.baja) + '%' }"></div></div>
        </div>

        <div class="efficiency-box mt-4">
          <div class="efficiency-title">🎯 Tasa de Eficiencia de Reparación:</div>
          <div class="efficiency-value">{{ stats.contadores.eficiencia }}%</div>
          <div class="progress-bg"><div class="progress-bar bar-blue" :style="{ width: stats.contadores.eficiencia + '%' }"></div></div>
        </div>
      </div>

      <div class="panel-box alerts-panel">
        <h4>📢 Alertas Críticas de Logística</h4>
        <hr class="divider">
        
        <div v-if="stats.alertas_inventario.length > 0">
          <div class="alert-item" v-for="mat in stats.alertas_inventario" :key="mat.nombre">
            <span class="alert-icon">⚠️</span>
            <div class="alert-text">
              <strong>Stock Crítico: {{ mat.nombre }}</strong>
              <p>Disponible: {{ parseFloat(mat.stock_actual).toFixed(2) }} m³ (Mínimo requerido: {{ mat.stock_minimo }} m³)</p>
            </div>
          </div>
        </div>
        <div v-else class="empty-alerts">
          ✅ Almacén Seguro: Todos los insumos viales superan el límite de reserva estipulado.
        </div>

        <h4 class="mt-4">📍 Incidencias por Zona</h4>
        <hr class="divider">
        <ul class="zone-list">
          <li v-for="z in stats.zonas" :key="z.zona">
            <span>📍 {{ z.zona }}</span>
            <span class="badge-zone">{{ z.total }} Reportes</span>
          </li>
          <li v-if="stats.zonas.length === 0" class="text-muted text-center">No hay reportes espaciales aún.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref(null);
const token = localStorage.getItem('token');
const config = { headers: { 'Authorization': `Bearer ${token}` } };

const cargarDashboard = async () => {
  try {
    const res = await axios.get('https://sdb-sistema-production.up.railway.app/api/dashboard-stats', config);
    
    // 💡 INTERCEPTOR INTERNO: Si el backend reporta un fallo controlado, saltamos el cavernícola
    if (res.data.res === false) {
      alert("🔍 DETALLE ENCONTRADO EN LARAVEL:\n\n" + res.data.error_detectado + "\n\nArchivo: " + res.data.archivo + "\nLínea: " + res.data.linea);
      return;
    }
    
    stats.value = res.data;
  } catch (error) {
    console.error("Error al cargar la analítica del dashboard:", error);
  }
};

const calcularPorcentaje = (valor) => {
  if (!stats.value || stats.value.contadores.total === 0) return 0;
  return (valor / stats.value.contadores.total) * 100;
};

onMounted(cargarDashboard);
</script>

<style scoped>
.dashboard-container { padding: 25px; font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f8fafc; min-height: 90vh; }
.welcome-box { background: #2c3e50; color: white; padding: 22px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.welcome-box h2 { margin: 0; font-size: 1.6rem; font-weight: 600; }
.welcome-box p { margin: 5px 0 0 0; opacity: 0.8; font-size: 0.95rem; }

/* GRID DE TARJETAS MÉTRICAS */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.metric-card { background: white; padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; transition: transform 0.2s; }
.metric-card:hover { transform: translateY(-2px); }
.card-icon { font-size: 2.2rem; }
.card-info h3 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #1e293b; }
.card-info p { margin: 2px 0 0 0; color: #64748b; font-size: 0.88rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.3px; }

/* BORDES LATERALES EN CARDS */
.card-total { border-left: 5px solid #34495e; }
.card-pending { border-left: 5px solid #e74c3c; }
.card-process { border-left: 5px solid #f1c40f; }
.card-fixed { border-left: 5px solid #2ecc71; }

/* DISEÑO LAYOUT DE PANELES */
.dashboard-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
@media (max-width: 850px) { .dashboard-layout { grid-template-columns: 1fr; } }
.panel-box { background: white; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.panel-box h4 { margin: 0 0 10px 0; font-size: 1.1rem; color: #1e293b; font-weight: 600; }
.divider { border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px; }

/* GRÁFICAS DE BARRAS PURE CSS */
.chart-bar-group { margin-bottom: 18px; }
.bar-info { display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 6px; color: #475569; }
.progress-bg { background: #e2e8f0; width: 100%; height: 10px; border-radius: 10px; overflow: hidden; }
.progress-bar { height: 100%; border-radius: 10px; transition: width 0.8s ease-in-out; }
.bar-red { background: #e74c3c; }
.bar-orange { background: #f39c12; }
.bar-green { background: #2ecc71; }
.bar-blue { background: #3498db; }

.efficiency-box { background: #f0fdf4; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; }
.efficiency-title { font-size: 0.9rem; font-weight: bold; color: #166534; }
.efficiency-value { font-size: 2.2rem; font-weight: 800; color: #15803d; margin: 5px 0; }

/* ALERTAS DE INVENTARIO */
.alert-item { display: flex; gap: 12px; background: #fff7ed; border: 1px solid #ffedd5; padding: 12px; border-radius: 8px; margin-bottom: 10px; }
.alert-icon { font-size: 1.2rem; }
.alert-text strong { color: #9a3412; font-size: 0.9rem; }
.alert-text p { margin: 2px 0 0 0; font-size: 0.82rem; color: #c2410c; }
.empty-alerts { background: #f0fdf4; color: #166534; padding: 12px; border-radius: 8px; border: 1px solid #bbf7d0; font-size: 0.88rem; font-weight: 500; text-align: center; }

/* LISTA DE ZONAS */
.zone-list { list-style: none; padding: 0; margin: 0; }
.zone-list li { display: flex; justify-content: space-between; padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.92rem; color: #334155; }
.zone-list li:last-child { border-bottom: none; }
.badge-zone { background: #e2e8f0; color: #475569; font-weight: bold; padding: 2px 8px; border-radius: 12px; font-size: 0.78rem; }
</style>