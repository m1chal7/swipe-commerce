<?php
/**
 * Categories management JavaScript
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

<script>
jQuery(document).ready(function($) {
    // Initialize sortable
    if (typeof $.fn.sortable !== 'undefined') {
        $('#sortable-categories').sortable({
            handle: '.drag-handle',
            placeholder: 'category-placeholder',
            tolerance: 'pointer',
            cursor: 'grabbing',
            distance: 10, // Require 10px movement to start drag
            cancel: '.action-btn, input, button, a', // Prevent dragging when clicking on these elements
            start: function(e, ui) {
                ui.placeholder.height(ui.item.height());
                ui.item.addClass('ui-sortable-helper');
                // Add visual feedback to all cards
                $('#sortable-categories .category-card-enhanced:not(.ui-sortable-helper)').addClass('drag-mode');
            },
            stop: function(e, ui) {
                ui.item.removeClass('ui-sortable-helper');
                $('#sortable-categories .category-card-enhanced').removeClass('drag-mode');
                saveOrder();
            },
            over: function(e, ui) {
                ui.placeholder.addClass('active-placeholder');
            },
            out: function(e, ui) {
                ui.placeholder.removeClass('active-placeholder');
            }
        });
    }

    // Prevent accidental drag when clicking buttons or checkboxes
    $(document).on('mousedown touchstart', '.card-action-zone, .category-checkbox, .action-btn', function(e) {
        e.stopPropagation();
    });

    // View toggle
    $('.view-btn').click(function() {
        const view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        const container = $('.categories-container');
        container.removeClass('grid-view list-view').addClass(view + '-view');
    });

    // Quick edit modal
    $('.quick-edit').click(function() {
        const categoryId = $(this).data('category-id');
        openQuickEditModal(categoryId);
    });

    $('.modal-close').click(function() {
        $('#quick-edit-modal').hide();
    });

    $('#save-quick-edit').click(function() {
        saveQuickEdit();
    });

    // Toggle visibility - temporarily disabled
    /*
    $('.toggle-visibility').click(function() {
        const categoryId = $(this).data('category-id');
        const isVisible = $(this).data('visible') === 'true';
        toggleVisibility(categoryId, !isVisible);
    });
    */

    // Delete category
    $('.delete-category').click(function() {
        const categoryId = $(this).data('category-id');
        if (confirm('<?php esc_attr_e("Are you sure you want to delete this category?", "swipecommerce-pro"); ?>')) {
            deleteCategory(categoryId);
        }
    });

    // Bulk actions
    $('#apply-bulk-action').click(function() {
        const action = $('#bulk-action-selector').val();
        const selected = $('.category-checkbox:checked').map(function() {
            return this.value;
        }).get();

        if (!action || selected.length === 0) {
            alert('<?php esc_attr_e("Please select an action and categories", "swipecommerce-pro"); ?>');
            return;
        }

        applyBulkAction(action, selected);
    });

    // Functions
    function openQuickEditModal(categoryId) {
        // Get category data from card
        const card = $('[data-category-id="' + categoryId + '"]');
        const name = card.find('.category-name').text();
        const description = card.find('.category-description').text();
        const icon = card.find('.category-icon-large').text();
        const colorScheme = card.find('.category-gradient-preview').attr('class').replace('category-gradient-preview ', '');
        const isVisible = card.find('.toggle-visibility').data('visible') === 'true';

        // Populate modal
        $('#quick-edit-category-id').val(categoryId);
        $('#quick-edit-name').val(name);
        $('#quick-edit-description').val(description);
        $('#quick-edit-icon').val(icon);
        $('#quick-edit-color').val(colorScheme);
        $('#quick-edit-visibility').prop('checked', isVisible);

        // Show modal
        $('#quick-edit-modal').show();
    }

    function saveQuickEdit() {
        const categoryId = $('#quick-edit-category-id').val();
        const formData = {
            action: 'swipecommerce_save_category',
            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
            id: categoryId,
            name: $('#quick-edit-name').val(),
            description: $('#quick-edit-description').val(),
            icon: $('#quick-edit-icon').val(),
            color_scheme: $('#quick-edit-color').val(),
            visibility: $('#quick-edit-visibility').is(':checked') ? '1' : '0',
            order: $('[data-category-id="' + categoryId + '"]').data('order')
        };

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                $('#quick-edit-modal').hide();
                location.reload(); // Simple reload for now
            } else {
                alert('<?php esc_attr_e("Failed to save changes", "swipecommerce-pro"); ?>');
            }
        });
    }

    // toggleVisibility function - temporarily disabled
    /*
    function toggleVisibility(categoryId, visible) {
        $.post(ajaxurl, {
            action: 'swipecommerce_toggle_category_visibility',
            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
            category_id: categoryId,
            visible: visible ? '1' : '0'
        }, function(response) {
            if (response.success) {
                location.reload();
            }
        });
    }
    */

    function deleteCategory(categoryId) {
        $.post(ajaxurl, {
            action: 'swipecommerce_delete_category',
            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
            category_id: categoryId
        }, function(response) {
            if (response.success) {
                $('[data-category-id="' + categoryId + '"]').fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    }

    function saveOrder() {
        const order = $('#sortable-categories .category-card-enhanced').map(function(index) {
            return {
                id: $(this).data('category-id'),
                order: index + 1
            };
        }).get();

        // Show saving indicator
        showOrderStatus('saving');

        $.post(ajaxurl, {
            action: 'swipecommerce_save_category_order',
            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
            order: JSON.stringify(order)
        })
        .done(function(response) {
            if (response.success) {
                showOrderStatus('success');
                // Update the order numbers in the UI
                updateOrderDisplay();
            } else {
                showOrderStatus('error', response.data || 'Unknown error');
            }
        })
        .fail(function() {
            showOrderStatus('error', 'Network error - please try again');
        });
    }

    function showOrderStatus(status, message) {
        const statusEl = $('#order-status');
        statusEl.removeClass('saving success error').addClass(status);
        
        switch(status) {
            case 'saving':
                statusEl.html('<span class="dashicons dashicons-update-alt spinning"></span> Saving order...');
                break;
            case 'success':
                statusEl.html('<span class="dashicons dashicons-yes-alt"></span> Order saved!');
                setTimeout(() => statusEl.fadeOut(), 2000);
                break;
            case 'error':
                statusEl.html('<span class="dashicons dashicons-warning"></span> Error: ' + message);
                setTimeout(() => statusEl.fadeOut(), 4000);
                break;
        }
        statusEl.show();
    }

    function updateOrderDisplay() {
        $('#sortable-categories .category-card-enhanced').each(function(index) {
            $(this).find('.stat-number:last').text(index + 1);
            $(this).data('order', index + 1);
        });
    }

    function applyBulkAction(action, categoryIds) {
        $.post(ajaxurl, {
            action: 'swipecommerce_bulk_category_action',
            nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
            bulk_action: action,
            category_ids: categoryIds
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data || '<?php esc_attr_e("Bulk action failed", "swipecommerce-pro"); ?>');
            }
        });
    }
});
</script>