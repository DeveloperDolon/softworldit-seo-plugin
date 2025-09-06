<?php
if (!defined('ABSPATH')) exit;

class DXSEO_Router {
    public static function bootstrap(){
        add_action('template_redirect', [__CLASS__, 'maybe_redirect'], 0);
        add_action('post_updated', [__CLASS__, 'on_post_updated'], 10, 3);
        add_action('trashed_post', [__CLASS__, 'on_post_trashed']);
        add_action('before_delete_post', [__CLASS__, 'on_post_deleted']);
        add_filter('robots_txt', [__CLASS__, 'extend_robots'], 99, 2);
        add_action('wp_head', [__CLASS__, 'maybe_meta_noindex'], 1);
    }

    protected static function current_path(){
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $home = wp_parse_url(home_url('/'));
        // Remove scheme + host if present
        return DXSEO_Helpers::sanitize_path($uri);
    }

    public static function maybe_redirect(){
        $opts = get_option('dxseo_settings', []);

        if (!empty($opts['strip_replytocom']) && isset($_GET['replytocom'])){
            // Strip replytocom param (duplicate pages)
            $url = remove_query_arg('replytocom');
            wp_redirect($url, 301); exit;
        }

        if (!empty($opts['disable_media_pages']) && is_attachment()){
            $parent = wp_get_post_parent_id(get_queried_object_id());
            $target = $parent ? get_permalink($parent) : home_url('/');
            wp_redirect($target, 301); exit;
        }

        // If configured to run only on 404, bail early
        if (!empty($opts['apply_to_404_only']) && !is_404()) return;

        global $wpdb; $table = DXSEO_Helpers::table();
        $path = self::current_path();
        $now = DXSEO_Helpers::now();

        // Fetch active rules that are within schedule window
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE active=1 AND (start_at IS NULL OR start_at<=%s) AND (end_at IS NULL OR end_at>=%s)",
            $now,$now
        ));

        // First exact matches
        foreach($rules as $r){
            if(!$r->regex && self::path_match_exact($path, $r->from_path)){
                self::execute_rule($r); return;
            }
        }
        // Then regex matches
        foreach($rules as $r){
            if($r->regex && self::path_match_regex($path, $r->from_path, $match)){
                self::execute_rule($r, $match); return;
            }
        }

        // Serve 410 for configured gone patterns
        if (!empty($opts['gone_patterns']) && is_array($opts['gone_patterns'])){
            foreach($opts['gone_patterns'] as $pat){
                $pat = trim($pat); if(!$pat) continue;
                if (self::path_match_regex($path, $pat, $m)){
                    status_header(410); nocache_headers();
                    echo '<!doctype html><meta name="robots" content="noindex"><h1>410 Gone</h1>';
                    exit;
                }
            }
        }
    }

    protected static function path_match_exact($request_path, $rule_path){
        // normalize
        return rtrim($request_path,'/') === rtrim($rule_path,'/');
    }

    protected static function path_match_regex($request_path, $pattern, &$match=null){
        $delim = '#';
        $regex = $pattern;
        if ($pattern[0] !== $delim) $regex = $delim.str_replace('#','\\#',$pattern).$delim.'i';
        return preg_match($regex, $request_path, $match) === 1;
    }

    protected static function execute_rule($r, $match=null){
        global $wpdb; $table = DXSEO_Helpers::table();

        // Build destination
        $to = $r->to_path;
        if ($r->regex && $match && strpos($to, '$') !== false){
            // Backrefs $1, $2 ...
            $to = preg_replace(self::ensure_delim($r->from_path), $to, self::current_path());
        }

        // Increment hit counters
        $wpdb->update($table, [
            'hits' => $r->hits + 1,
            'last_hit' => DXSEO_Helpers::now()
        ], ['id' => $r->id]);

        if ($r->status_code == 410){
            status_header(410); nocache_headers();
            echo '<!doctype html><meta name="robots" content="noindex"><h1>410 Gone</h1>';
            exit;
        }

        // Absolute URL if relative
        if ($to && strpos($to, 'http') !== 0){
            $to = home_url($to);
        }
        $code = in_array((int)$r->status_code, [301,302,307,308]) ? (int)$r->status_code : 301;
        wp_redirect($to ?: home_url('/'), $code); exit;
    }

    protected static function ensure_delim($pattern){
        return ($pattern[0] === '#') ? $pattern : '#'.str_replace('#','\\#',$pattern).'#i';
    }

    // Auto-create redirect when slug changes
    public static function on_post_updated($post_ID, $post_after, $post_before){
        if (wp_is_post_revision($post_ID)) return;
        $opts = get_option('dxseo_settings', []);
        if (empty($opts['autocreate_slug_redirect'])) return;
        if ($post_after->post_name !== $post_before->post_name){
            $old = get_permalink($post_before);
            $new = get_permalink($post_after);
            self::insert_redirect(
                DXSEO_Helpers::sanitize_path($old),
                DXSEO_Helpers::sanitize_path($new),
                0, 301, 'Auto: slug change'
            );
        }
    }

    // When post trashed or deleted
    public static function on_post_trashed($post_id){ self::handle_deleted($post_id); }
    public static function on_post_deleted($post_id){ self::handle_deleted($post_id); }

    protected static function handle_deleted($post_id){
        $opts = get_option('dxseo_settings', []);
        $behavior = $opts['deleted_behavior'] ?? '410';
        $from = DXSEO_Helpers::sanitize_path(get_permalink($post_id));
        $to = null; $code = 410; $note = 'Auto: deleted';
        if ($behavior === 'homepage'){
            $to = '/'; $code = 301;
        } elseif ($behavior === 'parent'){
            $p = wp_get_post_parent_id($post_id);
            $to = $p ? DXSEO_Helpers::sanitize_path(get_permalink($p)) : '/';
            $code = 301;
        }
        self::insert_redirect($from, $to, 0, $code, $note);
    }

    public static function insert_redirect($from, $to=null, $regex=0, $code=301, $note=''){
        global $wpdb; $table = DXSEO_Helpers::table();
        $wpdb->insert($table, [
            'from_path' => $from,
            'to_path' => $to,
            'regex' => (int)$regex,
            'status_code' => (int)$code,
            'active' => 1,
            'start_at' => null,
            'end_at' => null,
            'note' => $note,
            'created_at' => DXSEO_Helpers::now(),
            'updated_at' => DXSEO_Helpers::now()
        ]);
    }

    // robots.txt enhancements (disallow noisy params)
    public static function extend_robots($output, $public){
        $opts = get_option('dxseo_settings', []);
        if (!empty($opts['disallow_params'])){
            $home = wp_parse_url(home_url('/'));
            $hostPath = rtrim($home['path'] ?? '/', '/');
            $output .= "\n# DXSEO Crawl Cleanup\n";
            foreach($opts['disallow_params'] as $p){
                $p = trim($p); if(!$p) continue;
                $output .= "Disallow: $hostPath/*?$p=\n";
            }
        }
        return $output;
    }

    // Meta robots noindex for patterns
    public static function maybe_meta_noindex(){
        if (is_admin()) return;
        $opts = get_option('dxseo_settings', []);
        $path = self::current_path();
        if (!empty($opts['noindex_patterns'])){
            foreach($opts['noindex_patterns'] as $pat){
                $pat = trim($pat); if(!$pat) continue;
                if (self::path_match_regex($path, $pat, $m)){
                    echo '<meta name="robots" content="noindex,nofollow">';
                    break;
                }
            }
        }
    }
}
