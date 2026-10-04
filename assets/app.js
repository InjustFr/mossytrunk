import { registerVueControllerComponents } from '@symfony/ux-vue';
import '@hotwired/turbo';
import './stimulus_bootstrap.js';
import './progress-bar.js';
import './vue/i18n/index.js';
import '@fontsource/patua-one/400.css';
import '@fontsource-variable/inter/wght.css';
import './styles/tokens.css';
import './styles/base.css';
import './styles/modal.css';

const pages = import.meta.webpackContext('./vue/pages', { recursive: true, regExp: /\.vue$/, mode: 'lazy', prefetch: true });
registerVueControllerComponents(pages);

