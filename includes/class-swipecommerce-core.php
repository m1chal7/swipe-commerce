<?php
/**
 * The core plugin class that orchestrates all plugin functionality.
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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class SwipeCommerce_Core {

    /**
     * The single instance of the class.
     *
     * @since    1.0.7
     * @var      SwipeCommerce_Core
     */
    protected static $_instance = null;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.7
     * @var      array
     */
    protected $components = array();

    /**
     * Main SwipeCommerce_Core Instance.
     *
     * Ensures only one instance of SwipeCommerce_Core is loaded or can be loaded.
     *
     * @since  1.0.7
     * @static
     * @return SwipeCommerce_Core - Main instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * SwipeCommerce_Core Constructor.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin.
     *
     * @since    1.0.7
     */
    public function init() {
        // Initialize components
        $this->init_components();
        
        // Only load WooCommerce features if active
        if (class_exists('WooCommerce')) {
            $this->init_woocommerce_features();
        }
    }


    /**
     * Initialize all plugin components.
     *
     * @since    1.0.7
     */
    private function init_components() {
        
        // Initialize categories management (needed by all components)
        $this->components['categories'] = new SwipeCommerce_Categories();
        
        // Initialize frontend
        $this->components['frontend'] = new SwipeCommerce_Frontend($this->components['categories']);
        
        // Initialize assets management
        $this->components['assets'] = new SwipeCommerce_Assets();
        
        // Initialize AJAX handlers
        $this->components['ajax'] = new SwipeCommerce_Ajax($this->components['categories']);
        
        // Initialize admin components if needed
        if (is_admin()) {
            $this->components['settings'] = new SwipeCommerce_Settings();
            $this->components['admin'] = new SwipeCommerce_Admin($this->components['categories'], $this->components['settings']);
        }
    }

    /**
     * Initialize WooCommerce specific features.
     *
     * @since    1.0.7
     */
    private function init_woocommerce_features() {
        // WooCommerce integration is handled within individual components
        // This method can be used for any global WooCommerce setup if needed
    }

    /**
     * Get a component instance.
     *
     * @since    1.0.7
     * @param    string    $component_name    The component name to retrieve
     * @return   mixed     The component instance or null if not found
     */
    public function get_component($component_name) {
        return isset($this->components[$component_name]) ? $this->components[$component_name] : null;
    }

    /**
     * Prevent cloning of the instance.
     *
     * @since    1.0.7
     */
    public function __clone() {
        _doing_it_wrong(__FUNCTION__, __('Cloning is forbidden.', 'swipecommerce-pro'), '1.0.7');
    }

    /**
     * Prevent unserializing of the instance.
     *
     * @since    1.0.7
     */
    public function __wakeup() {
        _doing_it_wrong(__FUNCTION__, __('Unserializing instances of this class is forbidden.', 'swipecommerce-pro'), '1.0.7');
    }
}