import { createRoot } from '@wordpress/element';
import { ProfileView } from '../../components/profile-view';

window.addEventListener( 'DOMContentLoaded', () => {
	const ProfileViews = document.querySelectorAll( '.wp-block-cc-client-profile' );
	Array.from( ProfileViews ).forEach( view => {
		const attribuesJSON = (view as HTMLElement).dataset.attributes;
		const attributes = JSON.parse( attribuesJSON ? attribuesJSON : '{}' );
		const root = createRoot( view );
		root.render( 
			<ProfileView
				{ ...attributes }
			/>
		);
	} );
} );