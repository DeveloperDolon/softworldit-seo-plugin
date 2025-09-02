<?php
if (!defined('ABSPATH')) exit;

/* ---------------------------------------
 * Remove default WP canonical to avoid duplicates
 * ------------------------------------- */
remove_action('wp_head', 'rel_canonical');

/* ---------------------------------------
 * SoftworldIT Search Appearance
 * Features:
 * - Global templates for SEO Title & Meta Description:
 *   - Homepage, Posts, Pages, Categories, Tags
 * - Title separator customization
 * - Smart tags for titles/descriptions
 * - Robots meta defaults (noindex, nofollow) per type
 * - Per Post/Page meta box: NOINDEX/NOFOLLOW + Canonical URL
 * - Front-end output: title filters, meta description, robots, canonical
 *
 * Options key: swit_search_appearance (array)
 */
const SWIT_SA_OPTION = 'swit_search_appearance';

/* ---------------------------------------
 * Helpers: defaults, getters, smart tags
 * ------------------------------------- */
function swit_sa_default_options(){
    return [
        'separator' => '–',

        'home_title'  => '{site_name} {separator} {tagline}',
        'home_desc'   => '{site_name} — {tagline}',

        'post_title'  => '{post_title} {separator} {site_name}',
        'post_desc'   => '{excerpt}',

        'page_title'  => '{post_title} {separator} {site_name}',
        'page_desc'   => '{excerpt}',

        'cat_title'   => '{term_name} {separator} {site_name}',
        'cat_desc'    => '{term_description}',

        'tag_title'   => '{term_name} {separator} {site_name}',
        'tag_desc'    => '{term_description}',

        // Robots defaults
        'robots_home' => ['noindex'=>0,'nofollow'=>0],
        'robots_post' => ['noindex'=>0,'nofollow'=>0],
        'robots_page' => ['noindex'=>0,'nofollow'=>0],
        'robots_cat'  => ['noindex'=>0,'nofollow'=>0],
        'robots_tag'  => ['noindex'=>0,'nofollow'=>0],
    ];
}
function swit_sa_get_options(){
    $opt = get_option(SWIT_SA_OPTION, []);
    return wp_parse_args($opt, swit_sa_default_options());
}
function swit_sa_smart_tags_map($context=[]){
    $site_name = get_bloginfo('name');
    $tagline   = get_bloginfo('description');
    $sep       = $context['separator'] ?? '–';

    $post = $context['post'] ?? null;
    $term = $context['term'] ?? null;

    $post_title = $post ? get_the_title($post) : '';
    $excerpt    = '';
    if($post){
        $desc_meta = get_post_meta($post->ID, '_ayc_meta_description', true);
        if(!empty($desc_meta)){
            $excerpt = $desc_meta;
        }else{
            $raw = has_excerpt($post) ? get_the_excerpt($post) : wp_strip_all_tags($post->post_content);
            $excerpt = wp_trim_words($raw, 30, '…');
        }
    }

    $author     = $post ? get_the_author_meta('display_name', $post->post_author) : '';
    $date       = $post ? get_the_date('', $post) : '';
    $term_name  = $term ? $term->name : '';
    $term_description = $term ? wp_strip_all_tags(term_description($term->term_id, $term->taxonomy)) : '';

    return [
        '{site_name}'        => $site_name,
        '{tagline}'          => $tagline,
        '{separator}'        => $sep,
        '{post_title}'       => $post_title,
        '{excerpt}'          => $excerpt,
        '{author}'           => $author,
        '{date}'             => $date,
        '{term_name}'        => $term_name,
        '{term_description}' => $term_description,
    ];
}
function swit_sa_apply_smart_tags($template, $context=[]){
    $template = (string) $template;
    $map = swit_sa_smart_tags_map($context);
    $out = strtr($template, $map);
    $out = preg_replace('/\s{2,}/',' ', $out);
    return wp_strip_all_tags(trim($out));
}

/* ---------------------------------------
 * Admin Page
 * ------------------------------------- */
