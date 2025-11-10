module.exports = {
  dependencies: {
    '@stripe/stripe-react-native': {
      platforms: {
        android: {
          packageImportPath: 'import com.reactnativestripesdk.StripeSdkPackage;',
        },
      },
    },
  },
};