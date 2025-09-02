<?php
if (!defined('ABSPATH')) exit;

/**
 * Metrics (id => label) used in editor metabox badges
 */
function ayc_get_metrics(){
    return [
        'kw_in_title'      => 'Keyword in Title',
        'kw_in_desc'       => 'Keyword in Description',
        'kw_in_first_para' => 'Keyword in First Paragraph',
        'kw_in_subhead'    => 'Keyword in Subheadings (H2/H3)',
        'kp_density'       => 'Keyphrase Density',
        'links_internal'   => 'Internal Links',
        'links_external'   => 'External Links',
        'image_alt'        => 'Image Alt Text',
        'sentence_len'     => 'Sentence length',
        'passive'          => 'Passive voice',
        'transition'       => 'Transition words',
        'flesch'           => 'Flesch Reading Ease',
    ];
}

function ayc_add_meta_box(){
    add_meta_box('ayc_meta','Softworld AI SEO','ayc_meta_box_callback',['post','page'],'side','high');
}
add_action('add_meta_boxes','ayc_add_meta_box');

function ayc_meta_box_callback($post){
    // nonce
    wp_nonce_field('ayc_save_meta_box_data','ayc_meta_box_nonce');

    // saved meta
    $meta_title = get_post_meta($post->ID,'_ayc_meta_title',true);
    $meta_desc  = get_post_meta($post->ID,'_ayc_meta_description',true);
    $focus_kw   = get_post_meta($post->ID,'_ayc_focus_keyphrase',true);

    // saved overrides (notes + statuses)
    $stored_metrics = get_post_meta($post->ID,'_ayc_metric',true);
    if(!is_array($stored_metrics)) $stored_metrics = [];

    echo '<p><label for="ayc_meta_title"><strong>Meta Title</strong></label></p>';
    echo '<input type="text" id="ayc_meta_title" name="ayc_meta_title" value="'.esc_attr($meta_title).'" style="width:100%;" />';

    echo '<p style="margin-top:10px;"><label for="ayc_meta_description"><strong>Meta Description</strong></label></p>';
    echo '<textarea id="ayc_meta_description" name="ayc_meta_description" rows="3" style="width:100%;">'.esc_textarea($meta_desc).'</textarea>';

    echo '<p style="margin-top:10px;"><label for="ayc_focus_keyphrase"><strong>Focus Keyphrase</strong></label></p>';
    echo '<input type="text" id="ayc_focus_keyphrase" name="ayc_focus_keyphrase" value="'.esc_attr($focus_kw).'" style="width:100%;" placeholder="e.g., best pizza in dhaka" />';

    echo '<div class="ayc-card">';
    echo '<h4 class="ayc-h4">Content Analysis</h4>';

    echo '<p class="ayc-compact"><strong>Meta Title:</strong> <span id="ayc_analysis_title">–</span></p>';
    echo '<p class="ayc-compact"><strong>Meta Description:</strong> <span id="ayc_analysis_desc">–</span></p>';
    echo '<hr class="ayc-hr">';

    foreach(ayc_get_metrics() as $id=>$label){
        $note   = isset($stored_metrics[$id]['note'])   ? $stored_metrics[$id]['note']   : '';
        $status = isset($stored_metrics[$id]['status']) ? $stored_metrics[$id]['status'] : '';

        echo '<div class="ayc-row">';
        echo '  <p class="ayc-metric"><strong>'.$label.':</strong> ';
        echo '      <span id="ayc_'.$id.'" class="ayc-badge" data-id="'.$id.'" data-manual-status="'.esc_attr($status).'"></span> ';
        echo '      <em id="ayc_'.$id.'_ovr" class="ayc-override">'.($status ? '(overridden)' : '').'</em> ';
        echo '      <button class="ayc-suggest-btn" data-key="'.$id.'" type="button">💡 Suggest</button>';
        echo '  </p>';
        echo '  <div id="ayc_'.$id.'_msg" class="ayc-tooltip-msg">'.($note ? '<span class="ayc-note">💡 '.esc_html($note).'</span>' : '').'</div>';

        echo '  <input type="hidden" name="ayc_metric['.$id.'][note]" id="ayc_'.$id.'_note" value="'.esc_attr($note).'">';
        echo '  <input type="hidden" name="ayc_metric['.$id.'][status]" id="ayc_'.$id.'_status" value="'.esc_attr($status).'">';
        echo '</div>';
    }
    echo '</div>';

    // Small canvas inside metabox (optional). Guarded in JS.
    echo '<canvas id="ayc_metrics_chart" style="width:100%; height:180px; margin-top:10px;"></canvas>';

    // snippet preview
    ayc_snippet_preview($post);
}

function ayc_save_meta_box_data($post_id){
    // nonce + capability
    if(!isset($_POST['ayc_meta_box_nonce']) || !wp_verify_nonce($_POST['ayc_meta_box_nonce'],'ayc_save_meta_box_data')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if(isset($_POST['post_type']) && 'page' === $_POST['post_type']){
        if(!current_user_can('edit_page',$post_id)) return;
    } else {
        if(!current_user_can('edit_post',$post_id)) return;
    }

    if(isset($_POST['ayc_meta_title']))       update_post_meta($post_id,'_ayc_meta_title',sanitize_text_field($_POST['ayc_meta_title']));
    if(isset($_POST['ayc_meta_description'])) update_post_meta($post_id,'_ayc_meta_description',sanitize_textarea_field($_POST['ayc_meta_description']));
    if(isset($_POST['ayc_focus_keyphrase']))  update_post_meta($post_id,'_ayc_focus_keyphrase',sanitize_text_field($_POST['ayc_focus_keyphrase']));

    if(isset($_POST['ayc_metric']) && is_array($_POST['ayc_metric'])){
        $clean=[];
        foreach($_POST['ayc_metric'] as $key=>$arr){
            $note   = isset($arr['note'])   ? sanitize_text_field($arr['note'])   : '';
            $status = isset($arr['status']) ? sanitize_text_field($arr['status']) : '';
            $status = in_array($status,['good','warn','bad'],true) ? $status : '';
            $clean[$key] = ['note'=>$note,'status'=>$status];
        }
        update_post_meta($post_id,'_ayc_metric',$clean);
    }
}
add_action('save_post','ayc_save_meta_box_data');
