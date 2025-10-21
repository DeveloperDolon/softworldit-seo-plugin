<?php
/**
 * AI SEO Generator Class
 * 
 * Handles OpenAI integration for generating SEO titles and meta descriptions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Softworld_AI_SEO_Generator
{
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;

    public function __construct()
    {
        // Get settings from options
        $this->api_key = get_option('softworld_ai_seo_openai_api_key', false);
        $this->model = get_option('softworld_ai_seo_openai_model', 'gpt-3.5-turbo');
        $this->max_tokens = get_option('softworld_ai_seo_max_tokens', 150);
        $this->temperature = get_option('softworld_ai_seo_temperature', 0.7);

        // Add hooks
        add_action('wp_ajax_softworld_generate_seo_title', [$this, 'ajax_generate_seo_title']);
        add_action('wp_ajax_softworld_generate_seo_description', [$this, 'ajax_generate_seo_description']);
        add_action('wp_ajax_softworld_generate_bulk_seo', [$this, 'ajax_generate_bulk_seo']);
        add_action('wp_ajax_softworld_test_openai_connection', [$this, 'ajax_test_openai_connection']);
        add_action('wp_ajax_softworld_generate_faqs', [$this, 'ajax_generate_faqs']);
        add_action('wp_ajax_softworld_generate_key_points', [$this, 'ajax_generate_key_points']);


        // Add AJAX handlers
        add_action('wp_ajax_softworld_run_seo_audit', [$this, 'ajax_run_seo_audit']);
        add_action('wp_ajax_softworld_analyze_keywords', [$this, 'ajax_analyze_keywords']);
        add_action('wp_ajax_softworld_suggest_keywords', [$this, 'ajax_suggest_keywords']);
        add_action('wp_ajax_softworld_get_audit_history', [$this, 'ajax_get_audit_history']);
        add_action('wp_ajax_softworld_track_keyword', [$this, 'ajax_track_keyword']);
        add_action('wp_ajax_softworld_get_keyword_tracking', [$this, 'ajax_get_keyword_tracking']);
    }

    /**
     * Check if AI features are available
     */
    public function is_ai_available()
    {
        return !empty($this->api_key) && !empty($this->model);
    }

    /**
     * Generate SEO title using OpenAI
     */
    public function generate_seo_title($content, $post_type = 'post', $target_keywords = '', $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        // Prepare the prompt
        $prompt = $this->build_title_prompt($content, $post_type, $target_keywords, $tone);

        // Make API request
        return $this->make_openai_request($prompt, 60); // 60 tokens for title
    }

    /**
     * Generate SEO meta description using OpenAI
     */
    public function generate_seo_description($content, $post_type = 'post', $target_keywords = '', $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        // Prepare the prompt
        $prompt = $this->build_description_prompt($content, $post_type, $target_keywords, $tone);

        // Make API request
        return $this->make_openai_request($prompt, 160); // 160 tokens for description
    }

    public function create_usage_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            usage_type varchar(50) NOT NULL,
            content_length int(11) NOT NULL,
            post_type varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            usage_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY usage_type (usage_type),
            KEY usage_date (usage_date),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Build prompt for SEO title generation
     */
    private function build_title_prompt($content, $post_type, $target_keywords, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 500);
        
        $prompt = "You are an expert SEO copywriter. Generate a compelling SEO title based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content Preview: " . $content_excerpt . "\n";
        
        if (!empty($target_keywords)) {
            $prompt .= "Target Keywords: " . $target_keywords . "\n";
        }
        
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Keep it under 60 characters for optimal SEO\n";
        $prompt .= "- Make it compelling and click-worthy\n";
        $prompt .= "- Include target keywords naturally if provided\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the main value proposition\n\n";
        $prompt .= "Generate only the title, no explanations or quotes:";

        return $prompt;
    }

    /**
     * Build prompt for meta description generation
     */
    private function build_description_prompt($content, $post_type, $target_keywords, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 800);
        
        $prompt = "You are an expert SEO copywriter. Generate a compelling meta description based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content Preview: " . $content_excerpt . "\n";
        
        if (!empty($target_keywords)) {
            $prompt .= "Target Keywords: " . $target_keywords . "\n";
        }
        
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Keep it between 150-160 characters for optimal SEO\n";
        $prompt .= "- Make it compelling and informative\n";
        $prompt .= "- Include target keywords naturally if provided\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Include a clear call-to-action or value proposition\n";
        $prompt .= "- Summarize the main benefit or key takeaway\n\n";
        $prompt .= "Generate only the meta description, no explanations or quotes:";

        return $prompt;
    }

    /**
     * Extract content excerpt for AI processing
     */
    private function extract_content_excerpt($content, $max_length = 500)
    {
        // Remove HTML tags
        $content = wp_strip_all_tags($content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Trim to max length
        if (strlen($content) > $max_length) {
            $content = substr($content, 0, $max_length) . '...';
        }
        
        return trim($content);
    }

    /**
     * Make request to OpenAI API
     */
    private function make_openai_request($prompt, $max_tokens = null)
    {
        $max_tokens = $max_tokens ?: $this->max_tokens;
        
        $body = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => $this->temperature,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($body),
            'timeout' => 30,
            'method' => 'POST'
        ];

        $response = wp_remote_request('https://api.openai.com/v1/chat/completions', $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['choices'][0]['message']['content'])) {
            $error_message = isset($data['error']['message']) ? 
                $data['error']['message'] : 
                __('Unknown API error', 'softworld-ai-seo');
            
            return new WP_Error('api_error', $error_message);
        }

        return trim($data['choices'][0]['message']['content']);
    }

    /**
     * AJAX handler for generating SEO title
     */
    public function ajax_generate_seo_title()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $target_keywords = sanitize_text_field($_POST['target_keywords'] ?? '');
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_seo_title($content, $post_type, $target_keywords, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Log the generation
        $this->log_ai_usage('title', strlen($content), $post_type);

        wp_send_json_success([
            'title' => $result,
            'character_count' => strlen($result)
        ]);
    }

    /**
     * AJAX handler for generating SEO meta description
     */
    public function ajax_generate_seo_description()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $target_keywords = sanitize_text_field($_POST['target_keywords'] ?? '');
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_seo_description($content, $post_type, $target_keywords, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        // Log the generation
        $this->log_ai_usage('description', strlen($content), $post_type);

        wp_send_json_success([
            'description' => $result,
            'character_count' => strlen($result)
        ]);
    }

    /**
     * AJAX handler for bulk SEO generation
     */
    public function ajax_generate_bulk_seo()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $post_ids = array_map('intval', $_POST['post_ids'] ?? []);
        $generate_titles = !empty($_POST['generate_titles']);
        $generate_descriptions = !empty($_POST['generate_descriptions']);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($post_ids)) {
            wp_send_json_error(__('No posts selected', 'softworld-ai-seo'));
        }

        $results = [];
        $errors = [];

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $content = $post->post_title . "\n\n" . $post->post_content;
            $post_result = ['id' => $post_id];

            // Generate title if requested
            if ($generate_titles) {
                $title_result = $this->generate_seo_title($content, $post->post_type, '', $tone);
                if (is_wp_error($title_result)) {
                    $errors[] = sprintf(__('Post %d title: %s', 'softworld-ai-seo'), $post_id, $title_result->get_error_message());
                } else {
                    $post_result['title'] = $title_result;
                    // Optionally auto-save
                    if (!empty($_POST['auto_save'])) {
                        update_post_meta($post_id, '_softworld_ai_seo_title', $title_result);
                    }
                }
            }

            // Generate description if requested
            if ($generate_descriptions) {
                $desc_result = $this->generate_seo_description($content, $post->post_type, '', $tone);
                if (is_wp_error($desc_result)) {
                    $errors[] = sprintf(__('Post %d description: %s', 'softworld-ai-seo'), $post_id, $desc_result->get_error_message());
                } else {
                    $post_result['description'] = $desc_result;
                    // Optionally auto-save
                    if (!empty($_POST['auto_save'])) {
                        update_post_meta($post_id, '_softworld_ai_seo_description', $desc_result);
                    }
                }
            }

            $results[] = $post_result;

            // Log bulk usage
            $this->log_ai_usage('bulk', strlen($content), $post->post_type);
        }

        wp_send_json_success([
            'results' => $results,
            'errors' => $errors,
            'processed' => count($results)
        ]);
    }

    /**
     * AJAX handler for testing OpenAI connection
     */
    public function ajax_test_openai_connection()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $test_prompt = "Generate a simple SEO title for a blog post about WordPress plugins. Keep it under 60 characters.";
        
        $result = $this->make_openai_request($test_prompt, 30);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'connected' => false
            ]);
        }

        wp_send_json_success([
            'message' => __('OpenAI connection successful!', 'softworld-ai-seo'),
            'connected' => true,
            'test_result' => $result
        ]);
    }

    /**
     * Log AI usage for analytics
     */
    private function log_ai_usage($type, $content_length, $post_type)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        // Create table if it doesn't exist
        $this->create_usage_table();

        $wpdb->insert(
            $table_name,
            [
                'usage_type' => $type,
                'content_length' => $content_length,
                'post_type' => $post_type,
                'user_id' => get_current_user_id(),
                'usage_date' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%d', '%s']
        );
    }

    /**
     * Get AI usage statistics
     */
    public function get_usage_stats($days = 30)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'softworld_ai_usage';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return [
                'total_generations' => 0,
                'titles_generated' => 0,
                'descriptions_generated' => 0,
                'bulk_generations' => 0,
                'daily_usage' => []
            ];
        }

        $date_from = date('Y-m-d H:i:s', strtotime("-$days days"));

        $stats = [
            'total_generations' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_date >= %s", $date_from)
            ),
            'titles_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'title' AND usage_date >= %s", $date_from)
            ),
            'descriptions_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'description' AND usage_date >= %s", $date_from)
            ),
            'bulk_generations' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'bulk' AND usage_date >= %s", $date_from)
            ),
            // Add these lines to the get_usage_stats method:
            'faqs_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'faqs' AND usage_date >= %s", $date_from)
            ),
            'key_points_generated' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'key_points' AND usage_date >= %s", $date_from)
            ),
            // Add these stat queries
            'content_generations' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'content_generation' AND usage_date >= %s", $date_from)
            ),
            'content_expansions' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'content_expansion' AND usage_date >= %s", $date_from)
            ),
            'content_rewrites' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'content_rewrite' AND usage_date >= %s", $date_from)
            ),
            'summaries_created' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'content_summary' AND usage_date >= %s", $date_from)
            ),
            'outlines_created' => $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE usage_type = 'outline_generation' AND usage_date >= %s", $date_from)
            ),
        ];

        // Get daily usage for chart
        $daily_usage = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(usage_date) as date, COUNT(*) as count 
                 FROM $table_name 
                 WHERE usage_date >= %s 
                 GROUP BY DATE(usage_date) 
                 ORDER BY date ASC",
                $date_from
            ),
            ARRAY_A
        );

        $stats['daily_usage'] = $daily_usage;

        return $stats;
    }

    /**
     * Generate FAQs using OpenAI
     */
    public function generate_faqs($content, $post_type = 'post', $faq_count = 5, $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        $prompt = $this->build_faqs_prompt($content, $post_type, $faq_count, $tone);
        $result = $this->make_openai_request($prompt, 500);

        if (is_wp_error($result)) {
            return $result;
        }

        // Log the generation
        $this->log_ai_usage('faqs', strlen($content), $post_type);

        return $this->parse_faqs_response($result);
    }

    /**
     * Generate key points using OpenAI
     */
    public function generate_key_points($content, $post_type = 'post', $point_count = 5, $tone = 'professional')
    {
        if (!$this->is_ai_available()) {
            return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
        }

        $prompt = $this->build_key_points_prompt($content, $post_type, $point_count, $tone);
        $result = $this->make_openai_request($prompt, 300);

        if (is_wp_error($result)) {
            return $result;
        }

        // Log the generation
        $this->log_ai_usage('key_points', strlen($content), $post_type);

        return $this->parse_key_points_response($result);
    }

    /**
     * Build prompt for FAQ generation
     */
    private function build_faqs_prompt($content, $post_type, $faq_count, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 1000);
        
        $prompt = "You are an expert content strategist. Generate frequently asked questions (FAQs) based on the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content: " . $content_excerpt . "\n";
        $prompt .= "Number of FAQs: " . $faq_count . "\n";
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Generate exactly " . $faq_count . " relevant questions and answers\n";
        $prompt .= "- Questions should be what readers would commonly ask about this topic\n";
        $prompt .= "- Answers should be informative but concise (2-3 sentences each)\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the most important aspects of the content\n\n";
        $prompt .= "Format your response as:\n";
        $prompt .= "Q1: [Question]\nA1: [Answer]\n\nQ2: [Question]\nA2: [Answer]\n\n";
        $prompt .= "Continue this pattern for all questions.\n\n";
        $prompt .= "Generate the FAQs now:";

        return $prompt;
    }

    /**
     * Build prompt for key points generation
     */
    private function build_key_points_prompt($content, $post_type, $point_count, $tone)
    {
        $content_excerpt = $this->extract_content_excerpt($content, 1000);
        
        $prompt = "You are an expert content strategist. Extract the most important key points from the following content.\n\n";
        $prompt .= "Content Type: " . ucfirst($post_type) . "\n";
        $prompt .= "Content: " . $content_excerpt . "\n";
        $prompt .= "Number of Points: " . $point_count . "\n";
        $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Extract exactly " . $point_count . " key points\n";
        $prompt .= "- Each point should be a clear, actionable takeaway\n";
        $prompt .= "- Keep each point concise (1-2 sentences)\n";
        $prompt .= "- Match the specified tone\n";
        $prompt .= "- Focus on the most valuable insights\n";
        $prompt .= "- Prioritize practical and useful information\n\n";
        $prompt .= "Format your response as a numbered list:\n";
        $prompt .= "1. [Key Point]\n2. [Key Point]\n3. [Key Point]\n\n";
        $prompt .= "Generate the key points now:";

        return $prompt;
    }

    /**
     * Parse FAQs response from AI
     */
    private function parse_faqs_response($response)
    {
        $faqs = [];
        $lines = explode("\n", trim($response));
        $current_question = '';
        $current_answer = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line starts with Q followed by number and colon
            if (preg_match('/^Q\d+:\s*(.+)/', $line, $matches)) {
                // If we have a previous FAQ, save it
                if (!empty($current_question) && !empty($current_answer)) {
                    $faqs[] = [
                        'question' => $current_question,
                        'answer' => $current_answer
                    ];
                }
                $current_question = trim($matches[1]);
                $current_answer = '';
            }
            // Check if line starts with A followed by number and colon
            elseif (preg_match('/^A\d+:\s*(.+)/', $line, $matches)) {
                $current_answer = trim($matches[1]);
            }
            // If we're building an answer, continue adding to it
            elseif (!empty($current_question) && !empty($current_answer)) {
                $current_answer .= ' ' . $line;
            }
        }
        
        // Add the last FAQ if exists
        if (!empty($current_question) && !empty($current_answer)) {
            $faqs[] = [
                'question' => $current_question,
                'answer' => $current_answer
            ];
        }
        
        return $faqs;
    }

    /**
     * Parse key points response from AI
     */
    private function parse_key_points_response($response)
    {
        $points = [];
        $lines = explode("\n", trim($response));
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Check if line starts with number and period/dot
            if (preg_match('/^\d+\.\s*(.+)/', $line, $matches)) {
                $points[] = trim($matches[1]);
            }
            // Also handle bullet points
            elseif (preg_match('/^[-•]\s*(.+)/', $line, $matches)) {
                $points[] = trim($matches[1]);
            }
        }
        
        return $points;
    }

    /**
     * AJAX handler for generating FAQs
     */
    public function ajax_generate_faqs()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $faq_count = intval($_POST['faq_count'] ?? 5);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_faqs($content, $post_type, $faq_count, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'faqs' => $result,
            'count' => count($result)
        ]);
    }

    /**
     * AJAX handler for generating key points
     */
    public function ajax_generate_key_points()
    {
        check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
        }

        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $point_count = intval($_POST['point_count'] ?? 5);
        $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

        if (empty($content)) {
            wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
        }

        $result = $this->generate_key_points($content, $post_type, $point_count, $tone);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'key_points' => $result,
            'count' => count($result)
        ]);
    }

    /**
 * Generate content using AI based on topic/keywords
 */
