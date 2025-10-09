/* global VisiBlocData */
import {
    Fragment,
    cloneElement,
    Children,
    useMemo,
    useState,
    useEffect,
    useRef,
    useCallback,
    RawHTML,
} from '@wordpress/element';
import { addFilter, applyFilters } from '@wordpress/hooks';
import { createHigherOrderComponent, useInstanceId } from '@wordpress/compose';
import { BlockControls, InspectorControls } from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    PanelBody,
    SelectControl,
    ComboboxControl,
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
    Tooltip,
    TabPanel,
    Modal,
    Card,
    CardHeader,
    CardBody,
    CardFooter,
    Spinner,
} from '@wordpress/components';
import { __, sprintf, _n } from '@wordpress/i18n';
import { __experimentalGetSettings, dateI18n, format as formatDate } from '@wordpress/date';
import apiFetch from '@wordpress/api-fetch';
import { subscribe, select, useSelect } from '@wordpress/data';
import autop from '@wordpress/autop';
import debounce from 'lodash/debounce';

import './editor-styles.css';

const DEFAULT_SUPPORTED_BLOCKS = ['core/group'];

const DeviceOrientationPortraitIcon = () => (
    <svg
        width="24"
        height="24"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <rect
            x="7"
            y="3"
            width="10"
            height="18"
            rx="2"
            ry="2"
            fill="currentColor"
            opacity="0.2"
        />
        <rect
            x="9"
            y="5"
            width="6"
            height="14"
            rx="1.2"
            ry="1.2"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
        />
        <circle cx="12" cy="17.5" r="0.9" fill="currentColor" />
    </svg>
);

const DeviceOrientationLandscapeIcon = () => (
    <svg
        width="24"
        height="24"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <rect
            x="3"
            y="7"
            width="18"
            height="10"
            rx="2"
            ry="2"
            fill="currentColor"
            opacity="0.2"
        />
        <rect
            x="5"
            y="9"
            width="14"
            height="6"
            rx="1.2"
            ry="1.2"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
        />
        <circle cx="17.5" cy="12" r="0.9" fill="currentColor" />
    </svg>
);

const HiddenBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <path
            d="M2 12c2.8-5.3 6.5-8 10-8s7.2 2.7 10 8c-2.8 5.3-6.5 8-10 8s-7.2-2.7-10-8z"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinejoin="round"
        />
        <circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.2" />
        <path
            d="M12 9a3 3 0 0 1 3 3m6.5 6.5-17-17"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
        />
    </svg>
);

const ClockBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <circle
            cx="12"
            cy="12"
            r="7.5"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
        />
        <path
            d="M12 8.5v4l2.5 2"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    </svg>
);

const ClockAlertBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <circle
            cx="11"
            cy="11"
            r="7"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
        />
        <path
            d="M11 7.5v3.7l2.1 1.6"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
        <path
            d="M17.5 13.5v3.5"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
        />
        <circle cx="17.5" cy="18.5" r="0.8" fill="currentColor" />
    </svg>
);

const LayersBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <path
            d="M5.5 9.5 12 6l6.5 3.5L12 13z"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinejoin="round"
        />
        <path
            d="M5.5 14.5 12 18l6.5-3.5"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinejoin="round"
        />
    </svg>
);

const SlidersBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <path
            d="M7 5v14M17 5v14"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
        />
        <circle
            cx="7"
            cy="10"
            r="2.5"
            fill="currentColor"
            opacity="0.2"
            stroke="currentColor"
            strokeWidth="1.2"
        />
        <circle
            cx="17"
            cy="14"
            r="2.5"
            fill="currentColor"
            opacity="0.2"
            stroke="currentColor"
            strokeWidth="1.2"
        />
    </svg>
);

const InfoBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <circle
            cx="12"
            cy="12"
            r="8"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
        />
        <circle cx="12" cy="8.5" r="1" fill="currentColor" />
        <path
            d="M11 11.5h2v6"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
        />
    </svg>
);

const WarningBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <path
            d="m12 4 8 14H4z"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinejoin="round"
        />
        <path
            d="M12 10v4"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
        <circle cx="12" cy="17" r="1" fill="currentColor" />
    </svg>
);

const SuccessBadgeIcon = () => (
    <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        role="presentation"
        focusable="false"
        aria-hidden="true"
    >
        <circle
            cx="12"
            cy="12"
            r="7.5"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
        />
        <path
            d="m8.8 12.4 2.3 2.3 4-4.7"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    </svg>
);

const STATUS_BADGE_ICONS = {
    hidden: HiddenBadgeIcon,
    schedule: ClockBadgeIcon,
    'schedule-error': ClockAlertBadgeIcon,
    fallback: LayersBadgeIcon,
    advanced: SlidersBadgeIcon,
    warning: WarningBadgeIcon,
    success: SuccessBadgeIcon,
    default: InfoBadgeIcon,
};

