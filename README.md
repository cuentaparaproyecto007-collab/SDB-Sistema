# S.D.B. - Sistema de Detección de Baches 🚀

Plataforma avanzada de telemetría IoT y procesamiento inteligente de datos orientada al monitoreo preventivo, clasificación automatizada de daños estructurales y optimización del mantenimiento de la capa asfáltica para el Gobierno Autónomo Municipal.

---

## 📊 1. Estructura del Proyecto

El ecosistema está desarrollado bajo una arquitectura desacoplada full-stack distribuida de la siguiente manera:

*   **`/Sistema-backend`**: API REST robusta encargada de la persistencia, reglas de negocio y autenticación.
*   **`/Sistema-frontend`**: Interfaz de usuario SPA responsiva y moderna construida para una experiencia fluida.

---

## 🛠️ 2. Stack Tecnológico

*   **Backend**: Laravel / PHP (API REST, JWT, OTP Generator)
*   **Frontend**: Vue.js 3 (Composition API, Axios)
*   **IA & Biometría**: Face-api.js (Modelos SSD Mobilenet v1 y Face Landmark)
*   **Infraestructura Cloud**: Railway (Hosting) & Namecheap (Domain Registrar & DNS Cloud)

---

## 🚀 3. Guía de Despliegue en Producción e Infraestructura

La puesta en producción definitiva del ecosistema se realizó siguiendo un flujo profesional de aprovisionamiento de infraestructura y despliegue continuo (CI/CD):

### Paso A: Adquisición del Dominio Profesional en Namecheap
1. El dominio oficial del proyecto **`sdb-vial.online`** fue adquirido formalmente a través del proveedor de dominios **Namecheap**.
2. Se habilitaron las directivas de protección de privacidad de dominio (*WhoisGuard*) para resguardar los datos de la infraestructura y seguridad del sistema.

### Paso B: Alojamiento y Despliegue Continuo (Railway)
1. Los servicios de Frontend y Backend se configuraron de forma independiente en la plataforma cloud **Railway**, vinculando el repositorio de GitHub para disparar compilaciones automáticas ante cada actualización (*Push*) en la rama `main`.
2. Comando de construcción automatizado para el entorno cliente (Vue.js):
```bash
   npm run build
   ```

### Paso C: Enlace DNS y Certificado de Seguridad (HTTPS)
Para cumplir con el protocolo criptográfico seguro **HTTPS** obligatorio exigido en la evaluación, se realizó la delegación de zonas DNS desde el panel avanzado de **Namecheap** hacia los servidores de **Railway**, registrando los siguientes parámetros estrictos:

1. **Registro CNAME (Enrutamiento del Tráfico Web)**:
   * **Type**: CNAME Record
   * **Host**: `www`
   * **Value**: `mzhnj0ye.up.railway.app.` (Target provisto por el clúster de Railway)
   * **TTL**: Automatic

2. **Registro TXT (Validación de Propiedad y Emisión SSL)**:
   * **Type**: TXT Record
   * **Host**: `_railway-verify.www`
   * **Value**: `railway-verify=1c9edbf4541e75a1a16f7337575cf6ef7471efe8fb9e8...` (Token criptográfico único para validar la firma del certificado SSL/TLS automático de Let's Encrypt)

---

## 💻 4. Instalación y Configuración Local (Desarrollo)

Si desea replicar el entorno de desarrollo de forma local, ejecute los siguientes comandos:

### Requisitos Previos
* PHP 8.x / Composer
* Node.js (Versión LTS) / NPM

### Configuración del Backend
```bash
cd Sistema-backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Configuración del Frontend
```bash
cd Sistema-frontend
npm install
npm run dev
```

---

## 🔒 5. Esquema de Seguridad (3FA)
La plataforma implementa un flujo estricto de **Autenticación de Tres Factores (3FA)** para resguardar los accesos gubernamentales:
1. **Algo que sabes**: Contraseña tradicional encriptada en base de datos.
2. **Algo que tienes**: Código dinámico OTP de 6 dígitos enviado por canal seguro al correo institucional.
3. **Algo que eres**: Verificación de identidad mediante Inteligencia Artificial de reconocimiento facial biométrico (ejecutado en el cliente mediante modelos cargados en `/public/models`).

---
**Entorno de Producción Activo:** [https://www.sdb-vial.online](https://www.sdb-vial.online)