<?php

/* 
    Plugin Name: Article Protector
    Description: Protect your articles through a paywall 
    Version: 1.0
    Author: Vileroze
    Author URI: https://youtube.com
*/


/**
 * Activate the plugin.
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


//show only 100 words of premium article
add_filter( 'the_content', 'addToEnd' );
function addToEnd( $content ){
    $result = substr( $content, 0, 499 ) . "To continue reading please login";

    $result .= 
    '
        <form action="" method="post">
            <label for="uname"><b>Username</b></label>
            <input type="text" placeholder="Enter Username" name="uname" required>
        
            <label for="psw"><b>Password</b></label>
            <input type="password" placeholder="Enter Password" name="psw" required>

            <input type="submit" name="uLogin" value="Submit">
        </form>
    '.plugins_url('/public/css/article-protector.css', __FILE__);

    return $result;
}