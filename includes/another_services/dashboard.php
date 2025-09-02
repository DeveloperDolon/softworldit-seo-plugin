<?php
if (!defined('ABSPATH')) exit;

function softworldit_render_dashboard() {
    global $wpdb;
    ?>
    <div class="wrap" id="ayc-dashboard-root" style="font-family:Arial,sans-serif;">
        <h1 style="margin-bottom:10px;">📊 SoftworldIT SEO Dashboard</h1>
        <p style="margin-bottom:30px;">Overall SEO health & latest post meta overview</p>

        <!-- Overall Health Chart -->
        <div style="width:100%; max-width:1200px; background:#fff; border:1px solid #e5e5e5; padding:20px; border-radius:10px; margin-bottom:30px;">
            <h2 style="margin-bottom:15px;">🌟 Overall Health Check</h2>
            <canvas id="ayc_metrics_chart" style="width:100%; height:150px;"></canvas>
        </div>

        <!-- Small Donut Charts -->
        <div style="display:flex; flex-wrap:wrap; gap:20px; margin-bottom:30px;">
            <?php
            $metrics = [
                'meta_title'=>'Meta Title','meta_desc'=>'Meta Description','focus_keyphrase'=>'Focus Keyphrase',
                'internal_links'=>'Internal Links','external_links'=>'External Links','image_alt'=>'Image ALT',
                'transition'=>'Transition Words','flesch_ok'=>'Flesch >=60'
            ];
            foreach($metrics as $key=>$label): ?>
                <div class="ayc-donut-card" style="background:#fff; border:1px solid #e5e5e5; border-radius:10px; padding:15px; flex:1 1 220px; text-align:center;">
                    <h4 style="margin-bottom:10px; font-size:16px; font-weight:600;"><?= esc_html($label) ?></h4>
                    <div class="ayc-donut-wrap" style="position:relative;">
                        <canvas id="chart_<?= esc_attr($key) ?>" style="height:140px;"></canvas>
                        <div class="ayc-donut-center" id="center_<?= esc_attr($key) ?>" style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-weight:600;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Latest Posts Table -->
        <div style="background:#fff; border:1px solid #e5e5e5; padding:20px; border-radius:10px;">
            <h2 style="margin-bottom:15px;">Latest Posts (Editable)</h2>
            <div id="ayc-dashboard-list">
                <?php
                $LIMIT = 30;
                $posts = $wpdb->get_results($wpdb->prepare("
                    SELECT ID, post_title 
                    FROM {$wpdb->posts} 
                    WHERE post_type = 'post' AND post_status = 'publish'
                    ORDER BY post_date DESC
                    LIMIT %d
                ", $LIMIT));

                if(!$posts){
                    echo '<p>No published posts found.</p>';
                } else {
                    echo '<table class="widefat" style="width:100%; border-collapse:collapse;"><thead style="background:#f7f7f7;"><tr>';
                    echo '<th style="padding:8px; text-align:left;">Post</th>';
                    echo '<th style="padding:8px; text-align:left;">Meta Title</th>';
                    echo '<th style="padding:8px; text-align:left;">Meta Description</th>';
                    echo '<th style="padding:8px; text-align:left;">Focus Keyphrase</th>';
                    echo '<th style="padding:8px; text-align:left;">Action</th>';
                    echo '</tr></thead><tbody>';
                    foreach($posts as $p){
                        $meta_title = get_post_meta($p->ID, '_ayc_meta_title', true);
                        $meta_desc  = get_post_meta($p->ID, '_ayc_meta_description', true);
                        $focus_kw   = get_post_meta($p->ID, '_ayc_focus_keyphrase', true);
                        $short_title = $p->post_title ? wp_trim_words($p->post_title, 10, '...') : '(no title)';
                        echo '<tr>';
                        echo "<td style='padding:8px;'><a href='".get_edit_post_link($p->ID)."' target='_blank'>".esc_html($short_title)."</a></td>";
                        echo "<td style='padding:8px;'><input type='text' class='ayc-meta-title' data-id='".esc_attr($p->ID)."' value='".esc_attr($meta_title)."' style='width:100%;'></td>";
                        echo "<td style='padding:8px;'><input type='text' class='ayc-meta-desc' data-id='".esc_attr($p->ID)."' value='".esc_attr($meta_desc)."' style='width:100%;'></td>";
                        echo "<td style='padding:8px;'><input type='text' class='ayc-focus-kw' data-id='".esc_attr($p->ID)."' value='".esc_attr($focus_kw)."' style='width:100%;'></td>";
                        echo "<td style='padding:8px;'><button class='button ayc-save' data-id='".esc_attr($p->ID)."'>💾 Save</button></td>";
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/* ==================== AJAX: Fetch overview for charts ==================== */
add_action('wp_ajax_ayc_metrics_overview','softworldit_ajax_metrics_overview');
function softworldit_ajax_metrics_overview(){
    check_ajax_referer('ayc_nonce','nonce');

    $args = ['post_type'=>'post','post_status'=>'publish','posts_per_page'=>30];
    $posts = get_posts($args);

    $agg = [
        'meta_title'=>['good'=>0,'warn'=>0,'bad'=>0],
        'meta_desc'=>['good'=>0,'warn'=>0,'bad'=>0],
        'focus_keyphrase'=>['good'=>0,'warn'=>0,'bad'=>0],
        'internal_links'=>['good'=>0,'warn'=>0,'bad'=>0],
        'external_links'=>['good'=>0,'warn'=>0,'bad'=>0],
        'image_alt'=>['good'=>0,'warn'=>0,'bad'=>0],
        'transition'=>['good'=>0,'warn'=>0,'bad'=>0],
        'flesch_ok'=>['good'=>0,'warn'=>0,'bad'=>0]
    ];

    foreach($posts as $p){
        $title = get_post_meta($p->ID,'_ayc_meta_title',true);
        $desc  = get_post_meta($p->ID,'_ayc_meta_description',true);
        $kw    = get_post_meta($p->ID,'_ayc_focus_keyphrase',true);

        $agg['meta_title'][ $title ? 'good' : 'bad' ]++;
        $agg['meta_desc'][ $desc ? 'good':'bad' ]++;
        $agg['focus_keyphrase'][ $kw ? 'good':'bad' ]++;

        // Dummy positive values for now
        $agg['internal_links']['good']++;
        $agg['external_links']['good']++;
        $agg['image_alt']['good']++;
        $agg['transition']['good']++;
        $agg['flesch_ok']['good']++;
    }

    wp_send_json_success(['agg'=>$agg,'count'=>count($posts)]);
}

/* ==================== AJAX: Save post meta ==================== */
add_action('wp_ajax_ayc_save_post_meta','softworldit_ajax_save_post_meta');
function softworldit_ajax_save_post_meta(){
    check_ajax_referer('ayc_nonce','nonce');

    $post_id = intval($_POST['post_id'] ?? 0);
    if(!$post_id || !current_user_can('edit_post',$post_id)){
        wp_send_json_error('Invalid post');
    }

    if(isset($_POST['meta_title'])) update_post_meta($post_id,'_ayc_meta_title',sanitize_text_field($_POST['meta_title']));
    if(isset($_POST['meta_desc']))  update_post_meta($post_id,'_ayc_meta_description',sanitize_text_field($_POST['meta_desc']));
    if(isset($_POST['focus_kw']))   update_post_meta($post_id,'_ayc_focus_keyphrase',sanitize_text_field($_POST['focus_kw']));

    wp_send_json_success(['message'=>'Saved successfully']);
}
