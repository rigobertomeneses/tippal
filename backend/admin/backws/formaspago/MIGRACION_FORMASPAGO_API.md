# MIGRACIÓN FORMAS DE PAGO A API REST

**Fecha de creación**: 07/10/2025
**Módulo**: Formas de Pago
**Basado en**: Proceso de migración de Clientes

---

## 📋 INFORMACIÓN DEL MÓDULO

### Controllers Originales
- **`controllers/formapago.php`**: Listado de formas de pago con filtros por perfil
- **`controllers/modificarformapago.php`**: Formulario de creación/modificación
- **`controllers/uploadformapago.php`**: Guardado con manejo de imagen

### Funciones AJAX (lib/ajx_fnci.php)
- **`eliminarlista()`**: Eliminación lógica (línea 33116)
- **`cambiarestatuslista()`**: Cambio de estado activo/inactivo (línea 33255)

### Funciones Auxiliares (lib/funciones.php)
- **`VerificaListaDefecto($lista_id)`**: Valida si es forma de pago predeterminada del sistema
- **`GuardarProcesoLista()`**: INSERT/UPDATE en tabla `lista` y `listacuenta`
- **`GuardarListaFormaPago()`**: INSERT/UPDATE en tabla `listaformapago`

---

## 🗄️ TABLAS DE BASE DE DATOS

### 1. Tabla `lista` (Formas de Pago del Sistema)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| lista_id | INT | ID único |
| lista_nombre | VARCHAR | Nombre de la forma de pago |
| lista_cod | VARCHAR | Código identificador |
| lista_img | VARCHAR | Nombre del archivo de imagen |
| lista_orden | INT | Orden de visualización |
| lista_activo | TINYINT | Activo (0/1) |
| lista_eliminado | TINYINT | Eliminado (0/1) |
| lista_ppal | TINYINT | Es del sistema (1) o personalizada (0) |
| lista_idrel | INT | ID de forma de pago relacionada/principal |
| tipolista_id | INT | 21 = Forma de Pago |
| cuenta_id | INT | ID cuenta (2 = sistema) |
| compania_id | INT | ID compañía (1 = sistema) |
| lista_fechareg | DATETIME | Fecha de registro |

### 2. Tabla `listacuenta` (Personalizaciones por Cuenta/Compañía)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| listacuenta_id | INT | ID único |
| lista_id | INT | FK a lista |
| cuenta_id | INT | ID de la cuenta |
| compania_id | INT | ID de la compañía |
| listacuenta_nombre | VARCHAR | Nombre personalizado |
| listacuenta_img | VARCHAR | Imagen personalizada |
| listacuenta_orden | INT | Orden personalizado |
| listacuenta_activo | TINYINT | Activo (0/1) |
| listacuenta_eliminado | TINYINT | Eliminado (0/1) |
| listacuenta_fechareg | DATETIME | Fecha de registro |
| usuario_idreg | INT | Usuario que registró |

### 3. Tabla `listaformapago` (Datos Específicos de la Forma de Pago)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| listaformapago_id | INT | ID único |
| l_formapago_id | INT | FK a lista.lista_id |
| listacuenta_id | INT | FK a listacuenta |
| listaformapago_titular | VARCHAR | Nombre del titular |
| listaformapago_documento | VARCHAR | Documento del titular |
| listaformapago_email | VARCHAR | Email del titular |
| listaformapago_banco | VARCHAR | Nombre del banco |
| listaformapago_tipocuenta | VARCHAR | Tipo de cuenta |
| listaformapago_nrocuenta | VARCHAR | Número de cuenta |
| listaformapago_otros | TEXT | Otros datos |
| listaformapago_token | VARCHAR | Token/API Key (para integraciones) |
| listaformapago_clavepublica | VARCHAR | Clave pública (para integraciones) |
| cuenta_id | INT | ID cuenta |
| compania_id | INT | ID compañía |
| usuario_idreg | INT | Usuario que registró |
| listaformapago_fechareg | DATETIME | Fecha de registro |

### 4. Tabla `listacuentarel` (Disponibilidad Web/Sistema)

| Campo | Tipo | Descripción |
|-------|------|-------------|
| listacuentarel_id | INT | ID único |
| tipolista_id | INT | 117=Web, 118=Sistema |
| lista_id | INT | FK a lista |
| cuenta_id | INT | ID cuenta |
| compania_id | INT | ID compañía |
| listacuentarel_activo | TINYINT | Activo (0/1) |
| listacuentarel_eliminado | TINYINT | Eliminado (0/1) |
| listacuentarel_fechareg | DATETIME | Fecha registro |
| usuario_idreg | INT | Usuario que registró |

