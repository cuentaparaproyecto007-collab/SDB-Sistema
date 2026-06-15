import axios from 'axios';
import { isBackendDown } from '../services/networkState'; // 🔥 NUEVO: Importamos el guardián reactivo

const api = axios.create({
    baseURL: 'https://sdb-sistema-production.up.railway.app/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Esto es para que todas las peticiones lleven el Token de seguridad automáticamente
api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// 🔥 NUEVO: Interceptor de respuestas para control y tolerancia a fallos
api.interceptors.response.use(
    (response) => response, // Si la petición tiene éxito, se procesa con normalidad
    (error) => {
        // Si no hay respuesta (Servidor apagado / Network Error) o el servidor explota (Error 500)
        if (!error.response || error.response.status === 500) {
            isBackendDown.value = true; // Levanta la pantalla completa animada con el GIF
        }
        return Promise.reject(error); // Evita romper las promesas internas del sistema
    }
);

export default api;