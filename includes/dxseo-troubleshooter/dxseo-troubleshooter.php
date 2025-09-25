<?php
/**
 * Plugin Name: Softworld It SEO Troubleshooting & Support Infrastructure
 * Description: Adds a Troubleshooting & Support Infrastructure admin panel (debug toggle, sitemap fixer, JS error collector, Action Scheduler checks, PHP compatibility, one-time secure notes, temporary admin users, auto scan). Adds SEO importers (AIOSEO / Elementor / EDD) and PageSpeed tracking.
 * Version: 1.2.0
 * Author: Softworld It
 * Text Domain: dxseo-troubleshooter
 */

if (!defined('ABSPATH')) exit;

class DXSEO_Troubleshooter {

    public static function init() {
        // add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // existing ajax handlers
        add_action('wp_ajax_dxseo_enable_runtime_debug', [__CLASS__, 'ajax_enable_runtime_debug']);
        add_action('wp_ajax_dxseo_flush_rewrites', [__CLASS__, 'ajax_flush_rewrites']);
        add_action('wp_ajax_dxseo_log_js_error', [__CLASS__, 'ajax_log_js_error']);
        add_action('wp_ajax_dxseo_create_one_time_secret', [__CLASS__, 'ajax_create_one_time_secret']);
        add_action('wp_ajax_dxseo_run_pagespeed', [__CLASS__, 'ajax_run_pagespeed']);

        // admin_post handlers
        add_action('admin_init', [__CLASS__, 'remove_expired_temp_users']);
        add_action('admin_post_dxseo_create_temp_user', [__CLASS__, 'handle_create_temp_user']);
        add_action('admin_post_dxseo_delete_temp_user', [__CLASS__, 'handle_delete_temp_user']);
        add_action('admin_post_dxseo_clear_js_errors', [__CLASS__, 'handle_clear_js_errors']);
        add_action('admin_post_dxseo_import_aioseo', [__CLASS__, 'handle_import_aioseo']);
        add_action('admin_post_dxseo_import_elementor', [__CLASS__, 'handle_import_elementor']);
        add_action('admin_post_dxseo_import_edd', [__CLASS__, 'handle_import_edd']);
        add_action('admin_post_dxseo_save_pagespeed_key', [__CLASS__, 'handle_save_pagespeed_key']);

        // GSC CSV importer handler (new)
        add_action('admin_post_dxseo_import_gsc', [__CLASS__, 'handle_import_gsc']);
        add_action('admin_post_dxseo_clear_gsc_keywords', [__CLASS__, 'handle_clear_gsc_keywords']);


        // serve one-time secret
        add_action('init', [__CLASS__, 'serve_one_time_secret']);

        // persist runtime debug (with expiry)
        add_action('admin_init', [__CLASS__, 'persist_runtime_debug']);

        // auto-scan on admin init
        add_action('admin_init', [__CLASS__, 'auto_scan_plugins']);
    }

