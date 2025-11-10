# API de Requisitos - Documentación

## Descripción General

El módulo de **Requisitos** permite gestionar tipos de requisitos configurables y los documentos que los usuarios cargan para cumplir con dichos requisitos. El sistema soporta multi-tenancy completo y maneja perfiles de usuario.

## Estructura de Tablas

### Tablas Principales

1. **`lista`** - Catálogo de tipos de requisitos
   - Filtra por `tipolista_id = 49` para requisitos
   - Soporta registros del sistema (`lista_ppal = 1`) y personalizados (`lista_ppal = 0`)

2. **`listacuenta`** - Personalización de requisitos por cuenta/compañía

3. **`listarequisitoperfil`** - Relación entre requisitos y perfiles de usuario

4. **`requisito`** - Requisitos cargados por usuarios

5. **`requisitoarchivo`** - Archivos adjuntos de cada requisito

### Listas Relacionadas

- **tipolista_id = 49**: Tipos de requisitos
- **tipolista_id = 50**: Estatus de requisitos
  - ID 4: Pendiente (por defecto al crear)
  - Otros: En revisión, Confirmado, Rechazado, etc.

---

## Endpoints Implementados

### Base URL
```
https://www.gestiongo.com/admin/backws/requisitos/
```

---

## 1. Configuración de Requisitos (`requisitos.php`)

Gestiona los tipos de requisitos del sistema.

### 1.1 Listar Requisitos Configurados

**Endpoint:** `GET /requisitos.php`

**Parámetros Query:**
- `token` (requerido): Token de autenticación del usuario
- `compania` (opcional): ID de compañía para filtrar
- `id` (opcional): ID específico de requisito para obtener detalle
- `lid` (opcional): ID de listacuenta para personalización

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisitos obtenidos exitosamente",
  "data": [
    {
      "lista_id": "1795",
      "lista_cod": "REQ001",
      "lista_nombre": "Cédula de identidad",
      "lista_descrip": "Foto de cédula adelante y atrás",
      "lista_img": "67063ea123456.jpg",
      "lista_img_url": "https://www.gestiongo.com/admin/arch/67063ea123456.jpg",
      "lista_orden": "1",
      "lista_activo": "1",
      "lista_ppal": "0",
      "listacuenta_id": "2115",
      "cuenta_id": "123",
      "compania_id": "456",
      "cuenta_nombre": "AgroComercio",
      "compania_nombre": "AgroComercio",
      "perfil_id": "4",
      "perfil_nombre": "Cliente"
    }
  ]
}
```

**Filtrado por Perfil:**
- **Perfil 1 (Admin Sistema)**: Ve todos los requisitos
- **Perfil 2 (Admin Cuenta)**: Ve solo requisitos de su cuenta
- **Perfil 3+ (Admin Compañía/Otros)**: Ve solo requisitos de su cuenta y compañía

---

### 1.2 Crear Nuevo Requisito

**Endpoint:** `POST /requisitos.php`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "token": "usuario_codverif",
  "compania": "473",
  "cuenta": "123",
  "lista_nombre": "Licencia Nacional de Conducir",
  "lista_descrip": "Licencia vigente clase B",
  "lista_cod": "LIC001",
  "lista_orden": 2,
  "perfil_id": "4",
  "imagen": "data:image/jpeg;base64,/9j/4AAQSkZJRg..." // Opcional
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `compania` (opcional): ID de compañía
- `cuenta` (opcional): ID de cuenta (si se omite usa la del token)
- `lista_nombre` (requerido): Nombre del requisito
- `lista_descrip` (opcional): Descripción del requisito
- `lista_cod` (opcional): Código interno
- `lista_orden` (opcional): Orden de visualización (default: 0)
- `perfil_id` (requerido): ID del perfil al que aplica
- `imagen` (opcional): Imagen en base64 (prefijo data:image/...)

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisito creado exitosamente",
  "data": {
    "lista_id": "1800",
    "listacuenta_id": "2120",
    "lista_nombre": "Licencia Nacional de Conducir",
    "lista_img": "67063ff456789.jpg",
    "lista_img_url": "https://www.gestiongo.com/admin/arch/67063ff456789.jpg"
  }
}
```

**Permisos:** Solo perfiles 1, 2 y 3 pueden crear

---

### 1.3 Actualizar Requisito