public function generate_content($topic, $content_type = 'paragraph', $word_count = 300, $tone = 'professional', $keywords = '')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_content_prompt($topic, $content_type, $word_count, $tone, $keywords);
    $max_tokens = $this->calculate_tokens_for_words($word_count);
    
    $result = $this->make_openai_request($prompt, $max_tokens);

    if (is_wp_error($result)) {
        return $result;
    }

    // Log the generation
    $this->log_ai_usage('content_generation', strlen($topic), $content_type);

    return trim($result);
}

/**
 * Expand existing content
 */
public function expand_content($existing_content, $expansion_type = 'elaborate', $word_count = 200, $tone = 'professional')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_expansion_prompt($existing_content, $expansion_type, $word_count, $tone);
    $max_tokens = $this->calculate_tokens_for_words($word_count);
    
    $result = $this->make_openai_request($prompt, $max_tokens);

    if (is_wp_error($result)) {
        return $result;
    }

    $this->log_ai_usage('content_expansion', strlen($existing_content), $expansion_type);

    return trim($result);
}

/**
 * Rewrite/rephrase content
 */
public function rewrite_content($content, $rewrite_style = 'improve', $tone = 'professional')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_rewrite_prompt($content, $rewrite_style, $tone);
    $max_tokens = $this->calculate_tokens_for_content($content);
    
    $result = $this->make_openai_request($prompt, $max_tokens);

    if (is_wp_error($result)) {
        return $result;
    }

    $this->log_ai_usage('content_rewrite', strlen($content), $rewrite_style);

    return trim($result);
}

