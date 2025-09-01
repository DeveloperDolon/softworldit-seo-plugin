// jQuery(document).ready(function($) {
//     $('#softworld-bulk-seo-form').on('submit', function(e) {
//         e.preventDefault();
        
//         var form = $(this);
//         var spinner = form.find('.spinner');
        
//         spinner.addClass('is-active');
        
//         // Prepare data for AJAX
//         var data = {
//             action: 'softworld_bulk_update_seo',
//             nonce: softworld_ajax.nonce,
//             seo_data: {}
//         };
        
//         // Collect all the SEO data
//         form.find('textarea[name^="seo_data"]').each(function() {
//             var name = $(this).attr('name');
//             var matches = name.match(/seo_data\[(\d+)\]\[(\w+)\]/);
            
//             if (matches && matches.length === 3) {
//                 var postId = matches[1];
//                 var field = matches[2];
                
//                 if (!data.seo_data[postId]) {
//                     data.seo_data[postId] = {};
//                 }
                
//                 data.seo_data[postId][field] = $(this).val();
//             }
//         });
        
//         $.post(softworld_ajax.ajax_url, data, function(response) {
//             spinner.removeClass('is-active');
            
//             if (response.success) {
//                 alert(response.data.message);
//             } else {
//                 alert('Error updating SEO data');
//             }
//         }).fail(function() {
//             spinner.removeClass('is-active');
//             alert('Error updating SEO data');
//         });
//     });
// });