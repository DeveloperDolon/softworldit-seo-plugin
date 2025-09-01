<div class="wrap">
    <h1><?php _e('Bulk SEO Editor', 'softworld-ai-seo'); ?></h1>
   
    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>">
        <input type="hidden" name="page" value="<?php echo esc_attr($page_slug); ?>">
        <div class="tablenav top">
            <div class="alignleft actions">
                <label for="post-type-filter" class="screen-reader-text"><?php _e('Filter by post type', 'softworld-ai-seo'); ?></label>
                <select name="post_type" id="post-type-filter">
                    <option value=""><?php _e('All Post Types', 'softworld-ai-seo'); ?></option>
                    <?php foreach ($post_types as $type) : ?>
                        <option value="<?php echo esc_attr($type->name); ?>" <?php selected($post_type_filter, $type->name); ?>>
                            <?php echo esc_html($type->labels->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" class="button" value="<?php _e('Filter', 'softworld-ai-seo'); ?>">
            </div>
        </div>
    </form>
   
    <?php if (!empty($posts_query->posts)) : ?>
        <form id="softworld-bulk-seo-form">
            <?php wp_nonce_field('softworld_bulk_seo_nonce', 'softworld_bulk_nonce'); ?>
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th class="manage-column column-title"><?php _e('Title', 'softworld-ai-seo'); ?></th>
                        <th class="manage-column"><?php _e('SEO Title', 'softworld-ai-seo'); ?></th>
                        <th class="manage-column"><?php _e('SEO Description', 'softworld-ai-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts_query->posts as $post) : 
                        // Set up post data
                        setup_postdata($post);
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($post->post_title); ?></strong>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                            <?php _e('Edit', 'softworld-ai-seo'); ?>
                                        </a>
                                    </span> |
                                    <span class="view">
                                        <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                                            <?php _e('View', 'softworld-ai-seo'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <textarea name="seo_data[<?php echo $post->ID; ?>][title]" rows="2" class="large-text"><?php
                                    echo esc_textarea(get_post_meta($post->ID, '_softworld_ai_seo_title', true));
                                ?></textarea>
                            </td>
                            <td>
                                <textarea name="seo_data[<?php echo $post->ID; ?>][description]" rows="3" class="large-text"><?php
                                    echo esc_textarea(get_post_meta($post->ID, '_softworld_ai_seo_description', true));
                                ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
           
            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <input type="submit" class="button button-primary" value="<?php _e('Save Changes', 'softworld-ai-seo'); ?>">
                    <span class="spinner" style="float: none;"></span>
                </div>
               
                <div class="tablenav-pages">
                    <?php
                        $pagination_args = array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $posts_query->max_num_pages,
                            'current' => $paged
                        );
                       
                        if (!empty($post_type_filter)) {
                            $pagination_args['add_args'] = array('post_type' => $post_type_filter);
                        }
                       
                        echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        </form>
    <?php else : ?>
        <div class="notice notice-warning">
            <p><?php _e('No posts found for the selected post type.', 'softworld-ai-seo'); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#softworld-bulk-seo-form').on('submit', function(e) {
        e.preventDefault();
       
        var $spinner = $(this).find('.spinner');
        $spinner.addClass('is-active');
       
        var formData = $(this).serialize();
        formData += '&action=softworld_bulk_update_seo';
       
        // Use the localized ajax_url instead of ajaxurl
        var ajaxUrl = (typeof softworld_ajax !== 'undefined') ? softworld_ajax.ajax_url : ajaxurl;
       
        $.post(ajaxUrl, formData, function(response) {
            $spinner.removeClass('is-active');
           
            if(response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data);
            }
        }).fail(function() {
            $spinner.removeClass('is-active');
            alert('An error occurred. Please try again.');
        });
    });
});
</script>