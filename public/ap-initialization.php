<?php


/**
 * Runs on initialization
 */

// register style on initialization
add_action( 'init', 'article_protector_init' );
function article_protector_init(){

    //register styles
	wp_register_style( 'article-protector-style', plugins_url( '/assets/css/article-protector-public-css.css', __FILE__ ), false, '1.0.0', 'all');

    //register scripts
    wp_register_script( 'article-protector-script', plugins_url( '/assets/js/article-protector-public-js.js', __FILE__ ), false, '1.0.0', true );

    // use the style above
    add_action('wp_enqueue_scripts', 'article_protector_enqueue_scripts');
    function article_protector_enqueue_scripts(){
        wp_enqueue_style( 'article-protector-style' );
        wp_enqueue_script( 'article-protector-script' );
    }

    //display user registration form if user not logged in
    if ( ! is_user_logged_in() ) {
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
                array_splice($items_array, sizeof($items_array), 0, '<li><button id="myBtn">REGISTER</button>'.article_protector_user_registration().'</li>'); // insert custom item after 2nd one

                $items = implode('', $items_array);
            }
            return $items;
        }
    }

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


/**
 * create user registration form
 */

 function article_protector_user_registration(){
    ob_start();
    ?>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            <h5>SIGN UP FORM</h5>
            <form action="" method="POST">
                <div class="user-registration">
                    <div class="inline-input">
                        <label for="username"><b>Username</b></label>
                        <input type="text" placeholder="Enter username" name="user_name" required>
                    </div>

                    <div class="inline-input">
                        <label for="useremail"><b>Email</b></label>
                        <input type="email" placeholder="Enter your email" name="user_email" required>
                    </div>
                    
                    <div class="inline-input">
                        <label for="first_password"><b>Password</b></label>
                        <input type="password" placeholder="Enter password" name="first_password" required>    
                    </div>
                    
                    <div class="inline-input">
                        <label for="confirm_password"><b>Confirm Password</b></label>
                        <input type="password" placeholder="Confirm password" name="confirm_password" required>
                    </div>

                    <div class="inline-input">
                        <label for="article_quota"><b>QUOTA</b></label>
                        <input type="number" min="0" step="1" oninput="validity.valid||(value='');" placeholder="Article quota per month" name="article_quota" required>
                    </div>

                    <input type="submit" name="user_registration" value="REGISTER">

                </div>
            </form>
        </div>
    </div>

    <?php

    if( isset($_POST['user_registration']) ){

        $user_name = $_POST['user_name'];
        $user_email = $_POST['user_email'];
        $first_password = $_POST['first_password'];
        $confirm_password = $_POST['confirm_password'];
        $article_quota = $_POST['article_quota'];

        $error = [];

        if( username_exists( $user_name ) ){
            $error['user_name'] = "User name already taken";
        }

        if( email_exists( $user_email ) ){
            $error['user_email'] = "Email already in use";
        }

        if( ( $first_password == $confirm_password ) == false ){
            $error['user_pass'] = "Password do not match";
        }

        if( count( $error ) == 0 ){

            // creates new user and gets id
            $curr_registered_user_id = wp_create_user( $user_name, $confirm_password, $user_email );

            //add "article quota" to user meta 
            add_user_meta( $curr_registered_user_id, 'og_article_quota', $article_quota );
            add_user_meta( $curr_registered_user_id, 'article_quota', $article_quota );

        }else{
            //displays all errors
            foreach ($error as $err) {
                echo '<h5 class="form-err">'.$err.'!!</h5>';
            }
        }
    }

    return ob_get_clean();
}
// add_filter('template_redirect', 'article_protector_login_form');

