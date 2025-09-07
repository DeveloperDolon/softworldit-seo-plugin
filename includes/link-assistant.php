<?php
/**
 * Link Assistant Main Class
 * 
 * Handles internal and external links analysis, suggestions, and cornerstone content management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Link_Assistant
{
    private $text_domain;

    public function __construct($text_domain = 'softworld-ai-seo')
    {
        $this->text_domain = $text_domain;
        add_action('init', array($this, 'init'));

        // AJAX hooks
        add_action('wp_ajax_get_link_suggestions', array($this, 'ajax_get_link_suggestions'));
        add_action('wp_ajax_update_cornerstone_content', array($this, 'ajax_update_cornerstone_content'));
        add_action('wp_ajax_get_links_report', array($this, 'ajax_get_links_report'));
        add_action('wp_ajax_refresh_links_analysis', array($this, 'ajax_refresh_links_analysis'));
        add_action('wp_ajax_softworld_search_posts', array($this, 'ajax_search_posts'));
    }

    public function init()
    {
        // Schedule daily analysis
        if (!wp_next_scheduled('softworld_daily_links_analysis')) {
            wp_schedule_event(time(), 'daily', 'softworld_daily_links_analysis');
        }
        add_action('softworld_daily_links_analysis', array($this, 'analyze_all_content_links'));
    }

    /**
     * Safely verify AJAX nonce
     */
    private function verify_ajax_nonce()
    {
        $nonce = '';
        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field($_POST['nonce']);
        } elseif (isset($_GET['nonce'])) {
            $nonce = sanitize_text_field($_GET['nonce']);
        }

        if (empty($nonce) || !wp_verify_nonce($nonce, 'softworld_link_assistant_nonce')) {
            wp_send_json_error('Security check failed');
            exit;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            exit;
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['refresh_analysis']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'refresh_analysis')) {
                $this->analyze_all_content_links();
                echo '<div class="updated notice"><p>Links analysis refreshed successfully.</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Link Assistant', $this->text_domain); ?></h1>
            <p><?php _e('Analyze your internal and external links, get suggestions for better linking, and manage cornerstone content.', $this->text_domain); ?>
            </p>

            <div class="softworld-link-assistant-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#links-report" class="nav-tab nav-tab-active" data-tab="links-report">
                        <?php _e('Links Report', $this->text_domain); ?>
                    </a>
                    <a href="#link-suggestions" class="nav-tab" data-tab="link-suggestions">
                        <?php _e('Link Suggestions', $this->text_domain); ?>
                    </a>
                    <a href="#cornerstone-content" class="nav-tab" data-tab="cornerstone-content">
                        <?php _e('Cornerstone Content', $this->text_domain); ?>
                    </a>
                </nav>

                <div id="links-report" class="tab-content active">
                    <?php $this->render_links_report_tab(); ?>
                </div>

                <div id="link-suggestions" class="tab-content">
                    <?php $this->render_link_suggestions_tab(); ?>
                </div>

                <div id="cornerstone-content" class="tab-content">
                    <?php $this->render_cornerstone_content_tab(); ?>
                </div>
            </div>

            <div class="softworld-link-assistant-actions">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('refresh_analysis'); ?>
                    <?php submit_button(__('Refresh Analysis', $this->text_domain), 'secondary', 'refresh_analysis', false); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Render Links Report Tab
     */
    private function render_links_report_tab()
    {
        ?>
        <div class="softworld-links-report">
            <div class="softworld-report-header">
                <h2><?php _e('Links Overview', $this->text_domain); ?></h2>
                <div class="softworld-report-stats">
                    <div class="stat-card">
                        <span class="stat-number" id="total-posts">-</span>
                        <span class="stat-label"><?php _e('Total Posts', $this->text_domain); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" id="internal-links">-</span>
                        <span class="stat-label"><?php _e('Internal Links', $this->text_domain); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" id="external-links">-</span>
                        <span class="stat-label"><?php _e('External Links', $this->text_domain); ?></span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" id="orphaned-content">-</span>
                        <span class="stat-label"><?php _e('Orphaned Content', $this->text_domain); ?></span>
                    </div>
                </div>
            </div>

            <div class="softworld-report-filters">
                <select id="filter-post-type">
                    <option value=""><?php _e('All Post Types', $this->text_domain); ?></option>
                    <?php
                    $post_types = get_post_types(['public' => true], 'objects');
                    if ($post_types) {
                        foreach ($post_types as $post_type) {
                            echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->labels->name) . '</option>';
                        }
                    }
                    ?>
                </select>

                <select id="filter-link-status">
                    <option value=""><?php _e('All Link Status', $this->text_domain); ?></option>
                    <option value="well-linked"><?php _e('Well Linked', $this->text_domain); ?></option>
                    <option value="under-linked"><?php _e('Under Linked', $this->text_domain); ?></option>
                    <option value="over-linked"><?php _e('Over Linked', $this->text_domain); ?></option>
                    <option value="orphaned"><?php _e('Orphaned', $this->text_domain); ?></option>
                </select>

                <button type="button" id="apply-filters" class="button button-secondary">
                    <?php _e('Apply Filters', $this->text_domain); ?>
                </button>
            </div>

            <div id="links-report-table" class="softworld-report-table">
                <!-- Table will be loaded via AJAX -->
            </div>
        </div>
        <?php
    }

    /**
     * Render Link Suggestions Tab
     */
    private function render_link_suggestions_tab()
    {
        ?>
        <div class="softworld-link-suggestions">
            <div class="softworld-suggestions-header">
                <h2><?php _e('Link Suggestions', $this->text_domain); ?></h2>
                <p><?php _e('AI-powered suggestions to improve your internal linking structure.', $this->text_domain); ?></p>
            </div>

            <div class="softworld-suggestions-filters">
                <select id="suggestions-post-type">
                    <option value=""><?php _e('All Post Types', $this->text_domain); ?></option>
                    <?php
                    $post_types = get_post_types(['public' => true], 'objects');
                    if ($post_types) {
                        foreach ($post_types as $post_type) {
                            echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->labels->name) . '</option>';
                        }
                    }
                    ?>
                </select>

                <select id="suggestions-priority">
                    <option value=""><?php _e('All Priorities', $this->text_domain); ?></option>
                    <option value="high"><?php _e('High Priority', $this->text_domain); ?></option>
                    <option value="medium"><?php _e('Medium Priority', $this->text_domain); ?></option>
                    <option value="low"><?php _e('Low Priority', $this->text_domain); ?></option>
                </select>

                <button type="button" id="load-suggestions" class="button button-primary">
                    <?php _e('Load Suggestions', $this->text_domain); ?>
                </button>
            </div>

            <div id="link-suggestions-content" class="softworld-suggestions-content">
                <!-- Suggestions will be loaded via AJAX -->
            </div>
        </div>
        <?php
    }

    /**
     * Render Cornerstone Content Tab
     */
    private function render_cornerstone_content_tab()
    {
        $cornerstone_posts = $this->get_cornerstone_content();
        ?>
        <div class="softworld-cornerstone-content">
            <div class="softworld-cornerstone-header">
                <h2><?php _e('Cornerstone Content', $this->text_domain); ?></h2>
                <p><?php _e('Mark your most important content as cornerstone to prioritize it in link suggestions and SEO analysis.', $this->text_domain); ?>
                </p>
            </div>

            <div class="softworld-cornerstone-search">
                <input type="text" id="cornerstone-search"
                    placeholder="<?php _e('Search content to add as cornerstone...', $this->text_domain); ?>">
                <button type="button" id="search-cornerstone" class="button button-secondary">
                    <?php _e('Search', $this->text_domain); ?>
                </button>
            </div>

            <div id="cornerstone-search-results" class="softworld-search-results" style="display: none;"></div>

            <div class="softworld-cornerstone-list">
                <h3><?php _e('Current Cornerstone Content', $this->text_domain); ?></h3>
                <div id="cornerstone-content-list">
                    <?php if (!empty($cornerstone_posts)): ?>
                        <?php foreach ($cornerstone_posts as $post): ?>
                            <div class="cornerstone-item" data-post-id="<?php echo $post->ID; ?>">
                                <div class="cornerstone-info">
                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                    <?php
                                    $post_type_obj = get_post_type_object($post->post_type);
                                    $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : 'Post';
                                    ?>
                                    <span class="post-type"><?php echo esc_html($post_type_name); ?></span>
                                    <span class="post-date"><?php echo get_the_date('M j, Y', $post); ?></span>
                                </div>
                                <div class="cornerstone-stats">
                                    <span class="internal-links"><?php echo $this->count_internal_links($post->ID); ?> internal
                                        links</span>
                                    <span class="external-links"><?php echo $this->count_external_links($post->ID); ?> external
                                        links</span>
                                </div>
                                <div class="cornerstone-actions">
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">Edit</a>
                                    <button type="button" class="button button-small remove-cornerstone"
                                        data-post-id="<?php echo $post->ID; ?>">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-cornerstone-content">
                            <?php _e('No cornerstone content set. Use the search above to add important content.', $this->text_domain); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Analyze all content for links
     */
    public function analyze_all_content_links()
    {
        try {
            $posts = get_posts([
                'post_type' => get_post_types(['public' => true]),
                'post_status' => 'publish',
                'posts_per_page' => 50, // Limit to prevent memory issues
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_softworld_links_analyzed',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_softworld_links_analyzed',
                        'value' => strtotime('-1 week'),
                        'compare' => '<',
                        'type' => 'NUMERIC'
                    ]
                ]
            ]);

            foreach ($posts as $post) {
                $this->analyze_post_links($post->ID);
            }
        } catch (Exception $e) {
            error_log('Link Assistant analysis error: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Search posts for cornerstone content
     */
    public function ajax_search_posts()
    {
        try {
            // Use the search nonce for this specific function
            $nonce = sanitize_text_field($_GET['nonce'] ?? '');

            if (empty($nonce)) {
                wp_send_json_error('Security check failed - no nonce');
                return;
            }

            // Try both nonce types since you have both in your localization
            $nonce_valid = wp_verify_nonce($nonce, 'softworld_link_assistant_nonce') ||
                wp_verify_nonce($nonce, 'search_posts_nonce');

            if (!$nonce_valid) {
                wp_send_json_error('Security check failed - invalid nonce');
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error('Insufficient permissions');
                return;
            }

            $term = sanitize_text_field($_GET['term'] ?? '');

            if (empty($term)) {
                wp_send_json_error('Search term is required');
                return;
            }

            $posts = get_posts([
                'post_type' => get_post_types(['public' => true]),
                'post_status' => 'publish',
                'posts_per_page' => 10,
                's' => $term,
                'fields' => 'ids'
            ]);

            $results = [];
            foreach ($posts as $post_id) {
                $post_type_obj = get_post_type_object(get_post_type($post_id));
                $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : 'Post';

                $results[] = [
                    'id' => $post_id,
                    'label' => get_the_title($post_id) . ' (' . $post_type_name . ')'
                ];
            }

            wp_send_json_success($results);
        } catch (Exception $e) {
            error_log('Search posts error: ' . $e->getMessage());
            wp_send_json_error('Search failed');
        }
    }

    /**
     * Analyze links for a specific post
     */
    public function analyze_post_links($post_id)
    {
        try {
            $post = get_post($post_id);
            if (!$post)
                return;

            $content = $post->post_content;
            $internal_links = [];
            $external_links = [];
            $site_url = home_url();

            // Extract all links from content
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $url = $match[1];
                $anchor_text = strip_tags($match[2]);

                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    // Internal link
                    $internal_links[] = [
                        'url' => $url,
                        'anchor_text' => $anchor_text,
                        'target_post_id' => url_to_postid($url)
                    ];
                } elseif (strpos($url, 'http') === 0) {
                    // External link
                    $external_links[] = [
                        'url' => $url,
                        'anchor_text' => $anchor_text,
                        'domain' => parse_url($url, PHP_URL_HOST)
                    ];
                }
            }

            // Save analysis results
            update_post_meta($post_id, '_softworld_internal_links', $internal_links);
            update_post_meta($post_id, '_softworld_external_links', $external_links);
            update_post_meta($post_id, '_softworld_internal_links_count', count($internal_links));
            update_post_meta($post_id, '_softworld_external_links_count', count($external_links));
            update_post_meta($post_id, '_softworld_links_analyzed', time());

            // Calculate link density
            $word_count = str_word_count(strip_tags($content));
            $total_links = count($internal_links) + count($external_links);
            $link_density = $word_count > 0 ? ($total_links / $word_count) * 100 : 0;

            update_post_meta($post_id, '_softworld_link_density', $link_density);

            // Determine link status
            $status = $this->determine_link_status($post_id, count($internal_links), count($external_links), $link_density);
            update_post_meta($post_id, '_softworld_link_status', $status);
        } catch (Exception $e) {
            error_log('Analyze post links error: ' . $e->getMessage());
        }
    }

    /**
     * Determine link status based on analysis
     */
    private function determine_link_status($post_id, $internal_count, $external_count, $link_density)
    {
        try {
            // Check if orphaned (no internal links pointing to this post)
            $incoming_links = $this->count_incoming_internal_links($post_id);
            if ($incoming_links === 0) {
                return 'orphaned';
            }

            // Check link density
            if ($link_density > 5) {
                return 'over-linked';
            } elseif ($link_density < 1 && $internal_count < 2) {
                return 'under-linked';
            }

            return 'well-linked';
        } catch (Exception $e) {
            error_log('Determine link status error: ' . $e->getMessage());
            return 'not-analyzed';
        }
    }

    /**
     * Count incoming internal links to a post
     */
    private function count_incoming_internal_links($post_id)
    {
        try {
            global $wpdb;

            $post_url = get_permalink($post_id);
            if (!$post_url)
                return 0;

            $relative_url = str_replace(home_url(), '', $post_url);

            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_status = 'publish' 
                 AND post_type IN ('post', 'page') 
                 AND (post_content LIKE %s OR post_content LIKE %s)",
                '%href="' . esc_sql($post_url) . '"%',
                '%href="' . esc_sql($relative_url) . '"%'
            ));

            return (int) $count;
        } catch (Exception $e) {
            error_log('Count incoming links error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cornerstone content
     */
    public function get_cornerstone_content()
    {
        try {
            return get_posts([
                'post_type' => get_post_types(['public' => true]),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_key' => '_softworld_is_cornerstone',
                'meta_value' => '1',
                'orderby' => 'menu_order title',
                'order' => 'ASC'
            ]);
        } catch (Exception $e) {
            error_log('Get cornerstone content error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count internal links in a post
     */
    public function count_internal_links($post_id)
    {
        $count = get_post_meta($post_id, '_softworld_internal_links_count', true);
        return $count ? (int) $count : 0;
    }

    /**
     * Count external links in a post
     */
    public function count_external_links($post_id)
    {
        $count = get_post_meta($post_id, '_softworld_external_links_count', true);
        return $count ? (int) $count : 0;
    }

    /**
     * AJAX: Get links report
     */
    public function ajax_get_links_report()
    {
        try {
            $this->verify_ajax_nonce();

            $post_type = sanitize_text_field($_POST['post_type'] ?? '');
            $link_status = sanitize_text_field($_POST['link_status'] ?? '');
            $page = max(1, (int) ($_POST['page'] ?? 1));
            $per_page = 20;

            $args = [
                'post_type' => $post_type ?: get_post_types(['public' => true]),
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                'orderby' => 'date',
                'order' => 'DESC'
            ];

            if ($link_status) {
                $args['meta_key'] = '_softworld_link_status';
                $args['meta_value'] = $link_status;
            }

            $posts = get_posts($args);

            // Get statistics
            $stats = $this->get_links_statistics();

            $data = [
                'posts' => [],
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil(count($posts) / $per_page),
                    'total_posts' => $stats['total_posts']
                ]
            ];

            foreach ($posts as $post) {
                $internal_count = $this->count_internal_links($post->ID);
                $external_count = $this->count_external_links($post->ID);
                $link_status = get_post_meta($post->ID, '_softworld_link_status', true);
                $link_density = get_post_meta($post->ID, '_softworld_link_density', true);
                $is_cornerstone = get_post_meta($post->ID, '_softworld_is_cornerstone', true);

                $post_type_obj = get_post_type_object($post->post_type);
                $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : 'Post';

                $data['posts'][] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'post_type' => $post_type_name,
                    'date' => get_the_date('M j, Y', $post),
                    'internal_links' => $internal_count,
                    'external_links' => $external_count,
                    'link_status' => $link_status ?: 'not-analyzed',
                    'link_density' => round((float) $link_density, 2),
                    'is_cornerstone' => $is_cornerstone === '1',
                    'edit_url' => get_edit_post_link($post->ID),
                    'view_url' => get_permalink($post->ID)
                ];
            }

            wp_send_json_success($data);
        } catch (Exception $e) {
            error_log('Get links report error: ' . $e->getMessage());
            wp_send_json_error('Failed to load links report');
        }
    }

    /**
     * Get links statistics
     */
    private function get_links_statistics()
    {
        try {
            global $wpdb;

            $total_posts = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_status = 'publish' 
                AND post_type IN ('post', 'page')
            ");

            $internal_links = $wpdb->get_var("
                SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_softworld_internal_links_count'
            ");

            $external_links = $wpdb->get_var("
                SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} 
                WHERE meta_key = '_softworld_external_links_count'
            ");

            $orphaned_content = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key = '_softworld_link_status' 
                AND pm.meta_value = 'orphaned'
                AND p.post_status = 'publish'
            ");

            return [
                'total_posts' => (int) $total_posts,
                'internal_links' => (int) $internal_links,
                'external_links' => (int) $external_links,
                'orphaned_content' => (int) $orphaned_content
            ];
        } catch (Exception $e) {
            error_log('Get links statistics error: ' . $e->getMessage());
            return [
                'total_posts' => 0,
                'internal_links' => 0,
                'external_links' => 0,
                'orphaned_content' => 0
            ];
        }
    }

    /**
     * AJAX: Get link suggestions
     */
    public function ajax_get_link_suggestions()
    {
        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

            $this->verify_ajax_nonce();

            $post_type = sanitize_text_field($_POST['post_type'] ?? '');
            $priority = sanitize_text_field($_POST['priority'] ?? '');

            // Add debug logging
            error_log('Link Assistant: Starting suggestion generation');
            error_log('Link Assistant: Post type: ' . $post_type);
            error_log('Link Assistant: Priority: ' . $priority);

            $suggestions = $this->generate_link_suggestions($post_type, $priority);

            // Log success
            error_log('Link Assistant: Generated ' . count($suggestions) . ' suggestions');

            wp_send_json_success(['suggestions' => $suggestions]);
        } catch (Exception $e) {
            // Log the full error
            error_log('Link Assistant Error: ' . $e->getMessage());
            error_log('Link Assistant Stack Trace: ' . $e->getTraceAsString());

            wp_send_json_error('Failed to generate suggestions: ' . $e->getMessage());
        }
    }

    /**
     * Generate link suggestions
     */
    private function generate_link_suggestions($post_type = '', $priority = '')
    {
        try {
            $suggestions = [];

            // Get posts that need more internal links
            $args = [
                'post_type' => $post_type ?: get_post_types(['public' => true]),
                'post_status' => 'publish',
                'posts_per_page' => 20,
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => '_softworld_link_status',
                        'value' => 'under-linked'
                    ],
                    [
                        'key' => '_softworld_link_status',
                        'value' => 'orphaned'
                    ]
                ]
            ];

            error_log('Link Assistant: Query args: ' . print_r($args, true));

            $posts_needing_links = get_posts($args);
            error_log('Link Assistant: Found ' . count($posts_needing_links) . ' posts needing links');

            foreach ($posts_needing_links as $post) {
                $suggestion_priority = $this->calculate_suggestion_priority($post->ID);

                if ($priority && $suggestion_priority !== $priority) {
                    continue;
                }

                $related_posts = $this->find_related_posts($post->ID);
                error_log('Link Assistant: Found ' . count($related_posts) . ' related posts for post ID: ' . $post->ID);

                if (!empty($related_posts)) {
                    $post_type_obj = get_post_type_object($post->post_type);
                    $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : 'Post';

                    $suggestions[] = [
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'post_type' => $post_type_name,
                        'priority' => $suggestion_priority,
                        'reason' => $this->get_suggestion_reason($post->ID),
                        'related_posts' => array_slice($related_posts, 0, 5),
                        'edit_url' => get_edit_post_link($post->ID)
                    ];
                }
            }

            return $suggestions;
        } catch (Exception $e) {
            error_log('Generate link suggestions error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Calculate suggestion priority
     */
    private function calculate_suggestion_priority($post_id)
    {
        $link_status = get_post_meta($post_id, '_softworld_link_status', true);
        $is_cornerstone = get_post_meta($post_id, '_softworld_is_cornerstone', true);
        $internal_links_count = $this->count_internal_links($post_id);

        if ($is_cornerstone === '1' || $link_status === 'orphaned') {
            return 'high';
        } elseif ($internal_links_count === 0 || $link_status === 'under-linked') {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get suggestion reason
     */
    private function get_suggestion_reason($post_id)
    {
        $link_status = get_post_meta($post_id, '_softworld_link_status', true);
        $is_cornerstone = get_post_meta($post_id, '_softworld_is_cornerstone', true);
        $internal_links_count = $this->count_internal_links($post_id);

        if ($is_cornerstone === '1') {
            return __('Cornerstone content needs more internal links', $this->text_domain);
        } elseif ($link_status === 'orphaned') {
            return __('No internal links pointing to this content', $this->text_domain);
        } elseif ($internal_links_count === 0) {
            return __('No outgoing internal links', $this->text_domain);
        } elseif ($link_status === 'under-linked') {
            return __('Could benefit from more internal links', $this->text_domain);
        }

        return __('General link improvement recommended', $this->text_domain);
    }

    /**
     * Find related posts for linking suggestions
     */
    private function find_related_posts($post_id)
    {
        try {
            $post = get_post($post_id);
            if (!$post)
                return [];

            $related_posts = [];

            // Get posts from same category/tags
            $terms = wp_get_post_terms($post_id, ['category', 'post_tag'], ['fields' => 'ids']);

            if (!empty($terms) && !is_wp_error($terms)) {
                $related_by_terms = get_posts([
                    'post_type' => $post->post_type,
                    'post_status' => 'publish',
                    'posts_per_page' => 10,
                    'post__not_in' => [$post_id],
                    'tax_query' => [
                        [
                            'taxonomy' => ['category', 'post_tag'],
                            'field' => 'term_id',
                            'terms' => $terms,
                            'operator' => 'IN'
                        ]
                    ]
                ]);

                foreach ($related_by_terms as $related_post) {
                    $related_posts[] = [
                        'id' => $related_post->ID,
                        'title' => $related_post->post_title,
                        'url' => get_permalink($related_post->ID),
                        'relevance' => 'high',
                        'reason' => __('Same category/tag', $this->text_domain)
                    ];
                }
            }

            // Get cornerstone content
            $cornerstone_posts = $this->get_cornerstone_content();
            foreach ($cornerstone_posts as $cornerstone_post) {
                if ($cornerstone_post->ID !== $post_id) {
                    $related_posts[] = [
                        'id' => $cornerstone_post->ID,
                        'title' => $cornerstone_post->post_title,
                        'url' => get_permalink($cornerstone_post->ID),
                        'relevance' => 'high',
                        'reason' => __('Cornerstone content', $this->text_domain)
                    ];
                }
            }

            // Remove duplicates
            $unique_posts = [];
            $seen_ids = [];
            foreach ($related_posts as $related_post) {
                if (!in_array($related_post['id'], $seen_ids)) {
                    $unique_posts[] = $related_post;
                    $seen_ids[] = $related_post['id'];
                }
            }

            return $unique_posts;
        } catch (Exception $e) {
            error_log('Find related posts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * AJAX: Update cornerstone content
     */
    public function ajax_update_cornerstone_content()
    {
        try {
            $this->verify_ajax_nonce();

            $post_id = (int) ($_POST['post_id'] ?? 0);
            $action = sanitize_text_field($_POST['action_type'] ?? '');

            if (!$post_id || !get_post($post_id)) {
                wp_send_json_error('Invalid post ID');
                return;
            }

            if ($action === 'add') {
                update_post_meta($post_id, '_softworld_is_cornerstone', '1');
                $message = __('Post added to cornerstone content', $this->text_domain);
            } elseif ($action === 'remove') {
                delete_post_meta($post_id, '_softworld_is_cornerstone');
                $message = __('Post removed from cornerstone content', $this->text_domain);
            } else {
                wp_send_json_error('Invalid action');
                return;
            }

            wp_send_json_success(['message' => $message]);
        } catch (Exception $e) {
            error_log('Update cornerstone content error: ' . $e->getMessage());
            wp_send_json_error('Failed to update cornerstone content');
        }
    }

    /**
     * AJAX: Refresh links analysis
     */
    public function ajax_refresh_links_analysis()
    {
        try {
            $this->verify_ajax_nonce();

            $post_id = (int) ($_POST['post_id'] ?? 0);

            if ($post_id) {
                $this->analyze_post_links($post_id);
                $message = __('Links analysis refreshed for this post', $this->text_domain);
            } else {
                $this->analyze_all_content_links();
                $message = __('Links analysis refreshed for all content', $this->text_domain);
            }

            wp_send_json_success(['message' => $message]);
        } catch (Exception $e) {
            error_log('Refresh links analysis error: ' . $e->getMessage());
            wp_send_json_error('Failed to refresh analysis');
        }
    }
}
?>