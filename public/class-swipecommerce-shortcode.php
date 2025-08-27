<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Shortcode {

    public function render($atts, $content = null) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'type' => 'recent',
            'category' => '',
            'tags' => '',
            'featured' => false,
            'sale' => false,
            'limit' => 12,
            'columns' => 4,
            'rows' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'show_filters' => true,
            'show_navigation' => true,
            'autoplay' => false,
            'speed' => 3000,
            'theme' => 'default',
            'class' => '',
            'categories' => '',
            'show_category_headers' => true,
            'mobile_columns' => 2
        ), $atts, 'swipecommerce_slider');

        if (!class_exists('WooCommerce')) {
            return '<div class="swipecommerce-error">' . __('WooCommerce is required for SwipeCommerce slider.', 'swipecommerce-pro') . '</div>';
        }

        if ($atts['id']) {
            $slider_config = $this->get_slider_config($atts['id']);
            if ($slider_config) {
                $atts = array_merge($atts, $slider_config);
            }
        }

        $this->enqueue_slider_assets($atts['theme']);

        if (!empty($atts['categories'])) {
            return $this->render_category_slider($atts);
        } else {
            return $this->render_single_slider($atts);
        }
    }

    private function render_category_slider($atts) {
        $categories = array_map('trim', explode(',', $atts['categories']));
        $slider_data = array();

        foreach ($categories as $category_slug) {
            $category = get_term_by('slug', $category_slug, 'product_cat');
            if (!$category) continue;

            $args = array(
                'type' => $atts['type'],
                'category' => $category_slug,
                'limit' => $atts['limit'],
                'orderby' => $atts['orderby'],
                'order' => $atts['order'],
                'cache_key' => 'slider_' . $category_slug . '_' . md5(serialize($atts))
            );

            $woocommerce = new SwipeCommerce_WooCommerce();
            $products = $woocommerce->get_products_for_slider($args);

            if (!empty($products)) {
                $slider_data[] = array(
                    'category' => $category,
                    'products' => $products
                );
            }
        }

        ob_start();
        $this->render_template('category-slider', array(
            'slider_data' => $slider_data,
            'atts' => $atts
        ));
        return ob_get_clean();
    }

    private function render_single_slider($atts) {
        $args = array(
            'type' => $atts['type'],
            'category' => $atts['category'],
            'limit' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'cache_key' => 'slider_single_' . md5(serialize($atts))
        );

        $woocommerce = new SwipeCommerce_WooCommerce();
        $products = $woocommerce->get_products_for_slider($args);

        if (empty($products)) {
            return '<div class="swipecommerce-no-products">' . __('No products found.', 'swipecommerce-pro') . '</div>';
        }

        ob_start();
        $this->render_template('single-slider', array(
            'products' => $products,
            'atts' => $atts
        ));
        return ob_get_clean();
    }

    private function get_slider_config($slider_id) {
        global $wpdb;
        
        $slider = $wpdb->get_row($wpdb->prepare(
            "SELECT config FROM {$wpdb->prefix}swipecommerce_sliders WHERE id = %d AND status = 'active'",
            $slider_id
        ));
        
        if ($slider && $slider->config) {
            return json_decode($slider->config, true);
        }
        
        return false;
    }

    private function enqueue_slider_assets($theme = 'default') {
        static $enqueued = false;
        
        if (!$enqueued) {
            wp_enqueue_style('swipecommerce-slider');
            wp_enqueue_script('swipecommerce-slider');
            $enqueued = true;
        }

        if ($theme !== 'default') {
            wp_enqueue_style(
                'swipecommerce-theme-' . $theme,
                SWIPECOMMERCE_PLUGIN_URL . "public/assets/css/themes/{$theme}.css",
                array('swipecommerce-slider'),
                SWIPECOMMERCE_VERSION
            );
        }
    }

    private function render_template($template, $args = array()) {
        extract($args);
        
        $template_path = SWIPECOMMERCE_ABSPATH . "public/templates/{$template}.php";
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="swipecommerce-error">Template not found: ' . esc_html($template) . '</div>';
        }
    }

    public function get_filter_options() {
        return array(
            'sale' => __('On Sale', 'swipecommerce-pro'),
            'new' => __('New', 'swipecommerce-pro'),
            'under30' => __('Under $30', 'swipecommerce-pro'),
            'bestseller' => __('Top Rated', 'swipecommerce-pro'),
            'featured' => __('Featured', 'swipecommerce-pro'),
            'in_stock' => __('In Stock', 'swipecommerce-pro')
        );
    }

    public function get_available_categories() {
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0
        ));

        $category_options = array();
        foreach ($categories as $category) {
            $category_options[$category->slug] = $category->name;
        }

        return $category_options;
    }
}