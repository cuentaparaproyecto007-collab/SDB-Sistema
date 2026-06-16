<template>
  <div class="login-page-wrapper">
    
    <video autoplay loop muted playsinline class="bg-video">
      <source src="/login-bg.mp4" type="video/mp4">
    </video>

    <div class="bg-overlay"></div>

    <div class="login-split-container">
      
      <div class="text-side animate__animated animate__fadeInLeft">
        <div class="welcome-message">
          <span class="tag-cyber">GOBIERNO AUTÓNOMO MUNICIPAL</span>
          <h2>Innovación Tecnológica para el <br><span class="highlight-text">Desarrollo Vial Urbano</span></h2>
          <p>
            Plataforma avanzada de telemetría IoT y procesamiento inteligente de datos orientada al monitoreo preventivo, clasificación automatizada de daños estructurales y optimización del mantenimiento de la capa asfáltica.
          </p>
          <div class="features-grid">
            <div class="feature-item">
              <span class="f-icon">📟</span>
              <div>
                <h5>Sensores IoT</h5>
                <p>Muestreo y captura acelerométrica en tiempo real.</p>
              </div>
            </div>
            <div class="feature-item">
              <span class="f-icon">👤</span>
              <div>
                <h5>Seguridad Biométrica</h5>
                <p>Doble factor de autenticación e IA de reconocimiento facial.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="login-side">
        <div class="login-container animate__animated animate__fadeInRight">
          <div class="login-header">
            <h1 class="fw-bold">S.D.B.</h1>
            <p class="text-muted-custom">Sistema de Detección de Baches</p>
          </div>
          
          <form v-if="step === 1" @submit.prevent="handleLogin" class="animate__animated animate__fadeIn">
            <div class="form-group-custom">
              <label class="form-label-custom">CORREO ELECTRÓNICO</label>
              <input v-model="form.email" type="email" class="form-control-custom" placeholder="admin@sdb.com" required>
            </div>
            <div class="form-group-custom">
              <label class="form-label-custom">CONTRASEÑA</label>
              <input v-model="form.password" type="password" class="form-control-custom" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-primary-custom" :disabled="loading">
              {{ loading ? 'Validando...' : 'Siguiente ➔' }}
            </button>
          </form>

          <form v-if="step === 2" @submit.prevent="handleVerifyOtp" class="animate__animated animate__fadeIn">
            <div class="form-group-custom header-gap">
              <h5 class="text-step-title">Verificación de Seguridad</h5>
              <p class="text-step-desc">Ingresa el código de 6 dígitos enviado a tu correo.</p>
            </div>
            
            <div class="form-group-custom">
              <label class="form-label-custom">CÓDIGO DE ACCESO</label>
              <input v-model="otpCode" type="text" class="form-control-custom otp-field-style" maxlength="6" placeholder="000000" required>
            </div>
            
            <div class="checkbox-custom-container">
              <input v-model="rememberDevice" type="checkbox" id="remDevice" class="custom-checkbox-input">
              <label for="remDevice" class="checkbox-text-label">Recordar este equipo</label>
            </div>

            <div class="vertical-buttons-stack">
              <button type="submit" class="btn-primary-custom" :disabled="loading">
                {{ loading ? 'Verificando...' : 'Confirmar Código' }}
              </button>
              <button type="button" @click="step = 1" class="btn-secondary-custom-stacked">
                ✕ Cancelar y Volver
              </button>
            </div>
          </form>

          <div v-if="step === 3" class="face-auth animate__animated animate__fadeIn text-center">
            <div class="face-icon mb-3">👤</div>
            <p class="fw-bold mb-4 text-step-desc-white">Verificación Biométrica</p>
            
            <div class="video-container shadow-sm mb-4">
              <video ref="videoRef" autoplay muted playsinline class="camera-stream"></video>
              <canvas ref="canvasRef" class="overlay"></canvas>
            </div>

            <p v-if="!modelsLoaded" class="small text-info animate__animated animate__flash animate__infinite">
              Inicializando IA Facial...
            </p>

            <button @click="handleFaceAuth" class="btn-primary-custom" :disabled="loading || !modelsLoaded">
              {{ loading ? 'Procesando Rostro...' : 'Escanear Rostro' }}
            </button>
          </div>

          <p v-if="error" class="error-msg animate__animated animate__shakeX">{{ error }}</p>
          
          <div v-if="debugOtp" class="debug-otp mt-4">
            <small>🔑 Código de prueba: <b>{{ debugOtp }}</b></small>
          </div>

          <div class="version-tag-container">
            <span>v2.1.0</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/axios'; 