/**
 * Summarize content
 */
public function summarize_content($content, $summary_length = 'short', $format = 'paragraph')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_summary_prompt($content, $summary_length, $format);
    
    $max_tokens = 300;
    if ($summary_length === 'long') $max_tokens = 500;
    if ($summary_length === 'short') $max_tokens = 150;
    
    $result = $this->make_openai_request($prompt, $max_tokens);

    if (is_wp_error($result)) {
        return $result;
    }

    $this->log_ai_usage('content_summary', strlen($content), $summary_length);

    return trim($result);
}

/**
 * Generate outline for content
 */
public function generate_outline($topic, $sections = 5, $detail_level = 'detailed')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_outline_prompt($topic, $sections, $detail_level);
    $result = $this->make_openai_request($prompt, 400);

    if (is_wp_error($result)) {
        return $result;
    }

    $this->log_ai_usage('outline_generation', strlen($topic), 'outline');

    return $this->parse_outline_response($result);
}

/**
 * Build content generation prompt
 */
private function build_content_prompt($topic, $content_type, $word_count, $tone, $keywords)
{
    $prompt = "You are a professional content writer. Generate high-quality content based on the following specifications.\n\n";
    $prompt .= "Topic: " . $topic . "\n";
    $prompt .= "Content Type: " . ucfirst($content_type) . "\n";
    $prompt .= "Target Word Count: Approximately " . $word_count . " words\n";
    $prompt .= "Tone: " . ucfirst($tone) . "\n";
    
    if (!empty($keywords)) {
        $prompt .= "Keywords to Include: " . $keywords . "\n";
    }
    
    $prompt .= "\nRequirements:\n";
    $prompt .= "- Write engaging, original content\n";
    $prompt .= "- Maintain the specified tone throughout\n";
    $prompt .= "- Include relevant keywords naturally if provided\n";
    $prompt .= "- Make it reader-friendly and informative\n";
    $prompt .= "- Use proper grammar and formatting\n";
    
    if ($content_type === 'blog_post') {
        $prompt .= "- Structure with introduction, body paragraphs, and conclusion\n";
        $prompt .= "- Add subheadings where appropriate\n";
    } elseif ($content_type === 'product_description') {
        $prompt .= "- Highlight key features and benefits\n";
        $prompt .= "- Include a compelling call-to-action\n";
    }
    
    $prompt .= "\nGenerate the content now:";
    
    return $prompt;
}

