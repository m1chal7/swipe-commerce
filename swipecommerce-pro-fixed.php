<?php
/**
 * Plugin Name: SwipeCommerce Pro - Horizontal Product Showcase (Safe Version)
 * Plugin URI: https://swipecommerce.com
 * Description: Premium WooCommerce plugin that transforms product browsing with Netflix-style horizontal sliders
 * Version: 1.0.1
 * Author: SwipeCommerce Team
 * Text Domain: swipecommerce-pro
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
if (!defined('SWIPECOMMERCE_VERSION')) {
    define('SWIPECOMMERCE_VERSION', '1.0.0');
}
if (!defined('SWIPECOMMERCE_PLUGIN_URL')) {
    define('SWIPECOMMERCE_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('SWIPECOMMERCE_PLUGIN_PATH')) {
    define('SWIPECOMMERCE_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

class SwipeCommercePro_Safe {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_notices', array($this, 'check_requirements'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function check_requirements() {
        if (!class_exists('WooCommerce')) {
            $class = 'notice notice-error';
            $message = __('SwipeCommerce Pro requires WooCommerce to be installed and activated.', 'swipecommerce-pro');
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            return false;
        }
        return true;
    }

    public function init() {
        if (!$this->check_requirements()) {
            return;
        }

        // Load text domain
        load_plugin_textdomain('swipecommerce-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Initialize components safely
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('swipecommerce_slider', array($this, 'render_shortcode'));
        
        // Only load if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->init_woocommerce_features();
        }
    }

    public function activate() {
        // Safe activation - only create options, no complex DB operations
        if (!get_option('swipecommerce_version')) {
            update_option('swipecommerce_version', SWIPECOMMERCE_VERSION);
            update_option('swipecommerce_installed', current_time('mysql'));
        }
        flush_rewrite_rules();
    }

    public function enqueue_assets() {
        // Only enqueue if shortcode is present
        if ($this->has_shortcode_in_content()) {
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

            wp_localize_script('swipecommerce-public', 'swipecommerce_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('swipecommerce_nonce'),
                'loading_text' => __('Loading...', 'swipecommerce-pro'),
            ));
        }
    }

    public function render_shortcode($atts) {
        // Safe shortcode rendering with fallbacks
        if (!class_exists('WooCommerce')) {
            return '<div class="swipecommerce-error">' . __('WooCommerce is required.', 'swipecommerce-pro') . '</div>';
        }

        $atts = shortcode_atts(array(
            'limit' => 12,
            'columns' => 4,
            'category' => '',
            'type' => 'recent',
            'show_filters' => true,
        ), $atts, 'swipecommerce_slider');

        try {
            return $this->get_products_html($atts);
        } catch (Exception $e) {
            error_log('SwipeCommerce Error: ' . $e->getMessage());
            return '<div class="swipecommerce-error">' . __('Error loading products.', 'swipecommerce-pro') . '</div>';
        }
    }

    private function get_products_html($atts) {
        $products = $this->get_products_safe($atts);
        
        if (empty($products)) {
            return '<div class="swipecommerce-no-products">' . __('No products found.', 'swipecommerce-pro') . '</div>';
        }

        ob_start();
        ?>
        <div class="swipecommerce-slider-wrapper">
            <div class="swipecommerce-slider-container">
                <div class="swipecommerce-slider-track">
                    <?php foreach ($products as $product): ?>
                        <div class="swipecommerce-product-card" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                            <div class="swipecommerce-product-image">
                                <?php echo $product->get_image('medium'); ?>
                            </div>
                            <div class="swipecommerce-product-info">
                                <h3 class="swipecommerce-product-name">
                                    <a href="<?php echo esc_url($product->get_permalink()); ?>">
                                        <?php echo esc_html($product->get_name()); ?>
                                    </a>
                                </h3>
                                <div class="swipecommerce-product-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                                <div class="swipecommerce-product-actions">
                                    <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                       class="swipecommerce-add-to-cart button"
                                       data-product_id="<?php echo esc_attr($product->get_id()); ?>">
                                        <?php echo esc_html($product->add_to_cart_text()); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_products_safe($atts) {
        // Safe product retrieval with error handling
        try {
            $args = array(
                'status' => 'publish',
                'limit' => intval($atts['limit']),
                'orderby' => 'date',
                'order' => 'DESC',
            );

            if (!empty($atts['category'])) {
                $args['category'] = array(sanitize_text_field($atts['category']));
            }

            switch ($atts['type']) {
                case 'featured':
                    $args['featured'] = true;
                    break;
                case 'sale':
                    $args['include'] = wc_get_product_ids_on_sale();
                    break;
                default:
                    // Recent products (default)
                    break;
            }

            return wc_get_products($args);

        } catch (Exception $e) {
            error_log('SwipeCommerce get_products error: ' . $e->getMessage());
            return array();
        }
    }

    private function init_woocommerce_features() {
        // Safe WooCommerce integration
        add_action('wp_ajax_swipecommerce_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_swipecommerce_add_to_cart', array($this, 'ajax_add_to_cart'));
    }

    public function ajax_add_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'swipecommerce_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity'] ?? 1);

        $result = WC()->cart->add_to_cart($product_id, $quantity);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Product added to cart', 'swipecommerce-pro'),
                'cart_count' => WC()->cart->get_cart_contents_count()
            ));
        } else {
            wp_send_json_error(__('Failed to add product to cart', 'swipecommerce-pro'));
        }
    }

    private function has_shortcode_in_content() {
        global $post;
        return $post && has_shortcode($post->post_content, 'swipecommerce_slider');
    }
}

// Initialize the safe version
SwipeCommercePro_Safe::instance();