# Sistema S.D.B. - Plataforma de Gestión Vial Urbana

## 📊 Diagrama de Arquitectura de Red Docker
```text
 EXTERIOR (Navegador) ──[ Puerto 80 ]──> [ Servicio Frontend (Vue3/Nginx) ]
                                                        │
                                           (Red Virtual Privada Docker)
                                                        │
 EXTERIOR (Postman) ───[ Puerto 8000 ]──> [ Servicio Backend (Laravel API) ]
                                                        │
                                           (Red Virtual Privada Docker)
                                                        │
                                          [ Servicio Database (MySQL) ]