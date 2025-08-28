<?php
/**
 * Provide a admin area view for the categories management
 *
 * This file displays the categories management interface.
 *
 * @since      1.0.7
 * @package    SwipeCommerce_Pro
 * @subpackage SwipeCommerce_Pro/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$admin = SwipeCommerce_Core::instance()->get_component('admin');

// Display any success/error messages
$admin->display_admin_notices();
?>

<div class="wrap">
    <h1><?php esc_html_e('Custom Categories', 'swipecommerce-pro'); ?>
        <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&action=new'); ?>" class="page-title-action">
            <?php esc_html_e('Add New Category', 'swipecommerce-pro'); ?>
        </a>
    </h1>

    <?php if (!class_exists('WooCommerce')): ?>
    <div class="notice notice-warning">
        <p><strong><?php esc_html_e('Warning:', 'swipecommerce-pro'); ?></strong> 
        <?php esc_html_e('WooCommerce is not installed or activated. Product assignment may not work properly.', 'swipecommerce-pro'); ?></p>
    </div>
    <?php endif; ?>

    <div class="swipecommerce-categories-admin">
        <?php if (isset($_GET['action']) && $_GET['action'] === 'new' || $editing_category): ?>
            <!-- Category Form -->
            <?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/category-form.php'; ?>
        <?php else: ?>
            <!-- Categories List -->
            <?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/categories-list.php'; ?>
        <?php endif; ?>
    </div>
</div>