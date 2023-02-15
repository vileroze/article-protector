<?php

/* 
    Plugin Name: Article Protector
    Description: Protects your articles through a paywall and set the number of premium articles per month
    Version: 1.0
    Author: Vileroze
    Author URI: https://youtube.com
*/


/**
 * Activation hook
 */
function article_protector_activate() { 
	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules(); 
}
register_activation_hook( __FILE__, 'article_protector_activate' );


/**
 * Deactivation hook.
 */
function article_protector_deactivate() {
	// Clear the permalinks to remove our post type's rules from the database.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'article_protector_deactivate' );


require_once __DIR__ . '/public/ap-initialization.php';

require_once __DIR__ . '/admin/ap-settings-page.php';

require_once __DIR__ . '/public/ap-post-display-content.php';

require_once __DIR__ . '/admin/ap-metaboxes.php';

require_once __DIR__ . '/public/ap-reset-article-quota.php';