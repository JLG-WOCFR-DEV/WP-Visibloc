/* global VisiBlocData */
import { Fragment, cloneElement, Children } from '@wordpress/element';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    PanelBody,
    SelectControl,
    ToggleGroupControl,
    ToggleGroupControlOptionIcon,
    ToggleControl,
    CheckboxControl,
    DateTimePicker,
    Notice,
    Button,
    DropdownMenu,
    MenuGroup,
    MenuItem,
    BaseControl,
    Flex,
    FlexBlock,
    FlexItem,
    TextareaControl,
    TextControl,
} from '@wordpress/components';
import { __, sprintf, _n } from '@wordpress/i18n';
import { __experimentalGetSettings, dateI18n, format as formatDate } from '@wordpress/date';
import { subscribe, select } from '@wordpress/data';

import './editor-styles.css';

const DEFAULT_SUPPORTED_BLOCKS = ['core/group'];

const StatusBadge = ({ label, variant = '', screenReaderText = '' }) => {
    const classNames = ['visibloc-status-badge'];

    if (typeof variant === 'string' && variant.trim()) {
        classNames.push(`visibloc-status-badge--${variant.trim()}`);
    }

    return (
        <span className={classNames.join(' ')}>
            {label}
            {screenReaderText ? (
                <span className="screen-reader-text">{screenReaderText}</span>
            ) : null}
        </span>
    );
};

const normalizeSupportedBlocks = (blocks) => {
    if (!Array.isArray(blocks)) {
        return [];
    }

    return Array.from(
        new Set(
            blocks
                .map((blockName) => (typeof blockName === 'string' ? blockName.trim() : ''))
                .filter(Boolean),
        ),
    );
};

const rawLocalizedSupportedBlocks =
    typeof VisiBlocData === 'object' &&
    VisiBlocData !== null &&
    Array.isArray(VisiBlocData.supportedBlocks)
        ? VisiBlocData.supportedBlocks
        : DEFAULT_SUPPORTED_BLOCKS;

const localizedSupportedBlocks = normalizeSupportedBlocks(rawLocalizedSupportedBlocks);
const baseSupportedBlocks =
    localizedSupportedBlocks.length > 0
        ? localizedSupportedBlocks
        : DEFAULT_SUPPORTED_BLOCKS;
const supportedBlocksFilterResult = applyFilters(
    'visiblocSupportedBlocks',
    baseSupportedBlocks,
);
const normalizedSupportedBlocks = normalizeSupportedBlocks(supportedBlocksFilterResult);
const supportedBlocks =
    normalizedSupportedBlocks.length > 0 ? normalizedSupportedBlocks : baseSupportedBlocks;
const isSupportedBlockName = (blockName) => supportedBlocks.includes(blockName);

const DATE_SETTINGS =
    typeof __experimentalGetSettings === 'function' ? __experimentalGetSettings() : {};
const { formats: DATE_FORMATS = {} } = DATE_SETTINGS || {};
const DEFAULT_DATE_FORMAT = 'F j, Y';
const DEFAULT_TIME_FORMAT = 'H:i';
const WP_DATE_FORMAT =
    typeof DATE_FORMATS.date === 'string' && DATE_FORMATS.date.trim()
        ? DATE_FORMATS.date
        : DEFAULT_DATE_FORMAT;
const WP_TIME_FORMAT =
    typeof DATE_FORMATS.time === 'string' && DATE_FORMATS.time.trim()
        ? DATE_FORMATS.time
        : DEFAULT_TIME_FORMAT;
const WP_DATETIME_FORMAT =
    typeof DATE_FORMATS.datetime === 'string' && DATE_FORMATS.datetime.trim()
        ? DATE_FORMATS.datetime
        : `${WP_DATE_FORMAT} ${WP_TIME_FORMAT}`.trim();

const getIs12HourFormat = (settings) => {
    if (!settings || typeof settings !== 'object') {
        return false;
    }

    if (typeof settings.twelveHourTime === 'boolean') {
        return settings.twelveHourTime;
    }

    const { formats } = settings;

    if (formats && typeof formats === 'object') {
        const { time } = formats;

        if (typeof time === 'string' && time.trim()) {
            return /a(?!\\)/i.test(time);
        }
    }

    return false;
};

const is12Hour = getIs12HourFormat(DATE_SETTINGS);

const formatGmtOffset = (offset) => {
    if (typeof offset !== 'number' || Number.isNaN(offset)) {
        return '';
    }

    if (offset === 0) {
        return 'UTC';
    }

    const sign = offset > 0 ? '+' : '-';
    const absoluteOffset = Math.abs(offset);
    const totalMinutes = Math.round(absoluteOffset * 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    const paddedHours = String(hours).padStart(2, '0');
    const paddedMinutes = String(minutes).padStart(2, '0');

    return `UTC${sign}${paddedHours}:${paddedMinutes}`;
};

const TIMEZONE_LABEL = (() => {
    if (!DATE_SETTINGS || typeof DATE_SETTINGS !== 'object') {
        return 'UTC';
    }

    const { timezone, timezoneAbbr, gmt_offset: gmtOffset } = DATE_SETTINGS;

    if (timezone && typeof timezone === 'object') {
        if (typeof timezone.string === 'string' && timezone.string.trim()) {
            return timezone.string.trim();
        }

        if (typeof timezone.abbr === 'string' && timezone.abbr.trim()) {
            return timezone.abbr.trim();
        }
    }

    if (typeof timezone === 'string' && timezone.trim()) {
        return timezone.trim();
    }

    if (typeof timezoneAbbr === 'string' && timezoneAbbr.trim()) {
        return timezoneAbbr.trim();
    }

    const offsetLabel = formatGmtOffset(gmtOffset);

    if (offsetLabel) {
        return offsetLabel;
    }

    return 'UTC';
})();

const formatScheduleDate = (value) => {
    if (!value) {
        return null;
    }

    if (typeof dateI18n === 'function') {
        return dateI18n(WP_DATETIME_FORMAT, value);
    }

    return formatDate(WP_DATETIME_FORMAT, value);
};

const getCurrentSiteIsoDate = () => formatDate('Y-m-d\\TH:i:s', new Date());

const parseDateValue = (value) => {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return null;
    }

    return date;
};

const DEVICE_VISIBILITY_DEFAULT_OPTION = {
    id: 'all',
    label: __('Visible sur tous les appareils', 'visi-bloc-jlg'),
};

const DEVICE_VISIBILITY_OPTIONS = [
    {
        id: 'show-only',
        label: __('Afficher uniquement', 'visi-bloc-jlg'),
        options: [
            {
                id: 'desktop-only',
                label: __('Desktop', 'visi-bloc-jlg'),
                icon: 'desktop',
            },
            {
                id: 'tablet-only',
                label: __('Tablette', 'visi-bloc-jlg'),
                icon: 'tablet',
            },
            {
                id: 'mobile-only',
                label: __('Mobile', 'visi-bloc-jlg'),
                icon: 'smartphone',
            },
        ],
    },
    {
        id: 'hide-on',
        label: __('Masquer sur', 'visi-bloc-jlg'),
        options: [
            {
                id: 'hide-on-desktop',
                label: __('Desktop', 'visi-bloc-jlg'),
                icon: 'desktop',
            },
            {
                id: 'hide-on-tablet',
                label: __('Tablette', 'visi-bloc-jlg'),
                icon: 'tablet',
            },
            {
                id: 'hide-on-mobile',
                label: __('Mobile', 'visi-bloc-jlg'),
                icon: 'smartphone',
            },
        ],
    },
];