import { useRouter } from 'vue-router';
import * as faceapi from 'face-api.js';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const error = ref('');
const debugOtp = ref('');
const modelsLoaded = ref(false);
const videoStream = ref(null);

const videoRef = ref(null);
const canvasRef = ref(null);

const deviceId = 'PC-NAV-' + navigator.userAgent.substring(0, 10);
const form = ref({ email: '', password: '' });
const otpCode = ref('');
const rememberDevice = ref(false);

const loadModels = async () => {
  try {
    const MODEL_URL = '/models';
    await Promise.all([
      faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
      faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
    ]);
    modelsLoaded.value = true;
  } catch (err) {
    console.error("Error en modelos:", err);
  }
};

const startVideo = () => {
  navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 } })
    .then(stream => { 
      videoStream.value = stream;
      if(videoRef.value) videoRef.value.srcObject = stream; 
    })
    .catch(() => error.value = "Habilita la cámara para continuar");
};

const stopVideo = () => {
  if (videoStream.value) {
    videoStream.value.getTracks().forEach(track => track.stop());
    videoStream.value = null;
  }
};

onMounted(loadModels);

const handleLogin = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.post('/login', { ...form.value, device_id: deviceId });
    if (response.data.requires_2fa) {
      step.value = 2;
      debugOtp.value = response.data.otp_debug;
    } else {
      step.value = 3;
      setTimeout(startVideo, 100);
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Credenciales inválidas';
  } finally { loading.value = false; }
};

const handleVerifyOtp = async () => {
  loading.value = true;
  try {
    const res = await api.post('/verify-otp', {
      email: form.value.email,
      otp: otpCode.value,
      device_id: deviceId,
      remember_device: rememberDevice.value
    });
    localStorage.setItem('userName', res.data.user.name);
    step.value = 3;
    setTimeout(startVideo, 100); 
  } catch (err) {
    error.value = err.response?.data?.message || 'Código incorrecto';
  } finally { loading.value = false; }
};

const handleFaceAuth = async () => {
  loading.value = true;
  error.value = null; 
  try {
    const detection = await faceapi.detectSingleFace(videoRef.value);
    if (!detection) {
      error.value = "No detecto tu rostro. Asegúrate de tener buena luz.";
      loading.value = false;
      return;
    }
    const canvas = faceapi.createCanvasFromMedia(videoRef.value);
    const faceBase64 = canvas.toDataURL('image/jpeg');

    const response = await api.post('/verify-face', {
      email: form.value.email,
      face_image: faceBase64
    });

    saveSession(response.data);

  } catch (err) {
    error.value = err.response?.data?.message || "Validación facial fallida";
  } finally { loading.value = false; }
};

const saveSession = (data) => {
  stopVideo(); 
  localStorage.setItem('token', data.access_token);
  const roleName = data.user.role?.nombre || data.user.role;
  localStorage.setItem('role', roleName);
  localStorage.setItem('userName', data.user.name);

  if (data.user.role && data.user.role.permissions) {
    const permissionSlugs = data.user.role.permissions.map(p => p.slug);
    localStorage.setItem('userPermissions', JSON.stringify(permissionSlugs));
  } else {
    localStorage.setItem('userPermissions', JSON.stringify([]));
  }

  router.push('/dashboard');
};
</script>

