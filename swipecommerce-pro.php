<?php
/**
 * Plugin Name: SwipeCommerce - Horizontal Product Showcase
 * Plugin URI: https://swipecommerce.com
 * Description: Premium WooCommerce plugin that transforms product browsing with Netflix-style horizontal sliders
 * Version: 1.1.8
 * Author: Michał Urbaniak
 * Text Domain: swipecommerce-pro
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('SWIPECOMMERCE_VERSION')) {
    define('SWIPECOMMERCE_VERSION', '1.1.8');
}
if (!defined('SWIPECOMMERCE_PLUGIN_FILE')) {
    define('SWIPECOMMERCE_PLUGIN_FILE', __FILE__);
}
if (!defined('SWIPECOMMERCE_PLUGIN_URL')) {
    define('SWIPECOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('SWIPECOMMERCE_PLUGIN_PATH')) {
    define('SWIPECOMMERCE_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/**
 * Main plugin bootstrap class
 * 
 * This class handles plugin initialization and loads the core components.
 * The actual functionality is distributed across multiple classes in the includes/ directory.
 */
class SwipeCommerce_Bootstrap {
    
    /**
     * Singleton instance
     */
    protected static $_instance = null;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_notices', array($this, 'check_requirements'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check requirements first
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('swipecommerce-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Load core class
        $this->load_dependencies();
        
        // Initialize core
        SwipeCommerce_Core::instance();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-core.php';
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-frontend.php';
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-categories.php';
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-ajax.php';
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-assets.php';
        require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-settings.php';
        
        // Admin classes (only in admin)
        if (is_admin()) {
            require_once SWIPECOMMERCE_PLUGIN_PATH . 'includes/class-swipecommerce-admin.php';
        }
    }
    
    /**
     * Check plugin requirements
     */
    public function check_requirements() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>' . __('SwipeCommerce Pro', 'swipecommerce-pro') . ':</strong> ';
                echo __('WooCommerce is required for this plugin to work properly. Please install and activate WooCommerce.', 'swipecommerce-pro');
                echo '</p></div>';
            });
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>' . __('SwipeCommerce Pro', 'swipecommerce-pro') . ':</strong> ';
                echo sprintf(__('PHP version 7.4 or higher is required. You are running version %s.', 'swipecommerce-pro'), PHP_VERSION);
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set version option
        if (!get_option('swipecommerce_version')) {
            update_option('swipecommerce_version', SWIPECOMMERCE_VERSION);
            update_option('swipecommerce_installed', current_time('mysql'));
        }
        
        // Initialize default custom categories if they don't exist
        if (!get_option('swipecommerce_custom_categories')) {
            $this->init_default_categories();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    /**
     * Initialize default custom categories
     */
    private function init_default_categories() {
        $default_categories = array(
            array(
                'id' => 'bestsellers',
                'name' => __('Best Sellers', 'swipecommerce-pro'),
                'description' => __('Our most popular products', 'swipecommerce-pro'),
                'color_scheme' => 'gradient-pink',
                'icon' => '🏆',
                'order' => 1,
                'products' => array(),
                'visibility' => true
            ),
            array(
                'id' => 'staff-picks',
                'name' => __('Staff Picks', 'swipecommerce-pro'),
                'description' => __('Hand-picked by our team', 'swipecommerce-pro'),
                'color_scheme' => 'gradient-blue',
                'icon' => '⭐',
                'order' => 2,
                'products' => array(),
                'visibility' => true
            ),
            array(
                'id' => 'new-arrivals',
                'name' => __('New Arrivals', 'swipecommerce-pro'),
                'description' => __('Fresh products just added', 'swipecommerce-pro'),
                'color_scheme' => 'gradient-green',
                'icon' => '🌱',
                'order' => 3,
                'products' => array(),
                'visibility' => true
            )
        );

        update_option('swipecommerce_custom_categories', $default_categories);
    }
}

/**
 * Initialize the plugin
 */
function swipecommerce_pro_init() {
    return SwipeCommerce_Bootstrap::instance();
}

// Start the plugin
swipecommerce_pro_init();