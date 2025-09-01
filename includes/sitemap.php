<?php
if (!defined('ABSPATH')) {
    exit;
}

class SoftworldIT_SEO_Sitemap
{
    const QUERY_FLAG = 'softworld_sitemap';
    const QUERY_TYPE = 'softworld_type';
    const QUERY_PAGE = 'softworld_page';
    const QUERY_GROUP = 'softworld_group';
    const CHUNK_SIZE = 1000;

    public static function init()
    {
        add_action('init', [__CLASS__, 'add_rewrite']);
        add_filter('query_vars', [__CLASS__, 'register_query_vars']);
        add_action('template_redirect', [__CLASS__, 'maybe_render']);
        add_filter('robots_txt', [__CLASS__, 'robots_txt_link'], 99, 2);
    }

    public static function add_rewrite()
    {
        add_rewrite_tag('%' . self::QUERY_FLAG . '%', '([0-1])');
        add_rewrite_tag('%' . self::QUERY_TYPE . '%', '(posts|pages|taxonomies|cpt)');
        add_rewrite_tag('%' . self::QUERY_PAGE . '%', '([0-9]+)');
        add_rewrite_tag('%' . self::QUERY_GROUP . '%', '([a-z0-9_\-]+)');

        add_rewrite_rule('^sitemap\.xml$', 'index.php?' . self::QUERY_FLAG . '=1', 'top');
        add_rewrite_rule('^sitemap-(posts|pages|taxonomies|cpt)-([a-z0-9_\-]+)-([0-9]+)\.xml$', 'index.php?' . self::QUERY_FLAG . '=1&' . self::QUERY_TYPE . '=$matches[1]&' . self::QUERY_GROUP . '=$matches[2]&' . self::QUERY_PAGE . '=$matches[3]', 'top');
    }

    public static function register_query_vars($vars)
    {
        $vars[] = self::QUERY_FLAG;
        $vars[] = self::QUERY_TYPE;
        $vars[] = self::QUERY_PAGE;
        $vars[] = self::QUERY_GROUP;
        return $vars;
    }

    public static function robots_txt_link($output, $public)
    {
        $sitemap_url = home_url('/sitemap.xml');
        if (strpos($output, 'Sitemap:') === false) {
            $output .= "\nSitemap: " . esc_url_raw($sitemap_url) . "\n";
        }
        return $output;
    }

    public static function maybe_render()
    {
        if (!get_query_var(self::QUERY_FLAG))
            return;

        if (!defined('DONOTCACHEPAGE'))
            define('DONOTCACHEPAGE', true);

        $type = get_query_var(self::QUERY_TYPE);
        $page = absint(get_query_var(self::QUERY_PAGE));
        $group = get_query_var(self::QUERY_GROUP);

        header('Content-Type: application/xml; charset=utf-8');

        if ($type && $group && $page > 0) {
            echo self::render_sitemap_body($type, $group, $page);
        } else {
            echo self::render_sitemap_index();
        }
        exit;
    }

