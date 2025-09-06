<?php
if (!defined('ABSPATH')) exit; $opts = get_option('dxseo_settings');
?>
<div class="wrap dxseo-wrap">
  <h1 class="dxseo-h1">Crawl Cleanup</h1>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('dxseo_save_settings','_wpnonce'); ?>
    <input type="hidden" name="action" value="dxseo_save_settings"/>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">Disallow Query Parameters (robots.txt)</h2>
      <p>One per line. Supports wildcards like <code>utm_*</code>.</p>
      <textarea name="disallow_params" rows="6" class="widefat"><?php echo esc_textarea(implode("\n", $opts['disallow_params'] ?? [])); ?></textarea>
    </div>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">Noindex URL Patterns</h2>
      <p>Regex patterns. Example: <code>^/tag/</code>, <code>^/search</code></p>
      <textarea name="noindex_patterns" rows="6" class="widefat"><?php echo esc_textarea(implode("\n", $opts['noindex_patterns'] ?? [])); ?></textarea>
    </div>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">410 Gone Patterns</h2>
      <p>Regex patterns. Requests matching will return <strong>410</strong> to free crawl budget.</p>
      <textarea name="gone_patterns" rows="6" class="widefat"><?php echo esc_textarea(implode("\n", $opts['gone_patterns'] ?? [])); ?></textarea>
    </div>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">Other Tweaks</h2>
      <label class="toggle"><input type="checkbox" name="disable_media_pages" value="1" <?php checked(!empty($opts['disable_media_pages'])); ?>/> Disable attachment pages (redirect to parent)</label>
      <label class="toggle"><input type="checkbox" name="strip_replytocom" value="1" <?php checked(!empty($opts['strip_replytocom'])); ?>/> Strip <code>?replytocom=</code> parameter</label>
      <label class="toggle"><input type="checkbox" name="apply_to_404_only" value="1" <?php checked(!empty($opts['apply_to_404_only'])); ?>/> Apply redirect engine only on 404 pages</label>
    </div>

    <p><button class="button button-primary">Save Cleanup Rules</button></p>
  </form>

  <div class="dxseo-card">
    <h2 class="dxseo-h2">Tips</h2>
    <ul class="ul-disc">
      <li>Use <strong>410 Gone</strong> for permanently removed content.</li>
      <li>Disallow noisy tracking params to focus crawl on canonical URLs.</li>
      <li>Noindex thin or faceted pages that you don’t want indexed.</li>
    </ul>
  </div>
</div>