---

## 🔌 ENDPOINTS A CREAR

### 1. **GET** `/backws/formaspago/formaspago` (Listar o Obtener)

#### Caso 1: Listar Formas de Pago (sin `id`)

**Query String:**
```
?token=democliente&compania=381&page=1&limit=50&search=
```

**SELECT del Controller Original (formapago.php líneas 66-109):**
```sql
SELECT
    lista.lista_id, lista.lista_nombre, lista.lista_img, lista.lista_orden,
    lista.lista_activo, lista.lista_ppal, lista.lista_cod, lista.lista_idrel,
    lista.cuenta_id as cuenta_idsistema, lista.compania_id as compania_idsistema,

    cuenta.usuario_codigo as cuenta_codigo, cuenta.usuario_nombre as cuenta_nombre,
    cuenta.usuario_apellido as cuenta_apellido, compania.compania_nombre as compania_nombre,
    cuenta.usuario_codigo as cuentasistema_codigo, cuenta.usuario_nombre as cuentasistema_nombre,
    cuenta.usuario_apellido as cuentasistema_apellido, companiasistema.compania_nombre as companiasistema_nombre,

    listacuenta.cuenta_id, listacuenta.compania_id,
    listacuenta.listacuenta_id, listacuenta.listacuenta_activo, listacuenta.listacuenta_eliminado,
    listacuenta.listacuenta_img, listacuenta.listacuenta_orden, listacuenta.listacuenta_nombre,
    lista.tipolista_id,

    listaformapago.listaformapago_id, listaformapago.l_formapago_id,
    listaformapago.listaformapago_titular, listaformapago.listaformapago_documento,
    listaformapago.listaformapago_email, listaformapago.listaformapago_banco,
    listaformapago.listaformapago_tipocuenta, listaformapago.listaformapago_nrocuenta,
    listaformapago.listaformapago_otros, listaformapago.usuario_idreg,
    DATE_FORMAT(listaformapago_fechareg,'%d/%m/%Y %H:%i:%s') as listaformapago_fechareg

FROM lista
    INNER JOIN usuario cuentasistema ON cuentasistema.usuario_id = lista.cuenta_id
    INNER JOIN compania companiasistema ON companiasistema.compania_id = lista.compania_id

    LEFT JOIN listacuenta ON listacuenta.lista_id = lista.lista_id
        {$wherelistacuenta}

    LEFT JOIN listaformapago ON listaformapago.l_formapago_id = lista.lista_id
        AND listaformapago.listacuenta_id = listacuenta.listacuenta_id

    LEFT JOIN usuario cuenta ON cuenta.usuario_id = listacuenta.cuenta_id
    LEFT JOIN compania ON compania.compania_id = listacuenta.compania_id

    {$wherecuenta}
    {$wherecompania}

WHERE lista.lista_eliminado = '0'
  AND lista.tipolista_id = '21'
  {$where}
  AND ((lista.lista_ppal = '1' {$wherelistaactivo}) OR (lista.lista_ppal = '0'))

ORDER BY lista.lista_orden ASC
```

**Filtros por Perfil (formapago.php líneas 30-52):**

- **Perfil 1 (Admin Sistema)**: Ve todo
  ```php
  $where = "";
  $wherelistacuenta = "";
  ```

- **Perfil 2 (Admin Cuenta)**: Solo su cuenta
  ```php
  $where = " and listacuenta.cuenta_id = '{$cuenta_id}' ";
  $wherecuenta = " and listacuenta.cuenta_id = '{$cuenta_id}' ";
  $wherelistacuenta = " and listacuenta.cuenta_id = '{$cuenta_id}' ";
  $wherelistaactivo = " and lista.lista_activo = '1' ";
  ```

- **Perfil 3 (Admin Compañía)**: Solo su cuenta y compañía
  ```php
  $where = " and listacuenta.cuenta_id = '{$cuenta_id}' and listacuenta.compania_id = '{$compania_id}' ";
  $wherecuenta = " and listacuenta.cuenta_id = '{$cuenta_id}' ";
  $wherecompania = " and listacuenta.compania_id = '{$compania_id}' ";
  $wherelistacuenta = " and listacuenta.cuenta_id = '{$cuenta_id}' and listacuenta.compania_id = '{$compania_id}' ";
  $wherelistaactivo = " and lista.lista_activo = '1' ";
  ```

