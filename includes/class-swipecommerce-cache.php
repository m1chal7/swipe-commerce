<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Cache {

    private $cache_group = 'swipecommerce';
    private $cache_keys = array();

    public function __construct() {
        add_action('woocommerce_product_set_stock_status', array($this, 'invalidate_product_cache'));
        add_action('woocommerce_product_set_visibility', array($this, 'invalidate_product_cache'));
        add_action('save_post', array($this, 'invalidate_post_cache'));
        add_action('delete_post', array($this, 'invalidate_post_cache'));
    }

    public function get($key, $default = null) {
        $cache_key = $this->get_cache_key($key);
        
        if (function_exists('wp_cache_get')) {
            $cached = wp_cache_get($cache_key, $this->cache_group);
            if ($cached !== false) {
                return $cached;
            }
        }

        $cached = get_transient($cache_key);
        return ($cached !== false) ? $cached : $default;
    }

    public function set($key, $data, $expiration = 3600) {
        $cache_key = $this->get_cache_key($key);
        
        $this->cache_keys[] = $cache_key;
        
        if (function_exists('wp_cache_set')) {
            wp_cache_set($cache_key, $data, $this->cache_group, $expiration);
        }
        
        return set_transient($cache_key, $data, $expiration);
    }

    public function delete($key) {
        $cache_key = $this->get_cache_key($key);
        
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($cache_key, $this->cache_group);
        }
        
        return delete_transient($cache_key);
    }

    public function flush_cache() {
        global $wpdb;
        
        if (function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group($this->cache_group);
        }
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_swipecommerce_%' OR option_name LIKE '_transient_timeout_swipecommerce_%'");
        
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    public function get_cache_strategy() {
        return array(
            'object_cache' => array(
                'product_queries' => 3600,
                'slider_configs' => 86400,
                'user_preferences' => 604800
            ),
            'transient_cache' => array(
                'trending_products' => 1800,
                'sales_count' => 300,
                'viewing_now' => 60,
                'category_products' => 3600
            ),
            'fragment_cache' => array(
                'product_cards' => 3600,
                'filter_results' => 1800,
                'slider_html' => 7200
            )
        );
    }

    public function invalidate_product_cache($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) return;

        $cache_keys_to_clear = array(
            'product_' . $product_id,
            'product_data_' . $product_id,
        );

        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        foreach ($categories as $category_id) {
            $cache_keys_to_clear[] = 'category_products_' . $category_id;
        }

        if ($product->is_on_sale()) {
            $cache_keys_to_clear[] = 'sale_products';
        }

        if ($product->is_featured()) {
            $cache_keys_to_clear[] = 'featured_products';
        }

        $cache_keys_to_clear[] = 'recent_products';
        $cache_keys_to_clear[] = 'trending_products';
        $cache_keys_to_clear[] = 'bestsellers';

        foreach ($cache_keys_to_clear as $key) {
            $this->delete($key);
        }
    }

    public function invalidate_post_cache($post_id) {
        if (get_post_type($post_id) === 'product') {
            $this->invalidate_product_cache($post_id);
        }
    }

    private function get_cache_key($key) {
        return $this->cache_group . '_' . md5($key);
    }

    public function get_fragment_cache($key, $callback, $expiration = 3600) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        ob_start();
        $result = call_user_func($callback);
        $html = ob_get_clean();
        
        $this->set($key, $html, $expiration);
        
        return $html;
    }

    public function warm_cache() {
        $strategies = $this->get_cache_strategy();
        
        $woocommerce = new SwipeCommerce_WooCommerce();
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'number' => 10
        ));
        
        foreach ($categories as $category) {
            $args = array(
                'type' => 'recent',
                'category' => $category->slug,
                'limit' => 12,
                'cache_key' => 'category_products_' . $category->term_id
            );
            $woocommerce->get_products_for_slider($args);
        }
        
        $args = array(
            'type' => 'bestsellers',
            'limit' => 20,
            'cache_key' => 'bestsellers_warm'
        );
        $woocommerce->get_products_for_slider($args);
        
        $args = array(
            'type' => 'sale',
            'limit' => 20,
            'cache_key' => 'sale_products_warm'
        );
        $woocommerce->get_products_for_slider($args);
    }

    public function cleanup_expired_cache() {
        global $wpdb;
        
        $current_time = time();
        
        $expired_transients = $wpdb->get_results($wpdb->prepare("
            SELECT option_name
            FROM {$wpdb->options}
            WHERE option_name LIKE %s
            AND option_value < %d
        ", '_transient_timeout_swipecommerce_%', $current_time));
        
        foreach ($expired_transients as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient->option_name);
            delete_transient($key);
        }
    }

    public function get_cache_stats() {
        global $wpdb;
        
        $transient_count = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_swipecommerce_%'
        ");
        
        $cache_size = $wpdb->get_var("
            SELECT SUM(LENGTH(option_value))
            FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_swipecommerce_%'
        ");
        
        return array(
            'transient_count' => intval($transient_count),
            'cache_size' => intval($cache_size),
            'cache_size_formatted' => size_format($cache_size)
        );
    }

    public function preload_critical_data() {
        $critical_queries = array(
            'bestsellers' => array('type' => 'bestsellers', 'limit' => 8),
            'recent' => array('type' => 'recent', 'limit' => 8),
            'featured' => array('type' => 'featured', 'limit' => 8),
            'sale' => array('type' => 'sale', 'limit' => 8)
        );
        
        $woocommerce = new SwipeCommerce_WooCommerce();
        
        foreach ($critical_queries as $key => $args) {
            $args['cache_key'] = 'preload_' . $key;
            $woocommerce->get_products_for_slider($args);
        }
    }

    public function implement_cdn_integration() {
        $cdn_settings = get_option('swipecommerce_cdn', array());
        
        if (empty($cdn_settings['enabled'])) {
            return false;
        }
        
        add_filter('wp_get_attachment_url', function($url) use ($cdn_settings) {
            if (!empty($cdn_settings['url'])) {
                $upload_dir = wp_upload_dir();
                $url = str_replace($upload_dir['baseurl'], $cdn_settings['url'], $url);
            }
            return $url;
        });
        
        return true;
    }

    public function get_cache_headers() {
        $max_age = 3600; // 1 hour default
        
        if (is_product()) {
            $max_age = 1800; // 30 minutes for product pages
        } elseif (is_shop() || is_product_category()) {
            $max_age = 900; // 15 minutes for shop pages
        }
        
        return array(
            'Cache-Control' => 'public, max-age=' . $max_age,
            'Expires' => gmdate('D, d M Y H:i:s', time() + $max_age) . ' GMT',
            'Vary' => 'Accept-Encoding,User-Agent'
        );
    }
}