    // public static function admin_menu() {
    //     add_menu_page(
    //         'Troubleshooting & Support Infrastructure',
    //         'Troubleshooting & Support Infrastructure',
    //         'manage_options',
    //         'dxseo_troubleshoot',
    //         [__CLASS__, 'render_admin_page'],
    //         'dashicons-admin-tools',
    //         59
    //     );
    // }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'dxseo_troubleshoot') === false) return;
        $css = plugin_dir_path(__FILE__) . 'assets/admin.css';
        if (file_exists($css)) {
            wp_enqueue_style('dxseo-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css');
        }
        wp_enqueue_script('dxseo-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], null, true);
        wp_localize_script('dxseo-admin-js', 'dxseoAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_url' => admin_url('admin-post.php'),
            'nonce'    => wp_create_nonce('dxseo_nonce'),
        ]);
    }

    public static function render_admin_page() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        include plugin_dir_path(__FILE__) . '/dxseo-admin-page.php';
    }

    // --- Runtime Debug ---
    public static function ajax_enable_runtime_debug() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        // enable for short time only (5 minutes)
        $expires = time() + (5 * MINUTE_IN_SECONDS);
        update_option('dxseo_runtime_debug_expires', $expires);
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        wp_send_json_success(['message' => 'Runtime debug enabled for admin pages for 5 minutes (display_errors on).']);
    }

    public static function persist_runtime_debug() {
        $exp = get_option('dxseo_runtime_debug_expires', 0);
        if ($exp && time() <= intval($exp)) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            if ($exp) delete_option('dxseo_runtime_debug_expires');
        }
    }

    // --- Rewrite flush ---
    public static function ajax_flush_rewrites() {
    check_ajax_referer('dxseo_nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    flush_rewrite_rules(true);

    global $wp_rewrite;
    $rules = $wp_rewrite->wp_rewrite_rules();

    wp_send_json_success([
        'message' => 'Rewrite rules flushed.',
        'rules'   => $rules ? $rules : []
    ]);
}

    // --- JS error log ---
    public static function ajax_log_js_error() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $payload = wp_unslash($_POST['payload'] ?? '{}');
        // sanitize + truncate
        $payload = sanitize_textarea_field(mb_substr($payload, 0, 5000));
        $logs = get_option('dxseo_js_errors', []);
        $logs[] = [
            'time' => current_time('mysql'),
            'payload' => $payload,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];
        // keep last 200
        if (count($logs) > 200) $logs = array_slice($logs, -200);
        update_option('dxseo_js_errors', $logs);
        wp_send_json_success();
    }

    public static function handle_clear_js_errors() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_clear_js_errors');
        delete_option('dxseo_js_errors');
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot'));
        exit;
    }

    // --- Temporary admin users ---
    public static function handle_create_temp_user() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_create_temp_user');
        $username = sanitize_user($_POST['dx_username'] ?? '');
        $email = sanitize_email($_POST['dx_email'] ?? '');
        $days = intval($_POST['dx_days'] ?? 1);
        if (!$username || !$email) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=missing'));
            exit;
        }
        $password = wp_generate_password(12, true);
        if (username_exists($username)) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=exists'));
            exit;
        }
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id) || !$user_id) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=error'));
            exit;
        }
        $user = new WP_User($user_id);
        $user->set_role('administrator');
        $expiry = time() + ($days * DAY_IN_SECONDS);
        update_user_meta($user_id, 'dxseo_temp_user_expires', $expiry);
        $temp_users = get_option('dxseo_temp_users', []);
        $temp_users[$user_id] = ['username'=>$username,'expires'=>$expiry];
        update_option('dxseo_temp_users', $temp_users);
        // store password in transient (shown once) instead of URL
        $transient_key = 'dxseo_temp_pass_' . $user_id;
        set_transient($transient_key, $password, 5 * MINUTE_IN_SECONDS);
        $redirect = admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=created&u=' . $user_id);
        wp_redirect($redirect);
        exit;
    }

    public static function handle_delete_temp_user() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_delete_temp_user');
        $user_id = intval($_POST['dx_user_id'] ?? 0);
        if ($user_id && get_userdata($user_id)) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($user_id);
        }
        $temp_users = get_option('dxseo_temp_users', []);
        if (isset($temp_users[$user_id])) {
            unset($temp_users[$user_id]);
            update_option('dxseo_temp_users', $temp_users);
        }
        delete_transient('dxseo_temp_pass_' . $user_id);
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot'));
        exit;
    }

    public static function remove_expired_temp_users() {
        if (!current_user_can('manage_options')) return;
        $temp_users = get_option('dxseo_temp_users', []);
        $now = time();
        $changed = false;
        foreach ($temp_users as $uid => $meta) {
            if ($meta['expires'] <= $now) {
                if (get_userdata($uid)) {
                    require_once ABSPATH . 'wp-admin/includes/user.php';
                    wp_delete_user($uid);
                }
                delete_transient('dxseo_temp_pass_' . $uid);
                unset($temp_users[$uid]);
                $changed = true;
            }
        }
        if ($changed) update_option('dxseo_temp_users', $temp_users);
    }

    // --- One-time secret notes ---
    public static function serve_one_time_secret() {
        if (isset($_GET['dxseo_one_time_token'])) {
            $token = sanitize_text_field($_GET['dxseo_one_time_token']);
            $secrets = get_option('dxseo_one_time_secrets', []);
            if (!isset($secrets[$token])) {
                wp_die('Secret not found or already viewed.');
            }
            $data = $secrets[$token];
            $expires = $data['expires'] ?? 0;
            if (time() > $expires) {
                unset($secrets[$token]);
                update_option('dxseo_one_time_secrets', $secrets);
                wp_die('Secret expired.');
            }
            $payload = $data['payload'];
            unset($secrets[$token]);
            update_option('dxseo_one_time_secrets', $secrets);
            wp_die('<h2>One-time secret</h2><pre style="white-space:pre-wrap">' . esc_html($payload) . '</pre>');
        }
    }

    public static function create_one_time_secret($payload, $ttl_seconds=3600) {
        $token = wp_generate_password(24, false, false);
        $secrets = get_option('dxseo_one_time_secrets', []);
        $secrets[$token] = ['payload' => $payload, 'expires' => time() + $ttl_seconds];
        update_option('dxseo_one_time_secrets', $secrets);
        return $token;
    }

    // AJAX wrapper: create one-time secret (expects ttl in minutes from JS)
    public static function ajax_create_one_time_secret() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $payload = wp_unslash($_POST['payload'] ?? '');
        $ttl_minutes = intval($_POST['ttl'] ?? 60);
        $ttl_minutes = max(1, $ttl_minutes);
        $ttl_seconds = $ttl_minutes * MINUTE_IN_SECONDS;
        $token = self::create_one_time_secret($payload, $ttl_seconds);
        $url = add_query_arg('dxseo_one_time_token', $token, home_url('/'));
        wp_send_json_success(['url' => $url, 'ttl_minutes' => $ttl_minutes]);
    }

    // --- Auto scan (Yoast style) ---
    public static function auto_scan_plugins() {
        $active_plugins = get_option('active_plugins', []);
        $results = [];
        foreach ($active_plugins as $p) {
            if (strpos($p, 'wordpress-seo') !== false || strpos($p, 'all-in-one-seo') !== false || strpos($p, 'aioseo') !== false) {
                $results[] = $p;
            }
        }
        if (!empty($results)) {
            update_option('dxseo_auto_scan_results', $results);
        }
    }

    // --------------------
    // --- IMPORTERS
    // --------------------

    public static function handle_import_aioseo() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_import_aioseo');
        $result = self::import_aioseo();
        $msg = 'imported=' . intval($result['count_options']) . '&postmeta=' . intval($result['count_postmeta']);
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=aioseo_imported&' . $msg));
        exit;
    }

    public static function handle_import_elementor() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_import_elementor');
        $result = self::import_elementor_seo();
        $msg = 'copied=' . intval($result['copied_posts']);
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=elementor_imported&' . $msg));
        exit;
    }

    public static function handle_import_edd() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_import_edd');
        $result = self::import_edd_seo();
        $msg = 'copied=' . intval($result['copied_downloads']);
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=edd_imported&' . $msg));
        exit;
    }

    // Import All In One SEO options and postmeta into DXSEO namespace (non-destructive)
    public static function import_aioseo() {
        global $wpdb;
        $found_options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '%aioseo%' OR option_name LIKE '%aioseop%' OR option_name LIKE '%all_in_one_seo%'");
        $imports = get_option('dxseo_imports_aioseo', []);
        $count_options = 0;
        if (!empty($found_options)) {
            foreach ($found_options as $row) {
                $name = $row->option_name;
                $val = maybe_unserialize($row->option_value);
                $imports[$name] = $val;
                $count_options++;
            }
            update_option('dxseo_imports_aioseo', $imports);
        }
        // postmeta: copy per-post meta keys that match AIOSEO patterns
        $meta_rows = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE '%aioseop%' OR meta_key LIKE '%aioseo%' LIMIT 10000");
        $count_postmeta = 0;
        if (!empty($meta_rows)) {
            foreach ($meta_rows as $m) {
                $post_id = intval($m->post_id);
                $orig_key = $m->meta_key;
                $val = maybe_unserialize($m->meta_value);
                $dx_key = '_dxseo_imported_' . $orig_key;
                if (get_post_meta($post_id, $dx_key, true) === '') {
                    update_post_meta($post_id, $dx_key, $val);
                    $count_postmeta++;
                }
            }
        }
        return ['count_options' => $count_options, 'count_postmeta' => $count_postmeta];
    }

    // Elementor SEO importer: find posts built with Elementor and copy common SEO meta into DXSEO keys
    public static function import_elementor_seo() {
        global $wpdb;
        $posts = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' GROUP BY post_id");
        $copied = 0;
        if (!empty($posts)) {
            foreach ($posts as $post_id) {
                $post_id = intval($post_id);
                $candidates = [
                    '_yoast_wpseo_title',
                    '_yoast_wpseo_metadesc',
                    '_rank_math_title',
                    '_rank_math_description',
                    '_aioseop_title',
                    '_aioseop_description',
                    '_aioseo_title',
                    '_aioseo_description',
                ];
                $copied_any = false;
                foreach ($candidates as $k) {
                    $v = get_post_meta($post_id, $k, true);
                    if ($v !== '') {
                        $dx_key = '_dxseo_imported_' . ltrim($k, '_');
                        if (get_post_meta($post_id, $dx_key, true) === '') {
                            update_post_meta($post_id, $dx_key, maybe_unserialize($v));
                            $copied_any = true;
                        }
                    }
                }
                if ($copied_any) $copied++;
            }
        }
        return ['copied_posts' => $copied];
    }

    // EDD SEO importer: for post_type 'download' copy common seo keys into DXSEO keys
    public static function import_edd_seo() {
        $args = ['post_type' => 'download', 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids'];
        $query = new WP_Query($args);
        $copied = 0;
        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $candidates = [
                    '_yoast_wpseo_title',
                    '_yoast_wpseo_metadesc',
                    '_rank_math_title',
                    '_rank_math_description',
                    '_aioseop_title',
                    '_aioseop_description',
                ];
                $copied_any = false;
                foreach ($candidates as $k) {
                    $v = get_post_meta($post_id, $k, true);
                    if ($v !== '') {
                        $dx_key = '_dxseo_imported_' . ltrim($k, '_');
                        if (get_post_meta($post_id, $dx_key, true) === '') {
                            update_post_meta($post_id, $dx_key, maybe_unserialize($v));
                            $copied_any = true;
                        }
                    }
                }
                if ($copied_any) $copied++;
            }
            wp_reset_postdata();
        }
        return ['copied_downloads' => $copied];
    }

    // --------------------
    // --- GSC CSV IMPORTER (NEW)
    // --------------------

    // Admin handler for CSV upload (expects form with file input 'dx_gsc_file')
    public static function handle_import_gsc() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_import_gsc');

        // Basic validations
        if (empty($_FILES['dx_gsc_file']) || $_FILES['dx_gsc_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=gsc_error&err=nofile'));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload_overrides = ['test_form' => false];
        $file = wp_handle_upload($_FILES['dx_gsc_file'], $upload_overrides);

        if (isset($file['error'])) {
            wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=gsc_error&err=uploadfail'));
            exit;
        }

        $filepath = $file['file'];

        $imported = self::import_gsc_csv($filepath);

        // delete uploaded file from temp
        @unlink($filepath);

        $redirect = admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=gsc_imported&count=' . intval($imported['count']));
        wp_redirect($redirect);
        exit;
    }

    // Actually parse CSV and store rows in option
    // Expected common GSC CSV columns: "Query","Clicks","Impressions","CTR","Position","Page" (but code is tolerant)
    public static function import_gsc_csv($file_path) {
        $handle = @fopen($file_path, 'r');
        if (!$handle) return ['count' => 0];

        $headers = [];
        $rows = [];
        $row_count = 0;
        $max_rows = 5000; // keep reasonable limit
        $line_no = 0;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $line_no++;
            // skip empty lines
            $all_blank = true;
            foreach ($data as $d) { if (trim($d) !== '') { $all_blank = false; break; } }
            if ($all_blank) continue;

            if (empty($headers)) {
                // normalize header names
                $headers = array_map(function($h){
                    $h = trim($h);
                    $h = strtolower($h);
                    $h = str_replace([' ', "\t", "\r", "\n"], ['_','','',''], $h);
                    return $h;
                }, $data);
                continue;
            }

            $assoc = [];
            foreach ($data as $i => $val) {
                $key = $headers[$i] ?? 'col_' . $i;
                $val = trim($val);
                // common numeric cleans
                if (in_array($key, ['clicks','impressions','position'])) {
                    $val = $val === '' ? null : floatval(str_replace(',', '', $val));
                } elseif ($key === 'ctr') {
                    // CTR could be "1.23%" or decimal
                    $v = rtrim($val, '%');
                    if ($v === '') $val = null; else $val = floatval(str_replace(',', '', $v));
                }
                $assoc[$key] = sanitize_text_field($val);
            }
            // enrich common fields if possible
            $entry = [
                'time' => current_time('mysql'),
                'raw' => $assoc,
            ];
            $rows[] = $entry;
            $row_count++;
            if ($row_count >= $max_rows) break;
        }
        fclose($handle);

        if (empty($rows)) return ['count' => 0];

        // merge into existing option while keeping last X rows
        $existing = get_option('dxseo_gsc_keywords', []);
        if (!is_array($existing)) $existing = [];
        $combined = array_merge($existing, $rows);
        // keep last 5000 entries
        if (count($combined) > 5000) $combined = array_slice($combined, -5000);
        update_option('dxseo_gsc_keywords', $combined);

        return ['count' => $row_count];
    }

    // Utility: clear stored GSC keywords (not hooked publicly — used in admin page actions via admin-post)

    public static function clear_gsc_keywords() {
        delete_option('dxseo_gsc_keywords');
    }
    public static function handle_clear_gsc_keywords() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('dxseo_clear_gsc_keywords');
    self::clear_gsc_keywords();
    wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=gsc_cleared'));
    exit;
}

    // --- END GSC CSV IMPORTER ---

    // --------------------
    // --- PAGESPEED
    // --------------------

    public static function handle_save_pagespeed_key() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('dxseo_pagespeed_key');
        $key = sanitize_text_field($_POST['dx_pagespeed_key'] ?? '');
        update_option('dxseo_pagespeed_api_key', $key);
        wp_redirect(admin_url('admin.php?page=dxseo_troubleshoot&dxmsg=pagespeed_saved'));
        exit;
    }

    public static function ajax_run_pagespeed() {
        check_ajax_referer('dxseo_nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $url = esc_url_raw($_POST['url'] ?? '');
        $strategy = in_array($_POST['strategy'] ?? 'mobile', ['mobile','desktop']) ? $_POST['strategy'] : 'mobile';
        if (empty($url)) wp_send_json_error('Missing URL');

        $api_key = get_option('dxseo_pagespeed_api_key', '');
        if (empty($api_key)) wp_send_json_error('Missing PageSpeed API key. Save it in the plugin settings.');

        // throttle: allow one run per URL+strategy every 6 hours
        $hash = 'dxps_' . md5($url . '|' . $strategy);
        $last = get_option($hash . '_last_run', 0);
        if ($last && (time() - intval($last) < 6 * HOUR_IN_SECONDS)) {
            wp_send_json_error('Too many requests for this URL. Try again later.');
        }

        $request_url = 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . rawurlencode($url) . '&strategy=' . rawurlencode($strategy) . '&key=' . rawurlencode($api_key);
        $resp = wp_remote_get($request_url, ['timeout' => 30]);
        if (is_wp_error($resp)) {
            wp_send_json_error('Request failed: ' . $resp->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        if ($code !== 200) {
            wp_send_json_error('PageSpeed API error, HTTP ' . $code . ': ' . wp_trim_words($body, 30));
        }
        $json = json_decode($body, true);
        if (!$json) wp_send_json_error('Invalid JSON from PageSpeed API');

        $lighthouse = $json['lighthouseResult'] ?? [];
        $audits = $lighthouse['audits'] ?? [];
        $performance_score = isset($lighthouse['categories']['performance']['score']) ? intval(round($lighthouse['categories']['performance']['score'] * 100)) : null;
        $first_contentful_paint = isset($audits['first-contentful-paint']['numericValue']) ? floatval($audits['first-contentful-paint']['numericValue']) : null;
        $largest_contentful_paint = isset($audits['largest-contentful-paint']['numericValue']) ? floatval($audits['largest-contentful-paint']['numericValue']) : null;
        $cumulative_layout_shift = isset($audits['cumulative-layout-shift']['numericValue']) ? floatval($audits['cumulative-layout-shift']['numericValue']) : null;
        $time_to_interactive = isset($audits['interactive']['numericValue']) ? floatval($audits['interactive']['numericValue']) : null;

        $results = get_option('dxseo_pagespeed_results', []);
        $results[$hash] = [
            'url' => $url,
            'strategy' => $strategy,
            'performance_score' => $performance_score,
            'fcp' => $first_contentful_paint,
            'lcp' => $largest_contentful_paint,
            'cls' => $cumulative_layout_shift,
            'tti' => $time_to_interactive,
            'raw' => $json,
            'ts' => time(),
        ];
        update_option('dxseo_pagespeed_results', $results);
        update_option($hash . '_last_run', time());

        wp_send_json_success(['summary' => $results[$hash]]);
    }

}
