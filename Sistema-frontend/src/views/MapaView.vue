<template>
  <div class="map-container">
    <div id="map"></div>
    
    <button @click="fetchAndRenderMarkers" class="btn-refresh-map" :disabled="loadingData">
      <span>{{ loadingData ? '⏳ Sincronizando...' : '🔄 Sincronizar Red IoT' }}</span>
    </button>
    
    <div class="map-legend">
      <h4>Severidad del Bache</h4>
      <div><span class="dot red"></span> Alta (Peligro)</div>
      <div><span class="dot orange"></span> Media</div>
      <div><span class="dot green"></span> Baja</div>
      <hr style="margin: 10px 0; border: 0.5px solid #eee;">
      <h4>Estado Actual</h4>
      <div class="small"><span class="status-badge pending"></span> Pendiente</div>
      <div class="small"><span class="status-badge assigned"></span> Asignado</div> 
      <div class="small"><span class="status-badge processing"></span> En proceso</div>
      <div class="small"><span class="status-badge fixed"></span> Reparado</div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';

const userRole = ref(localStorage.getItem('role'));

const baches = ref([]);
const cuadrillas = ref([]); 
const materiales = ref([]); 
const token = localStorage.getItem('token');

const mapInstance = ref(null);
const markersGroup = ref(null);
const loadingData = ref(false);

