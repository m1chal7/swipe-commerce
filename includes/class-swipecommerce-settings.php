<?php
/**
 * The settings management class.
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
 * The settings management class.
 *
 * Handles plugin settings registration and management.
 */
class SwipeCommerce_Settings {

    /**
     * Initialize the class.
     *
     * @since    1.0.7
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize settings hooks.
     *
     * @since    1.0.7
     */
    private function init() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register all plugin settings.
     *
     * @since    1.0.7
     */
    public function register_settings() {
        // Register setting groups
        register_setting('swipecommerce_settings', 'swipecommerce_default_limit', array(
            'type' => 'integer',
            'default' => 12,
            'sanitize_callback' => array($this, 'sanitize_positive_integer'),
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_default_columns', array(
            'type' => 'integer',
            'default' => 4,
            'sanitize_callback' => array($this, 'sanitize_columns'),
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_enable_filters', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_enable_cart_ajax', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_always_load_assets', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_primary_color', array(
            'type' => 'string',
            'default' => '#2271b1',
            'sanitize_callback' => 'sanitize_hex_color',
        ));

        register_setting('swipecommerce_settings', 'swipecommerce_secondary_color', array(
            'type' => 'string',
            'default' => '#50575e',
            'sanitize_callback' => 'sanitize_hex_color',
        ));

        // Add settings sections
        $this->add_settings_sections();
    }

    /**
     * Add settings sections and fields.
     *
     * @since    1.0.7
     */
    private function add_settings_sections() {
        // General Settings Section
        add_settings_section(
            'swipecommerce_general',
            __('General Settings', 'swipecommerce-pro'),
            array($this, 'general_section_callback'),
            'swipecommerce_settings'
        );

        add_settings_field(
            'swipecommerce_default_limit',
            __('Default Product Limit', 'swipecommerce-pro'),
            array($this, 'number_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array(
                'field' => 'swipecommerce_default_limit',
                'default' => 12,
                'min' => 1,
                'max' => 50,
                'description' => __('Default number of products to display in sliders.', 'swipecommerce-pro')
            )
        );

        add_settings_field(
            'swipecommerce_default_columns',
            __('Default Columns', 'swipecommerce-pro'),
            array($this, 'number_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array(
                'field' => 'swipecommerce_default_columns',
                'default' => 4,
                'min' => 2,
                'max' => 8,
                'description' => __('Default number of columns for product display.', 'swipecommerce-pro')
            )
        );

        add_settings_field(
            'swipecommerce_enable_filters',
            __('Enable Category Filters', 'swipecommerce-pro'),
            array($this, 'checkbox_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array(
                'field' => 'swipecommerce_enable_filters',
                'default' => 1,
                'description' => __('Show category filter buttons in sliders.', 'swipecommerce-pro')
            )
        );

        add_settings_field(
            'swipecommerce_enable_cart_ajax',
            __('Enable AJAX Add to Cart', 'swipecommerce-pro'),
            array($this, 'checkbox_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array(
                'field' => 'swipecommerce_enable_cart_ajax',
                'default' => 1,
                'description' => __('Allow adding products to cart without page reload.', 'swipecommerce-pro')
            )
        );

        // Performance Settings Section
        add_settings_section(
            'swipecommerce_performance',
            __('Performance Settings', 'swipecommerce-pro'),
            array($this, 'performance_section_callback'),
            'swipecommerce_settings'
        );

        add_settings_field(
            'swipecommerce_always_load_assets',
            __('Always Load Assets', 'swipecommerce-pro'),
            array($this, 'checkbox_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_performance',
            array(
                'field' => 'swipecommerce_always_load_assets',
                'default' => 0,
                'description' => __('Load CSS/JS on all pages (recommended only if shortcodes are used dynamically).', 'swipecommerce-pro')
            )
        );

        // Styling Settings Section
        add_settings_section(
            'swipecommerce_styling',
            __('Styling Settings', 'swipecommerce-pro'),
            array($this, 'styling_section_callback'),
            'swipecommerce_settings'
        );

        add_settings_field(
            'swipecommerce_primary_color',
            __('Primary Color', 'swipecommerce-pro'),
            array($this, 'color_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_styling',
            array(
                'field' => 'swipecommerce_primary_color',
                'default' => '#2271b1',
                'description' => __('Main color for buttons and accents.', 'swipecommerce-pro')
            )
        );

        add_settings_field(
            'swipecommerce_secondary_color',
            __('Secondary Color', 'swipecommerce-pro'),
            array($this, 'color_field_callback'),
            'swipecommerce_settings',
            'swipecommerce_styling',
            array(
                'field' => 'swipecommerce_secondary_color',
                'default' => '#50575e',
                'description' => __('Secondary color for text and borders.', 'swipecommerce-pro')
            )
        );
    }

    /**
     * General section callback.
     *
     * @since    1.0.7
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure the basic settings for SwipeCommerce Pro.', 'swipecommerce-pro') . '</p>';
    }

    /**
     * Performance section callback.
     *
     * @since    1.0.7
     */
    public function performance_section_callback() {
        echo '<p>' . __('Optimize performance and loading behavior.', 'swipecommerce-pro') . '</p>';
    }

    /**
     * Styling section callback.
     *
     * @since    1.0.7
     */
    public function styling_section_callback() {
        echo '<p>' . __('Customize the visual appearance of your sliders.', 'swipecommerce-pro') . '</p>';
    }

    /**
     * Number field callback.
     *
     * @since    1.0.7
     * @param    array    $args    Field arguments.
     */
    public function number_field_callback($args) {
        $value = get_option($args['field'], $args['default']);
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        echo '<input type="number" id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '" value="' . esc_attr($value) . '"';
        if ($min !== '') echo ' min="' . esc_attr($min) . '"';
        if ($max !== '') echo ' max="' . esc_attr($max) . '"';
        echo ' class="small-text" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Checkbox field callback.
     *
     * @since    1.0.7
     * @param    array    $args    Field arguments.
     */
    public function checkbox_field_callback($args) {
        $value = get_option($args['field'], $args['default']);
        
        echo '<label for="' . esc_attr($args['field']) . '">';
        echo '<input type="checkbox" id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '" value="1"' . checked(1, $value, false) . ' />';
        echo ' ' . __('Enable this feature', 'swipecommerce-pro');
        echo '</label>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Color field callback.
     *
     * @since    1.0.7
     * @param    array    $args    Field arguments.
     */
    public function color_field_callback($args) {
        $value = get_option($args['field'], $args['default']);
        
        echo '<input type="text" id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '" value="' . esc_attr($value) . '" class="swipecommerce-color-picker" data-default-color="' . esc_attr($args['default']) . '" />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Select field callback.
     *
     * @since    1.0.7
     * @param    array    $args    Field arguments.
     */
    public function select_field_callback($args) {
        $value = get_option($args['field'], $args['default']);
        
        echo '<select id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '">';
        foreach ($args['options'] as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }

    /**
     * Sanitize positive integer values.
     *
     * @since    1.0.7
     * @param    mixed    $value    The value to sanitize.
     * @return   int               Sanitized integer value.
     */
    public function sanitize_positive_integer($value) {
        $value = intval($value);
        return $value > 0 ? $value : 1;
    }

    /**
     * Sanitize columns value.
     *
     * @since    1.0.7
     * @param    mixed    $value    The value to sanitize.
     * @return   int               Sanitized columns value.
     */
    public function sanitize_columns($value) {
        $value = intval($value);
        if ($value < 2) return 2;
        if ($value > 8) return 8;
        return $value;
    }

    /**
     * Sanitize checkbox values.
     *
     * @since    1.0.7
     * @param    mixed    $value    The value to sanitize.
     * @return   bool              Sanitized boolean value.
     */
    public function sanitize_checkbox($value) {
        return !empty($value) ? 1 : 0;
    }

    /**
     * Get all plugin settings with defaults.
     *
     * @since    1.0.7
     * @return   array    Array of settings.
     */
    public function get_all_settings() {
        return array(
            'default_limit' => get_option('swipecommerce_default_limit', 12),
            'default_columns' => get_option('swipecommerce_default_columns', 4),
            'enable_filters' => get_option('swipecommerce_enable_filters', 1),
            'enable_cart_ajax' => get_option('swipecommerce_enable_cart_ajax', 1),
            'always_load_assets' => get_option('swipecommerce_always_load_assets', 0),
            'primary_color' => get_option('swipecommerce_primary_color', '#2271b1'),
            'secondary_color' => get_option('swipecommerce_secondary_color', '#50575e'),
        );
    }

    /**
     * Get a single setting value.
     *
     * @since    1.0.7
     * @param    string    $setting_name    The setting name.
     * @param    mixed     $default         Default value if setting doesn't exist.
     * @return   mixed                      The setting value.
     */
    public function get_setting($setting_name, $default = null) {
        $option_name = 'swipecommerce_' . $setting_name;
        return get_option($option_name, $default);
    }

    /**
     * Update a single setting value.
     *
     * @since    1.0.7
     * @param    string    $setting_name    The setting name.
     * @param    mixed     $value           The value to save.
     * @return   bool                       True on success, false on failure.
     */
    public function update_setting($setting_name, $value) {
        $option_name = 'swipecommerce_' . $setting_name;
        return update_option($option_name, $value);
    }

    /**
     * Reset all settings to defaults.
     *
     * @since    1.0.7
     * @return   bool    True on success, false on failure.
     */
    public function reset_settings() {
        $defaults = array(
            'swipecommerce_default_limit' => 12,
            'swipecommerce_default_columns' => 4,
            'swipecommerce_enable_filters' => 1,
            'swipecommerce_enable_cart_ajax' => 1,
            'swipecommerce_always_load_assets' => 0,
            'swipecommerce_primary_color' => '#2271b1',
            'swipecommerce_secondary_color' => '#50575e',
        );

        $success = true;
        foreach ($defaults as $option_name => $default_value) {
            if (!update_option($option_name, $default_value)) {
                $success = false;
            }
        }

        return $success;
    }
}