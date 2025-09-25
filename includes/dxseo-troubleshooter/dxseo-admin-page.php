<?php if (!defined('ABSPATH')) exit;

$php_version = phpversion();
$dx_debug = ini_get('display_errors') || (defined('WP_DEBUG') && WP_DEBUG);
$js_errors = get_option('dxseo_js_errors', []);
$temp_users = get_option('dxseo_temp_users', []);
$dxmsg = $_GET['dxmsg'] ?? '';
$created_user = isset($_GET['u']) ? intval($_GET['u']) : 0;
$created_pass = '';
if ($created_user) {
    $created_pass = get_transient('dxseo_temp_pass_' . $created_user);
}
$auto_scan_results = get_option('dxseo_auto_scan_results', []);
$pagespeed_key = get_option('dxseo_pagespeed_api_key', '');
$pagespeed_results = get_option('dxseo_pagespeed_results', []);
$gsc_keywords = get_option('dxseo_gsc_keywords', []);

// echo $undefined_variable;

?>

<div class="wrap dxseo-wrap">
    <h1>Troubleshooting &amp; Support Infrastructure</h1>

    <?php if ($dxmsg === 'created'): ?>
        <div class="notice notice-success">
            <p>Temporary admin created. Username: <strong><?php echo esc_html($temp_users[$created_user]['username'] ?? ''); ?></strong>
                <?php if ($created_pass): ?> Password: <code><?php echo esc_html($created_pass); ?></code> (shown only once) <?php else: ?> (Password was shown earlier or expired) <?php endif; ?>
            </p>
        </div>
    <?php elseif ($dxmsg === 'exists'): ?>
        <div class="notice notice-error">
            <p>Username already exists.</p>
        </div>
    <?php elseif ($dxmsg === 'missing'): ?>
        <div class="notice notice-error">
            <p>Missing username or email.</p>
        </div>
    <?php elseif ($dxmsg === 'error'): ?>
        <div class="notice notice-error">
            <p>Failed to create user. Check username/email validity.</p>
        </div>
    <?php elseif ($dxmsg === 'aioseo_imported'): ?>
        <div class="notice notice-success">
            <p>All In One SEO import completed. Options imported: <?php echo intval($_GET['imported'] ?? 0); ?>, Postmeta copied: <?php echo intval($_GET['postmeta'] ?? 0); ?>.</p>
        </div>
    <?php elseif ($dxmsg === 'elementor_imported'): ?>
        <div class="notice notice-success">
            <p>Elementor SEO import completed. Posts affected: <?php echo intval($_GET['copied'] ?? 0); ?>.</p>
        </div>
    <?php elseif ($dxmsg === 'edd_imported'): ?>
        <div class="notice notice-success">
            <p>EDD SEO import completed. Downloads affected: <?php echo intval($_GET['copied'] ?? 0); ?>.</p>
        </div>
    <?php elseif ($dxmsg === 'pagespeed_saved'): ?>
        <div class="notice notice-success">
            <p>PageSpeed API key saved.</p>
        </div>
    <?php elseif ($dxmsg === 'gsc_imported'): ?>
        <div class="notice notice-success">
            <p>GSC CSV imported. Rows processed: <?php echo intval($_GET['count'] ?? 0); ?>.</p>
        </div>
    <?php elseif ($dxmsg === 'gsc_error'): ?>
        <div class="notice notice-error">
            <p>GSC import failed. Error: <?php echo esc_html($_GET['err'] ?? 'unknown'); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($dxmsg === 'gsc_cleared'): ?>
        <div class="notice notice-success">
            <p>All imported GSC data cleared successfully.</p>
        </div>
    <?php endif; ?>


    <h2>Troubleshooting</h2>
    <table class="widefat dx-table">
        <thead>
            <tr>
                <th>Issue</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Enable debugging in WordPress</strong><br><small>Runtime debug (display errors) for this request. For persistent debug, add WP_DEBUG constants to wp-config.php.</small></td>
                <td><button class="button" id="dx-enable-debug">Enable runtime debug</button></td>
            </tr>
            <tr>
    <td>
        <strong>Fix a 404 error when viewing sitemap</strong><br>
        <small>Flush rewrite rules and check Yoast/AIOSEO status.</small>
    </td>
    <td>
        <button class="button" id="dx-flush-rewrites">Flush rewrite rules</button>
        <div id="dxseo-debug-output" style="margin-top:10px; max-height:250px; overflow:auto; background:#f9f9f9; border:1px solid #ddd; padding:8px; display:none;">
            <!-- Rewrite rules list will appear here -->
        </div>
    </td>
