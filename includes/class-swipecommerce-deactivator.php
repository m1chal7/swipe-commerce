<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Deactivator {

    public static function deactivate() {
        self::clear_scheduled_events();
        self::clear_cache();
        
        flush_rewrite_rules();
    }

    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('swipecommerce_cleanup_analytics');
        wp_clear_scheduled_hook('swipecommerce_cache_cleanup');
    }

    private static function clear_cache() {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'swipecommerce_%_cache%'");
        
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        delete_transient('swipecommerce_trending_products');
        delete_transient('swipecommerce_sales_count');
        delete_transient('swipecommerce_viewing_now');
    }
}