# API de Retiros - GestionGo

**Fecha de Creación**: 07/10/2025
**Versión**: 1.0
**Base URL**: `https://www.gestiongo.com/admin/backws/retiros`

---

## 📋 Tabla de Contenidos

- [Descripción General](#descripción-general)
- [Autenticación](#autenticación)
- [Endpoints Disponibles](#endpoints-disponibles)
  - [GET /retiros](#get-retiros)
  - [GET /retiros?id={id}](#get-retirosidid)
  - [PUT /cambiarestatus](#put-cambiarestatus)
  - [PUT /cambiaractivo](#put-cambiaractivo)
  - [DELETE /eliminar](#delete-eliminar)
- [Códigos de Respuesta](#códigos-de-respuesta)
- [Filtros por Perfil](#filtros-por-perfil)
- [Procesamiento de Balance](#procesamiento-de-balance)
- [Ejemplos de Uso](#ejemplos-de-uso)

---

## Descripción General

Esta API REST proporciona endpoints para gestionar los **retiros de balance** de usuarios en GestionGo.

### Características Principales:

- ✅ **Autenticación por Token**: Validación de usuario en cada request
- ✅ **Multi-Tenancy**: Filtros automáticos según perfil de usuario
- ✅ **Procesamiento de Balance**: Actualización automática de `usuariobalance` al aprobar/rechazar
- ✅ **Notificaciones Push**: Envío automático vía Expo SDK
- ✅ **Eliminación Lógica**: Los retiros nunca se eliminan físicamente
- ✅ **UTF-8 Encoding**: Soporte completo para caracteres especiales
- ✅ **Paginación**: Listados con limit/offset configurables

### Tabla Principal:

- **usuariobalanceretiro**: Almacena los retiros solicitados por usuarios
- **Campos clave**: `usuariobalanceretiro_id`, `usuariobalanceretiro_monto`, `l_estatus_id`, `usuariobalanceretiro_procesado`

### Tablas Relacionadas:

- **usuarioretiro**: Datos bancarios del usuario (banco, cuenta, titular)
- **usuariobalance**: Balance de usuario (bloqueado, disponible, total)
- **usuario**: Información del usuario solicitante
- **lista**: Catálogos (estatus, formas de pago, monedas)

---

## Autenticación

Todos los endpoints requieren autenticación mediante token.

### Parámetros de Autenticación:

| Parámetro | Tipo | Ubicación | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | Query/Body | Token de verificación del usuario (`usuario_codverif`) |
| `compania` | integer | Query/Body | ID de la compañía del usuario |

### Validación:

```sql
SELECT usuario_id, perfil_id, cuenta_id
FROM usuario
WHERE usuario_activo = '1'
  AND usuario_codverif = '{token}'
  AND compania_id = '{compania}'
```

**Respuesta si token inválido:**

```json
{
  "code": 103,
  "message": "Usuario / Token no activo",
  "data": []
}
```

---

## Endpoints Disponibles

### GET /retiros

**Descripción**: Obtiene un listado de retiros con filtros opcionales.

**URL**: `https://www.gestiongo.com/admin/backws/retiros/retiros`

#### Parámetros Query String:

| Parámetro | Tipo | Requerido | Default | Descripción |
|-----------|------|-----------|---------|-------------|
| `token` | string | ✅ Sí | - | Token de autenticación |
| `compania` | integer | ✅ Sí | - | ID de la compañía |
| `page` | integer | ❌ No | 1 | Número de página |
| `limit` | integer | ❌ No | 50 | Registros por página (máx 100) |
| `estatus` | integer | ❌ No | - | Filtrar por ID de estatus (lista 64) |
| `fechadesde` | string | ❌ No | - | Fecha inicio (dd/mm/yyyy o yyyy-mm-dd) |
| `fechahasta` | string | ❌ No | - | Fecha fin (dd/mm/yyyy o yyyy-mm-dd) |
| `search` | string | ❌ No | - | Búsqueda por nombre, banco, cuenta, titular |

#### Ejemplo de Request:

```bash
GET https://www.gestiongo.com/admin/backws/retiros/retiros?token=democliente&compania=381&limit=10&page=1
```

#### Ejemplo de Response (200 OK):

```json
{
  "code": 100,
  "message": "Retiros obtenidos correctamente",
  "data": [
    {
      "usuariobalanceretiro_id": "1234",
      "usuariobalanceretiro_monto": "100000",
      "usuariobalanceretiro_fecha": "06/10/2025",
      "usuariobalanceretiro_fechareg": "06/10/2025 15:30:00",
      "usuariobalanceretiro_referencia": "REF-001",
      "usuariobalanceretiro_comentario": "Retiro mensual",
      "usuariobalanceretiro_activo": "1",
      "usuariobalanceretiro_procesado": "0",
      "usuario_id": "789",
      "usuario_nombre": "Juan",
      "usuario_apellido": "Pérez",
      "usuario_img": "https://www.gestiongo.com/admin/fotos/381/usuario123.jpg",
      "cuenta_id": "456",
      "cuenta_codigo": "CTA-001",
      "cuenta_nombre": "Empresa Demo",
      "compania_id": "381",
      "compania_nombre": "VT Gestión",
      "l_estatus_id": "1456",
      "estatus_nombre": "Pendiente",
      "estatus_cod": "1",
      "l_formapago_id": "234",
      "formapago_nombre": "Transferencia Bancaria",
      "l_moneda_id": "567",
      "moneda_nombre": "Pesos",
      "moneda_siglas": "$",
      "usuarioretiro_banco": "Banco Nacional",
      "usuarioretiro_titular": "Juan Pérez",
      "usuarioretiro_tipocuenta": "Ahorro",
      "usuarioretiro_nrocuenta": "1234567890"
    }
  ],
  "total": 25,
  "page": 1,
  "limit": 10
}
```

#### Filtros Aplicados Según Perfil:

| Perfil | Descripción | WHERE aplicado |
|--------|-------------|----------------|
| 1 | Administrador del Sistema | Sin filtros (ve todo) |
| 2 | Administrador de Cuenta | `cuenta_id = {cuenta_usuario}` |
| 3, 7 | Admin Compañía / Empleados | `cuenta_id = {cuenta_usuario} AND compania_id = {compania}` |
| Otros | Usuario normal | `cuenta_id = {cuenta_usuario} AND compania_id = {compania} AND usuario_id = {usuario}` |

---

### GET /retiros?id={id}

**Descripción**: Obtiene los detalles completos de un retiro específico.

**URL**: `https://www.gestiongo.com/admin/backws/retiros/retiros?id={id}`

#### Parámetros Query String:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | ✅ Sí | Token de autenticación |
| `compania` | integer | ✅ Sí | ID de la compañía |
| `id` | integer | ✅ Sí | ID del retiro (`usuariobalanceretiro_id`) |

#### Ejemplo de Request:

```bash
GET https://www.gestiongo.com/admin/backws/retiros/retiros?token=democliente&compania=381&id=1234
```

#### Ejemplo de Response (200 OK):

```json
{
  "code": 100,
  "message": "Retiro obtenido correctamente",
  "data": {
    "usuariobalanceretiro_id": "1234",
    "usuariobalanceretiro_monto": "100000",
    "usuariobalanceretiro_fecha": "06/10/2025",
    "usuariobalanceretiro_fechareg": "06/10/2025 15:30:00",
    "usuariobalanceretiro_referencia": "REF-001",
    "usuariobalanceretiro_comentario": "Retiro mensual",
    "usuariobalanceretiro_activo": "1",
    "usuariobalanceretiro_procesado": "0",
    "usuario_id": "789",
    "usuario_nombre": "Juan",
    "usuario_apellido": "Pérez",
    "usuario_img": "https://www.gestiongo.com/admin/fotos/381/usuario123.jpg",
    "cuenta_id": "456",
    "cuenta_codigo": "CTA-001",
    "cuenta_nombre": "Empresa Demo",
    "compania_id": "381",
    "compania_nombre": "VT Gestión",
    "compania_urlweb": "",
    "l_estatus_id": "1456",
    "estatus_nombre": "Pendiente",
    "estatus_cod": "1",
    "l_formapago_id": "234",
    "formapago_nombre": "Transferencia Bancaria",
    "l_moneda_id": "567",
    "moneda_nombre": "Pesos",
    "moneda_siglas": "$",
    "usuarioretiro_id": "890",
    "usuarioretiro_banco": "Banco Nacional",
    "usuarioretiro_titular": "Juan Pérez",
    "usuarioretiro_tipocuenta": "Ahorro",
    "usuarioretiro_documento": "12345678",
    "usuarioretiro_nrocuenta": "1234567890"
  }
}
```

#### Response si no existe (404):

```json
{
  "code": 106,
  "message": "Retiro no encontrado",
  "data": []
}
```

---

### PUT /cambiarestatus

**Descripción**: Cambia el estatus de un retiro. Si el estatus es "Aprobado" (código 2) o "Rechazado" (código 3), procesa automáticamente el balance del usuario.

**URL**: `https://www.gestiongo.com/admin/backws/retiros/cambiarestatus`

**Método**: `PUT`

**Content-Type**: `application/json`

#### Body Parameters:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | ✅ Sí | Token de autenticación |
| `compania` | integer | ✅ Sí | ID de la compañía |
| `usuariobalanceretiro_id` | integer | ✅ Sí | ID del retiro |
| `estatus` | integer | ✅ Sí | Nuevo ID de estatus (de lista 64) |

#### Ejemplo de Request:

```bash
PUT https://www.gestiongo.com/admin/backws/retiros/cambiarestatus
Content-Type: application/json

{
  "token": "democliente",
  "compania": 381,
  "usuariobalanceretiro_id": 1234,
  "estatus": 1457
}
```

#### Ejemplo de Response (200 OK):

```json
{
  "code": 100,
  "message": "Cambiado el Estatus Correctamente y se procesó el retiro",
  "data": {
    "usuariobalanceretiro_id": 1234,
    "l_estatus_id": 1457,
    "estatus_nombre": "Aprobado"
  }
}
```

#### Procesamiento de Balance:

##### Si estatus es "Aprobado" (código 2, lista 64):

1. **Obtiene balance actual** del usuario
2. **Resta del bloqueado**: `usuariobalance_bloqueado = usuariobalance_bloqueado - monto`
3. **Resta del total**: `usuariobalance_total = usuariobalance_total - monto`
4. **Marca como procesado**: `usuariobalanceretiro_procesado = 1`
5. **Envía notificación push**: "Retiro Aprobado"

```sql
UPDATE usuariobalance
SET usuariobalance_bloqueado = usuariobalance_bloqueado - {monto},
    usuariobalance_total = usuariobalance_total - {monto}
WHERE usuario_id = {usuario} AND compania_id = {compania}
```

##### Si estatus es "Rechazado" (código 3, lista 64):

1. **Obtiene balance actual** del usuario
2. **Resta del bloqueado**: `usuariobalance_bloqueado = usuariobalance_bloqueado - monto`
3. **Suma al disponible**: `usuariobalance_disponible = usuariobalance_disponible + monto`
4. **Marca como procesado**: `usuariobalanceretiro_procesado = 1`
5. **Envía notificación push**: "Retiro Rechazado - Monto devuelto"

```sql
UPDATE usuariobalance
SET usuariobalance_bloqueado = usuariobalance_bloqueado - {monto},
    usuariobalance_disponible = usuariobalance_disponible + {monto}
WHERE usuario_id = {usuario} AND compania_id = {compania}
```

#### Diagrama de Flujo del Balance:

```
SOLICITUD DE RETIRO (Usuario solicita retiro)
├─ Balance Disponible: -100.000
├─ Balance Bloqueado: +100.000
└─ Balance Total: sin cambios

APROBACIÓN (Código 2)
├─ Balance Bloqueado: -100.000
├─ Balance Total: -100.000
└─ Balance Disponible: sin cambios

RECHAZO (Código 3)
├─ Balance Bloqueado: -100.000
├─ Balance Disponible: +100.000
└─ Balance Total: sin cambios
```

---

### PUT /cambiaractivo

**Descripción**: Activa o desactiva un retiro sin eliminarlo. El retiro sigue siendo visible en el sistema.

**URL**: `https://www.gestiongo.com/admin/backws/retiros/cambiaractivo`

**Método**: `PUT`

**Content-Type**: `application/json`

#### Body Parameters:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | ✅ Sí | Token de autenticación |
| `compania` | integer | ✅ Sí | ID de la compañía |
| `usuariobalanceretiro_id` | integer | ✅ Sí | ID del retiro |
| `activo` | integer | ✅ Sí | 1 = Activar, 0 = Desactivar |

#### Ejemplo de Request (Desactivar):

```bash
PUT https://www.gestiongo.com/admin/backws/retiros/cambiaractivo
Content-Type: application/json

{
  "token": "democliente",
  "compania": 381,
  "usuariobalanceretiro_id": 1234,
  "activo": 0
}
```

#### Ejemplo de Response (200 OK):

```json
{
  "code": 100,
  "message": "Retiro desactivado correctamente",
  "data": {
    "usuariobalanceretiro_id": 1234,
    "usuariobalanceretiro_activo": 0
  }
}
```

#### Diferencia entre Desactivar y Eliminar:

| Acción | Campo `activo` | Campo `eliminado` | Visible en Listados |
|--------|----------------|-------------------|---------------------|
| **Desactivar** | 0 | 0 | ✅ Sí |
| **Eliminar** | 0 | 1 | ❌ No |

---

### DELETE /eliminar

**Descripción**: Realiza una **eliminación lógica** del retiro. El registro permanece en la base de datos pero no se muestra en los listados.

**URL**: `https://www.gestiongo.com/admin/backws/retiros/eliminar`

**Método**: `DELETE`

**Content-Type**: `application/json`

#### Body Parameters:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | ✅ Sí | Token de autenticación |
| `compania` | integer | ✅ Sí | ID de la compañía |
| `usuariobalanceretiro_id` | integer | ✅ Sí | ID del retiro |

#### Ejemplo de Request:

```bash
DELETE https://www.gestiongo.com/admin/backws/retiros/eliminar
Content-Type: application/json

{
  "token": "democliente",
  "compania": 381,
  "usuariobalanceretiro_id": 1234
}
```

#### Ejemplo de Response (200 OK):

```json
{
  "code": 100,
  "message": "Retiro eliminado correctamente",
  "data": {
    "usuariobalanceretiro_id": 1234
  }
}
```

#### Operación SQL Ejecutada:

```sql
UPDATE usuariobalanceretiro
SET usuariobalanceretiro_activo = '0',
    usuariobalanceretiro_eliminado = '1'
WHERE usuariobalanceretiro_id = {id}
```

⚠️ **IMPORTANTE**: Esta es una eliminación **lógica**, no física. El registro permanece en la BD pero con `usuariobalanceretiro_eliminado = 1`.

---

## Códigos de Respuesta

| Código | Significado | Descripción |
|--------|-------------|-------------|
| **100** | ✅ Éxito | Operación completada correctamente |
| **101** | ⚠️ Sin permisos | Usuario sin permisos (default) |
| **102** | ⚠️ Parámetros faltantes | Faltan parámetros requeridos |
| **103** | 🔒 Token inválido | Usuario/Token no activo |
| **105** | ❌ Error DB | Error en operación de base de datos |
| **106** | 🔍 No encontrado | Retiro no existe o fue eliminado |
| **107** | ⚠️ Estatus inválido | ID de estatus no existe en lista 64 |

---

## Filtros por Perfil

La API aplica automáticamente filtros según el perfil del usuario autenticado:

### Perfil 1: Administrador del Sistema

```sql
-- Sin filtros, ve TODOS los retiros del sistema
WHERE usuariobalanceretiro.usuariobalanceretiro_eliminado = '0'
```

### Perfil 2: Administrador de Cuenta

```sql
-- Solo retiros de su cuenta
WHERE usuariobalanceretiro.usuariobalanceretiro_eliminado = '0'
  AND usuariobalanceretiro.cuenta_id = '{cuenta_id}'
```

### Perfil 3 y 7: Admin Compañía / Empleados

```sql
-- Solo retiros de su cuenta y compañía
WHERE usuariobalanceretiro.usuariobalanceretiro_eliminado = '0'
  AND usuariobalanceretiro.cuenta_id = '{cuenta_id}'
  AND usuariobalanceretiro.compania_id = '{compania_id}'
```

### Otros Perfiles: Usuario Normal

```sql
-- Solo sus propios retiros
WHERE usuariobalanceretiro.usuariobalanceretiro_eliminado = '0'
  AND usuariobalanceretiro.cuenta_id = '{cuenta_id}'
  AND usuariobalanceretiro.compania_id = '{compania_id}'
  AND usuariobalanceretiro.usuario_id = '{usuario_id}'
```

---

## Procesamiento de Balance

### Tablas Involucradas:

1. **usuariobalanceretiro**: Registro del retiro
2. **usuariobalance**: Balance del usuario (3 campos clave)

### Campos de Balance:

| Campo | Descripción |
|-------|-------------|
| `usuariobalance_disponible` | Monto que el usuario puede retirar |
| `usuariobalance_bloqueado` | Monto en retiros pendientes de aprobación |
| `usuariobalance_total` | Suma de disponible + bloqueado |

### Flujo Completo de un Retiro:

```
1. SOLICITUD (Frontend/App)
   ├─ Usuario solicita retiro de $100.000
   ├─ INSERT en usuariobalanceretiro
   ├─ UPDATE usuariobalance:
   │   ├─ disponible = disponible - 100.000
   │   ├─ bloqueado = bloqueado + 100.000
   │   └─ total = sin cambios
   └─ Estado: Pendiente (código 1, lista 64)

2. APROBACIÓN (Admin via PUT /cambiarestatus)
   ├─ PUT estatus = {id_aprobado} (código 2)
   ├─ UPDATE usuariobalance:
   │   ├─ bloqueado = bloqueado - 100.000
   │   ├─ total = total - 100.000
   │   └─ disponible = sin cambios
   ├─ UPDATE usuariobalanceretiro_procesado = 1
   └─ PUSH: "Retiro Aprobado"

3. RECHAZO (Admin via PUT /cambiarestatus)
   ├─ PUT estatus = {id_rechazado} (código 3)
   ├─ UPDATE usuariobalance:
   │   ├─ bloqueado = bloqueado - 100.000
   │   ├─ disponible = disponible + 100.000
   │   └─ total = sin cambios (se devuelve al disponible)
   ├─ UPDATE usuariobalanceretiro_procesado = 1
   └─ PUSH: "Retiro Rechazado - Monto devuelto"
```

### Prevención de Doble Procesamiento:

```php
if ($retiro_procesado == "0") {
    // Solo procesar si no ha sido procesado antes
    // ... lógica de balance ...
}
```

---

## Ejemplos de Uso

### Ejemplo 1: Listar Retiros del Mes Actual

```bash
curl -X GET "https://www.gestiongo.com/admin/backws/retiros/retiros?token=democliente&compania=381&fechadesde=01/10/2025&fechahasta=31/10/2025&limit=50&page=1"
```

### Ejemplo 2: Buscar Retiros por Banco

```bash
curl -X GET "https://www.gestiongo.com/admin/backws/retiros/retiros?token=democliente&compania=381&search=Banco%20Nacional"
```

### Ejemplo 3: Aprobar un Retiro

```bash
curl -X PUT "https://www.gestiongo.com/admin/backws/retiros/cambiarestatus" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "usuariobalanceretiro_id": 1234,
    "estatus": 1457
  }'
```

### Ejemplo 4: Rechazar un Retiro

```bash
curl -X PUT "https://www.gestiongo.com/admin/backws/retiros/cambiarestatus" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "usuariobalanceretiro_id": 1234,
    "estatus": 1458
  }'
```

### Ejemplo 5: Eliminar un Retiro

```bash
curl -X DELETE "https://www.gestiongo.com/admin/backws/retiros/eliminar" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "usuariobalanceretiro_id": 1234
  }'
```

---

## Notas de Implementación

### Encoding UTF-8

Todos los textos recibidos y enviados usan encoding UTF-8:

```php
// Input
$search_decoded = utf8_decode($search);

// Output
"usuario_nombre" => utf8_encode($valor["usuario_nombre"])
```

### URLs Dinámicas

Las imágenes usan `ObtenerUrlArch()` para generar URLs dinámicas:

```php
"usuario_img" => ($usuario_img != "" && $usuario_img != "1.png")
  ? ObtenerUrlArch($compania_id) . "/" . $usuario_img
  : ""
```

### Paginación

```php
$offset = ($page - 1) * $limit;
// SQL: ... LIMIT {limit} OFFSET {offset}
```

### Conversión de Fechas

```php
// Acepta: dd/mm/yyyy o yyyy-mm-dd
if (strpos($fechadesde, '/') !== false) {
    $fechadesde = ConvertirFechaNormalFechaBd($fechadesde); // dd/mm/yyyy -> yyyy-mm-dd
}
```

---

## Migración desde Controllers

Esta API reemplaza la funcionalidad de:

- `controllers/retiros.php` → `GET /retiros`
- `controllers/verretiro.php` → `GET /retiros?id={id}`
- `lib/ajx_fnci.php::guardarestatusretiro()` → `PUT /cambiarestatus`
- `lib/ajx_fnci.php::cambiarestatusretiro()` → `PUT /cambiaractivo`
- `lib/ajx_fnci.php::eliminarretiro()` → `DELETE /eliminar`

---

## Recursos Adicionales

- **Colección de Postman**: `GestionGo_API_Retiros.postman_collection.json`
- **Pruebas Documentadas**: `PRUEBAS_ENDPOINTS.md` (crear después de testing)
- **Código Fuente**: `/backws/retiros/`

---

**Última Actualización**: 07/10/2025
**Autor**: Claude Code
**Versión de API**: 1.0
