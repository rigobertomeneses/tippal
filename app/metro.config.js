const { getDefaultConfig } = require('expo/metro-config');

const config = getDefaultConfig(__dirname);

// Configuración para soporte de 16KB page sizes en Android 15+
// Esto asegura que los assets binarios se manejen correctamente
config.resolver.assetExts.push('bin');

// Configuración adicional para optimización de memoria
config.transformer.minifierConfig = {
  ...config.transformer.minifierConfig,
  keep_fnames: true, // Mantiene nombres de funciones para mejor debugging
};

// iOS specific configuration to prevent crashes
config.resolver.sourceExts = [...config.resolver.sourceExts, 'cjs'];

// Ensure proper handling of iOS modules
config.resolver.resolverMainFields = ['react-native', 'browser', 'main'];

// Fix for iOS bundling issues
config.transformer.getTransformOptions = async () => ({
  transform: {
    experimentalImportSupport: false,
    inlineRequires: true,
  },
});

module.exports = config;