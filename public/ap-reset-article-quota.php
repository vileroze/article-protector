<?php


/**
 * Resetting post user quota every month.
 */

add_action( 'wp_footer', 'article_protector_reset_user_quota' );

function article_protector_reset_user_quota() {
    $last_quota_reset_month = str_pad( getdate( strtotime( get_option( 'last_run_month', '' ) ) )['mon'], 2, '0', STR_PAD_LEFT );
    $curr_month = date('m');

    // if( $curr_month == $last_quota_reset_month + 1 ){

    //     //set last reset month to current month
    //     update_option( 'last_run_month', date("Y-m-d") );

    //     //select users with 'quota' meta_key
    //     $user_query = new WP_User_Query( ['meta_query' => [ 'meta_key' => 'quota' ]]);

    //     // Get the results
    //     $users = $user_query->get_results();

    //     // Check for results
    //     if ( ! empty( $users ) ) {
    //         foreach ( $users as $user ){
            
    //             // get all the user's data
    //             $user_info = get_userdata( $user->ID );

    //             //update quota for all users
    //             update_user_meta( $user_info->ID, 'quota', '' );
    //         }
    //     }
    // }

    //////////////////////////////////////

    if( $curr_month == 2 ){

        //set last reset month to current month
        // update_option( 'last_run_month', date("Y-m-d") );

        //select users with 'article_quota' meta key
        $user_query = new WP_User_Query( ['meta_query' => [ 'meta_key' => 'og_article_quota' ]]);

        // Get the results
        $users = $user_query->get_results();

        // Check for results
        if ( ! empty( $users ) ) {
            foreach ( $users as $user ){
            
                // get all the user's data
                $user_info = get_userdata( $user->ID );

                //get user article_quota and remaining_article_quota meta
                $remaining_user_article_quota = (int)get_user_meta( $user_info->ID, 'remaining_article_quota', true );
                $curr_month_article_quota = (int)get_user_meta( $user_info->ID, 'article_quota', true );

                $next_month_article_quota = 0;

                //carry over remaining quota to next month only if remaining quota is equal to or less than 3
                if ( $remaining_user_article_quota <= 3 ) {
                    $next_month_article_quota = $curr_month_article_quota + $remaining_user_article_quota;
                }else{
                    $next_month_article_quota = $curr_month_article_quota;
                }
                
                //update article_quota
                update_user_meta( $user_info->ID, 'article_quota', $next_month_article_quota );

                //reset the remainng_article_quota to 0
                update_user_meta( $user_info->ID, 'remaining_article_quota', 0 );

                //clear visited articles
                
            }
        }
    }
}


// // creating custom scheduler
// add_filter( 'cron_schedules', 'article_protector_add_cron_interval' );
// function article_protector_add_cron_interval( $schedules ) {
//     $schedules['everytwoseconds'] = 
//     [
//         'interval'  => 2, // time in seconds
//         'display'   => 'Every Two Seconds'
//     ];
//     return $schedules;
// }

// //check if laready scheduled, if not, schedule 
// if ( ! wp_next_scheduled( 'update_user_meta_quota' ) ) {
//     wp_schedule_event( strtotime( '12am today' ), 'daily', 'update_user_meta_quota' );
// }
// add_action( 'update_user_meta_quota', 'article_protector_reset_user_quota' );



// If today is the first day of the month, then for each user with a 'quota' meta_key, set the value of
// hat meta_key to an empty string.

// function article_protector_reset_user_quota() {
//     //check if today is starting of month
//     if( date('d') == "01" ){
//         //select users with 'quota' meta_key
//         $user_query = new WP_User_Query( ['meta_query' => [ 'meta_key' => 'quota' ]]);

//         // Get the results
//         $users = $user_query->get_results();

//         // Check for results
//         if ( ! empty( $users ) ) {
//             foreach ( $users as $user ){
            
//                 // get all the user's data
//                 $user_info = get_userdata( $user->ID );

//                 //update quota for all users
//                 update_user_meta( $user_info->ID, 'quota', '' );
//             }
//         }
//     }
// }