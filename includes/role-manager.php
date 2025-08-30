<?php
/**
 * Role Manager for Softworld AI SEO Plugin
 * Handles role-based access control and meta box visibility
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Softworld_AI_SEO_Role_Manager {
    
    private $capability;
    private $settings_page;
    
    public function __construct() {
        $this->capability = 'manage_options';
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Meta box control
        add_action('add_meta_boxes', array($this, 'control_meta_box_visibility'), 99);
        
        // Capability control
        add_filter('user_has_cap', array($this, 'filter_capabilities'), 10, 4);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'softworld-ai-seo',
            __('Role Access Settings', 'softworld-ai-seo'),
            __('Role Access', 'softworld-ai-seo'),
            $this->capability,
            'softworld-ai-seo-role-access',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('softworld_ai_seo_role_settings', 'softworld_ai_seo_roles', array(
            'sanitize_callback' => array($this, 'sanitize_role_settings')
        ));
        
        add_settings_section(
            'softworld_ai_seo_role_access_section',
            __('Role-Based Access Control', 'softworld-ai-seo'),
            array($this, 'render_section_description'),
            'softworld-ai-seo-role-access'
        );
        
        add_settings_field(
            'allowed_roles',
            __('Roles with Access to Settings', 'softworld-ai-seo'),
            array($this, 'render_allowed_roles_field'),
            'softworld-ai-seo-role-access',
            'softworld_ai_seo_role_access_section'
        );
        
        add_settings_field(
            'hidden_meta_box_roles',
            __('Hide Meta Box From Roles', 'softworld-ai-seo'),
            array($this, 'render_meta_box_roles_field'),
            'softworld-ai-seo-role-access',
            'softworld_ai_seo_role_access_section'
        );
    }
    
    public function render_section_description() {
        echo '<p>' . __('Control which user roles can access plugin settings and view the SEO meta box.', 'softworld-ai-seo') . '</p>';
    }
    
    public function render_allowed_roles_field() {
        $options = get_option('softworld_ai_seo_roles', array());
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');
        $roles = get_editable_roles();
        
        foreach ($roles as $role_id => $role_info) {
            $checked = in_array($role_id, $allowed_roles) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="softworld_ai_seo_roles[allowed_roles][]" value="' . esc_attr($role_id) . '" ' . $checked . '> ' . esc_html($role_info['name']) . '</label><br>';
        }
    }
    
    public function render_meta_box_roles_field() {
        $options = get_option('softworld_ai_seo_roles', array());
        $hidden_roles = isset($options['hidden_meta_box_roles']) ? $options['hidden_meta_box_roles'] : array();
        $roles = get_editable_roles();
        
        foreach ($roles as $role_id => $role_info) {
            $checked = in_array($role_id, $hidden_roles) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="softworld_ai_seo_roles[hidden_meta_box_roles][]" value="' . esc_attr($role_id) . '" ' . $checked . '> ' . esc_html($role_info['name']) . '</label><br>';
        }
        
        echo '<p class="description">' . __('The SEO meta box will be hidden from selected roles on post edit screens.', 'softworld-ai-seo') . '</p>';
    }
    
    public function sanitize_role_settings($input) {
        $output = array();
        $roles = array_keys(get_editable_roles());
        
        // Validate allowed roles
        if (isset($input['allowed_roles']) && is_array($input['allowed_roles'])) {
            $output['allowed_roles'] = array();
            foreach ($input['allowed_roles'] as $role) {
                if (in_array($role, $roles)) {
                    $output['allowed_roles'][] = $role;
                }
            }
            // Always ensure administrator has access
            if (!in_array('administrator', $output['allowed_roles'])) {
                $output['allowed_roles'][] = 'administrator';
            }
        } else {
            $output['allowed_roles'] = array('administrator');
        }
        
        // Validate hidden meta box roles
        if (isset($input['hidden_meta_box_roles']) && is_array($input['hidden_meta_box_roles'])) {
            $output['hidden_meta_box_roles'] = array();
            foreach ($input['hidden_meta_box_roles'] as $role) {
                if (in_array($role, $roles)) {
                    $output['hidden_meta_box_roles'][] = $role;
                }
            }
        } else {
            $output['hidden_meta_box_roles'] = array();
        }
        
        return $output;
    }
    
    public function render_settings_page() {
        if (!current_user_can($this->capability)) {
            return;
        }
        
        // Show success message if settings were saved
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'softworld_ai_seo_messages',
                'softworld_ai_seo_message',
                __('Settings saved.', 'softworld-ai-seo'),
                'success'
            );
        }
        
        settings_errors('softworld_ai_seo_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('softworld_ai_seo_role_settings');
                do_settings_sections('softworld-ai-seo-role-access');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function control_meta_box_visibility() {
        global $wp_meta_boxes;
        
        $options = get_option('softworld_ai_seo_roles', array());
        $hidden_roles = isset($options['hidden_meta_box_roles']) ? $options['hidden_meta_box_roles'] : array();
        
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Check if current user has any of the hidden roles
        $should_hide = !empty(array_intersect($user_roles, $hidden_roles));
        
        if ($should_hide) {
            // Remove our SEO meta box
            remove_meta_box('softworld_ai_seo_meta_box', ['post', 'page'], 'normal');
        }
    }
    
    public function filter_capabilities($allcaps, $caps, $args, $user) {
        $options = get_option('softworld_ai_seo_roles', array());
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');
        
        // Check if we're dealing with our plugin's capabilities
        if (in_array('manage_softworld_ai_seo', $caps)) {
            $user_roles = $user->roles;
            
            // Check if user has any of the allowed roles
            $has_access = !empty(array_intersect($user_roles, $allowed_roles));
            
            if ($has_access) {
                foreach ($caps as $cap) {
                    $allcaps[$cap] = true;
                }
            } else {
                foreach ($caps as $cap) {
                    $allcaps[$cap] = false;
                }
            }
        }
        
        return $allcaps;
    }
}