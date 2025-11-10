module.exports = function(api) {
  api.cache(true);

  // Plugins base que siempre se aplican
  const plugins = [
    ["module:react-native-dotenv", {
      "envName": "APP_ENV",
      "moduleName": "@env",
      "path": ".env",
      "blacklist": null,
      "whitelist": [
        // Variables de configuración de la app
        "COMPANIA_ID",
        "urlserver",
        // Google Sign In
        "GOOGLE_DEV_LOCAL_IP",
        "GOOGLE_DEV_LOCAL_PORT",
        // Google Maps
        "GOOGLE_MAPS_API_KEY",
        // Firebase Realtime Database
        "FIREBASE_API_KEY",
        "FIREBASE_AUTH_DOMAIN",
        "FIREBASE_DATABASE_URL",
        "FIREBASE_PROJECT_ID",
        "FIREBASE_STORAGE_BUCKET",
        "FIREBASE_MESSAGING_SENDER_ID",
        "FIREBASE_APP_ID",
        "FIREBASE_MEASUREMENT_ID",
      ],
      "safe": false,
      "allowUndefined": true,
      "verbose": false
    }],
  ];

  // En producción, remover console.log para mejor rendimiento
  // Mantiene console.warn y console.error para depuración crítica
  if (process.env.NODE_ENV === 'production') {
    plugins.push([
      'transform-remove-console',
      {
        exclude: ['error', 'warn'] // Mantener console.error y console.warn
      }
    ]);
  }

  // IMPORTANTE: react-native-reanimated/plugin SIEMPRE debe ser el último
  plugins.push('react-native-reanimated/plugin');

  return {
    presets: ['babel-preset-expo'],
    plugins,
  };
};
