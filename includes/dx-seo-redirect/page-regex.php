<?php
if (!defined('ABSPATH')) exit; global $wpdb; $table = DXSEO_Helpers::table();
$items = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 500");
?>
<div class="wrap dxseo-wrap">
  <h1 class="dxseo-h1">Regex Redirects</h1>
  <p class="desc">/old/(.*) → /new/$1, support for 301/302/307/308/410. Use leading "/" for paths. Regex is optional.</p>
  <div class="dxseo-card">
    <div class="dxseo-grid">
      <input type="hidden" id="dxseo-id"/>
      <label>From Path/Pattern</label>
      <input id="dxseo-from" type="text" placeholder="/old/(.*)"/>
      <label>To Path (or blank for 410)</label>
      <input id="dxseo-to" type="text" placeholder="/new/$1"/>
      <label>Regex?</label>
      <select id="dxseo-regex"><option value="0">No</option><option value="1">Yes</option></select>
      <label>Status</label>
      <select id="dxseo-code">
        <option>301</option><option>302</option><option>307</option><option>308</option><option>410</option>
      </select>
      <label>Active</label>
      <select id="dxseo-active"><option value="1">Enabled</option><option value="0">Disabled</option></select>
      <label>Note</label>
      <input id="dxseo-note" type="text" placeholder="Optional note"/>
      <button class="button button-primary" id="dxseo-save">Save Redirect</button>
    </div>
  </div>

  <h2 class="dxseo-h2">Existing Rules</h2>
  <table class="widefat fixed striped">
    <thead><tr>
      <th>ID</th><th>From</th><th>To</th><th>Regex</th><th>Code</th><th>Active</th><th>Hits</th><th>Last Hit</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach($items as $r): ?>
      <tr data-id="<?php echo esc_attr($r->id); ?>">
        <td><?php echo intval($r->id); ?></td>
        <td><?php echo esc_html($r->from_path); ?></td>
        <td><?php echo esc_html($r->to_path); ?></td>
        <td><?php echo $r->regex? 'Yes':'No'; ?></td>
        <td><?php echo intval($r->status_code); ?></td>
        <td><?php echo $r->active? 'On':'Off'; ?></td>
        <td><?php echo intval($r->hits); ?></td>
        <td><?php echo esc_html($r->last_hit); ?></td>
        <td>
          <button class="button dxseo-edit">Edit</button>
          <button class="button dxseo-delete">Delete</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
