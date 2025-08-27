<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Public {

    private $loader;

    public function __construct() {
        $this->loader = new SwipeCommerce_Loader();
        $this->define_hooks();
    }

    private function define_hooks() {
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_scripts');
        $this->loader->add_action('wp_head', $this, 'add_inline_styles');
        $this->loader->add_action('wp_footer', $this, 'add_structured_data');
        
        $this->loader->add_filter('body_class', $this, 'add_body_classes');
        $this->loader->add_filter('post_class', $this, 'add_post_classes');
        
        // Widget and shortcode support
        $this->loader->add_action('widgets_init', $this, 'register_widgets');
        
        // Theme integration hooks
        $this->loader->add_action('wp', $this, 'maybe_integrate_with_theme');
        
        // Performance hooks
        $this->loader->add_action('template_redirect', $this, 'optimize_loading');
        
        $this->loader->run();
    }

    public function enqueue_styles() {
        $min_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        
        wp_enqueue_style(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . "public/assets/css/swipecommerce-public{$min_suffix}.css",
            array(),
            SWIPECOMMERCE_VERSION,
            'all'
        );

        $custom_css = get_option('swipecommerce_custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style('swipecommerce-public', $custom_css);
        }
    }

    public function enqueue_scripts() {
        $min_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        
        wp_enqueue_script(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . "public/assets/js/swipecommerce-public{$min_suffix}.js",
            array('jquery'),
            SWIPECOMMERCE_VERSION,
            true
        );

        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swipecommerce_nonce'),
            'loading_text' => __('Loading...', 'swipecommerce-pro'),
            'error_text' => __('Something went wrong. Please try again.', 'swipecommerce-pro'),
            'added_to_cart_text' => __('Added to cart!', 'swipecommerce-pro'),
            'view_cart_text' => __('View Cart', 'swipecommerce-pro'),
            'continue_shopping_text' => __('Continue Shopping', 'swipecommerce-pro'),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'currency_position' => get_option('woocommerce_currency_pos'),
            'thousand_separator' => wc_get_price_thousand_separator(),
            'decimal_separator' => wc_get_price_decimal_separator(),
            'price_decimals' => wc_get_price_decimals(),
            'settings' => array(
                'animation_speed' => get_option('swipecommerce_animation_speed', 300),
                'autoplay_speed' => get_option('swipecommerce_autoplay_speed', 3000),
                'lazy_loading' => get_option('swipecommerce_lazy_loading', true),
                'track_analytics' => get_option('swipecommerce_track_analytics', true)
            )
        );

        wp_localize_script('swipecommerce-public', 'swipecommerce_ajax', $localize_data);

        // Conditionally enqueue additional scripts
        if (get_option('swipecommerce_enable_quick_view', true)) {
            wp_enqueue_script('wc-single-product');
        }

        if (get_option('swipecommerce_enable_zoom', false)) {
            wp_enqueue_script('zoom');
        }
    }

    public function add_inline_styles() {
        $settings = get_option('swipecommerce_general', array());
        $conversion = get_option('swipecommerce_conversion', array());
        
        $custom_properties = array();
        
        if (!empty($settings['primary_color'])) {
            $custom_properties[] = '--swipecommerce-primary-color: ' . sanitize_hex_color($settings['primary_color']);
        }
        
        if (!empty($settings['secondary_color'])) {
            $custom_properties[] = '--swipecommerce-secondary-color: ' . sanitize_hex_color($settings['secondary_color']);
        }
        
        if (!empty($settings['border_radius'])) {
            $custom_properties[] = '--swipecommerce-border-radius: ' . intval($settings['border_radius']) . 'px';
        }
        
        if (!empty($settings['gap'])) {
            $custom_properties[] = '--swipecommerce-gap: ' . intval($settings['gap']) . 'px';
        }

        if (!empty($custom_properties)) {
            echo '<style id="swipecommerce-custom-properties">';
            echo ':root { ' . implode('; ', $custom_properties) . '; }';
            echo '</style>';
        }
    }

    public function add_body_classes($classes) {
        if ($this->has_swipecommerce_content()) {
            $classes[] = 'has-swipecommerce-slider';
            
            if (wp_is_mobile()) {
                $classes[] = 'swipecommerce-mobile';
            }
            
            $theme = get_option('swipecommerce_theme', 'default');
            $classes[] = 'swipecommerce-theme-' . sanitize_html_class($theme);
        }
        
        return $classes;
    }

    public function add_post_classes($classes) {
        global $post;
        
        if ($post && has_shortcode($post->post_content, 'swipecommerce_slider')) {
            $classes[] = 'has-swipecommerce-slider';
        }
        
        return $classes;
    }

    public function register_widgets() {
        require_once SWIPECOMMERCE_ABSPATH . 'public/class-swipecommerce-widget.php';
        register_widget('SwipeCommerce_Widget');
    }

    public function maybe_integrate_with_theme() {
        $integration = get_option('swipecommerce_theme_integration', array());
        
        if (!empty($integration['replace_shop_loop'])) {
            add_action('woocommerce_before_shop_loop', array($this, 'replace_shop_loop'), 5);
        }
        
        if (!empty($integration['add_to_single_product'])) {
            add_action('woocommerce_after_single_product_summary', array($this, 'add_related_slider'), 15);
        }
        
        if (!empty($integration['add_to_cart_page'])) {
            add_action('woocommerce_cart_collaterals', array($this, 'add_cart_recommendations'), 5);
        }
    }

    public function replace_shop_loop() {
        if (is_shop() || is_product_category()) {
            $category = '';
            if (is_product_category()) {
                $category = get_queried_object()->slug;
            }
            
            echo do_shortcode("[swipecommerce_slider type='recent' category='{$category}' show_filters='true']");
            
            // Hide the default product loop
            remove_action('woocommerce_shop_loop', 'woocommerce_output_content_wrapper_end', 10);
        }
    }

    public function add_related_slider() {
        global $product;
        
        if (!$product) return;
        
        $related_ids = wc_get_related_products($product->get_id(), 8);
        if (empty($related_ids)) return;
        
        echo '<div class="swipecommerce-related-products">';
        echo '<h2>' . esc_html__('You might also like', 'swipecommerce-pro') . '</h2>';
        echo do_shortcode('[swipecommerce_slider type="related" limit="8" show_navigation="true"]');
        echo '</div>';
    }

    public function add_cart_recommendations() {
        if (!WC()->cart->is_empty()) {
            echo '<div class="swipecommerce-cart-recommendations">';
            echo '<h2>' . esc_html__('Frequently bought together', 'swipecommerce-pro') . '</h2>';
            echo do_shortcode('[swipecommerce_slider type="bestsellers" limit="6" show_filters="false"]');
            echo '</div>';
        }
    }

    public function optimize_loading() {
        if (!$this->has_swipecommerce_content()) {
            return;
        }

        // Preload critical resources
        $this->preload_critical_assets();
        
        // Add resource hints
        add_action('wp_head', array($this, 'add_resource_hints'), 2);
        
        // Optimize images
        add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'), 10, 3);
    }

    private function preload_critical_assets() {
        echo '<link rel="preload" href="' . SWIPECOMMERCE_PLUGIN_URL . 'public/assets/css/swipecommerce-public.css" as="style">';
        echo '<link rel="preload" href="' . SWIPECOMMERCE_PLUGIN_URL . 'public/assets/js/swipecommerce-public.js" as="script">';
    }

    public function add_resource_hints() {
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
        
        // Preconnect to image CDN if configured
        $cdn_url = get_option('swipecommerce_cdn_url');
        if ($cdn_url) {
            echo '<link rel="preconnect" href="' . esc_url($cdn_url) . '">';
        }
    }

    public function add_lazy_loading($attr, $attachment, $size) {
        if (!empty($attr['class']) && strpos($attr['class'], 'swipecommerce') !== false) {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }
        return $attr;
    }

    public function add_structured_data() {
        if (!is_singular() || !$this->has_swipecommerce_content()) {
            return;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => get_the_title(),
            'description' => get_the_excerpt(),
            'url' => get_permalink(),
            'mainEntity' => array(
                '@type' => 'ItemList',
                'name' => 'Products',
                'numberOfItems' => $this->get_products_count_on_page()
            )
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }

    private function has_swipecommerce_content() {
        global $post;
        
        if (!$post) return false;
        
        return has_shortcode($post->post_content, 'swipecommerce_slider') || 
               get_post_meta($post->ID, '_has_swipecommerce_slider', true) ||
               is_active_widget(false, false, 'swipecommerce_widget');
    }

    private function get_products_count_on_page() {
        global $post;
        
        if (!$post) return 0;
        
        $count = 0;
        $pattern = get_shortcode_regex(array('swipecommerce_slider'));
        
        if (preg_match_all('/' . $pattern . '/s', $post->post_content, $matches)) {
            foreach ($matches[3] as $atts_string) {
                $atts = shortcode_parse_atts($atts_string);
                $limit = isset($atts['limit']) ? intval($atts['limit']) : 12;
                $count += $limit;
            }
        }
        
        return $count;
    }

    public function handle_ajax_add_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'swipecommerce_nonce')) {
            wp_send_json_error('Security check failed');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $variation_id = intval($_POST['variation_id'] ?? 0);

        $result = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);

        if ($result) {
            wp_send_json_success(array(
                'cart_count' => WC()->cart->get_cart_contents_count(),
                'cart_total' => WC()->cart->get_cart_total(),
                'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array())
            ));
        } else {
            wp_send_json_error('Failed to add product to cart');
        }
    }
}