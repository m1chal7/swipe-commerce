/**
 * SwipeCommerce Pro - Enhanced JavaScript to match HTML demo
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initSwipeCommerceSliders();
    });

    function initSwipeCommerceSliders() {
        $('.swipecommerce-slider-wrapper').each(function() {
            const wrapper = $(this);
            const track = wrapper.find('.swipecommerce-slider-track');
            
            if (track.length) {
                initSlider(wrapper, track);
                initNavigation(wrapper);
                initFilters(wrapper);
                initQuantitySelectors(wrapper);
                initProgressBar(wrapper);
            }
        });
    }

    function initSlider(wrapper, track) {
        // Smooth scrolling with navigation buttons
        const prevBtn = wrapper.find('.swipecommerce-prev');
        const nextBtn = wrapper.find('.swipecommerce-next');
        
        prevBtn.on('click', function() {
            const scrollAmount = track.find('.swipecommerce-category-section').first().outerWidth() + 40;
            track.animate({scrollLeft: track.scrollLeft() - scrollAmount}, 400, 'swing');
            updateNavigationState(wrapper);
        });

        nextBtn.on('click', function() {
            const scrollAmount = track.find('.swipecommerce-category-section').first().outerWidth() + 40;
            track.animate({scrollLeft: track.scrollLeft() + scrollAmount}, 400, 'swing');
            updateNavigationState(wrapper);
        });

        // Mouse drag scrolling
        let isDown = false;
        let startX;
        let scrollLeft;

        track.on('mousedown', function(e) {
            isDown = true;
            track.addClass('dragging');
            startX = e.pageX - track.offset().left;
            scrollLeft = track.scrollLeft();
            e.preventDefault();
        });

        track.on('mouseleave mouseup', function() {
            isDown = false;
            track.removeClass('dragging');
        });

        track.on('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offset().left;
            const walk = (x - startX) * 2;
            track.scrollLeft(scrollLeft - walk);
            updateProgressBar(wrapper);
        });

        // Touch scrolling for mobile
        let touchStartX = 0;
        
        track.on('touchstart', function(e) {
            touchStartX = e.originalEvent.touches[0].clientX;
        });

        track.on('touchmove', function(e) {
            updateProgressBar(wrapper);
        });

        track.on('touchend', function(e) {
            const touchEndX = e.originalEvent.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > 50) {
                const scrollAmount = track.width() * 0.4;
                if (diff > 0) {
                    track.animate({scrollLeft: track.scrollLeft() + scrollAmount}, 300);
                } else {
                    track.animate({scrollLeft: track.scrollLeft() - scrollAmount}, 300);
                }
            }
            updateNavigationState(wrapper);
        });

        // Update states on scroll
        track.on('scroll', function() {
            updateProgressBar(wrapper);
            updateNavigationState(wrapper);
        });

        // Initialize states
        updateNavigationState(wrapper);
        updateProgressBar(wrapper);
    }

    function initNavigation(wrapper) {
        // Category pills navigation
        wrapper.find('.swipecommerce-nav-pill').on('click', function() {
            const pill = $(this);
            const categoryId = pill.data('category');
            
            // Update active state
            wrapper.find('.swipecommerce-nav-pill').removeClass('active');
            pill.addClass('active');
            
            // Always show all category sections, just scroll to the target
            wrapper.find('.swipecommerce-category-section').show();
            
            // Find and scroll to target category section
            if (categoryId === 'all') {
                // Scroll to beginning for "all"
                const track = wrapper.find('.swipecommerce-slider-track');
                track.animate({
                    scrollLeft: 0
                }, 400, 'swing');
            } else {
                // Find specific category section and scroll to it
                const targetSection = wrapper.find('.swipecommerce-category-section[data-category="' + categoryId + '"]');
                if (targetSection.length) {
                    const track = wrapper.find('.swipecommerce-slider-track');
                    const targetPosition = targetSection.position().left + track.scrollLeft();
                    
                    track.animate({
                        scrollLeft: targetPosition
                    }, 400, 'swing');
                }
            }
            
            updateProgressBar(wrapper);
            updateNavigationState(wrapper);
        });
    }

    function initFilters(wrapper) {
        // Quick filter buttons
        wrapper.find('.swipecommerce-filter-btn').on('click', function() {
            const btn = $(this);
            const filter = btn.data('filter');
            
            // Toggle active state
            btn.toggleClass('active');
            
            // Apply filters
            applyFilters(wrapper);
        });
    }

    function applyFilters(wrapper) {
        const activeFilters = [];
        wrapper.find('.swipecommerce-filter-btn.active').each(function() {
            activeFilters.push($(this).data('filter'));
        });
        
        // Apply filters to products within currently visible category sections
        wrapper.find('.swipecommerce-category-section:visible').each(function() {
            const categorySection = $(this);
            let hasVisibleProducts = false;
            
            categorySection.find('.swipecommerce-product-card').each(function() {
                const card = $(this);
                const tags = (card.data('tags') || '').toString().split(',');
                
                if (activeFilters.length === 0) {
                    card.show();
                    hasVisibleProducts = true;
                } else {
                    const hasMatchingTag = activeFilters.some(filter => tags.includes(filter));
                    card.toggle(hasMatchingTag);
                    if (hasMatchingTag) {
                        hasVisibleProducts = true;
                    }
                }
            });
            
            // Hide category section if no products match the filter
            const categoryHeader = categorySection.find('.swipecommerce-category-header');
            if (hasVisibleProducts) {
                categoryHeader.show();
            } else {
                categoryHeader.hide();
            }
        });
        
        updateProgressBar(wrapper);
    }

    function initQuantitySelectors(wrapper) {
        // Quantity selector buttons
        wrapper.on('click', '.swipecommerce-qty-btn', function(e) {
            e.stopPropagation();
            const btn = $(this);
            const input = btn.siblings('.swipecommerce-qty-input');
            const currentValue = parseInt(input.val()) || 1;
            
            if (btn.hasClass('swipecommerce-plus')) {
                const maxValue = parseInt(input.attr('max')) || 10;
                if (currentValue < maxValue) {
                    input.val(currentValue + 1);
                }
            } else if (btn.hasClass('swipecommerce-minus')) {
                const minValue = parseInt(input.attr('min')) || 1;
                if (currentValue > minValue) {
                    input.val(currentValue - 1);
                }
            }
        });

        // Handle add to cart with quantity
        wrapper.on('click', '.swipecommerce-add-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const button = $(this);
            const quantity = button.closest('.swipecommerce-quick-add').find('.swipecommerce-qty-input').val() || 1;
            handleAddToCart(button, quantity);
        });
    }

    function initProgressBar(wrapper) {
        updateProgressBar(wrapper);
    }

    function updateProgressBar(wrapper) {
        const track = wrapper.find('.swipecommerce-slider-track');
        const progressBar = wrapper.find('.swipecommerce-scroll-progress-bar');
        
        if (track.length && progressBar.length) {
            const scrollLeft = track.scrollLeft();
            const scrollWidth = track[0].scrollWidth - track.width();
            const progress = scrollWidth > 0 ? (scrollLeft / scrollWidth) * 100 : 0;
            
            progressBar.css('width', Math.min(100, Math.max(0, progress)) + '%');
        }
    }

    function updateNavigationState(wrapper) {
        const track = wrapper.find('.swipecommerce-slider-track');
        const prevBtn = wrapper.find('.swipecommerce-prev');
        const nextBtn = wrapper.find('.swipecommerce-next');
        
        const scrollLeft = track.scrollLeft();
        const maxScroll = track[0].scrollWidth - track.width();
        
        prevBtn.prop('disabled', scrollLeft <= 0);
        nextBtn.prop('disabled', scrollLeft >= maxScroll - 1);
    }

    function handleAddToCart(button, quantity = 1) {
        if (typeof swipecommerce_ajax === 'undefined') {
            // Fallback to regular link behavior
            const url = button.data('add-to-cart-url');
            if (url) {
                window.location.href = url;
            }
            return;
        }

        const productId = button.data('product-id');
        const originalText = button.text();
        const originalBackground = button.css('background');

        // Update button state
        button.text(swipecommerce_ajax.loading_text || 'Adding...')
              .prop('disabled', true)
              .addClass('loading');

        $.ajax({
            url: swipecommerce_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'swipecommerce_add_to_cart',
                product_id: productId,
                quantity: quantity,
                nonce: swipecommerce_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Success animation
                    button.text('✓ Added')
                          .removeClass('loading')
                          .addClass('success')
                          .css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)');
                    
                    // Add success effect
                    button.closest('.swipecommerce-product-card').addClass('added-to-cart');
                    
                    // Update cart count if element exists
                    if (response.data.cart_count) {
                        $('.cart-count, .wc-cart-count, .cart-contents-count').text(response.data.cart_count);
                        $('.header-cart-count').text(response.data.cart_count).show();
                    }

                    // Trigger WooCommerce events
                    $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash, button]);
                    
                    // Show mini cart if available
                    if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.cart_redirect_after_add === 'no') {
                        $(document.body).trigger('wc_fragment_refresh');
                    }
                } else {
                    button.text('Error')
                          .removeClass('loading')
                          .addClass('error')
                          .css('background', '#ef4444');
                    console.error('Add to cart failed:', response.data);
                }
            },
            error: function(xhr, status, error) {
                button.text('Error')
                      .removeClass('loading')
                      .addClass('error')
                      .css('background', '#ef4444');
                console.error('AJAX error:', error);
            },
            complete: function() {
                // Reset button after delay
                setTimeout(function() {
                    button.text(originalText)
                          .css('background', originalBackground)
                          .prop('disabled', false)
                          .removeClass('loading success error');
                    
                    button.closest('.swipecommerce-product-card').removeClass('added-to-cart');
                }, 2500);
            }
        });
    }

    // Product card hover effects
    function initProductCardEffects() {
        $('.swipecommerce-product-card').on('mouseenter', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', function() {
            $(this).removeClass('hovered');
        });
    }

    // Smooth scrolling for category navigation
    function scrollToCategory(wrapper, categorySection) {
        const track = wrapper.find('.swipecommerce-slider-track');
        const targetPosition = categorySection.position().left + track.scrollLeft();
        
        track.animate({
            scrollLeft: targetPosition
        }, 600, 'swing');
    }

    // Initialize all enhanced features
    $(document).ready(function() {
        initProductCardEffects();
        
        // Add CSS classes for animations
        $('<style>')
            .text(`
                .swipecommerce-slider-track.dragging { cursor: grabbing !important; }
                .swipecommerce-product-card.added-to-cart { transform: scale(1.02); }
                .swipecommerce-add-btn.loading { position: relative; overflow: hidden; }
                .swipecommerce-add-btn.loading::after { 
                    content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                    animation: loading-shine 1.5s infinite; 
                }
                @keyframes loading-shine { 0% { left: -100%; } 100% { left: 100%; } }
                .swipecommerce-product-card.hovered { transform: translateY(-4px) scale(1.01); }
            `)
            .appendTo('head');
    });

    // Re-initialize on AJAX content updates
    $(document).on('wc_fragments_refreshed wc_fragments_loaded', function() {
        setTimeout(function() {
            initSwipeCommerceSliders();
            initProductCardEffects();
        }, 100);
    });

    // Handle window resize
    $(window).on('resize', debounce(function() {
        $('.swipecommerce-slider-wrapper').each(function() {
            updateProgressBar($(this));
            updateNavigationState($(this));
        });
    }, 250));

    // Debounce utility function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})(jQuery);