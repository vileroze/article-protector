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



// register style on initialization
add_action('init', 'register_custom_style');
function register_custom_style(){
	wp_register_style( 'custom-plugin-style', plugins_url('/public/css/article-protector-public.css', __FILE__), false, '1.0.0', 'all');
}

// use the style above
add_action('wp_enqueue_scripts', 'enqueue_style');
function enqueue_style(){
	wp_enqueue_style( 'custom-plugin-style' );
}


function article_protector_login_form(){

    ob_start(); 
    ?>

        <div class="premium-post-div">
            <h5>To continue reading this article please login</h5>
            <form action="" method="post">
                <label for="username"><b>Username</b></label>
                <input type="text" placeholder="Enter Username" name="username" required>
        
                <label for="psw"><b>Password</b></label>
                <input type="password" placeholder="Enter Password" name="psw" required>

                <input type="submit" name="userlogin" value="LOGIN">
            </form>
        </div>

    <?php 

    if( isset($_POST['userlogin']) ){
        $user_name = $_POST['username'];
        $user_pass = $_POST['psw'];

        $creds = [
            'user_login'    => $user_name,
            'user_password' => $user_pass,
            'remember'      => false
        ];
    
        $user = wp_signon( $creds, false );
    
        if ( is_wp_error( $user ) ) {
            echo $user->get_error_message();
        }
    }

    return ob_get_clean();

}
add_filter('template_redirect', 'article_protector_login_form');
// add_action( 'init', 'custom_login_form' );


//show variation of article according to if user logged in or not
add_filter( 'the_content', 'endContetDisplay' );
function endContetDisplay( $content ){

    if ( !is_user_logged_in() && ( isset(get_post_meta( get_the_ID(), 'paywalled')[0]) == "1" ) ) {
        $result = '';
        $content_substr = substr( $content, 0, 499 );

        $result .=
        '   
            <div class="substr-content">
                <div class="substr-overlay"></div>
                '.$content_substr.'
            </div>
        ';

        $result .= article_protector_login_form();

        return $result;
    }

    $curr_user = wp_get_current_user();
    $curr_username = $curr_user->user_login;
    $get_comment = "<strong>Did you enjoy the article ".$curr_username."? Leave your thoughts below in the comments!</strong>";

    return $content.$get_comment; 
}


/**
 * Adding metbox to posts.
 */

//adding the article protector metabox
add_action( 'add_meta_boxes', 'article_protector_metabox' );
//article protector metabox field
function article_protector_metabox(){
    add_meta_box("article_protector_metabox", "Article protector", "article_protector_metabox_field", "post", "side");
}

//all article protector metafields
function article_protector_metabox_field(){

    //check if post premium
    $is_premium_article = isset(get_post_meta( get_the_ID(), 'paywalled' )[0]) ? 'checked' : '';

    echo '
        <form id="articleProtectorForm" action="" method="POST">
            <input type="checkbox" name="premium_input" id="premium_input" value="1" '.$is_premium_article.'> Premium article
        </form>
    ';
    
}

//saving the value of the article protector metabox field
add_action('save_post', 'save_article_protector_meta');
function save_article_protector_meta(){
    update_post_meta(get_the_ID(), 'paywalled', isset($_POST["premium_input"]) ? 1 : 0 );
}