function softworldit_render_search(){
    if (!current_user_can('manage_options')) return;
    $opt = swit_sa_get_options();

    if(isset($_POST['swit_sa_save']) && check_admin_referer('swit_sa_nonce','swit_sa_nonce')){
        $new = $opt;

        $new['separator']   = sanitize_text_field($_POST['separator'] ?? $opt['separator']);

        $fields = ['home','post','page','cat','tag'];
        foreach($fields as $f){
            $new["{$f}_title"] = sanitize_text_field($_POST["{$f}_title"] ?? '');
            $new["{$f}_desc"]  = sanitize_textarea_field($_POST["{$f}_desc"] ?? '');
            $new["robots_{$f}"] = [
                'noindex'=> !empty($_POST["robots_{$f}_noindex"])?1:0,
                'nofollow'=>!empty($_POST["robots_{$f}_nofollow"])?1:0,
            ];
        }

        update_option(SWIT_SA_OPTION,$new);
        $opt = swit_sa_get_options();
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $sep_choices = ['–','|','•','»','«','·','-','—',':'];
    ?>
    <div class="wrap">
        <h1>🔍 Search Appearance</h1>
        <form method="post">
            <?php wp_nonce_field('swit_sa_nonce','swit_sa_nonce'); ?>
            <h2>Title Separator</h2>
            <p>
                <select name="separator">
                    <?php foreach($sep_choices as $s): ?>
                        <option value="<?php echo esc_attr($s); ?>" <?php selected($opt['separator'],$s); ?>>
                            <?php echo esc_html($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <hr>
            <?php
            foreach(['home'=>'Homepage','post'=>'Posts','page'=>'Pages','cat'=>'Categories','tag'=>'Tags'] as $k=>$label){
                ?>
                <h2><?php echo $label; ?></h2>
                <table class="form-table">
                    <tr>
                        <th>SEO Title</th>
                        <td><input type="text" class="regular-text" name="<?php echo $k; ?>_title" value="<?php echo esc_attr($opt[$k.'_title']); ?>"></td>
                    </tr>
                    <tr>
                        <th>Meta Description</th>
                        <td><textarea name="<?php echo $k; ?>_desc" rows="3" class="large-text"><?php echo esc_textarea($opt[$k.'_desc']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Robots (default)</th>
                        <td>
                            <label><input type="checkbox" name="robots_<?php echo $k; ?>_noindex" <?php checked($opt["robots_{$k}"]['noindex'],1); ?>> NOINDEX</label>
                            <label><input type="checkbox" name="robots_<?php echo $k; ?>_nofollow" <?php checked($opt["robots_{$k}"]['nofollow'],1); ?>> NOFOLLOW</label>
                        </td>
                    </tr>
                </table>
                <hr>
                <?php
            }
            ?>
            <p class="submit"><button class="button button-primary" name="swit_sa_save" value="1">Save Changes</button></p>
            <p><strong>Smart tags:</strong> {site_name}, {tagline}, {separator}, {post_title}, {excerpt}, {author}, {date}, {term_name}, {term_description}</p>
        </form>
    </div>
    <?php
}

/* ---------------------------------------
 * Meta Box
 * ------------------------------------- */
add_action('add_meta_boxes', function(){
    add_meta_box('swit_sa_meta','Search Appearance (Robots & Canonical)','swit_sa_meta_box_cb',['post','page'],'side','default');
});
function swit_sa_meta_box_cb($post){
    wp_nonce_field('swit_sa_meta_save','swit_sa_meta_nonce');
    $noindex  = (int) get_post_meta($post->ID,'_swit_noindex',true);
    $nofollow = (int) get_post_meta($post->ID,'_swit_nofollow',true);
    $canonical= esc_url(get_post_meta($post->ID,'_swit_canonical',true));
    ?>
    <p>
        <label><input type="checkbox" name="swit_noindex" <?php checked($noindex,1); ?>> NOINDEX</label><br>
        <label><input type="checkbox" name="swit_nofollow" <?php checked($nofollow,1); ?>> NOFOLLOW</label>
    </p>
    <p>
        <label><strong>Canonical URL</strong></label>
        <input type="url" name="swit_canonical" class="widefat" value="<?php echo esc_attr($canonical); ?>" placeholder="https://example.com/canonical-url">
    </p>
    <?php
}
add_action('save_post', function($post_id){
    if(!isset($_POST['swit_sa_meta_nonce']) || !wp_verify_nonce($_POST['swit_sa_meta_nonce'],'swit_sa_meta_save')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(isset($_POST['post_type']) && $_POST['post_type']=='page'){
        if(!current_user_can('edit_page',$post_id)) return;
    }else{
        if(!current_user_can('edit_post',$post_id)) return;
    }

    update_post_meta($post_id,'_swit_noindex',  !empty($_POST['swit_noindex']) ? 1 : 0);
    update_post_meta($post_id,'_swit_nofollow',!empty($_POST['swit_nofollow'])?1:0);

    if(!empty($_POST['swit_canonical'])){
        update_post_meta($post_id,'_swit_canonical',esc_url_raw(trim($_POST['swit_canonical'])));
    }else{
        delete_post_meta($post_id,'_swit_canonical');
    }
});

/* ---------------------------------------
 * Front-end output
 * ------------------------------------- */
add_filter('document_title_parts', function($parts){
    if(is_admin()) return $parts;
    $opt = swit_sa_get_options();
    $ctx = ['separator'=>$opt['separator']];

    if(is_front_page() || is_home()){
        $tpl = $opt['home_title'];
        $parts['title'] = swit_sa_apply_smart_tags($tpl,$ctx);
    } elseif(is_singular('post')){
        $post = get_queried_object();
        $ctx['post'] = $post;
        $tpl = $opt['post_title'];
        $parts['title'] = swit_sa_apply_smart_tags($tpl,$ctx);
    } elseif(is_page()){
        $post = get_queried_object();
        $ctx['post'] = $post;
        $tpl = $opt['page_title'];
        $parts['title'] = swit_sa_apply_smart_tags($tpl,$ctx);
    } elseif(is_category()){
        $term = get_queried_object();
        $ctx['term'] = $term;
        $tpl = $opt['cat_title'];
        $parts['title'] = swit_sa_apply_smart_tags($tpl,$ctx);
    } elseif(is_tag()){
        $term = get_queried_object();
        $ctx['term'] = $term;
        $tpl = $opt['tag_title'];
        $parts['title'] = swit_sa_apply_smart_tags($tpl,$ctx);
    }

    return $parts;
},20);

add_action('wp_head', function(){
    if(is_admin()) return;
    $opt = swit_sa_get_options();
    $ctx = ['separator'=>$opt['separator']];

    $desc = '';
    $robots = ['noindex'=>0,'nofollow'=>0];
    $canonical = '';

    if(is_front_page() || is_home()){
        $desc = swit_sa_apply_smart_tags($opt['home_desc'],$ctx);
        $robots = $opt['robots_home'];
        $canonical = home_url('/');
    }
    elseif(is_singular('post')){
        $post = get_queried_object();
        $ctx['post'] = $post;
        $desc_meta = get_post_meta($post->ID,'_ayc_meta_description',true);
        $desc = !empty($desc_meta)?wp_strip_all_tags($desc_meta):swit_sa_apply_smart_tags($opt['post_desc'],$ctx);
        $robots = $opt['robots_post'];
        $noindex  = (int)get_post_meta($post->ID,'_swit_noindex',true);
        $nofollow = (int)get_post_meta($post->ID,'_swit_nofollow',true);
        if($noindex)  $robots['noindex'] = 1;
        if($nofollow) $robots['nofollow'] = 1;
        $canonical = get_post_meta($post->ID,'_swit_canonical',true);
        if(!$canonical) $canonical = get_permalink($post);
    }
    elseif(is_page()){
        $post = get_queried_object();
        $ctx['post'] = $post;
        $desc_meta = get_post_meta($post->ID,'_ayc_meta_description',true);
        $desc = !empty($desc_meta)?wp_strip_all_tags($desc_meta):swit_sa_apply_smart_tags($opt['page_desc'],$ctx);
        $robots = $opt['robots_page'];
        $noindex  = (int)get_post_meta($post->ID,'_swit_noindex',true);
        $nofollow = (int)get_post_meta($post->ID,'_swit_nofollow',true);
        if($noindex)  $robots['noindex'] = 1;
        if($nofollow) $robots['nofollow'] = 1;
        $canonical = get_post_meta($post->ID,'_swit_canonical',true);
        if(!$canonical) $canonical = get_permalink($post);
    }
    elseif(is_category()){
        $term = get_queried_object();
        $ctx['term'] = $term;
        $desc = swit_sa_apply_smart_tags($opt['cat_desc'],$ctx);
        $robots = $opt['robots_cat'];
        $canonical = get_term_link($term);
    }
    elseif(is_tag()){
        $term = get_queried_object();
        $ctx['term'] = $term;
        $desc = swit_sa_apply_smart_tags($opt['tag_desc'],$ctx);
        $robots = $opt['robots_tag'];
        $canonical = get_term_link($term);
    }

    if($desc) echo '<meta name="description" content="'.esc_attr($desc).'" />'."\n";
    $rb = [];
    $rb[] = !empty($robots['noindex'])?'noindex':'index';
    $rb[] = !empty($robots['nofollow'])?'nofollow':'follow';
    echo '<meta name="robots" content="'.esc_attr(implode(',',$rb)).'" />'."\n";
    if($canonical) echo '<link rel="canonical" href="'.esc_url($canonical).'" />'."\n";
},20);

/* ---------------------------------------
 * Activation hook
 * ------------------------------------- */
register_activation_hook(dirname(__FILE__,2) . '/ai-yoast-clone.php', function(){
    if(false===get_option(SWIT_SA_OPTION,false)){
        add_option(SWIT_SA_OPTION,swit_sa_default_options());
    }
});
