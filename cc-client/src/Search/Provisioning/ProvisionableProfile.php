<?php
/**
 * A WordPress profile that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

use MeshResearch\CCClient\Search\SearchDocument;
use MeshResearch\CCClient\Search\SearchPerson;

class ProvisionableProfile implements ProvisionableInterface {
	public function __construct(
		public \WP_User $user,
		public string $search_id = ''
	) {
		$this->getSearchID();
	}

	public function toDocument(): SearchDocument {
		$network_node = get_current_network_node();

		$profile_data = [];
		if ( class_exists( '\BP_XProfile_ProfileData' ) ) {
			$profile_data = \BP_XProfile_ProfileData::get_all_for_user( $this->user->ID );
		}

		$other_urls = get_other_profile_urls( $this->user );

		$document = new SearchDocument(
			_internal_id: strval($this->user->ID),
			title: $this->user->display_name,
			description: wp_strip_all_tags( $profile_data['About']['field_data'] ?? '' ),
			owner: new SearchPerson(
				name: $this->user->display_name,
				username: $this->user->user_login,
				url: get_profile_url( $this->user ),
				role: 'user',
				network_node: $network_node
			),
			contributors: [],
			primary_url: get_profile_url( $this->user ),
			other_urls: $other_urls,
			thumbnail_url: get_avatar_url( $this->user->ID ),
			content: '',
			publication_date: $this->user->user_registered ? new \DateTime( $this->user->user_registered ) : null,
			modified_date: null,
			content_type: 'profile',
			network_node: $network_node
		);

		if ( $this->search_id ) {
			$document->_id = $this->search_id;
		}
		
		return $document;
	}

	public function getSearchID(): string {
		$search_id = get_user_meta( $this->user->ID, 'cc_search_id', true );
		if ( $search_id === false ) {
			$search_id = '';
		}
		$this->search_id = $search_id;
		return $search_id;
	}

	public function setSearchID( string $search_id ): void {
		$success = update_user_meta( $this->user->ID, 'cc_search_id', $search_id );
		$this->search_id = $search_id;
	}

	public static function getAll( bool $reset = false, bool $show_progress = false ): array {
		$users = get_users( [
			'number'  => -1,
			'blog_id' => 0,
		] );

		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Provisioning ' . count( $users ) . ' users...' );
		}

		$provisionable_profiles = [];
		$user_counter = 0;
		foreach ( $users as $user ) {
			$user_counter++;
			if ( $show_progress && class_exists( 'WP_CLI' ) && $user_counter % (count( $users ) / 10) === 0 ) {
				echo '.';
			}
			$provisionable_profile = new ProvisionableProfile( $user );
			if ( $reset ) {
				$provisionable_profile->setSearchID( '' );
			}
			$provisionable_profiles[] = $provisionable_profile;
		}
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			echo "\n";
		}

		return $provisionable_profiles;
	}

	public static function getAllAsDocuments( bool $reset = false, bool $show_progress = false ): array {
		$provisionable_profiles = self::getAll( $reset, $show_progress );
		$documents = [];
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			\WP_CLI::line( 'Converting ' . count( $provisionable_profiles ) . ' profiles to documents...' );
		}
		$document_counter = 0;
		foreach ( $provisionable_profiles as $provisionable_profile ) {
			$document_counter++;
			if ( $show_progress && class_exists( 'WP_CLI' ) && $document_counter % (count( $provisionable_profiles ) / 10) === 0 ) {
				echo '.';
			}
			$documents[] = $provisionable_profile->toDocument();
		}
		if ( $show_progress && class_exists( 'WP_CLI' ) ) {
			echo "\n";
		}
		return $documents;
	}

	public static function isAvailable(): bool {
		return true;
	}
}