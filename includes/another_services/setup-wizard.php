<?php
if (!defined('ABSPATH')) exit;

function swit_render_setup_wizard() {
    $step = intval($_GET['step'] ?? 1);

    // Handle form submission
    if(isset($_POST['swit_setup_submit']) && check_admin_referer('swit_setup_nonce','swit_setup_nonce_field')){
        switch($step){
            case 1: // Site Info
                update_option('swit_seo_site_name', sanitize_text_field($_POST['site_name']));
                update_option('swit_seo_site_type', sanitize_text_field($_POST['site_type']));
                update_option('swit_seo_site_owner', sanitize_text_field($_POST['site_owner']));
                break;
            case 2: // Search Appearance Defaults
                update_option('swit_seo_title_template', sanitize_text_field($_POST['title_template']));
                update_option('swit_seo_meta_desc_template', sanitize_text_field($_POST['meta_desc_template']));
                update_option('swit_seo_title_separator', sanitize_text_field($_POST['title_separator']));
                break;
            case 3: // Social Profiles
                update_option('swit_seo_facebook', esc_url_raw($_POST['facebook']));
                update_option('swit_seo_twitter', esc_url_raw($_POST['twitter']));
                update_option('swit_seo_linkedin', esc_url_raw($_POST['linkedin']));
                update_option('swit_seo_instagram', esc_url_raw($_POST['instagram']));
                break;
            case 4: // Content Types
                update_option('swit_seo_track_posts', isset($_POST['track_posts'])?1:0);
                update_option('swit_seo_track_pages', isset($_POST['track_pages'])?1:0);
                break;
            case 5: // Webmaster Tools
                update_option('swit_seo_google_verification', sanitize_text_field($_POST['google_verif']));
                update_option('swit_seo_bing_verification', sanitize_text_field($_POST['bing_verif']));
                break;
        }

        echo '<div class="notice notice-success"><p>✅ Step saved successfully!</p></div>';
    }

    // Load previous options
    $site_name = get_option('swit_seo_site_name','');
    $site_type = get_option('swit_seo_site_type','');
    $site_owner= get_option('swit_seo_site_owner','');

    $title_template     = get_option('swit_seo_title_template','');
    $meta_desc_template = get_option('swit_seo_meta_desc_template','');
    $title_separator    = get_option('swit_seo_title_separator','|');

    $facebook = get_option('swit_seo_facebook','');
    $twitter  = get_option('swit_seo_twitter','');
    $linkedin = get_option('swit_seo_linkedin','');
    $instagram= get_option('swit_seo_instagram','');

    $track_posts = get_option('swit_seo_track_posts',1);
    $track_pages = get_option('swit_seo_track_pages',1);

    $google_verif = get_option('swit_seo_google_verification','');
    $bing_verif   = get_option('swit_seo_bing_verification','');

    ?>
    <div class="wrap" style="max-width:800px;">
        <h1>🛠 SoftworldIT SEO Setup Wizard</h1>
        <form method="post">
            <?php wp_nonce_field('swit_setup_nonce','swit_setup_nonce_field'); ?>

            <?php if($step===1): ?>
                <h2>Step 1: Site Info</h2>
                <table class="form-table">
                    <tr>
                        <th>Website Name</th>
                        <td><input type="text" name="site_name" value="<?= esc_attr($site_name) ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Website Type</th>
                        <td>
                            <select name="site_type">
                                <option value="blog" <?= selected($site_type,'blog',false) ?>>Blog</option>
                                <option value="business" <?= selected($site_type,'business',false) ?>>Business</option>
                                <option value="news" <?= selected($site_type,'news',false) ?>>News</option>
                                <option value="shop" <?= selected($site_type,'shop',false) ?>>Shop</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Owner</th>
                        <td><input type="text" name="site_owner" value="<?= esc_attr($site_owner) ?>" class="regular-text"></td>
                    </tr>
                </table>

            <?php elseif($step===2): ?>
                <h2>Step 2: Search Appearance Defaults</h2>
                <table class="form-table">
                    <tr><th>Title Template</th>
                        <td><input type="text" name="title_template" value="<?= esc_attr($title_template) ?>" class="regular-text"></td></tr>
                    <tr><th>Meta Description Template</th>
                        <td><input type="text" name="meta_desc_template" value="<?= esc_attr($meta_desc_template) ?>" class="regular-text"></td></tr>
                    <tr><th>Title Separator</th>
                        <td><input type="text" name="title_separator" value="<?= esc_attr($title_separator) ?>" class="small-text"></td></tr>
                </table>

            <?php elseif($step===3): ?>
                <h2>Step 3: Social Profiles</h2>
                <table class="form-table">
                    <tr><th>Facebook</th><td><input type="url" name="facebook" value="<?= esc_attr($facebook) ?>" class="regular-text"></td></tr>
                    <tr><th>Twitter</th><td><input type="url" name="twitter" value="<?= esc_attr($twitter) ?>" class="regular-text"></td></tr>
                    <tr><th>LinkedIn</th><td><input type="url" name="linkedin" value="<?= esc_attr($linkedin) ?>" class="regular-text"></td></tr>
                    <tr><th>Instagram</th><td><input type="url" name="instagram" value="<?= esc_attr($instagram) ?>" class="regular-text"></td></tr>
                </table>

            <?php elseif($step===4): ?>
                <h2>Step 4: Content Types</h2>
                <table class="form-table">
                    <tr><th>Track Posts</th>
                        <td><input type="checkbox" name="track_posts" value="1" <?= checked(1,$track_posts,false) ?>></td></tr>
                    <tr><th>Track Pages</th>
                        <td><input type="checkbox" name="track_pages" value="1" <?= checked(1,$track_pages,false) ?>></td></tr>
                </table>

            <?php elseif($step===5): ?>
                <h2>Step 5: Webmaster Tools</h2>
                <table class="form-table">
                    <tr><th>Google Verification Code</th>
                        <td><input type="text" name="google_verif" value="<?= esc_attr($google_verif) ?>" class="regular-text"></td></tr>
                    <tr><th>Bing Verification Code</th>
                        <td><input type="text" name="bing_verif" value="<?= esc_attr($bing_verif) ?>" class="regular-text"></td></tr>
                </table>
            <?php endif; ?>

            <p class="submit">
                <button type="submit" name="swit_setup_submit" class="button button-primary">💾 Save Step</button>
                <?php if($step<5): ?>
                    <a href="<?= admin_url('admin.php?page=softworldit-setup&step='.($step+1)) ?>" class="button button-secondary">➡ Next Step</a>
                <?php endif; ?>
                <?php if($step>1): ?>
                    <a href="<?= admin_url('admin.php?page=softworldit-setup&step='.($step-1)) ?>" class="button button-secondary">⬅ Previous Step</a>
                <?php endif; ?>
            </p>
        </form>
    </div>
    <?php
}
