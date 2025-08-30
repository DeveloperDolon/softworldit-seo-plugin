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
