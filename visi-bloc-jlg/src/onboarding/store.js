import { useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { registerStore, useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

const STORE_NAME = 'visi-bloc-jlg/onboarding';

const STEP_ORDER = ['objective', 'audience', 'timing', 'content'];

const DEFAULT_STEP_VALUES = {
    objective: {
        goal: '',
        description: '',
        successMetric: '',
    },
    audience: {
        primary: '',
        secondary: '',
        notes: '',
        roles: '',
    },
    timing: {
        start: '',
        end: '',
        cadence: '',
    },
    content: {
        promise: '',
        callToAction: '',
        fallback: '',
    },
};

function createDefaultSteps() {
    return {
        objective: { ...DEFAULT_STEP_VALUES.objective },
        audience: { ...DEFAULT_STEP_VALUES.audience },
        timing: { ...DEFAULT_STEP_VALUES.timing },
        content: { ...DEFAULT_STEP_VALUES.content },
    };
}

const DEFAULT_STATE = {
    isOpen: false,
    currentStep: STEP_ORDER[0],
    steps: createDefaultSteps(),
    recipes: [],
    selectedRecipeId: '',
    badges: {},
    mode: 'simple',
    restEndpoint: '',
    hasInitialized: false,
    hasLoadedDraft: false,
    isLoadingDraft: false,
    isSavingDraft: false,
    lastError: '',
    draftUpdatedAt: null,
};

const ACTION_TYPES = {
    INITIALIZE: 'INITIALIZE',
    SET_OPEN: 'SET_OPEN',
    SET_STEP: 'SET_STEP',
    UPDATE_STEP: 'UPDATE_STEP',
    APPLY_RECIPE: 'APPLY_RECIPE',
    RECEIVE_RECIPES: 'RECEIVE_RECIPES',
    SET_BADGES: 'SET_BADGES',
    START_LOAD_DRAFT: 'START_LOAD_DRAFT',
    FINISH_LOAD_DRAFT: 'FINISH_LOAD_DRAFT',
    START_SAVE_DRAFT: 'START_SAVE_DRAFT',
    FINISH_SAVE_DRAFT: 'FINISH_SAVE_DRAFT',
    SET_ERROR: 'SET_ERROR',
    RESET_ERROR: 'RESET_ERROR',
    RESET_STEPS: 'RESET_STEPS',
};

function normalizeStepValue( value ) {
    if ( Array.isArray( value ) ) {
        return value.map( ( item ) => normalizeStepValue( item ) );
    }

    if ( value && 'object' === typeof value ) {
        return Object.keys( value ).reduce( ( acc, key ) => {
            acc[ key ] = normalizeStepValue( value[ key ] );

            return acc;
        }, {} );
    }

    if ( 'number' === typeof value ) {
        return String( value );
    }

    return 'string' === typeof value ? value : '';
}

function mergeStepValues( current, updates ) {
    if ( ! updates || 'object' !== typeof updates ) {
        return current;
    }

    return {
        ...current,
        ...Object.keys( updates ).reduce( ( acc, key ) => {
            if ( Object.prototype.hasOwnProperty.call( current, key ) ) {
                acc[ key ] = normalizeStepValue( updates[ key ] );
            }

            return acc;
        }, {} ),
    };
}

function buildInitialStateFromConfig( state, config ) {
    if ( state.hasInitialized ) {
        return state;
    }

    const nextState = { ...state, hasInitialized: true };

    if ( config && 'object' === typeof config ) {
        if ( Array.isArray( config.recipes ) ) {
            nextState.recipes = config.recipes.map( ( recipe ) => normalizeRecipe( recipe ) );
        }

        if ( config.mode && 'string' === typeof config.mode ) {
            const normalizedMode = config.mode.toLowerCase();
            nextState.mode = 'expert' === normalizedMode ? 'expert' : 'simple';
        }

        if ( config.restEndpoint && 'string' === typeof config.restEndpoint ) {
            nextState.restEndpoint = config.restEndpoint;
        }
    }

    return nextState;
}

function normalizeRecipe( recipe ) {
    if ( ! recipe || 'object' !== typeof recipe ) {
        return null;
    }

    const normalizedId = recipe.id && 'string' === typeof recipe.id ? recipe.id : '';
    const safeId = normalizedId || `recipe-${ Math.random().toString( 36 ).slice( 2 ) }`;

    const normalized = {
        id: safeId,
        title: recipe.title && 'string' === typeof recipe.title ? recipe.title : safeId,
        summary:
            recipe.summary && 'string' === typeof recipe.summary
                ? recipe.summary
                : '',
        steps: {
            objective: mergeStepValues( DEFAULT_STEP_VALUES.objective, recipe.objective || {} ),
            audience: mergeStepValues( DEFAULT_STEP_VALUES.audience, recipe.audience || {} ),
            timing: mergeStepValues( DEFAULT_STEP_VALUES.timing, recipe.timing || {} ),
            content: mergeStepValues( DEFAULT_STEP_VALUES.content, recipe.content || {} ),
        },
    };

    return normalized;
}

function reducer( state = DEFAULT_STATE, action ) {
    switch ( action.type ) {
        case ACTION_TYPES.INITIALIZE:
            return buildInitialStateFromConfig( state, action.config );
        case ACTION_TYPES.SET_OPEN:
            return {
                ...state,
                isOpen: action.isOpen,
                currentStep: action.isOpen ? state.currentStep : STEP_ORDER[0],
            };
        case ACTION_TYPES.SET_STEP: {
            const target = STEP_ORDER.includes( action.step ) ? action.step : state.currentStep;

            return {
                ...state,
                currentStep: target,
            };
        }
        case ACTION_TYPES.UPDATE_STEP: {
            if ( ! Object.prototype.hasOwnProperty.call( state.steps, action.step ) ) {
                return state;
            }

            return {
                ...state,
                steps: {
                    ...state.steps,
                    [ action.step ]: mergeStepValues( state.steps[ action.step ], action.values ),
                },
            };
        }
        case ACTION_TYPES.APPLY_RECIPE: {
            const recipe = state.recipes.find( ( item ) => item.id === action.recipeId );

            if ( ! recipe ) {
                return {
                    ...state,
                    selectedRecipeId: '',
                    steps: createDefaultSteps(),
                };
            }

            return {
                ...state,
                selectedRecipeId: recipe.id,
                steps: {
                    objective: { ...recipe.steps.objective },
                    audience: { ...recipe.steps.audience },
                    timing: { ...recipe.steps.timing },
                    content: { ...recipe.steps.content },
                },
                currentStep: state.currentStep,
            };
        }
        case ACTION_TYPES.RECEIVE_RECIPES:
            return {
                ...state,
                recipes: Array.isArray( action.recipes )
                    ? action.recipes
                          .map( ( recipe ) => normalizeRecipe( recipe ) )
                          .filter( Boolean )
                    : state.recipes,
            };
        case ACTION_TYPES.SET_BADGES:
            return {
                ...state,
                badges: action.badges && 'object' === typeof action.badges ? action.badges : {},
            };
        case ACTION_TYPES.START_LOAD_DRAFT:
            return {
                ...state,
                isLoadingDraft: true,
                lastError: '',
            };
        case ACTION_TYPES.FINISH_LOAD_DRAFT: {
            const nextSteps = action.draft && action.draft.steps ? action.draft.steps : null;

            return {
                ...state,
                isLoadingDraft: false,
                hasLoadedDraft: true,
                lastError: action.error || '',
                steps: nextSteps
                    ? {
                          objective: mergeStepValues( DEFAULT_STEP_VALUES.objective, nextSteps.objective ),
                          audience: mergeStepValues( DEFAULT_STEP_VALUES.audience, nextSteps.audience ),
                          timing: mergeStepValues( DEFAULT_STEP_VALUES.timing, nextSteps.timing ),
                          content: mergeStepValues( DEFAULT_STEP_VALUES.content, nextSteps.content ),
                      }
                    : state.steps,
                selectedRecipeId:
                    action.draft && 'string' === typeof action.draft.recipeId
                        ? action.draft.recipeId
                        : state.selectedRecipeId,
                draftUpdatedAt: action.updatedAt || null,
            };
        }
        case ACTION_TYPES.START_SAVE_DRAFT:
            return {
                ...state,
                isSavingDraft: true,
                lastError: '',
            };
        case ACTION_TYPES.FINISH_SAVE_DRAFT:
            return {
                ...state,
                isSavingDraft: false,
                draftUpdatedAt: action.updatedAt || state.draftUpdatedAt,
                lastError: action.error || '',
            };
        case ACTION_TYPES.SET_ERROR:
            return {
                ...state,
                lastError: action.message || '',
            };
        case ACTION_TYPES.RESET_ERROR:
            return {
                ...state,
                lastError: '',
            };
        case ACTION_TYPES.RESET_STEPS:
            return {
                ...state,
                steps: createDefaultSteps(),
                selectedRecipeId: '',
            };
        default:
            return state;
    }
}

const controls = {
    API_FETCH( { path, method = 'GET', data } ) {
        if ( ! path ) {
            return Promise.resolve( null );
        }

        const options = {
            method,
            data,
        };

        if ( /^https?:/i.test( path ) ) {
            options.url = path;
        } else {
            options.path = path;
        }

        return apiFetch( options );
    },
};

function* loadDraft( restEndpoint ) {
    if ( ! restEndpoint ) {
        return;
    }

    yield { type: ACTION_TYPES.START_LOAD_DRAFT };

    try {
        const response = yield {
            type: 'API_FETCH',
            path: restEndpoint,
            method: 'GET',
        };

        yield {
            type: ACTION_TYPES.FINISH_LOAD_DRAFT,
            draft: response && response.draft ? response.draft : null,
            updatedAt: response && response.updatedAt ? response.updatedAt : null,
        };
    } catch ( error ) {
        const message = error && error.message ? error.message : __( 'Erreur de chargement du brouillon.', 'visi-bloc-jlg' );

        yield {
            type: ACTION_TYPES.FINISH_LOAD_DRAFT,
            error: message,
        };
    }
}

function* saveDraft( restEndpoint, draft ) {
    if ( ! restEndpoint ) {
        return;
    }

    yield { type: ACTION_TYPES.START_SAVE_DRAFT };

    try {
        const response = yield {
            type: 'API_FETCH',
            path: restEndpoint,
            method: 'POST',
            data: draft,
        };

        yield {
            type: ACTION_TYPES.FINISH_SAVE_DRAFT,
            updatedAt: response && response.updatedAt ? response.updatedAt : null,
        };
    } catch ( error ) {
        const message = error && error.message ? error.message : __( 'Impossible d’enregistrer le brouillon.', 'visi-bloc-jlg' );

        yield {
            type: ACTION_TYPES.FINISH_SAVE_DRAFT,
            error: message,
        };
    }
}

const actions = {
    initialize( config ) {
        return {
            type: ACTION_TYPES.INITIALIZE,
            config,
        };
    },
    openWizard() {
        return {
            type: ACTION_TYPES.SET_OPEN,
            isOpen: true,
        };
    },
    closeWizard() {
        return {
            type: ACTION_TYPES.SET_OPEN,
            isOpen: false,
        };
    },
    goToStep( step ) {
        return {
            type: ACTION_TYPES.SET_STEP,
            step,
        };
    },
    updateStep( step, values ) {
        return {
            type: ACTION_TYPES.UPDATE_STEP,
            step,
            values,
        };
    },
    applyRecipe( recipeId ) {
        return {
            type: ACTION_TYPES.APPLY_RECIPE,
            recipeId,
        };
    },
    setBadges( badges ) {
        return {
            type: ACTION_TYPES.SET_BADGES,
            badges,
        };
    },
    receiveRecipes( recipes ) {
        return {
            type: ACTION_TYPES.RECEIVE_RECIPES,
            recipes,
        };
    },
    loadDraft,
    saveDraft,
    resetSteps() {
        return {
            type: ACTION_TYPES.RESET_STEPS,
        };
    },
    resetError() {
        return {
            type: ACTION_TYPES.RESET_ERROR,
        };
    },
};

const selectors = {
    isOpen( state ) {
        return state.isOpen;
    },
    getCurrentStep( state ) {
        return state.currentStep;
    },
    getSteps( state ) {
        return state.steps;
    },
    getStepValues( state, step ) {
        if ( ! step || ! Object.prototype.hasOwnProperty.call( state.steps, step ) ) {
            return state.steps.objective;
        }

        return state.steps[ step ];
    },
    getRecipes( state ) {
        return state.recipes;
    },
    getSelectedRecipeId( state ) {
        return state.selectedRecipeId;
    },
    getBadges( state ) {
        return state.badges;
    },
    getMode( state ) {
        return state.mode;
    },
    getRestEndpoint( state ) {
        return state.restEndpoint;
    },
    hasLoadedDraft( state ) {
        return state.hasLoadedDraft;
    },
    isLoadingDraft( state ) {
        return state.isLoadingDraft;
    },
    isSavingDraft( state ) {
        return state.isSavingDraft;
    },
    getLastError( state ) {
        return state.lastError;
    },
    getDraftUpdatedAt( state ) {
        return state.draftUpdatedAt;
    },
    getDraftPayload( state ) {
        return {
            recipeId: state.selectedRecipeId,
            steps: state.steps,
            mode: state.mode,
        };
    },
    isInitialized( state ) {
        return state.hasInitialized;
    },
};

const store = registerStore( STORE_NAME, {
    reducer,
    actions,
    selectors,
    controls,
} );

export function useOnboardingWizard( selector = ( stateSelectors ) => stateSelectors, deps = [] ) {
    const selectedState = useSelect( ( select ) => {
        const storeSelectors = select( STORE_NAME );

        if ( ! storeSelectors ) {
            return {};
        }

        return selector( storeSelectors );
    }, deps );
    const actionsDispatch = useDispatch( STORE_NAME );

    return useMemo( () => ({
        ...selectedState,
        actions: actionsDispatch,
    }), [ selectedState, actionsDispatch ] );
}

export { STORE_NAME, STEP_ORDER };
