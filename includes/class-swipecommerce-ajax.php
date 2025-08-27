<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Ajax {

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('wp_ajax_swipecommerce_track_event', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_swipecommerce_track_event', array($this, 'track_event'));
        
        add_action('wp_ajax_swipecommerce_get_products', array($this, 'get_products'));
        add_action('wp_ajax_nopriv_swipecommerce_get_products', array($this, 'get_products'));
        
        add_action('wp_ajax_swipecommerce_quick_view', array($this, 'quick_view'));
        add_action('wp_ajax_nopriv_swipecommerce_quick_view', array($this, 'quick_view'));
        
        add_action('wp_ajax_swipecommerce_load_more', array($this, 'load_more'));
        add_action('wp_ajax_nopriv_swipecommerce_load_more', array($this, 'load_more'));
    }

    public function track_event() {
        $security = new SwipeCommerce_Security();
        $security->verify_ajax_request();

        global $wpdb;

        $event_type = sanitize_text_field($_POST['event_type']);
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;
        $slider_id = isset($_POST['slider_id']) ? intval($_POST['slider_id']) : null;

        $event_data = array(
            'slider_id' => $slider_id,
            'product_id' => $product_id,
            'event_type' => $event_type,
            'user_id' => get_current_user_id() ?: null,
            'session_id' => $this->get_session_id(),
            'metadata' => wp_json_encode(array(
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'ip_address' => $this->get_client_ip(),
                'referrer' => wp_get_referer(),
                'timestamp' => current_time('mysql'),
                'page_url' => sanitize_url($_POST['page_url'] ?? ''),
                'device_type' => wp_is_mobile() ? 'mobile' : 'desktop'
            ))
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'swipecommerce_analytics',
            $event_data
        );

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Event tracked successfully', 'swipecommerce-pro')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to track event', 'swipecommerce-pro')
            ));
        }
    }

    public function get_products() {
        $security = new SwipeCommerce_Security();
        $security->verify_ajax_request();

        $args = array(
            'type' => sanitize_text_field($_POST['type'] ?? 'recent'),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'limit' => intval($_POST['limit'] ?? 12),
            'orderby' => sanitize_text_field($_POST['orderby'] ?? 'date'),
            'order' => sanitize_text_field($_POST['order'] ?? 'DESC'),
            'filters' => isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array()
        );

        $woocommerce = new SwipeCommerce_WooCommerce();
        $products = $woocommerce->get_products_for_slider($args);

        if (!empty($products)) {
            wp_send_json_success(array(
                'products' => $products,
                'total' => count($products)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No products found', 'swipecommerce-pro')
            ));
        }
    }

    public function quick_view() {
        $security = new SwipeCommerce_Security();
        $security->verify_ajax_request();

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
            'permalink' => $product->get_permalink(),
            'rating' => $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'stock_status' => $product->get_stock_status()
        ));
    }

    public function load_more() {
        $security = new SwipeCommerce_Security();
        $security->verify_ajax_request();

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 12);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', $_POST['filters']) : array();

        $args = array(
            'category' => $category,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'filters' => $filters
        );

        $woocommerce = new SwipeCommerce_WooCommerce();
        $products = $woocommerce->get_products_for_slider($args);

        if (!empty($products)) {
            ob_start();
            foreach ($products as $product) {
                include SWIPECOMMERCE_ABSPATH . 'public/templates/product-card.php';
            }
            $html = ob_get_clean();

            wp_send_json_success(array(
                'html' => $html,
                'products' => $products,
                'has_more' => count($products) === $per_page
            ));
        } else {
            wp_send_json_success(array(
                'html' => '',
                'products' => array(),
                'has_more' => false
            ));
        }
    }

    private function get_session_id() {
        if (session_id()) {
            return session_id();
        }

        if (!empty($_COOKIE['swipecommerce_session'])) {
            return sanitize_text_field($_COOKIE['swipecommerce_session']);
        }

        $session_id = uniqid('swipe_', true);
        setcookie('swipecommerce_session', $session_id, time() + (30 * 24 * 60 * 60), '/');
        return $session_id;
    }

    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}