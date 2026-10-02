const FIGURES = ['units', 'turnover', 'stockCost', 'urssaf', 'revenue'];

export const sumPotential = (products) => Object.fromEntries(FIGURES.map((figure) => [figure, products.reduce((sum, product) => sum + (product.potential?.[figure] ?? 0), 0)]));
