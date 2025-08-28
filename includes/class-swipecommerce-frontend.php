<?php
/**
 * The frontend-specific functionality of the plugin.
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
 * The frontend-specific functionality of the plugin.
 *
 * Handles shortcode rendering and frontend display logic.
 */
class SwipeCommerce_Frontend {

    /**
     * The categories manager instance.
     *
     * @since    1.0.7
     * @var      SwipeCommerce_Categories
     */
    private $categories;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.7
     * @param    SwipeCommerce_Categories    $categories    The categories manager instance.
     */
    public function __construct($categories) {
        $this->categories = $categories;
        $this->init();
    }

    /**
     * Initialize frontend hooks.
     *
     * @since    1.0.7
     */
    private function init() {
        add_shortcode('swipecommerce_slider', array($this, 'render_shortcode'));
    }

    /**
     * Render the swipecommerce_slider shortcode.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The shortcode output.
     */
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

    /**
     * Get the products HTML based on shortcode attributes.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The products HTML.
     */
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

    /**
     * Get HTML for a single custom category.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The custom category HTML.
     */
    private function get_custom_category_html($atts) {
        $custom_category = $this->categories->get_custom_category($atts['custom_category']);
        
        if (!$custom_category) {
            return '<div class="swipecommerce-error">' . __('Custom category not found.', 'swipecommerce-pro') . '</div>';
        }
        
        $products = $this->categories->get_products_by_custom_category($atts['custom_category'], $atts['limit']);
        
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

    /**
     * Get HTML for multiple custom categories.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The multi-category HTML.
     */
    private function get_multi_category_html($atts) {
        $custom_categories = $this->categories->get_custom_categories(true); // visible only
        
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
                        $category_products = $this->categories->get_products_by_custom_category($category['id'], $atts['limit']);
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

    /**
     * Get fallback HTML for products when no custom categories are available.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   string            The fallback HTML.
     */
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

    /**
     * Render a single product card.
     *
     * @since    1.0.7
     * @param    WC_Product    $product     The WooCommerce product object.
     * @param    array|null    $category    Optional category data for styling.
     */
    public function render_product_card($product, $category = null) {
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
                <a href="<?php echo esc_url($product->get_permalink()); ?>" class="swipecommerce-product-image-link">
                    <?php if ($product->get_image_id()): ?>
                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                    <?php else: ?>
                        <div style="font-size: 48px;">📦</div>
                    <?php endif; ?>
                </a>
                
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
                
                <?php 
                // Get product description using get_the_excerpt()
                $post_id = $product->get_id();
                $excerpt = get_the_excerpt($post_id);
                if (empty($excerpt)) {
                    // Fallback to short description if no excerpt
                    $excerpt = $product->get_short_description();
                }
                if (!empty($excerpt)): 
                ?>
                <div class="swipecommerce-product-description">
                    <?php echo wp_trim_words(wp_strip_all_tags($excerpt), 15, '...'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Safely retrieve products based on attributes.
     *
     * @since    1.0.7
     * @param    array    $atts    Shortcode attributes.
     * @return   array             Array of WC_Product objects.
     */
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
}