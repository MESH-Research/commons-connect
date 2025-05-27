<?php
/**
 * Main blocks file.
 *
 * @package MeshResearch\CCClient
 */

namespace MeshResearch\CCClient;

function register_search_block()
{
    register_block_type(CC_CLIENT_BASE_DIR . "/build/search");
}
add_action("init", __NAMESPACE__ . '\register_search_block');
