<?php

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