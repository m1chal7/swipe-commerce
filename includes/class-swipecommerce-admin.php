<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.7
 * @package    SwipeCommerce_Pro
 * @subpackage SwipeCommerce_Pro/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Handles admin menu, pages, and form processing.
 */
class SwipeCommerce_Admin {

    /**
     * The categories manager instance.
     *
     * @since    1.0.7
     * @var      SwipeCommerce_Categories
     */
    private $categories;

    /**
     * The settings manager instance.
     *
     * @since    1.0.7
     * @var      SwipeCommerce_Settings
     */
    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.7
     * @param    SwipeCommerce_Categories    $categories    The categories manager instance.
     * @param    SwipeCommerce_Settings      $settings      The settings manager instance.
     */
    public function __construct($categories, $settings) {
        $this->categories = $categories;
        $this->settings = $settings;
        $this->init();
    }

    /**
     * Initialize admin hooks.
     *
     * @since    1.0.7
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);
        add_action('admin_init', array($this, 'process_form_submissions'));
    }

    /**
     * Add plugin admin menu and pages.
     *
     * @since    1.0.7
     */
    public function add_admin_menu() {
        $capability = class_exists('WooCommerce') ? 'manage_woocommerce' : 'manage_options';
        
        // Main menu page
        add_menu_page(
            __('SwipeCommerce', 'swipecommerce-pro'),
            __('SwipeCommerce', 'swipecommerce-pro'),
            $capability,
            'swipecommerce-pro',
            array($this, 'admin_page'),
            'dashicons-slides',
            56
        );
        
        // Main Settings submenu (rename the first submenu)
        add_submenu_page(
            'swipecommerce-pro',
            __('Main Settings', 'swipecommerce-pro'),
            __('Main Settings', 'swipecommerce-pro'),
            $capability,
            'swipecommerce-pro', // Same slug as parent to replace default
            array($this, 'admin_page')
        );
        
        // Custom Categories submenu
        add_submenu_page(
            'swipecommerce-pro',
            __('Custom Categories', 'swipecommerce-pro'),
            __('Custom Categories', 'swipecommerce-pro'),
            $capability,
            'swipecommerce-categories',
            array($this, 'categories_page')
        );
    }