const DEVICE_VISIBILITY_FLAT_OPTIONS = DEVICE_VISIBILITY_OPTIONS.reduce(
    (accumulator, group) => {
        if (group && Array.isArray(group.options)) {
            return accumulator.concat(group.options);
        }

        return accumulator;
    },
    [],
);

const SUPPORTED_ADVANCED_RULE_TYPES = [
    'post_type',
    'taxonomy',
    'template',
    'recurring_schedule',
    'logged_in_status',
    'user_role_group',
    'woocommerce_cart',
    'query_param',
];

const ADVANCED_RULE_TYPE_OPTIONS = [
    { value: 'post_type', label: __('Type de contenu', 'visi-bloc-jlg') },
    { value: 'taxonomy', label: __('Taxonomie', 'visi-bloc-jlg') },
    { value: 'template', label: __('Modèle de page', 'visi-bloc-jlg') },
    { value: 'recurring_schedule', label: __('Horaire récurrent', 'visi-bloc-jlg') },
    { value: 'logged_in_status', label: __('État de connexion', 'visi-bloc-jlg') },
    { value: 'user_role_group', label: __('Groupe de rôles', 'visi-bloc-jlg') },
    { value: 'woocommerce_cart', label: __('Panier WooCommerce', 'visi-bloc-jlg') },
    { value: 'query_param', label: __('Paramètre d’URL', 'visi-bloc-jlg') },
];

const DEFAULT_ADVANCED_VISIBILITY = Object.freeze({
    logic: 'AND',
    rules: [],
});

const DEFAULT_RECURRING_SCHEDULE = Object.freeze({
    frequency: 'daily',
    days: [],
    startTime: '08:00',
    endTime: '17:00',
});

const getVisiBlocArray = (key) => {
    if (typeof VisiBlocData !== 'object' || VisiBlocData === null) {
        return [];
    }

    const value = VisiBlocData[key];

    return Array.isArray(value) ? value : [];
};

const getVisiBlocObject = (key) => {
    if (typeof VisiBlocData !== 'object' || VisiBlocData === null) {
        return null;
    }

    const value = VisiBlocData[key];

    if (!value || typeof value !== 'object') {
        return null;
    }

    return value;
};

const getBlockHasFallback = (attrs) => {
    if (!attrs || typeof attrs !== 'object') {
        return false;
    }

    const fallbackEnabled = typeof attrs.fallbackEnabled === 'undefined' ? true : Boolean(attrs.fallbackEnabled);
    const fallbackBehavior = typeof attrs.fallbackBehavior === 'string' ? attrs.fallbackBehavior : 'inherit';

    if (!fallbackEnabled) {
        return false;
    }

    if (fallbackBehavior === 'text') {
        const fallbackCustomText = typeof attrs.fallbackCustomText === 'string' ? attrs.fallbackCustomText : '';

        return fallbackCustomText.trim().length > 0;
    }

    if (fallbackBehavior === 'block') {
        return Boolean(attrs.fallbackBlockId);
    }

    const fallbackSettings = getVisiBlocObject('fallbackSettings') || {};

    return Boolean(fallbackSettings && fallbackSettings.hasContent);
};

const DAY_OF_WEEK_LOOKUP = (() => {
    const entries = getVisiBlocArray('daysOfWeek');

    const map = new Map();

    entries.forEach((item) => {
        if (item && typeof item === 'object' && typeof item.value === 'string') {
            map.set(item.value, item.label || item.value);
        }
    });

    return map;
})();

const getDefaultAdvancedVisibility = () => ({
    logic: DEFAULT_ADVANCED_VISIBILITY.logic,
    rules: [...DEFAULT_ADVANCED_VISIBILITY.rules],
});

const createRuleId = () => `rule-${Math.random().toString(36).slice(2)}-${Date.now()}`;

const getFirstOptionValue = (options) => {
    if (!Array.isArray(options) || !options.length) {
        return '';
    }

    const first = options.find((option) => option && typeof option.value !== 'undefined');

    if (!first) {
        return '';
    }

    const firstValue = typeof first.value === 'undefined' || first.value === null ? '' : first.value;

    return String(firstValue);
};

const getDefaultPostTypeRule = () => {
    const options = getVisiBlocArray('postTypes');

    return {
        id: createRuleId(),
        type: 'post_type',
        operator: 'is',
        value: getFirstOptionValue(options),
    };
};

const getDefaultTaxonomyRule = () => {
    const taxonomies = getVisiBlocArray('taxonomies');
    const firstTaxonomy = taxonomies.find((item) => item && typeof item.slug === 'string');

    return {
        id: createRuleId(),
        type: 'taxonomy',
        operator: 'in',
        taxonomy: firstTaxonomy ? firstTaxonomy.slug : '',
        terms: [],
    };
};

const getDefaultTemplateRule = () => {
    const templates = getVisiBlocArray('templates');

    return {
        id: createRuleId(),
        type: 'template',
        operator: 'is',
        value: getFirstOptionValue(templates),
    };
};

const getDefaultRecurringRule = () => ({
    id: createRuleId(),
    type: 'recurring_schedule',
    operator: 'matches',
    ...DEFAULT_RECURRING_SCHEDULE,
});

const getDefaultLoggedInStatusRule = () => {
    const statuses = getVisiBlocArray('loginStatuses');

    return {
        id: createRuleId(),
        type: 'logged_in_status',
        operator: 'is',
        value: getFirstOptionValue(statuses),
    };
};

const getDefaultRoleGroupRule = () => {
    const groups = getVisiBlocArray('roleGroups');

    return {
        id: createRuleId(),
        type: 'user_role_group',
        operator: 'matches',
        group: getFirstOptionValue(groups),
    };
};

const getDefaultWooCommerceCartRule = () => {
    const sources = getVisiBlocArray('woocommerceTaxonomies');
    const firstTaxonomy = sources.find((item) => item && typeof item.slug === 'string');

    return {
        id: createRuleId(),
        type: 'woocommerce_cart',
        operator: 'contains',
        taxonomy: firstTaxonomy ? firstTaxonomy.slug : '',
        terms: [],
    };
};

const getDefaultQueryParamRule = () => {
    const params = getVisiBlocArray('commonQueryParams');

    return {
        id: createRuleId(),
        type: 'query_param',
        operator: 'equals',
        param: getFirstOptionValue(params),
        value: '',
    };
};

const createDefaultRuleForType = (type) => {
    switch (type) {
        case 'taxonomy':
            return getDefaultTaxonomyRule();
        case 'template':
            return getDefaultTemplateRule();
        case 'recurring_schedule':
            return getDefaultRecurringRule();
        case 'logged_in_status':
            return getDefaultLoggedInStatusRule();
        case 'user_role_group':
            return getDefaultRoleGroupRule();
        case 'woocommerce_cart':
            return getDefaultWooCommerceCartRule();
        case 'query_param':
            return getDefaultQueryParamRule();
        case 'post_type':
        default:
            return getDefaultPostTypeRule();
    }
};

