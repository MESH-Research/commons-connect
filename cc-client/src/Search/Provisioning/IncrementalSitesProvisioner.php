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
		add_action( 'make_spam_blog', [ $this, 'provisionSiteSpammed' ], 10, 1 );
		add_action( 'make_ham_blog', [ $this, 'provisionSiteUnspammed' ], 10, 1 );
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
		error_log( sprintf( '[CC-Client] Site provisioning ADD - Site ID: %d, Domain: %s', $site->blog_id, $site->domain ) );
		$provisionable_site = new ProvisionableSite( $site );
		$indexed_document = $this->search_api->index( $provisionable_site->toDocument() );
		if ( $indexed_document ) {
			$provisionable_site->setSearchID( $indexed_document->_id );
			error_log( sprintf( '[CC-Client] Site provisioning ADD SUCCESS - Site ID: %d, Search ID: %s', $site->blog_id, $indexed_document->_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Site provisioning ADD FAILED - Site ID: %d', $site->blog_id ) );
		}
	}

	public function provisionUpdatedSite( \WP_Site $site ) {
		if ( ! $this->enabled ) {
			return;
		}
		error_log( sprintf( '[CC-Client] Site provisioning UPDATE - Site ID: %d, Domain: %s', $site->blog_id, $site->domain ) );
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();
		$indexed_document = $this->search_api->index_or_update( $provisionable_site->toDocument() );
		if ( $indexed_document ) {
			$provisionable_site->setSearchID( $indexed_document->_id );
			error_log( sprintf( '[CC-Client] Site provisioning UPDATE SUCCESS - Site ID: %d, Search ID: %s', $site->blog_id, $indexed_document->_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Site provisioning UPDATE FAILED - Site ID: %d', $site->blog_id ) );
		}
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
			error_log( sprintf( '[CC-Client] Site provisioning DELETE skipped (no search ID) - Site ID: %d, Domain: %s', $site->blog_id, $site->domain ) );
			return;
		}
		error_log( sprintf( '[CC-Client] Site provisioning DELETE - Site ID: %d, Search ID: %s, Domain: %s', $site->blog_id, $search_id, $site->domain ) );
		$success = $this->search_api->delete( $search_id );
		if ( $success ) {
			error_log( sprintf( '[CC-Client] Site provisioning DELETE SUCCESS - Site ID: %d, Search ID: %s', $site->blog_id, $search_id ) );
		} else {
			error_log( sprintf( '[CC-Client] Site provisioning DELETE FAILED - Site ID: %d, Search ID: %s', $site->blog_id, $search_id ) );
		}
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
		error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY CHANGE - Site ID: %d, Domain: %s, Old: %s, New: %s', $site->blog_id, $site->domain, $old_value, $new_value ) );
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();

		if ( $new_visibility > 0 ) {
			$indexed_document = $this->search_api->index_or_update( $provisionable_site->toDocument() );
			if ( $indexed_document ) {
				$provisionable_site->setSearchID( $indexed_document->_id );
				error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY UPDATE SUCCESS (now public) - Site ID: %d, Search ID: %s', $site->blog_id, $indexed_document->_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY UPDATE FAILED - Site ID: %d', $site->blog_id ) );
			}
		} elseif ( ! empty( $provisionable_site->search_id ) ) {
			error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY DELETE (now private) - Site ID: %d, Search ID: %s', $site->blog_id, $provisionable_site->search_id ) );
			$success = $this->search_api->delete( $provisionable_site->search_id );
			if ( $success ) {
				$provisionable_site->setSearchID( '' );
				error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY DELETE SUCCESS - Site ID: %d', $site->blog_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Site provisioning VISIBILITY DELETE FAILED - Site ID: %d', $site->blog_id ) );
			}
		}
	}

	/**
	 * Provision a site when it is marked as spam.
	 *
	 * @param int $site_id The ID of the site to provision.
	 */
	public function provisionSiteSpammed( int $site_id ) {
		$site = get_site( $site_id );
		if ( ! $site ) {
			error_log( sprintf( '[CC-Client] Site provisioning SPAM DELETE FAILED - Site not found, Site ID: %d', $site_id ) );
			return;
		}
		error_log( sprintf( '[CC-Client] Site provisioning SPAM DELETE - Site ID: %d, Domain: %s', $site_id, $site->domain ) );
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();

		if ( ! empty( $provisionable_site->search_id ) ) {
			$success = $this->search_api->delete( $provisionable_site->search_id );
			if ( $success ) {
				$provisionable_site->setSearchID( '' );
				error_log( sprintf( '[CC-Client] Site provisioning SPAM DELETE SUCCESS - Site ID: %d, Search ID: %s', $site_id, $provisionable_site->search_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Site provisioning SPAM DELETE FAILED - Site ID: %d, Search ID: %s', $site_id, $provisionable_site->search_id ) );
			}
		} else {
			error_log( sprintf( '[CC-Client] Site provisioning SPAM DELETE skipped (no search ID) - Site ID: %d', $site_id ) );
		}
	}

	/**
	 * Provision a site when it is marked as unspam.
	 *
	 * @param int $site_id The ID of the site to provision.
	 */
	public function provisionSiteUnspammed( int $site_id ) {
		$site = get_site( $site_id );
		if ( ! $site ) {
			error_log( sprintf( '[CC-Client] Site provisioning UNSPAM RE-ADD FAILED - Site not found, Site ID: %d', $site_id ) );
			return;
		}
		error_log( sprintf( '[CC-Client] Site provisioning UNSPAM RE-ADD - Site ID: %d, Domain: %s', $site_id, $site->domain ) );
		$provisionable_site = new ProvisionableSite( $site );
		$provisionable_site->getSearchID();

		if ( ! empty( $provisionable_site->search_id ) ) {
			$indexed_document = $this->search_api->index_or_update( $provisionable_site->toDocument() );
			if ( $indexed_document ) {
				$provisionable_site->setSearchID( $indexed_document->_id );
				error_log( sprintf( '[CC-Client] Site provisioning UNSPAM RE-ADD SUCCESS - Site ID: %d, Search ID: %s', $site_id, $indexed_document->_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Site provisioning UNSPAM RE-ADD FAILED - Site ID: %d', $site_id ) );
			}
		} else {
			error_log( sprintf( '[CC-Client] Site provisioning UNSPAM RE-ADD - Creating new entry for Site ID: %d', $site_id ) );
			$indexed_document = $this->search_api->index( $provisionable_site->toDocument() );
			if ( $indexed_document ) {
				$provisionable_site->setSearchID( $indexed_document->_id );
				error_log( sprintf( '[CC-Client] Site provisioning UNSPAM NEW ENTRY SUCCESS - Site ID: %d, Search ID: %s', $site_id, $indexed_document->_id ) );
			} else {
				error_log( sprintf( '[CC-Client] Site provisioning UNSPAM NEW ENTRY FAILED - Site ID: %d', $site_id ) );
			}
		}
	}
}