<style scoped>
/* Contenedor General de la Ventana */
.login-page-wrapper {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background-color: #060b19;
}

/* Video e Overlay de Fondo */
.bg-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 1;
}
.bg-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, rgba(8, 18, 41, 0.75), rgba(15, 23, 42, 0.85));
  backdrop-filter: blur(4px);
  z-index: 2;
}

/* Split Screen Layout */
.login-split-container {
  position: relative;
  width: 100%;
  height: 100%;
  max-width: 1300px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 60px;
  z-index: 3;
}
.text-side { flex: 1.2; display: flex; justify-content: flex-start; padding-right: 60px; }
.login-side { flex: 1; display: flex; justify-content: flex-end; }

/* Tarjeta de Cristal Tecnológico */
.login-container { 
  width: 100%;
  max-width: 410px; 
  padding: 45px 35px 30px 35px; 
  background: rgba(13, 27, 56, 0.75) !important; 
  backdrop-filter: blur(20px) !important;
  -webkit-backdrop-filter: blur(20px) !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  border-radius: 24px; 
  box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
}

.version-tag-container {
  text-align: center;
  margin-top: 25px;
  font-size: 0.75rem;
  color: rgba(148, 163, 184, 0.35); /* Gris sutil que no compite con los botones */
  font-weight: 600;
  letter-spacing: 0.06rem;
  width: 100%;
}