const normalizeRule = (rule) => {
    if (!rule || typeof rule !== 'object') {
        return null;
    }

    const { type } = rule;

    if (!SUPPORTED_ADVANCED_RULE_TYPES.includes(type)) {
        return null;
    }

    const normalized = {
        id: typeof rule.id === 'string' && rule.id ? rule.id : createRuleId(),
        type,
    };

    if (type === 'post_type') {
        normalized.operator = rule.operator === 'is_not' ? 'is_not' : 'is';
        normalized.value = typeof rule.value === 'string' ? rule.value : '';

        return normalized;
    }

    if (type === 'taxonomy') {
        normalized.operator = rule.operator === 'not_in' ? 'not_in' : 'in';
        normalized.taxonomy = typeof rule.taxonomy === 'string' ? rule.taxonomy : '';
        normalized.terms = Array.isArray(rule.terms)
            ? rule.terms
                  .map((term) => (typeof term === 'string' || typeof term === 'number' ? String(term) : ''))
                  .filter(Boolean)
            : [];

        return normalized;
    }

    if (type === 'template') {
        normalized.operator = rule.operator === 'is_not' ? 'is_not' : 'is';
        normalized.value = typeof rule.value === 'string' ? rule.value : '';

        return normalized;
    }

    if (type === 'logged_in_status') {
        normalized.operator = rule.operator === 'is_not' ? 'is_not' : 'is';
        normalized.value = typeof rule.value === 'string' ? rule.value : '';

        return normalized;
    }

    if (type === 'user_role_group') {
        normalized.operator = rule.operator === 'does_not_match' ? 'does_not_match' : 'matches';
        normalized.group = typeof rule.group === 'string' ? rule.group : '';

        return normalized;
    }

    if (type === 'woocommerce_cart') {
        normalized.operator = rule.operator === 'not_contains' ? 'not_contains' : 'contains';
        normalized.taxonomy = typeof rule.taxonomy === 'string' ? rule.taxonomy : '';
        normalized.terms = Array.isArray(rule.terms)
            ? rule.terms
                  .map((term) => (typeof term === 'string' || typeof term === 'number' ? String(term) : ''))
                  .filter(Boolean)
            : [];

        return normalized;
    }

    if (type === 'query_param') {
        const allowedOperators = [
            'equals',
            'not_equals',
            'contains',
            'not_contains',
            'exists',
            'not_exists',
        ];

        normalized.operator = allowedOperators.includes(rule.operator)
            ? rule.operator
            : 'equals';
        normalized.param = typeof rule.param === 'string' ? rule.param : '';
        normalized.value = typeof rule.value === 'string' ? rule.value : '';

        return normalized;
    }

    // Recurring schedule
    normalized.operator = 'matches';
    normalized.frequency = rule.frequency === 'weekly' ? 'weekly' : 'daily';
    normalized.days = Array.isArray(rule.days)
        ? rule.days
              .map((day) => (typeof day === 'string' ? day : ''))
              .filter((day) => DAY_OF_WEEK_LOOKUP.has(day))
        : [];
    normalized.startTime = typeof rule.startTime === 'string' ? rule.startTime : DEFAULT_RECURRING_SCHEDULE.startTime;
    normalized.endTime = typeof rule.endTime === 'string' ? rule.endTime : DEFAULT_RECURRING_SCHEDULE.endTime;

    return normalized;
};

const normalizeAdvancedVisibility = (value) => {
    if (!value || typeof value !== 'object') {
        return getDefaultAdvancedVisibility();
    }

    const logic = value.logic === 'OR' ? 'OR' : 'AND';
    const rules = Array.isArray(value.rules)
        ? value.rules
              .map(normalizeRule)
              .filter(Boolean)
        : [];

    return {
        logic,
        rules,
    };
};

function addVisibilityAttributesToGroup(settings, name) {
    if (!isSupportedBlockName(name)) {
        return settings;
    }

    settings.attributes = {
        ...settings.attributes,
        isHidden: {
            type: 'boolean',
            default: false,
        },
        deviceVisibility: {
            type: 'string',
            default: 'all',
        },
        isSchedulingEnabled: {
            type: 'boolean',
            default: false,
        },
        publishStartDate: {
            type: 'string',
        },
        publishEndDate: {
            type: 'string',
        },
        visibilityRoles: {
            type: 'array',
            default: [],
        },
        advancedVisibility: {
            type: 'object',
            default: DEFAULT_ADVANCED_VISIBILITY,
        },
        fallbackEnabled: {
            type: 'boolean',
            default: true,
        },
        fallbackBehavior: {
            type: 'string',
            default: 'inherit',
        },
        fallbackCustomText: {
            type: 'string',
            default: '',
        },
        fallbackBlockId: {
            type: 'number',
        },
    };

    return settings;
}

const withVisibilityControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (!isSupportedBlockName(props.name)) {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes, isSelected } = props;
        const {
            isHidden,
            deviceVisibility,
            isSchedulingEnabled,
            publishStartDate,
            publishEndDate,
            visibilityRoles,
            advancedVisibility: rawAdvancedVisibility,
            fallbackEnabled = true,
            fallbackBehavior = 'inherit',
            fallbackCustomText = '',
            fallbackBlockId,
        } = attributes;

        const advancedVisibility = normalizeAdvancedVisibility(rawAdvancedVisibility);
        const fallbackSettings = getVisiBlocObject('fallbackSettings') || {};
        const hasGlobalFallback = Boolean(fallbackSettings && fallbackSettings.hasContent);
        const globalFallbackSummary = fallbackSettings && typeof fallbackSettings.summary === 'string'
            ? fallbackSettings.summary
            : '';
        const fallbackBlocks = getVisiBlocArray('fallbackBlocks');
        const fallbackBlockOptions = fallbackBlocks
            .filter((item) => item && typeof item === 'object')
            .map((item) => {
                const rawValue = typeof item.value === 'number' ? item.value : parseInt(item.value, 10);
                const numericValue = Number.isNaN(rawValue) ? 0 : rawValue;

                return {
                    value: String(numericValue),
                    label: typeof item.label === 'string' && item.label.trim()
                        ? item.label
                        : `#${numericValue}`,
                };
            });
        const fallbackBehaviorOptions = [
            { value: 'inherit', label: __('Utiliser le repli global', 'visi-bloc-jlg') },
            { value: 'text', label: __('Texte personnalisé', 'visi-bloc-jlg') },
            { value: 'block', label: __('Bloc réutilisable', 'visi-bloc-jlg') },
        ];

        const updateAdvancedVisibility = (updater) => {
            const current = normalizeAdvancedVisibility({ ...advancedVisibility });
            const next = updater(current) || current;

            setAttributes({
                advancedVisibility: normalizeAdvancedVisibility(next),
            });
        };

        const onRoleChange = (isChecked, roleSlug) => {
            const newRoles = isChecked
                ? [...visibilityRoles, roleSlug]
                : visibilityRoles.filter((role) => role !== roleSlug);

            setAttributes({
                visibilityRoles: newRoles,
            });
        };

        let scheduleSummary = __('Aucune programmation.', 'visi-bloc-jlg');

        const timezoneSummary = sprintf(
            __('Fuseau horaire : %s', 'visi-bloc-jlg'),
            TIMEZONE_LABEL,
        );

        const startDateObj = parseDateValue(publishStartDate);
        const endDateObj = parseDateValue(publishEndDate);
        const hasScheduleRangeError =
            isSchedulingEnabled && !!startDateObj && !!endDateObj && endDateObj.getTime() < startDateObj.getTime();

        if (isSchedulingEnabled) {
            const startDate = formatScheduleDate(publishStartDate);
            const endDate = formatScheduleDate(publishEndDate);

            if (startDate && endDate) {
                /* translators: 1: Start date, 2: end date. */
                scheduleSummary = sprintf(
                    __('Du %s au %s.', 'visi-bloc-jlg'),
                    startDate,
                    endDate,
                );
            } else if (startDate) {
                /* translators: %s: Start date. */
                scheduleSummary = sprintf(
                    __('À partir du %s.', 'visi-bloc-jlg'),
                    startDate,
                );
            } else if (endDate) {
                /* translators: %s: End date. */
                scheduleSummary = sprintf(
                    __('Jusqu\'au %s.', 'visi-bloc-jlg'),
                    endDate,
                );
            } else {
                scheduleSummary = __('Activée, mais sans date définie.', 'visi-bloc-jlg');
            }

            if (hasScheduleRangeError) {
                scheduleSummary = __('Dates de programmation invalides.', 'visi-bloc-jlg');
            }
        }

        const renderAdvancedRule = (rule, index) => {
            const onUpdateRule = (partial) => {
                updateAdvancedVisibility((current) => {
                    const rules = [...current.rules];
                    rules[index] = normalizeRule({ ...rule, ...partial });

                    return {
                        ...current,
                        rules,
                    };
                });
            };

            const onChangeType = (newType) => {
                if (!SUPPORTED_ADVANCED_RULE_TYPES.includes(newType)) {
                    return;
                }

                updateAdvancedVisibility((current) => {
                    const rules = [...current.rules];
                    const replacement = createDefaultRuleForType(newType);
                    rules[index] = replacement;

                    return {
                        ...current,
                        rules,
                    };
                });
            };

            const onRemove = () => {
                updateAdvancedVisibility((current) => {
                    const rules = current.rules.filter((_, ruleIndex) => ruleIndex !== index);

                    return {
                        ...current,
                        rules,
                    };
                });
            };

            const commonHeader = (
                <Flex align="center" wrap>
                    <FlexBlock>
                        <SelectControl
                            label={__('Type de règle', 'visi-bloc-jlg')}
                            value={rule.type}
                            options={ADVANCED_RULE_TYPE_OPTIONS}
                            onChange={onChangeType}
                        />
                    </FlexBlock>
                    <FlexItem>
                        <Button isDestructive variant="tertiary" onClick={onRemove}>
                            {__('Supprimer', 'visi-bloc-jlg')}
                        </Button>
                    </FlexItem>
                </Flex>
            );

            if (rule.type === 'post_type') {
                const options = getVisiBlocArray('postTypes')
                    .map((item) => ({
                          value: item.value,
                          label: item.label,
                      }));

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                { value: 'is', label: __('Est', 'visi-bloc-jlg') },
                                { value: 'is_not', label: __('N’est pas', 'visi-bloc-jlg') },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('Type de contenu', 'visi-bloc-jlg')}
                            value={rule.value}
                            options={options}
                            onChange={(newValue) => onUpdateRule({ value: newValue })}
                        />
                    </div>
                );
            }

            if (rule.type === 'taxonomy') {
                const taxonomies = getVisiBlocArray('taxonomies');

                const currentTaxonomy = taxonomies.find((item) => item.slug === rule.taxonomy);
                const taxonomyOptions = taxonomies.map((item) => ({
                    value: item.slug,
                    label: item.label,
                }));
                const taxonomyTerms = currentTaxonomy && Array.isArray(currentTaxonomy.terms)
                    ? currentTaxonomy.terms
                    : [];
                const termOptions = taxonomyTerms.map((term) => ({
                    value: term.value,
                    label: term.label,
                }));

                const onToggleTerm = (isChecked, termValue) => {
                    const valueAsString = String(termValue);
                    const newTerms = isChecked
                        ? [...rule.terms, valueAsString]
                        : rule.terms.filter((currentTerm) => currentTerm !== valueAsString);

                    onUpdateRule({ terms: newTerms });
                };

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Taxonomie', 'visi-bloc-jlg')}
                            value={rule.taxonomy}
                            options={taxonomyOptions}
                            onChange={(newTaxonomy) =>
                                onUpdateRule({ taxonomy: newTaxonomy, terms: [] })
                            }
                        />
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                { value: 'in', label: __('Inclut au moins un terme', 'visi-bloc-jlg') },
                                { value: 'not_in', label: __('Exclut tous les termes', 'visi-bloc-jlg') },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        {termOptions.length > 0 ? (
                            <div className="visibloc-advanced-rule__terms">
                                {termOptions.map((term) => (
                                    <CheckboxControl
                                        key={term.value}
                                        label={term.label}
                                        checked={rule.terms.includes(term.value)}
                                        onChange={(isChecked) => onToggleTerm(isChecked, term.value)}
                                    />
                                ))}
                            </div>
                        ) : (
                            <p className="components-help-text">
                                {__(
                                    'Aucun terme disponible pour cette taxonomie.',
                                    'visi-bloc-jlg',
                                )}
                            </p>
                        )}
                    </div>
                );
            }

            if (rule.type === 'template') {
                const templates = getVisiBlocArray('templates')
                    .map((item) => ({
                          value: item.value,
                          label: item.label,
                      }));

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                { value: 'is', label: __('Est', 'visi-bloc-jlg') },
                                { value: 'is_not', label: __('N’est pas', 'visi-bloc-jlg') },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('Modèle', 'visi-bloc-jlg')}
                            value={rule.value}
                            options={templates}
                            onChange={(newValue) => onUpdateRule({ value: newValue })}
                        />
                    </div>
                );
            }

            if (rule.type === 'logged_in_status') {
                const statuses = getVisiBlocArray('loginStatuses').map((item) => ({
                    value: item.value,
                    label: item.label,
                }));

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                { value: 'is', label: __('Est', 'visi-bloc-jlg') },
                                { value: 'is_not', label: __('N’est pas', 'visi-bloc-jlg') },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('État de connexion', 'visi-bloc-jlg')}
                            value={rule.value}
                            options={statuses}
                            onChange={(newValue) => onUpdateRule({ value: newValue })}
                        />
                    </div>
                );
            }

            if (rule.type === 'user_role_group') {
                const roleGroups = getVisiBlocArray('roleGroups');
                const roleLabels = getVisiBlocObject('roles') || {};
                const currentGroup = roleGroups.find((group) => group && group.value === rule.group);
                const groupRoles = currentGroup && Array.isArray(currentGroup.roles)
                    ? currentGroup.roles
                    : [];

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                {
                                    value: 'matches',
                                    label: __('Correspond à', 'visi-bloc-jlg'),
                                },
                                {
                                    value: 'does_not_match',
                                    label: __('Ne correspond pas à', 'visi-bloc-jlg'),
                                },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('Groupe de rôles', 'visi-bloc-jlg')}
                            value={rule.group}
                            options={roleGroups.map((group) => ({
                                value: group.value,
                                label: group.label,
                            }))}
                            onChange={(newValue) => onUpdateRule({ group: newValue })}
                        />
                        {groupRoles.length > 0 && (
                            <p className="components-help-text">
                                {groupRoles
                                    .map((role) => roleLabels[role] || role)
                                    .filter(Boolean)
                                    .join(', ')}
                            </p>
                        )}
                    </div>
                );
            }

            if (rule.type === 'woocommerce_cart') {
                const taxonomies = getVisiBlocArray('woocommerceTaxonomies');
                const hasTaxonomies = Array.isArray(taxonomies) && taxonomies.length > 0;
                const currentTaxonomy = taxonomies.find(
                    (item) => item && item.slug === rule.taxonomy,
                );
                const taxonomyOptions = hasTaxonomies
                    ? taxonomies.map((item) => ({
                          value: item.slug,
                          label: item.label,
                      }))
                    : [
                          {
                              value: '',
                              label: __('Aucune taxonomie disponible', 'visi-bloc-jlg'),
                          },
                      ];
                const termOptions = currentTaxonomy && Array.isArray(currentTaxonomy.terms)
                    ? currentTaxonomy.terms.map((term) => ({
                          value: term.value,
                          label: term.label,
                      }))
                    : [];

                const onToggleTerm = (isChecked, termValue) => {
                    const valueAsString = String(termValue);
                    const newTerms = isChecked
                        ? [...rule.terms, valueAsString]
                        : rule.terms.filter((currentTerm) => currentTerm !== valueAsString);

                    onUpdateRule({ terms: newTerms });
                };

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={[
                                {
                                    value: 'contains',
                                    label: __('Contient des produits avec ces termes', 'visi-bloc-jlg'),
                                },
                                {
                                    value: 'not_contains',
                                    label: __('Ne contient aucun produit avec ces termes', 'visi-bloc-jlg'),
                                },
                            ]}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('Taxonomie WooCommerce', 'visi-bloc-jlg')}
                            value={rule.taxonomy}
                            options={taxonomyOptions}
                            onChange={(newValue) => onUpdateRule({ taxonomy: newValue, terms: [] })}
                            disabled={!hasTaxonomies}
                        />
                        {!hasTaxonomies ? (
                            <p className="components-help-text">
                                {__(
                                    'Aucune source WooCommerce n’est disponible. Activez WooCommerce pour utiliser cette règle.',
                                    'visi-bloc-jlg',
                                )}
                            </p>
                        ) : termOptions.length > 0 ? (
                            <div className="visibloc-advanced-rule__terms">
                                {termOptions.map((term) => (
                                    <CheckboxControl
                                        key={term.value}
                                        label={term.label}
                                        checked={rule.terms.includes(term.value)}
                                        onChange={(isChecked) => onToggleTerm(isChecked, term.value)}
                                    />
                                ))}
                            </div>
                        ) : (
                            <p className="components-help-text">
                                {__(
                                    'Aucun terme disponible pour cette taxonomie WooCommerce.',
                                    'visi-bloc-jlg',
                                )}
                            </p>
                        )}
                    </div>
                );
            }

            if (rule.type === 'query_param') {
                const suggestions = getVisiBlocArray('commonQueryParams').map((item) => ({
                    value: item.value,
                    label: item.label || item.value,
                }));

                const suggestionOptions = [
                    { value: '', label: __('Personnalisé…', 'visi-bloc-jlg') },
                    ...suggestions,
                ];

                const operatorOptions = [
                    { value: 'equals', label: __('Est égal à', 'visi-bloc-jlg') },
                    { value: 'not_equals', label: __('Est différent de', 'visi-bloc-jlg') },
                    { value: 'contains', label: __('Contient', 'visi-bloc-jlg') },
                    { value: 'not_contains', label: __('Ne contient pas', 'visi-bloc-jlg') },
                    { value: 'exists', label: __('Existe', 'visi-bloc-jlg') },
                    { value: 'not_exists', label: __('N’existe pas', 'visi-bloc-jlg') },
                ];

                const selectedSuggestion = suggestions.some((item) => item.value === rule.param)
                    ? rule.param
                    : '';

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={operatorOptions}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <SelectControl
                            label={__('Paramètre courant', 'visi-bloc-jlg')}
                            value={selectedSuggestion}
                            options={suggestionOptions}
                            onChange={(newValue) => {
                                if (!newValue) {
                                    return;
                                }

                                onUpdateRule({ param: newValue });
                            }}
                        />
                        <TextControl
                            label={__('Nom du paramètre', 'visi-bloc-jlg')}
                            value={rule.param}
                            onChange={(newValue) => onUpdateRule({ param: newValue })}
                            help={__(
                                'Saisissez le nom du paramètre de requête attendu (ex. utm_source).',
                                'visi-bloc-jlg',
                            )}
                        />
                        {!['exists', 'not_exists'].includes(rule.operator) && (
                            <TextControl
                                label={__('Valeur attendue', 'visi-bloc-jlg')}
                                value={rule.value}
                                onChange={(newValue) => onUpdateRule({ value: newValue })}
                            />
                        )}
                    </div>
                );
            }

            // Recurring schedule rule
            const onTimeChange = (field) => (event) => {
                const rawValue = event && event.target ? event.target.value : '';
                const newValue = typeof rawValue === 'string' ? rawValue : '';
                onUpdateRule({ [field]: newValue });
            };

            const onToggleDay = (isChecked, day) => {
                const newDays = isChecked
                    ? [...new Set([...rule.days, day])]
                    : rule.days.filter((currentDay) => currentDay !== day);
                onUpdateRule({ days: newDays });
            };

            return (
                <div key={rule.id} className="visibloc-advanced-rule">
                    {commonHeader}
                    <SelectControl
                        label={__('Fréquence', 'visi-bloc-jlg')}
                        value={rule.frequency}
                        options={[
                            { value: 'daily', label: __('Quotidien', 'visi-bloc-jlg') },
                            { value: 'weekly', label: __('Hebdomadaire', 'visi-bloc-jlg') },
                        ]}
                        onChange={(newFrequency) =>
                            onUpdateRule({
                                frequency: newFrequency,
                                days: newFrequency === 'weekly' ? rule.days : [],
                            })
                        }
                    />
                    <Flex gap="small">
                        <FlexBlock>
                            <BaseControl label={__('Heure de début', 'visi-bloc-jlg')}>
                                <input
                                    type="time"
                                    value={rule.startTime || ''}
                                    onChange={onTimeChange('startTime')}
                                    className="components-text-control__input"
                                />
                            </BaseControl>
                        </FlexBlock>
                        <FlexBlock>
                            <BaseControl label={__('Heure de fin', 'visi-bloc-jlg')}>
                                <input
                                    type="time"
                                    value={rule.endTime || ''}
                                    onChange={onTimeChange('endTime')}
                                    className="components-text-control__input"
                                />
                            </BaseControl>
                        </FlexBlock>
                    </Flex>
                    {rule.frequency === 'weekly' && DAY_OF_WEEK_LOOKUP.size > 0 && (
                        <div className="visibloc-advanced-rule__days">
                            <p className="components-base-control__label">
                                {__('Jours actifs', 'visi-bloc-jlg')}
                            </p>
                            {Array.from(DAY_OF_WEEK_LOOKUP.entries()).map(([value, label]) => (
                                <CheckboxControl
                                    key={value}
                                    label={label}
                                    checked={rule.days.includes(value)}
                                    onChange={(isChecked) => onToggleDay(isChecked, value)}
                                />
                            ))}
                        </div>
                    )}
                </div>
            );
        };

        const inactiveSummaryLabel = __('Inactif', 'visi-bloc-jlg');

        const panelTitleWithSummary = (label, summary) => (
            <Fragment>
                <span>{label}</span>
                <span className="components-panel__summary">
                    {summary && String(summary).trim() ? summary : inactiveSummaryLabel}
                </span>
            </Fragment>
        );

        const deviceVisibilitySummary = (() => {
            if (!deviceVisibility || deviceVisibility === DEVICE_VISIBILITY_DEFAULT_OPTION.id) {
                return '';
            }

            const group = DEVICE_VISIBILITY_OPTIONS.find((item) =>
                Array.isArray(item.options)
                    ? item.options.some((option) => option.id === deviceVisibility)
                    : false,
            );

            const option = DEVICE_VISIBILITY_FLAT_OPTIONS.find(
                (item) => item.id === deviceVisibility,
            );

            if (!option) {
                return '';
            }

            return group ? `${group.label} – ${option.label}` : option.label;
        })();

        const schedulingSummaryLabel = (() => {
            if (!isSchedulingEnabled) {
                return '';
            }

            if (hasScheduleRangeError) {
                return __('Erreur de dates', 'visi-bloc-jlg');
            }

            if (publishStartDate && publishEndDate) {
                return __('Plage définie', 'visi-bloc-jlg');
            }

            if (publishStartDate || publishEndDate) {
                return __('Date définie', 'visi-bloc-jlg');
            }

            return __('Programmation active', 'visi-bloc-jlg');
        })();

        const rolesSummary = (() => {
            const uniqueRoles = Array.from(new Set((visibilityRoles || []).filter(Boolean)));
            const count = uniqueRoles.length;

            if (!count) {
                return '';
            }

            return sprintf(
                _n('%d rôle', '%d rôles', count, 'visi-bloc-jlg'),
                count,
            );
        })();

        const advancedRulesSummary = (() => {
            const rulesCount = Array.isArray(advancedVisibility.rules)
                ? advancedVisibility.rules.length
                : 0;

            if (!rulesCount) {
                return '';
            }

            const logicLabel =
                advancedVisibility.logic === 'OR'
                    ? __('OU', 'visi-bloc-jlg')
                    : __('ET', 'visi-bloc-jlg');

            return sprintf(
                _n('%1$d règle %2$s', '%1$d règles %2$s', rulesCount, 'visi-bloc-jlg'),
                rulesCount,
                logicLabel,
            );
        })();

        const fallbackSummary = (() => {
            if (!fallbackEnabled) {
                return '';
            }

            const summaries = {
                inherit: __('Global', 'visi-bloc-jlg'),
                text: __('Texte', 'visi-bloc-jlg'),
                block: __('Bloc', 'visi-bloc-jlg'),
            };

            if (fallbackBehavior === 'block' && !fallbackBlockId) {
                return __('Bloc', 'visi-bloc-jlg');
            }

            return summaries[fallbackBehavior] || summaries.inherit;
        })();

        return (
            <Fragment>
                <BlockEdit {...props} />
                {isSelected && (
                    <Fragment>
                        <BlockControls>
                            <ToolbarGroup>
                                <ToolbarButton
                                    icon="visibility"
                                    label={__('Rendre visible', 'visi-bloc-jlg')}
                                    onClick={() => setAttributes({ isHidden: false })}
                                    isActive={isHidden === false}
                                />
                                <ToolbarButton
                                    icon="hidden"
                                    label={__('Rendre caché', 'visi-bloc-jlg')}
                                    onClick={() => setAttributes({ isHidden: true })}
                                    isActive={isHidden === true}
                                />
                            </ToolbarGroup>
                        </BlockControls>
                        <InspectorControls>
                            <PanelBody
                                title={panelTitleWithSummary(
                                    __('Contrôles de Visibilité', 'visi-bloc-jlg'),
                                    deviceVisibilitySummary,
                                )}
                                initialOpen={true}
                            >
                                <BaseControl label={__('Visibilité par Appareil', 'visi-bloc-jlg')}>
                                    <div className="visi-bloc-device-toggle-groups">
                                        {DEVICE_VISIBILITY_OPTIONS.map((group) => {
                                            const isGroupActive = group.options.some(
                                                (option) => option.id === deviceVisibility,
                                            );

                                            return (
                                                <div
                                                    key={group.id}
                                                    className="visi-bloc-device-toggle-group"
                                                >
                                                    <BaseControl.VisualLabel>
                                                        {group.label}
                                                    </BaseControl.VisualLabel>
                                                    <ToggleGroupControl
                                                        className="visi-bloc-device-toggle"
                                                        isBlock
                                                        isDeselectable
                                                        value={isGroupActive ? deviceVisibility : undefined}
                                                        onChange={(newValue) =>
                                                            setAttributes({
                                                                deviceVisibility:
                                                                    newValue ||
                                                                    DEVICE_VISIBILITY_DEFAULT_OPTION.id,
                                                            })
                                                        }
                                                    >
                                                        {group.options.map((option) => (
                                                            <ToggleGroupControlOptionIcon
                                                                key={option.id}
                                                                value={option.id}
                                                                icon={option.icon}
                                                                label={option.label}
                                                            />
                                                        ))}
                                                    </ToggleGroupControl>
                                                </div>
                                            );
                                        })}
                                        <Button
                                            className="visi-bloc-device-toggle-reset"
                                            variant="link"
                                            isLink
                                            onClick={() =>
                                                setAttributes({
                                                    deviceVisibility:
                                                        DEVICE_VISIBILITY_DEFAULT_OPTION.id,
                                                })
                                            }
                                            disabled={
                                                deviceVisibility ===
                                                DEVICE_VISIBILITY_DEFAULT_OPTION.id
                                            }
                                        >
                                            {DEVICE_VISIBILITY_DEFAULT_OPTION.label}
                                        </Button>
                                    </div>
                                </BaseControl>
                            </PanelBody>
                            <PanelBody
                                title={panelTitleWithSummary(
                                    __('Programmation', 'visi-bloc-jlg'),
                                    schedulingSummaryLabel,
                                )}
                                initialOpen={false}
                                className="visi-bloc-panel-schedule"
                            >
                                <ToggleControl
                                    label={__('Activer la programmation', 'visi-bloc-jlg')}
                                    checked={isSchedulingEnabled}
                                    onChange={() =>
                                        setAttributes({
                                            isSchedulingEnabled: !isSchedulingEnabled,
                                        })
                                    }
                                />
                                {isSchedulingEnabled && (
                                    <div>
                                        <p
                                            style={{
                                                fontStyle: 'italic',
                                                color: '#555',
                                            }}
                                        >
                                            {scheduleSummary}
                                        </p>
                                        {hasScheduleRangeError && (
                                            <Notice status="error" isDismissible={false}>
                                                {__(
                                                    'La date de fin doit être postérieure à la date de début.',
                                                    'visi-bloc-jlg',
                                                )}
                                            </Notice>
                                        )}
                                        <p
                                            style={{
                                                fontStyle: 'italic',
                                                color: '#555',
                                            }}
                                        >
                                            {timezoneSummary}
                                        </p>
                                        <CheckboxControl
                                            label={__('Définir une date de début', 'visi-bloc-jlg')}
                                            checked={!!publishStartDate}
                                            onChange={(isChecked) => {
                                                setAttributes({
                                                    publishStartDate: isChecked
                                                        ? getCurrentSiteIsoDate()
                                                        : undefined,
                                                });
                                            }}
                                        />
                                        {!!publishStartDate && (
                                            <div className="visi-bloc-datepicker-wrapper">
                                                <DateTimePicker
                                                    currentDate={publishStartDate}
                                                    onChange={(newDate) =>
                                                        setAttributes({
                                                            publishStartDate: newDate,
                                                        })
                                                    }
                                                    is12Hour={is12Hour}
                                                />
                                            </div>
                                        )}
                                        <CheckboxControl
                                            label={__('Définir une date de fin', 'visi-bloc-jlg')}
                                            checked={!!publishEndDate}
                                            onChange={(isChecked) => {
                                                setAttributes({
                                                    publishEndDate: isChecked
                                                        ? getCurrentSiteIsoDate()
                                                        : undefined,
                                                });
                                            }}
                                        />
                                        {!!publishEndDate && (
                                            <div className="visi-bloc-datepicker-wrapper">
                                                <DateTimePicker
                                                    currentDate={publishEndDate}
                                                    onChange={(newDate) =>
                                                        setAttributes({
                                                            publishEndDate: newDate,
                                                        })
                                                    }
                                                    is12Hour={is12Hour}
                                                />
                                            </div>
                                        )}
                                    </div>
                                )}
                            </PanelBody>
                            <PanelBody
                                title={panelTitleWithSummary(
                                    __('Visibilité par Rôle', 'visi-bloc-jlg'),
                                    rolesSummary,
                                )}
                                initialOpen={false}
                            >
                                <p>
                                    {__(
                                        "N'afficher que pour les rôles sélectionnés. Laisser vide pour afficher à tout le monde.",
                                        'visi-bloc-jlg',
                                    )}
                                </p>
                                <CheckboxControl
                                    label={__('Visiteurs Déconnectés', 'visi-bloc-jlg')}
                                    checked={visibilityRoles.includes('logged-out')}
                                    onChange={(isChecked) =>
                                        onRoleChange(isChecked, 'logged-out')
                                    }
                                />
                                <CheckboxControl
                                    label={__('Utilisateurs Connectés (tous)', 'visi-bloc-jlg')}
                                    checked={visibilityRoles.includes('logged-in')}
                                    onChange={(isChecked) =>
                                        onRoleChange(isChecked, 'logged-in')
                                    }
                                />
                                <hr />
                                {Object.entries(VisiBlocData.roles || {})
                                    .sort(([, firstLabel], [, secondLabel]) =>
                                        String(firstLabel).localeCompare(String(secondLabel)),
                                    )
                                    .map(([slug, name]) => (
                                        <CheckboxControl
                                            key={slug}
                                            label={name}
                                            checked={visibilityRoles.includes(slug)}
                                            onChange={(isChecked) =>
                                                onRoleChange(isChecked, slug)
                                            }
                                        />
                                    ))}
                            </PanelBody>
                            <PanelBody
                                title={panelTitleWithSummary(
                                    __('Règles de visibilité avancées', 'visi-bloc-jlg'),
                                    advancedRulesSummary,
                                )}
                                initialOpen={false}
                            >
                                <SelectControl
                                    label={__('Logique entre les règles', 'visi-bloc-jlg')}
                                    value={advancedVisibility.logic}
                                    options={[
                                        { value: 'AND', label: __('Toutes les règles doivent être vraies (ET)', 'visi-bloc-jlg') },
                                        { value: 'OR', label: __('Au moins une règle doit être vraie (OU)', 'visi-bloc-jlg') },
                                    ]}
                                    onChange={(newLogic) =>
                                        updateAdvancedVisibility((current) => ({
                                            ...current,
                                            logic: newLogic === 'OR' ? 'OR' : 'AND',
                                        }))
                                    }
                                />
                                {advancedVisibility.rules.map((rule, index) =>
                                    renderAdvancedRule(rule, index),
                                )}
                                <DropdownMenu
                                    text={__('Ajouter une règle de…', 'visi-bloc-jlg')}
                                    label={__('Ajouter une règle de…', 'visi-bloc-jlg')}
                                    toggleProps={{ variant: 'secondary' }}
                                >
                                    {({ onClose }) => (
                                        <MenuGroup
                                            label={__('Types de règles disponibles', 'visi-bloc-jlg')}
                                        >
                                            {ADVANCED_RULE_TYPE_OPTIONS.map((option) => (
                                                <MenuItem
                                                    key={option.value}
                                                    onClick={() => {
                                                        updateAdvancedVisibility((current) => ({
                                                            ...current,
                                                            rules: [
                                                                ...current.rules,
                                                                createDefaultRuleForType(option.value),
                                                            ],
                                                        }));
                                                        onClose();
                                                    }}
                                                >
                                                    {option.label}
                                                </MenuItem>
                                            ))}
                                        </MenuGroup>
                                    )}
                                </DropdownMenu>
                                <p className="components-help-text">
                                    {__(
                                        'Ces règles permettent d’affiner la visibilité selon le contexte du contenu, le modèle ou un horaire récurrent.',
                                        'visi-bloc-jlg',
                                    )}
                                </p>
                            </PanelBody>
                            <PanelBody
                                title={panelTitleWithSummary(
                                    __('Contenu de repli', 'visi-bloc-jlg'),
                                    fallbackSummary,
                                )}
                                initialOpen={false}
                            >
                                <ToggleControl
                                    label={__('Activer le repli pour ce bloc', 'visi-bloc-jlg')}
                                    checked={fallbackEnabled}
                                    onChange={() =>
                                        setAttributes({ fallbackEnabled: !fallbackEnabled })
                                    }
                                />
                                {!fallbackEnabled && (
                                    <Notice status="info" isDismissible={false}>
                                        {__('Aucun contenu de repli ne sera affiché si ce bloc est masqué.', 'visi-bloc-jlg')}
                                    </Notice>
                                )}
                                {fallbackEnabled && (
                                    <Fragment>
                                        <SelectControl
                                            label={__('Source du repli', 'visi-bloc-jlg')}
                                            value={fallbackBehavior}
                                            options={fallbackBehaviorOptions}
                                            onChange={(newBehavior) =>
                                                setAttributes({
                                                    fallbackBehavior: fallbackBehaviorOptions.some(
                                                        (option) => option.value === newBehavior,
                                                    )
                                                        ? newBehavior
                                                        : 'inherit',
                                                })
                                            }
                                        />
                                        {fallbackBehavior === 'inherit' && (
                                            hasGlobalFallback ? (
                                                <Notice status="info" isDismissible={false}>
                                                    {globalFallbackSummary
                                                        ? sprintf(
                                                              __('Repli global : %s', 'visi-bloc-jlg'),
                                                              globalFallbackSummary,
                                                          )
                                                        : __('Un repli global est configuré.', 'visi-bloc-jlg')}
                                                </Notice>
                                            ) : (
                                                <Notice status="warning" isDismissible={false}>
                                                    {__('Aucun repli global n’est actuellement défini.', 'visi-bloc-jlg')}
                                                </Notice>
                                            )
                                        )}
                                        {fallbackBehavior === 'text' && (
                                            <TextareaControl
                                                label={__('Texte affiché en repli', 'visi-bloc-jlg')}
                                                help={__('Ce texte remplace le bloc lorsque les visiteurs n’y ont pas accès.', 'visi-bloc-jlg')}
                                                value={fallbackCustomText}
                                                onChange={(value) =>
                                                    setAttributes({ fallbackCustomText: value })
                                                }
                                            />
                                        )}
                                        {fallbackBehavior === 'block' && (
                                            <Fragment>
                                                <SelectControl
                                                    label={__('Bloc réutilisable à afficher', 'visi-bloc-jlg')}
                                                    value={fallbackBlockId ? String(fallbackBlockId) : ''}
                                                    options={[
                                                        {
                                                            value: '',
                                                            label: __('— Sélectionnez un bloc —', 'visi-bloc-jlg'),
                                                        },
                                                        ...fallbackBlockOptions,
                                                    ]}
                                                    onChange={(newValue) => {
                                                        const parsedValue = parseInt(newValue, 10);

                                                        setAttributes({
                                                            fallbackBlockId: Number.isNaN(parsedValue)
                                                                ? 0
                                                                : parsedValue,
                                                        });
                                                    }}
                                                    disabled={!fallbackBlockOptions.length}
                                                />
                                                {!fallbackBlockOptions.length && (
                                                    <Notice status="warning" isDismissible={false}>
                                                        {__('Aucun bloc réutilisable publié n’est disponible.', 'visi-bloc-jlg')}
                                                    </Notice>
                                                )}
                                            </Fragment>
                                        )}
                                    </Fragment>
                                )}
                            </PanelBody>
                        </InspectorControls>
                    </Fragment>
                )}
            </Fragment>
        );
    };
}, 'withVisibilityControls');

