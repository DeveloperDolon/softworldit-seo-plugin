(function(){
    jQuery(function($){
        // Enable runtime debug
        $('#dx-enable-debug').on('click', function(){
            if (!confirm('Enable runtime debug (display_errors) for this request?')) return;
            $.post(dxseoAjax.ajax_url, {action:'dxseo_enable_runtime_debug', _ajax_nonce: dxseoAjax.nonce}, function(r){
                alert(r.data.message || 'Done');
                location.reload();
            });
        });

        // Flush rewrite rules
        $('#dx-flush-rewrites').on('click', function(){
            if (!confirm('Flush rewrite rules?')) return;
            $.post(dxseoAjax.ajax_url, {action:'dxseo_flush_rewrites', _ajax_nonce: dxseoAjax.nonce}, function(r){
                alert(r.data.message || 'Done');
            });
        });

        // Run WP-Cron
        $('#dx-run-wp-cron').on('click', function(){
            if (!confirm('Run WP-Cron now? This will attempt to run scheduled tasks.')) return;
            $.get(ajaxurl + '?doing_wp_cron', function(){
                alert('WP-Cron request sent (browser-initiated). Check Action Scheduler logs.');
            });
        });

        // Capture JS errors
        window.onerror = function(message, source, lineno, colno, error){
            var payload = JSON.stringify({message:message, source:source, lineno:lineno, colno:colno, stack: error && error.stack ? error.stack : null});
            $.post(dxseoAjax.ajax_url, {action:'dxseo_log_js_error', payload: payload, _ajax_nonce: dxseoAjax.nonce});
        };

        // One-time secret
        $('#dx-generate-secret').on('click', function(e){
            e.preventDefault();
            var payload = $('#dx-payload').val();
            var ttl = parseInt($('#dx-ttl').val()||60,10);
            if (!payload) { alert('Enter secret payload'); return; }

            $.post(ajaxurl, {action:'dxseo_create_one_time_secret', payload: payload, ttl: ttl, _ajax_nonce: dxseoAjax.nonce}, function(resp){
                if (resp && resp.data && resp.data.url) {
                    $('#dx-one-time-result').html('<p>One-time URL (copy and share): <a target="_blank" href="'+resp.data.url+'">'+resp.data.url+'</a></p><p>Note: URL will work only once and expires in '+ttl+' minutes.</p>');
                } else {
                    alert('Failed to create one-time secret.'); console.log(resp);
                }
            }).fail(function(xhr){
                alert('Failed to create one-time secret. Check server response.'); console.error(xhr.responseText);
            });
        });

    });
})();
