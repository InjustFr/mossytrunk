import { registerVueControllerComponents } from '@symfony/ux-vue';
import './stimulus_bootstrap.js';
import '@fontsource/dm-serif-display/400.css';
import '@fontsource-variable/inter/wght.css';
import './styles/tokens.css';
import './styles/base.css';

// Only pages are mounted from Twig (`vue_component('OrdersPage', …)`).
registerVueControllerComponents(import.meta.webpackContext('./vue/pages', { recursive: true, regExp: /\.vue$/ }));
