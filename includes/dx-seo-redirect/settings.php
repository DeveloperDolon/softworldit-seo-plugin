<?php
if (!defined('ABSPATH')) exit;

class DXSEO_Settings {
    public static function render_settings(){
        $opts = get_option('dxseo_settings');
        ?>
        <div class="wrap dxseo-wrap">
            <h1 class="dxseo-h1">DX SEO Settings</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('dxseo_save_settings','_wpnonce'); ?>
                <input type="hidden" name="action" value="dxseo_save_settings"/>
                <div class="dxseo-card">
                    <h2 class="dxseo-h2">Global</h2>
                    <label class="toggle"><input type="checkbox" name="autocreate_slug_redirect" value="1" <?php checked(!empty($opts['autocreate_slug_redirect'])); ?>/> Auto 301 on slug change</label>
                    <label class="toggle"><input type="checkbox" name="apply_to_404_only" value="1" <?php checked(!empty($opts['apply_to_404_only'])); ?>/> Apply redirects only on 404</label>
                </div>
                <p><button class="button button-primary">Save Settings</button></p>
            </form>
        </div>
        <?php
    }

    public static function handle_settings_posted($arr){
        $new = get_option('dxseo_settings', []);
        $new['autocreate_slug_redirect'] = !empty($arr['autocreate_slug_redirect']) ? 1 : 0;
        $new['apply_to_404_only'] = !empty($arr['apply_to_404_only']) ? 1 : 0;
        $new['disable_media_pages'] = !empty($arr['disable_media_pages']) ? 1 : 0;
        $new['strip_replytocom'] = !empty($arr['strip_replytocom']) ? 1 : 0;
        $new['deleted_behavior'] = in_array(($arr['deleted_behavior'] ?? '410'), ['410','homepage','parent']) ? $arr['deleted_behavior'] : '410';
        $new['disallow_params'] = self::lines_to_array($arr['disallow_params'] ?? '');
        $new['noindex_patterns'] = self::lines_to_array($arr['noindex_patterns'] ?? '');
        $new['gone_patterns'] = self::lines_to_array($arr['gone_patterns'] ?? '');
        update_option('dxseo_settings', $new);
    }

    protected static function lines_to_array($txt){
        if (is_array($txt)) return array_values(array_filter(array_map('trim', $txt)));
        $lines = preg_split('/\r?\n/', (string)$txt); 
        return array_values(array_filter(array_map('trim', $lines)));
    }

    public static function bootstrap_public(){
        add_action('admin_post_dxseo_save_settings', [__CLASS__, 'handle_settings']);
    }

    public static function handle_settings(){
        if (!current_user_can('manage_options')) wp_die('No');
        check_admin_referer('dxseo_save_settings');
        self::handle_settings_posted($_POST);
        wp_redirect(wp_get_referer());
        exit;
    }
}