**Lógica de Respuesta (formapago.php líneas 112-177):**
```php
// Si listacuenta_eliminado == 1, saltar registro (continue)
// Si listacuenta_id existe, usar datos de listacuenta en lugar de lista
// Si lista_ppal==1 && cuenta_id está vacío, es del sistema (no personalizado)
```

**Response Exitoso:**
```json
{
  "code": 100,
  "message": "Formas de pago obtenidas correctamente",
  "data": [
    {
      "lista_id": "123",
      "lista_nombre": "Mercado Pago",
      "lista_cod": "MP001",
      "lista_img": "https://gestiongo.com/admin/arch/mercadopago.png",
      "lista_orden": "1",
      "lista_activo": "1",
      "lista_ppal": "1",
      "lista_idrel": "0",
      "listacuenta_id": "456",
      "cuenta_id": "100",
      "cuenta_nombre": "VT Taxi",
      "compania_id": "200",
      "compania_nombre": "VT Taxi SRL",
      "listaformapago_id": "789",
      "listaformapago_titular": "Juan Pérez",
      "listaformapago_documento": "12345678",
      "listaformapago_email": "juan@email.com",
      "listaformapago_banco": "Banco Nacional",
      "listaformapago_tipocuenta": "Ahorros",
      "listaformapago_nrocuenta": "1234567890",
      "listaformapago_otros": "Notas adicionales",
      "listaformapago_token": "MP_TOKEN_123",
      "listaformapago_clavepublica": "MP_PUBLIC_KEY",
      "listaformapago_fechareg": "01/01/2024 10:30:00"
    }
  ],
  "total": 6
}
```

#### Caso 2: Obtener Forma de Pago Específica (con `id` y opcionalmente `lid`)

**Query String:**
```
?token=democliente&compania=381&id=123&lid=456
```

**SELECT del Controller Original (modificarformapago.php líneas 104-154):**
- Igual que el listado pero con filtros adicionales:
  ```sql
  AND lista.lista_id = '{$id}' {$whereid}
  ```
- Si viene `lid` (listacuenta_id):
  ```php
  $whereid = " and listacuenta.listacuenta_id = '{$lid}' ";
  ```

**Response incluye además:**
- `listaformapago_token`
- `listaformapago_clavepublica`
- Todos los campos para edición

---

### 2. **POST** `/backws/formaspago/formaspago` (Crear)

**Parámetros JSON (uploadformapago.php líneas 33-51):**
```json
{
  "token": "abc123",
  "compania": 200,
  "cuenta": 100,
  "lista_nombre": "PayPal",
  "lista_cod": "PP001",
  "lista_orden": 5,
  "lista_idrel": 0,
  "listaformapago_titular": "María García",
  "listaformapago_documento": "87654321",
  "listaformapago_email": "maria@email.com",
  "listaformapago_banco": "Banco Internacional",
  "listaformapago_tipocuenta": "Corriente",
  "listaformapago_nrocuenta": "9876543210",
  "listaformapago_otros": "Cuenta empresarial",
  "listaformapago_token": "PAYPAL_TOKEN",
  "listaformapago_clavepublica": "PAYPAL_PUBLIC_KEY"
}
```

**Campos Requeridos:**
- `token`, `compania`, `cuenta`
- `lista_nombre` (siempre requerido)

**Lógica de Creación (uploadformapago.php líneas 201-241):**

1. **Validar que no sea lista predeterminada** (línea 53):
   ```php
   $listadefecto = VerificaListaDefecto($lista_id);
   if ($listadefecto==true) {
       // Error: "Este registro no puede ser modificado"
   }
   ```

2. **Determinar lista_ppal** (líneas 193-197):
   ```php
   if ($cuenta=="2" && $compania=="1") {
       $lista_ppal = 1; // Es del sistema
   } else {
       $lista_ppal = 0; // Es personalizada
   }
   ```

3. **Imagen por defecto** (líneas 203-205):
   ```php
   if ($nombrecolocar=="") {
       $nombrecolocar = "0.jpg";
   }
   ```

4. **Guardar en `lista` y `listacuenta`** usando función:
   ```php
   $resultadoGuardarLista = GuardarProcesoLista(
       $lista_nombre, $lista_nombredos, $lista_descrip, $lista_img,
       $lista_ppal, $lista_orden, $tipolista_id=21, $cuenta, $compania,
       $lista_icono, $lista_color, $lista_idrel, $lista_url,
       $fechaactual, null, null, $lista_cod
   );
   ```

