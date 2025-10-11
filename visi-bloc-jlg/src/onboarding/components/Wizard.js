import { useEffect, useMemo } from '@wordpress/element';
import {
    Button,
    Card,
    CardBody,
    CardFooter,
    CardHeader,
    Flex,
    FlexItem,
    Modal,
    Notice,
    SelectControl,
    Spinner,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { STEP_ORDER, useOnboardingWizard } from '../store';
import ObjectiveStep from './ObjectiveStep';
import AudienceStep from './AudienceStep';
import TimingStep from './TimingStep';
import ContentStep from './ContentStep';

const STEP_COMPONENTS = {
    objective: ObjectiveStep,
    audience: AudienceStep,
    timing: TimingStep,
    content: ContentStep,
};

function StepNavigation( { currentStep, onSelectStep } ) {
    return (
        <Flex className="visibloc-onboarding-nav" wrap>
            {STEP_ORDER.map( ( step, index ) => {
                const isActive = currentStep === step;
                const label = [
                    sprintf( __( 'Étape %1$d', 'visi-bloc-jlg' ), index + 1 ),
                    getStepLabel( step ),
                ]
                    .filter( Boolean )
                    .join( ' · ' );

                return (
                    <FlexItem key={step}>
                        <Button
                            variant={isActive ? 'primary' : 'tertiary'}
                            onClick={() => onSelectStep( step )}
                            aria-current={isActive ? 'step' : undefined}
                        >
                            {label}
                        </Button>
                    </FlexItem>
                );
            } )}
        </Flex>
    );
}

function getStepLabel( step ) {
    switch ( step ) {
        case 'objective':
            return __( 'Objectif', 'visi-bloc-jlg' );
        case 'audience':
            return __( 'Audience', 'visi-bloc-jlg' );
        case 'timing':
            return __( 'Timing', 'visi-bloc-jlg' );
        case 'content':
            return __( 'Contenu', 'visi-bloc-jlg' );
        default:
            return '';
    }
}

function computeRecipeOptions( recipes ) {
    if ( ! Array.isArray( recipes ) ) {
        return [];
    }

    return [
        { label: __( 'Aucune recette appliquée', 'visi-bloc-jlg' ), value: '' },
        ...recipes.map( ( recipe ) => ({
            label: recipe.title || recipe.id,
            value: recipe.id,
        }) ),
    ];
}

function BadgesList( { badges } ) {
    const entries = Object.entries( badges || {} );

    if ( 0 === entries.length ) {
        return null;
    }

    return (
        <ul className="visibloc-onboarding-badges">
            {entries.map( ( [ key, badge ] ) => (
                <li key={key} className={`visibloc-onboarding-badges__item visibloc-onboarding-badges__item--${ badge.variant || 'info' }`}>
                    <span className="visibloc-onboarding-badges__label">{badge.label}</span>
                    {badge.description ? (
                        <span className="visibloc-onboarding-badges__description">{badge.description}</span>
                    ) : null}
                </li>
            ) )}
        </ul>
    );
}

export default function Wizard() {
    const {
        isOpen,
        currentStep,
        actions,
        recipes,
        selectedRecipeId,
        badges,
        isSavingDraft,
        isLoadingDraft,
        hasLoadedDraft,
        draftUpdatedAt,
        lastError,
        restEndpoint,
        draftPayload,
        hasInitialized,
    } = useOnboardingWizard( ( store ) => ({
        isOpen: store.isOpen(),
        currentStep: store.getCurrentStep(),
        recipes: store.getRecipes(),
        selectedRecipeId: store.getSelectedRecipeId(),
        badges: store.getBadges(),
        isSavingDraft: store.isSavingDraft(),
        isLoadingDraft: store.isLoadingDraft(),
        hasLoadedDraft: store.hasLoadedDraft(),
        draftUpdatedAt: store.getDraftUpdatedAt(),
        lastError: store.getLastError(),
        restEndpoint: store.getRestEndpoint(),
        draftPayload: store.getDraftPayload(),
        hasInitialized: store.isInitialized ? store.isInitialized() : false,
    }) );

    useEffect( () => {
        if ( hasInitialized ) {
            return;
        }

        const config = ( window.VisiBlocData && window.VisiBlocData.onboarding ) || {};
        actions.initialize( config );
    }, [ actions, hasInitialized ] );

    useEffect( () => {
        if ( restEndpoint && ! hasLoadedDraft ) {
            actions.loadDraft( restEndpoint );
        }
    }, [ actions, restEndpoint, hasLoadedDraft ] );

    const StepComponent = STEP_COMPONENTS[ currentStep ] || ObjectiveStep;
    const recipeOptions = useMemo( () => computeRecipeOptions( recipes ), [ recipes ] );
    const formattedDate = useMemo( () => {
        if ( ! draftUpdatedAt ) {
            return '';
        }

        try {
            return new Date( draftUpdatedAt * 1000 ).toLocaleString();
        } catch ( error ) {
            return '';
        }
    }, [ draftUpdatedAt ] );

    if ( ! isOpen ) {
        return null;
    }

    return (
        <Modal
            title={__( 'Assistant visibilité – Onboarding', 'visi-bloc-jlg' )}
            onRequestClose={() => actions.closeWizard()}
            className="visibloc-onboarding-modal"
        >
            <Card>
                <CardHeader>
                    <div className="visibloc-onboarding-modal__header">
                        <SelectControl
                            label={__( 'Bibliothèque de recettes', 'visi-bloc-jlg' )}
                            value={selectedRecipeId}
                            options={recipeOptions}
                            onChange={( newValue ) => actions.applyRecipe( newValue )}
                        />
                        <BadgesList badges={badges} />
                    </div>
                </CardHeader>
                <CardBody>
                    {isLoadingDraft && ! hasLoadedDraft ? (
                        <div className="visibloc-onboarding-loading">
                            <Spinner />
                            <p>{__( 'Chargement du brouillon…', 'visi-bloc-jlg' )}</p>
                        </div>
                    ) : (
                        <>
                            <StepNavigation currentStep={currentStep} onSelectStep={( step ) => actions.goToStep( step )} />
                            <div className="visibloc-onboarding-modal__content">
                                <StepComponent />
                            </div>
                        </>
                    )}
                    {lastError ? (
                        <Notice status="error" isDismissible onRemove={() => actions.resetError()}>
                            {lastError}
                        </Notice>
                    ) : null}
                </CardBody>
                <CardFooter>
                    <Flex justify="space-between" align="center">
                        <FlexItem>
                            {formattedDate ? (
                                <span className="visibloc-onboarding-modal__meta">
                                    {sprintf( __( 'Dernier enregistrement : %s', 'visi-bloc-jlg' ), formattedDate )}
                                </span>
                            ) : null}
                        </FlexItem>
                        <FlexItem>
                            <Button
                                variant="secondary"
                                onClick={() => actions.resetSteps()}
                                disabled={isSavingDraft}
                            >
                                {__( 'Réinitialiser', 'visi-bloc-jlg' )}
                            </Button>
                        </FlexItem>
                        <FlexItem>
                            <Button
                                variant="primary"
                                onClick={() => actions.saveDraft( restEndpoint, draftPayload )}
                                isBusy={isSavingDraft}
                                disabled={isSavingDraft}
                            >
                                {isSavingDraft
                                    ? __( 'Enregistrement…', 'visi-bloc-jlg' )
                                    : __( 'Enregistrer le brouillon', 'visi-bloc-jlg' )}
                            </Button>
                        </FlexItem>
                    </Flex>
                </CardFooter>
            </Card>
        </Modal>
    );
}
