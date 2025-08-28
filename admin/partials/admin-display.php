<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
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
$settings = SwipeCommerce_Core::instance()->get_component('settings');
$shortcode_examples = $admin->get_shortcode_examples();
$plugin_status = $admin->get_plugin_status();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (!class_exists('WooCommerce')): ?>
    <div class="notice notice-warning">
        <p><strong><?php esc_html_e('Warning:', 'swipecommerce-pro'); ?></strong> 
        <?php esc_html_e('WooCommerce is not installed or activated. Some features may not work properly.', 'swipecommerce-pro'); ?></p>
    </div>
    <?php endif; ?>
    
    <div style="background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2><?php esc_html_e('How to Use SwipeCommerce Pro', 'swipecommerce-pro'); ?></h2>
        <p><?php esc_html_e('Add Netflix-style product sliders to your pages and posts using these shortcodes:', 'swipecommerce-pro'); ?></p>
        
        <?php foreach ($shortcode_examples as $example): ?>
            <h3><?php echo esc_html($example['title']); ?></h3>
            
            <?php if (isset($example['code'])): ?>
                <code><?php echo esc_html($example['code']); ?></code>
                <?php if (isset($example['description'])): ?>
                    <p><em><?php echo esc_html($example['description']); ?></em></p>
                <?php endif; ?>
                
            <?php elseif (isset($example['examples'])): ?>
                <?php if (is_array($example['examples'])): ?>
                    <?php foreach ($example['examples'] as $code => $desc): ?>
                        <?php if (is_numeric($code)): ?>
                            <code><?php echo esc_html($desc); ?></code><br><br>
                        <?php else: ?>
                            <code><?php echo esc_html($code); ?></code> <em><?php echo esc_html($desc); ?></em><br><br>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($example['examples'] as $code): ?>
                        <code><?php echo esc_html($code); ?></code><br><br>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            <?php elseif (isset($example['params'])): ?>
                <ul>
                    <?php foreach ($example['params'] as $param => $description): ?>
                        <li><strong><?php echo esc_html($param); ?></strong>: <?php echo esc_html($description); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <form action="options.php" method="post">
        <?php
        settings_fields('swipecommerce_settings');
        do_settings_sections('swipecommerce_settings');
        submit_button(__('Save Settings', 'swipecommerce-pro'));
        ?>
    </form>
    
    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #667eea;">
        <h3><?php esc_html_e('Plugin Status', 'swipecommerce-pro'); ?></h3>
        
        <table class="widefat" style="background: white;">
            <tbody>
                <tr>
                    <td><strong><?php esc_html_e('Version:', 'swipecommerce-pro'); ?></strong></td>
                    <td><?php echo esc_html($plugin_status['version']); ?></td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('WooCommerce:', 'swipecommerce-pro'); ?></strong></td>
                    <td>
                        <?php if ($plugin_status['woocommerce_active']): ?>
                            <span style="color: green;">✓ <?php esc_html_e('Active', 'swipecommerce-pro'); ?></span>
                            <?php if ($plugin_status['woocommerce_version']): ?>
                                (v<?php echo esc_html($plugin_status['woocommerce_version']); ?>)
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: red;">✗ <?php esc_html_e('Not Found', 'swipecommerce-pro'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('AJAX Support:', 'swipecommerce-pro'); ?></strong></td>
                    <td>
                        <?php if ($plugin_status['ajax_enabled']): ?>
                            <span style="color: green;">✓ <?php esc_html_e('Enabled', 'swipecommerce-pro'); ?></span>
                        <?php else: ?>
                            <span style="color: orange;">○ <?php esc_html_e('Disabled', 'swipecommerce-pro'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Category Filters:', 'swipecommerce-pro'); ?></strong></td>
                    <td>
                        <?php if ($plugin_status['filters_enabled']): ?>
                            <span style="color: green;">✓ <?php esc_html_e('Enabled', 'swipecommerce-pro'); ?></span>
                        <?php else: ?>
                            <span style="color: orange;">○ <?php esc_html_e('Disabled', 'swipecommerce-pro'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php esc_html_e('Custom Categories:', 'swipecommerce-pro'); ?></strong></td>
                    <td>
                        <?php 
                        printf(
                            /* translators: %1$d visible categories, %2$d total categories */
                            esc_html__('%1$d visible / %2$d total', 'swipecommerce-pro'),
                            $plugin_status['visible_categories_count'],
                            $plugin_status['categories_count']
                        ); 
                        ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=swipecommerce-categories')); ?>" class="button button-small" style="margin-left: 10px;">
                            <?php esc_html_e('Manage Categories', 'swipecommerce-pro'); ?>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.swipecommerce-status-table {
    margin-top: 15px;
}

.swipecommerce-status-table td:first-child {
    width: 200px;
    font-weight: 600;
}

.swipecommerce-shortcode-examples code {
    display: inline-block;
    background: #f1f1f1;
    padding: 8px 12px;
    border-radius: 4px;
    margin: 5px 0;
    font-family: Consolas, Monaco, 'Courier New', monospace;
}

.swipecommerce-shortcode-examples h3 {
    color: #23282d;
    border-bottom: 1px solid #e1e1e1;
    padding-bottom: 10px;
    margin-top: 30px;
}

.swipecommerce-shortcode-examples h3:first-child {
    margin-top: 0;
}
</style>