const createIcon = (color) => new L.Icon({
  iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

const fetchAndRenderMarkers = async () => {
  if (!mapInstance.value || !markersGroup.value) return;
  
  loadingData.value = true;
  try {
    const config = { headers: { 'Authorization': `Bearer ${token}` } };
    
    if (userRole.value === 'Jefe de Cuadrilla') {
      const resBaches = await axios.get('https://sdb-sistema-production.up.railway.app/api/baches', config);
      // 💡 CORREGIDO: Ahora el jefe solo ve sus baches pendientes, asignados o en proceso
      baches.value = resBaches.data.filter(bache => bache.estado !== 'Reparado');
      cuadrillas.value = [];
      materiales.value = [];
    } else {
      const [resBaches, resCuadrillas, resMateriales] = await Promise.all([
        axios.get('https://sdb-sistema-production.up.railway.app/api/baches', config),
        axios.get('https://sdb-sistema-production.up.railway.app/api/cuadrillas', config),
        axios.get('https://sdb-sistema-production.up.railway.app/api/materiales', config)
      ]);
      baches.value = resBaches.data.filter(bache => bache.estado !== 'Reparado');
      cuadrillas.value = resCuadrillas.data;
      materiales.value = resMateriales.data;
    }

    // 🛡️ ESCUDO LEAFLET: Cerramos popups activos antes de limpiar capas para evitar el error '_latLngToNewLayerPoint'
    if (mapInstance.value) {
      mapInstance.value.closePopup();
    }
    markersGroup.value.clearLayers();

    baches.value.forEach(bache => {
      // Validación preventiva de coordenadas geográficas
      if (!bache.latitud || !bache.longitud) return;

      let color = 'green';
      if (bache.severidad === 'Alta') color = 'red';
      if (bache.severidad === 'Media') color = 'orange';

      const container = document.createElement('div');
      container.className = 'custom-popup';
      
      const nombreCuadrilla = bache.cuadrilla ? bache.cuadrilla.nombre : '<i>No asignada</i>';
      const seguroEstado = bache.estado || 'Pendiente';

      container.innerHTML = `
        <b style="color: #2c3e50; font-size: 1.1rem;">Bache #${bache.id}</b><br>
        <hr style="margin: 8px 0; border: 0.5px solid #ddd;">
        <b>📍 Zona:</b> ${bache.zona ? bache.zona.nombre : 'La Paz'}<br>
        <b>⚠️ Severidad:</b> ${bache.severidad}<br>
        <b>👥 Cuadrilla:</b> ${nombreCuadrilla}<br>
        <b>📊 Estado:</b> <span class="status-text ${seguroEstado.replace(/\s+/g, '-').toLowerCase()}">${seguroEstado}</span><br>
        <small class="text-muted">Detectado: ${new Date(bache.created_at).toLocaleDateString()}</small><br>
      `;

      if (seguroEstado === 'Reparado') {
        const obraInfo = document.createElement('div');
        obraInfo.className = 'obra-info-box';
        obraInfo.innerHTML = `
          <hr style="margin: 6px 0; border: 0.5px dashed #27ae60;">
          <b style="color: #27ae60; font-size: 0.8rem;">📐 Métricas e Insumos Viales:</b><br>
          <b>Eje X:</b> ${bache.eje_x} m<br>
          <b>Eje Y:</b> ${bache.eje_y} m<br>
          <b>Profundidad:</b> ${bache.profundidad_cm} cm<br>
          <b>Volumen Vac.:</b> ${parseFloat(bache.volumen_m3).toFixed(4)} m³<br>
          <b>Material:</b> <span style="font-size:0.75rem; font-weight:bold; color:#34495e;">${bache.material ? bache.material.nombre : 'N/A'}</span>
        `;
        container.appendChild(obraInfo);

        const successTag = document.createElement('div');
        successTag.innerHTML = '🎉 ¡Vía habilitada y segura!';
        successTag.className = 'success-tag';
        container.appendChild(successTag);
      }

      if (seguroEstado === 'Pendiente') {
        if (userRole.value === 'Jefe de Cuadrilla') {
          const infoTag = document.createElement('div');
          infoTag.innerHTML = '⏳ Esperando asignación oficial de la Alcaldía.';
          infoTag.style.cssText = 'color: #7f8c8d; font-weight: bold; font-size: 0.85rem; margin-top: 10px; text-align: center;';
          container.appendChild(infoTag);
        } else {
          const select = document.createElement('select');
          select.className = 'select-map';
          select.innerHTML = '<option value="">-- Asignar Equipo --</option>';
          cuadrillas.value.forEach(c => {
            select.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
          });

          const btnAsignar = document.createElement('button');
          btnAsignar.innerHTML = '📌 Asignar Cuadrilla';
          btnAsignar.className = 'btn-map btn-assign';
          btnAsignar.onclick = () => asignarEquipo(bache.id, select.value);

          container.appendChild(select);
          container.appendChild(btnAsignar);
        }
      } 
      
      if (seguroEstado === 'Asignado') {
        const btnProcess = document.createElement('button');
        btnProcess.innerHTML = '🛠️ Iniciar Reparación';
        btnProcess.className = 'btn-map btn-process';
        btnProcess.onclick = () => cambiarEstado(bache.id, 'En proceso');
        container.appendChild(btnProcess);
      }

      if (seguroEstado === 'En proceso') {
        const calcContainer = document.createElement('div');
        calcContainer.className = 'calculator-box';
        calcContainer.innerHTML = `
          <hr style="margin: 8px 0; border: 0.5px solid #ddd;">
          <b style="color: #2c3e50; font-size: 0.85rem;">📐 Métricas y Cierre de Obra:</b>
          <div class="input-grid">
            <label>Eje X (m): <input type="number" step="0.01" placeholder="0.0" class="input-map-calc inp-x"></label>
            <label>Eje Y (m): <input type="number" step="0.01" placeholder="0.0" class="input-map-calc inp-y"></label>
          </div>
          <div class="input-grid" style="margin-top: 5px;">
            <label>Prof. (cm): <input type="number" step="0.1" placeholder="Fijo en cm" class="input-map-calc inp-p"></label>
          </div>
          <div class="volume-preview">Volumen estimado: <b>0.0000 m³</b></div>
        `;

        const selectMat = document.createElement('select');
        selectMat.className = 'select-map';
        
        if (bache.cuadrilla_id) {
          selectMat.innerHTML = '<option value="">⏳ Cargando materiales del camión...</option>';
          
          axios.get(`http://localhost:8000/api/cuadrillas/${bache.cuadrilla_id}/materiales-activos`, config)
            .then(res => {
              selectMat.innerHTML = '<option value="">-- Seleccionar Insumo (En Camión) --</option>';
              if (res.data.length === 0) {
                selectMat.innerHTML = '<option value="">⚠️ Sin asfalto asignado en ruta hoy</option>';
              } else {
                res.data.forEach(m => {
                  selectMat.innerHTML += `<option value="${m.id}">${m.nombre} (${parseFloat(m.stock_actual).toFixed(2)} m³ en camión)</option>`;
                });
              }
            })
            .catch(err => {
              console.error("Error cargando camión:", err);
              selectMat.innerHTML = '<option value="">❌ Error al escanear camión</option>';
            });
        } else {
          selectMat.innerHTML = '<option value="">⚠️ Error: Sin cuadrilla vinculada</option>';
        }
        
        calcContainer.appendChild(selectMat);

        const inpX = calcContainer.querySelector('.inp-x');
        const inpY = calcContainer.querySelector('.inp-y');
        const inpP = calcContainer.querySelector('.input-map-calc.inp-p');
        const lblVol = calcContainer.querySelector('.volume-preview');

        const calcularM3 = () => {
          const x = parseFloat(inpX.value) || 0;
          const y = parseFloat(inpY.value) || 0;
          const p = parseFloat(inpP.value) || 0;
          
          const total_m3 = x * y * (p / 100);
          lblVol.innerHTML = `Volumen estimado: <b style="color: #2980b9;">${total_m3.toFixed(4)} m³</b>`;
        };

        inpX.oninput = calcularM3;
        inpY.oninput = calcularM3;
        inpP.oninput = calcularM3;

        const btnFix = document.createElement('button');
        btnFix.innerHTML = '✅ Finalizar y Descontar';
        btnFix.className = 'btn-map btn-fixed';
        btnFix.onclick = () => finalizarReparacion(bache.id, inpX.value, inpY.value, inpP.value, selectMat.value);

        calcContainer.appendChild(btnFix);
        container.appendChild(calcContainer);
      }

      L.marker([bache.latitud, bache.longitud], { icon: createIcon(color) })
        .addTo(markersGroup.value)
        .bindPopup(container);
    });

  } catch (error) {
    console.error("Error al sincronizar datos del mapa:", error);
  }
  loadingData.value = false;
};

const finalizarReparacion = async (id, x, y, prof, materialId) => {
  if (!x || !y || !prof || !materialId) {
    return alert("⚠️ Error: Todos los campos geométricos (X, Y, Profundidad) y el Material son mandatorios para la auditoría.");
  }

  try {
    const config = { headers: { 'Authorization': `Bearer ${token}` } };
    const payload = {
      estado: 'Reparado',
      eje_x: parseFloat(x),
      eje_y: parseFloat(y),
      profundidad_cm: parseFloat(prof),
      material_id: parseInt(materialId)
    };

    await axios.put(`https://sdb-sistema-production.up.railway.app/api/baches/${id}/estado`, payload, config);
    alert("🎉 ¡Bache reparado con éxito! Se calculó y descontó el volumen físico del camión en tránsito de la cuadrilla.");
    await fetchAndRenderMarkers(); 
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      alert(error.response.data.message);
    } else {
      alert("Error de comunicación con el motor transaccional S.D.B.");
    }
  }
};

const asignarEquipo = async (bacheId, cuadrillaId) => {
  if (!cuadrillaId) return alert("Por favor, selecciona una cuadrilla");
  try {
    await axios.put(`https://sdb-sistema-production.up.railway.app/api/baches/${bacheId}/asignar-cuadrilla`,
      { cuadrilla_id: cuadrillaId }, 
      { headers: { 'Authorization': `Bearer ${token}` } }
    );
    alert("Cuadrilla asignada exitosamente");
    await fetchAndRenderMarkers(); 
  } catch (error) {
    alert("Error al asignar cuadrilla");
  }
};

const cambiarEstado = async (id, nuevoEstado) => {
  if (!confirm(`¿Desea cambiar el estado del bache ID: ${id} a '${nuevoEstado}'?`)) return;
  try {
    await axios.put(`https://sdb-sistema-production.up.railway.app/api/baches/${id}/estado`,
      { estado: nuevoEstado }, 
      { headers: { 'Authorization': `Bearer ${token}` } }
    );
    alert(`Estado actualizado a: ${nuevoEstado}`);
    await fetchAndRenderMarkers(); 
  } catch (error) {
    alert("Error de comunicación con el servidor S.D.B.");
  }
};

const initMap = () => {
  const map = L.map('map').setView([-16.5042, -68.1305], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  mapInstance.value = map;
  markersGroup.value = L.layerGroup().addTo(map);

  fetchAndRenderMarkers();
};

onMounted(initMap);
</script>

<style scoped>
.map-container { position: relative; width: 100%; height: 85vh; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
#map { width: 100%; height: 100%; z-index: 1; }

.btn-refresh-map {
  position: absolute; top: 15px; left: 70px; z-index: 1000;
  background-color: #2c3e50; color: white; border: none; padding: 10px 18px;
  border-radius: 8px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease; font-family: inherit;
}
.btn-refresh-map:hover { background-color: #1a252f; transform: translateY(-1px); }
.btn-refresh-map:disabled { background-color: #95a5a6; cursor: not-allowed; }

.map-legend {
  position: absolute; bottom: 20px; right: 20px; z-index: 1000;
  background: white; padding: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.status-badge { height: 10px; width: 10px; display: inline-block; border-radius: 2px; margin-right: 8px; }
.pending { background: #e74c3c; }
.assigned { background: #3498db; }
.processing { background: #f1c40f; }
.fixed { background: #27ae60; }

.dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
.red { background-color: #eb3324; }
.orange { background-color: #ff9100; }
.green { background-color: #28a745; }

.calculator-box { background: #f8f9fa; padding: 8px; border-radius: 6px; margin-top: 5px; border: 1px solid #e2e8f0; }
.input-grid { display: flex; gap: 5px; }
.input-grid label { font-size: 0.75rem; font-weight: bold; color: #4a5568; display: flex; flex-direction: column; flex: 1; }
.input-map-calc { padding: 4px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 0.8rem; width: 90%; background: white; }
.volume-preview { font-size: 0.75rem; margin-top: 5px; text-align: center; background: #e2e8f0; padding: 4px; border-radius: 4px; color: #2d3748; }
.obra-info-box { background: #f0fdf4; padding: 6px; border-radius: 6px; border: 1px solid #bbf7d0; font-size: 0.78rem; line-height: 1.35; margin-top: 5px; color: #1e4620; }

.select-map { width: 100%; margin-top: 8px; padding: 6px; border-radius: 6px; border: 1px solid #ccc; background: white; font-size: 0.8rem; }
.btn-map {
  width: 100%; padding: 10px; margin-top: 8px; border: none; border-radius: 8px;
  color: white; font-weight: bold; cursor: pointer; transition: 0.3s;
}
.btn-assign { background-color: #3498db; }
.btn-assign:hover { background-color: #2980b9; }
.btn-process { background-color: #f1c40f; color: #2c3e50; }
.btn-fixed { background-color: #198754; }

.status-text { font-weight: bold; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
.pendiente { color: #e74c3c; background: #feebeb; }
.asignado { color: #3498db; background: #ebf5fb; }
.en-proceso { color: #f39c12; background: #fef5e7; }
.reparado { color: #27ae60; background: #eafaf1; }

.success-tag { margin-top: 10px; text-align: center; color: #27ae60; font-weight: bold; }
</style>