**Endpoint:** `PUT /requisitos.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "lista_id": "1800",
  "listacuenta_id": "2120",
  "compania": "473",
  "lista_nombre": "Licencia de Conducir Vigente",
  "lista_descrip": "Licencia clase B vigente",
  "lista_orden": 3,
  "perfil_id": "4",
  "imagen": "data:image/jpeg;base64,/9j/4AAQSkZJRg..." // Opcional
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `lista_id` (requerido): ID del requisito a actualizar
- `listacuenta_id` (opcional): ID de personalización
- Resto de campos igual que en POST

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisito actualizado exitosamente",
  "data": {
    "lista_id": "1800",
    "listacuenta_id": "2120",
    "lista_nombre": "Licencia de Conducir Vigente",
    "lista_img": "67064aa789012.jpg",
    "lista_img_url": "https://www.gestiongo.com/admin/arch/67064aa789012.jpg"
  }
}
```

**Permisos:** Solo perfiles 1, 2 y 3 pueden modificar

---

### 1.4 Eliminar Requisito

**Endpoint:** `DELETE /requisitos.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "lista_id": "1800",
  "cuenta": "123",
  "compania": "473"
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `lista_id` (requerido): ID del requisito a eliminar
- `cuenta` (opcional): ID de cuenta
- `compania` (opcional): ID de compañía

**Comportamiento:**
- Si `lista_ppal = 1` (sistema): Solo marca como inactivo
- Si `lista_ppal = 0` (personalizado): Hace borrado lógico completo

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisito eliminado exitosamente"
}
```

**Permisos:** Solo perfiles 1, 2 y 3 pueden eliminar

---

## 2. Requisitos de Usuarios (`requisitousuario.php`)

Gestiona los requisitos cargados por usuarios.

### 2.1 Listar Requisitos de Usuarios

**Endpoint:** `GET /requisitousuario.php`

**Parámetros Query:**
- `token` (requerido): Token de autenticación
- `compania` (opcional): ID de compañía para filtrar
- `id` (opcional): ID específico de requisito (incluye archivos adjuntos)
- `estatus` (opcional): ID de estatus para filtrar
- `fecha_desde` (opcional): Fecha inicio en formato YYYY-MM-DD
- `fecha_hasta` (opcional): Fecha fin en formato YYYY-MM-DD
- `usuario_id` (opcional): ID de usuario para filtrar
- `tipo_requisito` (opcional): ID del tipo de requisito (lista_id)
- `pagina` (opcional): Número de página (default: 1)
- `limite` (opcional): Registros por página (default: 20)

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisitos obtenidos exitosamente",
  "data": [
    {
      "requisito_id": "462",
      "l_requisitolista_id": "1795",
      "requisito_descrip": "",
      "requisito_cantarchivos": "1",
      "cuenta_id": "123",
      "compania_id": "473",
      "requisito_activo": "1",
      "requisito_fechareg": "01/10/2025 21:42:45",
      "l_estatus_id": "52",
      "usuario_id": "7890",
      "usuario_codigo": "U001",
      "usuario_email": "ulises.perez@example.com",
      "usuario_nombre": "Ulises",
      "usuario_apellido": "Perez",
      "usuario_telf": "8095551234",
      "usuario_documento": "40212345678",
      "cuenta_nombre": "Sateli Taxi",
      "cuenta_apellido": "",
      "compania_nombre": "SateliTaxi STX App",
      "requisitolista_id": "1795",
      "requisitolista_nombre": "Licencia Nacional de Conducir",
      "estatus_nombre": "En revisión",
      "tipoarchivo_nombre": "",
      "tipoarchivo_img": ""
    }
  ],
  "pagination": {
    "pagina": 1,
    "limite": 20,
    "total": 156,
    "total_paginas": 8
  }
}
```

**Con ID específico (incluye archivos):**
```json
{
  "code": 100,
  "message": "Requisitos obtenidos exitosamente",
  "data": [
    {
      "requisito_id": "462",
      "...": "...",
      "archivos": [
        {
          "requisitoarch_id": "789",
          "requisitoarch_arch": "67063ff123456.jpg",
          "requisitoarch_nombre": "IMG_20251001_204802898.jpg",
          "l_tipoarchivo_id": "61",
          "requisitoarch_activo": "1",
          "requisitoarch_fechareg": "01/10/2025 21:54:23",
          "requisitoarch_url": "https://www.gestiongo.com/admin/arch/67063ff123456.jpg"
        }
      ]
    }
  ]
}
```

---

### 2.2 Cargar Requisito de Usuario

**Endpoint:** `POST /requisitousuario.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "compania": "473",
  "cuenta": "123",
  "usuario_id": "7890",
  "tipo_requisito": "1795",
  "archivo": "data:image/jpeg;base64,/9j/4AAQSkZJRg...",
  "archivo_nombre": "licencia_frente.jpg"
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `compania` (requerido): ID de compañía
- `cuenta` (opcional): ID de cuenta (si se omite usa la del token)
- `usuario_id` (requerido): ID del usuario que carga el requisito
- `tipo_requisito` (requerido): ID del tipo de requisito (lista_id)
- `archivo` (requerido): Archivo en base64
- `archivo_nombre` (opcional): Nombre original del archivo

