<?php
/**
 * Tests for the ProvisionableUser class.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\Provisioning\ProvisionableUser;

class ProvisionableUserTest extends \WP_UnitTestCase {
	public function testToDocument() {
		$user_id = $this->factory->user->create();
		$user = get_user_by( 'ID', $user_id );

		$provisionable_user = new ProvisionableUser( $user );
		$document = $provisionable_user->toDocument();

		$this->assertEquals( $user->display_name, $document->title );
		$this->assertEquals( '', $document->description );
		$this->assertEquals( $user->display_name, $document->owner->name );
	}
}