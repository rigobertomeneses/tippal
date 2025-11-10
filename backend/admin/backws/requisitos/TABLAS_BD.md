# Estructura de Base de Datos - Módulo de Requisitos

## Descripción General

El módulo de Requisitos utiliza el sistema de listas de GestionGo para gestionar tipos de requisitos configurables y almacena los documentos que los usuarios cargan en tablas específicas.

---

## Tablas del Sistema

### 1. `lista`

Tabla principal del sistema de listas. Los requisitos son un tipo específico de lista.

**Campos relevantes:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `lista_id` | INT(11) PK | ID único de la lista |
| `lista_cod` | VARCHAR(50) | Código interno del requisito |
| `lista_nombre` | VARCHAR(200) | Nombre del requisito |
| `lista_descrip` | TEXT | Descripción del requisito |
| `lista_img` | VARCHAR(200) | Nombre del archivo de imagen |
| `lista_orden` | INT(11) | Orden de visualización |
| `lista_activo` | TINYINT(1) | Estado activo/inactivo |
| `lista_eliminado` | TINYINT(1) | Borrado lógico |
| `lista_ppal` | TINYINT(1) | 1=Sistema, 0=Personalizado |
| `tipolista_id` | INT(11) FK | **49 para requisitos** |
| `cuenta_id` | INT(11) FK | ID de cuenta (multi-tenancy) |
| `compania_id` | INT(11) FK | ID de compañía (multi-tenancy) |
| `lista_fechareg` | DATETIME | Fecha de registro |
| `usuario_idreg` | INT(11) FK | Usuario que registró |

**Índices:**
- PRIMARY KEY (`lista_id`)
- INDEX `idx_tipo_activo` (`tipolista_id`, `lista_activo`, `lista_eliminado`)
- INDEX `idx_cuenta_compania` (`cuenta_id`, `compania_id`)

**Filtro importante:**
```sql
WHERE tipolista_id = '49' AND lista_eliminado = '0'
```

---

### 2. `listacuenta`

Personalización de listas por cuenta/compañía.

**Campos relevantes:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `listacuenta_id` | INT(11) PK | ID único de personalización |
| `lista_id` | INT(11) FK | Referencia a `lista.lista_id` |
| `listacuenta_nombre` | VARCHAR(200) | Nombre personalizado |
| `listacuenta_descrip` | TEXT | Descripción personalizada |
| `listacuenta_img` | VARCHAR(200) | Imagen personalizada |
| `listacuenta_orden` | INT(11) | Orden personalizado |
| `listacuenta_activo` | TINYINT(1) | Estado activo/inactivo |
| `listacuenta_eliminado` | TINYINT(1) | Borrado lógico |
| `cuenta_id` | INT(11) FK | ID de cuenta |
| `compania_id` | INT(11) FK | ID de compañía |

**Índices:**
- PRIMARY KEY (`listacuenta_id`)
- INDEX `idx_lista_cuenta_compania` (`lista_id`, `cuenta_id`, `compania_id`)

**Uso:**
- Si existe registro en `listacuenta`, los valores personalizados reemplazan a los de `lista`
- Permite que cada cuenta/compañía tenga nombres y descripciones propias

---

### 3. `listarequisitoperfil`

Relación entre tipos de requisitos y perfiles de usuario.

**Campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `listarequisitoperfil_id` | INT(11) PK | ID único de relación |
| `l_requisitolista_id` | INT(11) FK | Referencia a `lista.lista_id` |
| `perfil_id` | INT(11) FK | ID del perfil al que aplica |
| `cuenta_id` | INT(11) FK | ID de cuenta |
| `compania_id` | INT(11) FK | ID de compañía |
| `listarequisitoperfil_activo` | TINYINT(1) | Estado activo/inactivo |
| `listarequisitoperfil_eliminado` | TINYINT(1) | Borrado lógico |
| `listarequisitoperfil_fechareg` | DATETIME | Fecha de registro |
| `usuario_idreg` | INT(11) FK | Usuario que registró |

**Índices:**
- PRIMARY KEY (`listarequisitoperfil_id`)
- UNIQUE INDEX `idx_unique_lista_perfil` (`l_requisitolista_id`, `perfil_id`)
- INDEX `idx_perfil` (`perfil_id`)

**Uso:**
- Define qué perfil de usuario debe cumplir cada requisito
- Un requisito puede aplicar a múltiples perfiles
- Ejemplos: perfil_id = 4 (Cliente), 21 (Conductor), etc.

---

### 4. `requisito`

Requisitos cargados por usuarios.

**Campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `requisito_id` | INT(11) PK | ID único del requisito |
| `l_requisitolista_id` | INT(11) FK | Tipo de requisito (referencia a `lista.lista_id`) |
| `requisito_descrip` | TEXT | Descripción/comentario del usuario |
| `l_tipoarchivo_id` | INT(11) FK | Tipo de archivo (deprecado, usar `requisitoarchivo`) |
| `requisito_arch` | VARCHAR(200) | Archivo principal (deprecado) |
| `requisito_archnombre` | VARCHAR(200) | Nombre original (deprecado) |
| `requisito_cantarchivos` | INT(11) | Cantidad de archivos adjuntos |
| `cuenta_id` | INT(11) FK | ID de cuenta |
| `compania_id` | INT(11) FK | ID de compañía |
| `requisito_activo` | TINYINT(1) | Estado activo/inactivo |
| `requisito_eliminado` | TINYINT(1) | Borrado lógico |
| `requisito_fechareg` | DATETIME | Fecha de carga |
| `usuario_idreg` | INT(11) FK | Usuario que registró |
| `l_estatus_id` | INT(11) FK | Estatus actual del requisito |
| `usuario_id` | INT(11) FK | Usuario propietario del requisito |

**Índices:**
- PRIMARY KEY (`requisito_id`)
- INDEX `idx_usuario_tipo` (`usuario_id`, `l_requisitolista_id`, `requisito_activo`)
- INDEX `idx_estatus` (`l_estatus_id`)
- INDEX `idx_cuenta_compania` (`cuenta_id`, `compania_id`)
- INDEX `idx_fechareg` (`requisito_fechareg`)

**Relaciones:**
- `l_requisitolista_id` → `lista.lista_id` (tipo de requisito)
- `l_estatus_id` → `lista.lista_id` (estatus del requisito)
- `usuario_id` → `usuario.usuario_id` (propietario)
- `usuario_idreg` → `usuario.usuario_id` (quien registró)

**Estatus típicos (lista con tipolista_id = 50):**
- ID 4: Pendiente por cargar
- ID 52: En revisión
- ID 53: Rechazado
- ID 54: Confirmado/Aprobado

**Nota importante:**
- Campos `requisito_arch`, `requisito_archnombre` y `l_tipoarchivo_id` están deprecados
- Usar tabla `requisitoarchivo` para manejar archivos adjuntos
- `requisito_cantarchivos` se actualiza automáticamente al agregar/eliminar archivos

---

### 5. `requisitoarchivo`

Archivos adjuntos de cada requisito (soporte multi-archivo).

**Campos:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `requisitoarch_id` | INT(11) PK | ID único del archivo |
| `requisito_id` | INT(11) FK | Referencia a `requisito.requisito_id` |
| `requisitoarch_arch` | VARCHAR(200) | Nombre único en servidor (generado) |
| `requisitoarch_nombre` | VARCHAR(200) | Nombre original del archivo |
| `l_tipoarchivo_id` | INT(11) FK | Tipo de archivo |
| `requisitoarch_activo` | TINYINT(1) | Estado activo/inactivo |
| `requisitoarch_eliminado` | TINYINT(1) | Borrado lógico |
| `requisitoarch_fechareg` | DATETIME | Fecha de carga |
| `usuario_idreg` | INT(11) FK | Usuario que subió |
| `l_estatus_id` | INT(11) FK | Estatus del archivo |

**Índices:**
- PRIMARY KEY (`requisitoarch_id`)
- INDEX `idx_requisito` (`requisito_id`, `requisitoarch_activo`, `requisitoarch_eliminado`)
- INDEX `idx_fechareg` (`requisitoarch_fechareg`)

**Tipos de archivo (lista con tipolista_id = 48):**
- ID 58: PDF
- ID 59: Word (DOC, DOCX)
- ID 60: Excel (XLS, XLSX)
- ID 61: Imagen (JPG, PNG, GIF)
- ID 62: Otro

**Ubicación física:**
- Los archivos se guardan en: `/arch/[nombre_unico]`
- Nombre único generado con `uniqid() + extensión`
- URL completa: `ObtenerUrlArch($compania_id) + requisitoarch_arch`

---

## Relaciones entre Tablas

```
lista (tipolista_id=49)
  ├── listacuenta (personalización)
  ├── listarequisitoperfil (perfil asignado)
  └── requisito (requisitos de usuarios)
      └── requisitoarchivo (archivos adjuntos)
```

---

## Consultas Comunes

### 1. Obtener tipos de requisitos configurados

