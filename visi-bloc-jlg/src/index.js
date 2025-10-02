/* global VisiBlocData */
import { Fragment } from '@wordpress/element';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    PanelBody,
    SelectControl,
    ToggleControl,
    CheckboxControl,
    DateTimePicker,
    Notice,
    Button,
    BaseControl,
    Flex,
    FlexBlock,
    FlexItem,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { __experimentalGetSettings, dateI18n, format as formatDate } from '@wordpress/date';
import { subscribe, select } from '@wordpress/data';

import './editor-styles.css';

const DEFAULT_SUPPORTED_BLOCKS = ['core/group'];

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

const DEVICE_VISIBILITY_OPTIONS = [
    {
        label: __('Visible sur tous les appareils', 'visi-bloc-jlg'),
        value: 'all',
    },
    {
        label: __('--- Afficher uniquement sur ---', 'visi-bloc-jlg'),
        value: 'separator-show',
        disabled: true,
    },
    {
        label: __('Desktop Uniquement', 'visi-bloc-jlg'),
        value: 'desktop-only',
    },
    {
        label: __('Tablette Uniquement', 'visi-bloc-jlg'),
        value: 'tablet-only',
    },
    {
        label: __('Mobile Uniquement', 'visi-bloc-jlg'),
        value: 'mobile-only',
    },
    {
        label: __('--- Cacher sur ---', 'visi-bloc-jlg'),
        value: 'separator-hide',
        disabled: true,
    },
    {
        label: __('Caché sur Desktop', 'visi-bloc-jlg'),
        value: 'hide-on-desktop',
    },
    {
        label: __('Caché sur Tablette', 'visi-bloc-jlg'),
        value: 'hide-on-tablet',
    },
    {
        label: __('Caché sur Mobile', 'visi-bloc-jlg'),
        value: 'hide-on-mobile',
    },
];

const DEVICE_BADGE_LABELS = new Map([
    ['desktop-only', __('Desktop Uniquement', 'visi-bloc-jlg')],
    ['tablet-only', __('Tablette Uniquement', 'visi-bloc-jlg')],
    ['mobile-only', __('Mobile Uniquement', 'visi-bloc-jlg')],
    ['hide-on-desktop', __('Caché sur Desktop', 'visi-bloc-jlg')],
    ['hide-on-tablet', __('Caché sur Tablette', 'visi-bloc-jlg')],
    ['hide-on-mobile', __('Caché sur Mobile', 'visi-bloc-jlg')],
]);

const ROLE_SPECIAL_LABELS = new Map([
    ['logged-in', __('Utilisateurs connectés', 'visi-bloc-jlg')],
    ['logged-out', __('Visiteurs non connectés', 'visi-bloc-jlg')],
]);

const ROLE_LABEL_LOOKUP = (() => {
    const map = new Map();

    if (typeof VisiBlocData === 'object' && VisiBlocData !== null) {
        const { roles } = VisiBlocData;

        if (roles && typeof roles === 'object') {
            Object.entries(roles).forEach(([slug, label]) => {
                if (typeof slug === 'string' && slug) {
                    const normalizedLabel =
                        typeof label === 'string' && label.trim() ? label.trim() : slug;

                    map.set(slug, normalizedLabel);
                }
            });
        }
    }

    return map;
})();

const BADGE_PREFIXES = Object.freeze({
    schedule: '🗓️',
    roles: '👥',
    device: '📱',
    advanced: '⚙️',
});

