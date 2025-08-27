<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Core {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('swipecommerce_cleanup_analytics', array($this, 'cleanup_analytics'));
        add_action('swipecommerce_cache_cleanup', array($this, 'cleanup_cache'));
    }

    public function init() {
        $this->load_dependencies();
        $this->setup_shortcodes();
        $this->setup_ajax_handlers();
    }

    private function load_dependencies() {
        require_once SWIPECOMMERCE_ABSPATH . 'public/class-swipecommerce-shortcode.php';
        require_once SWIPECOMMERCE_ABSPATH . 'includes/class-swipecommerce-ajax.php';
        require_once SWIPECOMMERCE_ABSPATH . 'includes/class-swipecommerce-cache.php';
        require_once SWIPECOMMERCE_ABSPATH . 'includes/class-swipecommerce-security.php';
    }

    private function setup_shortcodes() {
        $shortcode = new SwipeCommerce_Shortcode();
        add_shortcode('swipecommerce_slider', array($shortcode, 'render'));
    }

    private function setup_ajax_handlers() {
        $ajax = new SwipeCommerce_Ajax();
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . 'public/assets/css/swipecommerce-public.css',
            array(),
            SWIPECOMMERCE_VERSION,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'swipecommerce-public',
            SWIPECOMMERCE_PLUGIN_URL . 'public/assets/js/swipecommerce-public.js',
            array('jquery'),
            SWIPECOMMERCE_VERSION,
            true
        );

        wp_localize_script('swipecommerce-public', 'swipecommerce_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('swipecommerce_nonce'),
            'loading_text' => __('Loading...', 'swipecommerce-pro'),
            'error_text' => __('Something went wrong. Please try again.', 'swipecommerce-pro'),
        ));
    }

    public function cleanup_analytics() {
        global $wpdb;

        $old_date = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        $wpdb->delete(
            $wpdb->prefix . 'swipecommerce_analytics',
            array('created_at' => array('operator' => '<', 'value' => $old_date)),
            array('%s')
        );
    }

    public function cleanup_cache() {
        $cache = new SwipeCommerce_Cache();
        $cache->cleanup_expired_cache();
    }
}