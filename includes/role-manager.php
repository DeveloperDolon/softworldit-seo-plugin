<?php
/**
 * Role Manager for Softworld AI SEO Plugin
 * Handles role-based access control and meta box visibility
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Softworld_AI_SEO_Role_Manager
{

    private $capability;
    private $parent_slug;

    public function __construct($parent_slug = 'softworld-seo')
    {
        $this->capability = 'manage_options';
        $this->parent_slug = $parent_slug;
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Meta box control
        add_action('add_meta_boxes', array($this, 'control_meta_box_visibility'), 99);

        // Capability control
        add_filter('user_has_cap', array($this, 'filter_capabilities'), 10, 4);

        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            $this->parent_slug,
            __('Role Access Settings', 'softworld-ai-seo'),
            __('Role Access', 'softworld-ai-seo'),
            $this->capability,
            'softworld-ai-seo-role-access',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings()
    {
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

    public function render_section_description()
    {
        echo '<p>' . __('Control which user roles can access plugin settings and view the SEO meta box.', 'softworld-ai-seo') . '</p>';
    }

    public function render_allowed_roles_field()
    {
        $options = get_option('softworld_ai_seo_roles', array());
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');
        $roles = get_editable_roles();

        echo '<div class="role-checkbox-group">';
        foreach ($roles as $role_id => $role_info) {
            // Administrator always has access
            if ($role_id === 'administrator') {
                $checked = 'checked="checked" disabled';
                echo '<label class="role-checkbox-label"><input type="checkbox" name="softworld_ai_seo_roles[allowed_roles][]" value="' . esc_attr($role_id) . '" ' . $checked . '> ' . esc_html($role_info['name']) . ' <em>(always has access)</em></label><br>';
            } else {
                $checked = in_array($role_id, $allowed_roles) ? 'checked="checked"' : '';
                echo '<label class="role-checkbox-label"><input type="checkbox" name="softworld_ai_seo_roles[allowed_roles][]" value="' . esc_attr($role_id) . '" ' . $checked . '> ' . esc_html($role_info['name']) . '</label><br>';
            }
        }
        echo '</div>';
    }

    public function render_meta_box_roles_field()
    {
        $options = get_option('softworld_ai_seo_roles', array());
        $hidden_roles = isset($options['hidden_meta_box_roles']) ? $options['hidden_meta_box_roles'] : array();
        $roles = get_editable_roles();

        echo '<div class="role-checkbox-group">';
        foreach ($roles as $role_id => $role_info) {
            $checked = in_array($role_id, $hidden_roles) ? 'checked="checked"' : '';
            echo '<label class="role-checkbox-label"><input type="checkbox" name="softworld_ai_seo_roles[hidden_meta_box_roles][]" value="' . esc_attr($role_id) . '" ' . $checked . '> ' . esc_html($role_info['name']) . '</label><br>';
        }
        echo '</div>';

        echo '<p class="description">' . __('The SEO meta box will be hidden from selected roles on post edit screens.', 'softworld-ai-seo') . '</p>';
    }

    public function sanitize_role_settings($input)
    {
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

    public function render_settings_page()
    {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
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
                submit_button(__('Save Settings', 'softworld-ai-seo'));
                ?>
            </form>

            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #0073aa;">
                <h3>How to Assign Roles to Users</h3>
                <p>To assign the SEO Manager or SEO Editor role to users:</p>
                <ol>
                    <li>Go to <a href="<?php echo admin_url('users.php'); ?>">Users → All Users</a></li>
                    <li>Click on the user you want to modify</li>
                    <li>Scroll down to the "Role" dropdown menu</li>
                    <li>Select "SEO Manager" or "SEO Editor" from the dropdown</li>
                    <li>Click "Update User" to save changes</li>
                </ol>
                <p><strong>Available Custom Roles:</strong></p>
                <ul>
                    <li><strong>SEO Manager</strong>: Can manage posts, categories, and access plugin settings</li>
                    <li><strong>SEO Editor</strong>: Can edit posts but cannot publish or access plugin settings</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function control_meta_box_visibility()
    {
        global $pagenow;

        // Only run on post edit pages
        if (!in_array($pagenow, array('post.php', 'post-new.php'))) {
            return;
        }

        $options = get_option('softworld_ai_seo_roles', array());
        $hidden_roles = isset($options['hidden_meta_box_roles']) ? $options['hidden_meta_box_roles'] : array();

        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        // Check if current user has any of the hidden roles
        $should_hide = !empty(array_intersect($user_roles, $hidden_roles));

        if ($should_hide) {
            // Remove our SEO meta box with lower priority (runs after it's added)
            add_action('add_meta_boxes', array($this, 'remove_seo_meta_box'), 20);
        }
    }

    public function remove_seo_meta_box()
    {
        remove_meta_box('softworld_ai_seo_meta_box', 'post', 'normal');
        remove_meta_box('softworld_ai_seo_meta_box', 'page', 'normal');
    }

    public function filter_capabilities($allcaps, $caps, $args, $user)
    {
        $options = get_option('softworld_ai_seo_roles', array());
        $allowed_roles = isset($options['allowed_roles']) ? $options['allowed_roles'] : array('administrator');

        // Check if we're dealing with our plugin's capabilities
        $plugin_caps = array('manage_softworld_ai_seo', 'softworld_ai_seo_manage_settings');
        $matched_caps = array_intersect($caps, $plugin_caps);

        if (!empty($matched_caps)) {
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

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'softworld-ai-seo-role-access') === false) {
            return;
        }

        wp_enqueue_style(
            'softworld-ai-seo-role-admin',
            plugin_dir_url(__FILE__) . '../assets/css/role-admin.css',
            array(),
            '1.0.0'
        );
    }
}