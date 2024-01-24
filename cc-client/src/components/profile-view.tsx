import { useState, useEffect } from '@wordpress/element';

interface ProfileData {
	username?: string;
	display_name?: string;
	note?: string;
	url?: string;
}

export const ProfileView = ( { cc_server_url, cc_server_rest_base, username } ) => {

	const [ profileData, updateProfileData ] = useState( {} as ProfileData );

	const refreshProfileData = () => {
		const endpointURL = `${ cc_server_url }${ cc_server_rest_base }${ username }/profile`;

		fetch( endpointURL ).then( ( response ) => {
			response.json().then( ( data ) => {
				updateProfileData( data );
			} );
		} );
	};

	useEffect( () => {
		refreshProfileData();
	}, [] );

	return (
		<div>
			<h1>{ profileData.display_name }</h1>
			<div>
				<label>Username: </label>
				<span>{ profileData.username }</span>
			</div>
			<div>
				<label>Description: </label>
				<span dangerouslySetInnerHTML={{__html: profileData.note }}></span>
			</div>
			<div>
				<label>URL: </label>
				<span><a href={profileData.url}>{ profileData.url }</a></span>
			</div>
		</div>
	);
}