function addEditorCanvasClasses(props, block) {
    if (!isSupportedBlockName(block.name) || !block.attributes) {
        return props;
    }

    const { isHidden } = block.attributes;
    const hasFallbackIndicator = getBlockHasFallback(block.attributes);

    const newClasses = [
        props.className,
        isHidden ? 'bloc-editeur-cache' : '',
        hasFallbackIndicator ? 'bloc-editeur-repli' : '',
    ]
        .filter(Boolean)
        .join(' ');

    return {
        ...props,
        className: newClasses,
    };
}

function addSaveClasses(extraProps, blockType, attributes) {
    if (!isSupportedBlockName(blockType.name) || !attributes) {
        return extraProps;
    }

    const { deviceVisibility } = attributes;
    const newClasses = [
        extraProps.className,
        deviceVisibility && deviceVisibility !== 'all'
            ? `vb-${deviceVisibility}`
            : '',
    ]
        .filter(Boolean)
        .join(' ');

    return {
        ...extraProps,
        className: newClasses,
    };
}

const blockVisibilityState = new Map();
const pendingListViewUpdates = new Map();
let listViewRafHandle = null;

function getIsListViewOpened() {
    const editPostStore = select('core/edit-post');

    if (!editPostStore) {
        return false;
    }

    if (typeof editPostStore.isListViewOpened === 'function') {
        return editPostStore.isListViewOpened();
    }

    if (typeof editPostStore.isFeatureActive === 'function') {
        return editPostStore.isFeatureActive('listView');
    }

    return false;
}