/**
 * Build expansion prompt
 */
private function build_expansion_prompt($existing_content, $expansion_type, $word_count, $tone)
{
    $content_excerpt = $this->extract_content_excerpt($existing_content, 800);
    
    $prompt = "You are a professional content writer. Expand the following content.\n\n";
    $prompt .= "Existing Content: " . $content_excerpt . "\n";
    $prompt .= "Expansion Style: " . ucfirst($expansion_type) . "\n";
    $prompt .= "Additional Words Needed: Approximately " . $word_count . " words\n";
    $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
    
    $prompt .= "Requirements:\n";
    
    if ($expansion_type === 'elaborate') {
        $prompt .= "- Add more details and explanations to the existing points\n";
        $prompt .= "- Provide examples and evidence where appropriate\n";
    } elseif ($expansion_type === 'add_examples') {
        $prompt .= "- Add relevant examples to illustrate the points\n";
        $prompt .= "- Use real-world scenarios\n";
    } elseif ($expansion_type === 'add_context') {
        $prompt .= "- Provide background information and context\n";
        $prompt .= "- Explain the significance and implications\n";
    }
    
    $prompt .= "- Maintain consistency with the original content\n";
    $prompt .= "- Keep the same tone and style\n";
    $prompt .= "- Ensure smooth transitions\n\n";
    $prompt .= "Generate the expanded content:";
    
    return $prompt;
}

/**
 * Build rewrite prompt
 */
private function build_rewrite_prompt($content, $rewrite_style, $tone)
{
    $content_excerpt = $this->extract_content_excerpt($content, 1000);
    
    $prompt = "You are a professional content editor. Rewrite the following content.\n\n";
    $prompt .= "Original Content: " . $content_excerpt . "\n";
    $prompt .= "Rewrite Style: " . ucfirst($rewrite_style) . "\n";
    $prompt .= "Tone: " . ucfirst($tone) . "\n\n";
    
    $prompt .= "Requirements:\n";
    
    if ($rewrite_style === 'improve') {
        $prompt .= "- Enhance clarity and readability\n";
        $prompt .= "- Fix any grammar or style issues\n";
        $prompt .= "- Maintain the original meaning\n";
    } elseif ($rewrite_style === 'simplify') {
        $prompt .= "- Use simpler language and shorter sentences\n";
        $prompt .= "- Make it more accessible to general readers\n";
        $prompt .= "- Remove jargon where possible\n";
    } elseif ($rewrite_style === 'professional') {
        $prompt .= "- Use more formal and professional language\n";
        $prompt .= "- Enhance credibility and authority\n";
        $prompt .= "- Use industry-appropriate terminology\n";
    } elseif ($rewrite_style === 'casual') {
        $prompt .= "- Use conversational and friendly language\n";
        $prompt .= "- Make it more approachable\n";
        $prompt .= "- Use contractions and informal expressions where appropriate\n";
    }
    
    $prompt .= "- Preserve the core message and key points\n";
    $prompt .= "- Maintain similar length to the original\n\n";
    $prompt .= "Generate the rewritten content:";
    
    return $prompt;
}

/**
 * Build summary prompt
 */
private function build_summary_prompt($content, $summary_length, $format)
{
    $content_excerpt = $this->extract_content_excerpt($content, 2000);
    
    $prompt = "You are a professional content summarizer. Create a summary of the following content.\n\n";
    $prompt .= "Content: " . $content_excerpt . "\n";
    $prompt .= "Summary Length: " . ucfirst($summary_length) . "\n";
    $prompt .= "Format: " . ucfirst($format) . "\n\n";
    
    $prompt .= "Requirements:\n";
    
    if ($summary_length === 'short') {
        $prompt .= "- Keep it very concise (2-3 sentences)\n";
    } elseif ($summary_length === 'medium') {
        $prompt .= "- Create a moderate summary (1 paragraph)\n";
    } else {
        $prompt .= "- Create a comprehensive summary (2-3 paragraphs)\n";
    }
    
    if ($format === 'bullet_points') {
        $prompt .= "- Format as bullet points\n";
        $prompt .= "- Each point should be a key takeaway\n";
    } else {
        $prompt .= "- Format as flowing paragraph(s)\n";
    }
    
    $prompt .= "- Capture the main ideas and key points\n";
    $prompt .= "- Maintain the essence of the original content\n\n";
    $prompt .= "Generate the summary now:";
    
    return $prompt;
}

/**
 * Build outline prompt
 */
private function build_outline_prompt($topic, $sections, $detail_level)
{
    $prompt = "You are a professional content strategist. Create a detailed outline for the following topic.\n\n";
    $prompt .= "Topic: " . $topic . "\n";
    $prompt .= "Number of Main Sections: " . $sections . "\n";
    $prompt .= "Detail Level: " . ucfirst($detail_level) . "\n\n";
    
    $prompt .= "Requirements:\n";
    $prompt .= "- Create " . $sections . " main sections\n";
    
    if ($detail_level === 'detailed') {
        $prompt .= "- Include 3-5 subsections under each main section\n";
        $prompt .= "- Add brief descriptions for each point\n";
    } elseif ($detail_level === 'basic') {
        $prompt .= "- Include 2-3 subsections under each main section\n";
        $prompt .= "- Keep it concise\n";
    }
    
    $prompt .= "- Use hierarchical numbering (1, 1.1, 1.2, etc.)\n";
    $prompt .= "- Ensure logical flow and structure\n";
    $prompt .= "- Cover all important aspects of the topic\n\n";
    $prompt .= "Format:\n";
    $prompt .= "1. Main Section Title\n";
    $prompt .= "   1.1 Subsection\n";
    $prompt .= "   1.2 Subsection\n\n";
    $prompt .= "Generate the outline now:";
    
    return $prompt;
}

