require('dotenv').config();

/**
 * app.config.js - Configuración Dinámica Multi-Tenant
 *
 * Este archivo carga automáticamente la configuración correcta basándose en COMPANIA_ID del .env
 *
 * CÓMO CAMBIAR DE PROYECTO:
 * 1. Edita .env
 * 2. Cambia COMPANIA_ID al número de la compañía deseada
 * 3. Guarda el archivo
 * 4. Ejecuta: npx expo prebuild --clean (solo si cambiaste package/bundle ID)
 * 5. Listo - la configuración se cargará automáticamente
 *
 * ESTRUCTURA:
 * - config/companies.map.js → Mapeo de COMPANIA_ID a archivo de config
 * - config/companies/{nombre}.js → Configuración específica de cada compañía
 */

const COMPANIA_ID = process.env.COMPANIA_ID;
const companiesMap = require('./config/companies.map');

// Validar que exista COMPANIA_ID
if (!COMPANIA_ID) {
  throw new Error('❌ ERROR: COMPANIA_ID no está definido en el archivo .env');
}

// Obtener nombre del archivo de configuración
const configFileName = companiesMap[COMPANIA_ID];

if (!configFileName) {
  throw new Error(
    `❌ ERROR: No existe configuración para COMPANIA_ID=${COMPANIA_ID}\n` +
    `Verifica que esté mapeado en config/companies.map.js`
  );
}

// Cargar configuración de la compañía
let companyConfig;
try {
  companyConfig = require(`./config/companies/${configFileName}`);
  console.log(`✅ Configuración cargada: ${companyConfig.name} (ID: ${COMPANIA_ID})`);
} catch (error) {
  throw new Error(
    `❌ ERROR: No se pudo cargar la configuración para ${configFileName}\n` +
    `Asegúrate de que existe: config/companies/${configFileName}.js\n` +
    `Error: ${error.message}`
  );
}

