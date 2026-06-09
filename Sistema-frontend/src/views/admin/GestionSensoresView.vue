<template>
  <div class="gestion-container animate__animated animate__fadeIn">
    <div class="row-layout">
      
      <aside class="form-sidebar">
        <div class="card shadow-soft">
          <div class="card-header" :class="{ 'bg-orange': isEditing }">
            <i class="icon">{{ isEditing ? '✏️' : '📟' }}</i> {{ isEditing ? 'Editar Sensor IoT' : 'Registrar Nuevo Sensor' }}
          </div>
          <form @submit.prevent="guardarSensor" class="form-body">
            <div class="form-group">
              <label>Código Identificador</label>
              <input 
                v-model="form.codigo" 
                type="text" 
                placeholder="Ej: SNS-001" 
                required
                :disabled="loading"
              >
            </div>
            
            <div class="form-group">
              <label>Modelo del Hardware</label>
              <input 
                v-model="form.modelo" 
                type="text" 
                placeholder="Ej: MPU6050 (Acelerómetro)" 
                required
                :disabled="loading"
              >
            </div>

            <div class="form-group">
              <label>Estado Inicial</label>
              <select v-model="form.estado" required :disabled="loading || (isEditing && form.estado === 'Instalado')">
                <option value="Disponible">Disponible (En Almacén)</option>
                <option value="Mantenimiento">En Mantenimiento / Calibración</option>
                <option v-if="isEditing" value="Instalado" disabled>Instalado (Modificar desde Vehículos)</option>
              </select>
              <small class="hint-info" v-if="isEditing && form.estado === 'Instalado'">
                ℹ️ Los sensores instalados se liberan desvinculándolos de su vehículo.
              </small>
            </div>

            <div class="actions btn-group-vertical">
              <button type="submit" class="btn-primary full-width" :class="{ 'btn-orange': isEditing }" :disabled="loading">
                <i class="icon">💾</i> {{ loading ? 'Procesando...' : (isEditing ? 'Actualizar Hardware' : 'Guardar en Inventario') }}
              </button>
              <button v-if="isEditing" type="button" @click="cancelarEdicion" class="btn-secondary full-width mt-2" :disabled="loading">
                Cancelar Edición
              </button>
            </div>
          </form>
        </div>
      </aside>

      <main class="table-content">
        <div class="card border-none">
          <div class="card-header bg-dark">
            <div class="header-flex">
              <span><i class="icon">📋</i> Inventario de Dispositivos Hardware</span>
              <span class="count-badge">{{ sensores.length }} Registrados</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Código</th>
                  <th>Modelo Telemetría</th>
                  <th>Estado</th>
                  <th>Asignación</th>
                  <th class="text-center">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in sensores" :key="s.id" class="table-row" :class="{ 'row-editing': editingId === s.id }">
                  <td class="text-muted">#{{ s.id }}</td>
                  <td><span class="sensor-code">{{ s.codigo }}</span></td>
                  <td>{{ s.modelo }}</td>
                  <td>
                    <span :class="['badge-pill', getBadgeClass(s.estado)]">
                      {{ s.estado }}
                    </span>
                  </td>
                  <td>
                    <div class="vehiculo-info">
                      <span v-if="s.vehiculo">🚗 Placa: <b>{{ s.vehiculo.placa }}</b></span>
                      <span v-else class="text-muted italic">Sin vehículo acoplado</span>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="btn-actions-group">
                      <button :disabled="loading" @click="activarEdicion(s)" class="btn-action btn-edit" title="Editar Hardware">✏️</button>
                      <button :disabled="loading || s.estado === 'Instalado'" @click="eliminarSensor(s.id, s.codigo)" class="btn-action btn-delete" :title="s.estado === 'Instalado' ? 'No se puede eliminar un sensor instalado' : 'Eliminar Sensor'">🗑️</button>
                    </div>
                  </td>
                </tr>
                <tr v-if="sensores.length === 0 && !loading">
                  <td colspan="6" class="empty-state">
                    No se registran componentes de sensores IoT en la base de datos.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const sensores = ref([]);
const loading = ref(false);
const token = localStorage.getItem('token');

// Control de Estados CRUD
const isEditing = ref(false);
const editingId = ref(null);
const form = ref({ codigo: '', modelo: '', estado: 'Disponible' });

const cargarSensores = async () => {
  loading.value = true;
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.get('http://localhost:8000/api/sensores', config);
    sensores.value = res.data;
  } catch (e) {
    console.error("Error al sincronizar el inventario de hardware:", e);
  } finally {
    loading.value = false;
  }
};

const guardarSensor = async () => {
  if (!form.value.codigo || !form.value.modelo) return;
  
  loading.value = true;
  const config = { headers: { Authorization: `Bearer ${token}` } };
  
  try {
    if (isEditing.value) {
      await axios.put(`http://localhost:8000/api/sensores/${editingId.value}`, form.value, config);
      alert("Hardware actualizado correctamente.");
      cancelarEdicion();
    } else {
      await axios.post('http://localhost:8000/api/sensores', form.value, config);
      alert(`Sensor '${form.value.codigo}' añadido al inventario con éxito.`);
      form.value = { codigo: '', modelo: '', estado: 'Disponible' };
    }
    await cargarSensores(); 
  } catch (e) {
    alert(e.response?.data?.message || "Error al procesar la operación en el servidor.");
  } finally {
    loading.value = false;
  }
};

