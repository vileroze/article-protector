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

    $content_substr = substr( $content, 0, 499 );
    $content_substring_with_overlay = 
    '   
        <div class="substr-content">
            <div class="substr-overlay"></div>
            '.$content_substr.'
        </div>
    ';

    //check if no user has logged in and post is premium
    if ( !is_user_logged_in() && ( isset(get_post_meta( get_the_ID(), 'paywalled')[0]) == "1" ) ) {
        
        $result = '';

        $result .= $content_substring_with_overlay;

        $result .= article_protector_login_form();

        return $result;
    }

    //get current logged in user details
    $curr_user = wp_get_current_user();
    $curr_userID = $curr_user->ID;
    $curr_username = $curr_user->user_login;

    //check if user has reched their quota for this month
    $visited_articles =  get_user_meta( $curr_userID, 'quota', true ) == "" ? serialize([]) : get_user_meta( $curr_userID, 'quota', true );
    
    $unserialize_visited_articles = unserialize($visited_articles);
    $quota_reached_msg = '<h3 class="article-protector-msg">You have reached your quota for this month, please comback next month !</h3>';
    
    if(sizeof($unserialize_visited_articles) == 3){
        return $content_substring_with_overlay.$quota_reached_msg;
    }
    
    //message asking user to leave a comment
    $get_comment = "<strong>Did you enjoy the article ".$curr_username."? Leave your thoughts below in the comments!</strong>";

    //update quota of user by 1 if new article
    if (!in_array( get_the_ID(), $unserialize_visited_articles )){
        //add the current post id to the array
        array_push( $unserialize_visited_articles, get_the_ID() );
        //serialize the array
        $serialized_visited_array = serialize($unserialize_visited_articles);
        //update user quota
        update_user_meta( $curr_userID, 'quota', $serialized_visited_array );
    }

    //returns the entire content along with a message to leave a comment
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


/**
 * Resetting post user  quota every month.
 */


//creating custom scheduler
add_filter( 'cron_schedules', 'article_protector_add_cron_interval' );
function article_protector_add_cron_interval( $schedules ) {
    $schedules['everytwoseconds'] = 
    [
        'interval'  => 2, // time in seconds
        'display'   => 'Every Two Seconds'
    ];
    return $schedules;
}


if ( ! wp_next_scheduled( 'update_user_meta' ) ) {
    wp_schedule_event( time(), 'everytwoseconds', 'update_user_meta' );
}
add_action( 'update_user_meta', 'article_protector_reset_user_quota' );


function article_protector_reset_user_quota() {
    if(date('d') == "01" || date('d') == "1"){
        //select users with 'quota' meta_key
        $user_query = new WP_User_Query( ['meta_query' => [ 'meta_key' => 'quota' ]]);

        // Get the results
        $users = $user_query->get_results();

        // Check for results
        if (!empty($users)) {
            foreach ($users as $user){
            
                // get all the user's data
                $user_info = get_userdata($user->ID);
                //update quota for all users
                update_user_meta( $user_info->ID, 'quota', '' );
            }
        }
    }
}


