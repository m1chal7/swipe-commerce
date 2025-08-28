<?php
/**
 * Category form template
 *
 * @since      1.0.7
 * @package    SwipeCommerce_Pro
 * @subpackage SwipeCommerce_Pro/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$categories = SwipeCommerce_Core::instance()->get_component('categories');

$category = $editing_category ?: array(
    'id' => '',
    'name' => '',
    'description' => '',
    'color_scheme' => 'gradient-pink',
    'icon' => '🏆',
    'order' => 1,
    'products' => array(),
    'visibility' => true
);

$color_schemes = $categories->get_color_schemes();
?>

<div class="category-form">
    <h2><?php echo $editing_category ? esc_html__('Edit Category', 'swipecommerce-pro') : esc_html__('Add New Category', 'swipecommerce-pro'); ?></h2>
    
    <form method="post">
        <?php wp_nonce_field('swipecommerce_categories_nonce', 'swipecommerce_categories_nonce'); ?>
        <input type="hidden" name="swipecommerce_category_action" value="save_category">
        <input type="hidden" name="category_id" value="<?php echo esc_attr($category['id']); ?>">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="category_name"><?php esc_html_e('Category Name', 'swipecommerce-pro'); ?></label>
                </th>
                <td>
                    <input type="text" id="category_name" name="category_name" value="<?php echo esc_attr($category['name']); ?>" class="regular-text" required>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="category_description"><?php esc_html_e('Description', 'swipecommerce-pro'); ?></label>
                </th>
                <td>
                    <textarea id="category_description" name="category_description" rows="3" class="large-text"><?php echo esc_textarea($category['description']); ?></textarea>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="category_icon"><?php esc_html_e('Icon (Emoji)', 'swipecommerce-pro'); ?></label>
                </th>
                <td>
                    <input type="text" id="category_icon" name="category_icon" value="<?php echo esc_attr($category['icon']); ?>" class="small-text">
                    <p class="description"><?php esc_html_e('Enter an emoji to represent this category', 'swipecommerce-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="color_scheme"><?php esc_html_e('Color Scheme', 'swipecommerce-pro'); ?></label>
                </th>
                <td>
                    <select id="color_scheme" name="color_scheme">
                        <?php foreach ($color_schemes as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($category['color_scheme'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="color-scheme-preview <?php echo esc_attr($category['color_scheme']); ?>"></span>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Display Order', 'swipecommerce-pro'); ?>
                </th>
                <td>
                    <span class="order-display"><?php echo esc_html($category['order']); ?></span>
                    <p class="description"><?php esc_html_e('Use drag & drop in the categories list to change order.', 'swipecommerce-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Visibility', 'swipecommerce-pro'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="category_visibility" value="1" <?php checked($category['visibility'], true); ?>>
                        <?php esc_html_e('Show this category in sliders', 'swipecommerce-pro'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Uncheck to hide this category from frontend sliders', 'swipecommerce-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Assigned Products', 'swipecommerce-pro'); ?></label>
                </th>
                <td>
                    <?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/product-selector.php'; ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="<?php echo $editing_category ? esc_attr__('Update Category', 'swipecommerce-pro') : esc_attr__('Create Category', 'swipecommerce-pro'); ?>">
            <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories'); ?>" class="button">
                <?php esc_html_e('Cancel', 'swipecommerce-pro'); ?>
            </a>
        </p>
    </form>
</div>

<style>
.color-scheme-preview {
    display: inline-block;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    margin-left: 10px;
    vertical-align: middle;
    border: 2px solid #ddd;
}

.order-display {
    font-size: 18px;
    font-weight: 600;
    color: #2271b1;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Color scheme preview update
    $('#color_scheme').change(function() {
        $('.color-scheme-preview').attr('class', 'color-scheme-preview ' + $(this).val());
    });
});

</script>