<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_I18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'swipecommerce-pro',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}