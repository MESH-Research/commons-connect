<?php
/**
 * Tests for the ProvisionableProfile class.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\Provisioning\ProvisionableProfile;

class ProvisionableProfileTest extends \WP_UnitTestCase {
	public function testToDocument() {
		$user_id = $this->factory->user->create();
		$user = get_user_by( 'ID', $user_id );

		$provisionable_user = new ProvisionableProfile( $user );
		$document = $provisionable_user->toDocument();

		$this->assertEquals( $user->display_name, $document->title );
		$this->assertEquals( '', $document->description );
		$this->assertEquals( $user->display_name, $document->owner->name );
	}
}