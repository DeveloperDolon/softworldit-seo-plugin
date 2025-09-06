<?php
if (!defined('ABSPATH')) exit; $opts = get_option('dxseo_settings');
?>
<div class="wrap dxseo-wrap">
  <h1 class="dxseo-h1">Redirect on Slug Change & Deleted Content</h1>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('dxseo_save_settings','_wpnonce'); ?>
    <input type="hidden" name="action" value="dxseo_save_settings"/>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">Slug Change</h2>
      <label class="toggle"><input type="checkbox" name="autocreate_slug_redirect" value="1" <?php checked(!empty($opts['autocreate_slug_redirect'])); ?>/> Auto-create 301 when a post slug changes</label>
    </div>

    <div class="dxseo-card">
      <h2 class="dxseo-h2">Deleted Content</h2>
      <p>Pick default behavior when content is deleted.</p>
      <select name="deleted_behavior">
        <option value="410" <?php selected(($opts['deleted_behavior'] ?? '')==='410'); ?>>410 Gone (recommended)</option>
        <option value="homepage" <?php selected(($opts['deleted_behavior'] ?? '')==='homepage'); ?>>Redirect to Homepage (301)</option>
        <option value="parent" <?php selected(($opts['deleted_behavior'] ?? '')==='parent'); ?>>Redirect to Parent (301)</option>
      </select>
    </div>

    <p><button class="button button-primary">Save Changes</button></p>
  </form>
</div>