</tr>

            <tr>
                <td><strong>Fix JavaScript errors</strong><br><small>Capture JS errors in admin area (client-side) so you can inspect them here.</small></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('dxseo_clear_js_errors'); ?>
                        <input type="hidden" name="action" value="dxseo_clear_js_errors">
                        <button class="button">Clear logged JS errors</button>
                    </form>
                    <div class="dx-js-logs">
                        <h4>Recent JS errors (captured from admin pages)</h4>
                        <?php if (empty($js_errors)): ?>
                            <p>No JS errors logged yet.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach (array_slice(array_reverse($js_errors), 0, 20) as $log): ?>
                                    <li><strong><?php echo esc_html($log['time']); ?></strong> — <code><?php echo esc_html($log['payload']); ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>Troubleshoot Action Scheduler issues</strong><br><small>Detect Action Scheduler and run WP-Cron.</small></td>
                <td>
                    <?php if (class_exists('ActionScheduler')): ?>
                        <p>Action Scheduler detected.</p>
                    <?php else: ?>
                        <p>Action Scheduler not detected on this site.</p>
                    <?php endif; ?>
                    <button class="button" id="dx-run-wp-cron">Run WP-Cron now</button>
                </td>
            </tr>
            <tr>
                <td><strong>Supported PHP versions</strong><br><small>Shows current PHP version and recommendation.</small></td>
                <td>
                    <p>Current PHP: <strong><?php echo esc_html($php_version); ?></strong></p>
                    <p>Recommended: PHP 8.0 or greater for modern plugins. Ensure hosting supports at least 7.4.</p>
                </td>
            </tr>
            <tr>
                <td><strong>Privately sharing login credentials (one-time secret)</strong><br><small>Generate a one-time secret URL that shows the secret once and then deletes it.</small></td>
                <td>
                    <form id="dx-one-time-form" onsubmit="return false;">
                        <textarea name="payload" id="dx-payload" rows="4" style="width:100%;"></textarea>
                        <p>Expires in (minutes): <input type="number" id="dx-ttl" value="60" min="1" style="width:80px"></p>
                        <button class="button" id="dx-generate-secret">Generate one-time URL</button>
                    </form>
                    <div id="dx-one-time-result" style="margin-top:8px"></div>
                </td>
            </tr>
            <tr>
                <td><strong>Add a temporary administrator login</strong><br><small>Create a temporary admin user that will be removed after X days.</small></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="dx-temp-user-form">
                        <?php wp_nonce_field('dxseo_create_temp_user'); ?>
                        <input type="hidden" name="action" value="dxseo_create_temp_user">
                        <p><label>Username: <input type="text" name="dx_username" required></label></p>
                        <p><label>Email: <input type="email" name="dx_email" required></label></p>
                        <p><label>Days until expiry: <input type="number" name="dx_days" value="1" min="1"></label></p>
                        <p><button class="button button-primary" type="submit">Create temporary admin</button></p>
                    </form>

                    <h4>Active temporary admins</h4>
                    <?php if (empty($temp_users)): ?>
                        <p>No temporary admins currently.</p>
                    <?php else: ?>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Expires</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($temp_users as $uid => $meta): ?>
                                    <?php $u = get_userdata($uid);
                                    if (!$u) continue; ?>
                                    <tr>
                                        <td><?php echo esc_html($u->user_login); ?></td>
                                        <td><?php echo esc_html(date('Y-m-d H:i:s', $meta['expires'])); ?></td>
                                        <td>
                                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                                                <?php wp_nonce_field('dxseo_delete_temp_user'); ?>
                                                <input type="hidden" name="action" value="dxseo_delete_temp_user">
                                                <input type="hidden" name="dx_user_id" value="<?php echo intval($uid); ?>">
                                                <button class="button" type="submit">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </td>
            </tr>

            <tr>
                <td><strong>Auto Scan for SEO Plugins</strong><br><small>Detect active SEO plugins.</small></td>
                <td>
                    <?php if (empty($auto_scan_results)): ?>
                        <p>No SEO plugins detected.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($auto_scan_results as $plugin_file): ?>
                                <li><?php echo esc_html($plugin_file); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- NEW: SEO Importers -->
            <tr>
                <td><strong>SEO Tools Importer</strong><br><small>Import SEO settings safely (non-destructive) into SoftworldIt SEO namespace. This copies data — it does not delete or overwrite original plugin data.</small></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline">
                        <?php wp_nonce_field('dxseo_import_aioseo'); ?>
                        <input type="hidden" name="action" value="dxseo_import_aioseo">
                        <button class="button" type="submit">Import All SEO Data (advanced options + postmeta)</button>
                    </form>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline; margin-left:8px;">
                        <?php wp_nonce_field('dxseo_import_elementor'); ?>
                        <input type="hidden" name="action" value="dxseo_import_elementor">
                        <button class="button" type="submit">Import SEO for Elementor-built posts</button>
                    </form>

                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline; margin-left:8px;">
                        <?php wp_nonce_field('dxseo_import_edd'); ?>
                        <input type="hidden" name="action" value="dxseo_import_edd">
                        <button class="button" style="margin-top:8px" type="submit">Import SEO for Easy Digital Downloads (downloads)</button>
                    </form>

                    <p style="margin-top:8px"><small>Note: imports copy data into SoftworldIt SEO keys (prefix <code>_SoftworldIt SEO_imported_</code>) and store plugin-wide options under <code>SoftworldIt SEO_imports_*</code>. Nothing is deleted or overwritten.</small></p>
                </td>
            </tr>

            <!-- NEW: PageSpeed tracking -->
            <tr>
                <td><strong>Track Page Speed (single posts & pages)</strong><br><small>Use Google PageSpeed Insights API to fetch performance metrics per URL and save results for later inspection.</small></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom:8px;">
                        <?php wp_nonce_field('dxseo_pagespeed_key'); ?>
                        <input type="hidden" name="action" value="dxseo_save_pagespeed_key">
                        <p>API Key (Google PageSpeed Insights): <input type="text" name="dx_pagespeed_key" value="<?php echo esc_attr($pagespeed_key); ?>" style="width:400px"></p>
                        <p><button class="button" type="submit">Save API Key</button></p>
                    </form>

                    <div>
                        <p><strong>Run PageSpeed for a URL:</strong></p>
                        <input type="text" id="dx-pagespeed-url" placeholder="https://example.com/my-post/" style="width:60%;"> <select id="dx-pagespeed-strategy">
                            <option value="mobile">Mobile</option>
                            <option value="desktop">Desktop</option>
                        </select>
                        <button class="button" id="dx-run-pagespeed">Run</button>
                        <div id="dx-pagespeed-result" style="margin-top:8px"></div>
                    </div>

                    <div style="margin-top:12px">
                        <h4>Recent PageSpeed results (cached)</h4>
                        <?php if (empty($pagespeed_results)): ?>
                            <p>No results yet.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach (array_slice(array_reverse($pagespeed_results), 0, 10) as $r): ?>
                                    <li><strong><?php echo esc_html($r['url']); ?></strong> (<?php echo esc_html($r['strategy']); ?>) — Score: <?php echo esc_html($r['performance_score']); ?> — FCP: <?php echo esc_html($r['fcp']); ?> ms — LCP: <?php echo esc_html($r['lcp']); ?> ms — CLS: <?php echo esc_html($r['cls']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            <!-- NEW: Google Search Console CSV Importer -->
            <tr>
                <td><strong>Import Google Search Console keywords (CSV)</strong><br><small>Upload a CSV exported from Google Search Console (Performance -> Export -> CSV). The importer will parse common columns (Query, Clicks, Impressions, CTR, Position, Page) and store rows under <code>SoftworldIt SEO_gsc_keywords</code>. Non-destructive: existing imported rows are kept, new ones appended. Max keep: 5000 rows.</small></td>
                <td>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field('dxseo_import_gsc'); ?>
                        <input type="hidden" name="action" value="dxseo_import_gsc">
                        <p><input type="file" name="dx_gsc_file" accept=".csv,text/csv" required></p>
                        <p><button class="button button-primary" type="submit">Upload and import CSV</button></p>
                    </form>

                    <div style="margin-top:10px;">
                        <h4>Recent imported GSC rows (showing latest 20)</h4>
                        <?php if (empty($gsc_keywords) || !is_array($gsc_keywords)): ?>
                            <p>No GSC data imported yet.</p>
                        <?php else: ?>
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Time</th>
                                        <th>Data (sample)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $slice = array_slice(array_reverse($gsc_keywords), 0, 20);
                                    $i = 0;
                                    foreach ($slice as $r) {
                                        $i++;
                                        $raw = $r['raw'] ?? [];
                                        $sample = '';
                                        if (!empty($raw['query'])) $sample = esc_html($raw['query']);
                                        elseif (!empty($raw['page'])) $sample = esc_html($raw['page']);
                                        else $sample = esc_html(json_encode(array_slice($raw, 0, 3)));
                                        echo '<tr><td>' . intval($i) . '</td><td>' . esc_html($r['time']) . '</td><td><code>' . $sample . '</code></td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="margin-bottom:10px;">
                                <?php wp_nonce_field('dxseo_clear_gsc_keywords'); ?>
                                <input type="hidden" name="action" value="dxseo_clear_gsc_keywords">
                                <button class="button button-secondary" type="submit">Clear imported GSC data</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

        </tbody>
    </table>
</div>

<?php
// AJAX handler for flushing rewrite rules
add_action('wp_ajax_dxseo_flush_rewrites', function() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_ajax_referer('dxseo_ajax_nonce', '_ajax_nonce');

    flush_rewrite_rules();

    // Optionally return current rules for display
    $rules = get_option('rewrite_rules');
    wp_send_json_success([
        'message' => 'Rewrite rules flushed.',
        'rules' => array_slice($rules, 0, 50) // first 50 rules to avoid huge payload
    ]);
});
?>

<script>
(function(){
    jQuery(function($){
        $('#dx-flush-rewrites').on('click', function(){
            if (!confirm('Flush rewrite rules?')) return;
            $('#dxseo-debug-output').show().html('Flushing...');
            $.post(ajaxurl, {
                action: 'dxseo_flush_rewrites',
                _ajax_nonce: '<?php echo wp_create_nonce("dxseo_ajax_nonce"); ?>'
            }, function(r){
                if(r && r.success){
                    var html = '<p><?php echo "Rewrite rules flushed successfully."; ?></p>';
                    if(r.data.rules){
                        html += '<p>Sample rules:</p><ul>';
                        $.each(r.data.rules, function(k,v){
                            html += '<li>'+k+' → '+v+'</li>';
                        });
                        html += '</ul>';
                    }
                    $('#dxseo-debug-output').html(html);
                } else {
                    $('#dxseo-debug-output').html('<p style="color:red;">Failed to flush rewrite rules.</p>');
                }
            });
        });
    });
})();
</script>