**Tipos de archivo soportados:**
- Imágenes: JPEG, PNG, GIF
- Documentos: PDF, DOC, DOCX, XLS, XLSX
- Otros: Cualquier archivo (se guarda con extensión genérica)

**Comportamiento:**
- Si el usuario ya tiene un requisito de ese tipo, agrega el archivo al existente
- Si no existe, crea nuevo registro de requisito con estatus "Pendiente"
- Actualiza automáticamente el contador `requisito_cantarchivos`

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisito cargado exitosamente",
  "data": {
    "requisito_id": "462",
    "archivo_nombre": "licencia_frente.jpg",
    "archivo_guardado": "67063ff123456.jpg",
    "archivo_url": "https://www.gestiongo.com/admin/arch/67063ff123456.jpg",
    "total_archivos": 2
  }
}
```

---

### 2.3 Cambiar Estatus de Requisito

**Endpoint:** `PUT /requisitousuario.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "accion": "cambiar_estatus",
  "requisito_id": "462",
  "estatus": "53"
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `accion` (requerido): Debe ser "cambiar_estatus"
- `requisito_id` (requerido): ID del requisito
- `estatus` (requerido): Nuevo ID de estatus (lista_id de tipolista_id=50)

**Funcionalidad adicional:**
- Ejecuta automáticamente `VerificarUsuarioEstatus()` para actualizar el estatus general del usuario

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Estatus actualizado exitosamente"
}
```

**Permisos:** Solo perfiles 1, 2 y 3

---

### 2.4 Cambiar Estatus de Archivo Adjunto

**Endpoint:** `PUT /requisitousuario.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "accion": "cambiar_estatus_archivo",
  "requisitoarch_id": "789",
  "estatus": "0"
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `accion` (requerido): Debe ser "cambiar_estatus_archivo"
- `requisitoarch_id` (requerido): ID del archivo adjunto
- `estatus` (requerido): "0" = inactivo, "1" = activo

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Estatus del archivo actualizado exitosamente"
}
```

**Permisos:** Solo perfiles 1, 2 y 3

---

### 2.5 Eliminar Requisito de Usuario

**Endpoint:** `DELETE /requisitousuario.php`

**Body:**
```json
{
  "token": "usuario_codverif",
  "tipo": "requisito",
  "requisito_id": "462"
}
```

**Campos:**
- `token` (requerido): Token de autenticación
- `tipo` (requerido): "requisito" o "archivo"
- `requisito_id` (requerido si tipo="requisito"): ID del requisito
- `requisitoarch_id` (requerido si tipo="archivo"): ID del archivo

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Requisito eliminado exitosamente"
}
```

**Para eliminar solo un archivo:**
```json
{
  "token": "usuario_codverif",
  "tipo": "archivo",
  "requisitoarch_id": "789"
}
```

**Respuesta Exitosa (code: 100):**
```json
{
  "code": 100,
  "message": "Archivo eliminado exitosamente"
}
```

**Permisos:** Solo perfiles 1, 2 y 3

---

## Códigos de Respuesta

| Código | Descripción |
|--------|-------------|
| 100 | Éxito |
| 101 | Sin permisos |
| 102 | Datos faltantes |
| 103 | Usuario no activo |
| 104 | Token no encontrado |
| 105 | Error en operación |
| 106 | Registro no encontrado |
| 107 | Registro duplicado |

---

## Ejemplos de Uso Completos

### Flujo 1: Configurar un nuevo tipo de requisito

```javascript
// 1. Crear tipo de requisito
const crearRequisito = async () => {
  const response = await fetch('https://www.gestiongo.com/admin/backws/requisitos/requisitos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      token: 'abc123xyz',
      compania: '473',
      lista_nombre: 'Certificado de Antecedentes Penales',
      lista_descrip: 'Certificado vigente emitido por DNMC',
      lista_orden: 5,
      perfil_id: '4'
    })
  });

  const data = await response.json();
  console.log(data);
  // { code: 100, message: "Requisito creado exitosamente", data: {...} }
};
```

### Flujo 2: Usuario carga un documento

```javascript
// 2. Usuario carga su certificado
const cargarDocumento = async (base64File) => {
  const response = await fetch('https://www.gestiongo.com/admin/backws/requisitos/requisitousuario.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      token: 'usuario_token',
      compania: '473',
      usuario_id: '7890',
      tipo_requisito: '1805',
      archivo: base64File,
      archivo_nombre: 'certificado_penales.pdf'
    })
  });

  const data = await response.json();
  console.log(data);
  // { code: 100, message: "Requisito cargado exitosamente", data: {...} }
};
```