    /**
     * Display the main admin page.
     *
     * @since    1.0.7
     */
    public function admin_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('swipecommerce_messages', 'swipecommerce_message', __('Settings Saved', 'swipecommerce-pro'), 'updated');
        }
        settings_errors('swipecommerce_messages');
        
        include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/admin-display.php';
    }

    /**
     * Display the categories management page.
     *
     * @since    1.0.7
     */
    public function categories_page() {
        $categories = $this->categories->get_custom_categories();
        $editing_category = null;
        
        // Check if editing a category
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editing_category = $this->categories->get_custom_category(sanitize_key($_GET['edit']));
        }
        
        // Include the categories page template
        include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/categories-display.php';
    }

    /**
     * Process form submissions from admin pages.
     *
     * @since    1.0.7
     */
    public function process_form_submissions() {
        // Handle category form submissions
        if (isset($_POST['swipecommerce_category_action'])) {
            $this->handle_category_form();
        }
    }

    /**
     * Handle category form submissions.
     *
     * @since    1.0.7
     */
    private function handle_category_form() {
        // Verify nonce
        if (!isset($_POST['swipecommerce_categories_nonce']) || 
            !wp_verify_nonce($_POST['swipecommerce_categories_nonce'], 'swipecommerce_categories_nonce')) {
            wp_die(__('Security check failed', 'swipecommerce-pro'));
        }

        // Check permissions
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'swipecommerce-pro'));
        }

        $action = sanitize_text_field($_POST['swipecommerce_category_action']);

        switch ($action) {
            case 'save_category':
                $this->handle_save_category_form();
                break;
            case 'delete_category':
                $this->handle_delete_category_form();
                break;
        }
    }

    /**
     * Handle saving category from form.
     *
     * @since    1.0.7
     */
    private function handle_save_category_form() {
        $received_products_string = $_POST['category_products'] ?? '';
        $products_array = array_filter(array_map('intval', explode(',', $received_products_string)));
        
        $category_id = sanitize_key($_POST['category_id'] ?? '');
        $is_editing = !empty($category_id) && $this->categories->get_custom_category($category_id);
        
        $category_data = array(
            'id' => $category_id,
            'name' => sanitize_text_field($_POST['category_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['category_description'] ?? ''),
            'color_scheme' => sanitize_text_field($_POST['color_scheme'] ?? 'gradient-pink'),
            'icon' => wp_kses($_POST['category_icon'] ?? '🏆', array()),
            'order' => intval($_POST['category_order'] ?? 1),
            'products' => $products_array,
            'visibility' => !empty($_POST['category_visibility'])
        );
        
        // Set editing flag to avoid duplicate ID validation error
        if ($is_editing) {
            $category_data['_editing'] = true;
        }
        
        // Generate ID if new category
        if (empty($category_data['id'])) {
            $category_data['id'] = sanitize_key(strtolower(str_replace(' ', '-', $category_data['name'])));
        }

        // Validate the data
        $validation = $this->categories->validate_category_data($category_data);
        if (!$validation['valid']) {
            $error_message = implode('<br>', $validation['errors']);
            add_settings_error('swipecommerce_messages', 'validation_error', $error_message, 'error');
            return;
        }
        
        if ($this->categories->save_custom_category($category_data)) {
            add_settings_error('swipecommerce_messages', 'category_saved', __('Category saved successfully!', 'swipecommerce-pro'), 'updated');
            
            // Redirect to avoid resubmission
            wp_redirect(admin_url('admin.php?page=swipecommerce-categories&saved=1'));
            exit;
        } else {
            add_settings_error('swipecommerce_messages', 'category_error', __('Failed to save category.', 'swipecommerce-pro'), 'error');
        }
    }

    /**
     * Handle deleting category from form.
     *
     * @since    1.0.7
     */
    private function handle_delete_category_form() {
        $category_id = sanitize_key($_POST['delete_category_id'] ?? '');
        
        if (!empty($category_id) && $this->categories->delete_custom_category($category_id)) {
            add_settings_error('swipecommerce_messages', 'category_deleted', __('Category deleted successfully!', 'swipecommerce-pro'), 'updated');
            
            // Redirect to avoid resubmission
            wp_redirect(admin_url('admin.php?page=swipecommerce-categories&deleted=1'));
            exit;
        } else {
            add_settings_error('swipecommerce_messages', 'delete_error', __('Failed to delete category.', 'swipecommerce-pro'), 'error');
        }
    }

    /**
     * Display admin notices.
     *
     * @since    1.0.7
     */
    public function display_admin_notices() {
        // Handle URL parameters for success messages
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Category saved successfully!', 'swipecommerce-pro') . '</p></div>';
        }
        
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Category deleted successfully!', 'swipecommerce-pro') . '</p></div>';
        }
    }

    /**
     * Get shortcode usage examples for admin display.
     *
     * @since    1.0.7
     * @return   array    Array of shortcode examples.
     */
    public function get_shortcode_examples() {
        return array(
            'basic' => array(
                'title' => __('Basic Usage:', 'swipecommerce-pro'),
                'code' => '[swipecommerce_slider]',
                'description' => __('Displays all custom categories with default settings.', 'swipecommerce-pro')
            ),
            'with_params' => array(
                'title' => __('With Parameters:', 'swipecommerce-pro'),
                'examples' => array(
                    '[swipecommerce_slider limit="12" type="featured" show_filters="true"]',
                    '[swipecommerce_slider category="supplements" limit="8"]',
                    '[swipecommerce_slider type="sale" limit="10"]'
                ),
                'description' => __('Various parameter combinations.', 'swipecommerce-pro')
            ),
            'custom_categories' => array(
                'title' => __('Custom Categories:', 'swipecommerce-pro'),
                'examples' => array(
                    '[swipecommerce_slider]' => __('Shows all custom categories', 'swipecommerce-pro'),
                    '[swipecommerce_slider custom_category="bestsellers"]' => __('Shows single custom category', 'swipecommerce-pro'),
                    '[swipecommerce_slider show_custom_categories="false" type="recent"]' => __('Disable custom categories, show regular products', 'swipecommerce-pro')
                )
            ),
            'parameters' => array(
                'title' => __('Available Parameters:', 'swipecommerce-pro'),
                'params' => array(
                    'limit' => sprintf(__('Number of products (default: %d)', 'swipecommerce-pro'), $this->settings->get_setting('default_limit', 12)),
                    'type' => __('Product type: "recent", "featured", or "sale" (default: recent)', 'swipecommerce-pro'),
                    'category' => __('WooCommerce product category slug', 'swipecommerce-pro'),
                    'custom_category' => __('Custom category ID (e.g., "bestsellers", "staff-picks")', 'swipecommerce-pro'),
                    'show_custom_categories' => __('Show all custom categories (true/false, default: true)', 'swipecommerce-pro'),
                    'show_filters' => sprintf(__('Show category filters (true/false, default: %s)', 'swipecommerce-pro'), $this->settings->get_setting('enable_filters', 1) ? 'true' : 'false'),
                    'title' => __('Custom section title', 'swipecommerce-pro'),
                    'description' => __('Custom section description', 'swipecommerce-pro')
                )
            )
        );
    }

    /**
     * Get plugin status information for admin display.
     *
     * @since    1.0.7
     * @return   array    Array of status information.
     */
    public function get_plugin_status() {
        return array(
            'version' => SWIPECOMMERCE_VERSION,
            'woocommerce_active' => class_exists('WooCommerce'),
            'woocommerce_version' => class_exists('WooCommerce') ? WC()->version : null,
            'ajax_enabled' => $this->settings->get_setting('enable_cart_ajax', 1),
            'filters_enabled' => $this->settings->get_setting('enable_filters', 1),
            'categories_count' => count($this->categories->get_custom_categories()),
            'visible_categories_count' => count($this->categories->get_custom_categories(true))
        );
    }

    /**
     * Add contextual help to admin pages.
     *
     * @since    1.0.7
     * @param    string    $contextual_help    Existing help content.
     * @param    string    $screen_id          Current screen ID.
     * @param    WP_Screen $screen             Current screen object.
     * @return   string                        Modified help content.
     */
    public function add_contextual_help($contextual_help, $screen_id, $screen) {
        if (strpos($screen_id, 'swipecommerce') !== false) {
            $screen->add_help_tab(array(
                'id' => 'swipecommerce_overview',
                'title' => __('Overview', 'swipecommerce-pro'),
                'content' => '<p>' . __('SwipeCommerce Pro transforms your WooCommerce product display with Netflix-style horizontal sliders.', 'swipecommerce-pro') . '</p>'
            ));

            $screen->add_help_tab(array(
                'id' => 'swipecommerce_shortcodes',
                'title' => __('Shortcodes', 'swipecommerce-pro'),
                'content' => '<p>' . __('Use [swipecommerce_slider] to display product sliders anywhere on your site.', 'swipecommerce-pro') . '</p>'
            ));

            $screen->set_help_sidebar(
                '<p><strong>' . __('For more information:', 'swipecommerce-pro') . '</strong></p>' .
                '<p><a href="#" target="_blank">' . __('Plugin Documentation', 'swipecommerce-pro') . '</a></p>' .
                '<p><a href="#" target="_blank">' . __('Support Forum', 'swipecommerce-pro') . '</a></p>'
            );
        }

        return $contextual_help;
    }
}