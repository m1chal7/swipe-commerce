<?php
if (!defined('ABSPATH')) {
    exit;
}

$slider_id = 'swipecommerce-slider-' . uniqid();
$filter_options = array(
    'sale' => __('On Sale', 'swipecommerce-pro'),
    'new' => __('New', 'swipecommerce-pro'),
    'under30' => sprintf(__('Under %s', 'swipecommerce-pro'), wc_price(30)),
    'bestseller' => __('Top Rated', 'swipecommerce-pro')
);
?>

<div class="swipecommerce-slider-wrapper <?php echo esc_attr($atts['class']); ?>" 
     data-config="<?php echo esc_attr(wp_json_encode($atts)); ?>">
     
    <?php if ($atts['show_filters']): ?>
    <!-- Sticky Category Navigator -->
    <div class="swipecommerce-category-navigator">
        <div class="swipecommerce-nav-pills">
            <?php foreach ($slider_data as $index => $section): ?>
                <div class="swipecommerce-nav-pill <?php echo $index === 0 ? 'active' : ''; ?>" 
                     data-category="<?php echo esc_attr($section['category']->slug); ?>">
                    <?php echo esc_html($section['category']->name); ?>
                    <?php if (!empty($section['products'])): ?>
                        <span class="swipecommerce-count"><?php echo count($section['products']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="swipecommerce-quick-filters">
            <?php foreach ($filter_options as $filter => $label): ?>
                <button class="swipecommerce-filter-btn" data-filter="<?php echo esc_attr($filter); ?>">
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <div class="swipecommerce-scroll-progress">
            <div class="swipecommerce-scroll-progress-bar"></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="swipecommerce-slider-header">
        <h2><?php echo esc_html($atts['title'] ?? __('Featured Collections', 'swipecommerce-pro')); ?></h2>
        <p><?php echo esc_html($atts['description'] ?? __('Free shipping on orders over $50 • 30-day money back guarantee', 'swipecommerce-pro')); ?></p>
    </div>
    
    <div class="swipecommerce-slider-container">
        <?php if ($atts['show_navigation']): ?>
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
        <?php endif; ?>
        
        <div class="swipecommerce-slider-track" id="<?php echo esc_attr($slider_id); ?>-track">
            <?php foreach ($slider_data as $section): ?>
            <!-- <?php echo esc_html($section['category']->name); ?> Category -->
            <div class="swipecommerce-category-section" data-category="<?php echo esc_attr($section['category']->slug); ?>">
                <?php if ($atts['show_category_headers']): ?>
                <div class="swipecommerce-category-header swipecommerce-category-<?php echo esc_attr($section['category']->slug); ?>">
                    <span><?php echo esc_html($section['category']->name); ?></span>
                    <span class="swipecommerce-category-meta">
                        <?php echo esc_html($section['category']->description ?: sprintf(__('%d products', 'swipecommerce-pro'), count($section['products']))); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="swipecommerce-products-row">
                    <?php foreach ($section['products'] as $product): ?>
                        <?php $this->render_product_card($product, $atts); ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Recently Viewed Bar -->
<div class="swipecommerce-recently-viewed" id="<?php echo esc_attr($slider_id); ?>-recent">
    <span class="swipecommerce-recently-viewed-title"><?php esc_html_e('Recently Viewed', 'swipecommerce-pro'); ?></span>
    <div class="swipecommerce-recently-viewed-items"></div>
</div>

<?php
// Render product card method
if (!function_exists('render_product_card')) {
    function render_product_card($product, $atts) {
        $tags = array();
        if (!empty($product['badges'])) {
            foreach ($product['badges'] as $badge) {
                $tags[] = $badge['type'];
            }
        }
        
        $price_numeric = is_numeric($product['sale_price']) ? $product['sale_price'] : $product['regular_price'];
        ?>
        <div class="swipecommerce-product-card" 
             data-price="<?php echo esc_attr($price_numeric); ?>" 
             data-tags="<?php echo esc_attr(implode(',', $tags)); ?>"
             data-product-id="<?php echo esc_attr($product['id']); ?>">
             
            <div class="swipecommerce-product-image">
                <?php if ($product['image']): ?>
                    <img src="<?php echo esc_url($product['image']); ?>" 
                         alt="<?php echo esc_attr($product['name']); ?>"
                         loading="lazy" />
                <?php else: ?>
                    <div class="swipecommerce-product-placeholder">📦</div>
                <?php endif; ?>
                
                <?php if (!empty($product['badges'])): ?>
                <div class="swipecommerce-product-badges">
                    <?php foreach ($product['badges'] as $badge): ?>
                        <span class="swipecommerce-product-badge <?php echo esc_attr($badge['class']); ?>">
                            <?php echo esc_html($badge['text']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($product['social_proof'])): ?>
                <div class="swipecommerce-social-proof">
                    <?php foreach ($product['social_proof'] as $proof): ?>
                        <span><?php echo esc_html($proof['icon'] . ' ' . $proof['text']); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="swipecommerce-product-info">
                <div class="swipecommerce-product-name">
                    <a href="<?php echo esc_url($product['permalink']); ?>" target="_blank">
                        <?php echo esc_html($product['name']); ?>
                    </a>
                </div>
                
                <?php if ($product['rating'] > 0): ?>
                <div class="swipecommerce-product-rating">
                    <span class="swipecommerce-stars">
                        <?php echo str_repeat('★', floor($product['rating'])); ?>
                        <?php echo str_repeat('☆', 5 - floor($product['rating'])); ?>
                    </span>
                    <span class="swipecommerce-rating-count">(<?php echo esc_html($product['review_count']); ?>)</span>
                </div>
                <?php endif; ?>
                
                <div class="swipecommerce-product-price">
                    <span class="swipecommerce-price-current"><?php echo wp_kses_post($product['price']); ?></span>
                </div>
                
                <div class="swipecommerce-quick-add">
                    <div class="swipecommerce-quantity-selector">
                        <button class="swipecommerce-qty-btn swipecommerce-minus" type="button">−</button>
                        <input type="number" class="swipecommerce-qty-input" value="1" min="1" max="10" readonly>
                        <button class="swipecommerce-qty-btn swipecommerce-plus" type="button">+</button>
                    </div>
                    <button class="swipecommerce-add-btn" 
                            data-product-id="<?php echo esc_attr($product['id']); ?>"
                            data-add-to-cart-url="<?php echo esc_url($product['add_to_cart_url']); ?>">
                        <?php esc_html_e('Add', 'swipecommerce-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}

// Call the function for each product
foreach ($section['products'] as $product) {
    render_product_card($product, $atts);
}
?>