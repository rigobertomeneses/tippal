# TipPal - Proyecto Completo

Este repositorio contiene el código fuente completo de la aplicación TipPal, incluyendo frontend (app móvil) y backend (API).

## 📁 Estructura del Proyecto

```
tippal/
├── app/                       # Frontend - Aplicación móvil React Native
│   ├── src/                   # Código fuente de la app
│   ├── .env                   # Configuración de la app
│   ├── package.json           # Dependencias Node.js
│   └── README.md              # Documentación del frontend
│
├── backend/                   # Backend - API PHP/MySQL
│   └── admin/
│       ├── backws/            # Endpoints de la API (43 archivos)
│       ├── lib/               # Librerías (funciones, phpmailer, stripe, twilio)
│       ├── models/            # Modelos de datos (lista.php, chat.php)
│       ├── vendor/            # Dependencias Composer
│       ├── .env               # Configuración del backend
│       └── composer.json      # Dependencias PHP
│
├── Entrega.md                 # 📖 DOCUMENTACIÓN DE ENTREGA AL CLIENTE
└── README.md                  # Este archivo
```

## 🚀 Inicio Rápido

### Frontend (Aplicación Móvil)

```bash
# 1. Navegar al directorio del frontend
cd app

# 2. Instalar dependencias
npm install

# 3. Iniciar en modo desarrollo
npx expo start

# 4. Para build de producción
npx expo build:android
npx expo build:ios
```

**Configuración:** Editar `app/.env` para configurar el `COMPANIA_ID` y otras variables.

### Backend (API)

```bash
# 1. Navegar al directorio del backend
cd backend/admin

# 2. Instalar dependencias PHP
composer install

# 3. Configurar base de datos
# Editar .env con las credenciales de MySQL

# 4. Importar base de datos (ver Entrega.md)
mysql -u root -p gestiong_app < database_backup.sql

# 5. Configurar servidor web
# Apuntar DocumentRoot a backend/admin/
```

**Configuración:** Editar `backend/admin/.env` para configurar la base de datos, Stripe, Twilio, etc.

## 📖 Documentación Completa

**IMPORTANTE:** Para documentación técnica completa, arquitectura, API endpoints, credenciales, y guía de despliegue, consultar:

### → [📋 Entrega.md - DOCUMENTACIÓN OFICIAL DE ENTREGA](Entrega.md) ←

Este documento contiene toda la información necesaria para la entrega del proyecto:

### Contenido Técnico:
- ✅ Código fuente completo (Frontend + Backend)
- ✅ Arquitectura del sistema y diagramas de flujo
- ✅ Documentación de todos los endpoints de la API (43 endpoints)
- ✅ Configuración de Stripe (pagos y Cash Out)
- ✅ Configuración de servicios externos (SMS, email, notificaciones)
- ✅ Esquema de base de datos
- ✅ Guía completa de despliegue a producción

### Credenciales y Accesos:
- 🔑 Acceso al cPanel (tippalcorp.com)
- 🔑 Acceso a base de datos MySQL
- 🔑 Credenciales de servicios externos
- 🔑 Configuración de Google Play Console
- 🔑 Información sobre repositorio GitHub

### Traspasos y Transferencias:
- 📱 **Desarrollo con Expo.dev** - Instrucciones de traspaso
- 🏪 **Google Play Console** - Opciones de acceso y transferencia
- 📦 **Repositorio GitHub** - Actualmente público, instrucciones para hacerlo privado
- 📧 **Contacto para traspasos:** info@vtdesarrollo.com

## 🔑 Tecnologías Utilizadas

### Frontend
- **React Native** 0.81.4
- **Expo SDK** 54.0.20
- **Redux Toolkit** - Estado global
- **React Navigation v7** - Navegación
- **Axios** - Cliente HTTP
- **i18next** - Internacionalización (ES/EN)

### Backend
- **PHP** 7.4+
- **MySQL** 5.7+
- **Composer** - Gestor de dependencias
- **Stripe PHP SDK** - Procesamiento de pagos
- **Twilio PHP** - Envío de SMS
- **PHPMailer** - Envío de correos

## 🌐 URLs y Accesos

### Producción
- **App:** TipPal (Play Store / App Store)
- **API:** `https://www.gestiongo.com/admin/backws/`
- **Panel Admin:** `https://www.gestiongo.com/admin/`

## 📱 Características de TipPal

- ✅ Envío y recepción de propinas mediante códigos QR
- ✅ Billetera digital con balance en tiempo real
- ✅ Cash Out: retiro de fondos a cuentas bancarias (Stripe ACH)
- ✅ Historial completo de transacciones
- ✅ Sistema de referidos con comisiones
- ✅ Notificaciones push en tiempo real
- ✅ Múltiples idiomas (ES/EN)
- ✅ Integración con Stripe para pagos seguros

## 🔒 Seguridad

- Autenticación basada en tokens
- Validación de inputs en frontend y backend
- Protección contra SQL injection
- Comunicaciones HTTPS
- Validación de transacciones Stripe

## 📞 Soporte

Para soporte técnico o consultas:
- **Documentación:** Ver [Entrega.md](Entrega.md)

## 📄 Licencia

Código propietario - TipPal © 2025
