import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import es from './es.json';
import en from './en.json';
import { COMPANIA_ID } from '@env'

import AsyncStorage from "@react-native-async-storage/async-storage";


const STORE_LANGUAGE_KEY = "settings.lang";


const languageDetectorPlugin = {
    type: "languageDetector",
    async: true,
    init: () => { 
        //////console.log("hereee21");
    },
    detect: async function (callback: (lang: string) => void) {
        try {
            //////////console.log("here1");
            // get stored language from Async storage
            // put your own language detection logic here
            await AsyncStorage.getItem(STORE_LANGUAGE_KEY).then((language) => {
                if (language) {

                    if (COMPANIA_ID=="449"){
                        language = "es";
                    }
                    if (COMPANIA_ID=="467"){
                        language = "en";
                    }
                    ////////console.log("here3");
                    //if language was stored before, use this language in the app
                    return callback(language);
                } else {
                    ////////console.log("here4");
                    //if language was not stored yet, use english

                    if (COMPANIA_ID=="467"){
                        return callback("en");
                    }else{
                        return callback("es");
                    }
                }
            });
        } catch (error) {
            ////////console.log("Error reading language", error);
        }
    },
    cacheUserLanguage: async function (language: string) {
        try {
            if (COMPANIA_ID=="449"){
                if (language=="es"){
                    language = "es";
                }                
            }
            ////////console.log("here5");
            //save a user's language choice in Async storage
            await AsyncStorage.setItem(STORE_LANGUAGE_KEY, language);
        } catch (error) { 
            ////////console.log("here6");
        }
    },
};

const resources = { // list of languages
    es,
    en
};
i18n.use(initReactI18next)
.use({
    type: 'languageDetector',
    name: 'customDetector',
    async: true, // If this is set to true, your detect function receives a callback function that you should call with your language, useful to retrieve your language stored in AsyncStorage for example
    init: function () {
      /* use services and options */
      ////////console.log("herelanguageDetector");
    },
    detect: async function (callback: (val: string) => void) {
        //////console.log("here 11");
        try {
            // get stored language from Async storage
            // put your own language detection logic here
            await AsyncStorage.getItem(STORE_LANGUAGE_KEY).then((language) => {
                //////console.log("heree2");
                if (language) {
                    if (COMPANIA_ID=="449"){
                        if (language=="es"){
                            language = "es";
                        }                
                    }
                    ////////console.log("heree3");
                    //if language was stored before, use this language in the app
                    return callback(language);
                } else {
                    //////////console.log("heree4");
                    //if language was not stored yet, use english
                    if (COMPANIA_ID=="467"){
                        return callback("en");
                    }else{
                        return callback("es");
                    }
                }
                //////////console.log("heree5");
            });
        } catch (error) {
            //////////console.log("heree6");
            //////////console.log("Error reading language", error);
        }
    },
    cacheUserLanguage: async function (language: string) {
        try {
            if (COMPANIA_ID=="449"){
                if (language=="es"){
                    language = "es";
                }                
            }
            var valuesLanguage = await AsyncStorage.getItem(STORE_LANGUAGE_KEY);
            ////////console.log("heree9");
            ////////console.log(valuesLanguage);
            ////////console.log(STORE_LANGUAGE_KEY);
            ////////console.log(language);
            //save a user's language choice in Async storage
            await AsyncStorage.setItem(STORE_LANGUAGE_KEY, language);
        } catch (error) { 

            ////////console.log("heree8");
            ////////console.log(error);
        }
    },
})
.init({
    compatibilityJSON: 'v3', //To make it work for Android devices, add this line.
    resources,
    lng:
        COMPANIA_ID=="449" ? 'es' :
        COMPANIA_ID=="467" ? 'en' :
        'es', // default language to use.
    // if you're using a language detector, do not define the lng option
    interpolation: {
        escapeValue: false,
    },
});
export default i18n;