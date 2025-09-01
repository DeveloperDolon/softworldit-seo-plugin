<div class="wrap">
    <h1><?php _e('404 Error Log', 'softworld-ai-seo'); ?></h1>
    <p><?php _e('This page logs all "404 Not Found" errors on your website.', 'softworld-ai-seo'); ?></p>

    <?php
    if (isset($_GET['message']) && $_GET['message'] === 'cleared') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Log cleared successfully.', 'softworld-ai-seo') . '</p></div>';
    }
    ?>

    <form method="post" action="">
        <input type="hidden" name="action" value="clear_404_log">
        <?php wp_nonce_field('clear_404_log_action', 'clear_404_log_nonce'); ?>
        <?php submit_button(__('Clear Log', 'softworld-ai-seo'), 'delete'); ?>
    </form>
    <hr/>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php _e('URL', 'softworld-ai-seo'); ?></th>
                <th scope="col"><?php _e('Timestamp', 'softworld-ai-seo'); ?></th>
                <th scope="col"><?php _e('Referrer', 'softworld-ai-seo'); ?></th>
                <th scope="col"><?php _e('User Agent', 'softworld-ai-seo'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $log_entries = $this->get_log_entries();
            if ($log_entries) :
                foreach ($log_entries as $entry) : ?>
                    <tr>
                        <td><?php echo esc_html($entry->url); ?></td>
                        <td><?php echo esc_html($entry->timestamp); ?></td>
                        <td><?php echo esc_html($entry->referrer ? $entry->referrer : 'N/A'); ?></td>
                        <td><?php echo esc_html($entry->user_agent); ?></td>
                    </tr>
                <?php endforeach;
            else : ?>
                <tr>
                    <td colspan="4"><?php _e('No 404 errors logged yet.', 'softworld-ai-seo'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>