const activarEdicion = (sensor) => {
  isEditing.value = true;
  editingId.value = sensor.id;
  form.value = {
    codigo: sensor.codigo,
    modelo: sensor.modelo,
    estado: sensor.estado
  };
};

const cancelarEdicion = () => {
  isEditing.value = false;
  editingId.value = null;
  form.value = { codigo: '', modelo: '', estado: 'Disponible' };
};

const eliminarSensor = async (id, codigo) => {
  const seguro = confirm(`¿Está seguro de retirar el sensor "${codigo}" permanentemente del inventario?`);
  if (!seguro) return;

  loading.value = true; 
  try {
    const config = { headers: { Authorization: `Bearer ${token}` } };
    const res = await axios.delete(`http://localhost:8000/api/sensores/${id}`, config);
    alert(res.data.message || "Sensor eliminado.");
    
    if (editingId.value === id) cancelarEdicion();
    sensores.value = sensores.value.filter(item => item.id !== id);
  } catch (e) {
    alert(e.response?.data?.message || "Error de comunicación con el motor de base de datos.");
  } finally {
    loading.value = false; 
  }
};

const getBadgeClass = (estado) => {
  if (estado === 'Disponible') return 'pill-success';
  if (estado === 'Instalado') return 'pill-info';
  return 'pill-warn';
};

onMounted(cargarSensores);
</script>

<style scoped>
/* Estilos Base Sincronizados con S.D.B */
.gestion-container { padding: 5px; }
.row-layout { display: flex; gap: 20px; flex-wrap: wrap; }

.form-sidebar { flex: 1; min-width: 320px; }
.table-content { flex: 2; min-width: 500px; }

.card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #edf2f7; }
.card-header { background: #2c3e50; color: #42b983; padding: 18px 25px; font-weight: 700; border-bottom: 4px solid #42b983; font-size: 1.1rem; }
.bg-dark { background: #34495e; }
.bg-orange { background: #d69e2e !important; color: white !important; border-bottom: 4px solid #b7791f !important; }

.header-flex { display: flex; justify-content: space-between; align-items: center; }
.count-badge { background: rgba(66, 185, 131, 0.2); color: #42b983; padding: 4px 12px; border-radius: 6px; font-size: 0.8rem; }

.form-body { padding: 25px; }
.form-group { margin-bottom: 22px; display: flex; flex-direction: column; }
.form-group label { margin-bottom: 10px; font-weight: 600; color: #4a5568; font-size: 0.95rem; }

input, select { padding: 12px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; color: #2d3748; }
input:focus, select:focus { border-color: #42b983; outline: none; background: #f0fff4; }

.hint-info { color: #3182ce; margin-top: 8px; font-size: 0.8rem; }

.btn-primary { background: #42b983; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; }
.btn-primary:hover:not(:disabled) { background: #3aa373; transform: translateY(-2px); }
.btn-orange { background: #dd6b20 !important; }
.btn-secondary { background: #e2e8f0; color: #4a5568; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.full-width { width: 100%; }
.mt-2 { margin-top: 8px; }

.custom-table { width: 100%; border-collapse: collapse; }
.custom-table th { background: #f8fafc; text-align: left; padding: 15px 20px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 0.75rem; text-transform: uppercase; }
.custom-table td { padding: 18px 20px; border-bottom: 1px solid #edf2f7; color: #2d3748; vertical-align: middle; }
.table-row:hover { background-color: #f7fafc; }
.row-editing { background-color: #fefcbf !important; border-left: 4px solid #dd6b20; }

.sensor-code { font-weight: 700; color: #2c3e50; }

.btn-actions-group { display: flex; gap: 6px; justify-content: center; }
.btn-action { border: none; background: none; font-size: 1rem; padding: 6px 10px; cursor: pointer; border-radius: 6px; }
.btn-action:hover:not(:disabled) { transform: scale(1.15); }
.btn-edit:hover { background: #feebc8; }
.btn-delete:hover:not(:disabled) { background: #fed7d7; }
.btn-delete:disabled { opacity: 0.3; cursor: not-allowed; }

.badge-pill { padding: 6px 14px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
.pill-success { background: #c6f6d5; color: #22543d; }
.pill-warn { background: #feebc8; color: #7b341e; }
.pill-info { background: #bee3f8; color: #2b6cb0; }

.empty-state { padding: 50px !important; color: #a0aec0; font-style: italic; text-align: center; }
.text-center { text-align: center; }
.text-muted { color: #a0aec0; font-size: 0.85rem; }
.italic { font-style: italic; }

@media (max-width: 1024px) {
  .row-layout { flex-direction: column; }
}
</style>