5. **Guardar datos específicos** en `listaformapago`:
   ```php
   $resultadoGuardarListaFormaPago = GuardarListaFormaPago(
       "", $lista_id, $listaformapago_titular, $listaformapago_documento,
       $listaformapago_email, $listaformapago_banco, $listaformapago_tipocuenta,
       $listaformapago_nrocuenta, $listaformapago_otros, $fechaactual,
       $cuenta, $compania, $listacuenta_id, $listaformapago_token,
       $listaformapago_clavepublica, $usuario_id
   );
   ```

6. **Guardar relaciones Web/Sistema** (líneas 224-240):
   ```php
   // tipolista_id = 117 (Web)
   INSERT INTO listacuentarel (tipolista_id, lista_id, cuenta_id, compania_id, ...)
   VALUES ('117', '$lista_id', '$cuenta', '$compania', ...)

   // tipolista_id = 118 (Sistema)
   INSERT INTO listacuentarel (tipolista_id, lista_id, cuenta_id, compania_id, ...)
   VALUES ('118', '$lista_id', '$cuenta', '$compania', ...)
   ```

**Response:**
```json
{
  "code": 100,
  "message": "Forma de Pago Guardado Correctamente",
  "data": {
    "lista_id": "124",
    "listacuenta_id": "457"
  }
}
```

---

### 3. **PUT** `/backws/formaspago/formaspago` (Actualizar)

**Parámetros JSON:**
```json
{
  "token": "abc123",
  "compania": 200,
  "cuenta": 100,
  "lista_id": 123,
  "listacuenta_id": 456,
  "listaformapago_id": 789,
  "lista_nombre": "PayPal Actualizado",
  "lista_cod": "PP001",
  "lista_orden": 5,
  "listaformapago_titular": "María García Pérez",
  "listaformapago_nrocuenta": "9876543210-A"
}
```

**Lógica de Actualización (uploadformapago.php líneas 244-286):**

1. **Validar que no sea predeterminada**

2. **Actualizar usando función**:
   ```php
   $resultadoGuardarLista = GuardarProcesoModificarLista(
       $lista_id, $lista_nombre, $lista_nombredos, $lista_descrip,
       $nombrecolocar, $lista_ppal, $lista_orden, $tipolista_id=21,
       $cuenta, $compania, $lista_icono, $lista_color, $lista_idrel,
       $lista_url, $fechaactual, $listacuenta_id, null, null, $lista_cod
   );
   ```

3. **Actualizar datos de forma de pago**:
   ```php
   $resultadoGuardarListaFormaPago = GuardarListaFormaPago(
       $listaformapago_id, // SI tiene ID, hace UPDATE
       ...
   );
   ```

4. **Eliminar lógicamente relaciones anteriores** (líneas 262-267):
   ```php
   UPDATE listacuentarel
   SET listacuentarel_activo = '0',
       listacuentarel_eliminado = '1'
   WHERE cuenta_id = '$cuenta'
     AND compania_id = '$compania'
     AND tipolista_id IN (117,118)
     AND lista_id = '$lista_id'
   ```

5. **Insertar nuevas relaciones Web/Sistema** (líneas 269-285)

---

### 4. **DELETE** `/backws/formaspago/formaspago` (Eliminar)

**Parámetros JSON:**
```json
{
  "token": "abc123",
  "compania": 200,
  "cuenta": 100,
  "lista_id": 123
}
```

**Lógica de Eliminación (ajx_fnci.php líneas 33116-33253):**

1. **Validar predeterminada**:
   ```php
   $listadefecto = VerificaListaDefecto($lista_id);
   if ($listadefecto==true) {
       return error "predeterminado para el sistema"
   }
   ```

2. **Obtener listacuenta_id** (líneas 33142-33149):
   ```sql
   SELECT listacuenta_id FROM listacuenta
   WHERE lista_id = '$lista_id'
     AND cuenta_id = '$cuenta'
     AND compania_id = '$compania'
   ```

3. **Si es del sistema** ($cuenta=="2" && $compania=="1") (líneas 33152-33164):
   ```php
   UPDATE lista SET lista_activo = '0', lista_eliminado = '1'
   WHERE lista_id = '$lista_id'
     AND compania_id = '$compania'
     AND cuenta_id = '$cuenta'

   UPDATE listacuenta SET listacuenta_activo = '0', listacuenta_eliminado = '1'
   WHERE listacuenta_id = '$listacuenta_id'
   ```

