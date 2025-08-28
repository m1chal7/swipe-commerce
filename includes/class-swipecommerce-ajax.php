<?php
/**
 * The AJAX handlers class.
 *
 * @since      1.0.7
 * @package    SwipeCommerce_Pro
 * @subpackage SwipeCommerce_Pro/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The AJAX handlers class.
 *
 * Handles all AJAX requests for both frontend and admin functionality.
 */
class SwipeCommerce_Ajax {

    /**
     * The categories manager instance.
     *
     * @since    1.0.7
     * @var      SwipeCommerce_Categories
     */
    private $categories;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.7
     * @param    SwipeCommerce_Categories    $categories    The categories manager instance.
     */
    public function __construct($categories) {
        $this->categories = $categories;
        $this->init();
    }

    /**
     * Initialize AJAX hooks.
     *
     * @since    1.0.7
     */
    private function init() {
        // Frontend AJAX handlers
        add_action('wp_ajax_swipecommerce_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_swipecommerce_add_to_cart', array($this, 'ajax_add_to_cart'));

        // Admin AJAX handlers
        if (is_admin()) {
            add_action('wp_ajax_swipecommerce_save_category', array($this, 'ajax_save_category'));
            add_action('wp_ajax_swipecommerce_save_category_order', array($this, 'ajax_save_category_order'));
            add_action('wp_ajax_swipecommerce_delete_category', array($this, 'ajax_delete_category'));
            add_action('wp_ajax_swipecommerce_search_products', array($this, 'ajax_search_products'));
            add_action('wp_ajax_swipecommerce_bulk_category_action', array($this, 'ajax_bulk_category_action'));
            add_action('wp_ajax_swipecommerce_toggle_category_visibility', array($this, 'ajax_toggle_category_visibility'));
        }
    }

