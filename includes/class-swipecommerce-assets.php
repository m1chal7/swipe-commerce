<?php
/**
 * The assets management class.
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
 * The assets management class.
 *
 * Handles CSS and JavaScript loading for both frontend and admin.
 */
class SwipeCommerce_Assets {

    /**
     * Initialize the class.
     *
     * @since    1.0.7
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize asset hooks.
     *
     * @since    1.0.7
     */
    private function init() {
        // Frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueue frontend assets.
     *
     * @since    1.0.7
     */
    public function enqueue_frontend_assets() {
        // Only enqueue if shortcode is present or always load (depending on settings)
        if ($this->should_load_frontend_assets()) {
            wp_enqueue_style(
                'swipecommerce-public',
                SWIPECOMMERCE_PLUGIN_URL . 'public/assets/css/swipecommerce-minimal.css',
                array(),
                SWIPECOMMERCE_VERSION
            );

            wp_enqueue_script(
                'swipecommerce-public',
                SWIPECOMMERCE_PLUGIN_URL . 'public/assets/js/swipecommerce-minimal.js',
                array('jquery'),
                SWIPECOMMERCE_VERSION,
                true
            );

            // Localize script with AJAX data
            wp_localize_script('swipecommerce-public', 'swipecommerce_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('swipecommerce_nonce'),
                'loading_text' => __('Loading...', 'swipecommerce-pro'),
                'cart_text' => __('Added to cart!', 'swipecommerce-pro'),
                'error_text' => __('Error occurred. Please try again.', 'swipecommerce-pro'),
            ));
        }
    }

    /**
     * Enqueue admin assets.
     *
     * @since    1.0.7
     * @param    string    $hook    The current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on SwipeCommerce admin pages
        if (!$this->is_swipecommerce_admin_page($hook)) {
            return;
        }

        // Common admin styles
        wp_enqueue_style(
            'swipecommerce-admin',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/css/swipecommerce-admin.css',
            array(),
            SWIPECOMMERCE_VERSION
        );

        // Common admin scripts
        wp_enqueue_script(
            'swipecommerce-admin',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/js/swipecommerce-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            SWIPECOMMERCE_VERSION,
            true
        );

        // Localize admin script
        wp_localize_script('swipecommerce-admin', 'swipecommerce_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swipecommerce_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this category?', 'swipecommerce-pro'),
                'confirm_bulk_delete' => __('Are you sure you want to delete the selected categories?', 'swipecommerce-pro'),
                'saving' => __('Saving...', 'swipecommerce-pro'),
                'saved' => __('Saved!', 'swipecommerce-pro'),
                'error' => __('Error occurred. Please try again.', 'swipecommerce-pro'),
                'loading' => __('Loading...', 'swipecommerce-pro'),
                'no_selection' => __('Please select an action and categories', 'swipecommerce-pro'),
                'search_failed' => __('Search failed. Please try again.', 'swipecommerce-pro'),
                'no_products' => __('No products found', 'swipecommerce-pro'),
                'search_placeholder' => __('Search products by name, SKU...', 'swipecommerce-pro'),
            )
        ));

        // Category-specific assets
        if ($this->is_categories_page($hook)) {
            $this->enqueue_categories_assets();
        }
    }

    /**
     * Enqueue category management specific assets.
     *
     * @since    1.0.7
     */
    private function enqueue_categories_assets() {
        // jQuery UI for drag & drop
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Color picker if needed
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Category management specific script
        wp_enqueue_script(
            'swipecommerce-categories',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/js/swipecommerce-categories.js',
            array('jquery', 'jquery-ui-sortable', 'swipecommerce-admin'),
            SWIPECOMMERCE_VERSION,
            true
        );
    }

    /**
     * Check if frontend assets should be loaded.
     *
     * @since    1.0.7
     * @return   bool    True if assets should be loaded.
     */
    private function should_load_frontend_assets() {
        global $post;

        // Always load if option is set
        if (get_option('swipecommerce_always_load_assets', false)) {
            return true;
        }

        // Load if shortcode is present in current post
        if ($post && has_shortcode($post->post_content, 'swipecommerce_slider')) {
            return true;
        }

        // Load on specific pages where shortcode might be dynamically added
        if (is_front_page() || is_shop() || is_product_category()) {
            return true;
        }

        // Check if any widgets use the shortcode
        if ($this->has_shortcode_in_widgets()) {
            return true;
        }

        return false;
    }

