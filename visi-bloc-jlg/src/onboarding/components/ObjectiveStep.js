import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useOnboardingWizard } from '../store';

export default function ObjectiveStep() {
    const { goal = '', description = '', successMetric = '', actions } = useOnboardingWizard(
        ( store ) => store.getStepValues( 'objective' ),
        [],
    );

    const updateField = ( key, value ) => {
        actions.updateStep( 'objective', { [ key ]: value } );
    };

    return (
        <div className="visibloc-onboarding-step visibloc-onboarding-step--objective">
            <TextControl
                label={__( 'Objectif principal', 'visi-bloc-jlg' )}
                value={goal}
                onChange={( newValue ) => updateField( 'goal', newValue )}
            />
            <TextareaControl
                label={__( 'Description', 'visi-bloc-jlg' )}
                value={description}
                onChange={( newValue ) => updateField( 'description', newValue )}
                help={__( 'Expliquez comment l’assistant doit orienter la campagne.', 'visi-bloc-jlg' )}
                rows={4}
            />
            <TextControl
                label={__( 'Indicateur de succès', 'visi-bloc-jlg' )}
                value={successMetric}
                onChange={( newValue ) => updateField( 'successMetric', newValue )}
                help={__( 'Exemple : “Taux de conversion supérieur à 5 %”.', 'visi-bloc-jlg' )}
            />
        </div>
    );
}