function replayPendingListViewUpdates() {
    if (!pendingListViewUpdates.size) {
        return;
    }

    const updates = Array.from(pendingListViewUpdates.entries());

    pendingListViewUpdates.clear();

    if (listViewRafHandle) {
        window.cancelAnimationFrame(listViewRafHandle);
        listViewRafHandle = null;
    }

    updates.forEach(([clientId, state]) => {
        queueListViewUpdate(clientId, state);
    });
}

function flushListViewUpdates() {
    if (typeof document === 'undefined') {
        listViewRafHandle = null;

        return;
    }

    const unresolvedUpdates = new Map();

    pendingListViewUpdates.forEach((state, clientId) => {
        const row = document.querySelector(
            `.block-editor-list-view__block[data-block="${clientId}"]`,
        );

        if (!row) {
            unresolvedUpdates.set(clientId, state);

            return;
        }

        if (state.isHidden) {
            row.classList.add('bloc-editeur-cache');
        } else {
            row.classList.remove('bloc-editeur-cache');
        }

        if (state.hasFallback) {
            row.classList.add('bloc-editeur-repli');
        } else {
            row.classList.remove('bloc-editeur-repli');
        }
    });

    pendingListViewUpdates.clear();

    if (unresolvedUpdates.size) {
        unresolvedUpdates.forEach((isHidden, clientId) => {
            pendingListViewUpdates.set(clientId, isHidden);
        });
    }

    if (pendingListViewUpdates.size && getIsListViewOpened()) {
        listViewRafHandle = window.requestAnimationFrame(() => {
            flushListViewUpdates();
        });

        return;
    }

    listViewRafHandle = null;
}

