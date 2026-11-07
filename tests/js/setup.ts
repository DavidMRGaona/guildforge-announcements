import { config } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import moduleLocales from '../../resources/js/locales';

// The module's texts in Spanish, as the site loads them
config.global.plugins = [
    createI18n({
        legacy: false,
        locale: 'es',
        fallbackLocale: 'es',
        messages: { es: moduleLocales.es },
    }),
];
