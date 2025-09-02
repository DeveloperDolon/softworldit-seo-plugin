<?php
if (!defined('ABSPATH')) exit;

add_action('save_post', function($post_id){
    if(!isset($_POST['ayc_meta_box_nonce']) || !wp_verify_nonce($_POST['ayc_meta_box_nonce'],'ayc_save_meta_box_data')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(isset($_POST['post_type']) && $_POST['post_type']=='page' && !current_user_can('edit_page',$post_id)) return;
    if(!current_user_can('edit_post',$post_id)) return;

    if(isset($_POST['ayc_meta_title'])) update_post_meta($post_id,'_ayc_meta_title',sanitize_text_field($_POST['ayc_meta_title']));
    if(isset($_POST['ayc_meta_description'])) update_post_meta($post_id,'_ayc_meta_description',sanitize_textarea_field($_POST['ayc_meta_description']));
    if(isset($_POST['ayc_focus_keyphrase'])) update_post_meta($post_id,'_ayc_focus_keyphrase',sanitize_text_field($_POST['ayc_focus_keyphrase']));

    if(isset($_POST['ayc_metric']) && is_array($_POST['ayc_metric'])){
        $clean=[];
        foreach($_POST['ayc_metric'] as $k=>$arr){
            $note = $arr['note']??'';
            $status = in_array($arr['status'],['good','warn','bad'],true)?$arr['status']:'';
            $clean[$k] = ['note'=>sanitize_text_field($note),'status'=>$status];
        }
        update_post_meta($post_id,'_ayc_metric',$clean);
    }
});

// Add this to your save function
if (isset($_POST['softworld_custom_fields_pattern'])) {
    update_post_meta(
        $post_id,
        '_softworld_ai_seo_custom_fields_pattern',
        sanitize_text_field($_POST['softworld_custom_fields_pattern'])
    );
}

if (isset($_POST['softworld_wc_attributes_pattern'])) {
    update_post_meta(
        $post_id,
        '_softworld_ai_seo_wc_attributes_pattern',
        sanitize_text_field($_POST['softworld_wc_attributes_pattern'])
    );
}