/**
 * Parse outline response
 */
private function parse_outline_response($response)
{
    $lines = explode("\n", trim($response));
    $outline = [];
    $current_section = null;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Main section (1., 2., 3., etc.)
        if (preg_match('/^(\d+)\.\s*(.+)/', $line, $matches)) {
            $current_section = $matches[1];
            $outline[$current_section] = [
                'title' => trim($matches[2]),
                'subsections' => []
            ];
        }
        // Subsection (1.1, 1.2, etc.)
        elseif (preg_match('/^(\d+)\.(\d+)\s*(.+)/', $line, $matches)) {
            $parent = $matches[1];
            if (isset($outline[$parent])) {
                $outline[$parent]['subsections'][] = trim($matches[3]);
            }
        }
    }
    
    return $outline;
}

/**
 * Calculate tokens needed for word count
 */
private function calculate_tokens_for_words($word_count)
{
    // Rough estimate: 1 token ≈ 0.75 words
    return intval($word_count * 1.5);
}

/**
 * Calculate tokens for existing content
 */
private function calculate_tokens_for_content($content)
{
    $word_count = str_word_count(strip_tags($content));
    return intval($word_count * 1.5) + 200; // Add buffer
}

// Add AJAX handlers

/**
 * AJAX: Generate Content
 */
public function ajax_generate_content()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $topic = sanitize_textarea_field($_POST['topic'] ?? '');
    $content_type = sanitize_text_field($_POST['content_type'] ?? 'paragraph');
    $word_count = intval($_POST['word_count'] ?? 300);
    $tone = sanitize_text_field($_POST['tone'] ?? 'professional');
    $keywords = sanitize_text_field($_POST['keywords'] ?? '');

    if (empty($topic)) {
        wp_send_json_error(__('Topic is required', 'softworld-ai-seo'));
    }

    $result = $this->generate_content($topic, $content_type, $word_count, $tone, $keywords);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'content' => $result,
        'word_count' => str_word_count(strip_tags($result))
    ]);
}

/**
 * AJAX: Expand Content
 */
public function ajax_expand_content()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $content = sanitize_textarea_field($_POST['content'] ?? '');
    $expansion_type = sanitize_text_field($_POST['expansion_type'] ?? 'elaborate');
    $word_count = intval($_POST['word_count'] ?? 200);
    $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

    if (empty($content)) {
        wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
    }

    $result = $this->expand_content($content, $expansion_type, $word_count, $tone);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'expanded_content' => $result,
        'word_count' => str_word_count(strip_tags($result))
    ]);
}

/**
 * AJAX: Rewrite Content
 */
public function ajax_rewrite_content()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $content = sanitize_textarea_field($_POST['content'] ?? '');
    $rewrite_style = sanitize_text_field($_POST['rewrite_style'] ?? 'improve');
    $tone = sanitize_text_field($_POST['tone'] ?? 'professional');

    if (empty($content)) {
        wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
    }

    $result = $this->rewrite_content($content, $rewrite_style, $tone);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'rewritten_content' => $result,
        'word_count' => str_word_count(strip_tags($result))
    ]);
}

/**
 * AJAX: Summarize Content
 */
public function ajax_summarize_content()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $content = sanitize_textarea_field($_POST['content'] ?? '');
    $summary_length = sanitize_text_field($_POST['summary_length'] ?? 'short');
    $format = sanitize_text_field($_POST['format'] ?? 'paragraph');

    if (empty($content)) {
        wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
    }

    $result = $this->summarize_content($content, $summary_length, $format);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'summary' => $result,
        'word_count' => str_word_count(strip_tags($result))
    ]);
}

/**
 * AJAX: Generate Outline
 */
public function ajax_generate_outline()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $topic = sanitize_textarea_field($_POST['topic'] ?? '');
    $sections = intval($_POST['sections'] ?? 5);
    $detail_level = sanitize_text_field($_POST['detail_level'] ?? 'detailed');

    if (empty($topic)) {
        wp_send_json_error(__('Topic is required', 'softworld-ai-seo'));
    }

    $result = $this->generate_outline($topic, $sections, $detail_level);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success([
        'outline' => $result,
        'sections_count' => count($result)
    ]);
}


/**
 * Perform Advanced SEO Audit with AI Analysis
 */
public function perform_seo_audit($url, $content, $meta_data = [])
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    // Get page data for analysis
    $page_data = $this->prepare_audit_data($url, $content, $meta_data);
    
    $prompt = $this->build_audit_prompt($page_data);
    $result = $this->make_openai_request($prompt, 800);

    if (is_wp_error($result)) {
        return $result;
    }

    // Parse audit results
    $audit_results = $this->parse_audit_response($result);
    
    // Log the audit
    $this->log_ai_usage('seo_audit', strlen($content), 'audit');
    
    // Store audit results
    $this->store_audit_results($url, $audit_results);

    return $audit_results;
}

/**
 * Analyze keyword performance and get AI suggestions
 */
public function analyze_keywords($content, $target_keywords, $current_rankings = [])
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_keyword_analysis_prompt($content, $target_keywords, $current_rankings);
    $result = $this->make_openai_request($prompt, 600);

    if (is_wp_error($result)) {
        return $result;
    }

    // Parse keyword suggestions
    $keyword_analysis = $this->parse_keyword_analysis($result);
    
    // Log the analysis
    $this->log_ai_usage('keyword_analysis', strlen($content), 'keywords');

    return $keyword_analysis;
}

/**
 * Get AI-powered keyword suggestions based on topic
 */
public function suggest_keywords($topic, $seed_keywords = '', $intent = 'informational')
{
    if (!$this->is_ai_available()) {
        return new WP_Error('no_api_key', __('OpenAI API key is not configured', 'softworld-ai-seo'));
    }

    $prompt = $this->build_keyword_suggestion_prompt($topic, $seed_keywords, $intent);
    $result = $this->make_openai_request($prompt, 400);

    if (is_wp_error($result)) {
        return $result;
    }

    // Parse keyword suggestions
    $suggestions = $this->parse_keyword_suggestions($result);
    
    // Log the generation
    $this->log_ai_usage('keyword_suggestions', strlen($topic), 'keywords');

    return $suggestions;
}

/**
 * Prepare audit data from page/post
 */
