<?php
/**
 * Incremental provisioner for sites.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchAPI;

class IncrementalSitesProvisioner implements IncrementalProvisionerInterface {
	public function __construct(
		private SearchAPI $search_api,
		private bool $enabled = true
	) {
		$this->registerHooks();
	}
	
	public function registerHooks(): void {
		add_action( 'wp_initialize_site', [ $this, 'provisionNewSite' ], 50, 1 );
		add_action( 'wp_update_site', [ $this, 'provisionUpdatedSite' ] );
		add_action( 'update_option_blogname', [ $this, 'provisionUpdatedSiteOnOptionChange' ], 10, 3 );
		add_action( 'wp_delete_site', [ $this, 'provisionDeletedSite' ] );
		add_action( 'update_option_blog_public', [ $this, 'provisionSiteVisibilityChange' ], 10, 3 );
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function enable(): void {
		$this->enabled = true;
	}

	public function disable(): void {
		$this->enabled = false;
	}
	
	public function provisionNewSite( \WP_Site $site ) {
		if ( ! $this->enabled ) {
			return;
		}
		$provisionable_site = new ProvisionableSite( $site );
		$indexed_document = $this->search_api->index( $provisionable_site->toDocument() );
		$provisionable_site->setSearchID( $indexed_document->_id );
	}
	
	public function provisionUpdatedSite( \WP_Site $site ) {
		if ( ! $this->enabled ) {
			return;
		}
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();
		$indexed_document = $this->search_api->index_or_update( $provisionable_site->toDocument() );
		$provisionable_site->setSearchID( $indexed_document->_id );
	}
	
	public function provisionUpdatedSiteOnOptionChange( $old_value, $new_value, $option ) {
		if ( ! $this->enabled ) {
			return;
		}
		$site = get_blog_details( get_current_blog_id() );
		$this->provisionUpdatedSite( $site );
	}
	
	public function provisionDeletedSite( \WP_Site $site ) {
		if ( ! $this->enabled ) {
			return;
		}
		$provisionable_site = new ProvisionableSite( $site );
		$search_id = $provisionable_site->getSearchID();
		if ( empty( $search_id ) ) {
			return;
		}
		$this->search_api->delete( $search_id );
	}
	
	/**
	 * Update search index when site visibility changes.
	 * 
	 * When this is triggered, the current site should be the one whose visibility is changing. This is due to how
	 * the 'update_blog_option' function is implemented (by switching to the site, then calling 'update_option').
	 * 
	 * Visibility is stored as a string in the 'blog_public' option, encoded on KC as:
	 *   - '1' public and friendly to search engines
	 *   - '0' not public and not friendly to search engines
	 *   - '-1' visibile only to registered users of that network
	 *   - '-2' visible only to registered users of that site
	 *   - '-3' visible only to administrators of that site
	 */
	public function provisionSiteVisibilityChange( $old_value, $new_value, $option ) {
		if ( ! $this->enabled ) {
			return;
		}
		$new_visibility = intval( $new_value );

		$site = get_site();
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();
	
		if ( $new_visibility > 0 ) {
			$indexed_document = $this->search_api->index_or_update( $provisionable_site->toDocument() );
			$provisionable_site->setSearchID( $indexed_document->_id );
		} elseif ( ! empty( $provisionable_site->search_id ) ) {
			$this->search_api->delete( $provisionable_site->search_id );
			$provisionable_site->setSearchID( '' );
		}
	}
}