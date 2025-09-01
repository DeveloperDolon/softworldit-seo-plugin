<div class="wrap">
    <h1><?php _e('Crawl Optimization', 'softworld-ai-seo'); ?></h1>
    <p><?php _e('Optimize your site\'s crawl budget by disabling unnecessary archives and feeds.', 'softworld-ai-seo'); ?></p>

    <form method="post" action="options.php">
        <?php
        settings_fields('softworld_ai_seo_crawl_options');
        do_settings_sections('softworld-ai-seo-crawl-optimizer');
        submit_button();
        ?>
    </form>
</div>