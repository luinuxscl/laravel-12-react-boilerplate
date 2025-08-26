import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// Static resources bundled with the app (frontend only)
import en from '../locales/en.json';
import es from '../locales/es.json';

void i18n
  .use(initReactI18next)
  .init({
    resources: {
      en: { translation: en },
      es: { translation: es },
    },
    lng: 'en',
    fallbackLng: 'en',
    interpolation: { escapeValue: false },
    // React 18/19 recommended flags
    react: { useSuspense: false },
  });

export default i18n;
