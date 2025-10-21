<?php
/**
 * SEO Audit Template
 */

if (!defined('ABSPATH')) exit;

// Get recent posts for audit
$recent_posts = get_posts([
    'post_type' => ['post', 'page'],
    'posts_per_page' => 20,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get audit history
$ai_generator = softworld_ai_seo()->get_ai_generator();
$audit_history = $ai_generator ? $ai_generator->get_audit_history('', 5) : [];
?>

<div class="wrap softworld-seo-audit">
    <h1><?php _e('AI-Powered SEO Audit', 'softworld-ai-seo'); ?></h1>
    <p class="description"><?php _e('Detect critical errors and get actionable AI-powered suggestions to improve your SEO.', 'softworld-ai-seo'); ?></p>

    <div class="audit-container">
        <!-- Audit Form -->
        <div class="audit-panel">
            <div class="audit-form-section">
                <h2><?php _e('Run SEO Audit', 'softworld-ai-seo'); ?></h2>
                
                <div class="form-row">
                    <label for="audit-post-selector"><?php _e('Select Post/Page', 'softworld-ai-seo'); ?></label>
                    <select id="audit-post-selector" class="widefat">
                        <option value=""><?php _e('-- Select a post or page --', 'softworld-ai-seo'); ?></option>
                        <?php foreach ($recent_posts as $post): ?>
                            <option value="<?php echo esc_attr($post->ID); ?>" data-url="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                <?php echo esc_html($post->post_title); ?> (<?php echo ucfirst($post->post_type); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row" style="margin-top: 15px;">
                    <button type="button" class="button button-primary button-hero" id="btn-run-audit" disabled>
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Run Audit', 'softworld-ai-seo'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="btn-view-history" style="margin-left: 10px;">
                        <span class="dashicons dashicons-backup"></span>
                        <?php _e('View History', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>

            <!-- Audit Results -->
            <div id="audit-results" class="audit-results-section" style="display: none;">
                <div class="audit-header">
                    <h2><?php _e('Audit Results', 'softworld-ai-seo'); ?></h2>
                    <div class="audit-score-badge">
                        <div class="score-circle">
                            <span class="score-value">0</span>
                            <span class="score-max">/100</span>
                        </div>
                        <p class="score-label"><?php _e('SEO Score', 'softworld-ai-seo'); ?></p>
                    </div>
                </div>

                <div class="audit-url-info">
                    <strong><?php _e('Page:', 'softworld-ai-seo'); ?></strong> 
                    <a href="#" target="_blank" id="audited-url"></a>
                </div>

                <!-- Critical Errors -->
                <div class="audit-section critical-section">
                    <h3>
                        <span class="dashicons dashicons-warning"></span>
                        <?php _e('Critical Errors', 'softworld-ai-seo'); ?>
                        <span class="count-badge" id="critical-count">0</span>
                    </h3>
                    <div id="critical-errors-list" class="issues-list"></div>
                </div>

                <!-- Warnings -->
                <div class="audit-section warning-section">
                    <h3>
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('Warnings', 'softworld-ai-seo'); ?>
                        <span class="count-badge" id="warnings-count">0</span>
                    </h3>
                    <div id="warnings-list" class="issues-list"></div>
                </div>

                <!-- Recommendations -->
                <div class="audit-section recommendation-section">
                    <h3>
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php _e('Recommendations', 'softworld-ai-seo'); ?>
                        <span class="count-badge" id="recommendations-count">0</span>
                    </h3>
                    <div id="recommendations-list" class="issues-list"></div>
                </div>

                <div class="audit-actions">
                    <button type="button" class="button" id="btn-export-audit">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Report', 'softworld-ai-seo'); ?>
                    </button>
                    <button type="button" class="button" id="btn-rerun-audit">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Re-run Audit', 'softworld-ai-seo'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Audit History Sidebar -->
        <div class="audit-sidebar">
            <div class="sidebar-section">
                <h3><?php _e('Recent Audits', 'softworld-ai-seo'); ?></h3>
                <div id="audit-history-list">
                    <?php if (empty($audit_history)): ?>
                        <p class="no-audits"><?php _e('No audit history yet.', 'softworld-ai-seo'); ?></p>
                    <?php else: ?>
                        <?php foreach ($audit_history as $audit): ?>
                            <div class="history-item" data-url="<?php echo esc_attr($audit['url']); ?>">
                                <div class="history-score" data-score="<?php echo esc_attr($audit['score']); ?>">
                                    <?php echo esc_html($audit['score']); ?>
                                </div>
                                <div class="history-details">
                                    <div class="history-url"><?php echo esc_html(wp_parse_url($audit['url'], PHP_URL_PATH)); ?></div>
                                    <div class="history-date"><?php echo esc_html(human_time_diff(strtotime($audit['audit_date']), current_time('timestamp'))); ?> <?php _e('ago', 'softworld-ai-seo'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sidebar-section">
                <h3><?php _e('Audit Stats', 'softworld-ai-seo'); ?></h3>
                <div class="audit-stats">
                    <div class="stat-item">
                        <span class="stat-number" id="total-audits">0</span>
                        <span class="stat-label"><?php _e('Total Audits', 'softworld-ai-seo'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="avg-score">0</span>
                        <span class="stat-label"><?php _e('Avg Score', 'softworld-ai-seo'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" style="display: none;">
        <div class="loading-spinner">
            <span class="dashicons dashicons-update spin"></span>
            <p><?php _e('Running AI-powered SEO audit...', 'softworld-ai-seo'); ?></p>
            <p class="loading-subtext"><?php _e('This may take 10-30 seconds', 'softworld-ai-seo'); ?></p>
        </div>
    </div>
</div>