    /**
     * Check if shortcode exists in any active widgets.
     *
     * @since    1.0.7
     * @return   bool    True if shortcode found in widgets.
     */
    private function has_shortcode_in_widgets() {
        $sidebars_widgets = wp_get_sidebars_widgets();
        
        if (empty($sidebars_widgets)) {
            return false;
        }

        foreach ($sidebars_widgets as $sidebar_id => $widget_ids) {
            if (is_array($widget_ids)) {
                foreach ($widget_ids as $widget_id) {
                    // Check text widgets
                    if (strpos($widget_id, 'text-') === 0) {
                        $text_widgets = get_option('widget_text', array());
                        foreach ($text_widgets as $widget) {
                            if (isset($widget['text']) && has_shortcode($widget['text'], 'swipecommerce_slider')) {
                                return true;
                            }
                        }
                    }
                    
                    // Check custom HTML widgets
                    if (strpos($widget_id, 'custom_html-') === 0) {
                        $html_widgets = get_option('widget_custom_html', array());
                        foreach ($html_widgets as $widget) {
                            if (isset($widget['content']) && has_shortcode($widget['content'], 'swipecommerce_slider')) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if current admin page is a SwipeCommerce page.
     *
     * @since    1.0.7
     * @param    string    $hook    The current admin page hook.
     * @return   bool               True if SwipeCommerce admin page.
     */
    private function is_swipecommerce_admin_page($hook) {
        // List of our admin page hooks
        $swipecommerce_pages = array(
            'toplevel_page_swipecommerce-pro',
            'swipecommerce-pro_page_swipecommerce-categories',
            'admin_page_swipecommerce-settings', // if we add a separate settings page
        );

        return in_array($hook, $swipecommerce_pages);
    }

    /**
     * Check if current admin page is the categories management page.
     *
     * @since    1.0.7
     * @param    string    $hook    The current admin page hook.
     * @return   bool               True if categories page.
     */
    private function is_categories_page($hook) {
        return $hook === 'swipecommerce-pro_page_swipecommerce-categories';
    }

    /**
     * Enqueue assets for a specific context.
     *
     * @since    1.0.7
     * @param    string    $context    The context (frontend, admin, editor, etc.).
     */
    public function enqueue_context_assets($context) {
        switch ($context) {
            case 'frontend':
                $this->enqueue_frontend_assets();
                break;
                
            case 'editor':
                $this->enqueue_editor_assets();
                break;
                
            case 'customizer':
                $this->enqueue_customizer_assets();
                break;
        }
    }

    /**
     * Enqueue assets for block editor.
     *
     * @since    1.0.7
     */
    private function enqueue_editor_assets() {
        // Block editor specific styles
        wp_enqueue_style(
            'swipecommerce-editor',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/css/swipecommerce-editor.css',
            array(),
            SWIPECOMMERCE_VERSION
        );
    }

    /**
     * Enqueue assets for customizer.
     *
     * @since    1.0.7
     */
    private function enqueue_customizer_assets() {
        // Customizer specific styles
        wp_enqueue_style(
            'swipecommerce-customizer',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/css/swipecommerce-customizer.css',
            array(),
            SWIPECOMMERCE_VERSION
        );
    }

    /**
     * Register all plugin assets (for conditional loading).
     *
     * @since    1.0.7
     */
    public function register_assets() {
        // Register frontend assets
        wp_register_style(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . 'public/assets/css/swipecommerce-minimal.css',
            array(),
            SWIPECOMMERCE_VERSION
        );

        wp_register_script(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . 'public/assets/js/swipecommerce-minimal.js',
            array('jquery'),
            SWIPECOMMERCE_VERSION,
            true
        );

        // Register admin assets
        wp_register_style(
            'swipecommerce-admin',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/css/swipecommerce-admin.css',
            array(),
            SWIPECOMMERCE_VERSION
        );

        wp_register_script(
            'swipecommerce-admin',
            SWIPECOMMERCE_PLUGIN_URL . 'admin/js/swipecommerce-admin.js',
            array('jquery'),
            SWIPECOMMERCE_VERSION,
            true
        );
    }

    /**
     * Get inline styles for dynamic theming.
     *
     * @since    1.0.7
     * @return   string    CSS styles.
     */
    public function get_inline_styles() {
        $styles = '';
        
        // Get theme colors from options
        $primary_color = get_option('swipecommerce_primary_color', '#2271b1');
        $secondary_color = get_option('swipecommerce_secondary_color', '#50575e');
        
        if ($primary_color !== '#2271b1' || $secondary_color !== '#50575e') {
            $styles .= "
                .swipecommerce-slider-wrapper {
                    --swipecommerce-primary: {$primary_color};
                    --swipecommerce-secondary: {$secondary_color};
                }
            ";
        }
        
        return $styles;
    }
}