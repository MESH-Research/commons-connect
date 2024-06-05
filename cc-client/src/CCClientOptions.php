<?php
/**
 * Options for the CC Client plugin.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

class CCClientOptions {
	public function __construct(
		public string      $cc_search_key = '',
		public string      $cc_search_endpoint = '',
		public string      $cc_search_admin_key = '',
		public bool | null $incremental_provisioning_enabled = null
	) {
		$this->loadOptions(false);	
	}

	public function loadOptions( $overwrite = true ) : void {
		$saved_options = get_option( 'cc_client_options' );
		if ( ! is_array( $saved_options ) ) {
			$saved_options = [];
		}
		foreach ( $saved_options as $key => $value ) {
			if ( $overwrite || ! isset( $this->$key ) ) {
				$this->$key = $value;
			}
		}
		// Environment variables override saved options
		if ( getenv( 'CC_SEARCH_KEY' ) ) {
			$this->cc_search_key = getenv( 'CC_SEARCH_KEY' );
		}
		if ( getenv( 'CC_SEARCH_ENDPOINT' ) ) {
			$this->cc_search_endpoint = getenv( 'CC_SEARCH_ENDPOINT' );
		}
		if ( getenv( 'CC_SEARCH_ADMIN_KEY' ) ) {
			$this->cc_search_admin_key = getenv( 'CC_SEARCH_ADMIN_KEY' );
		}
		if ( getenv( 'CC_INCREMENTAL_PROVISIONING_ENABLED' ) ) {
			$this->incremental_provisioning_enabled = (bool) getenv( 'CC_INCREMENTAL_PROVISIONING_ENABLED' );
		}
	}

	public function saveOptions() : void {
		$options = [];
		foreach ( get_object_vars( $this ) as $key => $value ) {
			if ( ! empty( $value ) ) {
				$options[ $key ] = $value;
			}
		}
		update_option( 'cc_client_options', $options );
	}
}