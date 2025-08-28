<?php
/**
 * The custom categories management class.
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
 * The custom categories management class.
 *
 * Handles all custom category CRUD operations and data management.
 */
class SwipeCommerce_Categories {

    /**
     * Initialize the class.
     *
     * @since    1.0.7
     */
    public function __construct() {
        // Categories are loaded on-demand, no initialization needed
    }

    /**
     * Get all custom categories.
     *
     * @since    1.0.7
     * @param    bool    $visible_only    Whether to return only visible categories.
     * @return   array                    Array of category data.
     */
    public function get_custom_categories($visible_only = false) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        if ($visible_only) {
            $categories = array_filter($categories, function($cat) {
                return !empty($cat['visibility']);
            });
        }
        
        // Sort by order
        usort($categories, function($a, $b) {
            return ($a['order'] ?? 999) - ($b['order'] ?? 999);
        });
        
        return $categories;
    }

    /**
     * Get a single custom category by ID.
     *
     * @since    1.0.7
     * @param    string    $category_id    The category ID to retrieve.
     * @return   array|null                Category data or null if not found.
     */
    public function get_custom_category($category_id) {
        $categories = $this->get_custom_categories();
        
        foreach ($categories as $category) {
            if ($category['id'] === $category_id) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Save a custom category.
     *
     * @since    1.0.7
     * @param    array    $category_data    The category data to save.
     * @return   bool                       True on success, false on failure.
     */
    public function save_custom_category($category_data) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        // Sanitize data
        $category_data = array(
            'id' => sanitize_key($category_data['id']),
            'name' => sanitize_text_field($category_data['name']),
            'description' => sanitize_textarea_field($category_data['description']),
            'color_scheme' => sanitize_text_field($category_data['color_scheme']),
            'icon' => wp_kses($category_data['icon'], array()),
            'order' => intval($category_data['order']),
            'products' => array_map('intval', (array)$category_data['products']),
            'visibility' => !empty($category_data['visibility'])
        );
        
        // Find existing or add new
        $found = false;
        foreach ($categories as $index => $category) {
            if ($category['id'] === $category_data['id']) {
                $categories[$index] = $category_data;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $categories[] = $category_data;
        }
        
        return update_option('swipecommerce_custom_categories', $categories);
    }

    /**
     * Delete a custom category.
     *
     * @since    1.0.7
     * @param    string    $category_id    The category ID to delete.
     * @return   bool                      True on success, false on failure.
     */
    public function delete_custom_category($category_id) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        $categories = array_filter($categories, function($cat) use ($category_id) {
            return $cat['id'] !== $category_id;
        });
        
        return update_option('swipecommerce_custom_categories', array_values($categories));
    }

    /**
     * Get products for a specific custom category.
     *
     * @since    1.0.7
     * @param    string    $category_id    The category ID.
     * @param    int       $limit          Maximum number of products to retrieve.
     * @return   array                     Array of WC_Product objects.
     */
    public function get_products_by_custom_category($category_id, $limit = 12) {
        $category = $this->get_custom_category($category_id);
        
        if (!$category || empty($category['products'])) {
            return array();
        }
        
        $product_ids = array_slice($category['products'], 0, $limit);
        
        try {
            $args = array(
                'include' => $product_ids,
                'status' => 'publish',
                'limit' => $limit,
            );
            
            return wc_get_products($args);
        } catch (Exception $e) {
            error_log('SwipeCommerce get_products_by_custom_category error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Update the order of categories.
     *
     * @since    1.0.7
     * @param    array    $order_data    Array of category order data.
     * @return   bool                    True on success, false on failure.
     */
    public function update_categories_order($order_data) {
        if (!is_array($order_data)) {
            return false;
        }
        
        $categories = get_option('swipecommerce_custom_categories', array());
        
        // Update order for each category
        foreach ($order_data as $item) {
            $category_id = sanitize_key($item['id'] ?? '');
            $new_order = intval($item['order'] ?? 1);
            
            // Find and update the category
            for ($i = 0; $i < count($categories); $i++) {
                if ($categories[$i]['id'] === $category_id) {
                    $categories[$i]['order'] = $new_order;
                    break;
                }
            }
        }
        
        return update_option('swipecommerce_custom_categories', $categories);
    }

    /**
     * Toggle category visibility.
     *
     * @since    1.0.7
     * @param    string    $category_id    The category ID.
     * @param    bool      $visible        The visibility status.
     * @return   bool                      True on success, false on failure.
     */
    public function toggle_category_visibility($category_id, $visible) {
        $categories = get_option('swipecommerce_custom_categories', array());
        
        foreach ($categories as $index => $category) {
            if ($category['id'] === $category_id) {
                $categories[$index]['visibility'] = (bool) $visible;
                break;
            }
        }
        
        return update_option('swipecommerce_custom_categories', $categories);
    }

    /**
     * Search products for category assignment.
     *
     * @since    1.0.7
     * @param    string    $search_term    The search term.
     * @param    int       $page           The page number.
     * @param    int       $per_page       Products per page.
     * @return   array                     Search results with pagination info.
     */
    public function search_products($search_term = '', $page = 1, $per_page = 50) {
        // If search term is empty, return recent products for better UX
        if (empty($search_term)) {
            $args = array(
                'status' => 'publish',
                'limit' => $per_page,
                'offset' => ($page - 1) * $per_page,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            $products = wc_get_products($args);
            $results = array();
            
            foreach ($products as $product) {
                $results[] = $this->format_product_for_search($product);
            }
            
            return array(
                'products' => $results,
                'has_more' => count($results) >= $per_page,
                'total_found' => count($results),
                'page' => $page
            );
        }
        
        try {
            $results = array();
            
            // Simple and reliable search approach
            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $per_page,
                'paged' => $page,
                's' => $search_term, // WordPress native search
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_sku',
                        'value' => $search_term,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => '_sku',
                        'compare' => 'NOT EXISTS', // Allow products without SKU to be found by title
                        'value' => ''
                    )
                )
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $product = wc_get_product(get_the_ID());
                    if ($product) {
                        $results[] = $this->format_product_for_search($product);
                    }
                }
                wp_reset_postdata();
            }
            
            // Check if there are more results
            $has_more = $query->found_posts > ($page * $per_page);
            
            return array(
                'products' => $results,
                'has_more' => $has_more,
                'page' => $page,
                'total_found' => $query->found_posts
            );
            
        } catch (Exception $e) {
            error_log('SwipeCommerce search error: ' . $e->getMessage());
            return array(
                'products' => array(),
                'has_more' => false,
                'page' => $page,
                'total_found' => 0,
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Format a product for search results.
     *
     * @since    1.0.7
     * @param    WC_Product    $product    The WooCommerce product object.
     * @return   array                     Formatted product data.
     */
    private function format_product_for_search($product) {
        return array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price_html(),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail'),
            'sku' => $product->get_sku() ?: __('No SKU', 'swipecommerce-pro'),
            'status' => $product->get_status(),
            'type' => $product->get_type()
        );
    }

    /**
     * Bulk action handler for categories.
     *
     * @since    1.0.7
     * @param    string    $action         The bulk action to perform.
     * @param    array     $category_ids   Array of category IDs.
     * @return   bool                      True on success, false on failure.
     */
    public function bulk_action_categories($action, $category_ids) {
        if (!is_array($category_ids) || empty($category_ids)) {
            return false;
        }

        $categories = get_option('swipecommerce_custom_categories', array());
        $updated = false;

        switch ($action) {
            case 'enable':
                foreach ($categories as $index => $category) {
                    if (in_array($category['id'], $category_ids)) {
                        $categories[$index]['visibility'] = true;
                        $updated = true;
                    }
                }
                break;

            case 'disable':
                foreach ($categories as $index => $category) {
                    if (in_array($category['id'], $category_ids)) {
                        $categories[$index]['visibility'] = false;
                        $updated = true;
                    }
                }
                break;

            case 'delete':
                $categories = array_filter($categories, function($cat) use ($category_ids) {
                    return !in_array($cat['id'], $category_ids);
                });
                $categories = array_values($categories); // Re-index array
                $updated = true;
                break;
        }

        if ($updated) {
            return update_option('swipecommerce_custom_categories', $categories);
        }

        return false;
    }

    /**
     * Get available color schemes for categories.
     *
     * @since    1.0.7
     * @return   array    Array of color scheme options.
     */
    public function get_color_schemes() {
        return array(
            'gradient-pink' => __('Pink Gradient', 'swipecommerce-pro'),
            'gradient-blue' => __('Blue Gradient', 'swipecommerce-pro'),
            'gradient-green' => __('Green Gradient', 'swipecommerce-pro'),
            'gradient-purple' => __('Purple Gradient', 'swipecommerce-pro'),
            'gradient-orange' => __('Orange Gradient', 'swipecommerce-pro'),
        );
    }

    /**
     * Validate category data before saving.
     *
     * @since    1.0.7
     * @param    array    $category_data    The category data to validate.
     * @return   array                      Validation results.
     */
    public function validate_category_data($category_data) {
        $errors = array();

        // Validate required fields
        if (empty($category_data['name'])) {
            $errors[] = __('Category name is required.', 'swipecommerce-pro');
        }

        if (empty($category_data['id'])) {
            $errors[] = __('Category ID is required.', 'swipecommerce-pro');
        }

        // Validate color scheme
        $valid_schemes = array_keys($this->get_color_schemes());
        if (!empty($category_data['color_scheme']) && !in_array($category_data['color_scheme'], $valid_schemes)) {
            $errors[] = __('Invalid color scheme selected.', 'swipecommerce-pro');
        }

        // Validate order
        if (isset($category_data['order']) && (!is_numeric($category_data['order']) || $category_data['order'] < 1)) {
            $errors[] = __('Order must be a positive number.', 'swipecommerce-pro');
        }

        // Check for duplicate ID (if new category)
        if (!empty($category_data['id']) && !isset($category_data['_editing'])) {
            $existing = $this->get_custom_category($category_data['id']);
            if ($existing) {
                $errors[] = __('A category with this ID already exists.', 'swipecommerce-pro');
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors
        );
    }
}