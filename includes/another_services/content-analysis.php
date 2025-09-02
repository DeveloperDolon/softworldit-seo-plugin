<?php
if (!defined('ABSPATH')) exit;

// Declare metrics only once
if (!function_exists('ayc_get_metrics')) {
    function ayc_get_metrics() {
        return [
            'title'          => 'Keyword in Title',
            'description'    => 'Keyword in Description',
            'intro'          => 'Keyword in First Paragraph',
            'subheadings'    => 'Keyword in Subheadings (H2/H3)',
            'kp_density'     => 'Keyphrase Density',
            'internal_links' => 'Internal Links',
            'external_links' => 'External Links',
            'image_alt'      => 'Image Alt Text',
            'sentence_len'   => 'Sentence Length',
            'passive'        => 'Passive Voice',
            'transition'     => 'Transition Words',
            'flesch'         => 'Flesch Reading Ease',
        ];
    }
}

// Render content analysis box
function ayc_content_analysis_box($post) {
    $suggestions = get_post_meta($post->ID, '_ayc_suggestions', true);
    if (!$suggestions) $suggestions = [];

    echo '<div class="ayc-analysis-box">';
    echo '<h3>Content Analysis</h3>';
    echo '<ul>';

    foreach (ayc_get_metrics() as $field => $label) {
        $note = $suggestions[$field] ?? '';
        // Default status – can be updated dynamically if you integrate real analysis
        $status_icon = '⚠️ Check';
        $status_class = 'check';
        if (in_array($field, ['title','intro','subheadings'])) {
            $status_icon = '❌ Poor';
            $status_class = 'poor';
        } elseif (in_array($field, ['description'])) {
            $status_icon = '✅ Good';
            $status_class = 'good';
        }

        echo '<li>';
        echo "<strong>$label:</strong> <span class='status $status_class'>$status_icon</span> ";
        echo "<button type='button' class='ayc-suggest-btn' data-field='$field'>💡 Suggest</button>";
        echo "<input type='text' name='ayc_suggestions[$field]' value='".esc_attr($note)."' placeholder='Add suggestion...' class='ayc-suggest-input' style='display:none;'>";
        echo '</li>';
    }

    echo '</ul></div>';

    // Toggle suggestion input
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.ayc-suggest-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                let input = this.nextElementSibling;
                input.style.display = (input.style.display === "none") ? "inline-block" : "none";
            });
        });
    });
    </script>
    <?php
}

// Add meta box
add_action('add_meta_boxes', function() {
    add_meta_box('ayc_content_analysis', 'AI SEO - Content Analysis', 'ayc_content_analysis_box', ['post','page'], 'normal', 'high');
});

// Save suggestions
add_action('save_post', function($post_id) {
    if (isset($_POST['ayc_suggestions'])) {
        update_post_meta($post_id, '_ayc_suggestions', array_map('sanitize_text_field', $_POST['ayc_suggestions']));
    }
});
