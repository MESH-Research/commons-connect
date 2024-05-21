<?php
/**
 * BBPress replies and topics that can be provisioned to the search service.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient\Search\Provisioning;

class ProvisionableDiscussion extends ProvisionablePost {
	public static function getAll( $post_types = [ 'topic', 'reply' ] ) : array {
		$posts = get_posts( [
			'post_type' => $post_types,
			'post_status' => 'publish',
			'posts_per_page' => -1,
		] );
		
		$provisionable_posts = [];
		foreach ( $posts as $post ) {
			$discussion = new ProvisionableDiscussion( $post );
			if ( $discussion->is_public() ) {
				$provisionable_posts[] = $discussion;
			}
		}

		return $provisionable_posts;
	}

	public function is_public(): bool {
		if ( $this->post->post_type === 'topic' ) {
			$topic_post = $this->post;
		} elseif ( $this->post->post_type === 'reply' ) {
			$topic_post = get_post( $this->post->post_parent );
		} else {
			//We shouldn't ever get here but...
			return false;
		}
		$forum_post = get_post( $topic_post->post_parent );
		if ( ! $forum_post ) {
			return false;
		}
		return $forum_post->post_status === 'publish';
	}

	public static function isAvailable(): bool {
		return class_exists( 'bbPress' );
	}
}