### Flujo 3: Admin revisa y aprueba

```javascript
// 3. Admin cambia estatus a "Confirmado"
const aprobarRequisito = async () => {
  const response = await fetch('https://www.gestiongo.com/admin/backws/requisitos/requisitousuario.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      token: 'admin_token',
      accion: 'cambiar_estatus',
      requisito_id: '462',
      estatus: '54' // ID de "Confirmado"
    })
  });

  const data = await response.json();
  console.log(data);
  // { code: 100, message: "Estatus actualizado exitosamente" }
};
```

### Flujo 4: Listar requisitos pendientes

```javascript
// 4. Listar requisitos en revisión
const listarPendientes = async () => {
  const params = new URLSearchParams({
    token: 'admin_token',
    compania: '473',
    estatus: '52', // En revisión
    pagina: '1',
    limite: '50'
  });

  const response = await fetch(`https://www.gestiongo.com/admin/backws/requisitos/requisitousuario.php?${params}`);
  const data = await response.json();
  console.log(data);
  // { code: 100, message: "...", data: [...], pagination: {...} }
};
```

---

## Notas Importantes

### Multi-Tenancy
- Todos los endpoints respetan la jerarquía de perfiles
- Los filtros se aplican automáticamente según `perfil_id`:
  - **Perfil 1**: Acceso total
  - **Perfil 2**: Solo su cuenta
  - **Perfil 3+**: Solo su cuenta y compañía

### Imágenes y Archivos
- Todos los archivos se guardan en `/arch/` con nombre único (uniqid)
- Las URLs completas se generan con `ObtenerUrlArch($compania_id)`
- Formatos soportados: JPG, PNG, GIF, PDF, DOC, DOCX, XLS, XLSX
- Los archivos base64 deben incluir el prefijo `data:tipo/subtipo;base64,`

### Funciones del Sistema Utilizadas
- `GuardarProcesoLista()`: Maneja inserción de listas con multi-tenancy
- `GuardarProcesoModificarLista()`: Maneja actualización de listas
- `VerificarUsuarioEstatus()`: Actualiza el estatus general del usuario
- `ObtenerUrlArch()`: Obtiene la URL base de archivos por compañía
- `formatoFechaHoraBd()`: Formatea fecha/hora para BD

### Borrado Lógico
- Todos los DELETE son lógicos, no físicos
- Se marcan campos: `*_activo = '0'` y `*_eliminado = '1'`
- Los registros del sistema (`lista_ppal = 1`) solo se desactivan

---

## Migración desde Controllers

### Equivalencias

| Controller Original | Endpoint API | Método |
|---------------------|--------------|--------|
| `requisitos.php` (listado) | `GET /requisitos.php` | GET |
| `modificarrequisito.php?n=1` | `POST /requisitos.php` | POST |
| `modificarrequisito.php?id=X` | `PUT /requisitos.php` | PUT |
| `uploadrequisito.php` | `POST /requisitos.php` | POST |
| `requisitousuario.php` (listado) | `GET /requisitousuario.php` | GET |
| `verrequisitousuario.php?id=X` | `GET /requisitousuario.php?id=X` | GET |
| `cargarrequisito.php` | `POST /requisitousuario.php` | POST |
| `uploadcargarrequisito.php` | `POST /requisitousuario.php` | POST |
| `cambiarestatusrequisito()` | `PUT /requisitousuario.php` | PUT |
| `eliminarrequisito()` | `DELETE /requisitousuario.php` | DELETE |

### Funciones AJAX Migradas

| Función AJAX Original | Equivalente API |
|-----------------------|-----------------|
| `guardarestatusrequisitousuario()` | `PUT /requisitousuario.php` (accion: "cambiar_estatus") |
| `cambiarestatusrequisitoarchivo()` | `PUT /requisitousuario.php` (accion: "cambiar_estatus_archivo") |
| `eliminarrequisitoarchivo()` | `DELETE /requisitousuario.php` (tipo: "archivo") |
| `eliminarrequisito()` | `DELETE /requisitousuario.php` (tipo: "requisito") |

---

## Próximos Pasos

### Para Frontend
1. Reemplazar llamadas AJAX por fetch/axios a estos endpoints
2. Manejar respuestas con códigos 100-107
3. Implementar paginación en listados
4. Convertir archivos a base64 antes de enviar

### Para Testing
1. Probar cada endpoint con Postman
2. Verificar permisos por perfil
3. Validar multi-tenancy
4. Probar carga de diferentes tipos de archivo

---

**Autor:** API Migration Team
**Fecha:** 2025-10-09
**Versión:** 1.0
**Módulo Original:** controllers/requisitos.php, requisitousuario.php, modificarrequisito.php, cargarrequisito.php
