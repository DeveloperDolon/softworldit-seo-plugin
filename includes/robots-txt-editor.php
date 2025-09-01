<?php
/**
 * Robots.txt Editor for SOFTWORLD AI SEO Plugin
 */

class Softworld_AI_SEO_Robots_Txt_Editor {
    private $option_name = 'softworld_ai_seo_robots_txt';
    private $capability = 'manage_options';

    public function __construct() {
        add_action('admin_init', array($this, 'init'));
        add_action('init', array($this, 'maybe_output_robots_txt'));
    }

    public function init() {
        // Register settings
        register_setting('softworld_ai_seo_robots_txt', $this->option_name, array(
            'sanitize_callback' => array($this, 'sanitize_robots_txt'),
            'default' => $this->get_default_robots_txt()
        ));
    }

    public function sanitize_robots_txt($input) {
        // Basic sanitization - allow only text and common robots.txt directives
        return sanitize_textarea_field($input);
    }

    public function get_default_robots_txt() {
        $site_url = parse_url(site_url());
        $path = (!empty($site_url['path'])) ? $site_url['path'] : '';
        
        $default = "User-agent: *\n";
        $default .= "Disallow: /wp-admin/\n";
        $default .= "Allow: /wp-admin/admin-ajax.php\n";
        $default .= "Sitemap: " . home_url('/sitemap.xml') . "\n";
        
        return $default;
    }

    public function maybe_output_robots_txt() {
        if ($this->is_robots_txt_request()) {
            $this->output_robots_txt();
            exit;
        }
    }

    private function is_robots_txt_request() {
        return $_SERVER['REQUEST_URI'] === '/robots.txt';
    }

    public function output_robots_txt() {
        header('Content-Type: text/plain; charset=utf-8');
        echo $this->get_robots_txt_content();
    }

    public function get_robots_txt_content() {
        $custom_robots = get_option($this->option_name, '');
        
        if (!empty($custom_robots)) {
            return $custom_robots;
        }
        
        return $this->get_default_robots_txt();
    }

    public function render_admin_page() {
        if (!current_user_can($this->capability)) {
            return;
        }
        
        $robots_content = get_option($this->option_name, $this->get_default_robots_txt());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Robots.txt Editor', 'softworld-ai-seo'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('softworld_ai_seo_robots_txt');
                do_settings_sections('softworld_ai_seo_robots_txt');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="<?php echo esc_attr($this->option_name); ?>">
                                <?php esc_html_e('Robots.txt Content', 'softworld-ai-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea 
                                id="<?php echo esc_attr($this->option_name); ?>" 
                                name="<?php echo esc_attr($this->option_name); ?>" 
                                rows="15" 
                                style="width: 100%; font-family: monospace;" 
                                placeholder="<?php esc_attr_e('Enter your custom robots.txt content here...', 'softworld-ai-seo'); ?>"
                            ><?php echo esc_textarea($robots_content); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Customize your robots.txt file. Leave empty to use default settings.', 'softworld-ai-seo'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div class="robots-txt-info">
                <h3><?php esc_html_e('About Robots.txt', 'softworld-ai-seo'); ?></h3>
                <p>
                    <?php esc_html_e('The robots.txt file tells search engine crawlers which pages or files they can or cannot request from your site.', 'softworld-ai-seo'); ?>
                </p>
                <p>
                    <strong><?php esc_html_e('Current robots.txt URL:', 'softworld-ai-seo'); ?></strong>
                    <a href="<?php echo esc_url(home_url('/robots.txt')); ?>" target="_blank">
                        <?php echo esc_url(home_url('/robots.txt')); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
}