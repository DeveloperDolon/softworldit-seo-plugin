<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap dxseo-wrap">
  <h1 class="dxseo-h1">Export & Import</h1>

  <div class="dxseo-card">
    <h2 class="dxseo-h2">Export CSV</h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('dxseo_export'); ?>
      <input type="hidden" name="action" value="dxseo_export"/>
      <button class="button button-primary">Download CSV</button>
    </form>
  </div>

  <div class="dxseo-card">
    <h2 class="dxseo-h2">Import CSV</h2>
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <?php wp_nonce_field('dxseo_import'); ?>
      <input type="hidden" name="action" value="dxseo_import"/>
      <input type="file" name="dxseo_csv" accept=".csv" required />
      <button class="button button-primary">Import</button>
    </form>
  </div>
</div>
