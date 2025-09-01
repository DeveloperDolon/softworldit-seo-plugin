<?php

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Crawl_Optimizer
{
    private $options;

    public function __construct()
    {
        $this->options = get_option('softworld_ai_seo_crawl_options', []);

        add_action('admin_init', [$this, 'register_settings']);
        add_action('template_redirect', [$this, 'disable_archives']);
        add_action('init', [$this, 'disable_feeds']);
    }

    public function register_settings()
    {
        register_setting('softworld_ai_seo_crawl_options', 'softworld_ai_seo_crawl_options', [$this, 'sanitize_options']);

        add_settings_section(
            'crawl_optimization_section',
            __('Disable Unused Content', 'softworld-ai-seo'),
            null,
            'softworld-ai-seo-crawl-optimizer'
        );

        add_settings_field(
            'disable_date_archives',
            __('Disable Date Archives', 'softworld-ai-seo'),
            [$this, 'render_field_checkbox'],
            'softworld-ai-seo-crawl-optimizer',
            'crawl_optimization_section',
            ['id' => 'disable_date_archives', 'label' => 'Redirect date-based archives to the homepage.']
        );
        add_settings_field(
            'disable_author_archives',
            __('Disable Author Archives', 'softworld-ai-seo'),
            [$this, 'render_field_checkbox'],
            'softworld-ai-seo-crawl-optimizer',
            'crawl_optimization_section',
            ['id' => 'disable_author_archives', 'label' => 'Redirect author archives to the homepage.']
        );
        add_settings_field(
            'disable_tag_archives',
            __('Disable Tag Archives', 'softworld-ai-seo'),
            [$this, 'render_field_checkbox'],
            'softworld-ai-seo-crawl-optimizer',
            'crawl_optimization_section',
            ['id' => 'disable_tag_archives', 'label' => 'Redirect tag archives to the homepage.']
        );
         add_settings_field(
            'disable_feeds',
            __('Disable All Feeds', 'softworld-ai-seo'),
            [$this, 'render_field_checkbox'],
            'softworld-ai-seo-crawl-optimizer',
            'crawl_optimization_section',
            ['id' => 'disable_feeds', 'label' => 'Disable all RSS/Atom feeds.']
        );
    }

    public function render_field_checkbox($args)
    {
        $id = $args['id'];
        $checked = isset($this->options[$id]) ? 'checked' : '';
        echo "<input type='checkbox' id='$id' name='softworld_ai_seo_crawl_options[$id]' value='1' $checked>";
        echo "<label for='$id'> " . esc_html($args['label']) . "</label>";
    }


    public function sanitize_options($input)
    {
        $sanitized_input = [];
        foreach ($input as $key => $value) {
            $sanitized_input[$key] = absint($value);
        }
        return $sanitized_input;
    }

    public function disable_archives()
    {
        if (
            (isset($this->options['disable_date_archives']) && is_date()) ||
            (isset($this->options['disable_author_archives']) && is_author()) ||
            (isset($this->options['disable_tag_archives']) && is_tag())
        ) {
            wp_redirect(get_home_url(), 301);
            exit;
        }
    }

    public function disable_feeds()
    {
        if (!isset($this->options['disable_feeds'])) {
            return;
        }

        // Disable general feeds
        remove_action('do_feed', 'do_feed', 10);
        remove_action('do_feed_rdf', 'do_feed_rdf', 10);
        remove_action('do_feed_rss', 'do_feed_rss', 10);
        remove_action('do_feed_rss2', 'do_feed_rss2', 10);
        remove_action('do_feed_atom', 'do_feed_atom', 10);

        // Disable comment feeds
        remove_action('do_feed_rss2_comments', 'do_feed_rss2_comments', 10);
        remove_action('do_feed_atom_comments', 'do_feed_atom_comments', 10);

        // Remove feed links from head
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }

    public function render_admin_page()
    {
        require_once AI_SEO_PLUGIN_PATH . 'templates/admin-crawl-optimizer.php';
    }
}