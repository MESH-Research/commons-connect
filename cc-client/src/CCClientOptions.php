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