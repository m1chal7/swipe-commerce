/**
 * SwipeCommerce Pro - Public JavaScript
 * Based on the HTML demo functionality
 */

(function($) {
    'use strict';

    class SwipeCommerceSlider {
        constructor(element, options = {}) {
            this.element = element;
            this.options = {
                autoplay: false,
                speed: 3000,
                ...options
            };

            this.sliderTrack = this.element.querySelector('.swipecommerce-slider-track');
            this.prevBtn = this.element.querySelector('.swipecommerce-prev');
            this.nextBtn = this.element.querySelector('.swipecommerce-next');
            this.progressBar = this.element.querySelector('.swipecommerce-scroll-progress-bar');
            this.navPills = this.element.querySelectorAll('.swipecommerce-nav-pill');
            this.filterBtns = this.element.querySelectorAll('.swipecommerce-filter-btn');
            this.recentlyViewed = this.element.querySelector('.swipecommerce-recently-viewed');
            this.recentItems = this.element.querySelector('.swipecommerce-recently-viewed-items');

            this.isScrolling = false;
            this.viewedProducts = JSON.parse(localStorage.getItem('swipecommerce_viewed') || '[]');
            this.activeFilters = [];

            this.init();
        }

        init() {
            this.bindEvents();
            this.updateButtons();
            this.updateScrollProgress();
            this.updateRecentlyViewed();
            this.initQuantitySelectors();
            this.initAddToCartButtons();
            this.initProductTracking();
            this.initTouchSupport();

            if (this.options.autoplay) {
                this.startAutoplay();
            }
        }

        bindEvents() {
            // Navigation buttons
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => this.scrollPrev());
            }
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => this.scrollNext());
            }

            // Scroll tracking
            if (this.sliderTrack) {
                this.sliderTrack.addEventListener('scroll', () => {
                    if (!this.isScrolling) {
                        this.updateButtons();
                        this.updateScrollProgress();
                    }
                });
            }

            // Category navigation
            this.navPills.forEach(pill => {
                pill.addEventListener('click', (e) => this.navigateToCategory(e));
            });

            // Filter functionality
            this.filterBtns.forEach(btn => {
                btn.addEventListener('click', (e) => this.toggleFilter(e));
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.scrollPrev();
                if (e.key === 'ArrowRight') this.scrollNext();
            });

            // Window resize
            window.addEventListener('resize', () => {
                this.updateButtons();
                this.updateScrollProgress();
            });
        }

        initTouchSupport() {
            if (!this.sliderTrack) return;

            let touchStartX = 0;
            
            this.sliderTrack.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            }, { passive: true });

            this.sliderTrack.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        this.scrollNext();
                    } else {
                        this.scrollPrev();
                    }
                }
            }, { passive: true });
        }

        smoothScroll(target, duration = 400) {
            if (!this.sliderTrack) return;

            const start = this.sliderTrack.scrollLeft;
            const change = target - start;
            const startTime = performance.now();

            const animateScroll = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                const easeInOutCubic = progress < 0.5
                    ? 4 * progress * progress * progress
                    : 1 - Math.pow(-2 * progress + 2, 3) / 2;

                this.sliderTrack.scrollLeft = start + change * easeInOutCubic;

                if (progress < 1) {
                    requestAnimationFrame(animateScroll);
                } else {
                    this.isScrolling = false;
                    this.updateButtons();
                }
            };

            this.isScrolling = true;
            requestAnimationFrame(animateScroll);
        }

        scrollNext() {
            if (this.isScrolling || !this.sliderTrack) return;

            const currentScroll = this.sliderTrack.scrollLeft;
            const scrollAmount = this.getScrollAmount();
            const maxScroll = this.sliderTrack.scrollWidth - this.sliderTrack.clientWidth;
            const targetScroll = Math.min(currentScroll + scrollAmount, maxScroll);

            this.smoothScroll(targetScroll);
        }

        scrollPrev() {
            if (this.isScrolling || !this.sliderTrack) return;

            const currentScroll = this.sliderTrack.scrollLeft;
            const scrollAmount = this.getScrollAmount();
            const targetScroll = Math.max(currentScroll - scrollAmount, 0);

            this.smoothScroll(targetScroll);
        }

        getScrollAmount() {
            return this.sliderTrack ? this.sliderTrack.offsetWidth * 0.8 : 0;
        }

        updateButtons() {
            if (!this.sliderTrack || !this.prevBtn || !this.nextBtn) return;

            const scrollLeft = this.sliderTrack.scrollLeft;
            const scrollWidth = this.sliderTrack.scrollWidth;
            const clientWidth = this.sliderTrack.clientWidth;

            this.prevBtn.disabled = scrollLeft <= 0;
            this.nextBtn.disabled = scrollLeft >= scrollWidth - clientWidth - 10;
        }

        updateScrollProgress() {
            if (!this.sliderTrack || !this.progressBar) return;

            const scrollLeft = this.sliderTrack.scrollLeft;
            const scrollWidth = this.sliderTrack.scrollWidth - this.sliderTrack.clientWidth;
            const scrollPercent = scrollWidth > 0 ? (scrollLeft / scrollWidth) * 100 : 0;
            
            this.progressBar.style.width = Math.max(20, scrollPercent) + '%';

            // Update active nav pill
            const sections = this.element.querySelectorAll('.swipecommerce-category-section');
            const trackRect = this.sliderTrack.getBoundingClientRect();

            sections.forEach((section, index) => {
                const rect = section.getBoundingClientRect();
                if (rect.left >= trackRect.left && rect.left < trackRect.left + trackRect.width / 2) {
                    this.navPills.forEach(pill => pill.classList.remove('active'));
                    if (this.navPills[index]) {
                        this.navPills[index].classList.add('active');
                    }
                }
            });
        }

        navigateToCategory(e) {
            const category = e.target.dataset.category;
            const section = this.element.querySelector(`.swipecommerce-category-section[data-category="${category}"]`);

            if (section && this.sliderTrack) {
                const sectionLeft = section.offsetLeft - this.sliderTrack.offsetLeft;
                this.smoothScroll(sectionLeft - 60, 500);

                this.navPills.forEach(pill => pill.classList.remove('active'));
                e.target.classList.add('active');
            }
        }

        toggleFilter(e) {
            const filter = e.target.dataset.filter;
            e.target.classList.toggle('active');

            if (e.target.classList.contains('active')) {
                this.activeFilters.push(filter);
            } else {
                this.activeFilters = this.activeFilters.filter(f => f !== filter);
            }

            this.applyFilters();
        }

        applyFilters() {
            const cards = this.element.querySelectorAll('.swipecommerce-product-card');

            cards.forEach(card => {
                if (this.activeFilters.length === 0) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                } else {
                    let shouldShow = false;

                    this.activeFilters.forEach(filter => {
                        const tags = card.dataset.tags ? card.dataset.tags.split(',') : [];
                        const price = parseFloat(card.dataset.price) || 0;

                        switch (filter) {
                            case 'sale':
                                if (tags.includes('sale')) shouldShow = true;
                                break;
                            case 'new':
                                if (tags.includes('new')) shouldShow = true;
                                break;
                            case 'under30':
                                if (price < 30) shouldShow = true;
                                break;
                            case 'bestseller':
                                if (tags.includes('bestseller')) shouldShow = true;
                                break;
                            case 'featured':
                                if (tags.includes('featured')) shouldShow = true;
                                break;
                        }
                    });

                    if (shouldShow) {
                        card.style.display = 'block';
                        card.style.opacity = '1';
                    } else {
                        card.style.opacity = '0.3';
                    }
                }
            });
        }

        initQuantitySelectors() {
            this.element.querySelectorAll('.swipecommerce-qty-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const input = btn.parentElement.querySelector('.swipecommerce-qty-input');
                    let value = parseInt(input.value) || 1;

                    if (btn.classList.contains('swipecommerce-plus')) {
                        value = Math.min(value + 1, 10);
                    } else {
                        value = Math.max(value - 1, 1);
                    }

                    input.value = value;
                });
            });
        }

        initAddToCartButtons() {
            this.element.querySelectorAll('.swipecommerce-add-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.handleAddToCart(btn);
                });
            });
        }

        async handleAddToCart(button) {
            const productId = button.dataset.productId;
            const quantity = button.parentElement.querySelector('.swipecommerce-qty-input').value;
            const originalText = button.textContent;

            try {
                // Optimistic UI update
                button.textContent = `✓ Added ${quantity}`;
                button.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                button.disabled = true;

                // AJAX request
                const formData = new FormData();
                formData.append('action', 'woocommerce_add_to_cart');
                formData.append('product_id', productId);
                formData.append('quantity', quantity);

                const response = await fetch(swipecommerce_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                // Update cart count if fragments are present
                if (data.fragments) {
                    Object.keys(data.fragments).forEach(key => {
                        const elements = document.querySelectorAll(key);
                        elements.forEach(el => el.innerHTML = data.fragments[key]);
                    });
                }

                // Trigger cart updated event
                $(document.body).trigger('added_to_cart', [data.fragments, data.cart_hash, button]);

                // Track conversion
                this.trackEvent('add_cart', { product_id: productId, quantity: quantity });

            } catch (error) {
                console.error('Add to cart failed:', error);
                button.textContent = 'Error';
                button.style.background = 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
            } finally {
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                    button.disabled = false;
                }, 2000);
            }
        }

        initProductTracking() {
            this.element.querySelectorAll('.swipecommerce-product-card').forEach(card => {
                // Intersection Observer for view tracking
                if (window.IntersectionObserver) {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const productId = entry.target.dataset.productId;
                                this.trackEvent('view', { product_id: productId });
                                observer.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.5 });

                    observer.observe(card);
                }

                // Click tracking for recently viewed
                card.addEventListener('click', () => {
                    const productName = card.querySelector('.swipecommerce-product-name a').textContent.trim();
                    const productImage = card.querySelector('.swipecommerce-product-image img, .swipecommerce-product-placeholder');
                    const productEmoji = productImage ? (productImage.alt || productImage.textContent || '📦') : '📦';
                    const productId = card.dataset.productId;

                    // Add to recently viewed
                    const viewedProduct = { id: productId, name: productName, emoji: productEmoji.charAt(0) };
                    this.viewedProducts = this.viewedProducts.filter(p => p.id !== productId);
                    this.viewedProducts.unshift(viewedProduct);
                    this.viewedProducts = this.viewedProducts.slice(0, 5);

                    localStorage.setItem('swipecommerce_viewed', JSON.stringify(this.viewedProducts));
                    this.updateRecentlyViewed();

                    // Track click
                    this.trackEvent('click', { product_id: productId });
                });
            });
        }

        updateRecentlyViewed() {
            if (!this.recentlyViewed || !this.recentItems) return;

            if (this.viewedProducts.length > 0) {
                this.recentlyViewed.classList.add('show');
                this.recentItems.innerHTML = this.viewedProducts.map(p =>
                    `<div class="swipecommerce-recent-item" title="${p.name}" data-product-id="${p.id}">${p.emoji}</div>`
                ).join('');

                // Add click handlers to recent items
                this.recentItems.querySelectorAll('.swipecommerce-recent-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const productCard = this.element.querySelector(`[data-product-id="${item.dataset.productId}"]`);
                        if (productCard) {
                            productCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            productCard.style.animation = 'pulse 0.5s ease-in-out';
                        }
                    });
                });
            }
        }

        trackEvent(eventType, data = {}) {
            if (typeof swipecommerce_ajax === 'undefined') return;

            const trackingData = {
                action: 'swipecommerce_track_event',
                nonce: swipecommerce_ajax.nonce,
                event_type: eventType,
                ...data
            };

            // Send asynchronously without blocking UI
            fetch(swipecommerce_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(trackingData)
            }).catch(error => console.log('Tracking error:', error));
        }

        startAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
            }

            this.autoplayInterval = setInterval(() => {
                if (!this.isScrolling) {
                    this.scrollNext();
                    
                    // Reset to beginning if at end
                    if (this.nextBtn && this.nextBtn.disabled) {
                        setTimeout(() => {
                            this.smoothScroll(0, 800);
                        }, 2000);
                    }
                }
            }, this.options.speed);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }

        destroy() {
            this.stopAutoplay();
            // Remove event listeners and clean up
        }
    }

    // Initialize sliders when DOM is ready
    function initializeSliders() {
        document.querySelectorAll('.swipecommerce-slider-wrapper').forEach(element => {
            const config = element.dataset.config ? JSON.parse(element.dataset.config) : {};
            new SwipeCommerceSlider(element, config);
        });
    }

    // jQuery integration for compatibility
    $.fn.swipecommerceSlider = function(options) {
        return this.each(function() {
            new SwipeCommerceSlider(this, options);
        });
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSliders);
    } else {
        initializeSliders();
    }

    // Re-initialize on AJAX content load (for themes that use AJAX)
    $(document).on('wc_fragments_refreshed wc_fragments_loaded', initializeSliders);

})(jQuery);