const SUPPORTED_ADVANCED_RULE_TYPES = [
    'post_type',
    'taxonomy',
    'template',
    'recurring_schedule',
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

const createDefaultRuleForType = (type) => {
    switch (type) {
        case 'taxonomy':
            return getDefaultTaxonomyRule();
        case 'template':
            return getDefaultTemplateRule();
        case 'recurring_schedule':
            return getDefaultRecurringRule();
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
        } = attributes;

        const advancedVisibility = normalizeAdvancedVisibility(rawAdvancedVisibility);

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
                            options={[
                                { value: 'post_type', label: __('Type de contenu', 'visi-bloc-jlg') },
                                { value: 'taxonomy', label: __('Taxonomie', 'visi-bloc-jlg') },
                                { value: 'template', label: __('Modèle de page', 'visi-bloc-jlg') },
                                { value: 'recurring_schedule', label: __('Horaire récurrent', 'visi-bloc-jlg') },
                            ]}
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
                                title={__('Contrôles de Visibilité', 'visi-bloc-jlg')}
                                initialOpen={true}
                            >
                                <SelectControl
                                    label={__('Visibilité par Appareil', 'visi-bloc-jlg')}
                                    value={deviceVisibility}
                                    options={DEVICE_VISIBILITY_OPTIONS}
                                    onChange={(newValue) =>
                                        setAttributes({ deviceVisibility: newValue })
                                    }
                                />
                            </PanelBody>
                            <PanelBody
                                title={__('Programmation', 'visi-bloc-jlg')}
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
                                title={__('Visibilité par Rôle', 'visi-bloc-jlg')}
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
                                title={__('Règles de visibilité avancées', 'visi-bloc-jlg')}
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
                                <Button
                                    variant="secondary"
                                    onClick={() =>
                                        updateAdvancedVisibility((current) => ({
                                            ...current,
                                            rules: [
                                                ...current.rules,
                                                createDefaultRuleForType('post_type'),
                                            ],
                                        }))
                                    }
                                >
                                    {__('Ajouter une règle', 'visi-bloc-jlg')}
                                </Button>
                                <p className="components-help-text">
                                    {__(
                                        'Ces règles permettent d’affiner la visibilité selon le contexte du contenu, le modèle ou un horaire récurrent.',
                                        'visi-bloc-jlg',
                                    )}
                                </p>
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

    const state = buildVisibilityUiState(block.attributes);
    const baseClasses =
        typeof props.className === 'string'
            ? props.className
                  .split(/\s+/)
                  .map((className) => className.trim())
                  .filter(
                      (className) =>
                          className &&
                          !MANAGED_VISIBLOC_CLASS_PREFIXES.some((prefix) =>
                              className.startsWith(prefix),
                          ),
                  )
            : [];
    const existingClasses = new Set(baseClasses);

    state.classes.forEach((className) => {
        if (className) {
            existingClasses.add(className);
        }
    });

    const nextProps = {
        ...props,
        className: Array.from(existingClasses).join(' '),
    };

    Object.entries(state.dataset).forEach(([key, value]) => {
        nextProps[key] = value;
    });

    return nextProps;
}

function addSaveClasses(extraProps, blockType, attributes) {
    if (!isSupportedBlockName(blockType.name) || !attributes) {
        return extraProps;
    }

    const state = buildVisibilityUiState(attributes);
    const baseClasses =
        typeof extraProps.className === 'string'
            ? extraProps.className
                  .split(/\s+/)
                  .map((className) => className.trim())
                  .filter(
                      (className) =>
                          className &&
                          !MANAGED_VISIBLOC_CLASS_PREFIXES.some((prefix) =>
                              className.startsWith(prefix),
                          ),
                  )
            : [];
    const classSet = new Set(baseClasses);

    state.classes.forEach((className) => {
        if (className) {
            classSet.add(className);
        }
    });

    const legacyDeviceClass = state.deviceInfo
        ? `vb-${state.deviceInfo.sanitized}`
        : getLegacyDeviceClassName(attributes.deviceVisibility);

    if (legacyDeviceClass) {
        classSet.add(legacyDeviceClass);
    }

    return {
        ...extraProps,
        className: Array.from(classSet).join(' '),
    };
}

const sanitizeClassFragment = (value) => {
    if (typeof value !== 'string') {
        return '';
    }

    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9-]+/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+|-+$/g, '');
};