function queueListViewUpdate(clientId, state) {
    const pendingState = pendingListViewUpdates.get(clientId);

    if (
        pendingState &&
        pendingState.isHidden === state.isHidden &&
        pendingState.hasFallback === state.hasFallback
    ) {
        return;
    }

    pendingListViewUpdates.set(clientId, state);

    if (listViewRafHandle) {
        return;
    }

    listViewRafHandle = window.requestAnimationFrame(() => {
        flushListViewUpdates();
    });
}

function syncListView() {
    const blockEditor = select('core/block-editor');

    if (!blockEditor) {
        return;
    }

    const rootClientIds = blockEditor.getBlockOrder();

    if (!rootClientIds.length) {
        return;
    }

    const stack = [...rootClientIds];
    const seenSupportedBlocks = new Set();

    while (stack.length) {
        const clientId = stack.pop();
        const block = blockEditor.getBlock(clientId);

        if (!block) {
            continue;
        }

        if (isSupportedBlockName(block.name)) {
            const isHidden = Boolean(block.attributes.isHidden);
            const hasFallback = getBlockHasFallback(block.attributes);
            const previousState = blockVisibilityState.get(clientId) || {};

            if (
                previousState.isHidden !== isHidden ||
                previousState.hasFallback !== hasFallback
            ) {
                queueListViewUpdate(clientId, { isHidden, hasFallback });
            }

            blockVisibilityState.set(clientId, { isHidden, hasFallback });
            seenSupportedBlocks.add(clientId);
        }

        if (block.innerBlocks && block.innerBlocks.length) {
            block.innerBlocks.forEach((innerBlock) => {
                if (innerBlock && innerBlock.clientId) {
                    stack.push(innerBlock.clientId);
                }
            });
        }
    }

    if (seenSupportedBlocks.size !== blockVisibilityState.size) {
        Array.from(blockVisibilityState.keys()).forEach((clientId) => {
            if (!seenSupportedBlocks.has(clientId)) {
                blockVisibilityState.delete(clientId);
                pendingListViewUpdates.delete(clientId);
            }
        });
    }
}

