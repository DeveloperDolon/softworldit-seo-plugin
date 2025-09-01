<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SoftworldIT_SEO_Sitemap {
    const QUERY_FLAG = 'softworld_sitemap';
    const QUERY_TYPE = 'softworld_type';
    const QUERY_PAGE = 'softworld_page';
    const QUERY_GROUP = 'softworld_group';
    const CHUNK_SIZE = 1000;

    // Search engine ping URLs
    private static $search_engines = [
        'google' => 'https://www.google.com/webmasters/tools/ping?sitemap=',
        'bing' => 'https://www.bing.com/webmaster/ping.aspx?sitemap=',
        'yandex' => 'https://webmaster.yandex.com/ping?sitemap='
    ];

    public static function init() {
        add_action( 'init', [ __CLASS__, 'add_rewrite' ] );
        add_filter( 'query_vars', [ __CLASS__, 'register_query_vars' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_render' ] );
        add_filter( 'robots_txt', [ __CLASS__, 'robots_txt_link' ], 99, 2 );
        
        // Add ping action when sitemap is updated
        add_action( 'save_post', [ __CLASS__, 'maybe_ping_search_engines' ], 99, 2 );
        add_action( 'delete_post', [ __CLASS__, 'maybe_ping_search_engines' ], 99 );
        add_action( 'edited_term', [ __CLASS__, 'maybe_ping_search_engines' ], 99 );
        add_action( 'delete_term', [ __CLASS__, 'maybe_ping_search_engines' ], 99 );
    }

    public static function add_rewrite() {
        add_rewrite_tag( '%' . self::QUERY_FLAG . '%', '([0-1])' );
        add_rewrite_tag( '%' . self::QUERY_TYPE . '%', '(posts|pages|taxonomies|cpt)' );
        add_rewrite_tag( '%' . self::QUERY_PAGE . '%', '([0-9]+)' );
        add_rewrite_tag( '%' . self::QUERY_GROUP . '%', '([a-z0-9_\-]+)' );

        add_rewrite_rule( '^sitemap\.xml$', 'index.php?' . self::QUERY_FLAG . '=1', 'top' );
        add_rewrite_rule( '^sitemap-(posts|pages|taxonomies|cpt)-([a-z0-9_\-]+)-([0-9]+)\.xml$', 'index.php?' . self::QUERY_FLAG . '=1&' . self::QUERY_TYPE . '=$matches[1]&' . self::QUERY_GROUP . '=$matches[2]&' . self::QUERY_PAGE . '=$matches[3]', 'top' );
    }

    public static function register_query_vars( $vars ) {
        $vars[] = self::QUERY_FLAG;
        $vars[] = self::QUERY_TYPE;
        $vars[] = self::QUERY_PAGE;
        $vars[] = self::QUERY_GROUP;
        return $vars;
    }

    public static function robots_txt_link( $output, $public ) {
        $sitemap_url = home_url( '/sitemap.xml' );
        if ( strpos( $output, 'Sitemap:' ) === false ) {
            $output .= "\nSitemap: " . esc_url_raw( $sitemap_url ) . "\n";
        }
        return $output;
    }

    public static function maybe_render() {
        if ( ! get_query_var( self::QUERY_FLAG ) ) return;

        if ( ! defined( 'DONOTCACHEPAGE' ) ) define( 'DONOTCACHEPAGE', true );

        $type = get_query_var( self::QUERY_TYPE );
        $page = absint( get_query_var( self::QUERY_PAGE ) );
        $group = get_query_var( self::QUERY_GROUP );

        header( 'Content-Type: application/xml; charset=utf-8' );

        if ( $type && $group && $page > 0 ) {
            echo self::render_sitemap_body( $type, $group, $page );
        } else {
            echo self::render_sitemap_index();
        }
        exit;
    }

    protected static function render_sitemap_index() {
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
            'priority_posts' => 0.7,
            'priority_pages' => 0.8,
            'priority_taxonomies' => 0.5,
            'priority_cpt' => 0.6,
            'frequency_posts' => 'weekly',
            'frequency_pages' => 'monthly',
            'frequency_taxonomies' => 'weekly',
            'frequency_cpt' => 'weekly',
            'ping_search_engines' => 1,
            'ping_google' => 1,
            'ping_bing' => 1,
            'ping_yandex' => 0,
            'exclude_images' => 0,
        ];
        
        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        $now = esc_html( gmdate( 'c' ) );
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Include posts if enabled
        if ($settings['include_posts']) {
            $post_chunks = self::count_chunks_for( 'post', 'post' );
            for ( $i = 1; $i <= $post_chunks; $i++ ) {
                $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-posts-post-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
            }
        }

        // Include pages if enabled
        if ($settings['include_pages']) {
            $page_chunks = self::count_chunks_for( 'page', 'page' );
            for ( $i = 1; $i <= $page_chunks; $i++ ) {
                $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-pages-page-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
            }
        }

        // Include taxonomies if enabled
        if (!empty($settings['include_taxonomies'])) {
            foreach ($settings['include_taxonomies'] as $taxonomy) {
                $tax_chunks = self::count_chunks_for( $taxonomy, 'taxonomy' );
                for ( $i = 1; $i <= $tax_chunks; $i++ ) {
                    $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-taxonomies-{$taxonomy}-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
                }
            }
        }

        // Include custom post types if enabled
        if (!empty($settings['include_cpt'])) {
            foreach ($settings['include_cpt'] as $cpt) {
                $cpt_chunks = self::count_chunks_for( $cpt, 'cpt' );
                for ( $i = 1; $i <= $cpt_chunks; $i++ ) {
                    $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-cpt-{$cpt}-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
                }
            }
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    protected static function render_sitemap_body( $type, $group, $page_number ) {
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
            'priority_posts' => 0.7,
            'priority_pages' => 0.8,
            'priority_taxonomies' => 0.5,
            'priority_cpt' => 0.6,
            'frequency_posts' => 'weekly',
            'frequency_pages' => 'monthly',
            'frequency_taxonomies' => 'weekly',
            'frequency_cpt' => 'weekly',
            'ping_search_engines' => 1,
            'ping_google' => 1,
            'ping_bing' => 1,
            'ping_yandex' => 0,
            'exclude_images' => 0,
        ];
        
        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        $offset = ( max( 1, $page_number ) - 1 ) * self::CHUNK_SIZE;
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        
        // Include image namespace if images are not excluded
        if (!$settings['exclude_images']) {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        } else {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        }

        if ($type === 'posts' || $type === 'pages') {
            $post_type = $group === 'pages' ? 'page' : 'post';
            
            // Check if this post type is enabled
            if (($post_type === 'post' && !$settings['include_posts']) || 
                ($post_type === 'page' && !$settings['include_pages'])) {
                return self::render_empty_sitemap();
            }

            // Get priority and frequency for this content type
            $priority = $post_type === 'post' ? $settings['priority_posts'] : $settings['priority_pages'];
            $frequency = $post_type === 'post' ? $settings['frequency_posts'] : $settings['frequency_pages'];

            // Build query args
            $query_args = [
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => self::CHUNK_SIZE,
                'offset'         => $offset,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ];

            // Add exclusion for specific posts/pages
            $excluded_ids = $post_type === 'post' ? $settings['excluded_posts'] : $settings['excluded_pages'];
            if (!empty($excluded_ids)) {
                $query_args['post__not_in'] = $excluded_ids;
            }

            $q = new WP_Query( $query_args );

            foreach ( $q->posts as $post_id ) {
                $loc     = esc_url( get_permalink( $post_id ) );
                $lastmod = esc_html( get_post_modified_time( 'c', true, $post_id ) );
                $xml    .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>{$frequency}</changefreq><priority>{$priority}</priority>";
                
                // Add images if not excluded
                if (!$settings['exclude_images']) {
                    $xml .= self::get_post_images_xml($post_id);
                }
                
                $xml .= "</url>\n";
            }
        } 
        elseif ($type === 'taxonomies' && taxonomy_exists($group)) {
            // Check if this taxonomy is enabled
            if (!in_array($group, (array)$settings['include_taxonomies'])) {
                return self::render_empty_sitemap();
            }

            // Get priority and frequency for taxonomies
            $priority = $settings['priority_taxonomies'];
            $frequency = $settings['frequency_taxonomies'];

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
                    $xml .= "<url><loc>{$loc}</loc><changefreq>{$frequency}</changefreq><priority>{$priority}</priority></url>\n";
                }
            }
        }
        elseif ($type === 'cpt' && post_type_exists($group)) {
            // Check if this CPT is enabled
            if (!in_array($group, (array)$settings['include_cpt'])) {
                return self::render_empty_sitemap();
            }

            // Get priority and frequency for CPTs
            $priority = $settings['priority_cpt'];
            $frequency = $settings['frequency_cpt'];

            $query_args = [
                'post_type'      => $group,
                'post_status'    => 'publish',
                'posts_per_page' => self::CHUNK_SIZE,
                'offset'         => $offset,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'fields'         => 'ids',
            ];

            $q = new WP_Query( $query_args );

            foreach ( $q->posts as $post_id ) {
                $loc     = esc_url( get_permalink( $post_id ) );
                $lastmod = esc_html( get_post_modified_time( 'c', true, $post_id ) );
                $xml    .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>{$frequency}</changefreq><priority>{$priority}</priority>";
                
                // Add images if not excluded
                if (!$settings['exclude_images']) {
                    $xml .= self::get_post_images_xml($post_id);
                }
                
                $xml .= "</url>\n";
            }
        }

        $xml .= '</urlset>';
        return $xml;
    }

    protected static function get_post_images_xml($post_id) {
        $images_xml = '';
        $images = get_attached_media('image', $post_id);
        
        // Also check for featured image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $images[$featured_image_id] = get_post($featured_image_id);
        }
        
        foreach ($images as $image) {
            $image_url = wp_get_attachment_image_url($image->ID, 'full');
            $image_title = get_the_title($image->ID);
            $image_caption = wp_get_attachment_caption($image->ID);
            
            if ($image_url) {
                $images_xml .= '<image:image>';
                $images_xml .= '<image:loc>' . esc_url($image_url) . '</image:loc>';
                if ($image_title) {
                    $images_xml .= '<image:title>' . esc_html($image_title) . '</image:title>';
                }
                if ($image_caption) {
                    $images_xml .= '<image:caption>' . esc_html($image_caption) . '</image:caption>';
                }
                $images_xml .= '</image:image>';
            }
        }
        
        return $images_xml;
    }

    protected static function render_empty_sitemap() {
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= '</urlset>';
        return $xml;
    }

    protected static function count_chunks_for( $item, $type ) {
        $default_settings = [
            'include_posts' => 1,
            'include_pages' => 1,
            'excluded_posts' => [],
            'excluded_pages' => [],
            'include_taxonomies' => ['category', 'post_tag'],
            'include_cpt' => [],
            'priority_posts' => 0.7,
            'priority_pages' => 0.8,
            'priority_taxonomies' => 0.5,
            'priority_cpt' => 0.6,
            'frequency_posts' => 'weekly',
            'frequency_pages' => 'monthly',
            'frequency_taxonomies' => 'weekly',
            'frequency_cpt' => 'weekly',
            'ping_search_engines' => 1,
            'ping_google' => 1,
            'ping_bing' => 1,
            'ping_yandex' => 0,
            'exclude_images' => 0,
        ];
        
        $settings = get_option('softworld_sitemap_settings', $default_settings);
        $settings = wp_parse_args($settings, $default_settings);

        if ($type === 'post' || $type === 'page') {
            // Return 0 if this post type is disabled
            if (($item === 'post' && !$settings['include_posts']) || 
                ($item === 'page' && !$settings['include_pages'])) {
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

            return max( 1, ceil( $total_count / self::CHUNK_SIZE ) );
        }
        elseif ($type === 'taxonomy') {
            // Return 0 if this taxonomy is disabled
            if (!in_array($item, (array)$settings['include_taxonomies'])) {
                return 0;
            }

            $term_count = wp_count_terms($item, ['hide_empty' => true]);
            return max( 1, ceil( $term_count / self::CHUNK_SIZE ) );
        }
        elseif ($type === 'cpt') {
            // Return 0 if this CPT is disabled
            if (!in_array($item, (array)$settings['include_cpt'])) {
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

            return max( 1, ceil( $total_count / self::CHUNK_SIZE ) );
        }

        return 0;
    }

    /**
     * Ping search engines when content is updated
     */
    public static function maybe_ping_search_engines($post_id = null, $post = null) {
        // Check if we should ping search engines
        $settings = get_option('softworld_sitemap_settings', []);
        if (!isset($settings['ping_search_engines']) || !$settings['ping_search_engines']) {
            return;
        }

        // Don't ping for revisions or autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        // Don't ping for non-public post types
        if ($post && !in_array($post->post_type, ['post', 'page'])) {
            $cpts = isset($settings['include_cpt']) ? $settings['include_cpt'] : [];
            if (!in_array($post->post_type, $cpts)) {
                return;
            }
        }

        // Use a transient to prevent multiple pings within a short time
        if (get_transient('softworld_sitemap_pinged')) {
            return;
        }

        set_transient('softworld_sitemap_pinged', true, 3600); // Wait 1 hour between pings

        self::ping_search_engines();
    }

    /**
     * Ping search engines with sitemap URL
     */
    public static function ping_search_engines() {
        $settings = get_option('softworld_sitemap_settings', []);
        $sitemap_url = urlencode(home_url('/sitemap.xml'));
        
        foreach (self::$search_engines as $engine => $url) {
            $option_key = 'ping_' . $engine;
            if (isset($settings[$option_key]) && $settings[$option_key]) {
                $ping_url = $url . $sitemap_url;
                
                // Use wp_remote_get to ping the search engine
                wp_remote_get($ping_url, [
                    'blocking' => false,
                    'timeout' => 5,
                ]);
                
                // Log the ping
                self::log_ping($engine, $ping_url);
            }
        }
    }

    /**
     * Log ping attempts
     */
    protected static function log_ping($engine, $url) {
        $log = get_option('softworld_sitemap_ping_log', []);
        $log[] = [
            'time' => current_time('mysql'),
            'engine' => $engine,
            'url' => $url,
            'status' => 'sent'
        ];
        
        // Keep only the last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        update_option('softworld_sitemap_ping_log', $log);
    }

    /**
     * Manually ping search engines (for admin UI)
     */
    public static function manual_ping_search_engines() {
        self::ping_search_engines();
        return true;
    }

    /**
     * Get ping log
     */
    public static function get_ping_log() {
        return get_option('softworld_sitemap_ping_log', []);
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
        'priority_posts' => 0.7,
        'priority_pages' => 0.8,
        'priority_taxonomies' => 0.5,
        'priority_cpt' => 0.6,
        'frequency_posts' => 'weekly',
        'frequency_pages' => 'monthly',
        'frequency_taxonomies' => 'weekly',
        'frequency_cpt' => 'weekly',
        'ping_search_engines' => 1,
        'ping_google' => 1,
        'ping_bing' => 1,
        'ping_yandex' => 0,
        'exclude_images' => 0,
    ];
    
    // Get existing settings if any
    $existing_settings = get_option('softworld_sitemap_settings', []);
    
    // Merge with defaults, preserving existing values
    $settings = wp_parse_args($existing_settings, $default_settings);
    
    update_option('softworld_sitemap_settings', $settings);
});
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
SoftworldIT_SEO_Sitemap::init();