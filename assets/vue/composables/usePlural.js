const rules = new Intl.PluralRules('fr-FR');

export function plural(count, singular, pluralForm = `${singular}s`) {
    return `${count} ${rules.select(count) === 'one' ? singular : pluralForm}`;
}
