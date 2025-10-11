import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useOnboardingWizard } from '../store';

export default function ContentStep() {
    const { promise = '', callToAction = '', fallback = '', actions } = useOnboardingWizard(
        ( store ) => store.getStepValues( 'content' ),
        [],
    );

    const updateField = ( key, value ) => {
        actions.updateStep( 'content', { [ key ]: value } );
    };

    return (
        <div className="visibloc-onboarding-step visibloc-onboarding-step--content">
            <TextareaControl
                label={__( 'Promesse / message clé', 'visi-bloc-jlg' )}
                value={promise}
                onChange={( newValue ) => updateField( 'promise', newValue )}
                rows={3}
            />
            <TextControl
                label={__( 'Appel à l’action', 'visi-bloc-jlg' )}
                value={callToAction}
                onChange={( newValue ) => updateField( 'callToAction', newValue )}
            />
            <TextareaControl
                label={__( 'Fallback ou alternative', 'visi-bloc-jlg' )}
                value={fallback}
                onChange={( newValue ) => updateField( 'fallback', newValue )}
                rows={3}
                help={__( 'Message affiché si la condition échoue (optionnel).', 'visi-bloc-jlg' )}
            />
        </div>
    );
}
