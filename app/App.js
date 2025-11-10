import 'react-native-get-random-values'; // Polyfill for crypto.getRandomValues
import { AppState } from 'react-native';
//
import { useState, useEffect, useCallback } from 'react';

import store from './src/redux/store'
import { Provider } from 'react-redux'
import BarraNavegacion from './src/components/BarraNavegacion/BarraNavegacion.jsx';
import CustomSplashScreen from './src/components/General/CustomSplashScreen.jsx';
import Toast from 'react-native-toast-message';
import { GestureHandlerRootView } from 'react-native-gesture-handler'
import { SafeAreaProvider } from 'react-native-safe-area-context';
import './localization/i18n';
import { useColorScheme } from 'react-native';


import ThemeContext from './src/ThemeContext';

import * as Linking from 'expo-linking';
import * as SplashScreen from 'expo-splash-screen';

import { StripeProvider } from '@stripe/stripe-react-native';
import axios from './src/config/axiosConfig';
import { COMPANIA_ID } from '@env';
import { getApiUrl } from './src/config/apiConfig';

// Firebase Realtime Database
import FirebaseRealtimeService from './src/services/FirebaseRealtimeService';
import { getFirebaseConfig, isFirebaseEnabled } from './src/config/firebaseConfig';

// Theme Colors Configuration
import { getSplashBackgroundColor } from './src/config/themeColorsConfig';

// Importar Sentry para Expo - TEMPORALMENTE DESHABILITADO PARA DEBUGGING
// import * as Sentry from 'sentry-expo';
// import { DSNSENTRY } from '@env';

// Configurar Sentry (inicialización al nivel del módulo como en proyecto anterior)
// TEMPORALMENTE DESHABILITADO PARA DEBUGGING
/*
Sentry.init({
  dsn: DSNSENTRY,
  enableInExpoDevelopment: true,
  debug: false, // Si está en desarrollo, mostrar logs de debug
  // Set tracesSampleRate to 1.0 to capture 100% of transactions for performance monitoring.
  // We recommend adjusting this value in production
  tracesSampleRate: 1.0,
});
*/

// Prevenir que el splash screen se oculte automáticamente
SplashScreen.preventAutoHideAsync();

const prefix = Linking.createURL("/");

