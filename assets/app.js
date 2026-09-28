import { registerVueControllerComponents } from '@symfony/ux-vue';
import * as Turbo from '@hotwired/turbo';
import './stimulus_bootstrap.js';
import '@fontsource/dm-serif-display/400.css';
import '@fontsource-variable/inter/wght.css';
import './styles/tokens.css';
import './styles/base.css';

// Only pages are mounted from Twig (`vue_component('OrdersPage', …)`).
registerVueControllerComponents(import.meta.webpackContext('./vue/pages', { recursive: true, regExp: /\.vue$/ }));

// Turbo Drive (Symfony UX Turbo) swaps pages without a full reload; show its top progress bar quickly.
Turbo.session.progressBarDelay = 120;
