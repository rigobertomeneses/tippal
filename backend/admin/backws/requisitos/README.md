# Módulo de Requisitos - API REST

## 📋 Descripción

Sistema completo de gestión de requisitos para usuarios, permitiendo configurar tipos de documentos requeridos y que los usuarios los carguen para su revisión y aprobación.

**Fecha de Migración:** 09 de Octubre 2025
**Autor:** API Migration Team
**Versión:** 1.0

---

## 🚀 Quick Start

### Archivos Disponibles

```
/backws/requisitos/
├── requisitos.php              # CRUD de tipos de requisitos
├── requisitousuario.php        # Gestión de requisitos de usuarios
├── REQUISITOS_API.md           # Documentación completa de la API
├── TABLAS_BD.md                # Estructura de base de datos
└── README.md                   # Este archivo
```

### Ejemplo Rápido

```javascript
// Listar tipos de requisitos configurados
const response = await fetch(
  'https://www.gestiongo.com/admin/backws/requisitos/requisitos.php?token=ABC123&compania=473'
);
const data = await response.json();
console.log(data.data); // Array de requisitos
```

---

## 📚 Documentación

### 1. [REQUISITOS_API.md](REQUISITOS_API.md)
Documentación completa de todos los endpoints:
- Configuración de requisitos (CRUD)
- Requisitos de usuarios (carga, revisión, aprobación)
- Ejemplos de uso
- Códigos de respuesta

### 2. [TABLAS_BD.md](TABLAS_BD.md)
Estructura completa de base de datos:
- Descripción de tablas
- Relaciones
- Índices recomendados
- Consultas optimizadas

---

## 🔑 Endpoints Principales

### Configuración de Requisitos
**Base:** `/backws/requisitos/requisitos.php`

| Método | Descripción |
|--------|-------------|
| GET | Listar tipos de requisitos |
| POST | Crear nuevo tipo de requisito |
| PUT | Actualizar tipo de requisito |
| DELETE | Eliminar tipo de requisito |

### Requisitos de Usuarios
**Base:** `/backws/requisitos/requisitousuario.php`

| Método | Descripción |
|--------|-------------|
| GET | Listar requisitos cargados por usuarios |
| POST | Cargar nuevo documento (usuario) |
| PUT | Cambiar estatus de requisito/archivo |
| DELETE | Eliminar requisito o archivo |

---

## 🔐 Autenticación

Todos los endpoints requieren token de autenticación:

```javascript
// En Query String (GET)
?token=abc123xyz&compania=473

// En Body (POST, PUT, DELETE)
{
  "token": "abc123xyz",
  "compania": "473"
}
```

---

## 👥 Permisos por Perfil

| Perfil | Descripción | Permisos |
|--------|-------------|----------|
| 1 | Admin Sistema | Acceso total |
| 2 | Admin Cuenta | Solo su cuenta |
| 3 | Admin Compañía | Solo su cuenta y compañía |
| 4+ | Otros | Lectura limitada |

---

## 📊 Tablas Principales

1. **`lista`** (tipolista_id = 49) - Tipos de requisitos
2. **`listacuenta`** - Personalización por cuenta/compañía
3. **`listarequisitoperfil`** - Perfil asignado al requisito
4. **`requisito`** - Requisitos cargados por usuarios
5. **`requisitoarchivo`** - Archivos adjuntos (soporte multi-archivo)

---

## 📁 Formatos de Archivo Soportados

- **Imágenes:** JPG, PNG, GIF
- **Documentos:** PDF, DOC, DOCX
- **Hojas de cálculo:** XLS, XLSX
- **Otros:** Cualquier formato (guardado genérico)

**Ubicación:** `/arch/[nombre_unico]`
**Tamaño máximo:** Configurado en PHP

---

## 🎯 Flujo de Uso Típico

### 1. Admin configura requisito
```javascript
POST /requisitos.php
{
  "token": "admin_token",
  "compania": "473",
  "lista_nombre": "Cédula de Identidad",
  "perfil_id": "4"
}
```

### 2. Usuario carga documento
```javascript
POST /requisitousuario.php
{
  "token": "user_token",
  "usuario_id": "7890",
  "tipo_requisito": "1795",
  "archivo": "data:image/jpeg;base64,/9j/4AAQ...",
  "archivo_nombre": "cedula_frente.jpg"
}
```

### 3. Admin revisa y aprueba
```javascript
PUT /requisitousuario.php
{
  "token": "admin_token",
  "accion": "cambiar_estatus",
  "requisito_id": "462",
  "estatus": "54" // Confirmado
}
```

---

## 📝 Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 100 | ✅ Éxito |
| 101 | ⛔ Sin permisos |
| 102 | ⚠️ Datos faltantes |
| 103 | ⚠️ Usuario no activo |
| 104 | ❌ Token no encontrado |
| 105 | ❌ Error en operación |
| 106 | ❌ Registro no encontrado |
| 107 | ⚠️ Registro duplicado |

---

## 🔄 Migración desde Controllers

### Equivalencias de Archivos

