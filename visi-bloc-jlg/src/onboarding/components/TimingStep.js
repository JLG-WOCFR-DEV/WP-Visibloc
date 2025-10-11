import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useOnboardingWizard } from '../store';

export default function TimingStep() {
    const { start = '', end = '', cadence = '', actions } = useOnboardingWizard(
        ( store ) => store.getStepValues( 'timing' ),
        [],
    );

    const updateField = ( key, value ) => {
        actions.updateStep( 'timing', { [ key ]: value } );
    };

    return (
        <div className="visibloc-onboarding-step visibloc-onboarding-step--timing">
            <TextControl
                label={__( 'Point de départ', 'visi-bloc-jlg' )}
                value={start}
                onChange={( newValue ) => updateField( 'start', newValue )}
                help={__( 'Exemple : “Dès l’inscription” ou “J+3 après l’achat”.', 'visi-bloc-jlg' )}
            />
            <TextControl
                label={__( 'Date ou condition de fin', 'visi-bloc-jlg' )}
                value={end}
                onChange={( newValue ) => updateField( 'end', newValue )}
            />
            <TextControl
                label={__( 'Cadence', 'visi-bloc-jlg' )}
                value={cadence}
                onChange={( newValue ) => updateField( 'cadence', newValue )}
                help={__( 'Décrivez la fréquence (ex. “3 rappels sur 10 jours”).', 'visi-bloc-jlg' )}
            />
        </div>
    );
}
