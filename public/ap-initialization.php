<?php

/**
 * Register styles and scripts.
 */

// register style on initialization
add_action('init', 'article_protector_init');
function article_protector_init(){

    //register styles
	wp_register_style( 'article-protector-style', plugins_url('/assets/css/article-protector-public.css', __FILE__), false, '1.0.0', 'all');

    // use the style above
    add_action('wp_enqueue_scripts', 'article_protector_enqueue_style');
    function article_protector_enqueue_style(){
        wp_enqueue_style( 'article-protector-style' );
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