.login-header { text-align: center; margin-bottom: 35px; }
.login-header h1 { color: #2ecc71; font-size: 3rem; margin-bottom: 5px; font-weight: 800; letter-spacing: 1.5px; }
.text-muted-custom { color: #94a3b8; font-weight: 500; font-size: 0.95rem; margin: 0; }

.form-group-custom { margin-bottom: 22px; display: flex; flex-direction: column; text-align: left; }
.form-group-custom.header-gap { margin-bottom: 25px; }
.form-label-custom { color: #cbd5e1 !important; font-size: 0.75rem; letter-spacing: 0.08rem; font-weight: 700; margin-bottom: 8px; }

/* Inputs Base del Sistema */
.form-control-custom { 
  width: 100%; 
  height: 52px;
  padding: 14px 16px; 
  border: 2px solid rgba(255, 255, 255, 0.1) !important; 
  border-radius: 12px; 
  background: rgba(9, 17, 36, 0.7) !important; 
  color: #ffffff !important; 
  outline: none; 
  transition: all 0.3s ease;
  font-weight: 500;
  font-size: 0.95rem;
}
.form-control-custom::placeholder { color: rgba(255, 255, 255, 0.3); }
.form-control-custom:focus { 
  border-color: #2ecc71 !important; 
  background: rgba(9, 17, 36, 0.9) !important; 
  box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.2);
}

/* 🎯 CORRECCIÓN: Centrado matemático perfecto para los 6 números del OTP */
.otp-field-style {
  text-align: center !important;
  font-size: 1.6rem !important;
  letter-spacing: 0.5rem !important;
  font-weight: 700 !important;
  padding-left: 24px !important; /* El padding izquierdo compensa el letter-spacing final y centra el texto a la perfección */
}

/* Chrome Autocomplete Fix */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-text-fill-color: #ffffff !important;
  -webkit-box-shadow: 0 0 0px 1000px #0d1b38 inset !important;
  transition: background-color 5000s ease-in-out 0s;
}

/* Títulos del Paso 2 */
.text-step-title { color: #ffffff; font-weight: 700; margin-bottom: 6px; font-size: 1.3rem; text-align: left; }
.text-step-desc { color: #94a3b8; font-weight: 500; font-size: 0.9rem; margin-bottom: 0; line-height: 1.4; text-align: left; }
.text-step-desc-white { color: #f8fafc; font-weight: 500; }

/* 🎯 CORRECCIÓN: Checkbox limpio, espaciado y con texto blanco brillante legible */
.checkbox-custom-container {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  margin: -5px 0 25px 2px;
  width: 100%;
}
.custom-checkbox-input {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: #2ecc71;
}
.checkbox-text-label {
  color: #ffffff !important; /* Fuerza color blanco contra el fondo oscuro */
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  margin-bottom: 0 !important;
}

/* 🎯 CORRECCIÓN: Botones verticales idénticos al Paso 1 (Cero deformación) */
.vertical-buttons-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
}
.vertical-buttons-stack .btn-secondary-custom-stacked {
  width: 100%;
  height: 52px;
  background: rgba(255, 255, 255, 0.05);
  color: #cbd5e1;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  font-size: 1rem;
}
.vertical-buttons-stack .btn-secondary-custom-stacked:hover {
  background: rgba(239, 68, 68, 0.15); /* Sutil tinte rojo de cancelación al pasar el mouse */
  color: #fca5a5;
  border-color: rgba(239, 68, 68, 0.3);
}

/* Botón Primario Verde */
.btn-primary-custom { 
  background: #2ecc71; color: #041208; border: none; height: 52px; padding: 0; border-radius: 12px; 
  font-weight: 800; width: 100%; transition: all 0.3s ease; font-size: 1rem; cursor: pointer;
  box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-primary-custom:hover:not(:disabled) { background: #27ae60; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46, 204, 113, 0.45); color: white; }
.btn-primary-custom:disabled { opacity: 0.5; cursor: not-allowed; }

/* Mensaje de Bienvenida Izquierda */
.welcome-message { text-align: left; max-width: 580px; color: white; }
.tag-cyber { font-size: 0.8rem; font-weight: 800; color: #2ecc71; letter-spacing: 0.15rem; background: rgba(46, 204, 113, 0.12); padding: 6px 14px; border-radius: 6px; display: inline-block; margin-bottom: 20px; }
.welcome-message h2 { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; color: #f8fafc; }
.highlight-text { color: #2ecc71; }
.welcome-message p { color: #94a3b8; font-size: 1.05rem; line-height: 1.6; margin-bottom: 35px; }

/* Características */
.features-grid { display: flex; flex-direction: column; gap: 20px; }
.feature-item { display: flex; align-items: center; gap: 15px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); padding: 15px 20px; border-radius: 14px; }
.f-icon { font-size: 1.8rem; background: rgba(255, 255, 255, 0.05); width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
.feature-item h5 { margin: 0 0 4px 0; font-size: 1rem; font-weight: 700; color: #f1f5f9; }
.feature-item p { margin: 0; font-size: 0.85rem; color: #64748b; line-height: 1.4; }

/* Reconocimiento Facial */
.video-container { width: 100%; max-width: 240px; margin: 0 auto; border-radius: 50%; border: 4px solid #2ecc71; overflow: hidden; position: relative; background: #000; height: 240px; }
.camera-stream { width: 100%; height: 100%; object-fit: cover; }

/* Alertas y Debugger */
.error-msg { color: #fca5a5; background: rgba(220, 38, 38, 0.25); padding: 12px; border-radius: 10px; font-size: 0.85rem; text-align: center; margin-top: 15px; font-weight: 600; border: 1px solid rgba(239, 68, 68, 0.4); }
.debug-otp { background: rgba(16, 185, 129, 0.15); padding: 10px; border-radius: 8px; color: #34d399; text-align: center; font-weight: 600; border: 1px solid rgba(52, 211, 153, 0.3); width: 100%; box-sizing: border-box; }
.ls-2 { letter-spacing: 0.5rem; }
.cursor-pointer { cursor: pointer; }

/* Responsive */
@media (max-width: 992px) {
  .login-split-container { padding: 0 20px; justify-content: center; }
  .text-side { display: none; }
  .login-side { justify-content: center; }
}
</style>