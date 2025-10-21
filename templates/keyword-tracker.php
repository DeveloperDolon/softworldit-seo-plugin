<?php
/**
 * Keyword Tracker Template
 */

if (!defined('ABSPATH')) exit;

// Get posts for keyword tracking
$posts = get_posts([
    'post_type' => ['post', 'page'],
    'posts_per_page' => 50,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);
?>

<div class="wrap softworld-keyword-tracker">
    <h1><?php _e('AI-Powered Keyword Tracker', 'softworld-ai-seo'); ?></h1>
    <p class="description"><?php _e('Track your keyword performance and get AI-powered suggestions for improvement.', 'softworld-ai-seo'); ?></p>

    <div class="tracker-tabs">
        <button class="tracker-tab active" data-tab="suggestions"><?php _e('Keyword Suggestions', 'softworld-ai-seo'); ?></button>
        <button class="tracker-tab" data-tab="analysis"><?php _e('Keyword Analysis', 'softworld-ai-seo'); ?></button>
        <button class="tracker-tab" data-tab="tracking"><?php _e('Keyword Tracking', 'softworld-ai-seo'); ?></button>
    </div>

    <div class="tracker-content">
        
        <!-- Keyword Suggestions Tab -->
        <div class="tracker-panel active" id="suggestions-panel">
            <div class="panel-header">
                <h2><?php _e('AI Keyword Suggestions', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Get AI-powered keyword suggestions based on your topic.', 'softworld-ai-seo'); ?></p>
            </div>

            <div class="keyword-form">
                <div class="form-row">
                    <label for="suggestion-topic"><?php _e('Topic or Seed Keywords', 'softworld-ai-seo'); ?></label>
                    <textarea id="suggestion-topic" rows="3" placeholder="<?php _e('Enter your topic or seed keywords...', 'softworld-ai-seo'); ?>"></textarea>
                </div>

                <div class="form-row-group">
                    <div class="form-row">
                        <label for="suggestion-intent"><?php _e('Search Intent', 'softworld-ai-seo'); ?></label>
                        <select id="suggestion-intent">
                            <option value="informational"><?php _e('Informational', 'softworld-ai-seo'); ?></option>
                            <option value="commercial"><?php _e('Commercial', 'softworld-ai-seo'); ?></option>
                            <option value="transactional"><?php _e('Transactional', 'softworld-ai-seo'); ?></option>
                            <option value="navigational"><?php _e('Navigational', 'softworld-ai-seo'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="button button-primary" id="btn-suggest-keywords">
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php _e('Generate Suggestions', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>

            <div id="suggestions-results" class="keyword-results" style="display: none;">
                <div class="results-header">
                    <h3><?php _e('Suggested Keywords', 'softworld-ai-seo'); ?></h3>
                </div>

                <div class="keyword-categories">
                    <!-- Primary Keywords -->
                    <div class="keyword-category">
                        <h4><?php _e('Primary Keywords', 'softworld-ai-seo'); ?></h4>
                        <div id="primary-keywords-list" class="keyword-list"></div>
                    </div>

                    <!-- Long-tail Keywords -->
                    <div class="keyword-category">
                        <h4><?php _e('Long-tail Keywords', 'softworld-ai-seo'); ?></h4>
                        <div id="longtail-keywords-list" class="keyword-list"></div>
                    </div>

                    <!-- Question Keywords -->
                    <div class="keyword-category">
                        <h4><?php _e('Question Keywords', 'softworld-ai-seo'); ?></h4>
                        <div id="question-keywords-list" class="keyword-list"></div>
                    </div>

                    <!-- Related Topics -->
                    <div class="keyword-category">
                        <h4><?php _e('Related Topics', 'softworld-ai-seo'); ?></h4>
                        <div id="related-topics-list" class="keyword-list"></div>
                    </div>

                    <!-- Competitor Keywords -->
                    <div class="keyword-category">
                        <h4><?php _e('Competitor Keywords', 'softworld-ai-seo'); ?></h4>
                        <div id="competitor-keywords-list" class="keyword-list"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keyword Analysis Tab -->
        <div class="tracker-panel" id="analysis-panel">
            <div class="panel-header">
                <h2><?php _e('Content Keyword Analysis', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Analyze keyword usage in your content and get optimization recommendations.', 'softworld-ai-seo'); ?></p>
            </div>

            <div class="keyword-form">
                <div class="form-row">
                    <label for="analysis-post"><?php _e('Select Post/Page', 'softworld-ai-seo'); ?></label>
                    <select id="analysis-post" class="widefat">
                        <option value=""><?php _e('-- Select a post or page --', 'softworld-ai-seo'); ?></option>
                        <?php foreach ($posts as $post): ?>
                            <option value="<?php echo esc_attr($post->ID); ?>">
                                <?php echo esc_html($post->post_title); ?> (<?php echo ucfirst($post->post_type); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="analysis-keywords"><?php _e('Target Keywords', 'softworld-ai-seo'); ?></label>
                    <input type="text" id="analysis-keywords" placeholder="<?php _e('keyword1, keyword2, keyword3', 'softworld-ai-seo'); ?>">
                </div>

                <div class="form-actions">
                    <button type="button" class="button button-primary" id="btn-analyze-keywords" disabled>
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('Analyze Keywords', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>

            <div id="analysis-results" class="keyword-results" style="display: none;">
                <div class="results-header">
                    <h3><?php _e('Analysis Results', 'softworld-ai-seo'); ?></h3>
                </div>

                <div class="analysis-sections">
                    <!-- Keyword Density -->
                    <div class="analysis-section">
                        <h4><?php _e('Keyword Density', 'softworld-ai-seo'); ?></h4>
                        <div id="density-results" class="analysis-content"></div>
                    </div>

                    <!-- Keyword Placement -->
                    <div class="analysis-section">
                        <h4><?php _e('Keyword Placement', 'softworld-ai-seo'); ?></h4>
                        <div id="placement-results" class="analysis-content"></div>
                    </div>

                    <!-- Related Keywords -->
                    <div class="analysis-section">
                        <h4><?php _e('Related Keywords to Add', 'softworld-ai-seo'); ?></h4>
                        <div id="related-results" class="analysis-content"></div>
                    </div>

                    <!-- LSI Keywords -->
                    <div class="analysis-section">
                        <h4><?php _e('LSI Keywords', 'softworld-ai-seo'); ?></h4>
                        <div id="lsi-results" class="analysis-content"></div>
                    </div>

                    <!-- Opportunities -->
                    <div class="analysis-section">
                        <h4><?php _e('Optimization Opportunities', 'softworld-ai-seo'); ?></h4>
                        <div id="opportunities-results" class="analysis-content"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keyword Tracking Tab -->
        <div class="tracker-panel" id="tracking-panel">
            <div class="panel-header">
                <h2><?php _e('Keyword Position Tracking', 'softworld-ai-seo'); ?></h2>
                <p class="description"><?php _e('Track your keyword rankings over time.', 'softworld-ai-seo'); ?></p>
            </div>

            <div class="tracking-controls">
                <div class="form-row-group">
                    <div class="form-row">
                        <label for="tracking-url"><?php _e('Filter by URL', 'softworld-ai-seo'); ?></label>
                        <select id="tracking-url" class="widefat">
                            <option value=""><?php _e('All Pages', 'softworld-ai-seo'); ?></option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                    <?php echo esc_html($post->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="tracking-days"><?php _e('Time Period', 'softworld-ai-seo'); ?></label>
                        <select id="tracking-days">
                            <option value="7"><?php _e('Last 7 days', 'softworld-ai-seo'); ?></option>
                            <option value="30" selected><?php _e('Last 30 days', 'softworld-ai-seo'); ?></option>
                            <option value="90"><?php _e('Last 90 days', 'softworld-ai-seo'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="button button-primary" id="btn-load-tracking">
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e('Load Tracking Data', 'softworld-ai-seo'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="btn-add-keyword">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Keyword', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>

            <div id="tracking-results" class="tracking-results" style="display: none;">
                <div id="tracking-chart-container">
                    <canvas id="keyword-tracking-chart"></canvas>
                </div>

                <div id="tracking-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Keyword', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Current Position', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Previous Position', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Change', 'softworld-ai-seo'); ?></th>
                                <th><?php _e('Trend', 'softworld-ai-seo'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="tracking-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Keyword Modal -->
    <div id="add-keyword-modal" class="keyword-modal" style="display: none;">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h3><?php _e('Add Keyword to Track', 'softworld-ai-seo'); ?></h3>
            
            <div class="modal-form">
                <div class="form-row">
                    <label for="modal-keyword"><?php _e('Keyword', 'softworld-ai-seo'); ?></label>
                    <input type="text" id="modal-keyword" placeholder="<?php _e('Enter keyword', 'softworld-ai-seo'); ?>">
                </div>

                <div class="form-row">
                    <label for="modal-url"><?php _e('URL', 'softworld-ai-seo'); ?></label>
                    <select id="modal-url" class="widefat">
                        <?php foreach ($posts as $post): ?>
                            <option value="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                <?php echo esc_html($post->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="modal-position"><?php _e('Current Position', 'softworld-ai-seo'); ?></label>
                    <input type="number" id="modal-position" value="0" min="0" max="100">
                </div>

                <div class="modal-actions">
                    <button type="button" class="button button-primary" id="btn-save-keyword">
                        <?php _e('Save Keyword', 'softworld-ai-seo'); ?>
                    </button>
                    <button type="button" class="button" id="btn-cancel-keyword">
                        <?php _e('Cancel', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <span class="dashicons dashicons-update spin"></span>
            <p><?php _e('AI is analyzing...', 'softworld-ai-seo'); ?></p>
        </div>
    </div>
</div>