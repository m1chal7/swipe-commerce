<?php
/**
 * Product selector styles
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

<style>
/* Enhanced Product Selector Styles */
.product-selector-container {
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fafafa;
    overflow: hidden;
    margin-top: 10px;
}

.product-search-section {
    background: white;
    border-bottom: 1px solid #ddd;
}

.search-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.search-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.search-controls {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-controls input {
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    padding: 8px 12px;
    min-width: 250px;
    font-size: 14px;
}

.search-controls input:focus {
    background: white;
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}

.search-controls button {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    border-radius: 6px;
    padding: 8px 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.search-controls button:hover {
    background: rgba(255,255,255,0.3);
}

/* Product Grid */
.product-grid {
    padding: 20px;
    min-height: 300px;
    max-height: 500px;
    overflow-y: auto;
}

.product-grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-card {
    background: white;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.product-card:hover {
    border-color: #2271b1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-card.is-selected {
    border-color: #00a32a;
    background: #f6ffed;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,163,42,0.15);
}

.product-image {
    position: relative;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    overflow: hidden;
}

.product-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

.selection-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #00a32a;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
}

.product-details {
    padding: 15px;
}

.product-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 8px 0;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 12px;
}

.product-price {
    font-weight: 600;
    color: #2271b1;
    font-size: 14px;
}

.product-sku {
    font-size: 12px;
    color: #64748b;
}

.product-select-btn {
    width: 100%;
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.product-select-btn.available {
    background: #2271b1;
    color: white;
}

.product-select-btn.available:hover {
    background: #135e96;
    transform: translateY(-1px);
}

.product-select-btn.selected {
    background: #00a32a;
    color: white;
}

.product-select-btn.selected:hover {
    background: #008a2e;
}

/* Selected Products Section */
.selected-products-section {
    background: #f8f9fa;
    border-top: 1px solid #ddd;
}

.selected-header {
    padding: 15px 20px;
    background: #e8f4fd;
    border-bottom: 1px solid #c3e6fc;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.selected-header h4 {
    margin: 0;
    font-size: 14px;
    color: #0c4a6e;
    font-weight: 600;
}

.selected-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.drag-instructions {
    font-size: 12px;
    color: #64748b;
    font-style: italic;
}

.selected-products-grid {
    padding: 20px;
    min-height: 150px;
    max-height: 300px;
    overflow-y: auto;
}

.selected-products-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* When sortable is enabled, adjust layout */
.selected-products-grid.has-products .selected-products-list {
    padding: 5px;
}

.selected-product-item {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
    transition: all 0.2s;
    cursor: move;
}

.selected-product-item:hover {
    border-color: #2271b1;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.selected-product-item.dragging {
    opacity: 0.8;
    transform: rotate(2deg);
    z-index: 1000;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

/* Drag handle */
.drag-handle {
    color: #9ca3af;
    cursor: move;
    padding: 2px;
    transition: color 0.2s;
    flex-shrink: 0;
}

.drag-handle:hover {
    color: #6b7280;
}

.drag-handle .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Sortable placeholder */
.selected-product-placeholder {
    background: #f3f4f6;
    border: 2px dashed #d1d5db;
    border-radius: 6px;
    height: 60px;
    margin: 4px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-style: italic;
    position: relative;
}

.selected-product-placeholder:before {
    content: "Drop here";
    font-size: 12px;
}

.selected-product-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    background: #f8f9fa;
}

.selected-product-info {
    flex: 1;
    min-width: 0;
}

.selected-product-name {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.selected-product-price {
    font-size: 12px;
    color: #2271b1;
    font-weight: 500;
}

.remove-selected-product {
    background: #f87171;
    color: white;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.remove-selected-product:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.no-products-message {
    text-align: center;
    color: #64748b;
    font-style: italic;
    padding: 30px 20px;
}

.no-products-found {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.no-products-found h4 {
    margin: 0 0 10px 0;
    color: #374151;
}

.search-loading {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
    font-style: italic;
}

.search-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 15px;
    border-radius: 6px;
    margin: 20px;
    text-align: center;
}

#load-more-products {
    padding: 20px;
    text-align: center;
    border-top: 1px solid #e1e5e9;
    background: #fafafa;
}

#load-more-btn {
    background: #f3f4f6;
    border: 1px solid #d1d5db;
    color: #374151;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

#load-more-btn:hover {
    background: #e5e7eb;
    border-color: #9ca3af;
}

#load-more-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-header {
        flex-direction: column;
        text-align: center;
    }

    .search-controls {
        justify-content: center;
        width: 100%;
    }

    .search-controls input {
        min-width: 200px;
    }

    .product-grid-container {
        grid-template-columns: 1fr;
    }

    .drag-instructions {
        display: none;
    }
    
    .selected-controls {
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
    }
}
</style>