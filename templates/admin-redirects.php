<div class="wrap">
    <h1><?php _e('Redirect Manager', 'softworld-ai-seo'); ?></h1>

    <div id="col-container">
        <div id="col-right">
            <div class="col-wrap">
                <h2><?php _e('Current Redirects', 'softworld-ai-seo'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Source URL', 'softworld-ai-seo'); ?></th>
                            <th scope="col"><?php _e('Target URL', 'softworld-ai-seo'); ?></th>
                            <th scope="col"><?php _e('Type', 'softworld-ai-seo'); ?></th>
                            <th scope="col"><?php _e('Actions', 'softworld-ai-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $redirects = $this->get_all_redirects();
                        if ($redirects) :
                            foreach ($redirects as $redirect) : ?>
                                <tr>
                                    <td><?php echo esc_url($redirect->source_url); ?></td>
                                    <td><?php echo esc_url($redirect->target_url); ?></td>
                                    <td><?php echo esc_html($redirect->redirect_type); ?></td>
                                    <td>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=softworld-ai-seo-redirects&action=delete_redirect&redirect_id=' . $redirect->id), 'delete_redirect_' . $redirect->id); ?>" class="delete-link" onclick="return confirm('Are you sure?')"><?php _e('Delete', 'softworld-ai-seo'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else : ?>
                            <tr>
                                <td colspan="4"><?php _e('No redirects found.', 'softworld-ai-seo'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="col-left">
            <div class="col-wrap">
                <h2><?php _e('Add New Redirect', 'softworld-ai-seo'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('add_redirect', 'add_redirect_nonce'); ?>
                    <div class="form-field">
                        <label for="source_url"><?php _e('Source URL', 'softworld-ai-seo'); ?></label>
                        <input type="url" name="source_url" id="source_url" required="required" placeholder="https://www.yourdomain.com/old-url">
                        <p><?php _e('The full URL you want to redirect from.', 'softworld-ai-seo'); ?></p>
                    </div>
                    <div class="form-field">
                        <label for="target_url"><?php _e('Target URL', 'softworld-ai-seo'); ?></label>
                        <input type="url" name="target_url" id="target_url" required="required" placeholder="https://www.yourdomain.com/new-url">
                        <p><?php _e('The full URL you want to redirect to.', 'softworld-ai-seo'); ?></p>
                    </div>
                    <div class="form-field">
                        <label for="redirect_type"><?php _e('Redirect Type', 'softworld-ai-seo'); ?></label>
                        <select name="redirect_type" id="redirect_type">
                            <option value="301"><?php _e('301 - Permanent', 'softworld-ai-seo'); ?></option>
                            <option value="302"><?php _e('302 - Temporary', 'softworld-ai-seo'); ?></option>
                        </select>
                    </div>
                    <?php submit_button(__('Add Redirect', 'softworld-ai-seo')); ?>
                </form>
            </div>
        </div>
    </div>
</div>