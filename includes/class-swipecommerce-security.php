<?php

if (!defined('ABSPATH')) {
    exit;
}

class SwipeCommerce_Security {

    private $rate_limit_threshold = 100;
    private $rate_limit_window = 3600;

    public function __construct() {
        add_action('init', array($this, 'init_security_measures'));
    }

    public function init_security_measures() {
        add_filter('swipecommerce_user_input', array($this, 'sanitize_input'), 10, 2);
        add_filter('swipecommerce_query_args', array($this, 'validate_query_args'));
        add_action('wp_ajax_swipecommerce_action', array($this, 'verify_nonce_wrapper'));
    }

    public function verify_ajax_request() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swipecommerce_nonce')) {
            wp_die(__('Security check failed', 'swipecommerce-pro'), 'Security Error', array('response' => 403));
        }

        $this->check_rate_limit();
        $this->validate_ajax_data();
    }

    public function verify_nonce_wrapper() {
        $this->verify_ajax_request();
    }

    private function check_rate_limit() {
        $ip = $this->get_client_ip();
        $key = 'swipecommerce_rate_' . md5($ip);
        
        $current_requests = get_transient($key);
        
        if ($current_requests === false) {
            $current_requests = 1;
            set_transient($key, $current_requests, $this->rate_limit_window);
        } else {
            $current_requests++;
            set_transient($key, $current_requests, $this->rate_limit_window);
            
            if ($current_requests > $this->rate_limit_threshold) {
                wp_die(__('Rate limit exceeded. Please try again later.', 'swipecommerce-pro'), 'Rate Limit', array('response' => 429));
            }
        }
    }

    private function validate_ajax_data() {
        $max_data_size = 1024 * 10; // 10KB
        $post_data_size = strlen(serialize($_POST));
        
        if ($post_data_size > $max_data_size) {
            wp_die(__('Request data too large', 'swipecommerce-pro'), 'Data Error', array('response' => 413));
        }

        foreach ($_POST as $key => $value) {
            if (is_string($value) && strlen($value) > 1000) {
                wp_die(__('Invalid request data', 'swipecommerce-pro'), 'Data Error', array('response' => 400));
            }
        }
    }

    public function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
            case 'url':
                return sanitize_url($input);
            case 'int':
                return intval($input);
            case 'float':
                return floatval($input);
            case 'html':
                return wp_kses_post($input);
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }

    public function validate_query_args($args) {
        $safe_args = array();
        
        $allowed_keys = array(
            'post_type', 'posts_per_page', 'meta_query', 'tax_query', 
            'orderby', 'order', 'meta_key', 'meta_value', 'include', 'exclude'
        );

        foreach ($args as $key => $value) {
            if (in_array($key, $allowed_keys)) {
                if (is_array($value)) {
                    $safe_args[$key] = array_map('sanitize_text_field', $value);
                } else {
                    $safe_args[$key] = sanitize_text_field($value);
                }
            }
        }

        return $safe_args;
    }

    public function escape_sql($query) {
        global $wpdb;
        return $wpdb->prepare($query);
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

    public function is_suspicious_request() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $suspicious_patterns = array(
            'bot', 'crawler', 'spider', 'scraper',
            '<script', 'javascript:', 'vbscript:',
            'union select', 'drop table', 'insert into'
        );

        foreach ($suspicious_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false || stripos($request_uri, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    public function log_security_event($event_type, $details = array()) {
        if (!get_option('swipecommerce_log_security', true)) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => sanitize_text_field($event_type),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'user_id' => get_current_user_id(),
            'details' => wp_json_encode($details)
        );

        error_log('SwipeCommerce Security Event: ' . wp_json_encode($log_entry));
    }

    public function validate_file_upload($file) {
        $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_type, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('File type not allowed', 'swipecommerce-pro'));
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            return new WP_Error('file_too_large', __('File size exceeds limit', 'swipecommerce-pro'));
        }

        return true;
    }

    public function hash_sensitive_data($data) {
        return wp_hash($data);
    }

    public function encrypt_data($data, $key = null) {
        if (!$key) {
            $key = get_option('swipecommerce_encrypt_key', wp_generate_password(32, false));
            update_option('swipecommerce_encrypt_key', $key);
        }

        return base64_encode($data . '|' . hash_hmac('sha256', $data, $key));
    }

    public function decrypt_data($encrypted_data, $key = null) {
        if (!$key) {
            $key = get_option('swipecommerce_encrypt_key');
            if (!$key) return false;
        }

        $data = base64_decode($encrypted_data);
        $parts = explode('|', $data, 2);
        
        if (count($parts) !== 2) return false;
        
        $original_data = $parts[0];
        $hash = $parts[1];
        
        if (hash_equals(hash_hmac('sha256', $original_data, $key), $hash)) {
            return $original_data;
        }
        
        return false;
    }
}