private function prepare_audit_data($url, $content, $meta_data)
{
    $word_count = str_word_count(strip_tags($content));
    $has_headings = preg_match('/<h[1-6]/i', $content);
    $has_images = preg_match('/<img/i', $content);
    $internal_links = substr_count($content, get_site_url());
    $external_links = preg_match_all('/<a\s+(?:[^>]*?\s+)?href=(["\'])(https?:\/\/(?!' . preg_quote(get_site_url(), '/') . ').*?)\1/i', $content);
    
    return [
        'url' => $url,
        'title' => $meta_data['title'] ?? '',
        'meta_description' => $meta_data['description'] ?? '',
        'content_length' => $word_count,
        'has_headings' => $has_headings,
        'has_images' => $has_images,
        'internal_links' => $internal_links,
        'external_links' => $external_links,
        'content_preview' => $this->extract_content_excerpt($content, 500),
        'target_keywords' => $meta_data['keywords'] ?? ''
    ];
}

/**
 * Build SEO audit prompt
 */
private function build_audit_prompt($page_data)
{
    $prompt = "You are an expert SEO auditor. Analyze the following webpage data and provide a comprehensive SEO audit.\n\n";
    
    $prompt .= "PAGE DATA:\n";
    $prompt .= "URL: " . $page_data['url'] . "\n";
    $prompt .= "Title: " . ($page_data['title'] ?: 'NOT SET') . "\n";
    $prompt .= "Meta Description: " . ($page_data['meta_description'] ?: 'NOT SET') . "\n";
    $prompt .= "Content Length: " . $page_data['content_length'] . " words\n";
    $prompt .= "Has Headings: " . ($page_data['has_headings'] ? 'Yes' : 'No') . "\n";
    $prompt .= "Has Images: " . ($page_data['has_images'] ? 'Yes' : 'No') . "\n";
    $prompt .= "Internal Links: " . $page_data['internal_links'] . "\n";
    $prompt .= "External Links: " . $page_data['external_links'] . "\n";
    
    if (!empty($page_data['target_keywords'])) {
        $prompt .= "Target Keywords: " . $page_data['target_keywords'] . "\n";
    }
    
    $prompt .= "\nContent Preview:\n" . $page_data['content_preview'] . "\n\n";
    
    $prompt .= "AUDIT REQUIREMENTS:\n";
    $prompt .= "Analyze and provide detailed feedback on:\n\n";
    $prompt .= "CRITICAL_ERRORS:\n";
    $prompt .= "- List any critical SEO issues that must be fixed immediately\n";
    $prompt .= "- Format: ERROR: [Issue] | ACTION: [What to do]\n\n";
    $prompt .= "WARNINGS:\n";
    $prompt .= "- List important issues that should be addressed soon\n";
    $prompt .= "- Format: WARNING: [Issue] | SUGGESTION: [How to improve]\n\n";
    $prompt .= "RECOMMENDATIONS:\n";
    $prompt .= "- List optimization opportunities\n";
    $prompt .= "- Format: IMPROVE: [Area] | TIP: [Specific action]\n\n";
    $prompt .= "SCORE:\n";
    $prompt .= "- Overall SEO score out of 100\n";
    $prompt .= "- Format: SCORE: [number]/100\n\n";
    $prompt .= "Provide the audit now:";
    
    return $prompt;
}

/**
 * Build keyword analysis prompt
 */
private function build_keyword_analysis_prompt($content, $target_keywords, $current_rankings)
{
    $content_excerpt = $this->extract_content_excerpt($content, 800);
    
    $prompt = "You are an expert SEO keyword analyst. Analyze keyword usage and provide optimization suggestions.\n\n";
    
    $prompt .= "CONTENT PREVIEW:\n" . $content_excerpt . "\n\n";
    $prompt .= "TARGET KEYWORDS: " . $target_keywords . "\n\n";
    
    if (!empty($current_rankings)) {
        $prompt .= "CURRENT RANKINGS:\n";
        foreach ($current_rankings as $keyword => $position) {
            $prompt .= "- " . $keyword . ": Position " . $position . "\n";
        }
        $prompt .= "\n";
    }
    
    $prompt .= "ANALYSIS REQUIREMENTS:\n\n";
    $prompt .= "KEYWORD_DENSITY:\n";
    $prompt .= "- Analyze if keywords appear naturally and at optimal frequency\n";
    $prompt .= "- Format: DENSITY: [keyword] - [percentage]% | STATUS: [Good/Low/High]\n\n";
    $prompt .= "KEYWORD_PLACEMENT:\n";
    $prompt .= "- Check if keywords appear in important positions (title, first paragraph, headings)\n";
    $prompt .= "- Format: PLACEMENT: [keyword] | FOUND_IN: [locations] | MISSING_FROM: [locations]\n\n";
    $prompt .= "RELATED_KEYWORDS:\n";
    $prompt .= "- Suggest semantically related keywords to include\n";
    $prompt .= "- Format: SUGGEST: [keyword] | REASON: [why it's relevant]\n\n";
    $prompt .= "LSI_KEYWORDS:\n";
    $prompt .= "- Suggest LSI (Latent Semantic Indexing) keywords\n";
    $prompt .= "- Format: LSI: [keyword] | CONTEXT: [how to use]\n\n";
    $prompt .= "OPPORTUNITIES:\n";
    $prompt .= "- Identify keyword opportunities for ranking improvement\n";
    $prompt .= "- Format: OPPORTUNITY: [keyword] | ACTION: [specific recommendation]\n\n";
    $prompt .= "Provide the analysis now:";
    
    return $prompt;
}

/**
 * Build keyword suggestion prompt
 */
private function build_keyword_suggestion_prompt($topic, $seed_keywords, $intent)
{
    $prompt = "You are an expert keyword research specialist. Suggest high-value keywords for the following topic.\n\n";
    
    $prompt .= "TOPIC: " . $topic . "\n";
    
    if (!empty($seed_keywords)) {
        $prompt .= "SEED KEYWORDS: " . $seed_keywords . "\n";
    }
    
    $prompt .= "SEARCH INTENT: " . ucfirst($intent) . "\n\n";
    
    $prompt .= "PROVIDE KEYWORD SUGGESTIONS IN THESE CATEGORIES:\n\n";
    $prompt .= "PRIMARY_KEYWORDS:\n";
    $prompt .= "- 3-5 main keywords with high relevance\n";
    $prompt .= "- Format: PRIMARY: [keyword] | DIFFICULTY: [Easy/Medium/Hard] | VOLUME: [High/Medium/Low]\n\n";
    $prompt .= "LONG_TAIL_KEYWORDS:\n";
    $prompt .= "- 5-8 long-tail keywords (3+ words)\n";
    $prompt .= "- Format: LONG_TAIL: [keyword phrase] | INTENT: [what user wants]\n\n";
    $prompt .= "QUESTION_KEYWORDS:\n";
    $prompt .= "- 3-5 question-based keywords\n";
    $prompt .= "- Format: QUESTION: [keyword question] | TYPE: [What/How/Why/When]\n\n";
    $prompt .= "RELATED_TOPICS:\n";
    $prompt .= "- 3-5 related topic keywords\n";
    $prompt .= "- Format: RELATED: [keyword] | CONNECTION: [how it relates]\n\n";
    $prompt .= "COMPETITOR_KEYWORDS:\n";
    $prompt .= "- 3-5 keywords likely used by competitors\n";
    $prompt .= "- Format: COMPETITOR: [keyword] | OPPORTUNITY: [why target it]\n\n";
    $prompt .= "Provide the keyword suggestions now:";
    
    return $prompt;
}

