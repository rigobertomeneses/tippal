# Instalación del Sistema de Transferencias - PymeGo

## Archivos a subir al servidor

### 1. Backend - PHP (Subir a: `/admin/backws/`)

#### Archivos principales:
- `comprobartoken.php` - Función para validar tokens (subir a `/admin/backws/`)
- `transferencias/` - Carpeta completa con todos los endpoints

#### Contenido de la carpeta `transferencias/`:
- `contactos.php` - Endpoint para obtener contactos frecuentes
- `buscar_cuenta.php` - Endpoint para buscar cuentas por email/alias/CBU
- `realizar.php` - Endpoint para ejecutar transferencias
- `instalar.php` - Script de instalación de base de datos
- `actualizar_bd.sql` - Script SQL con las actualizaciones necesarias
- `README_INSTALACION.md` - Este archivo

### 2. Frontend - React Native (Ya están en el proyecto)

Las siguientes pantallas ya están creadas en `src/pages/Deposito/`:
- `TransferirInicio.jsx`
- `TransferirNuevaCuenta.jsx`
- `TransferirConfirmarCuenta.jsx`
- `TransferirDetalle.jsx`
- `TransferirConfirmar.jsx`
- `TransferirCompletada.jsx`

## Pasos de instalación

### Paso 1: Subir archivos al servidor

1. Subir el archivo `comprobartoken.php` a `/admin/backws/`
2. Crear la carpeta `transferencias` en `/admin/backws/`
3. Subir todos los archivos PHP de transferencias a `/admin/backws/transferencias/`

### Paso 2: Actualizar la base de datos

#### Opción A: Mediante navegador (recomendado)
1. Acceder a: `https://www.gestiongo.com/admin/backws/transferencias/instalar.php`
2. Verificar que todos los mensajes sean exitosos
3. La respuesta debe mostrar:
   ```json
   {
     "code": 0,
     "message": "Instalación completada",
     "detalles": [...]
   }
   ```

#### Opción B: Ejecutar SQL manualmente
Si prefieres ejecutar el SQL manualmente, usa el archivo `actualizar_bd.sql` en phpMyAdmin o tu cliente MySQL.

### Paso 3: Configurar permisos

Asegúrate de que los archivos PHP tengan permisos de lectura:
```bash
chmod 644 /path/to/admin/backws/comprobartoken.php
chmod 644 /path/to/admin/backws/transferencias/*.php
```

### Paso 4: Verificar endpoints

Prueba los endpoints con las siguientes URLs:

1. **Obtener contactos:**
   ```
   POST https://www.gestiongo.com/admin/backws/transferencias/contactos.php
   Body: {
     "token": "TOKEN_USUARIO",
     "compania": "465"
   }
   ```

2. **Buscar cuenta:**
   ```
   POST https://www.gestiongo.com/admin/backws/transferencias/buscar_cuenta.php
   Body: {
     "token": "TOKEN_USUARIO",
     "compania": "465",
     "tipo": "email",
     "valor": "usuario@ejemplo.com"
   }
   ```

3. **Realizar transferencia:**
   ```
   POST https://www.gestiongo.com/admin/backws/transferencias/realizar.php
   Body: {
     "token": "TOKEN_USUARIO",
     "compania": "465",
     "usuarioRecibeId": 123,
     "monto": 100.50,
     "concepto": "Pago de servicio",
     "formapago_cod": "999",
     "moneda": 1
   }
   ```

## Cambios en la base de datos

Los siguientes cambios se aplican automáticamente al ejecutar `instalar.php`:

### Tabla `usuario`:
- Nuevo campo: `usuario_alias` VARCHAR(100)
- Nuevo campo: `usuario_cbu` VARCHAR(22)
- Nuevo campo: `usuario_verificado` INT(1)
- Nuevos índices para mejorar búsquedas

### Tabla `movimiento`:
- Nuevos índices para mejorar consultas de transferencias

### Nueva tabla `notificacion`:
- Para almacenar notificaciones de transferencias

### Tabla `l_tipomov`:
- Nuevos tipos de movimiento para transferencias

## Navegación en la app

Para habilitar las transferencias en la app, agregar en la navegación:

```javascript
// En BarraNavegacionBilletera.jsx o el archivo de navegación correspondiente
<Stack.Screen name="TransferirInicio" component={TransferirInicio} />
<Stack.Screen name="TransferirNuevaCuenta" component={TransferirNuevaCuenta} />
<Stack.Screen name="TransferirConfirmarCuenta" component={TransferirConfirmarCuenta} />
<Stack.Screen name="TransferirDetalle" component={TransferirDetalle} />
<Stack.Screen name="TransferirConfirmar" component={TransferirConfirmar} />
<Stack.Screen name="TransferirCompletada" component={TransferirCompletada} />
```

## Soporte

Si encuentras algún problema durante la instalación:
1. Verifica los logs de PHP en el servidor
2. Asegúrate de que la base de datos tenga los permisos correctos
3. Verifica que el token del usuario sea válido

## Notas importantes

- Los endpoints validan automáticamente el token del usuario
- Las transferencias son transaccionales (se revierten si hay error)
- Se valida el saldo disponible antes de transferir
- Se envían notificaciones al destinatario
- Los movimientos quedan registrados para ambos usuarios