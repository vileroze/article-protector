<?php

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