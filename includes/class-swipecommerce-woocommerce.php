<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_WooCommerce {

    public function __construct() {
        $this->init_hooks();
        $this->register_image_sizes();
    }

    private function init_hooks() {
        add_filter('woocommerce_product_data_tabs', array($this, 'add_slider_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'slider_data_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'save_slider_data'));
        
        add_action('woocommerce_before_shop_loop', array($this, 'maybe_replace_shop_grid'));
        add_action('woocommerce_after_single_product_summary', array($this, 'related_products_slider'), 15);
        
        add_action('woocommerce_before_cart', array($this, 'cart_recommendations_slider'));
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'update_cart_fragments'));
        
        add_action('wp_ajax_swipecommerce_quick_view', array($this, 'handle_quick_view'));
        add_action('wp_ajax_nopriv_swipecommerce_quick_view', array($this, 'handle_quick_view'));
        add_action('wp_ajax_swipecommerce_load_more', array($this, 'handle_load_more'));
        add_action('wp_ajax_nopriv_swipecommerce_load_more', array($this, 'handle_load_more'));
        add_action('wp_ajax_swipecommerce_filter_products', array($this, 'handle_filter'));
        add_action('wp_ajax_nopriv_swipecommerce_filter_products', array($this, 'handle_filter'));
        
        add_filter('woocommerce_enqueue_styles', array($this, 'dequeue_unnecessary_styles'));
        
        add_action('wp_head', array($this, 'add_schema_markup'));
    }

    public function register_image_sizes() {
        add_image_size('swipecommerce_slider', 200, 200, true);
        add_image_size('swipecommerce_slider_mobile', 160, 160, true);
    }

    public function add_slider_tab($tabs) {
        $tabs['swipecommerce'] = array(
            'label'    => __('SwipeCommerce', 'swipecommerce-pro'),
            'target'   => 'swipecommerce_product_data',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 80,
        );
        return $tabs;
    }

    public function slider_data_panel() {
        echo '<div id="swipecommerce_product_data" class="panel woocommerce_options_panel">';
        
        woocommerce_wp_checkbox(array(
            'id'          => '_swipecommerce_featured',
            'label'       => __('Featured in sliders', 'swipecommerce-pro'),
            'description' => __('Show this product prominently in sliders', 'swipecommerce-pro'),
        ));
        
        woocommerce_wp_select(array(
            'id'          => '_swipecommerce_priority',
            'label'       => __('Slider Priority', 'swipecommerce-pro'),
            'options'     => array(
                'normal' => __('Normal', 'swipecommerce-pro'),
                'high'   => __('High', 'swipecommerce-pro'),
                'low'    => __('Low', 'swipecommerce-pro'),
            ),
            'desc_tip'    => true,
            'description' => __('Higher priority products appear first in sliders', 'swipecommerce-pro'),
        ));
        
        echo '</div>';
    }

    public function save_slider_data($post_id) {
        $featured = isset($_POST['_swipecommerce_featured']) ? 'yes' : 'no';
        update_post_meta($post_id, '_swipecommerce_featured', $featured);
        
        $priority = isset($_POST['_swipecommerce_priority']) ? sanitize_text_field($_POST['_swipecommerce_priority']) : 'normal';
        update_post_meta($post_id, '_swipecommerce_priority', $priority);
    }

    public function get_products_for_slider($args = array()) {
        $defaults = array(
            'type' => 'recent',
            'limit' => 12,
            'category' => '',
            'exclude_out_of_stock' => true,
            'cache_key' => null,
            'cache_time' => HOUR_IN_SECONDS,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        if ($args['cache_key']) {
            $cached = get_transient('swipecommerce_' . $args['cache_key']);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        $query_args = $this->build_product_query($args);
        $products = wc_get_products($query_args);
        
        $products_data = array_map(array($this, 'format_product_data'), $products);
        
        if ($args['cache_key']) {
            set_transient('swipecommerce_' . $args['cache_key'], $products_data, $args['cache_time']);
        }
        
        return $products_data;
    }

    private function build_product_query($args) {
        $query_args = array(
            'status' => 'publish',
            'limit' => $args['limit'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
        );

        if ($args['exclude_out_of_stock']) {
            $query_args['stock_status'] = 'instock';
        }

        if (!empty($args['category'])) {
            $query_args['category'] = array($args['category']);
        }

        switch ($args['type']) {
            case 'featured':
                $query_args['featured'] = true;
                break;
            case 'sale':
                $query_args['include'] = wc_get_product_ids_on_sale();
                break;
            case 'bestsellers':
                $query_args['meta_key'] = 'total_sales';
                $query_args['orderby'] = 'meta_value_num';
                $query_args['order'] = 'DESC';
                break;
            case 'recent':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;
        }

        return apply_filters('swipecommerce_product_query_args', $query_args, $args);
    }

    public function format_product_data($product) {
        if (!is_a($product, 'WC_Product')) {
            return false;
        }

        $data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'swipecommerce_slider'),
            'rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'badges' => $this->get_product_badges($product),
            'social_proof' => $this->get_social_proof($product),
            'permalink' => $product->get_permalink(),
            'add_to_cart_url' => $product->add_to_cart_url(),
            'ajax_add_to_cart' => $product->supports('ajax_add_to_cart'),
            'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')),
            'tags' => wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names')),
        );

        return apply_filters('swipecommerce_product_data', $data, $product);
    }

    private function get_product_badges($product) {
        $badges = array();

        if ($product->is_on_sale()) {
            $regular_price = (float) $product->get_regular_price();
            $sale_price = (float) $product->get_sale_price();
            if ($regular_price > 0) {
                $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
                $badges[] = array(
                    'type' => 'sale',
                    'text' => '-' . $discount . '%',
                    'class' => 'badge-sale'
                );
            }
        }

        if ($product->is_featured()) {
            $badges[] = array(
                'type' => 'featured',
                'text' => __('Featured', 'swipecommerce-pro'),
                'class' => 'badge-featured'
            );
        }

        $created_date = $product->get_date_created();
        if ($created_date && $created_date->getTimestamp() > strtotime('-30 days')) {
            $badges[] = array(
                'type' => 'new',
                'text' => __('New', 'swipecommerce-pro'),
                'class' => 'badge-new'
            );
        }

        $stock_quantity = $product->get_stock_quantity();
        if ($stock_quantity && $stock_quantity <= 5) {
            $badges[] = array(
                'type' => 'low_stock',
                'text' => sprintf(__('Only %d left!', 'swipecommerce-pro'), $stock_quantity),
                'class' => 'badge-low-stock'
            );
        }

        return apply_filters('swipecommerce_product_badges', $badges, $product);
    }

    private function get_social_proof($product) {
        $proof = array();

        $recent_sales = $this->get_recent_sales_count($product->get_id(), 24);
        if ($recent_sales > 5) {
            $proof[] = array(
                'type' => 'sales',
                'text' => sprintf(__('%d sold today', 'swipecommerce-pro'), $recent_sales),
                'icon' => '🔥'
            );
        }

        $viewing = $this->get_current_viewers($product->get_id());
        if ($viewing > 3) {
            $proof[] = array(
                'type' => 'viewing',
                'text' => sprintf(__('%d viewing now', 'swipecommerce-pro'), $viewing),
                'icon' => '👥'
            );
        }

        return apply_filters('swipecommerce_social_proof', $proof, $product);
    }

    private function get_recent_sales_count($product_id, $hours = 24) {
        global $wpdb;

        $cache_key = 'swipecommerce_sales_' . $product_id . '_' . $hours;
        $sales = get_transient($cache_key);

        if ($sales === false) {
            $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
            
            $sales = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(oim.meta_value)
                FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
                INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
                INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
                WHERE oim.meta_key = '_product_id'
                AND oim.meta_value = %d
                AND p.post_status IN ('wc-completed', 'wc-processing')
                AND p.post_date >= %s
            ", $product_id, $since));

            $sales = intval($sales);
            set_transient($cache_key, $sales, 300);
        }

        return $sales;
    }

    private function get_current_viewers($product_id) {
        $cache_key = 'swipecommerce_viewers_' . $product_id;
        $viewers = get_transient($cache_key);

        if ($viewers === false) {
            $viewers = rand(1, 8);
            set_transient($cache_key, $viewers, 60);
        }

        return $viewers;
    }

    public function handle_quick_view() {
        check_ajax_referer('swipecommerce_nonce', 'nonce');

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error(__('Product not found.', 'swipecommerce-pro'));
        }

        ob_start();
        wc_get_template('single-product/product-image.php');
        $image = ob_get_clean();

        ob_start();
        wc_get_template('single-product/short-description.php');
        $description = ob_get_clean();

        wp_send_json_success(array(
            'title' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => $image,
            'description' => $description,
            'add_to_cart_url' => $product->add_to_cart_url(),
            'permalink' => $product->get_permalink()
        ));
    }

    public function maybe_replace_shop_grid() {
        $replace_shop = get_option('swipecommerce_replace_shop', false);
        
        if ($replace_shop && !is_search()) {
            remove_action('woocommerce_output_content_wrapper', 'woocommerce_output_content_wrapper', 10);
            add_action('woocommerce_output_content_wrapper', array($this, 'output_slider_shop'), 10);
        }
    }

    public function related_products_slider() {
        global $product;
        
        if (!$product || !get_option('swipecommerce_show_related_slider', true)) {
            return;
        }

        $related_products = array_filter(array_map('wc_get_product', wc_get_related_products($product->get_id(), 8)), 'wc_products_array_filter_visible');

        if (empty($related_products)) {
            return;
        }

        $products_data = array_map(array($this, 'format_product_data'), $related_products);

        wc_get_template('single-product/related-slider.php', array(
            'products' => $products_data,
            'title' => __('You might also like', 'swipecommerce-pro')
        ), 'swipecommerce/', SWIPECOMMERCE_ABSPATH . 'public/templates/');
    }

    public function dequeue_unnecessary_styles($styles) {
        if (is_page() || is_product()) {
            unset($styles['woocommerce-layout']);
            unset($styles['woocommerce-smallscreen']);
        }
        return $styles;
    }

    public function add_schema_markup() {
        if (is_product()) {
            global $product;
            if ($product) {
                $schema = array(
                    '@context' => 'https://schema.org/',
                    '@type' => 'Product',
                    'name' => $product->get_name(),
                    'description' => $product->get_short_description(),
                    'sku' => $product->get_sku(),
                    'offers' => array(
                        '@type' => 'Offer',
                        'price' => $product->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                    )
                );

                echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
            }
        }
    }

    public function update_cart_fragments($fragments) {
        $fragments['span.swipecommerce-cart-count'] = '<span class="swipecommerce-cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';
        return $fragments;
    }
}