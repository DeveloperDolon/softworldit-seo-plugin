jQuery(document).ready(function($) {
    // Update selected count
    function updateSelectedCount() {
        const selectedCount = $('.page-checkbox:checked').length;
        $('#pages-selected-count').text(selectedCount);
    }
    
    // Initial count update
    updateSelectedCount();
    
    // Update count on checkbox change
    $('.page-checkbox').on('change', updateSelectedCount);
    
    // Search functionality
    $('#pages-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.exclusion-checkbox-item').each(function() {
            const title = $(this).find('.page-title').text().toLowerCase();
            const id = $(this).find('.page-id').text().toLowerCase();
            
            if (title.includes(searchTerm) || id.includes(searchTerm)) {
                $(this).removeClass('hidden');
            } else {
                $(this).addClass('hidden');
            }
        });
    });
});

// Select all pages
function selectAllPages() {
    document.querySelectorAll('.page-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    document.getElementById('pages-selected-count').textContent = 
        document.querySelectorAll('.page-checkbox').length;
}

// Deselect all pages
function deselectAllPages() {
    document.querySelectorAll('.page-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('pages-selected-count').textContent = '0';
}

// Clear search
function clearPagesSearch() {
    document.getElementById('pages-search').value = '';
    document.querySelectorAll('.exclusion-checkbox-item').forEach(item => {
        item.classList.remove('hidden');
    });
}


jQuery(document).ready(function($) {
    // Search functionality for exclusion lists
    $('#posts-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.post-checkbox').each(function() {
            var title = $(this).data('title').toLowerCase();
            if (title.indexOf(value) > -1) {
                $(this).closest('.exclusion-checkbox-item').show();
            } else {
                $(this).closest('.exclusion-checkbox-item').hide();
            }
        });
    });

    $('#pages-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.page-checkbox').each(function() {
            var title = $(this).data('title').toLowerCase();
            if (title.indexOf(value) > -1) {
                $(this).closest('.exclusion-checkbox-item').show();
            } else {
                $(this).closest('.exclusion-checkbox-item').hide();
            }
        });
    });

    // Update selected counts
    function updateSelectedCounts() {
        var postsSelected = $('.post-checkbox:checked').length;
        var pagesSelected = $('.page-checkbox:checked').length;
        $('#posts-selected-count').text(postsSelected);
        $('#pages-selected-count').text(pagesSelected);
    }

    $('.post-checkbox, .page-checkbox').on('change', updateSelectedCounts);
    updateSelectedCounts();
});

// Select all/deselect all functions
function selectAllPosts() {
    jQuery('.post-checkbox').prop('checked', true).trigger('change');
}

function deselectAllPosts() {
    jQuery('.post-checkbox').prop('checked', false).trigger('change');
}

function selectAllPages() {
    jQuery('.page-checkbox').prop('checked', true).trigger('change');
}

function deselectAllPages() {
    jQuery('.page-checkbox').prop('checked', false).trigger('change');
}

function clearPostsSearch() {
    jQuery('#posts-search').val('').trigger('keyup');
}

function clearPagesSearch() {
    jQuery('#pages-search').val('').trigger('keyup');
}

// Add any custom javascript for your admin pages here
(function($) {
    'use strict';
    // Example: Confirmation for delete actions
    $(document).ready(function() {
        $('.delete-link').on('click', function() {
            return confirm('Are you sure you want to delete this item?');
        });
    });
})(jQuery);