/**
 * Parse audit response
 */
private function parse_audit_response($response)
{
    $audit = [
        'score' => 0,
        'critical_errors' => [],
        'warnings' => [],
        'recommendations' => [],
        'raw_response' => $response
    ];
    
    $lines = explode("\n", $response);
    $current_section = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Extract score
        if (preg_match('/SCORE:\s*(\d+)\/100/i', $line, $matches)) {
            $audit['score'] = intval($matches[1]);
            continue;
        }
        
        // Parse critical errors
        if (preg_match('/ERROR:\s*(.+?)\s*\|\s*ACTION:\s*(.+)/i', $line, $matches)) {
            $audit['critical_errors'][] = [
                'issue' => trim($matches[1]),
                'action' => trim($matches[2]),
                'severity' => 'critical'
            ];
            continue;
        }
        
        // Parse warnings
        if (preg_match('/WARNING:\s*(.+?)\s*\|\s*SUGGESTION:\s*(.+)/i', $line, $matches)) {
            $audit['warnings'][] = [
                'issue' => trim($matches[1]),
                'suggestion' => trim($matches[2]),
                'severity' => 'warning'
            ];
            continue;
        }
        
        // Parse recommendations
        if (preg_match('/IMPROVE:\s*(.+?)\s*\|\s*TIP:\s*(.+)/i', $line, $matches)) {
            $audit['recommendations'][] = [
                'area' => trim($matches[1]),
                'tip' => trim($matches[2]),
                'severity' => 'info'
            ];
            continue;
        }
    }
    
    return $audit;
}

/**
 * Parse keyword analysis response
 */
private function parse_keyword_analysis($response)
{
    $analysis = [
        'density' => [],
        'placement' => [],
        'related_keywords' => [],
        'lsi_keywords' => [],
        'opportunities' => [],
        'raw_response' => $response
    ];
    
    $lines = explode("\n", $response);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Parse density
        if (preg_match('/DENSITY:\s*(.+?)\s*-\s*([\d.]+)%\s*\|\s*STATUS:\s*(\w+)/i', $line, $matches)) {
            $analysis['density'][] = [
                'keyword' => trim($matches[1]),
                'percentage' => floatval($matches[2]),
                'status' => strtolower(trim($matches[3]))
            ];
            continue;
        }
        
        // Parse placement
        if (preg_match('/PLACEMENT:\s*(.+?)\s*\|\s*FOUND_IN:\s*(.+?)\s*\|\s*MISSING_FROM:\s*(.+)/i', $line, $matches)) {
            $analysis['placement'][] = [
                'keyword' => trim($matches[1]),
                'found_in' => array_map('trim', explode(',', $matches[2])),
                'missing_from' => array_map('trim', explode(',', $matches[3]))
            ];
            continue;
        }
        
        // Parse related keywords
        if (preg_match('/SUGGEST:\s*(.+?)\s*\|\s*REASON:\s*(.+)/i', $line, $matches)) {
            $analysis['related_keywords'][] = [
                'keyword' => trim($matches[1]),
                'reason' => trim($matches[2])
            ];
            continue;
        }
        
        // Parse LSI keywords
        if (preg_match('/LSI:\s*(.+?)\s*\|\s*CONTEXT:\s*(.+)/i', $line, $matches)) {
            $analysis['lsi_keywords'][] = [
                'keyword' => trim($matches[1]),
                'context' => trim($matches[2])
            ];
            continue;
        }
        
        // Parse opportunities
        if (preg_match('/OPPORTUNITY:\s*(.+?)\s*\|\s*ACTION:\s*(.+)/i', $line, $matches)) {
            $analysis['opportunities'][] = [
                'keyword' => trim($matches[1]),
                'action' => trim($matches[2])
            ];
            continue;
        }
    }
    
    return $analysis;
}

/**
 * Parse keyword suggestions response
 */
private function parse_keyword_suggestions($response)
{
    $suggestions = [
        'primary_keywords' => [],
        'long_tail_keywords' => [],
        'question_keywords' => [],
        'related_topics' => [],
        'competitor_keywords' => [],
        'raw_response' => $response
    ];
    
    $lines = explode("\n", $response);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Parse primary keywords
        if (preg_match('/PRIMARY:\s*(.+?)\s*\|\s*DIFFICULTY:\s*(\w+)\s*\|\s*VOLUME:\s*(\w+)/i', $line, $matches)) {
            $suggestions['primary_keywords'][] = [
                'keyword' => trim($matches[1]),
                'difficulty' => strtolower(trim($matches[2])),
                'volume' => strtolower(trim($matches[3]))
            ];
            continue;
        }
        
        // Parse long-tail keywords
        if (preg_match('/LONG_TAIL:\s*(.+?)\s*\|\s*INTENT:\s*(.+)/i', $line, $matches)) {
            $suggestions['long_tail_keywords'][] = [
                'keyword' => trim($matches[1]),
                'intent' => trim($matches[2])
            ];
            continue;
        }
        
        // Parse question keywords
        if (preg_match('/QUESTION:\s*(.+?)\s*\|\s*TYPE:\s*(\w+)/i', $line, $matches)) {
            $suggestions['question_keywords'][] = [
                'keyword' => trim($matches[1]),
                'type' => trim($matches[2])
            ];
            continue;
        }
        
        // Parse related topics
        if (preg_match('/RELATED:\s*(.+?)\s*\|\s*CONNECTION:\s*(.+)/i', $line, $matches)) {
            $suggestions['related_topics'][] = [
                'keyword' => trim($matches[1]),
                'connection' => trim($matches[2])
            ];
            continue;
        }
        
        // Parse competitor keywords
        if (preg_match('/COMPETITOR:\s*(.+?)\s*\|\s*OPPORTUNITY:\s*(.+)/i', $line, $matches)) {
            $suggestions['competitor_keywords'][] = [
                'keyword' => trim($matches[1]),
                'opportunity' => trim($matches[2])
            ];
            continue;
        }
    }
    
    return $suggestions;
}

