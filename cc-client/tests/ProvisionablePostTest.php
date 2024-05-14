<?php
/**
 * Tests for the ProvisionablePost class.
 */

namespace MeshResearch\CCClient\Tests;

use MeshResearch\CCClient\Search\Provisioning\ProvisionablePost;

class ProvisionablePostTest extends \WP_UnitTestCase
{
	/**
	 * Test the toDocument method
	 */
	public function test_toDocument(): void {
		$post = $this->factory->post->create_and_get();
		$post_author = $this->factory->user->create_and_get();
		\wp_update_post([
			'ID' => $post->ID,
			'post_author' => $post_author->ID
		]);
		$post = \get_post($post->ID);
		$provisionable_post = new ProvisionablePost($post);
		$document = $provisionable_post->toDocument();

		$a = $document->toJSON();

		$this->assertEquals($post->post_title, $document->title);
	}
}