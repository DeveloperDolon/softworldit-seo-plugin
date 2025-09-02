<?php
if (!defined('ABSPATH')) exit;

function ayc_snippet_preview($post){
    $title = get_post_meta($post->ID,'_ayc_meta_title',true) ?: get_the_title($post);
    $desc  = get_post_meta($post->ID,'_ayc_meta_description',true) ?: wp_strip_all_tags( get_the_excerpt($post) );
    $url   = get_permalink($post);

    echo '<div class="ayc-card" style="margin-top:10px">';
    echo '  <strong>Snippet Preview</strong><br>';
    echo '  <div style="color:#1a0dab; font-size:16px; line-height:1.2">'.esc_html($title).'</div>';
    echo '  <div style="color:#006621; font-size:13px">'.esc_url($url).'</div>';
    echo '  <div style="color:#545454; font-size:14px">'.esc_html($desc).'</div>';
    echo '</div>';
}