const getDeviceBadgeInfo = (deviceVisibility) => {
    if (typeof deviceVisibility !== 'string') {
        return null;
    }

    const trimmed = deviceVisibility.trim();

    if (!trimmed || trimmed === 'all' || trimmed.startsWith('separator')) {
        return null;
    }

    const sanitized = sanitizeClassFragment(trimmed);

    if (!sanitized) {
        return null;
    }

    const label = DEVICE_BADGE_LABELS.get(trimmed) || trimmed;

    return {
        className: `vb-device-${sanitized}`,
        sanitized,
        label,
    };
};

const getLegacyDeviceClassName = (deviceVisibility) => {
    const badgeInfo = getDeviceBadgeInfo(deviceVisibility);

    if (!badgeInfo) {
        return '';
    }

    return `vb-${badgeInfo.sanitized}`;
};

const getRoleLabelFromSlug = (roleSlug) => {
    if (typeof roleSlug !== 'string' || !roleSlug) {
        return '';
    }

    if (ROLE_SPECIAL_LABELS.has(roleSlug)) {
        return ROLE_SPECIAL_LABELS.get(roleSlug);
    }

    if (ROLE_LABEL_LOOKUP.has(roleSlug)) {
        return ROLE_LABEL_LOOKUP.get(roleSlug);
    }

    return roleSlug;
};

const getRoleBadgeLabel = (visibilityRoles) => {
    if (!Array.isArray(visibilityRoles) || !visibilityRoles.length) {
        return null;
    }

    const uniqueRoles = Array.from(
        new Set(
            visibilityRoles
                .map((role) => (typeof role === 'string' ? role.trim() : ''))
                .filter(Boolean),
        ),
    );

    if (!uniqueRoles.length) {
        return null;
    }

    const roleLabels = uniqueRoles
        .map(getRoleLabelFromSlug)
        .map((label) => (typeof label === 'string' ? label.trim() : ''))
        .filter(Boolean);

    if (!roleLabels.length) {
        return __('Visibilité par rôle activée', 'visi-bloc-jlg');
    }

    return sprintf(__('Rôles : %s', 'visi-bloc-jlg'), roleLabels.join(', '));
};

const getScheduleBadgeLabel = (attributes) => {
    if (!attributes || typeof attributes !== 'object') {
        return null;
    }

    const { isSchedulingEnabled, publishStartDate, publishEndDate } = attributes;

    if (!isSchedulingEnabled) {
        return null;
    }

    const startDateLabel = formatScheduleDate(publishStartDate);
    const endDateLabel = formatScheduleDate(publishEndDate);
    const startDateObj = parseDateValue(publishStartDate);
    const endDateObj = parseDateValue(publishEndDate);
    const hasScheduleRangeError =
        !!startDateObj && !!endDateObj && endDateObj.getTime() < startDateObj.getTime();

    if (hasScheduleRangeError) {
        return __('Programmation invalide', 'visi-bloc-jlg');
    }

    if (startDateLabel && endDateLabel) {
        return sprintf(
            __('Programmation : %1$s → %2$s', 'visi-bloc-jlg'),
            startDateLabel,
            endDateLabel,
        );
    }

    if (startDateLabel) {
        return sprintf(__('Programmation : à partir du %s', 'visi-bloc-jlg'), startDateLabel);
    }

    if (endDateLabel) {
        return sprintf(__('Programmation : jusqu\'au %s', 'visi-bloc-jlg'), endDateLabel);
    }

    return __('Programmation activée', 'visi-bloc-jlg');
};

const getAdvancedBadgeLabel = (rawAdvancedVisibility) => {
    const advancedVisibility = normalizeAdvancedVisibility(rawAdvancedVisibility);

    if (!advancedVisibility || !Array.isArray(advancedVisibility.rules)) {
        return null;
    }

    if (!advancedVisibility.rules.length) {
        return null;
    }

    return __('Règles avancées actives', 'visi-bloc-jlg');
};

