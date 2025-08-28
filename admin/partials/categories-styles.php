<?php
/**
 * Categories management styles
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
/* Visual Manager Layout */
.swipecommerce-visual-manager {
    background: #f9f9f9;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.visual-manager-header {
    background: white;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.view-controls {
    display: flex;
    gap: 10px;
}

.view-btn {
    background: #f6f7f7;
    border: 1px solid #ddd;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.view-btn:hover {
    background: #e8f0fe;
    border-color: #2271b1;
}

.view-btn.active {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.bulk-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* Empty State */
.no-categories-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state-icon {
    margin-bottom: 20px;
}

.no-categories-state h3 {
    color: #50575e;
    margin-bottom: 10px;
}

.no-categories-state p {
    color: #646970;
    margin-bottom: 30px;
}

/* Drag Drop Notice */
.drag-drop-notice {
    background: #e8f4fd;
    border-left: 4px solid #2271b1;
    padding: 12px 20px;
    margin: 20px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #1d2327;
}

/* Categories Container */
.categories-container {
    padding: 20px;
    display: grid;
    gap: 20px;
}

.categories-container.grid-view {
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
}

.categories-container.list-view {
    grid-template-columns: 1fr;
}

/* Enhanced Category Cards */
.category-card-enhanced {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: stretch;
    min-height: 160px;
    cursor: default;
}

.category-card-enhanced:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: #2271b1;
}

.category-card-enhanced.ui-sortable-helper {
    opacity: 1 !important;
    transform: rotate(3deg) scale(1.03) !important;
    z-index: 1000;
}

/* Dedicated Drag Zone (Left) */
.card-drag-zone {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-right: 1px solid #e2e8f0;
    padding: 15px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 15px;
    min-width: 60px;
    border-radius: 12px 0 0 12px;
}

/* Main Content Area (Center) */
.card-main-content {
    flex: 1;
    padding: 20px;
    display: flex;
    gap: 20px;
}

/* Action Buttons Zone (Right) */
.card-action-zone {
    background: #f8fafc;
    border-left: 1px solid #e2e8f0;
    padding: 15px 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 60px;
    border-radius: 0 12px 12px 0;
}

.card-select input[type="checkbox"] {
    transform: scale(1.3);
    accent-color: #2271b1;
}

.drag-handle {
    background: rgba(34, 113, 177, 0.05);
    border: 2px dashed rgba(34, 113, 177, 0.2);
    border-radius: 8px;
    padding: 8px 4px;
    cursor: grab;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    width: 36px;
    height: 36px;
}

.drag-handle .dashicons {
    font-size: 12px;
    color: rgba(34, 113, 177, 0.6);
    line-height: 1;
}

.drag-handle:hover {
    background: rgba(34, 113, 177, 0.15);
    border-color: rgba(34, 113, 177, 0.5);
    cursor: grabbing;
    transform: scale(1.05);
}

.drag-handle:hover .dashicons {
    color: rgba(34, 113, 177, 0.8);
}

.category-card-enhanced.drag-mode {
    opacity: 0.4;
    transform: scale(0.95);
    transition: all 0.3s ease;
}

/* Content Layout Updates */
.category-header-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.category-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    flex: 1;
}

.category-status {
    margin-left: 10px;
}

.category-placeholder {
    background: rgba(34, 113, 177, 0.1);
    border: 2px dashed rgba(34, 113, 177, 0.3);
    border-radius: 12px;
    margin: 15px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-placeholder.active-placeholder {
    background: rgba(34, 113, 177, 0.2);
    border-color: rgba(34, 113, 177, 0.6);
    transform: scale(1.02);
}

.category-placeholder::before {
    content: '↓ Drop here ↓';
    color: rgba(34, 113, 177, 0.7);
    font-weight: 600;
    font-size: 16px;
}

/* Order Status Indicator */
.order-status {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 6px;
    padding: 8px 15px;
    margin: 10px 20px;
    font-size: 14px;
    text-align: center;
    transition: all 0.3s ease;
}

.order-status.saving {
    border-color: #2271b1;
    background: #e8f4fd;
    color: #2271b1;
}

.order-status.success {
    border-color: #00a32a;
    background: #e6ffed;
    color: #00a32a;
}

.order-status.error {
    border-color: #d63638;
    background: #fcf0f1;
    color: #d63638;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinning {
    animation: spin 1s linear infinite;
}

.category-status .status-active {
    color: #00a32a;
}

.category-status .status-inactive {
    color: #dba617;
}

/* Visual Section */
.category-visual {
    height: 120px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-gradient-preview {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.9;
}

.category-icon-large {
    font-size: 48px;
    z-index: 1;
    position: relative;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
}

/* Card Content */
.category-content {
    padding: 20px;
}

.category-name {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px 0;
    color: #1d2327;
}

.category-description {
    color: #646970;
    font-size: 13px;
    line-height: 1.4;
    margin: 0 0 15px 0;
}

.category-stats {
    display: flex;
    gap: 20px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 20px;
    font-weight: 700;
    color: #2271b1;
}

.stat-label {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Card Actions */
.category-actions {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 5px;
    opacity: 0;
    transition: opacity 0.2s;
}

.category-card-enhanced:hover .category-actions {
    opacity: 1;
}

.action-btn {
    background: rgba(255,255,255,0.95);
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    color: #50575e;
}

.action-btn:hover {
    background: #2271b1;
    color: white;
    border-color: #2271b1;
}

.action-btn.delete-category:hover {
    background: #d63638;
    border-color: #d63638;
}

/* Grid View Specific Styles */
.categories-container.grid-view .card-main-content {
    flex-direction: column;
    gap: 15px;
}

.categories-container.grid-view .category-visual {
    align-self: center;
}

.categories-container.grid-view .category-content {
    text-align: center;
}

/* List View Modifications */
.categories-container.list-view .category-card-enhanced {
    min-height: 120px;
}

.categories-container.list-view .card-main-content {
    flex-direction: row;
    align-items: center;
}

.categories-container.list-view .category-visual {
    width: 80px;
    height: 60px;
    margin-right: 20px;
    flex-shrink: 0;
    border-radius: 8px;
}

.categories-container.list-view .category-icon-large {
    font-size: 24px;
}

.categories-container.list-view .category-content {
    flex: 1;
    text-align: left;
}

.categories-container.list-view .category-stats {
    margin-left: 20px;
    margin-right: 0;
}

/* Modal Styles */
.swipecommerce-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90%;
    overflow: auto;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.modal-header {
    padding: 20px 20px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 20px;
}

.modal-header h2 {
    margin: 0;
    padding-bottom: 15px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s;
}

.modal-close:hover {
    background: #f0f0f0;
}

.modal-body {
    padding: 0 20px 20px 20px;
}

.form-row {
    margin-bottom: 20px;
}

.form-row label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Gradient Previews */
.gradient-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.gradient-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.gradient-green { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.gradient-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.gradient-orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

/* Responsive */
@media (max-width: 768px) {
    .visual-manager-header {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }

    .categories-container.grid-view {
        grid-template-columns: 1fr;
    }

    .categories-container.list-view .category-card-enhanced {
        flex-direction: column;
        text-align: center;
    }

    .categories-container.list-view .category-stats {
        margin: 15px 0 0 0;
    }
}
</style>