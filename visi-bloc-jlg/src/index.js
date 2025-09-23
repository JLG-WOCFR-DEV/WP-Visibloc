/* global VisiBlocData */
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
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
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { format } from '@wordpress/date';
import { subscribe, select } from '@wordpress/data';

import './editor-styles.css';

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

function addVisibilityAttributesToGroup(settings, name) {
    if (name !== 'core/group') {
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
    };

    return settings;
}

const withVisibilityControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (props.name !== 'core/group') {
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
        } = attributes;

        const onRoleChange = (isChecked, roleSlug) => {
            const newRoles = isChecked
                ? [...visibilityRoles, roleSlug]
                : visibilityRoles.filter((role) => role !== roleSlug);

            setAttributes({
                visibilityRoles: newRoles,
            });
        };

        let scheduleSummary = __('Aucune programmation.', 'visi-bloc-jlg');

        if (isSchedulingEnabled) {
            const startDate = publishStartDate
                ? format('d/m/Y H:i', publishStartDate)
                : null;
            const endDate = publishEndDate
                ? format('d/m/Y H:i', publishEndDate)
                : null;

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
        }

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
                                        <CheckboxControl
                                            label={__('Définir une date de début', 'visi-bloc-jlg')}
                                            checked={!!publishStartDate}
                                            onChange={(isChecked) => {
                                                setAttributes({
                                                    publishStartDate: isChecked
                                                        ? new Date().toISOString()
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
                                                    is12Hour={false}
                                                />
                                            </div>
                                        )}
                                        <CheckboxControl
                                            label={__('Définir une date de fin', 'visi-bloc-jlg')}
                                            checked={!!publishEndDate}
                                            onChange={(isChecked) => {
                                                setAttributes({
                                                    publishEndDate: isChecked
                                                        ? new Date().toISOString()
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
                                                    is12Hour={false}
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
                                {Object.entries(VisiBlocData.roles).map(([slug, name]) => (
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
                        </InspectorControls>
                    </Fragment>
                )}
            </Fragment>
        );
    };
}, 'withVisibilityControls');

function addEditorCanvasClasses(props, block) {
    if (block.name !== 'core/group' || !block.attributes) {
        return props;
    }

    const { isHidden } = block.attributes;
    const newClasses = [props.className, isHidden ? 'bloc-editeur-cache' : '']
        .filter(Boolean)
        .join(' ');

    return {
        ...props,
        className: newClasses,
    };
}

function addSaveClasses(extraProps, blockType, attributes) {
    if (blockType.name !== 'core/group' || !attributes) {
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

function flushListViewUpdates() {
    if (typeof document === 'undefined') {
        pendingListViewUpdates.clear();
        listViewRafHandle = null;

        return;
    }

    pendingListViewUpdates.forEach((isHidden, clientId) => {
        const row = document.querySelector(
            `.block-editor-list-view__block[data-block="${clientId}"]`,
        );

        if (!row) {
            return;
        }

        if (isHidden) {
            row.classList.add('bloc-editeur-cache');
        } else {
            row.classList.remove('bloc-editeur-cache');
        }
    });

    pendingListViewUpdates.clear();
    listViewRafHandle = null;
}

function queueListViewUpdate(clientId, isHidden) {
    if (pendingListViewUpdates.get(clientId) === isHidden) {
        return;
    }

    pendingListViewUpdates.set(clientId, isHidden);

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
    const seenGroups = new Set();

    while (stack.length) {
        const clientId = stack.pop();
        const block = blockEditor.getBlock(clientId);

        if (!block) {
            continue;
        }

        if (block.name === 'core/group') {
            const isHidden = Boolean(block.attributes.isHidden);
            const previousState = blockVisibilityState.get(clientId);

            if (previousState !== isHidden) {
                queueListViewUpdate(clientId, isHidden);
            }

            blockVisibilityState.set(clientId, isHidden);
            seenGroups.add(clientId);
        }

        if (block.innerBlocks && block.innerBlocks.length) {
            block.innerBlocks.forEach((innerBlock) => {
                if (innerBlock && innerBlock.clientId) {
                    stack.push(innerBlock.clientId);
                }
            });
        }
    }

    if (seenGroups.size !== blockVisibilityState.size) {
        Array.from(blockVisibilityState.keys()).forEach((clientId) => {
            if (!seenGroups.has(clientId)) {
                blockVisibilityState.delete(clientId);
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

subscribe(syncListView);
