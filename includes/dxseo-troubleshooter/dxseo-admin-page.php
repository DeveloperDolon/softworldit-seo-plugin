<?php if (!defined('ABSPATH')) exit;

$php_version = phpversion();
$dx_debug = ini_get('display_errors') || defined('WP_DEBUG') && WP_DEBUG;
$js_errors = get_option('dxseo_js_errors', []);
$temp_users = get_option('dxseo_temp_users', []);
$dxmsg = $_GET['dxmsg'] ?? '';
$created_user = isset($_GET['u']) ? intval($_GET['u']) : 0;
$created_pass = isset($_GET['p']) ? rawurldecode($_GET['p']) : '';
$auto_scan_results = get_option('dxseo_auto_scan_results', []);
?>

<div class="wrap dxseo-wrap">
    <h1>Troubleshooting &amp; Support Infrastructure</h1>

    <?php if ($dxmsg === 'created'): ?>
        <div class="notice notice-success"><p>Temporary admin created. Username: <strong><?php echo esc_html($temp_users[$created_user]['username'] ?? ''); ?></strong> Password: <code><?php echo esc_html($created_pass); ?></code></p></div>
    <?php elseif ($dxmsg === 'exists'): ?>
        <div class="notice notice-error"><p>Username already exists.</p></div>
    <?php elseif ($dxmsg === 'missing'): ?>
        <div class="notice notice-error"><p>Missing username or email.</p></div>
    <?php endif; ?>

    <h2>Troubleshooting</h2>
    <table class="widefat dx-table">
        <thead><tr><th>Issue</th><th>Action</th></tr></thead>
        <tbody>
            <tr>
                <td><strong>Enable debugging in WordPress</strong><br><small>Runtime debug (display errors) for this request. For persistent debug, add WP_DEBUG constants to wp-config.php.</small></td>
                <td><button class="button" id="dx-enable-debug">Enable runtime debug</button></td>
            </tr>
            <tr>
                <td><strong>Fix a 404 error when viewing sitemap</strong><br><small>Flush rewrite rules and check Yoast/AIOSEO status.</small></td>
                <td><button class="button" id="dx-flush-rewrites">Flush rewrite rules</button></td>
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
                                <?php foreach (array_slice(array_reverse($js_errors),0,20) as $log): ?>
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
                    <form id="dx-one-time-form">
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
                            <thead><tr><th>User</th><th>Expires</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php foreach ($temp_users as $uid => $meta): ?>
                                    <?php $u = get_userdata($uid); if (!$u) continue; ?>
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
                <td><strong>Auto Scan for SEO Plugins</strong><br><small>Detect active SEO plugins like Yoast SEO or All in One SEO.</small></td>
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
        </tbody>
    </table>
</div>
