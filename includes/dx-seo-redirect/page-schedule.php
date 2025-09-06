<?php
if (!defined('ABSPATH')) exit; global $wpdb; $table = DXSEO_Helpers::table();
$items = $wpdb->get_results("SELECT * FROM $table WHERE (start_at IS NOT NULL OR end_at IS NOT NULL) ORDER BY id DESC LIMIT 200");
?>
<div class="wrap dxseo-wrap">
  <h1 class="dxseo-h1">Schedule Redirect</h1>
  <div class="dxseo-card">
    <div class="dxseo-grid">
      <input type="hidden" id="dxseo-id"/>
      <label>From Path/Pattern</label>
      <input id="dxseo-from" type="text" placeholder="/campaign"/>
      <label>To Path</label>
      <input id="dxseo-to" type="text" placeholder="/campaign-2025"/>
      <label>Regex?</label>
      <select id="dxseo-regex"><option value="0">No</option><option value="1">Yes</option></select>
      <label>Status</label>
      <select id="dxseo-code"><option>301</option><option>302</option><option>307</option><option>308</option></select>
      <label>Active</label>
      <select id="dxseo-active"><option value="1">Enabled</option><option value="0">Disabled</option></select>
      <label>Start (YYYY-MM-DD HH:MM)</label>
      <input id="dxseo-start" type="text" placeholder="2025-09-05 00:00"/>
      <label>End (optional)</label>
      <input id="dxseo-end" type="text" placeholder="2025-09-12 23:59"/>
      <label>Note</label>
      <input id="dxseo-note" type="text" placeholder="Promo week"/>
      <button class="button button-primary" id="dxseo-save">Save Schedule</button>
    </div>
  </div>

  <h2 class="dxseo-h2">Scheduled Rules</h2>
  <table class="widefat fixed striped">
    <thead><tr>
      <th>ID</th><th>From</th><th>To</th><th>Start</th><th>End</th><th>Active</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php foreach($items as $r): ?>
        <tr data-id="<?php echo esc_attr($r->id); ?>">
          <td><?php echo intval($r->id); ?></td>
          <td><?php echo esc_html($r->from_path); ?></td>
          <td><?php echo esc_html($r->to_path); ?></td>
          <td><?php echo esc_html($r->start_at); ?></td>
          <td><?php echo esc_html($r->end_at); ?></td>
          <td><?php echo $r->active? 'On':'Off'; ?></td>
          <td>
            <button class="button dxseo-edit">Edit</button>
            <button class="button dxseo-delete">Delete</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
