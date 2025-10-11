import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useOnboardingWizard } from '../store';

export default function AudienceStep() {
    const { primary = '', secondary = '', notes = '', roles = '', actions } = useOnboardingWizard(
        ( store ) => store.getStepValues( 'audience' ),
        [],
    );

    const updateField = ( key, value ) => {
        actions.updateStep( 'audience', { [ key ]: value } );
    };

    return (
        <div className="visibloc-onboarding-step visibloc-onboarding-step--audience">
            <TextareaControl
                label={__( 'Segment principal', 'visi-bloc-jlg' )}
                value={primary}
                onChange={( newValue ) => updateField( 'primary', newValue )}
                rows={3}
            />
            <TextareaControl
                label={__( 'Segment secondaire', 'visi-bloc-jlg' )}
                value={secondary}
                onChange={( newValue ) => updateField( 'secondary', newValue )}
                rows={3}
            />
            <TextControl
                label={__( 'Rôles ou profils cibles', 'visi-bloc-jlg' )}
                value={roles}
                onChange={( newValue ) => updateField( 'roles', newValue )}
                help={__( 'Indiquez les rôles WordPress ou personas CRM concernés.', 'visi-bloc-jlg' )}
            />
            <TextareaControl
                label={__( 'Notes ou exclusions', 'visi-bloc-jlg' )}
                value={notes}
                onChange={( newValue ) => updateField( 'notes', newValue )}
                rows={4}
            />
        </div>
    );
}
