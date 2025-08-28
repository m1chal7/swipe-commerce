<?php
/**
 * Enhanced product selector interface
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

<div id="enhanced-product-selector">
    <input type="hidden" id="category_products" name="category_products" value="<?php echo esc_attr(implode(',', $category['products'])); ?>">
    
    <!-- Product Selection Interface -->
    <div class="product-selector-container">
        
        <!-- Search & Filter Section -->
        <div class="product-search-section">
            <div class="search-header">
                <h4><?php esc_html_e('Add Products to Category', 'swipecommerce-pro'); ?></h4>
                <div class="search-controls">
                    <input type="text" id="product-search" placeholder="<?php esc_attr_e('Search products by name, SKU...', 'swipecommerce-pro'); ?>" class="regular-text">
                    <button type="button" class="button" id="clear-search"><?php esc_html_e('Show All', 'swipecommerce-pro'); ?></button>
                </div>
            </div>
            
            <div id="search-results" class="product-grid">
                <div class="search-loading"><?php esc_html_e('Loading products...', 'swipecommerce-pro'); ?></div>
            </div>
            
            <div id="load-more-products" style="display: none;">
                <button type="button" class="button" id="load-more-btn"><?php esc_html_e('Load More Products', 'swipecommerce-pro'); ?></button>
            </div>
        </div>
        
        <!-- Selected Products Preview -->
        <div class="selected-products-section">
            <div class="selected-header">
                <h4><?php esc_html_e('Selected Products', 'swipecommerce-pro'); ?> <span id="selected-count">0</span></h4>
                <div class="selected-controls">
                    <span class="drag-instructions"><?php esc_html_e('Drag to reorder', 'swipecommerce-pro'); ?></span>
                    <button type="button" class="button" id="clear-all-selections"><?php esc_html_e('Clear All', 'swipecommerce-pro'); ?></button>
                </div>
            </div>
            
            <div id="selected-products-preview" class="selected-products-grid sortable-products">
                <div class="no-products-message"><?php esc_html_e('No products selected. Search and click products to add them.', 'swipecommerce-pro'); ?></div>
            </div>
        </div>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentPage = 1;
    let isLoading = false;
    let currentSearchTerm = '';
    let selectedProductsCache = {};

    // Initialize selected products cache first
    initializeSelectedProductsCache();
    
    // Then load initial products and preview
    loadInitialProducts();
    loadSelectedProductsPreview();
    
    // Initialize drag-and-drop sorting
    initializeSortableProducts();
    
    // Product search functionality
    let searchTimeout;
    $('#product-search').on('input', function() {
        clearTimeout(searchTimeout);
        currentSearchTerm = $(this).val().trim();
        currentPage = 1;
        
        searchTimeout = setTimeout(() => {
            searchProducts(currentSearchTerm, 1);
        }, 300);
    });

    // Clear search / Show all products
    $('#clear-search').on('click', function() {
        $('#product-search').val('');
        currentSearchTerm = '';
        currentPage = 1;
        searchProducts('', 1);
    });

    // Load more products
    $('#load-more-btn').on('click', function() {
        if (!isLoading) {
            currentPage++;
            searchProducts(currentSearchTerm, currentPage, true);
        }
    });

    // Clear all selections
    $('#clear-all-selections').on('click', function() {
        if (confirm('<?php esc_attr_e("Are you sure you want to remove all products from this category?", "swipecommerce-pro"); ?>')) {
            $('#category_products').val('');
            selectedProductsCache = {};
            updateSelectedCount();
            loadSelectedProductsPreview();
            refreshSearchResults();
        }
    });

    function loadInitialProducts() {
        searchProducts('', 1);
    }
    
    function searchProducts(term, page = 1, append = false) {
        if (isLoading) return;
        isLoading = true;
        
        if (!append) {
            $('#search-results').html('<div class="search-loading"><?php esc_html_e("Loading products...", "swipecommerce-pro"); ?></div>');
            $('#load-more-products').hide();
        } else {
            $('#load-more-btn').prop('disabled', true).text('<?php esc_html_e("Loading...", "swipecommerce-pro"); ?>');
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'swipecommerce_search_products',
                nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                search: term,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data.products, response.data.has_more, append);
                } else {
                    if (!append) {
                        $('#search-results').html('<div class="search-error"><?php esc_html_e("Search failed. Please try again.", "swipecommerce-pro"); ?></div>');
                    }
                }
            },
            error: function() {
                if (!append) {
                    $('#search-results').html('<div class="search-error"><?php esc_html_e("Search failed. Please try again.", "swipecommerce-pro"); ?></div>');
                }
            },
            complete: function() {
                isLoading = false;
                $('#load-more-btn').prop('disabled', false).text('<?php esc_html_e("Load More Products", "swipecommerce-pro"); ?>');
            }
        });
    }
    
    function displaySearchResults(products, hasMore = false, append = false) {
        const selectedProducts = getSelectedProductIds();
        
        if (!append && products.length === 0) {
            $('#search-results').html(`
                <div class="no-products-found">
                    <h4><?php esc_html_e("No products found", "swipecommerce-pro"); ?></h4>
                    <p><?php esc_html_e("Try adjusting your search or browse all products.", "swipecommerce-pro"); ?></p>
                </div>
            `);
            return;
        }
        
        let html = '';
        products.forEach(product => {
            const isSelected = selectedProducts.includes(product.id);
            selectedProductsCache[product.id] = product; // Cache for selected products display
            
            html += createProductCard(product, isSelected);
        });
        
        if (append) {
            $('#search-results .product-grid-container').append(html);
        } else {
            $('#search-results').html('<div class="product-grid-container">' + html + '</div>');
        }
        
        // Show/hide load more button
        if (hasMore) {
            $('#load-more-products').show();
        } else {
            $('#load-more-products').hide();
        }
    }

    function createProductCard(product, isSelected) {
        const imageUrl = product.image || '<?php echo wc_placeholder_img_src("thumbnail"); ?>';
        const buttonClass = isSelected ? 'selected' : 'available';
        const buttonText = isSelected ? '<?php esc_html_e("Selected", "swipecommerce-pro"); ?>' : '<?php esc_html_e("Select", "swipecommerce-pro"); ?>';
        
        return `
            <div class="product-card ${isSelected ? 'is-selected' : ''}" data-product-id="${product.id}">
                <div class="product-image">
                    <img src="${imageUrl}" alt="${product.name}" onerror="this.src='<?php echo wc_placeholder_img_src("thumbnail"); ?>'">
                    ${isSelected ? '<div class="selection-badge">✓</div>' : ''}
                </div>
                <div class="product-details">
                    <h4 class="product-name">${product.name}</h4>
                    <div class="product-meta">
                        <span class="product-price">${product.price}</span>
                        <span class="product-sku">SKU: ${product.sku}</span>
                    </div>
                    <button type="button" class="product-select-btn ${buttonClass}" data-product-id="${product.id}">
                        ${buttonText}
                    </button>
                </div>
            </div>
        `;
    }
    
    // Handle product selection
    $(document).on('click', '.product-select-btn', function(e) {
        e.preventDefault();
        const productId = parseInt($(this).data('product-id'));
        const productCard = $(this).closest('.product-card');
        const isCurrentlySelected = productCard.hasClass('is-selected');
        
        if (isCurrentlySelected) {
            removeProductFromSelection(productId, productCard);
        } else {
            addProductToSelection(productId, productCard);
        }
    });

    function addProductToSelection(productId, productCard) {
        let selectedProducts = getSelectedProductIds();
        if (!selectedProducts.includes(productId)) {
            selectedProducts.push(productId);
            updateSelectedProducts(selectedProducts);
            
            // Update UI
            updateProductCardState(productCard, true);
            loadSelectedProductsPreview();
            updateSelectedCount();
            
            // Re-initialize sortable after adding new items
            setTimeout(function() {
                initializeSortableProducts();
            }, 100);
        }
    }
    
    function removeProductFromSelection(productId, productCard) {
        let selectedProducts = getSelectedProductIds();
        selectedProducts = selectedProducts.filter(id => id !== productId);
        updateSelectedProducts(selectedProducts);
        
        // Update UI
        updateProductCardState(productCard, false);
        loadSelectedProductsPreview();
        updateSelectedCount();
    }

    function updateProductCardState(productCard, isSelected) {
        const btn = productCard.find('.product-select-btn');
        const badge = productCard.find('.selection-badge');
        
        if (isSelected) {
            productCard.addClass('is-selected');
            btn.removeClass('available').addClass('selected').text('<?php esc_html_e("Selected", "swipecommerce-pro"); ?>');
            if (badge.length === 0) {
                productCard.find('.product-image').append('<div class="selection-badge">✓</div>');
            }
        } else {
            productCard.removeClass('is-selected');
            btn.removeClass('selected').addClass('available').text('<?php esc_html_e("Select", "swipecommerce-pro"); ?>');
            badge.remove();
        }
    }

    function loadSelectedProductsPreview() {
        const selectedIds = getSelectedProductIds();
        const container = $('#selected-products-preview');
        
        if (selectedIds.length === 0) {
            container.html('<div class="no-products-message"><?php esc_html_e("No products selected. Search and click products to add them.", "swipecommerce-pro"); ?></div>');
            container.removeClass('has-products');
            return;
        }
        
        let html = '<div class="selected-products-list">';
        selectedIds.forEach(productId => {
            const product = selectedProductsCache[productId];
            if (product) {
                html += createSelectedProductCard(product);
            } else {
                // If product not in cache, create a placeholder and fetch it
                loadProductDataAndUpdate(productId);
            }
        });
        html += '</div>';
        
        container.html(html).addClass('has-products');
        
        // Initialize sortable after a short delay to ensure DOM is ready
        setTimeout(function() {
            initializeSortableProducts();
        }, 50);
    }

    function createSelectedProductCard(product) {
        const imageUrl = product.image || '<?php echo wc_placeholder_img_src("thumbnail"); ?>';
        return `
            <div class="selected-product-item" data-product-id="${product.id}">
                <div class="drag-handle" title="<?php esc_attr_e('Drag to reorder', 'swipecommerce-pro'); ?>">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <img src="${imageUrl}" alt="${product.name}" class="selected-product-image">
                <div class="selected-product-info">
                    <span class="selected-product-name">${product.name}</span>
                    <span class="selected-product-price">${product.price}</span>
                </div>
                <button type="button" class="remove-selected-product" data-product-id="${product.id}" title="<?php esc_attr_e("Remove from category", "swipecommerce-pro"); ?>">
                    ✕
                </button>
            </div>
        `;
    }

    // Handle removal from selected products preview
    $(document).on('click', '.remove-selected-product', function(e) {
        e.preventDefault();
        const productId = parseInt($(this).data('product-id'));
        const productCard = $(`.product-card[data-product-id="${productId}"]`);
        removeProductFromSelection(productId, productCard);
    });

    function getSelectedProductIds() {
        const value = $('#category_products').val();
        return value ? value.split(',').map(id => parseInt(id)).filter(id => id > 0) : [];
    }
    
    function updateSelectedProducts(productIds) {
        $('#category_products').val(productIds.join(','));
    }
    
    function updateSelectedCount() {
        const count = getSelectedProductIds().length;
        $('#selected-count').text(`(${count})`);
    }

    function refreshSearchResults() {
        searchProducts(currentSearchTerm, 1);
    }
    
    // Initialize selected count
    updateSelectedCount();
    
    // New functions for fixing visibility and ordering
    function initializeSelectedProductsCache() {
        const selectedIds = getSelectedProductIds();
        if (selectedIds.length > 0) {
            // Load product data for already selected products
            selectedIds.forEach(productId => {
                if (!selectedProductsCache[productId]) {
                    loadProductDataAndUpdate(productId);
                }
            });
        }
    }
    
    function loadProductDataAndUpdate(productId) {
        // Fetch individual product data via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'swipecommerce_get_product_data',
                nonce: '<?php echo wp_create_nonce("swipecommerce_admin_nonce"); ?>',
                product_id: productId
            },
            success: function(response) {
                if (response.success && response.data) {
                    selectedProductsCache[productId] = response.data;
                    // Refresh the selected products preview if this was the last missing product
                    loadSelectedProductsPreview();
                }
            },
            error: function() {
                console.warn('Could not load product data for ID:', productId);
            }
        });
    }
    
    function initializeSortableProducts() {
        const container = $('#selected-products-preview .selected-products-list');
        
        // Destroy existing sortable if it exists
        if (container.hasClass('ui-sortable')) {
            container.sortable('destroy');
        }
        
        // Initialize sortable if we have products
        if ($('#selected-products-preview').hasClass('has-products') && container.length) {
            container.sortable({
                items: '.selected-product-item',
                handle: '.drag-handle',
                axis: 'y',
                cursor: 'move',
                placeholder: 'selected-product-placeholder',
                tolerance: 'pointer',
                start: function(event, ui) {
                    ui.placeholder.height(ui.item.height());
                    ui.item.addClass('dragging');
                },
                stop: function(event, ui) {
                    ui.item.removeClass('dragging');
                    updateProductOrderFromSortable();
                }
            });
        }
    }
    
    function updateProductOrderFromSortable() {
        const orderedIds = [];
        $('#selected-products-preview .selected-product-item').each(function() {
            const productId = parseInt($(this).data('product-id'));
            orderedIds.push(productId);
        });
        
        // Update the hidden field
        const hiddenField = $('#category_products');
        const newValue = orderedIds.join(',');
        
        hiddenField.val(newValue);
        updateSelectedCount();
    }
    
});
</script>

<?php include_once SWIPECOMMERCE_PLUGIN_PATH . 'admin/partials/product-selector-styles.php'; ?>