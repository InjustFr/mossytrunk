import { formatCents, formatPercent } from './useMoney.js';

export const COST_KINDS = { fixed: 'fixed', percent: 'percent' };

export const formatCostAmount = (cost) => (cost.kind === COST_KINDS.percent ? formatPercent(cost.amount / 10_000, 2) : formatCents(cost.amount));

export const costSummary = (costs) => costs.map(formatCostAmount).join(' + ');
