# API de Depósitos - GestionGo

**Fecha de Creación**: 07/10/2025
**Estado**: ✅ Implementado

## 📋 TABLA DE CONTENIDOS

1. [Descripción General](#descripción-general)
2. [Endpoints Disponibles](#endpoints-disponibles)
3. [Modelo de Datos](#modelo-de-datos)
4. [Reglas de Negocio](#reglas-de-negocio)
5. [Ejemplos de Uso](#ejemplos-de-uso)

---

## Descripción General

Esta API permite gestionar los depósitos de usuarios en el sistema GestionGo. Los depósitos son registrados en la tabla `pago` y pueden tener diferentes estados que afectan el balance del usuario.

### Archivos Migrados

- **controllers/depositos.php** → `/backws/depositos/depositos.php` (GET)
- **controllers/verpagodeposito.php** → `/backws/depositos/depositos.php` (GET por ID) + `/backws/depositos/cambiarestatus.php` (PUT)
- **lib/ajx_fnci.php::eliminarpago()** → `/backws/depositos/eliminar.php` (DELETE)
- **lib/ajx_fnci.php::cambiarestatuspago()** → `/backws/depositos/cambiaractivo.php` (PUT)

---

## Endpoints Disponibles

### 1. **GET** `/backws/depositos/depositos` - Listar Depósitos

Obtiene un listado de depósitos con filtros y paginación.

#### Parámetros (Query String)

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | Sí | Token de autenticación del usuario |
| `compania` | int | Sí | ID de la compañía |
| `estatus` | int | No | Filtro por ID de estatus (lista 55) |
| `fechadesde` | string | No | Fecha desde (formato: dd/mm/yyyy o yyyy-mm-dd) |
| `fechahasta` | string | No | Fecha hasta (formato: dd/mm/yyyy o yyyy-mm-dd) |
| `page` | int | No | Número de página (default: 1) |
| `limit` | int | No | Registros por página (default: 50) |
| `search` | string | No | Búsqueda por nombre, referencia, banco o código |

#### Respuesta Exitosa (code: 100)

```json
{
  "code": 100,
  "message": "Depósitos obtenidos correctamente",
  "data": [
    {
      "pago_id": "123",
      "pago_codint": "DEP-001",
      "pago_codexterno": "EXT-123",
      "pago_monto": "1000.00",
      "pago_fecha": "01/10/2025",
      "pago_fechareg": "01/10/2025 10:30:00",
      "pago_referencia": "REF123456",
      "pago_banco": "Banco Demo",
      "pago_comentario": "Depósito de prueba",
      "pago_img": "https://www.gestiongo.com/admin/arch/archivo.jpg",
      "pago_archoriginal": "comprobante.jpg",
      "pago_activo": "1",
      "pago_procesado": "0",
      "usuario_id": "456",
      "usuario_nombre": "Juan",
      "usuario_apellido": "Pérez",
      "usuario_img": "https://www.gestiongo.com/admin/arch/usuario.jpg",
      "cuenta_id": "100",
      "cuenta_codigo": "CTA-001",
      "cuenta_nombre": "Cuenta Principal",
      "compania_id": "200",
      "compania_nombre": "Compañía Demo",
      "l_estatus_id": "1234",
      "estatus_nombre": "Pendiente",
      "estatus_cod": "1",
      "l_formapago_id": "567",
      "formapago_nombre": "Transferencia",
      "l_moneda_id": "890",
      "moneda_nombre": "Dólar",
      "moneda_siglas": "$",
      "tipopago_cod": "1",
      "tipopago_nombre": "Depósito",
      "modulo_id": "12",
      "modulo_nombreunico": "depositos",
      "elemento_id": "0"
    }
  ],
  "total": 100,
  "page": 1,
  "limit": 50
}
```

#### Filtros por Perfil

- **Perfil 1 (Admin Sistema)**: Ve todos los depósitos
- **Perfil 2 (Admin Cuenta)**: Solo depósitos de su cuenta
- **Perfil 3 y 7 (Admin Compañía/Empleados)**: Solo depósitos de su cuenta y compañía
- **Otros**: Solo sus propios depósitos

---

### 2. **GET** `/backws/depositos/depositos?id={id}` - Obtener Depósito por ID

Obtiene los detalles completos de un depósito específico.

#### Parámetros (Query String)

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `token` | string | Sí | Token de autenticación del usuario |
| `compania` | int | Sí | ID de la compañía |
| `id` | int | Sí | ID del depósito |

#### Respuesta Exitosa (code: 100)

```json
{
  "code": 100,
  "message": "Depósito obtenido correctamente",
  "data": {
    "pago_id": "123",
    "pago_codint": "DEP-001",
    "pago_codexterno": "EXT-123",
    "pago_monto": "1000.00",
    "pago_fecha": "01/10/2025",
    "pago_fechareg": "01/10/2025 10:30:00",
    "pago_referencia": "REF123456",
    "pago_banco": "Banco Demo",
    "pago_comentario": "Depósito de prueba",
    "pago_img": "https://www.gestiongo.com/admin/arch/archivo.jpg",
    "pago_archoriginal": "comprobante.jpg",
    "pago_activo": "1",
    "pago_procesado": "0",
    "usuario_id": "456",
    "usuario_nombre": "Juan",
    "usuario_apellido": "Pérez",
    "usuario_img": "https://www.gestiongo.com/admin/arch/usuario.jpg",
    "cuenta_id": "100",
    "cuenta_codigo": "CTA-001",
    "cuenta_nombre": "Cuenta Principal",
    "compania_id": "200",
    "compania_nombre": "Compañía Demo",
    "compania_urlweb": "https://demo.com",
    "l_estatus_id": "1234",
    "estatus_nombre": "Pendiente",
    "estatus_cod": "1",
    "l_formapago_id": "567",
    "formapago_nombre": "Transferencia",
    "l_moneda_id": "890",
    "moneda_nombre": "Dólar",
    "moneda_siglas": "$",
    "l_tipoarchivo_id": "61",
    "tipopago_cod": "1",
    "tipopago_nombre": "Depósito",
    "modulo_id": "12",
    "modulo_nombreunico": "depositos",
    "elemento_id": "0"
  }
}
```

---

### 3. **PUT** `/backws/depositos/cambiarestatus` - Cambiar Estatus del Depósito

Cambia el estatus de un depósito. Si se aprueba o rechaza, procesa el balance del usuario y envía notificación push.

#### Parámetros (JSON Body)

```json
{
  "token": "abc123",
  "compania": 200,
  "pago_id": 123,
  "estatus": 1234
}
```

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `token` | string | Sí | Token de autenticación |
| `compania` | int | Sí | ID de la compañía |
| `pago_id` | int | Sí | ID del depósito |
| `estatus` | int | Sí | Nuevo ID de estatus (lista 55) |

#### Respuesta Exitosa (code: 100)

```json
{
  "code": 100,
  "message": "Cambiado el Estatus Correctamente y se hizo efectivo el monto al usuario",
  "data": {
    "pago_id": "123",
    "l_estatus_id": "1234"
  }
}
```

#### Lógica Especial al Aprobar (estatus código 2, lista 55)

1. Obtiene el balance del usuario (`usuariobalance`)
2. Resta el monto de `usuariobalance_bloqueado`
3. Suma el monto a `usuariobalance_disponible`
4. Suma el monto a `usuariobalance_total`
5. Marca `pago_procesado = 1`
6. Actualiza la tabla `movimiento` relacionada
7. Envía notificación push al usuario

#### Lógica Especial al Rechazar (estatus código 3, lista 55)

1. Resta el monto de `usuariobalance_bloqueado`
2. Resta el monto de `usuariobalance_total`
3. NO incrementa `usuariobalance_disponible`
4. Actualiza la tabla `movimiento` relacionada
5. Envía notificación push al usuario

---

### 4. **PUT** `/backws/depositos/cambiaractivo` - Activar/Desactivar Depósito

Cambia el estado activo del depósito (habilitado/deshabilitado) sin eliminarlo.

#### Parámetros (JSON Body)

```json
{
  "token": "abc123",
  "compania": 200,
  "pago_id": 123,
  "activo": 0
}
```

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `token` | string | Sí | Token de autenticación |
| `compania` | int | Sí | ID de la compañía |
| `pago_id` | int | Sí | ID del depósito |
| `activo` | int | Sí | Estado - 0 = Inactivo, 1 = Activo |

#### Respuesta Exitosa (code: 100)

```json
{
  "code": 100,
  "message": "Depósito desactivado correctamente",
  "data": {
    "pago_id": "123",
    "pago_activo": "0"
  }
}
```

---

### 5. **DELETE** `/backws/depositos/eliminar` - Eliminar Depósito

Realiza eliminación lógica del depósito (`pago_activo = 0`, `pago_eliminado = 1`).

#### Parámetros (JSON Body)

```json
{
  "token": "abc123",
  "compania": 200,
  "pago_id": 123
}
```

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `token` | string | Sí | Token de autenticación |
| `compania` | int | Sí | ID de la compañía |
| `pago_id` | int | Sí | ID del depósito |

#### Respuesta Exitosa (code: 100)

```json
{
  "code": 100,
  "message": "Depósito eliminado correctamente",
  "data": {
    "pago_id": "123"
  }
}
```

---

## Modelo de Datos

### Tabla Principal: `pago`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `pago_id` | INT | ID único del pago/depósito |
| `pago_codint` | VARCHAR | Código interno del depósito |
| `pago_codexterno` | VARCHAR | Código externo (pasarela de pago) |
| `pago_monto` | DECIMAL | Monto del depósito |
| `pago_fecha` | DATE | Fecha del depósito |
| `pago_referencia` | VARCHAR | Referencia bancaria |
| `pago_banco` | VARCHAR | Nombre del banco |
| `pago_comentario` | TEXT | Observaciones |
| `pago_img` | VARCHAR | Nombre de archivo adjunto |
| `pago_archoriginal` | VARCHAR | Nombre original del archivo |
| `pago_procesado` | TINYINT | Procesado (0/1) |
| `pago_activo` | TINYINT | Activo (0/1) |
| `pago_eliminado` | TINYINT | Eliminado (0/1) |
| `pago_fechareg` | DATETIME | Fecha de registro |
| `usuario_id` | INT | ID del usuario que hizo el depósito |
| `cuenta_id` | INT | ID de la cuenta |
| `compania_id` | INT | ID de la compañía |
| `l_formapago_id` | INT | ID forma de pago (lista) |
| `l_tipopago_id` | INT | ID tipo de pago (lista - código 1 para depósitos) |
| `l_moneda_id` | INT | ID moneda (lista) |
| `l_estatus_id` | INT | ID estatus (lista 55) |
| `l_tipoarchivo_id` | INT | ID tipo de archivo adjunto (lista) |
| `modulo_id` | INT | ID módulo relacionado |
| `elemento_id` | INT | ID elemento relacionado |

### Tabla Relacionada: `usuariobalance`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `usuariobalance_id` | INT | ID único |
| `usuario_id` | INT | ID del usuario |
| `compania_id` | INT | ID de la compañía |
| `usuariobalance_total` | DECIMAL | Balance total |
| `usuariobalance_disponible` | DECIMAL | Balance disponible |
| `usuariobalance_bloqueado` | DECIMAL | Balance bloqueado |
| `usuariobalance_pendiente` | DECIMAL | Balance pendiente |

### Tabla Relacionada: `movimiento`

Registro de movimientos del usuario. Se actualiza cuando cambia el estatus del depósito.

---

## Reglas de Negocio

### 1. Validación de Token

Todos los endpoints requieren token válido:
- `usuario_activo = 1`
- `usuario_codverif = $token`
- `compania_id = $compania`

Si falla: `code: 103, message: "Usuario / Token no activo"`

### 2. Multi-Tenancy por Perfil

Los filtros se aplican según el perfil del usuario autenticado:

| Perfil | Descripción | WHERE Aplicado |
|--------|-------------|----------------|
| 1 | Admin Sistema | (ninguno - ve todo) |
| 2 | Admin Cuenta | `pago.cuenta_id = $cuenta_id` |
| 3 | Admin Compañía | `pago.cuenta_id = $cuenta_id AND pago.compania_id = $compania_id` |
| 7 | Empleados | `pago.cuenta_id = $cuenta_id AND pago.compania_id = $compania_id` |
| Otros | Usuario Normal | `pago.cuenta_id = $cuenta_id AND pago.compania_id = $compania_id AND pago.usuario_id = $usuario_id` |

### 3. Estados del Depósito (lista 55)

| Código | Estatus | Acción al cambiar |
|--------|---------|-------------------|
| 1 | Pendiente | Solo cambia estatus |
| 2 | Aprobado | Procesa balance: bloqueado → disponible + total |
| 3 | Rechazado | Procesa balance: bloqueado - total |
| Otros | Personalizados | Solo cambia estatus |

### 4. Proceso de Aprobación de Depósito

Cuando se aprueba un depósito (`estatus = código 2, lista 55`):

1. Verifica `pago_procesado != 1` (para no procesar dos veces)
2. Obtiene el balance actual del usuario
3. Actualiza `usuariobalance`:
   - `usuariobalance_bloqueado = bloqueado - pago_monto`
   - `usuariobalance_disponible = disponible + pago_monto`
   - `usuariobalance_total = total + pago_monto`
4. Marca `pago_procesado = 1`
5. Actualiza tabla `movimiento` relacionada
6. Envía notificación push al usuario

### 5. Proceso de Rechazo de Depósito

Cuando se rechaza un depósito (`estatus = código 3, lista 55`):

1. Verifica `pago_procesado != 1`
2. Actualiza `usuariobalance`:
   - `usuariobalance_bloqueado = bloqueado - pago_monto`
   - `usuariobalance_total = total - pago_monto`
3. Actualiza tabla `movimiento` relacionada
4. Envía notificación push al usuario

### 6. Conversión de Moneda (Compañía 395)

Para la compañía 395, se aplica conversión de moneda usando la tabla `tasacambio`:
- Se obtiene la tasa de cambio vigente
- Se divide el monto por `tasacambio_ventavalor`
- El resultado se usa para actualizar el balance

### 7. Notificaciones Push

Al cambiar el estatus de un depósito, se envía notificación push automática:
- Se registra en tabla `correomasivo` y `correomasivodetalle`
- Se usa el campo `usuario_notas` como `usuario_pushtoken`
- Se envía via Expo SDK
- Si falla el push, no se detiene el proceso principal

### 8. Manejo de Archivos

- Los archivos adjuntos se guardan en `/arch/`
- Las URLs se generan con `ObtenerUrlArch($compania_id)`
- Soporta imágenes, PDFs, Word, Excel, etc.
- El tipo se identifica con `l_tipoarchivo_id`

---

## Ejemplos de Uso

### Ejemplo 1: Listar todos los depósitos del mes actual

```bash
curl -X GET "https://www.gestiongo.com/admin/backws/depositos/depositos?token=democliente&compania=381&fechadesde=01/10/2025&fechahasta=31/10/2025&limit=10&page=1"
```

### Ejemplo 2: Obtener depósito específico

```bash
curl -X GET "https://www.gestiongo.com/admin/backws/depositos/depositos?token=democliente&compania=381&id=123"
```

### Ejemplo 3: Aprobar un depósito

```bash
curl -X PUT "https://www.gestiongo.com/admin/backws/depositos/cambiarestatus" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "pago_id": 123,
    "estatus": 1234
  }'
```

### Ejemplo 4: Desactivar un depósito

```bash
curl -X PUT "https://www.gestiongo.com/admin/backws/depositos/cambiaractivo" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "pago_id": 123,
    "activo": 0
  }'
```

### Ejemplo 5: Eliminar un depósito

```bash
curl -X DELETE "https://www.gestiongo.com/admin/backws/depositos/eliminar" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "democliente",
    "compania": 381,
    "pago_id": 123
  }'
```

### Ejemplo 6: Buscar depósitos por usuario

```bash
curl -X GET "https://www.gestiongo.com/admin/backws/depositos/depositos?token=democliente&compania=381&search=Juan"
```

---

## Códigos de Respuesta

| Código | Significado | Cuándo usar |
|--------|-------------|-------------|
| 100 | Éxito | Operación completada correctamente |
| 101 | Sin permisos / Error genérico | Usuario sin permisos, validación fallida |
| 102 | Datos faltantes | Parámetros requeridos no enviados |
| 103 | Usuario/Token no activo | Token inválido o usuario inactivo |
| 105 | Error en operación | Error al actualizar en BD |
| 106 | Registro no encontrado | Depósito no existe |

---

## Notas Importantes

1. **Fechas**: Aceptan formato `dd/mm/yyyy` o `yyyy-mm-dd`
2. **URLs de Archivos**: Siempre se generan con `ObtenerUrlArch()` para soporte multi-dominio
3. **UTF-8**: Los datos de entrada/salida usan codificación UTF-8
4. **Paginación**: Default 50 registros por página
5. **Eliminación**: Siempre lógica, nunca física
6. **Balance**: Solo se procesa una vez por depósito (`pago_procesado`)

---

**Última actualización**: 07/10/2025
