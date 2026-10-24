const kebab = '[a-z0-9]+(?:-[a-z0-9]+)*';

/** @type {import('stylelint').Config} */
export default {
    extends: ['stylelint-config-standard-scss'],
    ignoreFiles: ['dist/**', 'node_modules/**'],
    rules: {
        // BEM: block__element--modifier (kebab-case parts) + WordPress classes.
        'selector-class-pattern': [
            `^${kebab}(?:__${kebab})?(?:--${kebab})?$`,
            { message: 'Use BEM: .block__element--modifier' },
        ],
        'selector-id-pattern': [`^${kebab}$`, { message: 'Use kebab-case ids (they match <main id>)' }],
        // Color tokens use a `c_` prefix ($c_accent).
        'scss/dollar-variable-pattern': ['^[a-z][a-z0-9]*(?:[-_][a-z0-9]+)*$'],
        'scss/at-mixin-pattern': [`^${kebab}$`],
        'scss/at-function-pattern': [`^${kebab}$`],
        'scss/no-global-function-names': true,
        'no-descending-specificity': null,
        'declaration-empty-line-before': null,
        'scss/dollar-variable-empty-line-before': null,
        // `@include` mixins sit directly next to declarations.
        'at-rule-empty-line-before': [
            'always',
            {
                except: ['blockless-after-same-name-blockless', 'first-nested'],
                ignore: ['after-comment'],
                ignoreAtRules: ['include', 'else'],
            },
        ],
    },
};