const App = () => {
  const [appIsReady, setAppIsReady] = useState(false);
  const [showCustomSplash, setShowCustomSplash] = useState(true);
  const [stripePublishableKey, setStripePublishableKey] = useState('pk_test_51RmOnXRKrBp5IXxbZN1lqlhs0WbOrzrwLUyeXu58FVapsRJPJogSgKjvFJEgp2Fgh6KvXdz3AJTLt7A46LUEp2fv00aWqZrPYc'); // Default test key

  const linking = {
    prefixes: [prefix],
    config: {
      screens: {
        Home: "home",
        Settings: "settings",
      },
    },
  };

  //const prefix = Linking.makeUrl("/");

  function handleDeepLink(event){
    let data = Linking.parse(event.url);

    // Handle AgroComercio specific deep links
    if (COMPANIA_ID === "387") {
      // Handle referral links for AgroComercio
      if (data && data.queryParams) {
        const { ref, producto, action } = data.queryParams;

        // Handle referral code
        if (ref) {

          // Store referral data for registration
          store.dispatch({
            type: 'referral/setReferralData',
            payload: {
              codigo_referido: ref,
              producto_id: producto || null,
              timestamp: Date.now()
            }
          });

          // If producto_id is present, navigate to that product
          if (producto) {
            store.dispatch({
              type: 'navigation/setPendingNavigation',
              payload: {
                screen: 'PublicacionDetalle',
                params: {
                  item: { prod_id: producto },
                  from_referral: true,
                  referral_code: ref
                }
              }
            });
          }
        }
      }

      // Handle path-based deep links (e.g., agrocomercio://producto/123)
      if (data && data.path) {
        const pathParts = data.path.split('/');

        // Handle product deep link: agrocomercio://producto/[id]
        if (pathParts[0] === 'producto' && pathParts[1]) {
          const productoId = pathParts[1];

          store.dispatch({
            type: 'navigation/setPendingNavigation',
            payload: {
              screen: 'PublicacionDetalle',
              params: {
                item: { prod_id: productoId },
                from_deeplink: true
              }
            }
          });
        }
      }
    }

    // Handle TipPal specific deep links
    if (COMPANIA_ID === "475" && data && data.queryParams) {
      const { recipient_id, action } = data.queryParams;

      // Handle QR tip payment deep link
      if (action === 'send_tip' && recipient_id) {

        // Store the tip payment data for processing after app initialization
        store.dispatch({
          type: 'qr/setTipData',
          payload: {
            recipient_id: recipient_id,
            action: action,
            timestamp: Date.now()
          }
        });

        // This will be handled in the navigation component to show tip screen
      }
    }

    // Handle general deep link actions for all apps
    if (data && data.queryParams && data.queryParams.trans) {
      /* Deep link trans parameter handling - intentionally empty for now */
    }
  }

  useEffect(() => {
      ////////
      Linking.addEventListener("url", handleDeepLink);
      return () => {
        //Linking.removeEventListener("url");
      }
  }, [])

  useEffect(() => {
    async function prepare() {
      try {

        // Aquí puedes realizar cualquier tarea de inicialización necesaria
        // Por ejemplo: cargar fuentes, assets, datos del usuario, etc.

        // Obtener la clave pública de Stripe desde la base de datos
        try {

          const response = await axios.get(getApiUrl() + 'stripe/get-publishable-key.php', {
            params: { compania: COMPANIA_ID }
          });

          if (response.data.code === 0 && response.data.data.publishable_key) {
            setStripePublishableKey(response.data.data.publishable_key);

          }
        } catch (error) {
          console.error('Error fetching Stripe publishable key:', error);
        }

        // Inicializar Firebase Realtime Database
        try {
          // Verificar si Firebase está habilitado para esta compañía
          if (isFirebaseEnabled()) {
            const firebaseConfig = getFirebaseConfig();
            FirebaseRealtimeService.initialize(firebaseConfig);
          }
        } catch (error) {
          console.warn('⚠️ Error al inicializar Firebase:', error);
        }

        // Pequeño delay mínimo para splash screen suave (500ms)
        await new Promise(resolve => setTimeout(resolve, 500));

      } catch (e) {
        console.warn('❌ Error en preparación:', e);
      } finally {
        // Indicar que la app está lista
        setAppIsReady(true);
      }
    }

    prepare();
  }, []);

  /*
  const linking = {
    prefixes: [prefix],
    config: {
      screens: {
        Home: "home",
        Settings: "settings",
      },
    },
  };
  */

  //const url = Linking.useUrl();
  ////////

  const colorScheme = useColorScheme();

  const [theme, setTheme] = useState("light");
  const themeData = { theme, setTheme };

  //return <WebView source={{ uri: 'https://www.sistemasgo.com' }} />;

  // <BarraNavegacion />
  // <Provider  store={store}>

  const [appState, setAppState] = useState(AppState.currentState);

  useEffect(() => {
    const appStateListener = AppState.addEventListener('change', //can be 'background' or 'active'
        nextAppState => {
        
          setAppState(nextAppState);
          
          if(nextAppState == 'background')
          {
              //////
              //entered background mode
          }
          else if(nextAppState == 'active' || null)
          {
              //entered foreground mode
              //////
          }
      },
    );
    return () => {
      appStateListener?.remove();
    };
  }, []);

  const onLayoutRootView = useCallback(async () => {
    if (appIsReady) {
      try {
        // Esto le dice al splash screen nativo que se oculte inmediatamente
        await SplashScreen.hideAsync();
        // El custom splash se mostrará automáticamente
      } catch (error) {
        console.error('❌ Error ocultando splash screen:', error);
      }
    }
  }, [appIsReady]);

  // Ocultar splash screen nativo cuando la app esté lista
  useEffect(() => {
    if (appIsReady) {
      const hideSplash = async () => {
        try {
          await SplashScreen.hideAsync();
          // El custom splash se mostrará ahora
        } catch (error) {
          console.error('❌ Error en useEffect ocultando splash:', error);
        }
      };
      // Pequeño delay para asegurar que el render completo antes de ocultar
      setTimeout(hideSplash, 100);
    }
  }, [appIsReady]);

  // Manejar el custom splash screen
  const handleCustomSplashFinish = useCallback(() => {
    // Ocultar el custom splash después de la animación
    setShowCustomSplash(false);
  }, []);

  if (!appIsReady) {
    // Muestra el splash screen nativo mientras la app no está lista
    return null;
  }

  return (
    <SafeAreaProvider>
      <StripeProvider
        publishableKey={stripePublishableKey}
      >
        <Provider store={store}>
          <ThemeContext.Provider value={themeData} >
            <GestureHandlerRootView style={{ flex: 1 }} onLayout={onLayoutRootView}>
              <BarraNavegacion />
              <Toast />

              {/* Custom Splash Screen - se muestra después del splash nativo */}
              {showCustomSplash && (
                <CustomSplashScreen
                  visible={showCustomSplash}
                  duration={2500} // 2.5 segundos (ajustable)
                  backgroundColor={getSplashBackgroundColor()} // Color desde configuración de compañía
                  onFinish={handleCustomSplashFinish}
                />
              )}
            </GestureHandlerRootView>
          </ThemeContext.Provider>
        </Provider>
      </StripeProvider>
    </SafeAreaProvider>
  )
}  

export default App; 