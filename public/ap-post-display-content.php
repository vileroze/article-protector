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
                echo $wp_err;
            }else{
                //redirect back to current page
                global $wp;
                wp_safe_redirect(home_url( $wp->request ));
            }
            
        }

        return ob_get_clean();

    }
    add_filter('template_redirect', 'article_protector_login_form');


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

        //get all articles the user has already read
        $visited_articles =  get_user_meta( $curr_userID, 'visited_articles', true ) == "" ? serialize([]) : get_user_meta( $curr_userID, 'visited_articles', true );
        $unserialize_visited_articles = unserialize($visited_articles);

        //get specific user article quota, if no value sets to 3 as default
        $user_article_quota = get_user_meta( $curr_userID, 'article_quota', true ) > 0 ? get_user_meta( $curr_userID, 'article_quota', true ) : 3;

        //get time left
        $dtFirst = new DateTime('first day of next month'); //first day of next month
        $dtToday = new DateTime('today');
        $time_until_task_will_run = $dtToday->diff($dtFirst);
        $days_remaining = $time_until_task_will_run->format('%a');

        //message to show user if auota reached for current month
        $quota_reached_msg = '<h5 class="article-protector-msg">You have reached your quota for this month, please comback next month !!</h5>';
        $quota_reached_msg .= '<p class="article-protector-msg">'.$days_remaining.' day(s) remaining !!</p>';

        //check if user reached quota
        if( ( sizeof($unserialize_visited_articles) > $user_article_quota ) && in_array( get_the_ID(), $unserialize_visited_articles) != 1 ){ // //sizeof($unserialize_visited_articles) == (int)get_option( 'ap_month_quota', '3' )
            return $content_substring_with_overlay.$quota_reached_msg;
        }
        
        //message asking user to leave a comment
        $get_comment = "<strong>Did you enjoy the article ".$curr_username."? Leave your thoughts below in the comments!</strong>";

        //update quota of user by if user visits new article
        if ( ! in_array( get_the_ID(), $unserialize_visited_articles ) ){ //checks if article already read
            if ( ( isset( get_post_meta( get_the_ID(), 'paywalled')[0] ) == "1" ) ) { //checks for premium articles
                //add the current post id to the array
                array_push( $unserialize_visited_articles, get_the_ID() );

                $serialized_visited_array = serialize($unserialize_visited_articles);

                //update visited_articles meta
                update_user_meta( $curr_userID, 'visited_articles', $serialized_visited_array );

                //decrement user article_quota meta
                update_user_meta( $curr_userID, 'remaining_article_quota', $user_article_quota - 1 );
            }
        }

        //displays the entire content along with a message to leave a comment
        return $content.$get_comment;
    }