    protected static function render_sitemap_index()
    {
        $settings = get_option('softworld_sitemap_settings', [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => [],
            'include_cpt' => [],
        ]);

        $now = esc_html(gmdate('c'));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Include posts if enabled
        if ($settings['include_posts']) {
            $post_chunks = self::count_chunks_for('post', 'post');
            for ($i = 1; $i <= $post_chunks; $i++) {
                $xml .= "<sitemap><loc>" . esc_url(home_url("/sitemap-posts-post-{$i}.xml")) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
            }
        }

        // Include pages if enabled
        if ($settings['include_pages']) {
            $page_chunks = self::count_chunks_for('page', 'page');
            for ($i = 1; $i <= $page_chunks; $i++) {
                $xml .= "<sitemap><loc>" . esc_url(home_url("/sitemap-pages-page-{$i}.xml")) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
            }
        }

        // Include taxonomies if enabled
        if (!empty($settings['include_taxonomies'])) {
            foreach ($settings['include_taxonomies'] as $taxonomy) {
                $tax_chunks = self::count_chunks_for($taxonomy, 'taxonomy');
                for ($i = 1; $i <= $tax_chunks; $i++) {
                    $xml .= "<sitemap><loc>" . esc_url(home_url("/sitemap-taxonomies-{$taxonomy}-{$i}.xml")) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
                }
            }
        }

        // Include custom post types if enabled
        if (!empty($settings['include_cpt'])) {
            foreach ($settings['include_cpt'] as $cpt) {
                $cpt_chunks = self::count_chunks_for($cpt, 'cpt');
                for ($i = 1; $i <= $cpt_chunks; $i++) {
                    $xml .= "<sitemap><loc>" . esc_url(home_url("/sitemap-cpt-{$cpt}-{$i}.xml")) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
                }
            }
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    protected static function render_sitemap_body($type, $group, $page_number)
    {
        $settings = get_option('softworld_sitemap_settings', [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => [],
            'include_cpt' => [],
        ]);

        $offset = (max(1, $page_number) - 1) * self::CHUNK_SIZE;
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        if ($type === 'posts' || $type === 'pages') {
            $post_type = $group === 'pages' ? 'page' : 'post';

            // Check if this post type is enabled
            if (
                ($post_type === 'post' && !$settings['include_posts']) ||
                ($post_type === 'page' && !$settings['include_pages'])
            ) {
                return self::render_empty_sitemap();
            }

            // Build query args
            $query_args = [
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => self::CHUNK_SIZE,
                'offset' => $offset,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            ];

            // Add exclusion for specific posts/pages
            $excluded_ids = $post_type === 'post' ? $settings['excluded_posts'] : $settings['excluded_pages'];
            if (!empty($excluded_ids)) {
                $query_args['post__not_in'] = $excluded_ids;
            }

            $q = new WP_Query($query_args);

            foreach ($q->posts as $post_id) {
                $loc = esc_url(get_permalink($post_id));
                $lastmod = esc_html(get_post_modified_time('c', true, $post_id));
                $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
            }
        } elseif ($type === 'taxonomies' && taxonomy_exists($group)) {
            // Check if this taxonomy is enabled
            if (!in_array($group, $settings['include_taxonomies'])) {
                return self::render_empty_sitemap();
            }

            $terms = get_terms([
                'taxonomy' => $group,
                'hide_empty' => true,
                'number' => self::CHUNK_SIZE,
                'offset' => $offset,
                'orderby' => 'name',
                'order' => 'ASC',
                'fields' => 'ids',
            ]);

            if (!is_wp_error($terms)) {
                foreach ($terms as $term_id) {
                    $term = get_term($term_id, $group);
                    $loc = esc_url(get_term_link($term));
                    $xml .= "<url><loc>{$loc}</loc><changefreq>weekly</changefreq><priority>0.5</priority></url>\n";
                }
            }
        } elseif ($type === 'cpt' && post_type_exists($group)) {
            // Check if this CPT is enabled
            if (!in_array($group, $settings['include_cpt'])) {
                return self::render_empty_sitemap();
            }

            $query_args = [
                'post_type' => $group,
                'post_status' => 'publish',
                'posts_per_page' => self::CHUNK_SIZE,
                'offset' => $offset,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids',
            ];

            $q = new WP_Query($query_args);

            foreach ($q->posts as $post_id) {
                $loc = esc_url(get_permalink($post_id));
                $lastmod = esc_html(get_post_modified_time('c', true, $post_id));
                $xml .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
            }
        }

        $xml .= '</urlset>';
        return $xml;
    }

    protected static function render_empty_sitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= '</urlset>';
        return $xml;
    }

    protected static function count_chunks_for($item, $type)
    {
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
        ];

        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        if ($type === 'post' || $type === 'page') {
            // Return 0 if this post type is disabled
            if (
                ($item === 'post' && !$settings['include_posts']) ||
                ($item === 'page' && !$settings['include_pages'])
            ) {
                return 0;
            }

            // Get total count excluding specific items
            $count_args = [
                'post_type' => $item,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ];

            $excluded_ids = $item === 'post' ? $settings['excluded_posts'] : $settings['excluded_pages'];
            if (!empty($excluded_ids)) {
                $count_args['post__not_in'] = $excluded_ids;
            }

            $count_query = new WP_Query($count_args);
            $total_count = $count_query->found_posts;

            return max(1, ceil($total_count / self::CHUNK_SIZE));
        } elseif ($type === 'taxonomy') {
            // Return 0 if this taxonomy is disabled
            if (!in_array($item, (array) $settings['include_taxonomies'])) {
                return 0;
            }

            $term_count = wp_count_terms($item, ['hide_empty' => true]);
            return max(1, ceil($term_count / self::CHUNK_SIZE));
        } elseif ($type === 'cpt') {
            // Return 0 if this CPT is disabled
            if (!in_array($item, (array) $settings['include_cpt'])) {
                return 0;
            }

            $count_args = [
                'post_type' => $item,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ];

            $count_query = new WP_Query($count_args);
            $total_count = $count_query->found_posts;

            return max(1, ceil($total_count / self::CHUNK_SIZE));
        }

        return 0;
    }
}

register_activation_hook( __FILE__, function() {
    SoftworldIT_SEO_Sitemap::add_rewrite();
    flush_rewrite_rules();
    
    // Set default settings on activation
    $default_settings = [
        'include_posts' => 1,
        'include_pages' => 1,
        'excluded_posts' => [],
        'excluded_pages' => [],
        'include_taxonomies' => ['category', 'post_tag'],
        'include_cpt' => [],
    ];
    
    // Get existing settings if any
    $existing_settings = get_option('softworld_sitemap_settings', []);
    
    // Merge with defaults, preserving existing values
    $settings = wp_parse_args($existing_settings, $default_settings);
    
    update_option('softworld_sitemap_settings', $settings);
});
register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
SoftworldIT_SEO_Sitemap::init();