| Controller Original | Endpoint API | Método |
|---------------------|--------------|--------|
| `requisitos.php` (listado) | `GET /requisitos.php` | GET |
| `modificarrequisito.php` | `POST/PUT /requisitos.php` | POST/PUT |
| `uploadrequisito.php` | `POST /requisitos.php` | POST |
| `requisitousuario.php` | `GET /requisitousuario.php` | GET |
| `verrequisitousuario.php` | `GET /requisitousuario.php?id=X` | GET |
| `cargarrequisito.php` | `POST /requisitousuario.php` | POST |
| `uploadcargarrequisito.php` | `POST /requisitousuario.php` | POST |

### Funciones AJAX Migradas

| Función Original | Endpoint API |
|------------------|--------------|
| `guardarestatusrequisitousuario()` | `PUT /requisitousuario.php` |
| `cambiarestatusrequisitoarchivo()` | `PUT /requisitousuario.php` |
| `eliminarrequisitoarchivo()` | `DELETE /requisitousuario.php` |
| `eliminarrequisito()` | `DELETE /requisitousuario.php` |

---

## ⚠️ Notas Importantes

### Multi-Tenancy
- Todos los endpoints respetan la jerarquía de perfiles
- Los filtros se aplican automáticamente según `perfil_id`
- Cada cuenta/compañía puede personalizar nombres y descripciones

### Archivos Base64
- Todos los archivos se envían en formato base64
- Incluir prefijo: `data:tipo/subtipo;base64,`
- Se guardan con nombre único: `uniqid() + extensión`
- URL completa: `ObtenerUrlArch($compania_id) + nombre_archivo`

### Borrado Lógico
- Ningún DELETE es físico
- Se marcan: `*_activo = '0'` y `*_eliminado = '1'`
- Registros del sistema (`lista_ppal = 1`) solo se desactivan

### Funciones del Sistema
- `GuardarProcesoLista()` - Maneja inserción con multi-tenancy
- `GuardarProcesoModificarLista()` - Maneja actualización
- `VerificarUsuarioEstatus()` - Actualiza estatus general del usuario
- `ObtenerUrlArch()` - Obtiene URL base de archivos

---

## 🧪 Testing

### Con Postman

```bash
# Importar colección (próximamente)
# requisitos.postman_collection.json
```

### Tests Recomendados

1. **Autenticación**
   - ✅ Token válido
   - ❌ Token inválido
   - ❌ Token vencido

2. **Permisos**
   - ✅ Admin Sistema ve todo
   - ✅ Admin Cuenta ve solo su cuenta
   - ✅ Admin Compañía ve solo su compañía

3. **CRUD Requisitos**
   - ✅ Crear requisito con imagen
   - ✅ Crear requisito sin imagen
   - ✅ Actualizar nombre y descripción
   - ✅ Actualizar imagen
   - ✅ Eliminar requisito sistema (solo inactiva)
   - ✅ Eliminar requisito personalizado (borrado lógico)

4. **Carga de Documentos**
   - ✅ Cargar imagen JPG
   - ✅ Cargar PDF
   - ✅ Cargar múltiples archivos al mismo requisito
   - ❌ Archivo mayor a límite

5. **Gestión de Estatus**
   - ✅ Cambiar estatus a "En revisión"
   - ✅ Cambiar estatus a "Confirmado"
   - ✅ Cambiar estatus a "Rechazado"
   - ✅ Verificar que actualiza estatus general del usuario

---

## 📞 Soporte

Para reportar problemas o solicitar mejoras:

1. Documentar caso de uso
2. Incluir ejemplo de request/response
3. Especificar perfil_id del usuario
4. Adjuntar logs si aplica

---

## 📜 Historial de Cambios

### v1.0 (2025-10-09)
- ✨ Migración completa desde controllers a API REST
- ✅ Implementación de `requisitos.php` (CRUD tipos)
- ✅ Implementación de `requisitousuario.php` (gestión usuarios)
- 📝 Documentación completa (API + BD)
- 🔒 Seguridad: validación de tokens y permisos
- 🌐 Multi-tenancy completo
- 📄 Soporte multi-archivo por requisito

---

## 🎓 Referencias

### Archivos Fuente Originales
- `/admin/controllers/requisitos.php`
- `/admin/controllers/modificarrequisito.php`
- `/admin/controllers/uploadrequisito.php`
- `/admin/controllers/requisitousuario.php`
- `/admin/controllers/verrequisitousuario.php`
- `/admin/controllers/cargarrequisito.php`
- `/admin/controllers/uploadcargarrequisito.php`
- `/admin/lib/ajx_fnci.php` (funciones AJAX)

### Documentación del Sistema
- `/backws/CLAUDE_API.md` - Guía de desarrollo de API
- `/backws/PROCESO_MIGRACION_MODULOS.md` - Proceso de migración
- `/backws/formaspago/` - Módulo de referencia

---

**¡La migración del módulo de Requisitos está completa y lista para producción!** 🎉

Para empezar a usar la API, consulta [REQUISITOS_API.md](REQUISITOS_API.md).
