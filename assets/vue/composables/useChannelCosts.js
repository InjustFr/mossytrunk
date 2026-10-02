import { perLocale } from '../i18n/locale.js';
import { formatCents } from './useMoney.js';

export const COST_KINDS = { fixed: 'fixed', percent: 'percent' };

const basisPointsFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 2 }));

export const formatCostAmount = (cost) => (cost.kind === COST_KINDS.percent ? basisPointsFormatter().format(cost.amount / 10_000) : formatCents(cost.amount));

export const costSummary = (costs) => costs.map(formatCostAmount).join(' + ');