const buildVisibilityUiState = (attributes = {}) => {
    const classes = new Set();
    const badges = [];
    const dataset = {};

    if (attributes && attributes.isHidden) {
        classes.add('bloc-editeur-cache');
        dataset['data-visibloc-label'] = __('Bloc masqué', 'visi-bloc-jlg');
    } else {
        dataset['data-visibloc-label'] = undefined;
    }

    const deviceInfo = getDeviceBadgeInfo(attributes.deviceVisibility);

    if (deviceInfo) {
        classes.add(deviceInfo.className);
        badges.push(
            `${BADGE_PREFIXES.device} ${sprintf(
                __('Appareil : %s', 'visi-bloc-jlg'),
                deviceInfo.label,
            )}`,
        );
    }

    const roleBadge = getRoleBadgeLabel(attributes.visibilityRoles);

    if (roleBadge) {
        classes.add('bloc-editeur-role');
        badges.push(`${BADGE_PREFIXES.roles} ${roleBadge}`);
    }

    const scheduleBadge = getScheduleBadgeLabel(attributes);

    if (scheduleBadge) {
        classes.add('bloc-editeur-programme');
        badges.push(`${BADGE_PREFIXES.schedule} ${scheduleBadge}`);
    }

    const advancedBadge = getAdvancedBadgeLabel(attributes.advancedVisibility);

    if (advancedBadge) {
        classes.add('bloc-editeur-conditions');
        badges.push(`${BADGE_PREFIXES.advanced} ${advancedBadge}`);
    }

    const classList = Array.from(classes).filter(Boolean).sort();
    const badgesValue = badges.join('\n');

    dataset['data-visibloc-badges'] = badgesValue || undefined;

    return {
        classes: classList,
        badges: badgesValue,
        dataset,
        deviceInfo,
    };
};

const areUiStatesEqual = (first, second) => {
    if (first === second) {
        return true;
    }

    if (!first || !second) {
        return false;
    }

    if (first.badges !== second.badges) {
        return false;
    }

    if (first.classes.length !== second.classes.length) {
        return false;
    }

    for (let index = 0; index < first.classes.length; index += 1) {
        if (first.classes[index] !== second.classes[index]) {
            return false;
        }
    }

    return true;
};

const blockVisibilityState = new Map();
const pendingListViewUpdates = new Map();
let listViewRafHandle = null;

const MANAGED_VISIBLOC_CLASS_PREFIXES = ['bloc-editeur-', 'vb-device-'];

const applyListViewState = (row, state) => {
    if (!row || !state) {
        return;
    }

    const classSet = new Set(Array.isArray(state.classes) ? state.classes : []);

    Array.from(row.classList).forEach((className) => {
        if (
            MANAGED_VISIBLOC_CLASS_PREFIXES.some((prefix) => className.startsWith(prefix)) &&
            !classSet.has(className)
        ) {
            row.classList.remove(className);
        }
    });

    state.classes.forEach((className) => {
        if (className) {
            row.classList.add(className);
        }
    });

    if (state.badges) {
        row.setAttribute('data-visibloc-badges', state.badges);
    } else {
        row.removeAttribute('data-visibloc-badges');
    }
};

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

        applyListViewState(row, state);
    });

    pendingListViewUpdates.clear();

    if (unresolvedUpdates.size) {
        unresolvedUpdates.forEach((state, clientId) => {
            pendingListViewUpdates.set(clientId, state);
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
    if (!state) {
        pendingListViewUpdates.delete(clientId);

        return;
    }

    if (areUiStatesEqual(pendingListViewUpdates.get(clientId), state)) {
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
            const state = buildVisibilityUiState(block.attributes || {});
            const previousState = blockVisibilityState.get(clientId);

            if (!areUiStatesEqual(previousState, state)) {
                queueListViewUpdate(clientId, state);
            }

            blockVisibilityState.set(clientId, state);
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
