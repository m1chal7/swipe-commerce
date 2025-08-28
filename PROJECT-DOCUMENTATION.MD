# SwipeCommerce Pro - Project Documentation

## Overview
SwipeCommerce Pro is a premium WordPress/WooCommerce plugin that transforms product browsing with Netflix-style horizontal sliders. The plugin provides an enhanced shopping experience through smooth animations, category-based organization, and modern UI design.

**Version:** 1.1.4  
**WordPress Compatibility:** 5.0+  
**WooCommerce Compatibility:** 5.0+  
**PHP Requirements:** 7.4+

## Plugin Architecture

### Core Components

The plugin follows a modular architecture with clear separation of concerns:

```
swipecommerce-pro.php (Bootstrap)
├── includes/
│   ├── class-swipecommerce-core.php (Main orchestrator)
│   ├── class-swipecommerce-frontend.php (Shortcode & display logic)
│   ├── class-swipecommerce-categories.php (Custom categories management)
│   ├── class-swipecommerce-ajax.php (AJAX handlers)
│   ├── class-swipecommerce-assets.php (CSS/JS management)
│   ├── class-swipecommerce-settings.php (Plugin settings)
│   └── class-swipecommerce-admin.php (Admin interface)
├── admin/partials/ (Admin UI templates)
└── public/assets/ (Frontend CSS/JS)
```

### Class Hierarchy

1. **SwipeCommerce_Bootstrap** (swipecommerce-pro.php:23)
   - Main plugin initialization
   - WordPress hooks registration
   - Requirements validation
   - Default categories setup

2. **SwipeCommerce_Core** (class-swipecommerce-core.php:21)
   - Component orchestration
   - Singleton pattern implementation
   - WooCommerce integration management

3. **SwipeCommerce_Frontend** (class-swipecommerce-frontend.php:20)
   - Shortcode processing: `[swipecommerce_slider]`
   - HTML rendering for product sliders
   - Multiple display modes (single/multi-category)

4. **SwipeCommerce_Categories** (class-swipecommerce-categories.php:20)
   - Custom category CRUD operations
   - Product search and assignment
   - Category validation and ordering

5. **SwipeCommerce_Ajax** (class-swipecommerce-ajax.php:20)
   - Frontend: Add to cart functionality
   - Admin: Category management operations
   - Product search API

6. **SwipeCommerce_Assets** (class-swipecommerce-assets.php:20)
   - Smart asset loading (conditional)
   - Frontend/admin asset management
   - Shortcode detection in widgets

7. **SwipeCommerce_Settings** (class-swipecommerce-settings.php:20)
   - WordPress Settings API integration
   - Configuration validation
   - Default values management

8. **SwipeCommerce_Admin** (class-swipecommerce-admin.php:20)
   - Admin menu creation
   - Form processing
   - Admin page routing

## Key Features

### Frontend Features
- **Netflix-style Horizontal Sliders**: Smooth scrolling product showcase
- **Custom Categories**: Curated product collections (Best Sellers, Staff Picks, etc.)
- **AJAX Add to Cart**: No-refresh shopping experience
- **Responsive Design**: Mobile-first approach
- **Category Filtering**: Quick product filtering
- **Progress Indicators**: Visual scroll progress
- **Mouse & Touch Support**: Drag scrolling capability

### Admin Features
- **Category Management**: Visual category builder
- **Product Assignment**: Search and assign products to categories
- **Product Ordering**: Drag-and-drop product ordering within categories
- **Drag & Drop Ordering**: Intuitive category reordering
- **Bulk Actions**: Enable/disable/delete multiple categories
- **Color Schemes**: Pre-defined gradient themes
- **Settings Panel**: Comprehensive configuration options

## Database Schema

