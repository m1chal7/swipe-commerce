<?php
/**
 * Categories list template
 *
 * @since      1.0.7
 * @package    SwipeCommerce_Pro
 * @subpackage SwipeCommerce_Pro/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="swipecommerce-visual-manager">
    <!-- Top Bar -->
    <div class="visual-manager-header">
        <div class="view-controls">
            <button type="button" class="view-btn active" data-view="grid">
                <span class="dashicons dashicons-grid-view"></span> <?php esc_html_e('Grid View', 'swipecommerce-pro'); ?>
            </button>
            <button type="button" class="view-btn" data-view="list">
                <span class="dashicons dashicons-list-view"></span> <?php esc_html_e('List View', 'swipecommerce-pro'); ?>
            </button>
        </div>
        
        <div class="bulk-actions">
            <select id="bulk-action-selector">
                <option value=""><?php esc_html_e('Bulk Actions', 'swipecommerce-pro'); ?></option>
                <option value="enable"><?php esc_html_e('Enable Selected', 'swipecommerce-pro'); ?></option>
                <option value="disable"><?php esc_html_e('Disable Selected', 'swipecommerce-pro'); ?></option>
                <option value="delete"><?php esc_html_e('Delete Selected', 'swipecommerce-pro'); ?></option>
            </select>
            <button type="button" class="button" id="apply-bulk-action">
                <?php esc_html_e('Apply', 'swipecommerce-pro'); ?>
            </button>
        </div>
    </div>

    <?php if (empty($categories)): ?>
        <div class="no-categories-state">
            <div class="empty-state-icon">
                <span class="dashicons dashicons-category" style="font-size: 48px; color: #c3c4c7;"></span>
            </div>
            <h3><?php esc_html_e('No Custom Categories Yet', 'swipecommerce-pro'); ?></h3>
            <p><?php esc_html_e('Create your first custom category to organize products in beautiful Netflix-style sliders.', 'swipecommerce-pro'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&action=new'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-plus-alt" style="margin-right: 5px;"></span>
                <?php esc_html_e('Create Your First Category', 'swipecommerce-pro'); ?>
            </a>
        </div>
    <?php else: ?>
        <!-- Drag & Drop Notice -->
        <div class="drag-drop-notice">
            <span class="dashicons dashicons-move"></span>
            <?php esc_html_e('Drag and drop categories to reorder them. Changes are saved automatically.', 'swipecommerce-pro'); ?>
        </div>

        <!-- Order Status Indicator -->
        <div id="order-status" class="order-status" style="display: none;"></div>

        <!-- Categories Grid/List -->
        <div class="categories-container grid-view" id="sortable-categories">
            <?php foreach ($categories as $index => $category): ?>
                <div class="category-card-enhanced" data-category-id="<?php echo esc_attr($category['id']); ?>" data-order="<?php echo esc_attr($category['order']); ?>">
                    
                    <!-- Dedicated Drag Zone (Left) -->
                    <div class="card-drag-zone">
                        <div class="drag-handle" title="<?php esc_attr_e('Drag to reorder', 'swipecommerce-pro'); ?>">
                            <span class="dashicons dashicons-menu"></span>
                            <span class="dashicons dashicons-menu"></span>
                            <span class="dashicons dashicons-menu"></span>
                        </div>
                        <div class="card-select">
                            <input type="checkbox" class="category-checkbox" value="<?php echo esc_attr($category['id']); ?>">
                        </div>
                    </div>

                    <!-- Main Card Content (Center) -->
                    <div class="card-main-content">
                        <div class="category-visual">
                            <div class="category-icon-large">
                                <?php echo esc_html($category['icon']); ?>
                            </div>
                            <div class="category-gradient-preview <?php echo esc_attr($category['color_scheme']); ?>"></div>
                        </div>

                        <div class="category-content">
                            <div class="category-header-info">
                                <h3 class="category-name"><?php echo esc_html($category['name']); ?></h3>
                                <!-- Visibility status temporarily hidden -->
                            </div>
                            <p class="category-description"><?php echo esc_html($category['description']); ?></p>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo count($category['products']); ?></span>
                                    <span class="stat-label"><?php esc_html_e('Products', 'swipecommerce-pro'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo esc_html($category['order']); ?></span>
                                    <span class="stat-label"><?php esc_html_e('Order', 'swipecommerce-pro'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons Zone (Right) -->
                    <div class="card-action-zone">
                        <button type="button" class="action-btn quick-edit" data-category-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Quick Edit', 'swipecommerce-pro'); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=swipecommerce-categories&edit=' . $category['id']); ?>" class="action-btn" title="<?php esc_attr_e('Full Edit', 'swipecommerce-pro'); ?>">
                            <span class="dashicons dashicons-admin-generic"></span>
                        </a>
                        <!-- Visibility toggle temporarily hidden
                        <button type="button" class="action-btn toggle-visibility" data-category-id="<?php echo esc_attr($category['id']); ?>" data-visible="<?php echo $category['visibility'] ? 'true' : 'false'; ?>" title="<?php esc_attr_e('Toggle Visibility', 'swipecommerce-pro'); ?>">
                            <span class="dashicons dashicons-<?php echo $category['visibility'] ? 'visibility' : 'hidden'; ?>"></span>
                        </button>
                        -->
                        <button type="button" class="action-btn delete-category" data-category-id="<?php echo esc_attr($category['id']); ?>" title="<?php esc_attr_e('Delete', 'swipecommerce-pro'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Edit Modal -->
<div id="quick-edit-modal" class="swipecommerce-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php esc_html_e('Quick Edit Category', 'swipecommerce-pro'); ?></h2>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="quick-edit-form">
                <input type="hidden" id="quick-edit-category-id">
                
                <div class="form-row">
                    <label for="quick-edit-name"><?php esc_html_e('Category Name', 'swipecommerce-pro'); ?></label>
                    <input type="text" id="quick-edit-name" class="regular-text" required>
                </div>
                
                <div class="form-row">
                    <label for="quick-edit-description"><?php esc_html_e('Description', 'swipecommerce-pro'); ?></label>
                    <textarea id="quick-edit-description" rows="3" class="large-text"></textarea>
                </div>
                
                <div class="form-row">
                    <label for="quick-edit-icon"><?php esc_html_e('Icon', 'swipecommerce-pro'); ?></label>
                    <input type="text" id="quick-edit-icon" class="small-text">
                </div>
                
                <div class="form-row">
                    <label for="quick-edit-color"><?php esc_html_e('Color Scheme', 'swipecommerce-pro'); ?></label>
                    <select id="quick-edit-color">
                        <option value="gradient-pink"><?php esc_html_e('Pink Gradient', 'swipecommerce-pro'); ?></option>
                        <option value="gradient-blue"><?php esc_html_e('Blue Gradient', 'swipecommerce-pro'); ?></option>
                        <option value="gradient-green"><?php esc_html_e('Green Gradient', 'swipecommerce-pro'); ?></option>
                        <option value="gradient-purple"><?php esc_html_e('Purple Gradient', 'swipecommerce-pro'); ?></option>
                        <option value="gradient-orange"><?php esc_html_e('Orange Gradient', 'swipecommerce-pro'); ?></option>
                    </select>
                </div>
                
                <!-- Visibility option temporarily hidden
                <div class="form-row">
                    <label>
                        <input type="checkbox" id="quick-edit-visibility">
                        <?php esc_html_e('Visible in frontend', 'swipecommerce-pro'); ?>
                    </label>
                </div>
                -->
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button button-primary" id="save-quick-edit">
                <?php esc_html_e('Save Changes', 'swipecommerce-pro'); ?>
            </button>
            <button type="button" class="button modal-close">
                <?php esc_html_e('Cancel', 'swipecommerce-pro'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Include the styles and scripts -->
<?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/categories-styles.php'; ?>
<?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/categories-scripts.php'; ?>