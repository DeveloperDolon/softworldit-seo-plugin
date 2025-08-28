<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SoftworldIT_SEO_Sitemap {
    const QUERY_FLAG = 'softworld_sitemap';
    const QUERY_TYPE = 'softworld_type';
    const QUERY_PAGE = 'softworld_page';
    const CHUNK_SIZE = 1000;

    public static function init() {
        add_action( 'init', [ __CLASS__, 'add_rewrite' ] );
        add_filter( 'query_vars', [ __CLASS__, 'register_query_vars' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_render' ] );
        add_filter( 'robots_txt', [ __CLASS__, 'robots_txt_link' ], 99, 2 );
    }

    public static function add_rewrite() {
        add_rewrite_tag( '%' . self::QUERY_FLAG . '%', '([0-1])' );
        add_rewrite_tag( '%' . self::QUERY_TYPE . '%', '(posts|pages)' );
        add_rewrite_tag( '%' . self::QUERY_PAGE . '%', '([0-9]+)' );

        add_rewrite_rule( '^sitemap\.xml$', 'index.php?' . self::QUERY_FLAG . '=1', 'top' );
        add_rewrite_rule( '^sitemap-(posts|pages)-([0-9]+)\.xml$', 'index.php?' . self::QUERY_FLAG . '=1&' . self::QUERY_TYPE . '=$matches[1]&' . self::QUERY_PAGE . '=$matches[2]', 'top' );
    }

    public static function register_query_vars( $vars ) {
        $vars[] = self::QUERY_FLAG;
        $vars[] = self::QUERY_TYPE;
        $vars[] = self::QUERY_PAGE;
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

        header( 'Content-Type: application/xml; charset=utf-8' );

        if ( $type && $page > 0 ) {
            echo self::render_sitemap_body( $type, $page );
        } else {
            echo self::render_sitemap_index();
        }
        exit;
    }

    protected static function render_sitemap_index() {
        $now         = esc_html( gmdate( 'c' ) );
        $post_chunks = self::count_chunks_for( 'post' );
        $page_chunks = self::count_chunks_for( 'page' );

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        for ( $i = 1; $i <= $post_chunks; $i++ ) {
            $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-posts-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
        }
        for ( $i = 1; $i <= $page_chunks; $i++ ) {
            $xml .= "<sitemap><loc>" . esc_url( home_url( "/sitemap-pages-{$i}.xml" ) ) . "</loc><lastmod>{$now}</lastmod></sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    protected static function render_sitemap_body( $group, $page_number ) {
        $post_type = $group === 'pages' ? 'page' : 'post';
        $offset    = ( max( 1, $page_number ) - 1 ) * self::CHUNK_SIZE;

        $q = new WP_Query( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => self::CHUNK_SIZE,
            'offset'         => $offset,
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ] );

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $q->posts as $post_id ) {
            $loc     = esc_url( get_permalink( $post_id ) );
            $lastmod = esc_html( get_post_modified_time( 'c', true, $post_id ) );
            $xml    .= "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><changefreq>weekly</changefreq><priority>0.7</priority></url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    protected static function count_chunks_for( $post_type ) {
        $counts = wp_count_posts( $post_type );
        return max( 1, ceil( (int) ( $counts->publish ?? 0 ) / self::CHUNK_SIZE ) );
    }
}

register_activation_hook( __FILE__, function() {
    SoftworldIT_SEO_Sitemap::add_rewrite();
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
SoftworldIT_SEO_Sitemap::init();
