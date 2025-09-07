<?php
/**
 * Email Report Template
 * Variables available: $report_data, $frequency
 */

if (!defined('ABSPATH')) {
    exit;
}

$site_name = get_bloginfo('name');
$site_url = get_bloginfo('url');
$report_date = date('F j, Y');
$frequency_label = ucfirst($frequency);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($frequency_label); ?> SEO Report - <?php echo esc_html($site_name); ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 30px 20px;
        }
        
        .section {
            margin-bottom: 30px;
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .section-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1d2327;
        }
        
        .section-content {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 15px 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e1e5e9;
        }
        
        .stat-number {
            display: block;
            font-size: 24px;
            font-weight: 700;
            color: #0073aa;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #646970;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .issue-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .issue-item {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 4px solid;
            background-color: #f9f9f9;
        }
        
        .issue-high {
            border-left-color: #d63638;
            background-color: #fef7f7;
        }
        
        .issue-medium {
            border-left-color: #dba617;
            background-color: #fffbf1;
        }
        
        .issue-low {
            border-left-color: #00a32a;
            background-color: #f6fdf6;
        }
        
        .no-issues {
            color: #00a32a;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            padding: 20px;
        }
        
        .top-pages {
            border-collapse: collapse;
            width: 100%;
        }
        
        .top-pages th,
        .top-pages td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .top-pages th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #1d2327;
            font-size: 14px;
        }
        
        .top-pages td {
            font-size: 13px;
        }
        
        .page-title {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .query-params {
            font-family: 'Courier New', monospace;
            background-color: #f1f3f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e1e5e9;
        }
        
        .footer p {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #646970;
        }
        
        .footer a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            margin-top: 15px;
        }
        
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 20px 15px;
            }
            
            .section-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo esc_html($frequency_label); ?> SEO Report</h1>
            <p><?php echo esc_html($site_name); ?> • <?php echo esc_html($report_date); ?></p>
        </div>
        
        <div class="content">
            <!-- Traffic Overview -->
            <?php if (isset($report_data['traffic'])): ?>
            <div class="section">
                <div class="section-header">
                    <h2>🚀 Traffic Overview</h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['traffic']['total_visits']); ?></span>
                            <span class="stat-label">Total Visits</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['traffic']['organic_traffic']); ?></span>
                            <span class="stat-label">Organic Traffic</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo esc_html($report_data['traffic']['bounce_rate']); ?></span>
                            <span class="stat-label">Bounce Rate</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo esc_html($report_data['traffic']['avg_session_duration']); ?></span>
                            <span class="stat-label">Avg Duration</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($report_data['traffic']['top_pages'])): ?>
                    <h3>Top Performing Pages</h3>
                    <table class="top-pages">
                        <thead>
                            <tr>
                                <th>Page Title</th>
                                <th>Visits</th>
                                <th>Engagement</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['traffic']['top_pages'] as $page): ?>
                            <tr>
                                <td class="page-title"><?php echo esc_html($page['title']); ?></td>
                                <td><?php echo number_format($page['visits']); ?></td>
                                <td><?php echo esc_html($page['engagement']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Keywords Performance -->
            <?php if (isset($report_data['keywords'])): ?>
            <div class="section">
                <div class="section-header">
                    <h2>🎯 Keywords Performance</h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['keywords']['total_keywords']); ?></span>
                            <span class="stat-label">Total Keywords</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['keywords']['avg_position'], 1); ?></span>
                            <span class="stat-label">Avg Position</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['keywords']['clicks']); ?></span>
                            <span class="stat-label">Total Clicks</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['keywords']['impressions']); ?></span>
                            <span class="stat-label">Impressions</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Technical Issues -->
            <?php if (isset($report_data['issues'])): ?>
            <div class="section">
                <div class="section-header">
                    <h2>🔧 Technical SEO Issues</h2>
                </div>
                <div class="section-content">
                    <?php if (empty($report_data['issues'])): ?>
                        <div class="no-issues">✅ No critical issues detected!</div>
                    <?php else: ?>
                        <ul class="issue-list">
                            <?php foreach ($report_data['issues'] as $issue): ?>
                            <li class="issue-item issue-<?php echo esc_attr($issue['severity']); ?>">
                                <?php echo esc_html($issue['description']); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content Analysis -->
            <?php if (isset($report_data['content'])): ?>
            <div class="section">
                <div class="section-header">
                    <h2>📝 Content Analysis</h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['content']['total_posts']); ?></span>
                            <span class="stat-label">Total Posts</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['content']['avg_word_count']); ?></span>
                            <span class="stat-label">Avg Word Count</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['content']['posts_without_images']); ?></span>
                            <span class="stat-label">No Images</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['content']['posts_without_internal_links']); ?></span>
                            <span class="stat-label">No Internal Links</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Query Args Monitoring -->
            <?php if (isset($report_data['query_args'])): ?>
            <div class="section">
                <div class="section-header">
                    <h2>🔍 Query Parameters Monitoring</h2>
                </div>
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['query_args']['new_parameters']); ?></span>
                            <span class="stat-label">New This Week</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['query_args']['high_impact_parameters']); ?></span>
                            <span class="stat-label">High Impact</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($report_data['query_args']['total_monitored']); ?></span>
                            <span class="stat-label">Total Monitored</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo count($report_data['query_args']['top_parameters']); ?></span>
                            <span class="stat-label">Active Parameters</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($report_data['query_args']['top_parameters'])): ?>
                    <h3>Most Active Parameters</h3>
                    <table class="top-pages">
                        <thead>
                            <tr>
                                <th>Query Parameter</th>
                                <th>Hit Count</th>
                                <th>Impact Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['query_args']['top_parameters'] as $param): ?>
                            <tr>
                                <td><code class="query-params"><?php echo esc_html($param->query_args); ?></code></td>
                                <td><?php echo number_format($param->hit_count); ?></td>
                                <td><?php echo number_format($param->impact_score); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This report was generated automatically by the Softworld AI SEO Plugin.</p>
            <p>Visit your WordPress admin to manage settings and view detailed analytics.</p>
            <a href="<?php echo admin_url('admin.php?page=softworld-ai-seo-advanced-settings'); ?>" class="cta-button">
                Manage SEO Settings
            </a>
        </div>
    </div>
</body>
</html>