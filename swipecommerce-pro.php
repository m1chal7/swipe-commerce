<?php
/**
 * Plugin Name: SwipeCommerce Pro - Horizontal Product Showcase
 * Plugin URI: https://swipecommerce.com
 * Description: Premium WooCommerce plugin that transforms product browsing with Netflix-style horizontal sliders
 * Version: 1.0.2
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
    define('SWIPECOMMERCE_VERSION', '1.0.2');
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
        
        // Admin interface - wait for WooCommerce to be ready
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'), 20);
            add_action('admin_init', array($this, 'init_admin_settings'));
            
            // AJAX handlers for category management
            add_action('wp_ajax_swipecommerce_save_category', array($this, 'ajax_save_category'));
            add_action('wp_ajax_swipecommerce_delete_category', array($this, 'ajax_delete_category'));
            add_action('wp_ajax_swipecommerce_search_products', array($this, 'ajax_search_products'));
        }
        
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
            
            // Initialize default custom categories
            $this->init_default_categories();
        }
        flush_rewrite_rules();
    }

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
            'limit' => get_option('swipecommerce_default_limit', 12),
            'columns' => get_option('swipecommerce_default_columns', 4),
            'category' => '', // WooCommerce category
            'custom_category' => '', // Our custom category
            'type' => 'recent',
            'show_filters' => get_option('swipecommerce_enable_filters', 1),
            'show_custom_categories' => true, // Show custom category navigation
            'title' => '',
            'description' => '',
        ), $atts, 'swipecommerce_slider');

        try {
            return $this->get_products_html($atts);
        } catch (Exception $e) {
            error_log('SwipeCommerce Error: ' . $e->getMessage());
            return '<div class="swipecommerce-error">' . __('Error loading products.', 'swipecommerce-pro') . '</div>';
        }
    }

    private function get_products_html($atts) {
        // Handle custom categories
        if (!empty($atts['custom_category'])) {
            return $this->get_custom_category_html($atts);
        }
        
        // Handle multiple custom categories or mixed display
        if ($atts['show_custom_categories']) {
            return $this->get_multi_category_html($atts);
        }
        
        // Original single category display
        $products = $this->get_products_safe($atts);
        
        if (empty($products)) {
            return '<div class="swipecommerce-no-products">' . __('No products found.', 'swipecommerce-pro') . '</div>';
        }

        $slider_id = 'swipecommerce-slider-' . uniqid();
        
        ob_start();
        ?>
        <div class="swipecommerce-slider-wrapper" data-slider-id="<?php echo esc_attr($slider_id); ?>">
            
            <!-- Category Navigator -->
            <?php if ($atts['show_filters']): ?>
            <div class="swipecommerce-category-navigator">
                <div class="swipecommerce-nav-pills">
                    <div class="swipecommerce-nav-pill active" data-category="all">
                        <?php esc_html_e('All Products', 'swipecommerce-pro'); ?>
                        <span class="swipecommerce-count"><?php echo count($products); ?></span>
                    </div>
                </div>
                
                <div class="swipecommerce-quick-filters">
                    <button class="swipecommerce-filter-btn" data-filter="sale"><?php esc_html_e('On Sale', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="new"><?php esc_html_e('New', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="featured"><?php esc_html_e('Featured', 'swipecommerce-pro'); ?></button>
                </div>
                
                <div class="swipecommerce-scroll-progress">
                    <div class="swipecommerce-scroll-progress-bar"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="swipecommerce-slider-header">
                <h2><?php echo esc_html($atts['title'] ?? __('Featured Products', 'swipecommerce-pro')); ?></h2>
                <p><?php echo esc_html($atts['description'] ?? __('High-quality products for your health and wellness', 'swipecommerce-pro')); ?></p>
            </div>
            
            <!-- Slider Container -->
            <div class="swipecommerce-slider-container">
                <button class="swipecommerce-nav-button swipecommerce-prev" 
                        id="<?php echo esc_attr($slider_id); ?>-prev" 
                        aria-label="<?php esc_attr_e('Previous', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                
                <button class="swipecommerce-nav-button swipecommerce-next" 
                        id="<?php echo esc_attr($slider_id); ?>-next" 
                        aria-label="<?php esc_attr_e('Next', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
                
                <div class="swipecommerce-slider-track" id="<?php echo esc_attr($slider_id); ?>-track">
                    <div class="swipecommerce-category-section" data-category="all">
                        <div class="swipecommerce-category-header swipecommerce-category-bestsellers">
                            <span><?php echo esc_html($atts['type'] === 'sale' ? __('Sale Products', 'swipecommerce-pro') : __('Products', 'swipecommerce-pro')); ?></span>
                            <span class="swipecommerce-category-meta"><?php echo count($products); ?> <?php esc_html_e('items', 'swipecommerce-pro'); ?></span>
                        </div>
                        
                        <div class="swipecommerce-products-row">
                            <?php foreach ($products as $product): ?>
                                <?php $this->render_product_card($product); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_custom_category_html($atts) {
        $custom_category = $this->get_custom_category($atts['custom_category']);
        
        if (!$custom_category) {
            return '<div class="swipecommerce-error">' . __('Custom category not found.', 'swipecommerce-pro') . '</div>';
        }
        
        $products = $this->get_products_by_custom_category($atts['custom_category'], $atts['limit']);
        
        if (empty($products)) {
            return '<div class="swipecommerce-no-products">' . __('No products found in this category.', 'swipecommerce-pro') . '</div>';
        }

        $slider_id = 'swipecommerce-slider-' . uniqid();
        
        ob_start();
        ?>
        <div class="swipecommerce-slider-wrapper" data-slider-id="<?php echo esc_attr($slider_id); ?>">
            
            <!-- Header -->
            <div class="swipecommerce-slider-header">
                <h2><?php echo esc_html($atts['title'] ?: $custom_category['name']); ?></h2>
                <p><?php echo esc_html($atts['description'] ?: $custom_category['description']); ?></p>
            </div>
            
            <!-- Slider Container -->
            <div class="swipecommerce-slider-container">
                <button class="swipecommerce-nav-button swipecommerce-prev" 
                        id="<?php echo esc_attr($slider_id); ?>-prev" 
                        aria-label="<?php esc_attr_e('Previous', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                
                <button class="swipecommerce-nav-button swipecommerce-next" 
                        id="<?php echo esc_attr($slider_id); ?>-next" 
                        aria-label="<?php esc_attr_e('Next', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
                
                <div class="swipecommerce-slider-track" id="<?php echo esc_attr($slider_id); ?>-track">
                    <div class="swipecommerce-category-section" data-category="<?php echo esc_attr($custom_category['id']); ?>">
                        <div class="swipecommerce-category-header <?php echo esc_attr($custom_category['color_scheme']); ?>">
                            <span><?php echo esc_html($custom_category['icon'] . ' ' . $custom_category['name']); ?></span>
                            <span class="swipecommerce-category-meta"><?php echo count($products); ?> <?php esc_html_e('items', 'swipecommerce-pro'); ?></span>
                        </div>
                        
                        <div class="swipecommerce-products-row">
                            <?php foreach ($products as $product): ?>
                                <?php $this->render_product_card($product); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_multi_category_html($atts) {
        $custom_categories = $this->get_custom_categories(true); // visible only
        
        if (empty($custom_categories)) {
            // Fallback to regular products if no custom categories
            return $this->get_products_html_fallback($atts);
        }

        $slider_id = 'swipecommerce-slider-' . uniqid();
        
        ob_start();
        ?>
        <div class="swipecommerce-slider-wrapper" data-slider-id="<?php echo esc_attr($slider_id); ?>">
            
            <!-- Custom Category Navigator -->
            <div class="swipecommerce-category-navigator">
                <div class="swipecommerce-nav-pills">
                    <?php foreach ($custom_categories as $category): ?>
                        <?php $products_count = count($category['products']); ?>
                        <?php if ($products_count > 0): ?>
                            <div class="swipecommerce-nav-pill <?php echo $category === reset($custom_categories) ? 'active' : ''; ?>" 
                                 data-category="<?php echo esc_attr($category['id']); ?>">
                                <?php echo esc_html($category['icon'] . ' ' . $category['name']); ?>
                                <span class="swipecommerce-count"><?php echo $products_count; ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($atts['show_filters']): ?>
                <div class="swipecommerce-quick-filters">
                    <button class="swipecommerce-filter-btn" data-filter="sale"><?php esc_html_e('On Sale', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="new"><?php esc_html_e('New', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="featured"><?php esc_html_e('Featured', 'swipecommerce-pro'); ?></button>
                </div>
                <?php endif; ?>
                
                <div class="swipecommerce-scroll-progress">
                    <div class="swipecommerce-scroll-progress-bar"></div>
                </div>
            </div>

            <!-- Header -->
            <div class="swipecommerce-slider-header">
                <h2><?php echo esc_html($atts['title'] ?: __('Featured Collections', 'swipecommerce-pro')); ?></h2>
                <p><?php echo esc_html($atts['description'] ?: __('Curated product collections for every need', 'swipecommerce-pro')); ?></p>
            </div>
            
            <!-- Slider Container -->
            <div class="swipecommerce-slider-container">
                <button class="swipecommerce-nav-button swipecommerce-prev" 
                        id="<?php echo esc_attr($slider_id); ?>-prev" 
                        aria-label="<?php esc_attr_e('Previous', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                
                <button class="swipecommerce-nav-button swipecommerce-next" 
                        id="<?php echo esc_attr($slider_id); ?>-next" 
                        aria-label="<?php esc_attr_e('Next', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
                
                <div class="swipecommerce-slider-track" id="<?php echo esc_attr($slider_id); ?>-track">
                    <?php foreach ($custom_categories as $category): ?>
                        <?php 
                        $category_products = $this->get_products_by_custom_category($category['id'], $atts['limit']);
                        if (empty($category_products)) continue;
                        ?>
                        
                        <div class="swipecommerce-category-section" data-category="<?php echo esc_attr($category['id']); ?>">
                            <div class="swipecommerce-category-header <?php echo esc_attr($category['color_scheme']); ?>">
                                <span><?php echo esc_html($category['icon'] . ' ' . $category['name']); ?></span>
                                <span class="swipecommerce-category-meta"><?php echo count($category_products); ?> <?php esc_html_e('items', 'swipecommerce-pro'); ?></span>
                            </div>
                            
                            <div class="swipecommerce-products-row">
                                <?php foreach ($category_products as $product): ?>
                                    <?php $this->render_product_card($product, $category); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_products_html_fallback($atts) {
        $products = $this->get_products_safe($atts);
        
        if (empty($products)) {
            return '<div class="swipecommerce-no-products">' . __('No products found.', 'swipecommerce-pro') . '</div>';
        }

        $slider_id = 'swipecommerce-slider-' . uniqid();
        
        ob_start();
        ?>
        <div class="swipecommerce-slider-wrapper" data-slider-id="<?php echo esc_attr($slider_id); ?>">
            
            <!-- Category Navigator -->
            <?php if ($atts['show_filters']): ?>
            <div class="swipecommerce-category-navigator">
                <div class="swipecommerce-nav-pills">
                    <div class="swipecommerce-nav-pill active" data-category="all">
                        <?php esc_html_e('All Products', 'swipecommerce-pro'); ?>
                        <span class="swipecommerce-count"><?php echo count($products); ?></span>
                    </div>
                </div>
                
                <div class="swipecommerce-quick-filters">
                    <button class="swipecommerce-filter-btn" data-filter="sale"><?php esc_html_e('On Sale', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="new"><?php esc_html_e('New', 'swipecommerce-pro'); ?></button>
                    <button class="swipecommerce-filter-btn" data-filter="featured"><?php esc_html_e('Featured', 'swipecommerce-pro'); ?></button>
                </div>
                
                <div class="swipecommerce-scroll-progress">
                    <div class="swipecommerce-scroll-progress-bar"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="swipecommerce-slider-header">
                <h2><?php echo esc_html($atts['title'] ?: __('Featured Products', 'swipecommerce-pro')); ?></h2>
                <p><?php echo esc_html($atts['description'] ?: __('High-quality products for your health and wellness', 'swipecommerce-pro')); ?></p>
            </div>
            
            <!-- Slider Container -->
            <div class="swipecommerce-slider-container">
                <button class="swipecommerce-nav-button swipecommerce-prev" 
                        id="<?php echo esc_attr($slider_id); ?>-prev" 
                        aria-label="<?php esc_attr_e('Previous', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                    </svg>
                </button>
                
                <button class="swipecommerce-nav-button swipecommerce-next" 
                        id="<?php echo esc_attr($slider_id); ?>-next" 
                        aria-label="<?php esc_attr_e('Next', 'swipecommerce-pro'); ?>">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                    </svg>
                </button>
                
                <div class="swipecommerce-slider-track" id="<?php echo esc_attr($slider_id); ?>-track">
                    <div class="swipecommerce-category-section" data-category="all">
                        <div class="swipecommerce-category-header swipecommerce-category-bestsellers">
                            <span><?php echo esc_html($atts['type'] === 'sale' ? __('Sale Products', 'swipecommerce-pro') : __('Products', 'swipecommerce-pro')); ?></span>
                            <span class="swipecommerce-category-meta"><?php echo count($products); ?> <?php esc_html_e('items', 'swipecommerce-pro'); ?></span>
                        </div>
                        
                        <div class="swipecommerce-products-row">
                            <?php foreach ($products as $product): ?>
                                <?php $this->render_product_card($product); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_product_card($product, $category = null) {
        $sale_price = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        $is_on_sale = $product->is_on_sale();
        $is_featured = $product->is_featured();
        
        // Calculate badges
        $badges = array();
        if ($is_on_sale && $regular_price) {
            $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
            $badges[] = array('text' => "-{$discount}%", 'class' => 'badge-sale');
        }
        if ($is_featured) {
            $badges[] = array('text' => __('Featured', 'swipecommerce-pro'), 'class' => 'badge-featured');
        }
        
        // Tags for filtering
        $tags = array();
        if ($is_on_sale) $tags[] = 'sale';
        if ($is_featured) $tags[] = 'featured';
        
        $price_for_filter = $sale_price ?: $regular_price;
        ?>
        <div class="swipecommerce-product-card" 
             data-product-id="<?php echo esc_attr($product->get_id()); ?>"
             data-price="<?php echo esc_attr($price_for_filter); ?>"
             data-tags="<?php echo esc_attr(implode(',', $tags)); ?>">
             
            <div class="swipecommerce-product-image">
                <?php if ($product->get_image_id()): ?>
                    <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                <?php else: ?>
                    <div style="font-size: 48px;">📦</div>
                <?php endif; ?>
                
                <?php if (!empty($badges)): ?>
                <div class="swipecommerce-product-badges">
                    <?php foreach ($badges as $badge): ?>
                        <span class="swipecommerce-product-badge <?php echo esc_attr($badge['class']); ?>">
                            <?php echo esc_html($badge['text']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="swipecommerce-product-info">
                <div class="swipecommerce-product-name">
                    <a href="<?php echo esc_url($product->get_permalink()); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </div>
                
                <?php if ($product->get_average_rating() > 0): ?>
                <div class="swipecommerce-product-rating">
                    <span class="swipecommerce-stars">
                        <?php echo str_repeat('★', floor($product->get_average_rating())); ?>
                        <?php echo str_repeat('☆', 5 - floor($product->get_average_rating())); ?>
                    </span>
                    <span class="swipecommerce-rating-count">(<?php echo $product->get_review_count(); ?>)</span>
                </div>
                <?php endif; ?>
                
                <div class="swipecommerce-product-price">
                    <span class="swipecommerce-price-current"><?php echo $product->get_price_html(); ?></span>
                </div>
                
                <div class="swipecommerce-quick-add">
                    <div class="swipecommerce-quantity-selector">
                        <button class="swipecommerce-qty-btn swipecommerce-minus" type="button">−</button>
                        <input type="number" class="swipecommerce-qty-input" value="1" min="1" max="10" readonly>
                        <button class="swipecommerce-qty-btn swipecommerce-plus" type="button">+</button>
                    </div>
                    <button class="swipecommerce-add-btn" 
                            data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                            data-add-to-cart-url="<?php echo esc_url($product->add_to_cart_url()); ?>">
                        <?php esc_html_e('Add', 'swipecommerce-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
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

    // Custom Category Management Methods
    public function get_custom_categories($visible_only = false) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        if ($visible_only) {
            $categories = array_filter($categories, function($cat) {
                return !empty($cat['visibility']);
            });
        }
        
        // Sort by order
        usort($categories, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });
        
        return $categories;
    }

    public function get_custom_category($category_id) {
        $categories = $this->get_custom_categories();
        
        foreach ($categories as $category) {
            if ($category['id'] === $category_id) {
                return $category;
            }
        }
        
        return null;
    }

    public function save_custom_category($category_data) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        // Sanitize data
        $category_data = array(
            'id' => sanitize_key($category_data['id']),
            'name' => sanitize_text_field($category_data['name']),
            'description' => sanitize_textarea_field($category_data['description']),
            'color_scheme' => sanitize_text_field($category_data['color_scheme']),
            'icon' => wp_kses($category_data['icon'], array()),
            'order' => intval($category_data['order']),
            'products' => array_map('intval', (array)$category_data['products']),
            'visibility' => !empty($category_data['visibility'])
        );
        
        // Find existing or add new
        $found = false;
        foreach ($categories as $index => $category) {
            if ($category['id'] === $category_data['id']) {
                $categories[$index] = $category_data;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $categories[] = $category_data;
        }
        
        return update_option('swipecommerce_custom_categories', $categories);
    }

    public function delete_custom_category($category_id) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        $categories = array_filter($categories, function($cat) use ($category_id) {
            return $cat['id'] !== $category_id;
        });
        
        return update_option('swipecommerce_custom_categories', array_values($categories));
    }

    public function get_products_by_custom_category($category_id, $limit = 12) {
        $category = $this->get_custom_category($category_id);
        
        if (!$category || empty($category['products'])) {
            return array();
        }
        
        $product_ids = array_slice($category['products'], 0, $limit);
        
        try {
            $args = array(
                'include' => $product_ids,
                'status' => 'publish',
                'limit' => $limit,
            );
            
            return wc_get_products($args);
        } catch (Exception $e) {
            error_log('SwipeCommerce get_products_by_custom_category error: ' . $e->getMessage());
            return array();
        }
    }

    public function add_admin_menu() {
        // Primary admin menu for SwipeCommerce Pro
        $capability = class_exists('WooCommerce') ? 'manage_woocommerce' : 'manage_options';
        
        // Main menu page
        add_menu_page(
            __('SwipeCommerce Pro', 'swipecommerce-pro'),
            __('SwipeCommerce Pro', 'swipecommerce-pro'),
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

    public function init_admin_settings() {
        register_setting('swipecommerce_settings', 'swipecommerce_default_limit');
        register_setting('swipecommerce_settings', 'swipecommerce_default_columns');
        register_setting('swipecommerce_settings', 'swipecommerce_enable_filters');
        register_setting('swipecommerce_settings', 'swipecommerce_enable_cart_ajax');
        
        add_settings_section(
            'swipecommerce_general',
            __('General Settings', 'swipecommerce-pro'),
            null,
            'swipecommerce_settings'
        );

        add_settings_field(
            'swipecommerce_default_limit',
            __('Default Product Limit', 'swipecommerce-pro'),
            array($this, 'settings_field_number'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array('field' => 'swipecommerce_default_limit', 'default' => 12, 'min' => 1, 'max' => 50)
        );

        add_settings_field(
            'swipecommerce_default_columns',
            __('Default Columns', 'swipecommerce-pro'),
            array($this, 'settings_field_number'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array('field' => 'swipecommerce_default_columns', 'default' => 4, 'min' => 2, 'max' => 8)
        );

        add_settings_field(
            'swipecommerce_enable_filters',
            __('Enable Category Filters', 'swipecommerce-pro'),
            array($this, 'settings_field_checkbox'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array('field' => 'swipecommerce_enable_filters', 'default' => 1)
        );

        add_settings_field(
            'swipecommerce_enable_cart_ajax',
            __('Enable AJAX Add to Cart', 'swipecommerce-pro'),
            array($this, 'settings_field_checkbox'),
            'swipecommerce_settings',
            'swipecommerce_general',
            array('field' => 'swipecommerce_enable_cart_ajax', 'default' => 1)
        );
    }

    public function settings_field_number($args) {
        $value = get_option($args['field'], $args['default']);
        $min = isset($args['min']) ? $args['min'] : '';
        $max = isset($args['max']) ? $args['max'] : '';
        
        echo '<input type="number" id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '" value="' . esc_attr($value) . '"';
        if ($min !== '') echo ' min="' . esc_attr($min) . '"';
        if ($max !== '') echo ' max="' . esc_attr($max) . '"';
        echo ' class="small-text" />';
    }

    public function settings_field_checkbox($args) {
        $value = get_option($args['field'], $args['default']);
        echo '<input type="checkbox" id="' . esc_attr($args['field']) . '" name="' . esc_attr($args['field']) . '" value="1"' . checked(1, $value, false) . ' />';
        echo '<label for="' . esc_attr($args['field']) . '">' . __('Enable this feature', 'swipecommerce-pro') . '</label>';
    }

    public function admin_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('swipecommerce_messages', 'swipecommerce_message', __('Settings Saved', 'swipecommerce-pro'), 'updated');
        }
        settings_errors('swipecommerce_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (!class_exists('WooCommerce')): ?>
            <div class="notice notice-warning">
                <p><strong><?php esc_html_e('Warning:', 'swipecommerce-pro'); ?></strong> 
                <?php esc_html_e('WooCommerce is not installed or activated. Some features may not work properly.', 'swipecommerce-pro'); ?></p>
            </div>
            <?php endif; ?>
            
            <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2><?php esc_html_e('How to Use SwipeCommerce Pro', 'swipecommerce-pro'); ?></h2>
                <p><?php esc_html_e('Add Netflix-style product sliders to your pages and posts using these shortcodes:', 'swipecommerce-pro'); ?></p>
                
                <h3><?php esc_html_e('Basic Usage:', 'swipecommerce-pro'); ?></h3>
                <code>[swipecommerce_slider]</code>
                
                <h3><?php esc_html_e('With Parameters:', 'swipecommerce-pro'); ?></h3>
                <code>[swipecommerce_slider limit="12" type="featured" show_filters="true"]</code><br><br>
                <code>[swipecommerce_slider category="supplements" limit="8"]</code><br><br>
                <code>[swipecommerce_slider type="sale" limit="10"]</code>
                
                <h3><?php esc_html_e('Custom Categories:', 'swipecommerce-pro'); ?></h3>
                <code>[swipecommerce_slider]</code> <em><?php esc_html_e('- Shows all custom categories', 'swipecommerce-pro'); ?></em><br><br>
                <code>[swipecommerce_slider custom_category="bestsellers"]</code> <em><?php esc_html_e('- Shows single custom category', 'swipecommerce-pro'); ?></em><br><br>
                <code>[swipecommerce_slider show_custom_categories="false" type="recent"]</code> <em><?php esc_html_e('- Disable custom categories, show regular products', 'swipecommerce-pro'); ?></em>
                
                <h3><?php esc_html_e('Available Parameters:', 'swipecommerce-pro'); ?></h3>
                <ul>
                    <li><strong>limit</strong>: Number of products (default: <?php echo get_option('swipecommerce_default_limit', 12); ?>)</li>
                    <li><strong>type</strong>: "recent", "featured", or "sale" (default: recent)</li>
                    <li><strong>category</strong>: WooCommerce product category slug</li>
                    <li><strong>custom_category</strong>: Custom category ID (e.g., "bestsellers", "staff-picks")</li>
                    <li><strong>show_custom_categories</strong>: Show all custom categories (true/false, default: true)</li>
                    <li><strong>show_filters</strong>: Show category filters (true/false, default: <?php echo get_option('swipecommerce_enable_filters', 1) ? 'true' : 'false'; ?>)</li>
                    <li><strong>title</strong>: Custom section title</li>
                    <li><strong>description</strong>: Custom section description</li>
                </ul>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('swipecommerce_settings');
                do_settings_sections('swipecommerce_settings');
                submit_button(__('Save Settings', 'swipecommerce-pro'));
                ?>
            </form>
            
            <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea;">
                <h3><?php esc_html_e('Plugin Status', 'swipecommerce-pro'); ?></h3>
                <p><strong><?php esc_html_e('Version:', 'swipecommerce-pro'); ?></strong> <?php echo SWIPECOMMERCE_VERSION; ?></p>
                <p><strong><?php esc_html_e('WooCommerce:', 'swipecommerce-pro'); ?></strong> 
                    <?php echo class_exists('WooCommerce') ? '<span style="color: green;">✓ ' . __('Active', 'swipecommerce-pro') . '</span>' : '<span style="color: red;">✗ ' . __('Not Found', 'swipecommerce-pro') . '</span>'; ?>
                </p>
                <p><strong><?php esc_html_e('AJAX Support:', 'swipecommerce-pro'); ?></strong> 
                    <?php echo get_option('swipecommerce_enable_cart_ajax', 1) ? '<span style="color: green;">✓ ' . __('Enabled', 'swipecommerce-pro') . '</span>' : '<span style="color: orange;">○ ' . __('Disabled', 'swipecommerce-pro') . '</span>'; ?>
                </p>
            </div>
        </div>
        <?php
    }

    // AJAX Handlers for Category Management
    public function ajax_save_category() {
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'swipecommerce-pro'));
        }
        
        $category_data = array(
            'id' => sanitize_key($_POST['id'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'color_scheme' => sanitize_text_field($_POST['color_scheme'] ?? 'gradient-pink'),
            'icon' => wp_kses($_POST['icon'] ?? '🏆', array()),
            'order' => intval($_POST['order'] ?? 1),
            'products' => array_map('intval', explode(',', $_POST['products'] ?? '')),
            'visibility' => !empty($_POST['visibility'])
        );
        
        // Generate ID if new category
        if (empty($category_data['id'])) {
            $category_data['id'] = sanitize_key(strtolower(str_replace(' ', '-', $category_data['name'])));
        }
        
        $result = $this->save_custom_category($category_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Category saved successfully', 'swipecommerce-pro'),
                'category' => $category_data
            ));
        } else {
            wp_send_json_error(__('Failed to save category', 'swipecommerce-pro'));
        }
    }
    
    public function ajax_delete_category() {
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'swipecommerce-pro'));
        }
        
        $category_id = sanitize_key($_POST['category_id'] ?? '');
        
        if (empty($category_id)) {
            wp_send_json_error(__('Invalid category ID', 'swipecommerce-pro'));
        }
        
        $result = $this->delete_custom_category($category_id);
        
        if ($result) {
            wp_send_json_success(__('Category deleted successfully', 'swipecommerce-pro'));
        } else {
            wp_send_json_error(__('Failed to delete category', 'swipecommerce-pro'));
        }
    }
    
    public function ajax_search_products() {
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'swipecommerce-pro'));
        }
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = 50; // Increased from 20
        
        if (empty($search_term)) {
            wp_send_json_success(array('products' => array(), 'has_more' => false));
        }
        
        try {
            // Multi-method search approach
            $results = array();
            $found_ids = array();
            
            // 1. WooCommerce native search (titles, content)
            $args = array(
                'search' => $search_term,
                'status' => 'publish',
                'limit' => $per_page,
                'offset' => ($page - 1) * $per_page,
                'orderby' => 'relevance'
            );
            
            $products = wc_get_products($args);
            foreach ($products as $product) {
                if (!in_array($product->get_id(), $found_ids)) {
                    $found_ids[] = $product->get_id();
                    $results[] = $this->format_product_for_search($product);
                }
            }
            
            // 2. SKU search if we have room for more results
            if (count($results) < $per_page) {
                $sku_search = new WP_Query(array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => $per_page - count($results),
                    'meta_query' => array(
                        array(
                            'key' => '_sku',
                            'value' => $search_term,
                            'compare' => 'LIKE'
                        )
                    ),
                    'post__not_in' => $found_ids
                ));
                
                if ($sku_search->have_posts()) {
                    foreach ($sku_search->posts as $post) {
                        $product = wc_get_product($post->ID);
                        if ($product && !in_array($product->get_id(), $found_ids)) {
                            $found_ids[] = $product->get_id();
                            $results[] = $this->format_product_for_search($product);
                        }
                    }
                }
            }
            
            // 3. Category search if we still have room
            if (count($results) < $per_page) {
                $remaining = $per_page - count($results);
                $cat_search = new WP_Query(array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => $remaining,
                    's' => $search_term, // WordPress native search includes excerpts/content
                    'post__not_in' => $found_ids
                ));
                
                if ($cat_search->have_posts()) {
                    foreach ($cat_search->posts as $post) {
                        $product = wc_get_product($post->ID);
                        if ($product && !in_array($product->get_id(), $found_ids)) {
                            $found_ids[] = $product->get_id();
                            $results[] = $this->format_product_for_search($product);
                        }
                    }
                }
            }
            
            // Check if there are more results
            $has_more = count($results) >= $per_page;
            
            wp_send_json_success(array(
                'products' => $results,
                'has_more' => $has_more,
                'page' => $page,
                'total_found' => count($found_ids)
            ));
            
        } catch (Exception $e) {
            error_log('SwipeCommerce search error: ' . $e->getMessage());
            wp_send_json_error(__('Search failed', 'swipecommerce-pro'));
        }
    }
    
    private function format_product_for_search($product) {
        return array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail'),
            'sku' => $product->get_sku() ?: __('No SKU', 'swipecommerce-pro'),
            'status' => $product->get_status(),
            'type' => $product->get_type()
        );
    }

    // Categories Admin Page
    public function categories_page() {
        // Handle form submissions
        if ($_POST && check_admin_referer('swipecommerce_categories_nonce')) {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'save_category':
                        $this->handle_save_category_form();
                        break;
                    case 'delete_category':
                        $this->handle_delete_category_form();
                        break;
                }
            }
        }
        
        $categories = $this->get_custom_categories();
        $editing_category = null;
        
        // Check if editing a category
        if (isset($_GET['edit']) && !empty($_GET['edit'])) {
            $editing_category = $this->get_custom_category(sanitize_key($_GET['edit']));
        }
        
        $this->render_categories_page($categories, $editing_category);
    }

    private function handle_save_category_form() {
        $category_data = array(
            'id' => sanitize_key($_POST['category_id'] ?? ''),
            'name' => sanitize_text_field($_POST['category_name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['category_description'] ?? ''),
            'color_scheme' => sanitize_text_field($_POST['color_scheme'] ?? 'gradient-pink'),
            'icon' => wp_kses($_POST['category_icon'] ?? '🏆', array()),
            'order' => intval($_POST['category_order'] ?? 1),
            'products' => array_filter(array_map('intval', explode(',', $_POST['category_products'] ?? ''))),
            'visibility' => !empty($_POST['category_visibility'])
        );
        
        // Generate ID if new category
        if (empty($category_data['id'])) {
            $category_data['id'] = sanitize_key(strtolower(str_replace(' ', '-', $category_data['name'])));
        }
        
        if ($this->save_custom_category($category_data)) {
            echo '<div class="notice notice-success"><p>' . __('Category saved successfully!', 'swipecommerce-pro') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to save category.', 'swipecommerce-pro') . '</p></div>';
        }
    }

    private function handle_delete_category_form() {
        $category_id = sanitize_key($_POST['delete_category_id'] ?? '');
        
        if (!empty($category_id) && $this->delete_custom_category($category_id)) {
            echo '<div class="notice notice-success"><p>' . __('Category deleted successfully!', 'swipecommerce-pro') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to delete category.', 'swipecommerce-pro') . '</p></div>';
        }
    }

    private function render_categories_page($categories, $editing_category) {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Custom Categories', 'swipecommerce-pro'); ?>
                <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&action=new'); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Category', 'swipecommerce-pro'); ?>
                </a>
            </h1>

            <?php if (!class_exists('WooCommerce')): ?>
            <div class="notice notice-warning">
                <p><strong><?php esc_html_e('Warning:', 'swipecommerce-pro'); ?></strong> 
                <?php esc_html_e('WooCommerce is not installed or activated. Product assignment may not work properly.', 'swipecommerce-pro'); ?></p>
            </div>
            <?php endif; ?>

            <div class="swipecommerce-categories-admin">
                <?php if (isset($_GET['action']) && $_GET['action'] === 'new' || $editing_category): ?>
                    <!-- Category Form -->
                    <?php $this->render_category_form($editing_category); ?>
                <?php else: ?>
                    <!-- Categories List -->
                    <?php $this->render_categories_list($categories); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Enhanced Visual Manager Styles -->
        <style>
            /* Visual Manager Layout */
            .swipecommerce-visual-manager {
                background: #f9f9f9;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }

            .visual-manager-header {
                background: white;
                padding: 20px;
                border-bottom: 1px solid #e0e0e0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .view-controls {
                display: flex;
                gap: 10px;
            }

            .view-btn {
                background: #f6f7f7;
                border: 1px solid #ddd;
                padding: 8px 15px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }

            .view-btn:hover {
                background: #e8f0fe;
                border-color: #2271b1;
            }

            .view-btn.active {
                background: #2271b1;
                color: white;
                border-color: #2271b1;
            }

            .bulk-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }

            /* Empty State */
            .no-categories-state {
                text-align: center;
                padding: 60px 20px;
            }

            .empty-state-icon {
                margin-bottom: 20px;
            }

            .no-categories-state h3 {
                color: #50575e;
                margin-bottom: 10px;
            }

            .no-categories-state p {
                color: #646970;
                margin-bottom: 30px;
            }

            /* Drag Drop Notice */
            .drag-drop-notice {
                background: #e8f4fd;
                border-left: 4px solid #2271b1;
                padding: 12px 20px;
                margin: 20px;
                border-radius: 4px;
                display: flex;
                align-items: center;
                gap: 10px;
                color: #1d2327;
            }

            /* Categories Container */
            .categories-container {
                padding: 20px;
                display: grid;
                gap: 20px;
            }

            .categories-container.grid-view {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }

            .categories-container.list-view {
                grid-template-columns: 1fr;
            }

            /* Enhanced Category Cards */
            .category-card-enhanced {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                overflow: hidden;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                cursor: move;
                position: relative;
            }

            .category-card-enhanced:hover {
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                transform: translateY(-2px);
            }

            .category-card-enhanced.ui-sortable-helper {
                transform: rotate(5deg) scale(1.02);
                box-shadow: 0 12px 30px rgba(0,0,0,0.25);
                z-index: 1000;
            }

            /* Card Header */
            .category-card-header {
                position: absolute;
                top: 10px;
                left: 10px;
                right: 10px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                z-index: 2;
            }

            .card-select input[type="checkbox"] {
                transform: scale(1.2);
            }

            .drag-handle {
                background: rgba(255,255,255,0.9);
                border-radius: 6px;
                padding: 6px;
                cursor: move;
                opacity: 0;
                transition: opacity 0.2s;
            }

            .category-card-enhanced:hover .drag-handle {
                opacity: 1;
            }

            .category-status .status-active {
                color: #00a32a;
            }

            .category-status .status-inactive {
                color: #dba617;
            }

            /* Visual Section */
            .category-visual {
                height: 120px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .category-gradient-preview {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                opacity: 0.9;
            }

            .category-icon-large {
                font-size: 48px;
                z-index: 1;
                position: relative;
                filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
            }

            /* Card Content */
            .category-content {
                padding: 20px;
            }

            .category-name {
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 8px 0;
                color: #1d2327;
            }

            .category-description {
                color: #646970;
                font-size: 13px;
                line-height: 1.4;
                margin: 0 0 15px 0;
            }

            .category-stats {
                display: flex;
                gap: 20px;
            }

            .stat-item {
                text-align: center;
            }

            .stat-number {
                display: block;
                font-size: 20px;
                font-weight: 700;
                color: #2271b1;
            }

            .stat-label {
                font-size: 11px;
                color: #646970;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            /* Card Actions */
            .category-actions {
                position: absolute;
                top: 10px;
                right: 10px;
                display: flex;
                gap: 5px;
                opacity: 0;
                transition: opacity 0.2s;
            }

            .category-card-enhanced:hover .category-actions {
                opacity: 1;
            }

            .action-btn {
                background: rgba(255,255,255,0.95);
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 6px;
                cursor: pointer;
                transition: all 0.2s;
                text-decoration: none;
                color: #50575e;
            }

            .action-btn:hover {
                background: #2271b1;
                color: white;
                border-color: #2271b1;
            }

            .action-btn.delete-category:hover {
                background: #d63638;
                border-color: #d63638;
            }

            /* List View Modifications */
            .categories-container.list-view .category-card-enhanced {
                display: flex;
                align-items: center;
                padding: 20px;
            }

            .categories-container.list-view .category-visual {
                width: 80px;
                height: 60px;
                margin-right: 20px;
                border-radius: 8px;
                overflow: hidden;
            }

            .categories-container.list-view .category-icon-large {
                font-size: 24px;
            }

            .categories-container.list-view .category-content {
                flex: 1;
                padding: 0;
            }

            .categories-container.list-view .category-stats {
                margin-left: auto;
                margin-right: 100px;
            }

            /* Modal Styles */
            .swipecommerce-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .modal-content {
                background: white;
                border-radius: 8px;
                max-width: 500px;
                width: 90%;
                max-height: 90%;
                overflow: auto;
                box-shadow: 0 25px 50px rgba(0,0,0,0.25);
            }

            .modal-header {
                padding: 20px 20px 0 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #e0e0e0;
                margin-bottom: 20px;
            }

            .modal-header h2 {
                margin: 0;
                padding-bottom: 15px;
            }

            .modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: background 0.2s;
            }

            .modal-close:hover {
                background: #f0f0f0;
            }

            .modal-body {
                padding: 0 20px 20px 20px;
            }

            .form-row {
                margin-bottom: 20px;
            }

            .form-row label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
            }

            .modal-footer {
                padding: 15px 20px;
                border-top: 1px solid #e0e0e0;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }

            /* Gradient Previews */
            .gradient-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
            .gradient-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
            .gradient-green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
            .gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .gradient-orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

            /* Responsive */
            @media (max-width: 768px) {
                .visual-manager-header {
                    flex-direction: column;
                    gap: 15px;
                    align-items: stretch;
                }

                .categories-container.grid-view {
                    grid-template-columns: 1fr;
                }

                .categories-container.list-view .category-card-enhanced {
                    flex-direction: column;
                    text-align: center;
                }

                .categories-container.list-view .category-stats {
                    margin: 15px 0 0 0;
                }
            }
        </style>

        <!-- Enhanced JavaScript for Visual Manager -->
        <script>
            jQuery(document).ready(function($) {
                // Initialize sortable
                if (typeof $.fn.sortable !== 'undefined') {
                    $('#sortable-categories').sortable({
                        handle: '.drag-handle',
                        placeholder: 'category-placeholder',
                        tolerance: 'pointer',
                        start: function(e, ui) {
                            ui.placeholder.height(ui.item.height());
                            ui.item.addClass('ui-sortable-helper');
                        },
                        stop: function(e, ui) {
                            ui.item.removeClass('ui-sortable-helper');
                            saveOrder();
                        }
                    });
                }

                // View toggle
                $('.view-btn').click(function() {
                    const view = $(this).data('view');
                    $('.view-btn').removeClass('active');
                    $(this).addClass('active');
                    
                    const container = $('.categories-container');
                    container.removeClass('grid-view list-view').addClass(view + '-view');
                });

                // Quick edit modal
                $('.quick-edit').click(function() {
                    const categoryId = $(this).data('category-id');
                    openQuickEditModal(categoryId);
                });

                $('.modal-close').click(function() {
                    $('#quick-edit-modal').hide();
                });

                $('#save-quick-edit').click(function() {
                    saveQuickEdit();
                });

                // Toggle visibility
                $('.toggle-visibility').click(function() {
                    const categoryId = $(this).data('category-id');
                    const isVisible = $(this).data('visible') === 'true';
                    toggleVisibility(categoryId, !isVisible);
                });

                // Delete category
                $('.delete-category').click(function() {
                    const categoryId = $(this).data('category-id');
                    if (confirm('<?php esc_attr_e("Are you sure you want to delete this category?", "swipecommerce-pro"); ?>')) {
                        deleteCategory(categoryId);
                    }
                });

                // Bulk actions
                $('#apply-bulk-action').click(function() {
                    const action = $('#bulk-action-selector').val();
                    const selected = $('.category-checkbox:checked').map(function() {
                        return this.value;
                    }).get();

                    if (!action || selected.length === 0) {
                        alert('<?php esc_attr_e("Please select an action and categories", "swipecommerce-pro"); ?>');
                        return;
                    }

                    applyBulkAction(action, selected);
                });

                // Functions
                function openQuickEditModal(categoryId) {
                    // Get category data from card
                    const card = $('[data-category-id="' + categoryId + '"]');
                    const name = card.find('.category-name').text();
                    const description = card.find('.category-description').text();
                    const icon = card.find('.category-icon-large').text();
                    const colorScheme = card.find('.category-gradient-preview').attr('class').replace('category-gradient-preview ', '');
                    const isVisible = card.find('.toggle-visibility').data('visible') === 'true';

                    // Populate modal
                    $('#quick-edit-category-id').val(categoryId);
                    $('#quick-edit-name').val(name);
                    $('#quick-edit-description').val(description);
                    $('#quick-edit-icon').val(icon);
                    $('#quick-edit-color').val(colorScheme);
                    $('#quick-edit-visibility').prop('checked', isVisible);

                    // Show modal
                    $('#quick-edit-modal').show();
                }

                function saveQuickEdit() {
                    const categoryId = $('#quick-edit-category-id').val();
                    const formData = {
                        action: 'swipecommerce_save_category',
                        nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                        id: categoryId,
                        name: $('#quick-edit-name').val(),
                        description: $('#quick-edit-description').val(),
                        icon: $('#quick-edit-icon').val(),
                        color_scheme: $('#quick-edit-color').val(),
                        visibility: $('#quick-edit-visibility').is(':checked') ? '1' : '0',
                        order: $('[data-category-id="' + categoryId + '"]').data('order')
                    };

                    $.post(ajaxurl, formData, function(response) {
                        if (response.success) {
                            $('#quick-edit-modal').hide();
                            location.reload(); // Simple reload for now
                        } else {
                            alert('<?php esc_attr_e("Failed to save changes", "swipecommerce-pro"); ?>');
                        }
                    });
                }

                function toggleVisibility(categoryId, visible) {
                    $.post(ajaxurl, {
                        action: 'swipecommerce_toggle_category_visibility',
                        nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                        category_id: categoryId,
                        visible: visible ? '1' : '0'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }

                function deleteCategory(categoryId) {
                    $.post(ajaxurl, {
                        action: 'swipecommerce_delete_category',
                        nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                        category_id: categoryId
                    }, function(response) {
                        if (response.success) {
                            $('[data-category-id="' + categoryId + '"]').fadeOut(function() {
                                $(this).remove();
                            });
                        }
                    });
                }

                function saveOrder() {
                    const order = $('#sortable-categories .category-card-enhanced').map(function(index) {
                        return {
                            id: $(this).data('category-id'),
                            order: index + 1
                        };
                    }).get();

                    $.post(ajaxurl, {
                        action: 'swipecommerce_save_category_order',
                        nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                        order: JSON.stringify(order)
                    });
                }

                function applyBulkAction(action, categoryIds) {
                    $.post(ajaxurl, {
                        action: 'swipecommerce_bulk_category_action',
                        nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                        bulk_action: action,
                        category_ids: categoryIds
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data || '<?php esc_attr_e("Bulk action failed", "swipecommerce-pro"); ?>');
                        }
                    });
                }
            });
        </script>
        <?php
    }

    private function render_categories_list($categories) {
        ?>
        <div class="swipecommerce-visual-manager">
            <!-- Top Bar -->
            <div class="visual-manager-header">
                <div class="view-controls">
                    <button type="button" class="view-btn active" data-view="grid">
                        <span class="dashicons dashicons-grid-view"></span> <?php esc_html_e('Grid View', 'swipecommerce-pro'); ?>
                    </button>
                    <button type="button" class="view-btn" data-view="list">
                        <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('List View', 'swipecommerce-pro'); ?>
                    </button>
                </div>
                
                <div class="bulk-actions">
                    <select id="bulk-action-selector">
                        <option value=""><?php esc_html_e('Bulk Actions', 'swipecommerce-pro'); ?></option>
                        <option value="enable"><?php esc_html_e('Enable Selected', 'swipecommerce-pro'); ?></option>
                        <option value="disable"><?php esc_html_e('Disable Selected', 'swipecommerce-pro'); ?></option>
                        <option value="delete"><?php esc_html_e('Delete Selected', 'swipecommerce-pro'); ?></option>
                    </select>
                    <button type="button" class="button" id="apply-bulk-action">
                        <?php esc_html_e('Apply', 'swipecommerce-pro'); ?>
                    </button>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div class="no-categories-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-category" style="font-size: 48px; color: #c3c4c7;"></span>
                    </div>
                    <h3><?php esc_html_e('No Custom Categories Yet', 'swipecommerce-pro'); ?></h3>
                    <p><?php esc_html_e('Create your first custom category to organize products in beautiful Netflix-style sliders.', 'swipecommerce-pro'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&action=new'); ?>" class="button button-primary button-hero">
                        <span class="dashicons dashicons-plus-alt" style="margin-right: 5px;"></span>
                        <?php esc_html_e('Create Your First Category', 'swipecommerce-pro'); ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- Drag & Drop Notice -->
                <div class="drag-drop-notice">
                    <span class="dashicons dashicons-move"></span>
                    <?php esc_html_e('Drag and drop categories to reorder them. Changes are saved automatically.', 'swipecommerce-pro'); ?>
                </div>

                <!-- Categories Grid/List -->
                <div class="categories-container grid-view" id="sortable-categories">
                    <?php foreach ($categories as $index => $category): ?>
                        <div class="category-card-enhanced" data-category-id="<?php echo esc_attr($category['id']); ?>" data-order="<?php echo esc_attr($category['order']); ?>">
                            <div class="category-card-header">
                                <div class="card-select">
                                    <input type="checkbox" class="category-checkbox" value="<?php echo esc_attr($category['id']); ?>">
                                </div>
                                <div class="drag-handle">
                                    <span class="dashicons dashicons-move"></span>
                                </div>
                                <div class="category-status">
                                    <?php if ($category['visibility']): ?>
                                        <span class="status-active" title="<?php esc_attr_e('Visible', 'swipecommerce-pro'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-inactive" title="<?php esc_attr_e('Hidden', 'swipecommerce-pro'); ?>">
                                            <span class="dashicons dashicons-hidden"></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="category-visual">
                                <div class="category-icon-large">
                                    <?php echo esc_html($category['icon']); ?>
                                </div>
                                <div class="category-gradient-preview <?php echo esc_attr($category['color_scheme']); ?>"></div>
                            </div>

                            <div class="category-content">
                                <h3 class="category-name"><?php echo esc_html($category['name']); ?></h3>
                                <p class="category-description"><?php echo esc_html($category['description']); ?></p>
                                
                                <div class="category-stats">
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo count($category['products']); ?></span>
                                        <span class="stat-label"><?php esc_html_e('Products', 'swipecommerce-pro'); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo esc_html($category['order']); ?></span>
                                        <span class="stat-label"><?php esc_html_e('Order', 'swipecommerce-pro'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="category-actions">
                                <button type="button" class="action-btn quick-edit" data-category-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Quick Edit', 'swipecommerce-pro'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&edit=' . $category['id']); ?>" class="action-btn" title="<?php esc_attr_e('Full Edit', 'swipecommerce-pro'); ?>">
                                    <span class="dashicons dashicons-admin-generic"></span>
                                </a>
                                <button type="button" class="action-btn toggle-visibility" data-category-id="<?php echo esc_attr($category['id']); ?>" data-visible="<?php echo $category['visibility'] ? 'true' : 'false'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'swipecommerce-pro'); ?>">
                                    <span class="dashicons dashicons-<?php echo $category['visibility'] ? 'visibility' : 'hidden'; ?>"></span>
                                </button>
                                <button type="button" class="action-btn delete-category" data-category-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Delete', 'swipecommerce-pro'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Edit Modal -->
        <div id="quick-edit-modal" class="swipecommerce-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><?php esc_html_e('Quick Edit Category', 'swipecommerce-pro'); ?></h2>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="quick-edit-form">
                        <input type="hidden" id="quick-edit-category-id">
                        
                        <div class="form-row">
                            <label for="quick-edit-name"><?php esc_html_e('Category Name', 'swipecommerce-pro'); ?></label>
                            <input type="text" id="quick-edit-name" class="regular-text" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="quick-edit-description"><?php esc_html_e('Description', 'swipecommerce-pro'); ?></label>
                            <textarea id="quick-edit-description" rows="3" class="large-text"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <label for="quick-edit-icon"><?php esc_html_e('Icon', 'swipecommerce-pro'); ?></label>
                            <input type="text" id="quick-edit-icon" class="small-text">
                        </div>
                        
                        <div class="form-row">
                            <label for="quick-edit-color"><?php esc_html_e('Color Scheme', 'swipecommerce-pro'); ?></label>
                            <select id="quick-edit-color">
                                <option value="gradient-pink"><?php esc_html_e('Pink Gradient', 'swipecommerce-pro'); ?></option>
                                <option value="gradient-blue"><?php esc_html_e('Blue Gradient', 'swipecommerce-pro'); ?></option>
                                <option value="gradient-green"><?php esc_html_e('Green Gradient', 'swipecommerce-pro'); ?></option>
                                <option value="gradient-purple"><?php esc_html_e('Purple Gradient', 'swipecommerce-pro'); ?></option>
                                <option value="gradient-orange"><?php esc_html_e('Orange Gradient', 'swipecommerce-pro'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <label>
                                <input type="checkbox" id="quick-edit-visibility">
                                <?php esc_html_e('Visible in frontend', 'swipecommerce-pro'); ?>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button button-primary" id="save-quick-edit">
                        <?php esc_html_e('Save Changes', 'swipecommerce-pro'); ?>
                    </button>
                    <button type="button" class="button modal-close">
                        <?php esc_html_e('Cancel', 'swipecommerce-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_category_form($editing_category) {
        $category = $editing_category ?: array(
            'id' => '',
            'name' => '',
            'description' => '',
            'color_scheme' => 'gradient-pink',
            'icon' => '🏆',
            'order' => 1,
            'products' => array(),
            'visibility' => true
        );

        $color_schemes = array(
            'gradient-pink' => __('Pink Gradient', 'swipecommerce-pro'),
            'gradient-blue' => __('Blue Gradient', 'swipecommerce-pro'),
            'gradient-green' => __('Green Gradient', 'swipecommerce-pro'),
            'gradient-purple' => __('Purple Gradient', 'swipecommerce-pro'),
            'gradient-orange' => __('Orange Gradient', 'swipecommerce-pro'),
        );
        ?>
        
        <div class="category-form">
            <h2><?php echo $editing_category ? esc_html__('Edit Category', 'swipecommerce-pro') : esc_html__('Add New Category', 'swipecommerce-pro'); ?></h2>
            
            <form method="post">
                <?php wp_nonce_field('swipecommerce_categories_nonce'); ?>
                <input type="hidden" name="action" value="save_category">
                <input type="hidden" name="category_id" value="<?php echo esc_attr($category['id']); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="category_name"><?php esc_html_e('Category Name', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="category_name" name="category_name" value="<?php echo esc_attr($category['name']); ?>" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="category_description"><?php esc_html_e('Description', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <textarea id="category_description" name="category_description" rows="3" class="large-text"><?php echo esc_textarea($category['description']); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="category_icon"><?php esc_html_e('Icon (Emoji)', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="category_icon" name="category_icon" value="<?php echo esc_attr($category['icon']); ?>" class="small-text">
                            <p class="description"><?php esc_html_e('Enter an emoji to represent this category', 'swipecommerce-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="color_scheme"><?php esc_html_e('Color Scheme', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <select id="color_scheme" name="color_scheme">
                                <?php foreach ($color_schemes as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($category['color_scheme'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="color-scheme-preview <?php echo esc_attr($category['color_scheme']); ?>"></span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="category_order"><?php esc_html_e('Display Order', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="category_order" name="category_order" value="<?php echo esc_attr($category['order']); ?>" class="small-text" min="1">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Visibility', 'swipecommerce-pro'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="category_visibility" value="1" <?php checked($category['visibility'], true); ?>>
                                <?php esc_html_e('Show this category in sliders', 'swipecommerce-pro'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php esc_html_e('Assigned Products', 'swipecommerce-pro'); ?></label>
                        </th>
                        <td>
                            <div id="assigned-products">
                                <input type="hidden" id="category_products" name="category_products" value="<?php echo esc_attr(implode(',', $category['products'])); ?>">
                                
                                <div class="product-search">
                                    <input type="text" id="product-search" placeholder="<?php esc_attr_e('Search products...', 'swipecommerce-pro'); ?>" class="regular-text">
                                    <div id="search-results" style="display: none;"></div>
                                </div>
                                
                                <div id="selected-products" class="selected-products">
                                    <!-- Selected products will be loaded here -->
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="<?php echo $editing_category ? esc_attr__('Update Category', 'swipecommerce-pro') : esc_attr__('Create Category', 'swipecommerce-pro'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories'); ?>" class="button">
                        <?php esc_html_e('Cancel', 'swipecommerce-pro'); ?>
                    </a>
                </p>
            </form>
        </div>

        <!-- Enhanced Admin JavaScript -->
        <script>
            jQuery(document).ready(function($) {
                // Load existing products
                loadSelectedProducts();
                
                // Color scheme preview update
                $('#color_scheme').change(function() {
                    $('.color-scheme-preview').attr('class', 'color-scheme-preview ' + $(this).val());
                });
                
                // Product search functionality
                let searchTimeout;
                $('#product-search').on('input', function() {
                    clearTimeout(searchTimeout);
                    const searchTerm = $(this).val();
                    
                    if (searchTerm.length < 2) {
                        $('#search-results').hide();
                        return;
                    }
                    
                    searchTimeout = setTimeout(() => {
                        searchProducts(searchTerm);
                    }, 300);
                });
                
                function searchProducts(term, page = 1) {
                    $('#search-results').html('<div class="search-loading"><p><?php esc_html_e("Searching...", "swipecommerce-pro"); ?></p></div>').show();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'swipecommerce_search_products',
                            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                            search: term,
                            page: page
                        },
                        success: function(response) {
                            if (response.success) {
                                displaySearchResults(response.data.products, response.data.has_more, response.data.total_found);
                            } else {
                                $('#search-results').html('<div class="search-error"><p><?php esc_html_e("Search failed. Please try again.", "swipecommerce-pro"); ?></p></div>').show();
                            }
                        },
                        error: function() {
                            $('#search-results').html('<div class="search-error"><p><?php esc_html_e("Search failed. Please try again.", "swipecommerce-pro"); ?></p></div>').show();
                        }
                    });
                }
                
                function displaySearchResults(products, hasMore = false, totalFound = 0) {
                    const resultsDiv = $('#search-results');
                    const selectedProducts = $('#category_products').val().split(',').map(id => parseInt(id)).filter(id => id > 0);
                    
                    if (products.length === 0) {
                        resultsDiv.html(`
                            <div class="search-no-results">
                                <p><?php esc_html_e("No products found", "swipecommerce-pro"); ?></p>
                                <small><?php esc_html_e("Try searching by product name, SKU, or description", "swipecommerce-pro"); ?></small>
                            </div>
                        `).show();
                        return;
                    }
                    
                    let html = '<div class="search-results-header">';
                    html += `<p><strong>${totalFound}</strong> <?php esc_html_e("products found", "swipecommerce-pro"); ?></p>`;
                    html += '</div>';
                    
                    html += '<div class="search-results-list">';
                    products.forEach(product => {
                        const isSelected = selectedProducts.includes(product.id);
                        const imageUrl = product.image && product.image !== '' ? product.image : '<?php echo wc_placeholder_img_src("thumbnail"); ?>';
                        html += `
                            <div class="search-result-item ${isSelected ? 'selected' : ''}" data-product-id="${product.id}">
                                <img src="${imageUrl}" alt="" width="40" height="40" onerror="this.src='<?php echo wc_placeholder_img_src("thumbnail"); ?>'">
                                <div class="product-info">
                                    <strong>${product.name}</strong>
                                    <span class="price">${product.price}</span>
                                    <span class="sku"><?php esc_html_e("SKU:", "swipecommerce-pro"); ?> ${product.sku}</span>
                                    <span class="type">${product.type}</span>
                                </div>
                                <button type="button" class="button button-small ${isSelected ? 'remove-product' : 'add-product'}">
                                    ${isSelected ? '<?php esc_html_e("Remove", "swipecommerce-pro"); ?>' : '<?php esc_html_e("Add", "swipecommerce-pro"); ?>'}
                                </button>
                            </div>
                        `;
                    });
                    html += '</div>';
                    
                    if (hasMore) {
                        html += '<div class="search-load-more"><p><em><?php esc_html_e("Showing first 50 results. Be more specific to find other products.", "swipecommerce-pro"); ?></em></p></div>';
                    }
                    
                    resultsDiv.html(html).show();
                }
                
                // Handle add/remove product buttons
                $(document).on('click', '.add-product, .remove-product', function() {
                    const productId = parseInt($(this).closest('.search-result-item').data('product-id'));
                    const isAdding = $(this).hasClass('add-product');
                    
                    if (isAdding) {
                        addProductToCategory(productId);
                    } else {
                        removeProductFromCategory(productId);
                    }
                    
                    // Update button state
                    $(this).removeClass('add-product remove-product')
                           .addClass(isAdding ? 'remove-product' : 'add-product')
                           .text(isAdding ? '<?php esc_html_e("Remove", "swipecommerce-pro"); ?>' : '<?php esc_html_e("Add", "swipecommerce-pro"); ?>');
                    
                    $(this).closest('.search-result-item').toggleClass('selected');
                });
                
                function addProductToCategory(productId) {
                    let selectedProducts = $('#category_products').val().split(',').map(id => parseInt(id)).filter(id => id > 0);
                    if (!selectedProducts.includes(productId)) {
                        selectedProducts.push(productId);
                        $('#category_products').val(selectedProducts.join(','));
                        loadSelectedProducts();
                    }
                }
                
                function removeProductFromCategory(productId) {
                    let selectedProducts = $('#category_products').val().split(',').map(id => parseInt(id)).filter(id => id > 0);
                    selectedProducts = selectedProducts.filter(id => id !== productId);
                    $('#category_products').val(selectedProducts.join(','));
                    loadSelectedProducts();
                }
                
                function loadSelectedProducts() {
                    const productIds = $('#category_products').val();
                    if (!productIds) {
                        $('#selected-products').html('<p><?php esc_html_e("No products assigned to this category yet.", "swipecommerce-pro"); ?></p>');
                        return;
                    }
                    
                    // For now, just show the count - we could enhance this to show actual product details
                    const ids = productIds.split(',').filter(id => id);
                    $('#selected-products').html(`<p><strong>${ids.length}</strong> <?php esc_html_e("products assigned", "swipecommerce-pro"); ?></p>`);
                }
                
                // Hide search results when clicking outside
                $(document).click(function(e) {
                    if (!$(e.target).closest('#assigned-products').length) {
                        $('#search-results').hide();
                    }
                });
            });
        </script>
        
        <style>
            .search-results-list {
                border: 1px solid #ddd;
                background: white;
                max-height: 400px;
                overflow-y: auto;
                border-radius: 4px;
            }
            
            .search-results-header {
                padding: 10px;
                background: #f8f9fa;
                border-bottom: 1px solid #ddd;
                font-size: 13px;
            }
            
            .search-loading {
                padding: 20px;
                text-align: center;
                color: #666;
                font-style: italic;
            }
            
            .search-error {
                padding: 15px;
                background: #ffe6e6;
                color: #d63638;
                border-radius: 4px;
            }
            
            .search-no-results {
                padding: 20px;
                text-align: center;
                color: #666;
            }
            
            .search-no-results small {
                display: block;
                margin-top: 5px;
                color: #999;
            }
            
            .search-result-item {
                display: flex;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #eee;
                gap: 12px;
                transition: background-color 0.2s;
            }
            
            .search-result-item:hover {
                background: #f8f9fa;
            }
            
            .search-result-item.selected {
                background: #e8f4fd;
                border-left: 3px solid #0073aa;
            }
            
            .search-result-item img {
                border-radius: 4px;
                border: 1px solid #ddd;
                flex-shrink: 0;
            }
            
            .product-info {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            
            .product-info strong {
                font-size: 14px;
                line-height: 1.3;
                color: #23282d;
            }
            
            .product-info .price {
                color: #0073aa;
                font-size: 12px;
                font-weight: 600;
            }
            
            .product-info .sku {
                color: #666;
                font-size: 11px;
            }
            
            .product-info .type {
                color: #999;
                font-size: 10px;
                text-transform: uppercase;
                background: #f0f0f0;
                padding: 2px 6px;
                border-radius: 10px;
                display: inline-block;
                width: fit-content;
            }
            
            .search-load-more {
                padding: 15px;
                background: #f8f9fa;
                border-top: 1px solid #ddd;
                text-align: center;
                font-style: italic;
                color: #666;
            }
            
            .selected-products {
                margin-top: 15px;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 4px;
                border-left: 4px solid #0073aa;
            }
            
            #product-search {
                position: relative;
            }
            
            #search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                margin-top: 2px;
            }
        </style>
        <?php
    }

    private function has_shortcode_in_content() {
        global $post;
        return $post && has_shortcode($post->post_content, 'swipecommerce_slider');
    }
}

// Initialize the safe version
SwipeCommercePro_Safe::instance();