addFilter(
    'blocks.registerBlockType',
    'visi-bloc-jlg/add-visibility-attributes',
    addVisibilityAttributesToGroup,
);
addFilter(
    'editor.BlockEdit',
    'visi-bloc-jlg/with-visibility-controls',
    withVisibilityControls,
);
addFilter(
    'blocks.getSaveContent.extraProps',
    'visi-bloc-jlg/add-save-classes',
    addSaveClasses,
);
addFilter(
    'editor.BlockListBlock',
    'visi-bloc-jlg/add-editor-status-badges',
    createHigherOrderComponent(
        (BlockListBlock) => (props) => {
            const className = typeof props.className === 'string' ? props.className : '';
            const hasHiddenBadge = className.includes('bloc-editeur-cache');
            const hasFallbackBadge = className.includes('bloc-editeur-repli');

            if (!hasHiddenBadge && !hasFallbackBadge) {
                return <BlockListBlock {...props} />;
            }

            const element = <BlockListBlock {...props} />;
            const existingChildren = Children.toArray(element.props.children);
            const badges = [];

            if (hasHiddenBadge) {
                badges.push(
                    <StatusBadge
                        key="visibloc-hidden-badge"
                        label={__('Bloc masqué', 'visi-bloc-jlg')}
                        variant="hidden"
                        screenReaderText={__(
                            'Ce bloc est masqué pour les visiteurs du site.',
                            'visi-bloc-jlg',
                        )}
                    />,
                );
            }

            if (hasFallbackBadge) {
                badges.push(
                    <StatusBadge
                        key="visibloc-fallback-badge"
                        label={__('Repli actif', 'visi-bloc-jlg')}
                        variant="fallback"
                        screenReaderText={__(
                            'Le contenu de repli est affiché à la place du bloc original.',
                            'visi-bloc-jlg',
                        )}
                    />,
                );
            }

            return cloneElement(element, element.props, [...badges, ...existingChildren]);
        },
        'withVisibilityStatusBadges',
    ),
);
addFilter(
    'editor.BlockListBlock.props',
    'visi-bloc-jlg/add-editor-canvas-classes',
    addEditorCanvasClasses,
);

let wasListViewOpened = getIsListViewOpened();

function handleEditorSubscription() {
    const isCurrentlyOpened = getIsListViewOpened();

    syncListView();

    if (isCurrentlyOpened && !wasListViewOpened) {
        replayPendingListViewUpdates();
    }

    wasListViewOpened = isCurrentlyOpened;
}

subscribe(handleEditorSubscription);
