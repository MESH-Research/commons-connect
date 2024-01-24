import apiFetch from '@wordpress/api-fetch';

import {
	useState,
	useEffect,
} from '@wordpress/element';

import {
	Button
} from '@wordpress/components';


type SiteOptions = {
	cc_server_url: string;
};

export const SettingsPanel = () => {
	const [ siteOptions, updateSiteOptions ] = useState( {
		cc_server_url: '',
	} );

    const {
        cc_server_url,
    } = siteOptions as SiteOptions;

	useEffect( () => {
		refreshSiteOptions();
	}, [] );

	const refreshSiteOptions = () => {
		apiFetch( {
			path: '/cc-client/v1/options',
		} ).then( ( options ) => {
			updateSiteOptions( options as SiteOptions);
		} );
	};

	const doSave = () => {
		apiFetch( {
			path: '/cc-client/v1/options',
			method: 'POST',
			data: siteOptions,
		} ).then( ( response ) => { console.log( response ) } );
	};

	
	return (
		<div>
			<h1>Settings Panel</h1>
			<div id="settings-form">
				<label>CommonsConnect Server URL:</label>
				<input 
					type = "text"
					value = { cc_server_url }
					name = "cc_server_url"
					onChange = { ( event ) => { updateSiteOptions( { ...siteOptions, 'cc_server_url': event.target.value } ) } }
				/>
			</div>
				<Button
					variant = "primary"
					onClick = { doSave }
				>
				Save
			</Button>
		</div>
	);
}