    /**
     * AJAX handler for adding products to cart.
     *
     * @since    1.0.7
     */
    public function ajax_add_to_cart() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'swipecommerce_nonce')) {
            wp_send_json_error(__('Security check failed', 'swipecommerce-pro'));
            return;
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(__('WooCommerce is not active', 'swipecommerce-pro'));
            return;
        }

        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if ($product_id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'swipecommerce-pro'));
            return;
        }

        try {
            $result = WC()->cart->add_to_cart($product_id, $quantity);

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Product added to cart', 'swipecommerce-pro'),
                    'cart_count' => WC()->cart->get_cart_contents_count()
                ));
            } else {
                wp_send_json_error(__('Failed to add product to cart', 'swipecommerce-pro'));
            }
        } catch (Exception $e) {
            error_log('SwipeCommerce add to cart error: ' . $e->getMessage());
            wp_send_json_error(__('Failed to add product to cart', 'swipecommerce-pro'));
        }
    }

    /**
     * AJAX handler for saving categories.
     *
     * @since    1.0.7
     */
    public function ajax_save_category() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }
        
        $category_data = array(
            'id' => sanitize_key($_POST['id'] ?? ''),
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'color_scheme' => sanitize_text_field($_POST['color_scheme'] ?? 'gradient-pink'),
            'icon' => wp_kses($_POST['icon'] ?? '🏆', array()),
            'order' => intval($_POST['order'] ?? 1),
            'products' => array_filter(array_map('intval', explode(',', $_POST['products'] ?? ''))),
            'visibility' => isset($_POST['visibility']) ? !empty($_POST['visibility']) : true
        );
        
        // Generate ID if new category
        if (empty($category_data['id'])) {
            $category_data['id'] = sanitize_key(strtolower(str_replace(' ', '-', $category_data['name'])));
        }

        // Validate the data
        $validation = $this->categories->validate_category_data($category_data);
        if (!$validation['valid']) {
            wp_send_json_error(array(
                'message' => __('Validation failed', 'swipecommerce-pro'),
                'errors' => $validation['errors']
            ));
            return;
        }
        
        $result = $this->categories->save_custom_category($category_data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Category saved successfully', 'swipecommerce-pro'),
                'category' => $category_data
            ));
        } else {
            wp_send_json_error(__('Failed to save category', 'swipecommerce-pro'));
        }
    }
    
    /**
     * AJAX handler for saving category order.
     *
     * @since    1.0.7
     */
    public function ajax_save_category_order() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }
        
        $order_data = json_decode(stripslashes($_POST['order'] ?? '[]'), true);
        
        if (!is_array($order_data)) {
            wp_send_json_error(__('Invalid order data', 'swipecommerce-pro'));
            return;
        }
        
        $result = $this->categories->update_categories_order($order_data);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Category order saved successfully', 'swipecommerce-pro')
            ));
        } else {
            wp_send_json_error(__('Failed to save category order', 'swipecommerce-pro'));
        }
    }
    
    /**
     * AJAX handler for deleting categories.
     *
     * @since    1.0.7
     */
    public function ajax_delete_category() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }
        
        $category_id = sanitize_key($_POST['category_id'] ?? '');
        
        if (empty($category_id)) {
            wp_send_json_error(__('Invalid category ID', 'swipecommerce-pro'));
            return;
        }
        
        $result = $this->categories->delete_custom_category($category_id);
        
        if ($result) {
            wp_send_json_success(__('Category deleted successfully', 'swipecommerce-pro'));
        } else {
            wp_send_json_error(__('Failed to delete category', 'swipecommerce-pro'));
        }
    }
    
    /**
     * AJAX handler for searching products.
     *
     * @since    1.0.7
     */
    public function ajax_search_products() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }
        
        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = 50; // Increased from 20
        
        try {
            $results = $this->categories->search_products($search_term, $page, $per_page);
            
            if (isset($results['error'])) {
                wp_send_json_error(__('Search failed: ', 'swipecommerce-pro') . $results['error']);
                return;
            }

            wp_send_json_success($results);
            
        } catch (Exception $e) {
            error_log('SwipeCommerce search error: ' . $e->getMessage());
            wp_send_json_error(__('Search failed: ', 'swipecommerce-pro') . $e->getMessage());
        }
    }

    /**
     * AJAX handler for bulk category actions.
     *
     * @since    1.0.7
     */
    public function ajax_bulk_category_action() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $category_ids = array_map('sanitize_key', $_POST['category_ids'] ?? array());

        if (empty($action) || empty($category_ids)) {
            wp_send_json_error(__('Invalid action or categories', 'swipecommerce-pro'));
            return;
        }

        $result = $this->categories->bulk_action_categories($action, $category_ids);

        if ($result) {
            $messages = array(
                'enable' => __('Categories enabled successfully', 'swipecommerce-pro'),
                'disable' => __('Categories disabled successfully', 'swipecommerce-pro'),
                'delete' => __('Categories deleted successfully', 'swipecommerce-pro')
            );

            $message = isset($messages[$action]) ? $messages[$action] : __('Bulk action completed', 'swipecommerce-pro');
            wp_send_json_success($message);
        } else {
            wp_send_json_error(__('Failed to perform bulk action', 'swipecommerce-pro'));
        }
    }

    /**
     * AJAX handler for toggling category visibility.
     *
     * @since    1.0.7
     */
    public function ajax_toggle_category_visibility() {
        // Security check
        check_ajax_referer('swipecommerce_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'swipecommerce-pro'));
            return;
        }

        $category_id = sanitize_key($_POST['category_id'] ?? '');
        $visible = !empty($_POST['visible']);

        if (empty($category_id)) {
            wp_send_json_error(__('Invalid category ID', 'swipecommerce-pro'));
            return;
        }

        $result = $this->categories->toggle_category_visibility($category_id, $visible);

        if ($result) {
            wp_send_json_success(array(
                'message' => $visible ? __('Category enabled', 'swipecommerce-pro') : __('Category disabled', 'swipecommerce-pro'),
                'visible' => $visible
            ));
        } else {
            wp_send_json_error(__('Failed to update category visibility', 'swipecommerce-pro'));
        }
    }

    /**
     * Validate admin AJAX request permissions.
     *
     * @since    1.0.7
     * @param    string    $nonce_action    The nonce action to verify.
     * @return   bool                       True if valid, false otherwise.
     */
    private function validate_admin_request($nonce_action = 'swipecommerce_admin_nonce') {
        // Check nonce
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            return false;
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            return false;
        }
        
        return true;
    }

    /**
     * Send standardized error response.
     *
     * @since    1.0.7
     * @param    string    $message    The error message.
     * @param    array     $data       Optional additional data.
     */
    private function send_error($message, $data = array()) {
        wp_send_json_error(array(
            'message' => $message,
            'data' => $data
        ));
    }

    /**
     * Send standardized success response.
     *
     * @since    1.0.7
     * @param    string    $message    The success message.
     * @param    array     $data       Optional additional data.
     */
    private function send_success($message, $data = array()) {
        wp_send_json_success(array(
            'message' => $message,
            'data' => $data
        ));
    }
}