<?php


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
            'user_login'    => __( $user_name, 'hitmag' ),
            'user_password' => $user_pass,
            'remember'      => false
        ];
        
        //sign in the user and store the current user 
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

    //get time left
    $dtFirst = new DateTime('tomorrow'); //first day of next month
    $dtToday = new DateTime('today');
    $time_until_task_will_run = $dtToday->diff($dtFirst);
    $days_remaining = $time_until_task_will_run->format('%a');

    $quota_reached_msg = '<h5 class="article-protector-msg">You have reached your quota for this month, please comback next month !!</h5>';
    $quota_reached_msg .= '<p class="article-protector-msg">'.$days_remaining.' day(s) remaining !!</p>';

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