```sql
SELECT
    lista.lista_id,
    lista.lista_nombre,
    lista.lista_descrip,
    lista.lista_img,
    lista.lista_orden,
    perfil.perfil_nombre,
    COALESCE(listacuenta.listacuenta_nombre, lista.lista_nombre) as nombre_mostrar,
    COALESCE(listacuenta.listacuenta_activo, lista.lista_activo) as activo
FROM lista
    LEFT JOIN listacuenta ON listacuenta.lista_id = lista.lista_id
        AND listacuenta.cuenta_id = :cuenta_id
        AND listacuenta.compania_id = :compania_id
    LEFT JOIN listarequisitoperfil ON listarequisitoperfil.l_requisitolista_id = lista.lista_id
        AND listarequisitoperfil.listarequisitoperfil_activo = '1'
    LEFT JOIN perfil ON perfil.perfil_id = listarequisitoperfil.perfil_id
WHERE
    lista.tipolista_id = '49'
    AND lista.lista_eliminado = '0'
ORDER BY lista.lista_orden ASC;
```

### 2. Obtener requisitos de un usuario

```sql
SELECT
    requisito.requisito_id,
    requisito.requisito_fechareg,
    requisito.requisito_cantarchivos,
    lista.lista_nombre as tipo_requisito,
    estatus.lista_nombre as estatus,
    usuario.usuario_nombre,
    usuario.usuario_apellido
FROM requisito
    INNER JOIN lista ON lista.lista_id = requisito.l_requisitolista_id
    LEFT JOIN lista estatus ON estatus.lista_id = requisito.l_estatus_id
    INNER JOIN usuario ON usuario.usuario_id = requisito.usuario_id
WHERE
    requisito.usuario_id = :usuario_id
    AND requisito.requisito_eliminado = '0'
ORDER BY requisito.requisito_fechareg DESC;
```

### 3. Obtener archivos de un requisito

```sql
SELECT
    requisitoarch_id,
    requisitoarch_arch,
    requisitoarch_nombre,
    l_tipoarchivo_id,
    requisitoarch_fechareg,
    requisitoarch_activo
FROM requisitoarchivo
WHERE
    requisito_id = :requisito_id
    AND requisitoarch_eliminado = '0'
ORDER BY requisitoarch_fechareg DESC;
```

### 4. Verificar si usuario tiene un tipo de requisito

```sql
SELECT requisito_id, l_estatus_id, requisito_cantarchivos
FROM requisito
WHERE
    usuario_id = :usuario_id
    AND l_requisitolista_id = :tipo_requisito
    AND requisito_activo = '1'
    AND requisito_eliminado = '0'
LIMIT 1;
```

### 5. Contar requisitos por estatus

```sql
SELECT
    estatus.lista_nombre,
    COUNT(*) as total
FROM requisito
    INNER JOIN lista estatus ON estatus.lista_id = requisito.l_estatus_id
WHERE
    requisito.cuenta_id = :cuenta_id
    AND requisito.compania_id = :compania_id
    AND requisito.requisito_eliminado = '0'
GROUP BY requisito.l_estatus_id, estatus.lista_nombre
ORDER BY total DESC;
```

---

## Proceso de Creación de Requisito

### Paso 1: Usuario carga archivo
```sql
-- 1. Verificar si ya existe requisito para ese usuario y tipo
SELECT requisito_id FROM requisito
WHERE usuario_id = :usuario_id
    AND l_requisitolista_id = :tipo_requisito
    AND requisito_activo = '1';

-- 2a. Si NO existe, crear nuevo requisito
INSERT INTO requisito (
    l_requisitolista_id, cuenta_id, compania_id,
    requisito_activo, requisito_eliminado,
    requisito_fechareg, usuario_idreg,
    l_estatus_id, usuario_id, requisito_cantarchivos
) VALUES (
    :tipo_requisito, :cuenta_id, :compania_id,
    '1', '0',
    NOW(), :usuario_idreg,
    :estatus_pendiente, :usuario_id, 1
);

-- 2b. Si existe, usar ese requisito_id

-- 3. Insertar archivo adjunto
INSERT INTO requisitoarchivo (
    requisito_id, requisitoarch_arch, requisitoarch_nombre,
    l_tipoarchivo_id, requisitoarch_activo, requisitoarch_eliminado,
    requisitoarch_fechareg, usuario_idreg, l_estatus_id
) VALUES (
    :requisito_id, :archivo_guardado, :archivo_original,
    :tipo_archivo, '1', '0',
    NOW(), :usuario_idreg, :estatus_pendiente
);

-- 4. Actualizar contador de archivos
UPDATE requisito
SET requisito_cantarchivos = (
    SELECT COUNT(*)
    FROM requisitoarchivo
    WHERE requisito_id = :requisito_id
        AND requisitoarch_activo = '1'
        AND requisitoarch_eliminado = '0'
)
WHERE requisito_id = :requisito_id;
```

