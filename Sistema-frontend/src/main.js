import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

// 🔥 NUEVO: Importamos Axios global y nuestro estado reactivo
import axios from 'axios'
import { isBackendDown } from './services/networkState'

// 🔥 NUEVO: Interceptor de respuestas global para capturar el servidor apagado
axios.interceptors.response.use(
    (response) => response, 
    (error) => {
        // Si el backend está apagado por completo (Network Error) o responde 500
        if (!error.response || error.response.status === 500) {
            isBackendDown.value = true; // Levanta el escudo animado en toda la app
        }
        return Promise.reject(error);
    }
);

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
