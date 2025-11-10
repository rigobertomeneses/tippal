/**
 * Configuración para TipPal
 * Generado automáticamente desde: app-tippal.json
 */

module.exports = {
  "name": "TipPal",
  "companiaId": "467",
  "slug": "tippal",
  "scheme": "tippal",
  "version": "2.2.9",
  "owner": "gestiongo",
  "easProjectId": "aec7d249-9450-4f3c-b40e-d16e66a23862",
  "androidPackage": "com.sistemasgo.tippal",
  "iosBundleIdentifier": "com.sistemasgo.tippal",
  "androidVersionCode": 229,
  "iosBuildNumber": "2.2.9",
  "backgroundColor": "#FFFFFF",
  "splashBackgroundColor": "#FFFFFF",
  "googleServicesFile": "./assets/googleservices/google-services-tippal.json",
  "intentFilters": [
    {
      "action": "VIEW",
      "data": [
        {
          "scheme": "https",
          "host": "www.tippalcorp.com/",
          "pathPrefix": "/openapp"
        }
      ],
      "category": [
        "BROWSABLE",
        "DEFAULT"
      ]
    },
    {
      "action": "VIEW",
      "data": [
        {
          "scheme": "tippal"
        }
      ],
      "category": [
        "BROWSABLE",
        "DEFAULT"
      ]
    }
  ],
  "androidPermissions": [
    "ACCESS_FINE_LOCATION",
    "CAMERA",
    "android.permission.ACCESS_COARSE_LOCATION",
    "android.permission.ACCESS_FINE_LOCATION",
    "android.permission.CAMERA",
    "android.permission.RECORD_AUDIO"
  ],
  "androidBlockedPermissions": [
    "READ_MEDIA_IMAGES",
    "READ_MEDIA_VIDEO"
  ],
  "locationConfig": {
    "locationAlwaysAndWhenInUsePermission": "Allow $(PRODUCT_NAME) to use your location."
  },
  "cameraPermission": "Allow $(PRODUCT_NAME) to access your camera",
  "iosInfoPlist": {
    "ITSAppUsesNonExemptEncryption": false
  },
  "sentryOrganization": "sistemasgo",
  "sentryProject": "tippal",
  "updatesUrl": null,
  "googlePlayUrl": "https://play.google.com/store/apps/details?id=com.sistemasgo.tippal",
  "appStoreUrl": null,
  "sentryDsn": "https://176df68af7ea07f7e6446b58520836a5@o1306368.ingest.us.sentry.io/4509785276874752",
  "forceLocationPermissions": false,
  "locationPermissionType": "primerplano",
  "apiUrl": "https://www.gestiongo.com/admin/backws/",
  "referidosEnabled": false,
  "demoMode": true,
  "tipoApp": "billetera",
  "colorHeader": "#424242",
  "colorButtonPrimary": "#424242",
  "barraNavegacion": "BarraNavegacionBilleteraDrawer",
  "googleMapsApiKey": "AIzaSyCM7M5O1V9yEyaHFxups-rV9Q8VirgpC2s",
  "firebase": null
};