/**
 * Store audit results in database
 */
public function store_audit_results($url, $audit_results)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_seo_audits';
    
    // Create table if not exists
    $this->create_audit_table();
    
    $wpdb->insert(
        $table_name,
        [
            'url' => $url,
            'score' => $audit_results['score'],
            'critical_errors' => json_encode($audit_results['critical_errors']),
            'warnings' => json_encode($audit_results['warnings']),
            'recommendations' => json_encode($audit_results['recommendations']),
            'audit_date' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s', '%d']
    );
    
    return $wpdb->insert_id;
}

/**
 * Create audit table
 */
public function create_audit_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_seo_audits';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        url varchar(500) NOT NULL,
        score int(3) NOT NULL DEFAULT 0,
        critical_errors longtext,
        warnings longtext,
        recommendations longtext,
        audit_date datetime DEFAULT CURRENT_TIMESTAMP,
        user_id bigint(20) NOT NULL,
        PRIMARY KEY (id),
        KEY url (url(191)),
        KEY audit_date (audit_date),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Get audit history
 */
public function get_audit_history($url = '', $limit = 10)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_seo_audits';
    
    if ($url) {
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE url = %s ORDER BY audit_date DESC LIMIT %d",
            $url,
            $limit
        ), ARRAY_A);
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY audit_date DESC LIMIT %d",
        $limit
    ), ARRAY_A);
}

/**
 * AJAX: Run SEO Audit
 */
public function ajax_run_seo_audit()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $post_id = intval($_POST['post_id'] ?? 0);
    
    if (!$post_id) {
        wp_send_json_error(__('Invalid post ID', 'softworld-ai-seo'));
    }

    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(__('Post not found', 'softworld-ai-seo'));
    }

    $url = get_permalink($post_id);
    $content = $post->post_content;
    $meta_data = [
        'title' => get_post_meta($post_id, '_softworld_ai_seo_title', true) ?: $post->post_title,
        'description' => get_post_meta($post_id, '_softworld_ai_seo_description', true),
        'keywords' => get_post_meta($post_id, '_softworld_ai_seo_keywords', true)
    ];

    $result = $this->perform_seo_audit($url, $content, $meta_data);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success($result);
}

/**
 * AJAX: Analyze Keywords
 */
public function ajax_analyze_keywords()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $content = sanitize_textarea_field($_POST['content'] ?? '');
    $target_keywords = sanitize_text_field($_POST['target_keywords'] ?? '');
    $current_rankings = json_decode(stripslashes($_POST['current_rankings'] ?? '{}'), true);

    if (empty($content)) {
        wp_send_json_error(__('Content is required', 'softworld-ai-seo'));
    }

    $result = $this->analyze_keywords($content, $target_keywords, $current_rankings);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success($result);
}

/**
 * AJAX: Suggest Keywords
 */
public function ajax_suggest_keywords()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $topic = sanitize_textarea_field($_POST['topic'] ?? '');
    $seed_keywords = sanitize_text_field($_POST['seed_keywords'] ?? '');
    $intent = sanitize_text_field($_POST['intent'] ?? 'informational');

    if (empty($topic)) {
        wp_send_json_error(__('Topic is required', 'softworld-ai-seo'));
    }

    $result = $this->suggest_keywords($topic, $seed_keywords, $intent);

    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }

    wp_send_json_success($result);
}

/**
 * AJAX: Get Audit History
 */
public function ajax_get_audit_history()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $url = sanitize_text_field($_POST['url'] ?? '');
    $limit = intval($_POST['limit'] ?? 10);

    $history = $this->get_audit_history($url, $limit);

    // Decode JSON fields
    foreach ($history as &$audit) {
        $audit['critical_errors'] = json_decode($audit['critical_errors'], true);
        $audit['warnings'] = json_decode($audit['warnings'], true);
        $audit['recommendations'] = json_decode($audit['recommendations'], true);
    }

    wp_send_json_success($history);
}

/**
 * AJAX: Track Keyword
 */
public function ajax_track_keyword()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $keyword = sanitize_text_field($_POST['keyword'] ?? '');
    $url = sanitize_text_field($_POST['url'] ?? '');
    $position = intval($_POST['position'] ?? 0);

    if (empty($keyword) || empty($url)) {
        wp_send_json_error(__('Keyword and URL are required', 'softworld-ai-seo'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_keyword_tracking';
    
    // Create table if not exists
    $this->create_keyword_tracking_table();

    $wpdb->insert(
        $table_name,
        [
            'keyword' => $keyword,
            'url' => $url,
            'position' => $position,
            'checked_date' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ],
        ['%s', '%s', '%d', '%s', '%d']
    );

    wp_send_json_success(__('Keyword tracked successfully', 'softworld-ai-seo'));
}

/**
 * AJAX: Get Keyword Tracking
 */
public function ajax_get_keyword_tracking()
{
    check_ajax_referer('softworld_ai_seo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('Permission denied', 'softworld-ai-seo'));
    }

    $url = sanitize_text_field($_POST['url'] ?? '');
    $days = intval($_POST['days'] ?? 30);

    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_keyword_tracking';

    $query = "SELECT keyword, position, checked_date 
              FROM $table_name 
              WHERE checked_date >= DATE_SUB(NOW(), INTERVAL %d DAY)";
    
    $params = [$days];
    
    if ($url) {
        $query .= " AND url = %s";
        $params[] = $url;
    }
    
    $query .= " ORDER BY checked_date DESC";

    $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    // Group by keyword for trend analysis
    $tracking_data = [];
    foreach ($results as $row) {
        $keyword = $row['keyword'];
        if (!isset($tracking_data[$keyword])) {
            $tracking_data[$keyword] = [
                'keyword' => $keyword,
                'history' => []
            ];
        }
        $tracking_data[$keyword]['history'][] = [
            'position' => $row['position'],
            'date' => $row['checked_date']
        ];
    }

    wp_send_json_success(array_values($tracking_data));
}

/**
 * Create keyword tracking table
 */
public function create_keyword_tracking_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'softworld_keyword_tracking';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        url varchar(500) NOT NULL,
        position int(4) NOT NULL DEFAULT 0,
        checked_date datetime DEFAULT CURRENT_TIMESTAMP,
        user_id bigint(20) NOT NULL,
        PRIMARY KEY (id),
        KEY keyword (keyword),
        KEY url (url(191)),
        KEY checked_date (checked_date),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
}