### WordPress Options
- `swipecommerce_custom_categories`: Array of custom category data
- `swipecommerce_default_limit`: Default product limit (default: 12)
- `swipecommerce_default_columns`: Column count (default: 4)
- `swipecommerce_enable_filters`: Filter buttons visibility (default: true)
- `swipecommerce_enable_cart_ajax`: AJAX cart functionality (default: true)
- `swipecommerce_always_load_assets`: Force asset loading (default: false)
- `swipecommerce_primary_color`: Theme primary color (default: #2271b1)
- `swipecommerce_secondary_color`: Theme secondary color (default: #50575e)

### Category Data Structure
```php
array(
    'id' => 'category-slug',
    'name' => 'Display Name',
    'description' => 'Category description',
    'color_scheme' => 'gradient-pink|blue|green|purple|orange',
    'icon' => '🏆', // Emoji icon
    'order' => 1, // Display order
    'products' => [123, 456, 789], // WooCommerce product IDs
    'visibility' => true // Show/hide category
)
```

## Shortcode Usage

### Basic Usage
```php
[swipecommerce_slider]
```

### Advanced Parameters
```php
[swipecommerce_slider 
    limit="12" 
    columns="4" 
    category="electronics" 
    custom_category="bestsellers"
    type="featured|sale|recent"
    show_filters="1"
    show_custom_categories="1"
    title="Custom Title"
    description="Custom Description"
]
```

## API Endpoints

### Frontend AJAX
- `swipecommerce_add_to_cart`: Add product to WooCommerce cart

### Admin AJAX
- `swipecommerce_save_category`: Save/update category data
- `swipecommerce_save_category_order`: Update category display order
- `swipecommerce_delete_category`: Remove category
- `swipecommerce_search_products`: Product search for assignment
- `swipecommerce_bulk_category_action`: Bulk enable/disable/delete
- `swipecommerce_toggle_category_visibility`: Toggle single category visibility
- `swipecommerce_get_product_data`: Retrieve individual product data for category editing

## File Structure

### Core Files
```
swipecommerce-pro.php                    # Main plugin file (bootstrap)
├── includes/                            # PHP classes
│   ├── class-swipecommerce-core.php    # Main orchestrator
│   ├── class-swipecommerce-frontend.php # Shortcode rendering
│   ├── class-swipecommerce-categories.php # Category management
│   ├── class-swipecommerce-ajax.php    # AJAX handlers
│   ├── class-swipecommerce-assets.php  # Asset management
│   ├── class-swipecommerce-settings.php # Settings API
│   └── class-swipecommerce-admin.php   # Admin interface
├── admin/                               # Admin interface
│   └── partials/                        # Admin templates
│       ├── admin-display.php           # Main settings page
│       ├── categories-display.php      # Category management page
│       ├── categories-list.php         # Category list table
│       ├── categories-styles.php       # Admin CSS
│       ├── categories-scripts.php      # Admin JavaScript
│       ├── category-form.php           # Category edit form
│       ├── product-selector.php        # Product assignment UI
│       └── product-selector-styles.php # Product selector CSS
└── public/                              # Frontend assets
    └── assets/
        ├── css/
        │   ├── swipecommerce-minimal.css # Main stylesheet
        │   └── swipecommerce-public.css  # Alternative stylesheet
        └── js/
            ├── swipecommerce-minimal.js  # Main JavaScript
            └── swipecommerce-public.js   # Alternative JavaScript
```

### Supporting Files
```
CLAUDE.md                                # Development instructions
plugin-plan.md                          # Plugin planning document
error-log.md                            # Error tracking
test.md                                 # Testing notes
swipe-slider-html-demo.html             # Static HTML demo
```

## Security Features

### Nonce Verification
- Frontend: `swipecommerce_nonce`
- Admin: `swipecommerce_admin_nonce`

### Capability Checks
- Admin functions require `manage_woocommerce` or `manage_options`
- All user inputs are sanitized using WordPress functions

### Data Sanitization
- `sanitize_key()` for category IDs
- `sanitize_text_field()` for text inputs
- `sanitize_textarea_field()` for descriptions
- `wp_kses()` for HTML content
- `intval()` for numeric values

## Performance Optimizations

### Smart Asset Loading
- Assets load only when shortcode is detected
- Widget content scanning for dynamic shortcodes
- Conditional loading on shop/category pages
- Option to force asset loading if needed

### Database Optimization
- Single option storage for all categories
- Efficient product queries using WooCommerce API
- Caching-friendly data structures

### JavaScript Optimizations
- Event delegation for dynamic content
- Throttled scroll events
- Optimized DOM queries with caching

## Hooks and Filters

### Actions
- `plugins_loaded`: Plugin initialization
- `admin_menu`: Admin interface setup
- `admin_init`: Settings registration
- `wp_enqueue_scripts`: Frontend asset loading
- `admin_enqueue_scripts`: Admin asset loading

### Filters
- Available for future extensibility
- Custom category data filtering
- Asset loading conditions

## Styling System

### CSS Variables
```css
.swipecommerce-slider-wrapper {
    --swipecommerce-primary: #2271b1;
    --swipecommerce-secondary: #50575e;
}
```

### Color Schemes
- `gradient-pink`: Pink gradient theme
- `gradient-blue`: Blue gradient theme  
- `gradient-green`: Green gradient theme
- `gradient-purple`: Purple gradient theme
- `gradient-orange`: Orange gradient theme

### Responsive Breakpoints
- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: < 768px

## Development Guidelines

### Code Standards
- WordPress Coding Standards
- PSR-4 autoloading structure
- PHPDoc documentation
- Semantic versioning

### Security Best Practices
- Input sanitization on all user data
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Prepared statements for any custom queries

### Performance Guidelines
- Minimize database queries
- Use WooCommerce native functions
- Implement proper caching strategies
- Optimize asset loading

## Testing

### Manual Testing Checklist
- [ ] Plugin activation/deactivation
- [ ] WooCommerce dependency check
- [ ] PHP version compatibility
- [ ] Shortcode rendering in posts/pages
- [ ] Category management (CRUD operations)
- [ ] Product assignment functionality
- [ ] AJAX add to cart
- [ ] Responsive design testing
- [ ] Admin interface functionality

### Browser Compatibility
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Troubleshooting

### Common Issues
1. **Assets not loading**: Check shortcode detection and asset loading settings
2. **AJAX errors**: Verify nonce generation and user capabilities
3. **Category not saving**: Check form validation and sanitization
4. **Products not displaying**: Verify WooCommerce product status and visibility

### Debug Mode
Enable WordPress debug mode for detailed error logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Version History

### 1.1.4 (Current)
- **MOBILE UI/UX OPTIMIZATION**: Comprehensive mobile interface optimization
- **Issue**: Mobile interface was wasting significant screen space with excessive padding, large font sizes, and oversized elements
- **Improvements**: 
  - Reduced main container padding from 15px to 8px (mobile) and 5px (small mobile)
  - Optimized product card dimensions from 160px to 140px width (130px on small mobile)
  - Compressed typography sizes throughout the interface
  - Tightened gaps between elements for better space utilization
  - Enhanced navigation elements with more compact sizing
  - Added responsive breakpoint for screens under 480px
- **Impact**: Significantly improved mobile user experience with better space utilization and more products visible horizontally

### 1.1.3
- **CRITICAL BUG FIX**: Fixed category position reset on ANY update (comprehensive fix)
- **Issue**: Every time "Update Category" was clicked, category jumped to first position
- **Root Cause Analysis**: 
  - Categories are sorted by `order` field in `get_custom_categories()` (line 48)
  - Both save paths defaulted order to `1` when form didn't send `category_order` field
  - Form template shows order but doesn't include it in submission
- **Solution**: Modified both save handlers to preserve existing category order when editing:
  - Admin form handler: `class-swipecommerce-admin.php:193`
  - AJAX handler: `class-swipecommerce-ajax.php:130`
- **Impact**: Categories now maintain their position during any update operation

### 1.1.2
- **CRITICAL BUG FIX**: Fixed category position reset when updating product order
- **Issue**: When editing category products, category order defaulted to 1 if not explicitly sent in request
- **Root Cause**: AJAX handler was using `$_POST['order'] ?? 1` instead of preserving existing category order
- **Solution**: Modified AJAX handler to preserve existing category order when editing
- **Impact**: Categories now maintain their position when product order is updated
- **Fixed in**: `class-swipecommerce-ajax.php:130`

### 1.1.1
- **CRITICAL BUG FIX**: Fixed category reordering issue after product updates
- **Issue**: After reordering products within a category and saving, the category would move to first position in the category list
- **Root Cause**: AJAX handler was missing `_editing` flag detection, causing validation to fail and categories to be treated as new
- **Solution**: Added proper edit detection logic to AJAX handler matching the admin form handler
- **Impact**: Category order now remains stable when updating product order within categories
- **Fixed in**: `class-swipecommerce-ajax.php:120-137`

### 1.1.0
- **CRITICAL BUG FIX**: Fixed product ordering not persisting after save
- **Issue**: Validation was failing with "duplicate ID" error when editing existing categories
- **Root Cause**: Missing `_editing` flag in validation for edit operations
- **Solution**: Added proper edit/create detection and validation bypass for existing categories
- **Impact**: Drag-and-drop product ordering now persists correctly

### 1.0.9
- **Product Ordering**: Drag-and-drop product ordering within categories
- **Admin UI Fix**: Fixed selected products visibility bug when editing categories
- **Enhanced UX**: Improved sortable interface with visual feedback
- **Better Product Management**: Maintained product order in frontend display

### 1.0.8
- Documentation improvements
- Minor bug fixes

### 1.0.7
- Stable release with full functionality
- Custom category management
- Netflix-style slider interface
- Complete admin panel
- AJAX functionality
- Responsive design

## Future Enhancements

### Planned Features
- Block editor (Gutenberg) integration
- Additional animation effects
- Advanced filtering options
- Product comparison features
- Analytics integration
- Multi-language support

### API Extensions
- REST API endpoints
- Webhook integration
- Third-party plugin compatibility
- Custom post type support

## Support and Documentation

### File Locations
- Main documentation: `PROJECT-DOCUMENTATION.MD`
- Development notes: `CLAUDE.md`
- Planning document: `plugin-plan.md`
- Error logs: `error-log.md`

### Key Functions Reference
- Shortcode rendering: `SwipeCommerce_Frontend::render_shortcode()` (class-swipecommerce-frontend.php:57)
- Category management: `SwipeCommerce_Categories::get_custom_categories()` (class-swipecommerce-categories.php:38)
- AJAX handling: `SwipeCommerce_Ajax::ajax_add_to_cart()` (class-swipecommerce-ajax.php:67)
- Settings management: `SwipeCommerce_Settings::get_all_settings()` (class-swipecommerce-settings.php:365)

## License
This is a premium WordPress plugin. All rights reserved.

---

*Last updated: [Current Date]*  
*Documentation Version: 1.0*