4. **Si es personalizada** (líneas 33165-33223):
   - **Si NO existe listacuenta** (es lista del sistema que se va a "eliminar" para esta cuenta):
     - Crear entrada en listacuenta con `activo=0, eliminado=1`
   - **Si existe listacuenta**:
     - UPDATE listacuenta: `activo=0, eliminado=1`
     - UPDATE lista: `activo=0, eliminado=1`

**Response:**
```json
{
  "code": 100,
  "message": "Eliminado Correctamente",
  "data": {
    "lista_id": "123"
  }
}
```

---

### 5. **PUT** `/backws/formaspago/cambiarestatus` (Cambiar Estado)

**Parámetros JSON:**
```json
{
  "token": "abc123",
  "compania": 200,
  "cuenta": 100,
  "lista_id": 123,
  "activo": 0
}
```

**Lógica (ajx_fnci.php líneas 33255-33354):**

1. **Validar predeterminada**

2. **Obtener listacuenta_id**

3. **Si es del sistema** (líneas 33291-33298):
   ```php
   UPDATE lista SET lista_activo = '$estatus'
   WHERE lista_id = '$lista_id'
     AND compania_id = '$compania'
     AND cuenta_id = '$cuenta'

   UPDATE listacuenta SET listacuenta_activo = '$estatus'
   WHERE listacuenta_id = '$listacuenta_id'
   ```

4. **Si es personalizada** (líneas 33299-33346):
   - **Si NO existe listacuenta**:
     - Crear listacuenta con el estado indicado
   - **Si existe listacuenta**:
     - UPDATE listacuenta con nuevo estado
   - UPDATE lista con nuevo estado

**Response:**
```json
{
  "code": 100,
  "message": "Cambiado el Estatus Correctamente",
  "data": {
    "lista_id": "123",
    "lista_activo": "0"
  }
}
```

---

### 6. **POST** `/backws/formaspago/uploadimagen` (Subir Imagen)

**Parámetros JSON:**
```json
{
  "token": "abc123",
  "compania": 200,
  "cuenta": 100,
  "lista_id": 123,
  "listacuenta_id": 456,
  "imagen_base64": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
}
```

**Proceso (uploadformapago.php líneas 87-188):**

1. **Validar extensión** JPG o PNG (línea 115)
2. **Generar nombre único** (línea 158):
   ```php
   $nombrecolocar = uniqid() . "." . $extension;
   ```
3. **Guardar en arch/** (línea 171)
4. **Actualizar lista o listacuenta** según corresponda

**Response:**
```json
{
  "code": 100,
  "message": "Imagen subida correctamente",
  "data": {
    "lista_id": "123",
    "lista_img": "5f9a8b7c6d5e4.png",
    "url": "https://gestiongo.com/admin/arch/5f9a8b7c6d5e4.png"
  }
}
```

---

## ✅ VALIDACIONES Y REGLAS DE NEGOCIO

### 1. Formas de Pago Predeterminadas
- Función: `VerificaListaDefecto($lista_id)`
- No se puede eliminar, modificar ni cambiar estado
- Error: "Este registro no puede ser modificado/eliminado porque es predeterminado para el sistema"

### 2. Multi-Tenancy Complejo
- **lista_ppal = 1**: Forma de pago del sistema (cuenta=2, compania=1)
- **lista_ppal = 0**: Forma de pago personalizada por cuenta/compañía
- Si se personaliza una del sistema, se crea entrada en `listacuenta`

### 3. Filtrado por Perfil
- Ver código exacto de formapago.php líneas 30-52

### 4. Relaciones Web/Sistema
- Siempre insertar en `listacuentarel` con tipolista_id = 117 (Web) y 118 (Sistema)

### 5. Imagen
- Por defecto: `0.jpg`
- Formato: JPG, PNG
- Nombre: `uniqid()` + extensión

---

## 📊 CÓDIGOS DE RESPUESTA

| Código | Significado |
|--------|-------------|
| 100 | Éxito |
| 101 | Sin permisos / Lista predeterminada |
| 102 | Datos faltantes |
| 103 | Token inválido |
| 105 | Error en operación |
| 106 | Registro no encontrado |

---

## 🎯 PLAN DE IMPLEMENTACIÓN

1. ✅ Crear `/backws/formaspago/formaspago.php` - CRUD completo
2. ✅ Crear `/backws/formaspago/cambiarestatus.php` - Cambiar activo/inactivo
3. ✅ Crear `/backws/formaspago/uploadimagen.php` - Subir imagen Base64
4. ✅ Probar todos los endpoints
5. ✅ Crear documentación Postman

---

**IMPORTANTE**: Mantener toda la lógica exacta de los controllers originales. Los SELECTs deben ser idénticos.