const StatusBadge = ({ label, variant = '', screenReaderText = '', description = '' }) => {
    const classNames = ['visibloc-status-badge'];
    const hasDescription = typeof description === 'string' && description.trim().length > 0;
    const normalizedVariant = typeof variant === 'string' ? variant.trim() : '';

    if (normalizedVariant) {
        classNames.push(`visibloc-status-badge--${normalizedVariant}`);
    }

    const IconComponent = STATUS_BADGE_ICONS[normalizedVariant] || STATUS_BADGE_ICONS.default;

    const content = (
        <span className={classNames.join(' ')}>
            {IconComponent ? (
                <span className="visibloc-status-badge__icon" aria-hidden="true">
                    <IconComponent />
                </span>
            ) : null}
            <span className="visibloc-status-badge__label">{label}</span>
            {hasDescription ? (
                <span className="visibloc-status-badge__description">{description}</span>
            ) : null}
            {screenReaderText ? (
                <span className="screen-reader-text">{screenReaderText}</span>
            ) : null}
        </span>
    );

    if (!hasDescription) {
        return content;
    }

    return (
        <Tooltip text={description}>
            {content}
        </Tooltip>
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
    {
        id: 'orientation-show',
        label: __('Orientation ciblée', 'visi-bloc-jlg'),
        options: [
            {
                id: 'portrait-only',
                label: __('Portrait', 'visi-bloc-jlg'),
                icon: DeviceOrientationPortraitIcon,
            },
            {
                id: 'landscape-only',
                label: __('Paysage', 'visi-bloc-jlg'),
                icon: DeviceOrientationLandscapeIcon,
            },
        ],
    },
    {
        id: 'orientation-hide',
        label: __('Masquer en orientation', 'visi-bloc-jlg'),
        options: [
            {
                id: 'hide-on-portrait',
                label: __('Portrait', 'visi-bloc-jlg'),
                icon: DeviceOrientationPortraitIcon,
            },
            {
                id: 'hide-on-landscape',
                label: __('Paysage', 'visi-bloc-jlg'),
                icon: DeviceOrientationLandscapeIcon,
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
    'user_segment',
    'woocommerce_cart',
    'query_param',
    'cookie',
    'visit_count',
];

const ADVANCED_RULE_TYPE_OPTIONS = [
    { value: 'post_type', label: __('Type de contenu', 'visi-bloc-jlg') },
    { value: 'taxonomy', label: __('Taxonomie', 'visi-bloc-jlg') },
    { value: 'template', label: __('Modèle de page', 'visi-bloc-jlg') },
    { value: 'recurring_schedule', label: __('Horaire récurrent', 'visi-bloc-jlg') },
    { value: 'logged_in_status', label: __('État de connexion', 'visi-bloc-jlg') },
    { value: 'user_role_group', label: __('Groupe de rôles', 'visi-bloc-jlg') },
    { value: 'user_segment', label: __('Segment marketing', 'visi-bloc-jlg') },
    { value: 'woocommerce_cart', label: __('Panier WooCommerce', 'visi-bloc-jlg') },
    { value: 'query_param', label: __('Paramètre d’URL', 'visi-bloc-jlg') },
    { value: 'cookie', label: __('Cookie', 'visi-bloc-jlg') },
    { value: 'visit_count', label: __('Compteur de visites', 'visi-bloc-jlg') },
];

const DEFAULT_ADVANCED_VISIBILITY = Object.freeze({
    logic: 'AND',
    rules: [],
    savedGroups: [],
});

const DEFAULT_RECURRING_SCHEDULE = Object.freeze({
    frequency: 'daily',
    days: [],
    startTime: '08:00',
    endTime: '17:00',
});

const SITE_TIMEZONE_VALUE = 'site';

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

const FALLBACK_PREVIEW_UNSAFE_SELECTORS =
    'script, iframe, frame, frameset, object, embed, link, meta, style, noscript, form, input, button, textarea, select';

const sanitizeFallbackPreviewHtml = (html) => {
    if (typeof html !== 'string') {
        return '';
    }

    const trimmed = html.trim();

    if (!trimmed) {
        return '';
    }

    if (typeof document === 'undefined') {
        return trimmed;
    }

    const template = document.createElement('template');
    template.innerHTML = trimmed;

    template.content.querySelectorAll(FALLBACK_PREVIEW_UNSAFE_SELECTORS).forEach((node) => {
        node.remove();
    });

    template.content.querySelectorAll('*').forEach((element) => {
        Array.from(element.attributes).forEach((attribute) => {
            const attributeName = attribute.name.toLowerCase();

            if (attributeName.startsWith('on')) {
                element.removeAttribute(attribute.name);

                return;
            }

            if (['src', 'href', 'xlink:href'].includes(attributeName)) {
                const value = attribute.value.trim().toLowerCase();

                if (value.startsWith('javascript:') || value.startsWith('data:')) {
                    element.removeAttribute(attribute.name);
                }
            }
        });
    });

    return template.innerHTML.trim();
};

const getTextFallbackPreviewHtml = (text) => {
    if (typeof text !== 'string') {
        return '';
    }

    const trimmed = text.trim();

    if (!trimmed) {
        return '';
    }

    let html = trimmed;

    if (typeof autop === 'function') {
        html = autop(trimmed);
    }

    return sanitizeFallbackPreviewHtml(html);
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

const getVisibilityAttributeSignature = (attrs) => {
    if (!attrs || typeof attrs !== 'object') {
        return '';
    }

    const isHidden = attrs.isHidden ? '1' : '0';
    const fallbackEnabled = typeof attrs.fallbackEnabled === 'undefined' ? true : Boolean(attrs.fallbackEnabled);
    const fallbackBehavior = typeof attrs.fallbackBehavior === 'string' ? attrs.fallbackBehavior : 'inherit';
    const fallbackBlockId = attrs.fallbackBlockId ? String(attrs.fallbackBlockId) : '';
    const fallbackCustomText = typeof attrs.fallbackCustomText === 'string' ? attrs.fallbackCustomText.trim() : '';

    return [isHidden, fallbackEnabled ? '1' : '0', fallbackBehavior, fallbackBlockId, fallbackCustomText].join('|');
};

const blockVisibilityState = new Map();
const pendingListViewUpdates = new Map();
const listViewRowCache = new Map();
const registeredSupportedClientIds = new Set();
const blockAttributeHashes = new Map();
const dirtyClientIds = new Set();
let lastClientIdsSignature = '';
let listViewRafHandle = null;
let listViewDensityObserver = null;
let observedListViewElement = null;
let compactBadgeModeEnabled = false;

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
    savedGroups: [...DEFAULT_ADVANCED_VISIBILITY.savedGroups],
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
        savedGroups: [],
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

const getDefaultUserSegmentRule = () => {
    const segments = getVisiBlocArray('userSegments');

    return {
        id: createRuleId(),
        type: 'user_segment',
        operator: 'matches',
        segment: getFirstOptionValue(segments),
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

const getDefaultCookieRule = () => {
    const cookies = getVisiBlocArray('commonCookies');

    return {
        id: createRuleId(),
        type: 'cookie',
        operator: 'equals',
        name: getFirstOptionValue(cookies),
        value: '',
    };
};

const getDefaultVisitCountRule = () => ({
    id: createRuleId(),
    type: 'visit_count',
    operator: 'at_least',
    threshold: 3,
});

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
        case 'user_segment':
            return getDefaultUserSegmentRule();
        case 'woocommerce_cart':
            return getDefaultWooCommerceCartRule();
        case 'query_param':
            return getDefaultQueryParamRule();
        case 'cookie':
            return getDefaultCookieRule();
        case 'visit_count':
            return getDefaultVisitCountRule();
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
        normalized.savedGroups = Array.isArray(rule.savedGroups)
            ? rule.savedGroups
                  .map((group) => {
                      if (!group || typeof group !== 'object') {
                          return null;
                      }

                      const terms = Array.isArray(group.terms)
                          ? group.terms
                                .map((term) =>
                                    typeof term === 'string' || typeof term === 'number' ? String(term) : '',
                                )
                                .filter(Boolean)
                          : [];

                      if (!terms.length) {
                          return null;
                      }

                      const rawId = typeof group.id === 'string' ? group.id.trim() : '';
                      const id = rawId || `group-${terms.join('-')}`;
                      const label = typeof group.label === 'string' ? group.label.trim() : '';

                      return {
                          id,
                          label: label || __('Sélection enregistrée', 'visi-bloc-jlg'),
                          terms,
                      };
                  })
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

    if (type === 'user_segment') {
        normalized.operator = rule.operator === 'does_not_match' ? 'does_not_match' : 'matches';
        normalized.segment = typeof rule.segment === 'string' ? rule.segment : '';

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

    if (type === 'cookie') {
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
        normalized.name = typeof rule.name === 'string' ? rule.name : '';
        normalized.value = typeof rule.value === 'string' ? rule.value : '';

        return normalized;
    }

    if (type === 'visit_count') {
        const allowedOperators = ['at_least', 'at_most', 'equals', 'not_equals'];
        const parsedThreshold = Number.parseInt(rule.threshold, 10);
        const threshold = Number.isFinite(parsedThreshold) && parsedThreshold >= 0 ? parsedThreshold : 0;

        normalized.operator = allowedOperators.includes(rule.operator) ? rule.operator : 'at_least';
        normalized.threshold = threshold;

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

    const savedGroups = Array.isArray(value.savedGroups)
        ? value.savedGroups.filter((group) => group && typeof group === 'object')
        : [];

    return {
        logic,
        rules,
        savedGroups,
    };
};

const getSiteIsoDateWithOffset = (days = 0) => {
    const offset = Number.isFinite(days) ? Number(days) : 0;
    const base = new Date();
    base.setDate(base.getDate() + offset);

    return formatDate('Y-m-d\\TH:i:s', base);
};

const pickPreferredValues = (preferred, available) => {
    const availableSet = Array.isArray(available) ? available.filter(Boolean) : [];

    if (!availableSet.length) {
        return [];
    }

    const preferredArray = Array.isArray(preferred) ? preferred : [];
    const selected = preferredArray
        .map((value) => (typeof value === 'string' ? value : ''))
        .filter((value) => value && availableSet.includes(value));

    if (selected.length) {
        return Array.from(new Set(selected));
    }

    return [availableSet[0]];
};

const ensureNumericId = (value) => {
    if (typeof value === 'number') {
        return value;
    }

    if (typeof value === 'string' && value) {
        const parsed = parseInt(value, 10);

        if (!Number.isNaN(parsed)) {
            return parsed;
        }
    }

    return undefined;
};

const buildRestPath = (namespace, base) => {
    const normalizedNamespace = (typeof namespace === 'string' ? namespace : 'wp/v2')
        .replace(/^\/+/, '')
        .replace(/\/+$/, '');
    const normalizedBase = (typeof base === 'string' ? base : '')
        .replace(/^\/+/, '')
        .replace(/\/+$/, '');

    if (!normalizedBase) {
        return `/${normalizedNamespace}`;
    }

    return `/${normalizedNamespace}/${normalizedBase}`;
};

const resolveRecipeRule = (rule, context) => {
    if (!rule || typeof rule !== 'object') {
        return null;
    }

    const baseRule = { ...rule };

    if (baseRule.type === 'taxonomy') {
        const availableTaxonomies = Array.isArray(context.taxonomies)
            ? context.taxonomies
                  .map((item) => (item && typeof item.slug === 'string' ? item.slug : ''))
                  .filter(Boolean)
            : [];

        baseRule.taxonomy = typeof baseRule.taxonomy === 'string' && baseRule.taxonomy
            ? baseRule.taxonomy
            : availableTaxonomies[0] || '';
    }

    if (baseRule.type === 'user_segment') {
        const availableSegments = Array.isArray(context.userSegments)
            ? context.userSegments
                  .map((segment) => (segment && typeof segment.value === 'string' ? segment.value : ''))
                  .filter(Boolean)
            : [];
        const preferred = baseRule.segment ? [baseRule.segment] : [];
        const [selectedSegment] = pickPreferredValues(preferred, availableSegments);
        baseRule.segment = selectedSegment || '';
    }

    if (baseRule.type === 'woocommerce_cart') {
        const sources = Array.isArray(context.woocommerceTaxonomies)
            ? context.woocommerceTaxonomies
                  .map((taxonomy) => (taxonomy && typeof taxonomy.slug === 'string' ? taxonomy.slug : ''))
                  .filter(Boolean)
            : [];
        baseRule.taxonomy = typeof baseRule.taxonomy === 'string' && baseRule.taxonomy
            ? baseRule.taxonomy
            : sources[0] || '';
    }

    if (!baseRule.id) {
        baseRule.id = createRuleId();
    }

    return normalizeRule(baseRule);
};

const buildRecipeAttributes = (recipe, context = {}) => {
    if (!recipe || typeof recipe !== 'object') {
        return null;
    }

    const { attributes = {}, id } = recipe;
    const updates = {};

    if (typeof id === 'string') {
        updates.scenarioPreset = id;
    }

    if (typeof attributes.deviceVisibility === 'string') {
        updates.deviceVisibility = attributes.deviceVisibility;
    }

    if (typeof attributes.isSchedulingEnabled === 'boolean') {
        updates.isSchedulingEnabled = attributes.isSchedulingEnabled;

        if (attributes.isSchedulingEnabled) {
            updates.publishStartDate = getCurrentSiteIsoDate();
            const duration = Number.isFinite(attributes.scheduleWindowDays)
                ? Number(attributes.scheduleWindowDays)
                : null;
            updates.publishEndDate = Number.isFinite(duration)
                ? getSiteIsoDateWithOffset(duration)
                : '';
        } else {
            updates.publishStartDate = '';
            updates.publishEndDate = '';
        }
    }

    if (attributes.roles) {
        const availableRoles = Array.isArray(context.availableRoles)
            ? context.availableRoles
            : [];
        updates.visibilityRoles = pickPreferredValues(attributes.roles, availableRoles);
    }

    if (attributes.fallback) {
        updates.fallbackEnabled = true;
        const behavior = attributes.fallback.behavior;

        if (behavior === 'text') {
            updates.fallbackBehavior = 'text';
            updates.fallbackCustomText = attributes.fallback.message || '';
        } else if (behavior === 'block') {
            updates.fallbackBehavior = 'block';
            const blocks = Array.isArray(context.fallbackBlocks) ? context.fallbackBlocks : [];
            const preferredBlockId = ensureNumericId(attributes.fallback.blockId);
            const fallbackBlock = preferredBlockId
                ? preferredBlockId
                : (() => {
                      const first = blocks.find((block) => typeof block.value !== 'undefined');
                      if (!first) {
                          return undefined;
                      }

                      return ensureNumericId(first.value);
                  })();

            if (typeof fallbackBlock === 'number') {
                updates.fallbackBlockId = fallbackBlock;
            }
        } else {
            updates.fallbackBehavior = 'inherit';
        }
    }

    if (Array.isArray(attributes.advancedRules) && attributes.advancedRules.length > 0) {
        const rules = attributes.advancedRules
            .map((rule) => resolveRecipeRule(rule, context))
            .filter(Boolean);

        if (rules.length > 0) {
            updates.advancedVisibility = normalizeAdvancedVisibility({
                logic: attributes.advancedLogic === 'OR' ? 'OR' : 'AND',
                rules,
            });
        }
    }

    return updates;
};

const buildScenarioPresets = (recipes, context) => {
    if (!Array.isArray(recipes)) {
        return [];
    }

    return recipes
        .map((recipe) => {
            if (!recipe || typeof recipe !== 'object') {
                return null;
            }

            const attributes = buildRecipeAttributes(recipe, context);

            if (!attributes) {
                return null;
            }

            return {
                id: recipe.id,
                label: recipe.title || recipe.id,
                description: recipe.description || '',
                severity: recipe.severity || 'medium',
                attributes,
            };
        })
        .filter(Boolean);
};

const TaxonomyRuleEditor = ({
    rule,
    taxonomies,
    onUpdateRule,
    commonHeader,
}) => {
    const taxonomyItems = useMemo(() => Array.isArray(taxonomies) ? taxonomies : [], [taxonomies]);
    const currentTaxonomy = useMemo(
        () => taxonomyItems.find((item) => item && item.slug === rule.taxonomy) || null,
        [taxonomyItems, rule.taxonomy],
    );
    const initialTerms = useMemo(() => {
        if (!currentTaxonomy || !Array.isArray(currentTaxonomy.terms)) {
            return [];
        }

        return currentTaxonomy.terms
            .map((term) =>
                term && typeof term === 'object'
                    ? {
                          value: typeof term.value === 'string' ? term.value : String(term.value || ''),
                          label: typeof term.label === 'string' ? term.label : String(term.value || ''),
                      }
                    : null,
            )
            .filter((term) => term && term.value);
    }, [currentTaxonomy]);

    const [searchTerm, setSearchTerm] = useState('');
    const [termOptions, setTermOptions] = useState(initialTerms);
    const [isSearching, setSearching] = useState(false);
    const [searchError, setSearchError] = useState('');
    const activeRequestRef = useRef(null);
    const termLabelMapRef = useRef(new Map());

    useEffect(() => {
        setTermOptions(initialTerms);
        setSearchTerm('');
        setSearchError('');
    }, [initialTerms, rule.taxonomy]);

    useEffect(() => {
        const map = termLabelMapRef.current;
        initialTerms.forEach((term) => {
            map.set(term.value, term.label);
        });
    }, [initialTerms]);

    useEffect(() => {
        const map = termLabelMapRef.current;
        termOptions.forEach((term) => {
            map.set(term.value, term.label);
        });
    }, [termOptions]);

    useEffect(() => {
        if (!searchTerm) {
            if (activeRequestRef.current) {
                activeRequestRef.current.abort();
                activeRequestRef.current = null;
            }

            setSearching(false);
            setSearchError('');
            setTermOptions(initialTerms);

            return;
        }

        const namespace = currentTaxonomy ? currentTaxonomy.rest_namespace : 'wp/v2';
        const base = currentTaxonomy ? currentTaxonomy.rest_base : rule.taxonomy;
        const path = buildRestPath(namespace, base);
        const controller = new AbortController();
        activeRequestRef.current = controller;
        setSearching(true);
        setSearchError('');

        apiFetch({
            path: `${path}?per_page=20&search=${encodeURIComponent(searchTerm)}`,
            signal: controller.signal,
        })
            .then((results) => {
                if (controller.signal.aborted) {
                    return;
                }

                const nextOptions = Array.isArray(results)
                    ? results
                          .map((item) => {
                              if (!item || typeof item !== 'object') {
                                  return null;
                              }

                              const value = typeof item.slug === 'string' && item.slug
                                  ? item.slug
                                  : typeof item.id !== 'undefined'
                                      ? String(item.id)
                                      : '';

                              const label = typeof item.name === 'string' && item.name
                                  ? item.name
                                  : value;

                              if (!value) {
                                  return null;
                              }

                              return { value, label };
                          })
                          .filter(Boolean)
                    : [];

                setTermOptions(nextOptions.length ? nextOptions : initialTerms);
            })
            .catch((error) => {
                if (controller.signal.aborted) {
                    return;
                }

                if (error && error.name === 'AbortError') {
                    return;
                }

                setSearchError(__('Impossible de charger les termes. Réessayez plus tard.', 'visi-bloc-jlg'));
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setSearching(false);
                }
            });

        return () => {
            controller.abort();
        };
    }, [searchTerm, currentTaxonomy, initialTerms, rule.taxonomy]);

    const taxonomyOptions = useMemo(
        () =>
            taxonomyItems.map((item) => ({
                value: item.slug,
                label: item.label,
            })),
        [taxonomyItems],
    );

    const getTermLabel = useCallback((value) => {
        const map = termLabelMapRef.current;

        if (map.has(value)) {
            return map.get(value);
        }

        return value;
    }, []);

    const onSelectTerm = useCallback(
        (value) => {
            if (!value) {
                return;
            }

            const stringValue = String(value);

            if (rule.terms.includes(stringValue)) {
                return;
            }

            onUpdateRule({ terms: [...rule.terms, stringValue] });
        },
        [onUpdateRule, rule.terms],
    );

    const onRemoveTerm = useCallback(
        (value) => {
            onUpdateRule({ terms: rule.terms.filter((term) => term !== value) });
        },
        [onUpdateRule, rule.terms],
    );

    const onSaveGroup = useCallback(
        (name) => {
            const sanitizedName = typeof name === 'string' ? name.trim() : '';

            if (!rule.terms.length) {
                return;
            }

            const nextGroup = {
                id: `group-${createRuleId()}`,
                label: sanitizedName || sprintf(__('Groupe %d', 'visi-bloc-jlg'), (rule.savedGroups || []).length + 1),
                terms: [...rule.terms],
            };

            const groups = Array.isArray(rule.savedGroups) ? rule.savedGroups : [];

            onUpdateRule({ savedGroups: [...groups, nextGroup] });
        },
        [onUpdateRule, rule.savedGroups, rule.terms],
    );

    const onApplyGroup = useCallback(
        (group) => {
            if (!group || !Array.isArray(group.terms)) {
                return;
            }

            onUpdateRule({ terms: group.terms.filter(Boolean) });
        },
        [onUpdateRule],
    );

    const onDeleteGroup = useCallback(
        (groupId) => {
            const groups = Array.isArray(rule.savedGroups) ? rule.savedGroups : [];
            onUpdateRule({ savedGroups: groups.filter((group) => group && group.id !== groupId) });
        },
        [onUpdateRule, rule.savedGroups],
    );

    const [isSavingGroup, setSavingGroup] = useState(false);
    const [groupName, setGroupName] = useState('');

    useEffect(() => {
        if (!isSavingGroup) {
            setGroupName('');
        }
    }, [isSavingGroup]);

    const selectedCountLabel = useMemo(() => {
        const count = rule.terms.length;

        if (count === 0) {
            return __('Aucun terme sélectionné.', 'visi-bloc-jlg');
        }

        return _n('%d terme sélectionné.', '%d termes sélectionnés.', count, 'visi-bloc-jlg');
    }, [rule.terms.length]);

    return (
        <div className="visibloc-advanced-rule">
            {commonHeader}
            <SelectControl
                label={__('Taxonomie', 'visi-bloc-jlg')}
                value={rule.taxonomy}
                options={taxonomyOptions}
                onChange={(newTaxonomy) =>
                    onUpdateRule({ taxonomy: newTaxonomy, terms: [], savedGroups: [] })
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
            <ComboboxControl
                __nextHasNoMarginBottom
                label={__('Ajouter un terme', 'visi-bloc-jlg')}
                value=""
                options={termOptions}
                onChange={(optionValue) => {
                    onSelectTerm(optionValue);
                }}
                onFilterValueChange={(value) => setSearchTerm(value)}
                allowReset={false}
                placeholder={__('Rechercher…', 'visi-bloc-jlg')}
            />
            {isSearching ? (
                <div className="visibloc-term-search-status">
                    <Spinner />
                    <span>{__('Recherche des termes…', 'visi-bloc-jlg')}</span>
                </div>
            ) : null}
            {searchError ? (
                <Notice status="error" isDismissible={false}>
                    {searchError}
                </Notice>
            ) : null}
            <div className="visibloc-term-selection">
                <p className="visibloc-term-selection__count">
                    {sprintf(selectedCountLabel, rule.terms.length)}
                </p>
                <div className="visibloc-term-chips" role="list">
                    {rule.terms.map((term) => (
                        <span key={term} role="listitem" className="visibloc-term-chip">
                            <span className="visibloc-term-chip__label">{getTermLabel(term)}</span>
                            <Button
                                onClick={() => onRemoveTerm(term)}
                                icon="no-alt"
                                label={__('Retirer ce terme', 'visi-bloc-jlg')}
                                variant="tertiary"
                                size="small"
                            />
                        </span>
                    ))}
                    {rule.terms.length === 0 && (
                        <span className="visibloc-term-chip visibloc-term-chip--placeholder">
                            {__('Aucun terme encore ajouté.', 'visi-bloc-jlg')}
                        </span>
                    )}
                </div>
            </div>
            <div className="visibloc-term-groups">
                {rule.terms.length > 0 ? (
                    <div className="visibloc-term-groups__actions">
                        {isSavingGroup ? (
                            <Flex align="center" gap={8} justify="flex-start">
                                <FlexBlock>
                                    <TextControl
                                        label={__('Nom du groupe', 'visi-bloc-jlg')}
                                        value={groupName}
                                        onChange={setGroupName}
                                        placeholder={__('Audience prioritaire', 'visi-bloc-jlg')}
                                    />
                                </FlexBlock>
                                <FlexItem>
                                    <Button
                                        variant="primary"
                                        onClick={() => {
                                            onSaveGroup(groupName);
                                            setSavingGroup(false);
                                        }}
                                    >
                                        {__('Enregistrer', 'visi-bloc-jlg')}
                                    </Button>
                                </FlexItem>
                                <FlexItem>
                                    <Button variant="tertiary" onClick={() => setSavingGroup(false)}>
                                        {__('Annuler', 'visi-bloc-jlg')}
                                    </Button>
                                </FlexItem>
                            </Flex>
                        ) : (
                            <Button variant="tertiary" onClick={() => setSavingGroup(true)}>
                                {__('Enregistrer cette sélection', 'visi-bloc-jlg')}
                            </Button>
                        )}
                    </div>
                ) : null}
                {Array.isArray(rule.savedGroups) && rule.savedGroups.length > 0 ? (
                    <div className="visibloc-term-groups__list">
                        <p className="visibloc-term-groups__title">
                            {__('Groupes enregistrés', 'visi-bloc-jlg')}
                        </p>
                        {rule.savedGroups.map((group) => (
                            <div key={group.id} className="visibloc-term-group">
                                <Button
                                    variant="secondary"
                                    onClick={() => onApplyGroup(group)}
                                    className="visibloc-term-group__apply"
                                >
                                    <span className="visibloc-term-group__label">{group.label}</span>
                                    <span className="visibloc-term-group__count">{group.terms.length}</span>
                                </Button>
                                <Button
                                    icon="trash"
                                    label={__('Supprimer ce groupe', 'visi-bloc-jlg')}
                                    onClick={() => onDeleteGroup(group.id)}
                                    variant="tertiary"
                                />
                            </div>
                        ))}
                    </div>
                ) : null}
            </div>
        </div>
    );
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
        publishTimezone: {
            type: 'string',
            default: SITE_TIMEZONE_VALUE,
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
        scenarioPreset: {
            type: 'string',
            default: '',
        },
    };

    return settings;
}

const withVisibilityControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (!isSupportedBlockName(props.name)) {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes, isSelected, clientId } = props;
        const {
            isHidden,
            deviceVisibility,
            isSchedulingEnabled,
            publishStartDate,
            publishEndDate,
            publishTimezone = SITE_TIMEZONE_VALUE,
            visibilityRoles,
            advancedVisibility: rawAdvancedVisibility,
            fallbackEnabled = true,
            fallbackBehavior = 'inherit',
            fallbackCustomText = '',
            fallbackBlockId,
            scenarioPreset = '',
        } = attributes;

        const advancedVisibility = normalizeAdvancedVisibility(rawAdvancedVisibility);
        const fallbackSettings = useMemo(
            () => getVisiBlocObject('fallbackSettings') || {},
            [],
        );
        const fallbackBlocks = useMemo(() => getVisiBlocArray('fallbackBlocks'), []);
        const rolesMap = useMemo(() => getVisiBlocObject('roles') || {}, []);
        const availableRoleKeys = useMemo(() => Object.keys(rolesMap), [rolesMap]);
        const guidedRecipes = useMemo(() => getVisiBlocArray('guidedRecipes'), []);
        const userSegments = useMemo(() => getVisiBlocArray('userSegments'), []);
        const woocommerceTaxonomies = useMemo(
            () => getVisiBlocArray('woocommerceTaxonomies'),
            [],
        );
        const editorTaxonomies = useMemo(() => getVisiBlocArray('taxonomies'), []);
        const scenarioContext = useMemo(
            () => ({
                availableRoles: availableRoleKeys,
                fallbackBlocks,
                fallbackSettings,
                userSegments,
                woocommerceTaxonomies,
                taxonomies: editorTaxonomies,
            }),
            [
                availableRoleKeys,
                fallbackBlocks,
                fallbackSettings,
                userSegments,
                woocommerceTaxonomies,
                editorTaxonomies,
            ],
        );
        const scenarioPresets = useMemo(
            () => buildScenarioPresets(guidedRecipes, scenarioContext),
            [guidedRecipes, scenarioContext],
        );
        const [isFallbackPreviewVisible, setFallbackPreviewVisible] = useState(false);
        const [activeInspectorStep, setActiveInspectorStep] = useState('device');
        const [isRecipeModalOpen, setRecipeModalOpen] = useState(false);
        const scenarioSeverityLabels = useMemo(
            () => ({
                critical: __('Critique', 'visi-bloc-jlg'),
                high: __('Prioritaire', 'visi-bloc-jlg'),
                medium: __('Standard', 'visi-bloc-jlg'),
                low: __('Suggestion', 'visi-bloc-jlg'),
            }),
            [],
        );
        const normalizeSeverity = useCallback((value) => {
            const allowed = ['critical', 'high', 'medium', 'low'];

            if (allowed.includes(value)) {
                return value;
            }

            return 'medium';
        }, []);
        const handleApplyScenario = useCallback(
            (preset) => {
                if (!preset || !preset.attributes) {
                    return;
                }

                setAttributes(preset.attributes);

                if (preset.attributes.isSchedulingEnabled) {
                    setActiveInspectorStep('schedule');
                } else if (
                    preset.attributes.fallbackEnabled &&
                    preset.attributes.fallbackBehavior &&
                    preset.attributes.fallbackBehavior !== 'inherit'
                ) {
                    setActiveInspectorStep('fallback');
                } else if (
                    preset.attributes.advancedVisibility &&
                    Array.isArray(preset.attributes.advancedVisibility.rules) &&
                    preset.attributes.advancedVisibility.rules.length > 0
                ) {
                    setActiveInspectorStep('advanced');
                } else if (
                    Array.isArray(preset.attributes.visibilityRoles) &&
                    preset.attributes.visibilityRoles.length > 0
                ) {
                    setActiveInspectorStep('roles');
                } else {
                    setActiveInspectorStep('device');
                }
            },
            [setAttributes, setActiveInspectorStep],
        );
        const handleApplyRecipe = useCallback(
            (recipe) => {
                const attributesPatch = buildRecipeAttributes(recipe, scenarioContext);

                if (!attributesPatch) {
                    return;
                }

                setAttributes(attributesPatch);

                if (attributesPatch.isSchedulingEnabled) {
                    setActiveInspectorStep('schedule');
                } else if (
                    attributesPatch.advancedVisibility &&
                    Array.isArray(attributesPatch.advancedVisibility.rules) &&
                    attributesPatch.advancedVisibility.rules.length > 0
                ) {
                    setActiveInspectorStep('advanced');
                } else if (
                    Array.isArray(attributesPatch.visibilityRoles) &&
                    attributesPatch.visibilityRoles.length > 0
                ) {
                    setActiveInspectorStep('roles');
                } else if (
                    attributesPatch.fallbackEnabled &&
                    attributesPatch.fallbackBehavior &&
                    attributesPatch.fallbackBehavior !== 'inherit'
                ) {
                    setActiveInspectorStep('fallback');
                } else {
                    setActiveInspectorStep('device');
                }

                setRecipeModalOpen(false);
            },
            [scenarioContext, setAttributes, setActiveInspectorStep],
        );
        const hasFallback = getBlockHasFallback(attributes);
        const hasGlobalFallback = Boolean(fallbackSettings && fallbackSettings.hasContent);
        const globalFallbackSummary = fallbackSettings && typeof fallbackSettings.summary === 'string'
            ? fallbackSettings.summary
            : '';
        const localizedFallbackPreview =
            fallbackSettings && typeof fallbackSettings.previewHtml === 'string'
                ? fallbackSettings.previewHtml
                : '';
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

        const globalFallbackPreviewHtml = useMemo(
            () => sanitizeFallbackPreviewHtml(localizedFallbackPreview),
            [localizedFallbackPreview],
        );

        const customFallbackPreviewHtml = useMemo(
            () => (fallbackBehavior === 'text' ? getTextFallbackPreviewHtml(fallbackCustomText) : ''),
            [fallbackBehavior, fallbackCustomText],
        );

        useEffect(() => {
            markClientIdAsDirty(clientId);
        }, [clientId, isHidden, fallbackBehavior, fallbackCustomText, fallbackBlockId, fallbackEnabled]);

        const { blockPreviewHtml, isBlockPreviewResolving } = useSelect(
            (selectFn) => {
                if (fallbackBehavior !== 'block' || !fallbackBlockId) {
                    return {
                        blockPreviewHtml: '',
                        isBlockPreviewResolving: false,
                    };
                }

                const coreStore = selectFn('core');
                const dataStore = selectFn('core/data');
                const record = coreStore
                    ? coreStore.getEntityRecord('postType', 'wp_block', fallbackBlockId)
                    : undefined;
                const isResolving = dataStore
                    ? dataStore.isResolving('core', 'getEntityRecord', [
                          'postType',
                          'wp_block',
                          fallbackBlockId,
                      ])
                    : false;

                const html =
                    record && record.content && typeof record.content.rendered === 'string'
                        ? record.content.rendered
                        : '';

                return {
                    blockPreviewHtml: html,
                    isBlockPreviewResolving: isResolving || typeof record === 'undefined',
                };
            },
            [fallbackBehavior, fallbackBlockId],
        );

        const blockFallbackPreviewHtml = useMemo(
            () => (fallbackBehavior === 'block' ? sanitizeFallbackPreviewHtml(blockPreviewHtml) : ''),
            [fallbackBehavior, blockPreviewHtml],
        );

        const fallbackPreviewDetails = useMemo(() => {
            if (!fallbackEnabled) {
                return {
                    status: 'info',
                    message: __('Le repli est désactivé pour ce bloc.', 'visi-bloc-jlg'),
                    html: '',
                };
            }

            if (fallbackBehavior === 'text') {
                const trimmedText = typeof fallbackCustomText === 'string' ? fallbackCustomText.trim() : '';

                if (!trimmedText) {
                    return {
                        status: 'warning',
                        message: __('Aucun texte de repli défini.', 'visi-bloc-jlg'),
                        html: '',
                    };
                }

                if (!customFallbackPreviewHtml) {
                    return {
                        status: 'warning',
                        message: __('Prévisualisation indisponible pour ce texte.', 'visi-bloc-jlg'),
                        html: '',
                    };
                }

                return {
                    status: 'ready',
                    message: '',
                    html: customFallbackPreviewHtml,
                };
            }

            if (fallbackBehavior === 'block') {
                if (!fallbackBlockId) {
                    return {
                        status: 'warning',
                        message: __('Aucun bloc réutilisable sélectionné.', 'visi-bloc-jlg'),
                        html: '',
                    };
                }

                if (isBlockPreviewResolving) {
                    return {
                        status: 'info',
                        message: __('Chargement de l’aperçu du bloc de repli…', 'visi-bloc-jlg'),
                        html: '',
                    };
                }

                if (!blockFallbackPreviewHtml) {
                    return {
                        status: 'warning',
                        message: __('Impossible d’afficher l’aperçu du bloc sélectionné.', 'visi-bloc-jlg'),
                        html: '',
                    };
                }

                return {
                    status: 'ready',
                    message: '',
                    html: blockFallbackPreviewHtml,
                };
            }

            if (!hasGlobalFallback) {
                return {
                    status: 'warning',
                    message: __('Aucun repli global n’est configuré.', 'visi-bloc-jlg'),
                    html: '',
                };
            }

            if (!globalFallbackPreviewHtml) {
                return {
                    status: 'warning',
                    message: __('Aucun aperçu disponible pour le repli global.', 'visi-bloc-jlg'),
                    html: '',
                };
            }

            return {
                status: 'ready',
                message: '',
                html: globalFallbackPreviewHtml,
            };
        }, [
            fallbackEnabled,
            fallbackBehavior,
            fallbackCustomText,
            customFallbackPreviewHtml,
            fallbackBlockId,
            isBlockPreviewResolving,
            blockFallbackPreviewHtml,
            hasGlobalFallback,
            globalFallbackPreviewHtml,
        ]);

        const fallbackPreviewHtmlContent = fallbackPreviewDetails.html;
        const fallbackPreviewNoticeStatus = fallbackPreviewDetails.status;
        const fallbackPreviewNoticeMessage = fallbackPreviewDetails.message;
        const fallbackPreviewRegionRef = useRef(null);
        const fallbackPreviewRegionId = useInstanceId(
            withVisibilityControls,
            'visibloc-fallback-preview',
        );
        const fallbackPreviewRegionLabelId = `${fallbackPreviewRegionId}__label`;

        useEffect(() => {
            if (
                isFallbackPreviewVisible &&
                fallbackEnabled &&
                fallbackPreviewRegionRef.current
            ) {
                fallbackPreviewRegionRef.current.focus({ preventScroll: true });
            }
        }, [
            isFallbackPreviewVisible,
            fallbackEnabled,
            fallbackPreviewHtmlContent,
            fallbackPreviewNoticeStatus,
        ]);

        useEffect(
            () => () => {
                if (!clientId) {
                    return;
                }

                blockVisibilityState.delete(clientId);
                pendingListViewUpdates.delete(clientId);
                listViewRowCache.delete(clientId);
                registeredSupportedClientIds.delete(clientId);
            },
            [clientId],
        );


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

        const timezoneEntries = useMemo(() => getVisiBlocArray('timezones'), []);
        const timezoneOptions = useMemo(
            () =>
                timezoneEntries
                    .map((item) => {
                        if (!item || typeof item !== 'object') {
                            return null;
                        }

                        const value = typeof item.value === 'string' ? item.value.trim() : '';

                        if (!value) {
                            return null;
                        }

                        const label =
                            typeof item.label === 'string' && item.label.trim()
                                ? item.label
                                : value;

                        return {
                            value,
                            label,
                        };
                    })
                    .filter(Boolean),
            [timezoneEntries],
        );
        const siteTimezoneLabel = useMemo(
            () => sprintf(__('Fuseau du site (%s)', 'visi-bloc-jlg'), TIMEZONE_LABEL),
            [],
        );
        const timezoneControlOptions = useMemo(
            () => [
                { value: SITE_TIMEZONE_VALUE, label: siteTimezoneLabel },
                ...timezoneOptions,
            ],
            [siteTimezoneLabel, timezoneOptions],
        );
        const normalizedPublishTimezone = useMemo(
            () =>
                timezoneControlOptions.some((option) => option.value === publishTimezone)
                    ? publishTimezone
                    : SITE_TIMEZONE_VALUE,
            [publishTimezone, timezoneControlOptions],
        );
        const timezoneDisplayLabel = useMemo(() => {
            const match = timezoneControlOptions.find(
                (option) => option.value === normalizedPublishTimezone,
            );

            return match ? match.label : siteTimezoneLabel;
        }, [normalizedPublishTimezone, timezoneControlOptions, siteTimezoneLabel]);
        const hasCustomScheduleTimezone = normalizedPublishTimezone !== SITE_TIMEZONE_VALUE;
        const timezoneSummary = useMemo(
            () => sprintf(__('Fuseau horaire : %s', 'visi-bloc-jlg'), timezoneDisplayLabel),
            [timezoneDisplayLabel],
        );

        let scheduleSummary = __('Aucune programmation.', 'visi-bloc-jlg');

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
                return (
                    <TaxonomyRuleEditor
                        key={rule.id}
                        rule={rule}
                        taxonomies={editorTaxonomies}
                        onUpdateRule={onUpdateRule}
                        commonHeader={commonHeader}
                    />
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

            if (rule.type === 'user_segment') {
                const segments = getVisiBlocArray('userSegments')
                    .map((item) => ({
                        value: typeof item.value === 'string' ? item.value : '',
                        label:
                            typeof item.label === 'string' && item.label.trim()
                                ? item.label
                                : typeof item.value === 'string'
                                  ? item.value
                                  : '',
                    }))
                    .filter((option) => option.value);

                const operatorOptions = [
                    { value: 'matches', label: __('Appartient au segment', 'visi-bloc-jlg') },
                    { value: 'does_not_match', label: __('N’appartient pas au segment', 'visi-bloc-jlg') },
                ];

                const selectedSegment = typeof rule.segment === 'string' ? rule.segment : '';

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={operatorOptions}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <ComboboxControl
                            label={__('Identifiant de segment', 'visi-bloc-jlg')}
                            value={selectedSegment}
                            options={segments}
                            onChange={(newValue) => onUpdateRule({ segment: newValue || '' })}
                            allowReset
                            help={__(
                                'Choisissez un segment fourni par vos intégrations marketing ou saisissez un identifiant personnalisé.',
                                'visi-bloc-jlg',
                            )}
                        />
                        {!segments.length && (
                            <p className="components-help-text">
                                {__(
                                    'Aucun segment n’a encore été exposé via le filtre « visibloc_jlg_user_segments ».',
                                    'visi-bloc-jlg',
                                )}
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

            if (rule.type === 'cookie') {
                const suggestions = getVisiBlocArray('commonCookies').map((item) => ({
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

                const selectedSuggestion = suggestions.some((item) => item.value === rule.name)
                    ? rule.name
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
                            label={__('Cookie courant', 'visi-bloc-jlg')}
                            value={selectedSuggestion}
                            options={suggestionOptions}
                            onChange={(newValue) => {
                                if (!newValue) {
                                    return;
                                }

                                onUpdateRule({ name: newValue });
                            }}
                        />
                        <TextControl
                            label={__('Nom du cookie', 'visi-bloc-jlg')}
                            value={rule.name}
                            onChange={(newValue) => onUpdateRule({ name: newValue })}
                            help={__(
                                'Saisissez le nom exact du cookie attendu (respect de la casse).',
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

            if (rule.type === 'visit_count') {
                const operatorOptions = [
                    { value: 'at_least', label: __('Au moins', 'visi-bloc-jlg') },
                    { value: 'at_most', label: __('Au plus', 'visi-bloc-jlg') },
                    { value: 'equals', label: __('Est exactement', 'visi-bloc-jlg') },
                    { value: 'not_equals', label: __('N’est pas exactement', 'visi-bloc-jlg') },
                ];

                const thresholdValue = Number.isFinite(rule.threshold) && rule.threshold >= 0 ? String(rule.threshold) : '0';

                return (
                    <div key={rule.id} className="visibloc-advanced-rule">
                        {commonHeader}
                        <SelectControl
                            label={__('Condition', 'visi-bloc-jlg')}
                            value={rule.operator}
                            options={operatorOptions}
                            onChange={(newOperator) => onUpdateRule({ operator: newOperator })}
                        />
                        <TextControl
                            label={__('Nombre de visites', 'visi-bloc-jlg')}
                            type="number"
                            min={0}
                            value={thresholdValue}
                            onChange={(newValue) => {
                                const parsed = Number.parseInt(newValue, 10);
                                onUpdateRule({
                                    threshold: Number.isNaN(parsed) || parsed < 0 ? 0 : parsed,
                                });
                            }}
                            help={__(
                                'Le compteur de visites est stocké dans le cookie « visibloc_visit_count ».',
                                'visi-bloc-jlg',
                            )}
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

        const inactiveSummaryLabel = __('Inactif', 'visi-bloc-jlg');

        const summaryOrInactive = (summary) =>
            summary && String(summary).trim() ? summary : inactiveSummaryLabel;

        const renderHelpText = (text, extraClassName = '') => {
            if (!text) {
                return null;
            }

            const classNames = ['visi-bloc-help-text'];

            if (extraClassName) {
                classNames.push(extraClassName);
            }

            return <p className={classNames.join(' ')}>{text}</p>;
        };

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

            let summaryLabel;

            if (publishStartDate && publishEndDate) {
                summaryLabel = __('Plage définie', 'visi-bloc-jlg');
            } else if (publishStartDate || publishEndDate) {
                summaryLabel = __('Date définie', 'visi-bloc-jlg');
            } else {
                summaryLabel = __('Programmation active', 'visi-bloc-jlg');
            }

            if (hasCustomScheduleTimezone) {
                return `${summaryLabel} – ${timezoneDisplayLabel}`;
            }

            return summaryLabel;
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

        const hiddenDescriptionParts = [];

        if (isHidden) {
            hiddenDescriptionParts.push(__('Bloc masqué manuellement.', 'visi-bloc-jlg'));
        }

        if (deviceVisibilitySummary) {
            hiddenDescriptionParts.push(
                sprintf(__('Appareils : %s', 'visi-bloc-jlg'), deviceVisibilitySummary),
            );
        }

        if (isSchedulingEnabled) {
            hiddenDescriptionParts.push(
                sprintf(__('Programmation : %s', 'visi-bloc-jlg'), scheduleSummary),
            );
            hiddenDescriptionParts.push(
                sprintf(__('Fuseau horaire : %s', 'visi-bloc-jlg'), timezoneDisplayLabel),
            );
        }

        if (rolesSummary) {
            hiddenDescriptionParts.push(
                sprintf(__('Rôles ciblés : %s', 'visi-bloc-jlg'), rolesSummary),
            );
        }

        if (advancedRulesSummary) {
            hiddenDescriptionParts.push(
                sprintf(__('Règles avancées : %s', 'visi-bloc-jlg'), advancedRulesSummary),
            );
        }

        const fallbackDescriptionParts = [];

        if (hasFallback) {
            if (fallbackSummary) {
                fallbackDescriptionParts.push(
                    sprintf(__('Type : %s', 'visi-bloc-jlg'), fallbackSummary),
                );
            }

            if (fallbackBehavior === 'inherit') {
                if (globalFallbackSummary) {
                    fallbackDescriptionParts.push(
                        sprintf(__('Repli global : %s', 'visi-bloc-jlg'), globalFallbackSummary),
                    );
                } else if (hasGlobalFallback) {
                    fallbackDescriptionParts.push(
                        __('Utilise le contenu de repli global.', 'visi-bloc-jlg'),
                    );
                } else {
                    fallbackDescriptionParts.push(
                        __('Aucun repli global défini.', 'visi-bloc-jlg'),
                    );
                }
            } else if (fallbackBehavior === 'text') {
                const trimmedText = typeof fallbackCustomText === 'string'
                    ? fallbackCustomText.trim()
                    : '';

                if (trimmedText) {
                    fallbackDescriptionParts.push(trimmedText);
                }
            } else if (fallbackBehavior === 'block' && fallbackBlockId) {
                const matchingFallbackBlock = fallbackBlockOptions.find(
                    (option) => option.value === String(fallbackBlockId),
                );

                if (matchingFallbackBlock && matchingFallbackBlock.label) {
                    fallbackDescriptionParts.push(
                        sprintf(__('Bloc : %s', 'visi-bloc-jlg'), matchingFallbackBlock.label),
                    );
                }
            }
        }

        if (clientId) {
            const hiddenDescription = hiddenDescriptionParts.join('\n').trim();
            const fallbackDescription = fallbackDescriptionParts.join('\n').trim();
            const previousState = blockVisibilityState.get(clientId) || {};
            const nextState = {
                ...previousState,
                isHidden: Boolean(isHidden),
                hasFallback,
                hiddenDescription,
                fallbackDescription,
            };

            blockVisibilityState.set(clientId, nextState);

            if (
                previousState.isHidden !== nextState.isHidden ||
                previousState.hasFallback !== nextState.hasFallback ||
                previousState.hiddenDescription !== nextState.hiddenDescription ||
                previousState.fallbackDescription !== nextState.fallbackDescription
            ) {
                queueListViewUpdate(clientId, nextState);
            }
        }

        const advancedRulesList = Array.isArray(advancedVisibility.rules)
            ? advancedVisibility.rules
            : [];
        const hasAdvancedRules = advancedRulesList.length > 0;
        const hasAdvancedWarnings = hasAdvancedRules
            ? advancedRulesList.some((rule) => {
                  if (!rule || typeof rule !== 'object') {
                      return false;
                  }

                  if (rule.type === 'taxonomy' || rule.type === 'woocommerce_cart') {
                      return !Array.isArray(rule.terms) || rule.terms.length === 0;
                  }

                  if (rule.type === 'user_segment') {
                      return !rule.segment;
                  }

                  return false;
              })
            : false;
        const fallbackIssues = fallbackEnabled
            ? (fallbackBehavior === 'text' && !fallbackCustomText.trim()) ||
              (fallbackBehavior === 'block' && !fallbackBlockId) ||
              (fallbackBehavior === 'inherit' && !hasGlobalFallback)
            : false;
        const stepBadges = {};

        if (isHidden) {
            stepBadges.device = {
                variant: 'hidden',
                label: __('Masqué', 'visi-bloc-jlg'),
                description: __('Ce bloc est masqué manuellement.', 'visi-bloc-jlg'),
            };
        }

        if (isSchedulingEnabled) {
            stepBadges.schedule = hasScheduleRangeError
                ? {
                      variant: 'warning',
                      label: __('Dates invalides', 'visi-bloc-jlg'),
                      description: __('Vérifiez la cohérence des dates de début et de fin.', 'visi-bloc-jlg'),
                  }
                : {
                      variant: 'schedule',
                      label: __('Programmation active', 'visi-bloc-jlg'),
                      description: scheduleSummary,
                  };
        }

        if (Array.isArray(visibilityRoles) && visibilityRoles.length > 0) {
            stepBadges.roles = {
                variant: 'success',
                label: __('Audience ciblée', 'visi-bloc-jlg'),
                description: rolesSummary || __('Des rôles spécifiques sont sélectionnés.', 'visi-bloc-jlg'),
            };
        }

        if (hasAdvancedRules) {
            stepBadges.advanced = hasAdvancedWarnings
                ? {
                      variant: 'warning',
                      label: __('Règles incomplètes', 'visi-bloc-jlg'),
                      description: __(
                          'Complétez les taxonomies, segments ou conditions manquantes.',
                          'visi-bloc-jlg',
                      ),
                  }
                : {
                      variant: 'advanced',
                      label: __('Règles actives', 'visi-bloc-jlg'),
                      description: advancedRulesSummary || __('Des règles avancées filtrent ce bloc.', 'visi-bloc-jlg'),
                  };
        }

        if (fallbackEnabled) {
            stepBadges.fallback = fallbackIssues
                ? {
                      variant: 'warning',
                      label: __('Repli à compléter', 'visi-bloc-jlg'),
                      description: __(
                          'Ajoutez un texte, un bloc ou définissez un repli global pour éviter une impasse.',
                          'visi-bloc-jlg',
                      ),
                  }
                : {
                      variant: 'fallback',
                      label: __('Repli prêt', 'visi-bloc-jlg'),
                      description: fallbackSummary || __('Un repli est prêt pour ce bloc.', 'visi-bloc-jlg'),
                  };
        }

        const inspectorSteps = [
            {
                id: 'device',
                label: __('Appareils', 'visi-bloc-jlg'),
                summary: deviceVisibilitySummary,
                content: (
                    <BaseControl label={__('Visibilité par appareil', 'visi-bloc-jlg')}>
                        <div className="visi-bloc-device-toggle-groups">
                            {DEVICE_VISIBILITY_OPTIONS.map((group) => {
                                const isGroupActive = group.options.some(
                                    (option) => option.id === deviceVisibility,
                                );

                                return (
                                    <div key={group.id} className="visi-bloc-device-toggle-group">
                                        <BaseControl.VisualLabel>{group.label}</BaseControl.VisualLabel>
                                        <ToggleGroupControl
                                            className="visi-bloc-device-toggle"
                                            isBlock
                                            isDeselectable
                                            value={isGroupActive ? deviceVisibility : undefined}
                                            onChange={(newValue) =>
                                                setAttributes({
                                                    deviceVisibility:
                                                        newValue || DEVICE_VISIBILITY_DEFAULT_OPTION.id,
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
                                        deviceVisibility: DEVICE_VISIBILITY_DEFAULT_OPTION.id,
                                    })
                                }
                                disabled={deviceVisibility === DEVICE_VISIBILITY_DEFAULT_OPTION.id}
                            >
                                {DEVICE_VISIBILITY_DEFAULT_OPTION.label}
                            </Button>
                        </div>
                    </BaseControl>
                ),
            },
            {
                id: 'schedule',
                label: __('Calendrier', 'visi-bloc-jlg'),
                summary: schedulingSummaryLabel,
                content: (
                    <Fragment>
                        <ToggleControl
                            label={__('Activer la programmation', 'visi-bloc-jlg')}
                            checked={isSchedulingEnabled}
                            onChange={() =>
                                setAttributes({
                                    isSchedulingEnabled: !isSchedulingEnabled,
                                })
                            }
                        />
                        {renderHelpText(scheduleSummary, 'is-subtle')}
                        {isSchedulingEnabled && (
                            <div className="visibloc-schedule-controls">
                                {hasScheduleRangeError && (
                                    <Notice status="error" isDismissible={false}>
                                        {__(
                                            'La date de fin doit être postérieure à la date de début.',
                                            'visi-bloc-jlg',
                                        )}
                                    </Notice>
                                )}
                                <ComboboxControl
                                    label={__('Fuseau horaire de programmation', 'visi-bloc-jlg')}
                                    value={normalizedPublishTimezone}
                                    options={timezoneControlOptions}
                                    onChange={(newValue) => {
                                        const matchingOption = timezoneControlOptions.find(
                                            (option) => option.value === newValue,
                                        );

                                        setAttributes({
                                            publishTimezone: matchingOption
                                                ? matchingOption.value
                                                : SITE_TIMEZONE_VALUE,
                                        });
                                    }}
                                    placeholder={__('Rechercher un fuseau horaire…', 'visi-bloc-jlg')}
                                    __nextHasNoMarginBottom
                                    help={__(
                                        'Choisissez un fuseau horaire spécifique pour cette plage. Par défaut, le fuseau du site est utilisé.',
                                        'visi-bloc-jlg',
                                    )}
                                />
                                {renderHelpText(timezoneSummary, 'is-subtle')}
                                <div className="visibloc-schedule-date-field">
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
                                </div>
                                <div className="visibloc-schedule-date-field">
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
                            </div>
                        )}
                    </Fragment>
                ),
            },
            {
                id: 'roles',
                label: __('Rôles', 'visi-bloc-jlg'),
                summary: rolesSummary,
                content: (
                    <BaseControl
                        label={__('Audience ciblée', 'visi-bloc-jlg')}
                        help={__(
                            "Sélectionnez les rôles autorisés à voir ce bloc. Laisser vide pour l’afficher à tout le monde.",
                            'visi-bloc-jlg',
                        )}
                    >
                        <div className="visibloc-roles-list">
                            <CheckboxControl
                                label={__('Visiteurs déconnectés', 'visi-bloc-jlg')}
                                checked={visibilityRoles.includes('logged-out')}
                                onChange={(isChecked) => onRoleChange(isChecked, 'logged-out')}
                            />
                            <CheckboxControl
                                label={__('Utilisateurs connectés (tous)', 'visi-bloc-jlg')}
                                checked={visibilityRoles.includes('logged-in')}
                                onChange={(isChecked) => onRoleChange(isChecked, 'logged-in')}
                            />
                            <div className="visibloc-roles-list__divider" aria-hidden="true" />
                            {Object.entries(VisiBlocData.roles || {})
                                .sort(([, firstLabel], [, secondLabel]) =>
                                    String(firstLabel).localeCompare(String(secondLabel)),
                                )
                                .map(([slug, name]) => (
                                    <CheckboxControl
                                        key={slug}
                                        label={name}
                                        checked={visibilityRoles.includes(slug)}
                                        onChange={(isChecked) => onRoleChange(isChecked, slug)}
                                    />
                                ))}
                        </div>
                    </BaseControl>
                ),
            },
            {
                id: 'advanced',
                label: __('Règles avancées', 'visi-bloc-jlg'),
                summary: advancedRulesSummary,
                content: (
                    <div className="visibloc-advanced-rules">
                        <SelectControl
                            label={__('Logique entre les règles', 'visi-bloc-jlg')}
                            value={advancedVisibility.logic}
                            options={[
                                {
                                    value: 'AND',
                                    label: __('Toutes les règles doivent être vraies (ET)', 'visi-bloc-jlg'),
                                },
                                {
                                    value: 'OR',
                                    label: __('Au moins une règle doit être vraie (OU)', 'visi-bloc-jlg'),
                                },
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
                                <MenuGroup label={__('Types de règles disponibles', 'visi-bloc-jlg')}>
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
                        {renderHelpText(
                            __('Ces règles permettent d’affiner la visibilité selon le contexte du contenu, le modèle ou un horaire récurrent.', 'visi-bloc-jlg'),
                            'is-subtle',
                        )}
                    </div>
                ),
            },
            {
                id: 'fallback',
                label: __('Repli', 'visi-bloc-jlg'),
                summary: fallbackSummary,
                content: (
                    <Fragment>
                        <ToggleControl
                            label={__('Activer le repli pour ce bloc', 'visi-bloc-jlg')}
                            checked={fallbackEnabled}
                            onChange={() => setAttributes({ fallbackEnabled: !fallbackEnabled })}
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
                                        onChange={(value) => setAttributes({ fallbackCustomText: value })}
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
                                <ToggleControl
                                    label={__('Prévisualiser le repli', 'visi-bloc-jlg')}
                                    checked={isFallbackPreviewVisible}
                                    onChange={() =>
                                        setFallbackPreviewVisible((currentValue) => !currentValue)
                                    }
                                    disabled={!fallbackEnabled}
                                    aria-controls={fallbackPreviewRegionId}
                                    aria-expanded={isFallbackPreviewVisible && fallbackEnabled}
                                />
                                {isFallbackPreviewVisible && fallbackEnabled && (
                                    <div
                                        ref={fallbackPreviewRegionRef}
                                        id={fallbackPreviewRegionId}
                                        className="visibloc-fallback-preview"
                                        role="region"
                                        aria-live="polite"
                                        aria-labelledby={fallbackPreviewRegionLabelId}
                                        tabIndex="-1"
                                    >
                                        <span
                                            id={fallbackPreviewRegionLabelId}
                                            className="screen-reader-text"
                                        >
                                            {__('Aperçu du contenu de repli', 'visi-bloc-jlg')}
                                        </span>
                                        {fallbackPreviewHtmlContent ? (
                                            <div className="visibloc-fallback-preview__content">
                                                <RawHTML>{fallbackPreviewHtmlContent}</RawHTML>
                                            </div>
                                        ) : (
                                            <Notice
                                                status={
                                                    fallbackPreviewNoticeStatus === 'warning'
                                                        ? 'warning'
                                                        : 'info'
                                                }
                                                isDismissible={false}
                                            >
                                                {fallbackPreviewNoticeMessage ||
                                                    __('Aucun aperçu disponible pour ce repli.', 'visi-bloc-jlg')}
                                            </Notice>
                                        )}
                                    </div>
                                )}
                            </Fragment>
                        )}
                    </Fragment>
                ),
            },
        ];

        const defaultInspectorStep = inspectorSteps[0] || null;
        const inspectorTabs = inspectorSteps.map((step, index) => {
            const badge = stepBadges[step.id];
            const stepTitle = sprintf(__('Étape %1$d · %2$s', 'visi-bloc-jlg'), index + 1, step.label);

            return {
                name: step.id,
                title: (
                    <span className="visibloc-stepper__tab-label">
                        <span className="visibloc-stepper__tab-text">{stepTitle}</span>
                        {badge ? (
                            <Tooltip text={badge.description}>
                                <span
                                    className={`visibloc-stepper__status visibloc-stepper__status--${badge.variant}`}
                                    aria-label={badge.description}
                                >
                                    {badge.label}
                                </span>
                            </Tooltip>
                        ) : null}
                    </span>
                ),
                className: 'visibloc-stepper__tab',
            };
        });

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
                                title={__('Parcours de visibilité', 'visi-bloc-jlg')}
                                initialOpen={true}
                                className="visibloc-panel--guided"
                            >
                                {scenarioPresets.length > 0 && (
                                    <div className="visibloc-scenario-presets">
                                        <div className="visibloc-scenario-presets__header">
                                            <p className="visibloc-scenario-presets__title">
                                                {__('Scénarios recommandés', 'visi-bloc-jlg')}
                                            </p>
                                            <Button
                                                variant="link"
                                                onClick={() => setRecipeModalOpen(true)}
                                            >
                                                {__('Explorer toutes les recettes', 'visi-bloc-jlg')}
                                            </Button>
                                        </div>
                                        <div className="visibloc-scenario-presets__grid">
                                            {scenarioPresets.map((preset) => {
                                                const severity = normalizeSeverity(preset.severity);
                                                const label =
                                                    scenarioSeverityLabels[severity] ||
                                                    scenarioSeverityLabels.medium;
                                                const isActivePreset = scenarioPreset === preset.id;

                                                return (
                                                    <Button
                                                        key={preset.id}
                                                        className={`visibloc-scenario-presets__button visibloc-scenario-presets__button--${severity}`}
                                                        variant={isActivePreset ? 'primary' : 'secondary'}
                                                        aria-pressed={isActivePreset}
                                                        onClick={() => handleApplyScenario(preset)}
                                                    >
                                                        <span className="visibloc-scenario-presets__badge">{label}</span>
                                                        <span className="visibloc-scenario-presets__label">
                                                            {preset.label}
                                                        </span>
                                                        {preset.description ? (
                                                            <span className="visibloc-scenario-presets__description">
                                                                {preset.description}
                                                            </span>
                                                        ) : null}
                                                    </Button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                )}
                                {inspectorTabs.length > 0 && (
                                    <TabPanel
                                        className="visibloc-stepper"
                                        activeClass="is-active"
                                        initialTabName={activeInspectorStep}
                                        onSelect={(tabName) => setActiveInspectorStep(tabName)}
                                        tabs={inspectorTabs}
                                    >
                                        {(tab) => {
                                            const currentStep =
                                                inspectorSteps.find((step) => step.id === tab.name) ||
                                                defaultInspectorStep;
                                            const stepSummary = summaryOrInactive(
                                                currentStep ? currentStep.summary : '',
                                            );
                                            const stepContent = currentStep ? currentStep.content : null;

                                            return (
                                                <Fragment>
                                                    {renderHelpText(stepSummary, 'is-summary')}
                                                    <div className="visibloc-step-content">{stepContent}</div>
                                                </Fragment>
                                            );
                                        }}
                                    </TabPanel>
                                )}
                            </PanelBody>
                        </InspectorControls>
                        {isRecipeModalOpen && (
                            <Modal
                                title={__('Bibliothèque de recettes', 'visi-bloc-jlg')}
                                onRequestClose={() => setRecipeModalOpen(false)}
                                className="visibloc-recipes-modal"
                            >
                                <div className="visibloc-recipes-modal__grid">
                                    {guidedRecipes.length > 0 ? (
                                        guidedRecipes.map((recipe) => {
                                            const severity = normalizeSeverity(recipe.severity);
                                            const label =
                                                scenarioSeverityLabels[severity] ||
                                                scenarioSeverityLabels.medium;

                                            return (
                                                <Card key={recipe.id} className="visibloc-recipes-modal__card">
                                                    <CardHeader>
                                                        <div className="visibloc-recipes-modal__heading">
                                                            <h3 className="visibloc-recipes-modal__title">
                                                                {recipe.title}
                                                            </h3>
                                                            <span
                                                                className={`visibloc-scenario-presets__badge visibloc-scenario-presets__badge--${severity}`}
                                                            >
                                                                {label}
                                                            </span>
                                                        </div>
                                                    </CardHeader>
                                                    <CardBody>
                                                        <p>{recipe.description}</p>
                                                    </CardBody>
                                                    <CardFooter>
                                                        <Button
                                                            variant="primary"
                                                            onClick={() => handleApplyRecipe(recipe)}
                                                        >
                                                            {__('Appliquer cette recette', 'visi-bloc-jlg')}
                                                        </Button>
                                                    </CardFooter>
                                                </Card>
                                            );
                                        })
                                    ) : (
                                        <p className="visibloc-recipes-modal__empty">
                                            {__('Aucune recette n’est disponible pour le moment.', 'visi-bloc-jlg')}
                                        </p>
                                    )}
                                </div>
                            </Modal>
                        )}
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

function toggleCompactBadgeMode(shouldEnable) {
    if (compactBadgeModeEnabled === shouldEnable) {
        return;
    }

    if (typeof document === 'undefined' || !document.body) {
        compactBadgeModeEnabled = shouldEnable;

        return;
    }

    compactBadgeModeEnabled = shouldEnable;
    document.body.classList.toggle('visibloc-compact-badges', shouldEnable);
}

function disconnectListViewDensityObserver() {
    if (listViewDensityObserver && observedListViewElement) {
        listViewDensityObserver.unobserve(observedListViewElement);
    }

    if (listViewDensityObserver) {
        listViewDensityObserver.disconnect();
    }

    listViewDensityObserver = null;
    observedListViewElement = null;
    toggleCompactBadgeMode(false);
}

function ensureListViewDensityObserver() {
    if (
        typeof document === 'undefined' ||
        typeof ResizeObserver === 'undefined'
    ) {
        return;
    }

    const container =
        document.querySelector('.block-editor-list-view__container') ||
        document.querySelector('.block-editor-list-view');

    if (!container) {
        return;
    }

    if (!listViewDensityObserver) {
        listViewDensityObserver = new ResizeObserver((entries) => {
            entries.forEach((entry) => {
                const target = entry && entry.target ? entry.target : container;
                const width = entry && entry.contentRect ? entry.contentRect.width : target.offsetWidth;

                toggleCompactBadgeMode(width < 260);
            });
        });
    }

    if (observedListViewElement && observedListViewElement !== container) {
        listViewDensityObserver.unobserve(observedListViewElement);
    }

    observedListViewElement = container;
    listViewDensityObserver.observe(container);

    const initialWidth = container.getBoundingClientRect
        ? container.getBoundingClientRect().width
        : container.offsetWidth;

    toggleCompactBadgeMode(initialWidth < 260);
}

function getAllClientIds(blockEditor) {
    if (!blockEditor) {
        return [];
    }

    if (typeof blockEditor.getClientIdsWithDescendants === 'function') {
        const ids = blockEditor.getClientIdsWithDescendants();

        if (Array.isArray(ids)) {
            return ids;
        }
    }

    const rootClientIds = blockEditor.getBlockOrder();

    if (!Array.isArray(rootClientIds) || !rootClientIds.length) {
        return [];
    }

    const stack = [...rootClientIds];
    const result = [];

    while (stack.length) {
        const clientId = stack.pop();

        if (!clientId) {
            continue;
        }

        result.push(clientId);

        const childIds = blockEditor.getBlockOrder(clientId);

        if (Array.isArray(childIds) && childIds.length) {
            childIds.forEach((childId) => stack.push(childId));
        }
    }

    return result;
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
        let row = listViewRowCache.get(clientId);

        if (!row || !row.isConnected) {
            row = document.querySelector(
                `.block-editor-list-view__block[data-block="${clientId}"]`,
            );

            if (row) {
                listViewRowCache.set(clientId, row);
            }
        }

        if (!row) {
            listViewRowCache.delete(clientId);
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

    ensureListViewDensityObserver();

    listViewRafHandle = null;
}

function queueListViewUpdate(clientId, state) {
    const pendingState = pendingListViewUpdates.get(clientId);

    if (
        pendingState &&
        pendingState.isHidden === state.isHidden &&
        pendingState.hasFallback === state.hasFallback &&
        pendingState.hiddenDescription === state.hiddenDescription &&
        pendingState.fallbackDescription === state.fallbackDescription
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

function syncListViewInternal() {
    const blockEditor = select('core/block-editor');

    if (!blockEditor) {
        return;
    }

    const allClientIds = getAllClientIds(blockEditor);
    const signature = allClientIds.join('|');

    if (!allClientIds.length) {
        if (registeredSupportedClientIds.size || blockVisibilityState.size) {
            registeredSupportedClientIds.clear();
            blockVisibilityState.clear();
            pendingListViewUpdates.clear();
            listViewRowCache.clear();
            blockAttributeHashes.clear();
            dirtyClientIds.clear();
            lastClientIdsSignature = '';
        }

        return;
    }

    const currentClientIds = new Set(allClientIds);

    if (signature !== lastClientIdsSignature) {
        allClientIds.forEach((clientId) => {
            if (registeredSupportedClientIds.has(clientId)) {
                return;
            }

            let blockName = typeof blockEditor.getBlockName === 'function'
                ? blockEditor.getBlockName(clientId)
                : undefined;

            if (!blockName) {
                const block = blockEditor.getBlock(clientId);
                blockName = block ? block.name : undefined;
            }

            if (blockName && isSupportedBlockName(blockName)) {
                registeredSupportedClientIds.add(clientId);
                dirtyClientIds.add(clientId);
                blockAttributeHashes.delete(clientId);
            }
        });

        const staleClientIds = Array.from(registeredSupportedClientIds).filter(
            (clientId) => !currentClientIds.has(clientId),
        );

        staleClientIds.forEach((clientId) => {
            registeredSupportedClientIds.delete(clientId);
            blockVisibilityState.delete(clientId);
            pendingListViewUpdates.delete(clientId);
            listViewRowCache.delete(clientId);
            blockAttributeHashes.delete(clientId);
            dirtyClientIds.delete(clientId);
        });

        lastClientIdsSignature = signature;
    }

    if (!dirtyClientIds.size) {
        return;
    }

    const idsToProcess = Array.from(dirtyClientIds);
    dirtyClientIds.clear();

    idsToProcess.forEach((clientId) => {
        const block = blockEditor.getBlock(clientId);

        if (!block || !isSupportedBlockName(block.name)) {
            blockAttributeHashes.delete(clientId);
            blockVisibilityState.delete(clientId);
            pendingListViewUpdates.delete(clientId);
            listViewRowCache.delete(clientId);
            registeredSupportedClientIds.delete(clientId);
            return;
        }

        const signatureValue = getVisibilityAttributeSignature(block.attributes);

        if (blockAttributeHashes.get(clientId) === signatureValue) {
            return;
        }

        blockAttributeHashes.set(clientId, signatureValue);

        const previousState = blockVisibilityState.get(clientId) || {};
        const nextState = {
            ...previousState,
            isHidden: Boolean(block.attributes.isHidden),
            hasFallback: getBlockHasFallback(block.attributes),
        };

        blockVisibilityState.set(clientId, nextState);
        queueListViewUpdate(clientId, nextState);
    });
}

const debouncedSyncListView = debounce(syncListViewInternal, 120);

function scheduleListViewSync() {
    debouncedSyncListView();
}

function flushScheduledListViewSync() {
    if (typeof debouncedSyncListView.flush === 'function') {
        debouncedSyncListView.flush();
    } else {
        syncListViewInternal();
    }
}

function markClientIdAsDirty(clientId) {
    if (!clientId) {
        return;
    }

    dirtyClientIds.add(clientId);
    scheduleListViewSync();
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
            const visibilityState = props.clientId
                ? blockVisibilityState.get(props.clientId) || {}
                : {};
            const hiddenDescription =
                typeof visibilityState.hiddenDescription === 'string'
                    ? visibilityState.hiddenDescription
                    : '';
            const fallbackDescription =
                typeof visibilityState.fallbackDescription === 'string'
                    ? visibilityState.fallbackDescription
                    : '';

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
                        description={hiddenDescription}
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
                        description={fallbackDescription}
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

    scheduleListViewSync();

    if (isCurrentlyOpened && !wasListViewOpened) {
        flushScheduledListViewSync();
        replayPendingListViewUpdates();
    }

    if (isCurrentlyOpened) {
        ensureListViewDensityObserver();
    } else if (wasListViewOpened) {
        disconnectListViewDensityObserver();
    }

    wasListViewOpened = isCurrentlyOpened;
}

subscribe(handleEditorSubscription);
