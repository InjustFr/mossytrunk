import { registerVueControllerComponents } from '@symfony/ux-vue';
import * as Turbo from '@hotwired/turbo';
import './stimulus_bootstrap.js';
import './progress-bar.js';
import './vue/i18n/index.js';
import '@fontsource/patua-one/400.css';
import '@fontsource-variable/inter/wght.css';
import './styles/tokens.css';
import './styles/base.css';

// Only pages are mounted from Twig (`vue_component('OrdersPage', …)`).
registerVueControllerComponents(import.meta.webpackContext('./vue/pages', { recursive: true, regExp: /\.vue$/ }));

// Turbo Drive (Symfony UX Turbo) swaps pages without a full reload. Its own progress bar is replaced by
// ours (progress-bar.js), which also covers the API calls of the page being displayed.
Turbo.session.progressBarDelay = Number.MAX_SAFE_INTEGER;
