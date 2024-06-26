<?php
/**
 * Functions for working with provisionables.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

function get_provisionable( string $type, string $wpid ): ProvisionableProfile | ProvisionableGroup | ProvisionableSite | ProvisionablePost {
	switch ( $type ) {
		case 'profile':
			$item = get_user_by( 'ID', $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid user ID' );
			}
			return new ProvisionableProfile( $item );
		case 'group':
			$item = new \BP_Groups_Group( $wpid );
			if ( ! $item->id ) {
				throw new \Exception( 'Invalid group ID' );
			}
			return new ProvisionableGroup( $item );
		case 'site':
			$item = get_blog_details( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid site ID' );
			}
			return new ProvisionableSite( $item );
		case 'post':
			$item = get_post( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid post ID' );
			}
			return new ProvisionablePost( $item );
		case 'discussion':
			$item = get_post( $wpid );
			if ( ! $item ) {
				throw new \Exception( 'Invalid post ID' );
			}
			return new ProvisionablePost( $item );
		default:
			throw new \Exception( 'Invalid provisionable type: ' . $type );
	}
}

function get_available_provisionables(): array {
	$provisioners = [];
	if ( ProvisionablePost::isAvailable() ) {
		$provisioners[] = 'post';
		$provisioners[] = 'discussion';
	}
	if ( ProvisionableProfile::isAvailable() ) {
		$provisioners[] = 'profile';
	}
	if ( ProvisionableGroup::isAvailable() ) {
		$provisioners[] = 'group';
	}
	if ( ProvisionableSite::isAvailable() ) {
		$provisioners[] = 'site';
	}
	return $provisioners;
}