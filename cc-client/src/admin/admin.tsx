import { createRoot } from '@wordpress/element';

import { SettingsPanel } from './settings-panel';

import './admin.scss';

window.addEventListener( 'DOMContentLoaded', () => {
	const rootElement = document.getElementById( 'cc-client-admin' ) as HTMLElement;
	if ( rootElement ) {
		const root = createRoot( rootElement );
		root.render( <SettingsPanel /> );
	}
} );