// Construir configuración de Expo
module.exports = {
  expo: {
    name: companyConfig.name,
    owner: companyConfig.owner,
    slug: companyConfig.slug,
    version: companyConfig.version,
    scheme: companyConfig.scheme,
    newArchEnabled: true, // Nueva Arquitectura habilitada (RN 0.81.5+)
    experiments: {
      typedRoutes: false
    },
    icon: "./assets/img/logoicon.png",
    userInterfaceStyle: "light",
    updates: {
      enabled: true,
      checkAutomatically: "ON_LOAD",
      fallbackToCacheTimeout: 30000,
      ...(companyConfig.updatesUrl && { url: companyConfig.updatesUrl })
    },
    runtimeVersion: {
      policy: "appVersion"
    },
    splash: {
      image: "./assets/img/splashscreen.png",
      resizeMode: "contain",
      backgroundColor: companyConfig.splashBackgroundColor || companyConfig.backgroundColor
    },
    android: {
      softwareKeyboardLayoutMode: "pan",
      splash: {
        image: "./assets/img/splashscreen.png",
        resizeMode: "contain",
        backgroundColor: companyConfig.splashBackgroundColor || companyConfig.backgroundColor
      },
      intentFilters: companyConfig.intentFilters,
      config: {
        googleMaps: {
          apiKey: companyConfig.googleMapsApiKey
        }
      },
      adaptiveIcon: {
        foregroundImage: "./assets/img/splashscreen.png",
        backgroundColor: companyConfig.splashBackgroundColor || companyConfig.backgroundColor
      },
      edgeToEdgeEnabled: true,
      versionCode: companyConfig.androidVersionCode,
      permissions: companyConfig.androidPermissions,
      blockedPermissions: companyConfig.androidBlockedPermissions || [],
      package: companyConfig.androidPackage,
      googleServicesFile: companyConfig.googleServicesFile
    },
    web: {
      favicon: "./assets/img/splashscreen.png"
    },
    plugins: [
      [
        "expo-location",
        companyConfig.locationConfig
      ],
      [
        "expo-splash-screen",
        {
          backgroundColor: companyConfig.backgroundColor,
          image: "./assets/splash-icon.png",
          imageResizeMode: "contain",
          dark: {
            image: "./assets/img/splashscreen.png",
            backgroundColor: companyConfig.backgroundColor
          },
          android: {
            softwareKeyboardLayoutMode: "pan",
            splash: {
              image: "./assets/img/splashscreen.png",
              resizeMode: "contain",
              backgroundColor: companyConfig.backgroundColor
            }
          },
          imageWidth: 300
        }
      ],
      [
        "expo-build-properties",
        {
          android: {
            compileSdkVersion: 35,
            targetSdkVersion: 35,
            buildToolsVersion: "35.0.0",
            enableProguardInReleaseBuilds: true,
            enableShrinkResourcesInReleaseBuilds: true,
            allowBackup: false,
            minSdkVersion: 24,
            softwareKeyboardLayoutMode: "pan",
            enableMinifyInReleaseBuilds: true
          },
          ios: {
            deploymentTarget: "15.1"
          }
        }
      ],
      [
        "expo-notifications",
        {
          defaultChannel: "default",
          enableBackgroundRemoteNotifications: true
        }
      ],
      [
        "expo-camera",
        {
          cameraPermission: companyConfig.cameraPermission
        }
      ],
      [
        "expo-screen-orientation",
        {
          initialOrientation: "DEFAULT"
        }
      ],
      [
        "sentry-expo",
        {
          organization: companyConfig.sentryOrganization || "sistemasgo",
          project: companyConfig.sentryProject || companyConfig.slug
        }
      ],
      "expo-asset",
      "expo-web-browser",
      "expo-font",
      "./plugins/withStripeProguard"
    ],
    ios: {
      supportsTablet: true,
      buildNumber: companyConfig.iosBuildNumber,
      infoPlist: companyConfig.iosInfoPlist,
      bundleIdentifier: companyConfig.iosBundleIdentifier,
      config: {
        googleMapsApiKey: companyConfig.googleMapsApiKey
      }
    },
    extra: {
      eas: {
        projectId: companyConfig.easProjectId
      },
      // Store URLs - Migrated from .env GOOGLEPLAY/APPSTORE
      googlePlayUrl: companyConfig.googlePlayUrl,
      appStoreUrl: companyConfig.appStoreUrl,
      // Sentry DSN - Migrated from .env DSNSENTRY
      sentryDsn: companyConfig.sentryDsn,
      // Location Permissions - Migrated from .env FORCE_LOCATION_PERMISSIONS/PERMISO_UBICACION
      forceLocationPermissions: companyConfig.forceLocationPermissions,
      locationPermissionType: companyConfig.locationPermissionType,
      // API URL - Migrated from .env APP_URLAPI
      apiUrl: companyConfig.apiUrl,
      // Referidos - Migrated from .env REFERIDOS
      referidosEnabled: companyConfig.referidosEnabled,
      // Demo Mode - Migrated from .env DEMO
      demoMode: companyConfig.demoMode,
      // App Type - Migrated from .env TIPOAPP
      tipoApp: companyConfig.tipoApp,
      // App Name - Migrated from .env NAME
      appName: companyConfig.name,
      // Theme Colors - Migrated from .env COLORHEADER/COLORBOTONPRINCIPAL
      colorHeader: companyConfig.colorHeader,
      colorButtonPrimary: companyConfig.colorButtonPrimary,
      splashBackgroundColor: companyConfig.splashBackgroundColor,
      // Navigation Bar Component - Migrated from .env BARRANAVEGACION
      barraNavegacion: companyConfig.barraNavegacion,
      // Google Maps API Key - Migrated from .env GOOGLE_MAPS_API_KEY
      googleMapsApiKey: companyConfig.googleMapsApiKey,
      // Firebase Configuration - Migrated from .env FIREBASE_*
      firebase: companyConfig.firebase
    }
  }
};
