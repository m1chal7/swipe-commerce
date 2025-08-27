<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Activator {

    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_sample_sliders();
        self::schedule_events();
        
        update_option('swipecommerce_version', SWIPECOMMERCE_VERSION);
        
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}swipecommerce_sliders (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            config longtext,
            status enum('active', 'inactive', 'draft') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            modified_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            author_id bigint(20) unsigned,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_author (author_id)
        ) $charset_collate;";

        $sql .= "CREATE TABLE {$wpdb->prefix}swipecommerce_analytics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slider_id int(11),
            product_id bigint(20) unsigned,
            event_type enum('view', 'click', 'add_cart', 'quick_view', 'filter') NOT NULL,
            user_id bigint(20) unsigned NULL,
            session_id varchar(64),
            metadata longtext,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_slider (slider_id),
            KEY idx_product (product_id),
            KEY idx_date (created_at),
            KEY idx_event (event_type)
        ) $charset_collate;";

        $sql .= "CREATE TABLE {$wpdb->prefix}swipecommerce_experiments (
            id int(11) NOT NULL AUTO_INCREMENT,
            slider_id int(11),
            variant_name varchar(100),
            variant_config longtext,
            impressions int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            status enum('running', 'paused', 'completed') DEFAULT 'running',
            winner tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_slider (slider_id),
            KEY idx_status (status)
        ) $charset_collate;";

        $sql .= "CREATE TABLE {$wpdb->prefix}swipecommerce_user_prefs (
            user_id bigint(20) unsigned NOT NULL,
            viewed_products longtext,
            category_preferences longtext,
            filter_history longtext,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function set_default_options() {
        $defaults = array(
            'swipecommerce_general' => array(
                'products_per_view' => 4,
                'rows' => 1,
                'gap' => 20,
                'autoplay' => false,
                'autoplay_speed' => 3000,
                'mobile_products_per_view' => 2,
                'enable_swipe' => true,
                'show_pagination' => true
            ),
            'swipecommerce_filters' => array(
                'show_price_filter' => true,
                'show_category_pills' => true,
                'show_sort_options' => true,
                'sticky_navigation' => true
            ),
            'swipecommerce_conversion' => array(
                'show_sales_count' => true,
                'show_viewing_now' => true,
                'show_stock_status' => true,
                'urgency_threshold' => 5,
                'enable_quick_add' => true,
                'enable_quick_view' => true,
                'show_quantity_selector' => true
            ),
            'swipecommerce_analytics' => array(
                'track_impressions' => true,
                'track_clicks' => true,
                'track_conversions' => true,
                'google_analytics_integration' => false
            )
        );

        foreach ($defaults as $option_name => $option_value) {
            if (!get_option($option_name)) {
                update_option($option_name, $option_value);
            }
        }
    }

    private static function create_sample_sliders() {
        global $wpdb;

        $sample_config = json_encode(array(
            'type' => 'recent',
            'category' => '',
            'limit' => 12,
            'show_filters' => true,
            'theme' => 'default'
        ));

        $wpdb->insert(
            $wpdb->prefix . 'swipecommerce_sliders',
            array(
                'name' => 'Default Product Slider',
                'config' => $sample_config,
                'status' => 'active',
                'author_id' => get_current_user_id()
            )
        );
    }

    private static function schedule_events() {
        if (!wp_next_scheduled('swipecommerce_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'swipecommerce_cleanup_analytics');
        }

        if (!wp_next_scheduled('swipecommerce_cache_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'swipecommerce_cache_cleanup');
        }
    }
}