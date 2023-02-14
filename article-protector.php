<?php

/* 
    Plugin Name: Article Protector
    Description: Protect your articles through a paywall 
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


/**
 * Register styles and scripts.
 */

// register style on initialization
add_action('init', 'article_protector_init');
function article_protector_init(){

    //register styles
	wp_register_style( 'custom-plugin-style', plugins_url('/public/css/article-protector-public.css', __FILE__), false, '1.0.0', 'all');

    /**
     * Adding logout button at the end of main menu if user logged in
     */

    if( is_user_logged_in() ){
        add_filter('wp_nav_menu_items','article_protector_custom_menu_items', 10, 2);
        function article_protector_custom_menu_items( $items, $args ) 
        {
            if( $args->theme_location == 'menu-1' ) // only for primary menu
            {
                $items_array = array();
                while ( false !== ( $item_pos = strpos ( $items, '<li', 3 ) ) )
                {
                    $items_array[] = substr($items, 0, $item_pos);
                    $items = substr($items, $item_pos);
                }
                $items_array[] = $items;
                array_splice($items_array, sizeof($items_array), 0, '<li><a class="logout-btn" href='.wp_logout_url( home_url() ).'>LOGOUT</a></li>'); // insert custom item after 2nd one

                $items = implode('', $items_array);
            }
            return $items;
        }
    }
}

// use the style above
add_action('wp_enqueue_scripts', 'enqueue_style');
function enqueue_style(){
	wp_enqueue_style( 'custom-plugin-style' );
}


/**
 * Admin settings page
 */

 add_action( 'admin_menu', 'article_protector_settings_link' );
 function article_protector_settings_link(){
    add_options_page( 'Article Protector Settings', 'Article Protector', 'manage_options', 'article-protector-settings-page', 'article_protector_HTML' );
 }
 
 add_action( 'admin_init', 'ap_settings' );
 function ap_settings(){
     add_settings_section( 'ap_quota_section', null, null, 'article-protector-settings-page' );
     add_settings_field( 'ap_month_quota', 'Number of premium articles per user per month', 'quotaHTML', 'article-protector-settings-page', 'ap_quota_section' );
     register_setting( 'article_protector_plugin', 'ap_month_quota', ['sanitize_callback' => 'sanitize_text_field', 'default' => '3'] );
 }
 
 function quotaHTML(){ 
     $curr_article_quota = get_option( 'ap_month_quota', '3' ) ;
     echo '<input type="text" placeholder="Article quota per month" name="ap_month_quota" value="'.$curr_article_quota.'">';
 }
 
 function article_protector_HTML() { ?>
     <div>
         <h1>Article Protector Settings</h1>
         <form action="options.php" method="POST">
             <?php 
                 settings_fields( 'article_protector_plugin' );
                 do_settings_sections( 'article-protector-settings-page' );
                 submit_button();
             ?>
         </form>
     </div>
 <?php }


/**
 * Frontend user login form
 */

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
            $wp_err =  $user->get_error_message();

            return $wp_err;
        }

        //redirect back to current page
        global $wp;
        wp_safe_redirect(home_url( $wp->request ));
    }

    return ob_get_clean();

}
add_filter('template_redirect', 'article_protector_login_form');
// add_action( 'init', 'custom_login_form' );


//show variation of article according to if user logged in or not
add_filter( 'the_content', 'endContetDisplay' );
/**
 * If the user is not logged in and the post is premium, display the first 500 characters of the post
 * and a login form. 
 * If the user is logged in and the post is premium, display the entire post and a message asking the
 * user to leave a comment. 
 * If the user is logged in and the post is not premium, display the entire post.
 * 
 * @param content The post content.
 * 
 * @return content The post content.
 */
function endContetDisplay( $content ){

    $content_substr = substr( $content, 0, 499 );
    $content_substring_with_overlay = 
    '   
        <div class="substr-content">
            <div class="substr-overlay"></div>
            '.$content_substr.'
        </div>';

    //check if no user has logged in and post is premium
    if ( !is_user_logged_in() && ( isset( get_post_meta( get_the_ID(), 'paywalled')[0] ) == "1" ) ) {
        //return only first 100 words of content
        $result = '';
        $result .= $content_substring_with_overlay;
        $result .= article_protector_login_form();

        return $result;
    }

    //get current logged in user details
    $curr_user = wp_get_current_user();
    $curr_userID = $curr_user->ID;
    $curr_username = $curr_user->user_login;

    //get all articles for the user
    $visited_articles =  get_user_meta( $curr_userID, 'quota', true ) == "" ? serialize([]) : get_user_meta( $curr_userID, 'quota', true );
    $unserialize_visited_articles = unserialize($visited_articles);

    $quota_reached_msg = '<h5 class="article-protector-msg">You have reached your quota for this month, please comback next month !</h5>';
    //check if user reached quota
    if(sizeof($unserialize_visited_articles) == (int)get_option( 'ap_month_quota', '3' )){
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

    //displays the entire content along with a message to leave a comment
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
        </form>';
    
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

//check if laready scheduled, if not, schedule 
if ( ! wp_next_scheduled( 'update_user_meta' ) ) {
    wp_schedule_event( time(), 'everytwoseconds', 'update_user_meta' );
}
add_action( 'update_user_meta', 'article_protector_reset_user_quota' );

/**
 * If today is the first day of the month, then for each user with a 'quota' meta_key, set the value of
 * that meta_key to an empty string.
 */
function article_protector_reset_user_quota() {
    //check if today is starting of month
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



