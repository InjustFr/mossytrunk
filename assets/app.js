import { registerVueControllerComponents } from '@symfony/ux-vue';
import './stimulus_bootstrap.js';
import './styles/tokens.css';
import './styles/base.css';

// Only pages are mounted from Twig (`vue_component('OrdersPage', …)`).
registerVueControllerComponents(import.meta.webpackContext('./vue/pages', { recursive: true, regExp: /\.vue$/ }));