---

## Funciones del Sistema Relacionadas

### `GuardarProcesoLista()`
Maneja la inserción/actualización de registros en `lista` y `listacuenta` con lógica de multi-tenancy.

**Parámetros:**
```php
GuardarProcesoLista(
    $lista_nombre,      // Nombre del requisito
    $lista_nombredos,   // (No usado)
    $lista_descrip,     // Descripción
    $lista_img,         // Nombre de imagen
    $lista_ppal,        // 1=Sistema, 0=Personalizado
    $lista_orden,       // Orden de visualización
    $tipolista_id,      // 49 para requisitos
    $cuenta_id,         // ID de cuenta
    $compania_id,       // ID de compañía
    $lista_icono,       // (No usado)
    $lista_color,       // (No usado)
    $lista_idrel,       // (No usado)
    $lista_url,         // (No usado)
    $fechaactual,       // Fecha de registro
    $lista_nombredos2,  // (No usado)
    $lista_nombredos3,  // (No usado)
    $lista_cod          // Código interno
)
```

**Retorna:**
```php
[
    'lista_id' => 1795,
    'listacuenta_id' => 2115
]
```

### `GuardarProcesoModificarLista()`
Similar a `GuardarProcesoLista()` pero para actualizar registros existentes.

### `VerificarUsuarioEstatus()`
Actualiza el estatus general del usuario basado en el estado de sus requisitos.

**Lógica:**
- Si todos los requisitos están "Confirmados" → Usuario activo
- Si alguno está "Rechazado" → Usuario requiere atención
- Si alguno está "Pendiente" → Usuario incompleto

### `ObtenerUrlArch($compania_id)`
Obtiene la URL base para archivos de una compañía específica.

**Retorno:**
```php
"https://www.gestiongo.com/admin/arch/"
// o
"https://subdomain.midominio.com/arch/"
```

---

## Consideraciones de Performance

### Índices Recomendados

```sql
-- Tabla requisito
CREATE INDEX idx_usuario_tipo_activo ON requisito(usuario_id, l_requisitolista_id, requisito_activo);
CREATE INDEX idx_fecha_estatus ON requisito(requisito_fechareg, l_estatus_id);
CREATE INDEX idx_cuenta_compania_activo ON requisito(cuenta_id, compania_id, requisito_activo);

-- Tabla requisitoarchivo
CREATE INDEX idx_requisito_activo ON requisitoarchivo(requisito_id, requisitoarch_activo, requisitoarch_eliminado);
CREATE INDEX idx_fechareg ON requisitoarchivo(requisitoarch_fechareg);
```

### Consultas Optimizadas

**Evitar:**
```sql
-- Mal: Sub-consulta por cada fila
SELECT *, (SELECT COUNT(*) FROM requisitoarchivo WHERE requisito_id = r.requisito_id) as total
FROM requisito r;
```

**Usar:**
```sql
-- Bien: Usar campo requisito_cantarchivos (actualizado automáticamente)
SELECT *, requisito_cantarchivos as total
FROM requisito;
```

---

## Migración y Compatibilidad

### Campos Deprecados (mantener por compatibilidad)
- `requisito.requisito_arch` - Reemplazado por `requisitoarchivo`
- `requisito.requisito_archnombre` - Reemplazado por `requisitoarchivo`
- `requisito.l_tipoarchivo_id` - Usar `requisitoarchivo.l_tipoarchivo_id`

### Actualización de Datos Existentes
Si hay datos en campos deprecados, migrar con:

```sql
-- Migrar archivo principal a tabla requisitoarchivo
INSERT INTO requisitoarchivo (
    requisito_id, requisitoarch_arch, requisitoarch_nombre,
    l_tipoarchivo_id, requisitoarch_activo, requisitoarch_eliminado,
    requisitoarch_fechareg, usuario_idreg
)
SELECT
    requisito_id, requisito_arch, requisito_archnombre,
    l_tipoarchivo_id, requisito_activo, '0',
    requisito_fechareg, usuario_idreg
FROM requisito
WHERE requisito_arch IS NOT NULL
    AND requisito_arch != ''
    AND requisito_arch != '0.jpg'
    AND NOT EXISTS (
        SELECT 1 FROM requisitoarchivo ra
        WHERE ra.requisito_id = requisito.requisito_id
    );
```

---

**Autor:** API Migration Team
**Fecha:** 2025-10-09
**Versión:** 1.0
