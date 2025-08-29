/**
 * Holiday Packages Component - Frontend JavaScript
 * Version 2.0.0
 * Modern interactive features for travel booking experience
 */

(function() {
    'use strict';

    // Main HolidayPackages object
    window.HolidayPackages = window.HolidayPackages || {};

    // Configuration
    HolidayPackages.config = {
        ajaxUrl: 'index.php?option=com_holidaypackages&task=ajax',
        token: document.querySelector('input[name="' + Joomla.getOptions('csrf.token', '') + '"]')?.value || '',
        loadingClass: 'hp-loading',
        fadeClass: 'hp-fade-in'
    };

    /**
     * Utility functions
     */
    HolidayPackages.utils = {
        
        // Show loading spinner
        showLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                element.innerHTML = '<div class="hp-loading"><div class="hp-spinner"></div></div>';
            }
        },

        // Hide loading spinner
        hideLoading: function(element) {
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }
            if (element) {
                const loading = element.querySelector('.hp-loading');
                if (loading) {
                    loading.remove();
                }
            }
        },

        // Show message
        showMessage: function(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.hp-messages') || document.body;
            container.insertBefore(alertDiv, container.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        },

        // Format currency
        formatCurrency: function(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // AJAX request
        ajax: function(data, callback, errorCallback) {
            data.token = HolidayPackages.config.token;
            
            fetch(HolidayPackages.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (callback) callback(result.data, result.message);
                } else {
                    if (errorCallback) {
                        errorCallback(result.message);
                    } else {
                        HolidayPackages.utils.showMessage(result.message, 'danger');
                    }
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                const message = 'An error occurred. Please try again.';
                if (errorCallback) {
                    errorCallback(message);
                } else {
                    HolidayPackages.utils.showMessage(message, 'danger');
                }
            });
        }
    };

    /**
     * Package listing and search functionality
     */
    HolidayPackages.packages = {
        
        // Initialize package listing
        init: function() {
            this.initFilters();
            this.initSearch();
            this.initSort();
            this.initWishlist();
            this.initLoadMore();
            this.initPackageCards();
        },

        // Initialize filters
        initFilters: function() {
            const filterForm = document.querySelector('.hp-filters-form');
            if (!filterForm) return;

            const filterInputs = filterForm.querySelectorAll('select, input[type="range"]');
            
            filterInputs.forEach(input => {
                input.addEventListener('change', HolidayPackages.utils.debounce(() => {
                    this.applyFilters();
                }, 300));
            });

            // Price range slider
            const priceRange = document.querySelector('#price-range');
            if (priceRange) {
                priceRange.addEventListener('input', HolidayPackages.utils.debounce(() => {
                    document.querySelector('#price-display').textContent = 
                        HolidayPackages.utils.formatCurrency(priceRange.value);
                    this.applyFilters();
                }, 300));
            }
        },

        // Initialize search
        initSearch: function() {
            const searchInput = document.querySelector('.hp-search-input');
            if (!searchInput) return;

            searchInput.addEventListener('input', HolidayPackages.utils.debounce(() => {
                this.applyFilters();
            }, 500));
        },

        // Initialize sorting
        initSort: function() {
            const sortSelect = document.querySelector('.hp-sort-select');
            if (!sortSelect) return;

            sortSelect.addEventListener('change', () => {
                this.applyFilters();
            });
        },

        // Apply filters and search
        applyFilters: function() {
            const form = document.querySelector('.hp-filters-form');
            if (!form) return;

            const formData = new FormData(form);
            const searchParams = new URLSearchParams();
            
            for (let [key, value] of formData.entries()) {
                if (value) searchParams.append(key, value);
            }

            searchParams.append('task', 'getFilteredPackages');
            
            HolidayPackages.utils.showLoading('.hp-packages-grid');
            
            HolidayPackages.utils.ajax(
                Object.fromEntries(searchParams),
                (data) => {
                    this.updatePackageGrid(data.packages);
                    this.updateResultsCount(data.total);
                },
                () => {
                    HolidayPackages.utils.hideLoading('.hp-packages-grid');
                }
            );
        },

        // Update package grid
        updatePackageGrid: function(packages) {
            const grid = document.querySelector('.hp-packages-grid');
            if (!grid) return;

            if (packages.length === 0) {
                grid.innerHTML = '<div class="hp-no-results">No packages found matching your criteria.</div>';
                return;
            }

            grid.innerHTML = packages.map(pkg => this.renderPackageCard(pkg)).join('');
            
            // Reinitialize card interactions
            this.initPackageCards();
        },

        // Render package card
        renderPackageCard: function(pkg) {
            const badges = [];
            if (pkg.featured) badges.push('<span class="hp-badge featured">Featured</span>');
            if (pkg.hot_deal) badges.push('<span class="hp-badge hot-deal">Hot Deal</span>');
            if (pkg.trending) badges.push('<span class="hp-badge trending">Trending</span>');

            const discount = pkg.discount_percentage > 0 ? 
                `<span class="hp-price-original">${HolidayPackages.utils.formatCurrency(pkg.price_adult + pkg.discount_amount, pkg.currency)}</span>` : '';

            return `
                <div class="hp-package-card" data-package-id="${pkg.id}">
                    <div class="hp-package-image">
                        <img src="${pkg.image}" alt="${pkg.title}" loading="lazy">
                        <div class="hp-package-badges">${badges.join('')}</div>
                    </div>
                    <div class="hp-package-content">
                        <h3 class="hp-package-title">
                            <a href="${pkg.url}">${pkg.title}</a>
                        </h3>
                        <div class="hp-package-meta">
                            <span><i class="fas fa-map-marker-alt"></i> ${pkg.destination}</span>
                            <span><i class="fas fa-calendar-alt"></i> ${pkg.duration_days} Days</span>
                            <span><i class="fas fa-tag"></i> ${pkg.package_type}</span>
                        </div>
                        <p class="hp-package-description">${pkg.short_description}</p>
                        ${pkg.rating > 0 ? `
                        <div class="hp-package-rating">
                            <div class="hp-stars">${this.renderStars(pkg.rating)}</div>
                            <span class="hp-rating-text">${pkg.rating}/5 (${pkg.review_count} reviews)</span>
                        </div>
                        ` : ''}
                        <div class="hp-package-footer">
                            <div class="hp-price">
                                <div class="hp-price-current">${HolidayPackages.utils.formatCurrency(pkg.price_adult, pkg.currency)}</div>
                                ${discount}
                                <div class="hp-price-per">per person</div>
                            </div>
                            <div class="hp-package-actions">
                                <a href="${pkg.url}" class="hp-btn hp-btn-primary">View Details</a>
                                <button class="hp-btn hp-btn-outline hp-wishlist-btn" data-package-id="${pkg.id}">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        // Render star rating
        renderStars: function(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            
            let stars = '';
            
            // Full stars
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star"></i>';
            }
            
            // Half star
            if (hasHalfStar) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            }
            
            // Empty stars
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star"></i>';
            }
            
            return stars;
        },

        // Update results count
        updateResultsCount: function(total) {
            const countElement = document.querySelector('.hp-results-count');
            if (countElement) {
                countElement.textContent = `${total} packages found`;
            }
        },

        // Initialize wishlist functionality
        initWishlist: function() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.hp-wishlist-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.hp-wishlist-btn');
                    const packageId = btn.dataset.packageId;
                    
                    if (btn.classList.contains('active')) {
                        this.removeFromWishlist(packageId, btn);
                    } else {
                        this.addToWishlist(packageId, btn);
                    }
                }
            });
        },

        // Add to wishlist
        addToWishlist: function(packageId, btn) {
            HolidayPackages.utils.ajax({
                task: 'addToWishlist',
                package_id: packageId
            }, () => {
                btn.classList.add('active');
                btn.querySelector('i').classList.replace('far', 'fas');
                HolidayPackages.utils.showMessage('Added to wishlist!', 'success');
            });
        },

        // Remove from wishlist
        removeFromWishlist: function(packageId, btn) {
            HolidayPackages.utils.ajax({
                task: 'removeFromWishlist',
                package_id: packageId
            }, () => {
                btn.classList.remove('active');
                btn.querySelector('i').classList.replace('fas', 'far');
                HolidayPackages.utils.showMessage('Removed from wishlist!', 'info');
            });
        },

        // Initialize load more functionality
        initLoadMore: function() {
            const loadMoreBtn = document.querySelector('.hp-load-more-btn');
            if (!loadMoreBtn) return;

            loadMoreBtn.addEventListener('click', () => {
                const offset = document.querySelectorAll('.hp-package-card').length;
                
                HolidayPackages.utils.ajax({
                    task: 'loadMorePackages',
                    offset: offset
                }, (data) => {
                    if (data.packages.length > 0) {
                        const grid = document.querySelector('.hp-packages-grid');
                        const newCards = data.packages.map(pkg => this.renderPackageCard(pkg)).join('');
                        grid.insertAdjacentHTML('beforeend', newCards);
                        
                        // Hide load more button if no more packages
                        if (data.packages.length < data.limit) {
                            loadMoreBtn.style.display = 'none';
                        }
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                });
            });
        },

        // Initialize package card interactions
        initPackageCards: function() {
            // Add hover effects and animations
            const cards = document.querySelectorAll('.hp-package-card');
            cards.forEach(card => {
                // Add fade-in animation
                card.classList.add('hp-fade-in');
                
                // Lazy load images
                const img = card.querySelector('img[loading="lazy"]');
                if (img && 'IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const image = entry.target;
                                image.src = image.dataset.src || image.src;
                                imageObserver.unobserve(image);
                            }
                        });
                    });
                    imageObserver.observe(img);
                }
            });
        }
    };

    /**
     * Package booking functionality
     */
    HolidayPackages.booking = {
        
        currentStep: 1,
        totalSteps: 4,
        formData: {},

        // Initialize booking form
        init: function() {
            this.initStepNavigation();
            this.initTravelerCounters();
            this.initDatePickers();
            this.initPriceCalculation();
            this.initPromoCode();
            this.initFormValidation();
        },

        // Initialize step navigation
        initStepNavigation: function() {
            const nextBtns = document.querySelectorAll('.hp-btn-next');
            const prevBtns = document.querySelectorAll('.hp-btn-prev');

            nextBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (this.validateCurrentStep()) {
                        this.nextStep();
                    }
                });
            });

            prevBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    this.prevStep();
                });
            });
        },

        // Go to next step
        nextStep: function() {
            if (this.currentStep < this.totalSteps) {
                this.hideStep(this.currentStep);
                this.currentStep++;
                this.showStep(this.currentStep);
                this.updateStepIndicator();
                
                if (this.currentStep === 3) {
                    this.updateBookingSummary();
                }
            }
        },

        // Go to previous step
        prevStep: function() {
            if (this.currentStep > 1) {
                this.hideStep(this.currentStep);
                this.currentStep--;
                this.showStep(this.currentStep);
                this.updateStepIndicator();
            }
        },

        // Show step
        showStep: function(stepNumber) {
            const step = document.querySelector(`[data-step="${stepNumber}"]`);
            if (step) {
                step.style.display = 'block';
            }
        },

        // Hide step
        hideStep: function(stepNumber) {
            const step = document.querySelector(`[data-step="${stepNumber}"]`);
            if (step) {
                step.style.display = 'none';
            }
        },

        // Update step indicator
        updateStepIndicator: function() {
            const steps = document.querySelectorAll('.hp-step');
            steps.forEach((step, index) => {
                const stepNum = index + 1;
                if (stepNum < this.currentStep) {
                    step.classList.add('completed');
                    step.classList.remove('active');
                } else if (stepNum === this.currentStep) {
                    step.classList.add('active');
                    step.classList.remove('completed');
                } else {
                    step.classList.remove('active', 'completed');
                }
            });
        },

        // Initialize traveler counters
        initTravelerCounters: function() {
            const counters = document.querySelectorAll('.hp-counter');
            
            counters.forEach(counter => {
                const minusBtn = counter.querySelector('.hp-counter-minus');
                const plusBtn = counter.querySelector('.hp-counter-plus');
                const display = counter.querySelector('.hp-counter-value');
                const input = counter.querySelector('input[type="hidden"]');
                
                minusBtn.addEventListener('click', () => {
                    let value = parseInt(display.textContent);
                    if (value > 0) {
                        value--;
                        display.textContent = value;
                        input.value = value;
                        this.calculatePrice();
                    }
                    this.updateCounterButtons(counter, value);
                });
                
                plusBtn.addEventListener('click', () => {
                    let value = parseInt(display.textContent);
                    const max = parseInt(counter.dataset.max) || 20;
                    if (value < max) {
                        value++;
                        display.textContent = value;
                        input.value = value;
                        this.calculatePrice();
                    }
                    this.updateCounterButtons(counter, value);
                });
                
                // Initialize button states
                this.updateCounterButtons(counter, parseInt(display.textContent));
            });
        },

        // Update counter button states
        updateCounterButtons: function(counter, value) {
            const minusBtn = counter.querySelector('.hp-counter-minus');
            const plusBtn = counter.querySelector('.hp-counter-plus');
            const max = parseInt(counter.dataset.max) || 20;
            
            minusBtn.disabled = value <= 0;
            plusBtn.disabled = value >= max;
        },

        // Initialize date pickers
        initDatePickers: function() {
            const departureDateInput = document.querySelector('#departure-date');
            if (departureDateInput) {
                // Set minimum date to tomorrow
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                departureDateInput.min = tomorrow.toISOString().split('T')[0];
                
                departureDateInput.addEventListener('change', () => {
                    this.calculatePrice();
                    this.checkAvailability();
                });
            }
        },

        // Calculate price
        calculatePrice: function() {
            const formData = new FormData(document.querySelector('.hp-booking-form'));
            
            HolidayPackages.utils.ajax({
                task: 'calculatePrice',
                ...Object.fromEntries(formData)
            }, (data) => {
                this.updatePriceSummary(data);
            });
        },

        // Update price summary
        updatePriceSummary: function(pricing) {
            document.querySelector('.hp-subtotal').textContent = 
                HolidayPackages.utils.formatCurrency(pricing.subtotal, pricing.currency);
            
            document.querySelector('.hp-tax').textContent = 
                HolidayPackages.utils.formatCurrency(pricing.tax, pricing.currency);
            
            document.querySelector('.hp-discount').textContent = 
                HolidayPackages.utils.formatCurrency(pricing.discount, pricing.currency);
            
            document.querySelector('.hp-total').textContent = 
                HolidayPackages.utils.formatCurrency(pricing.total, pricing.currency);
        },

        // Check availability
        checkAvailability: function() {
            const formData = new FormData(document.querySelector('.hp-booking-form'));
            
            HolidayPackages.utils.ajax({
                task: 'checkAvailability',
                ...Object.fromEntries(formData)
            }, (data) => {
                const availabilityMsg = document.querySelector('.hp-availability-message');
                if (availabilityMsg) {
                    if (data.available) {
                        availabilityMsg.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Available for booking</span>';
                    } else {
                        availabilityMsg.innerHTML = '<span class="text-danger"><i class="fas fa-times"></i> Not available for selected dates</span>';
                    }
                }
            });
        },

        // Initialize promo code
        initPromoCode: function() {
            const promoBtn = document.querySelector('.hp-apply-promo');
            if (!promoBtn) return;

            promoBtn.addEventListener('click', () => {
                const promoInput = document.querySelector('#promo-code');
                const promoCode = promoInput.value.trim();
                
                if (!promoCode) {
                    HolidayPackages.utils.showMessage('Please enter a promo code', 'warning');
                    return;
                }
                
                HolidayPackages.utils.ajax({
                    task: 'validatePromoCode',
                    promo_code: promoCode
                }, (data) => {
                    HolidayPackages.utils.showMessage('Promo code applied successfully!', 'success');
                    this.calculatePrice();
                }, (error) => {
                    HolidayPackages.utils.showMessage(error, 'danger');
                });
            });
        },

        // Initialize form validation
        initFormValidation: function() {
            const form = document.querySelector('.hp-booking-form');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                if (this.validateForm()) {
                    this.submitBooking();
                }
            });
        },

        // Validate current step
        validateCurrentStep: function() {
            const currentStepElement = document.querySelector(`[data-step="${this.currentStep}"]`);
            const requiredInputs = currentStepElement.querySelectorAll('[required]');
            
            let isValid = true;
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        },

        // Validate entire form
        validateForm: function() {
            const requiredInputs = document.querySelectorAll('.hp-booking-form [required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            return isValid;
        },

        // Update booking summary
        updateBookingSummary: function() {
            const formData = new FormData(document.querySelector('.hp-booking-form'));
            const summary = document.querySelector('.hp-booking-summary-details');
            
            if (summary) {
                // Update summary with form data
                // This would be implemented based on specific form structure
            }
        },

        // Submit booking
        submitBooking: function() {
            const formData = new FormData(document.querySelector('.hp-booking-form'));
            const submitBtn = document.querySelector('.hp-submit-booking');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            HolidayPackages.utils.ajax({
                task: 'submitBooking',
                ...Object.fromEntries(formData)
            }, (data) => {
                // Redirect to confirmation page
                window.location.href = data.redirect_url;
            }, (error) => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Complete Booking';
                HolidayPackages.utils.showMessage(error, 'danger');
            });
        }
    };

    /**
     * Reviews functionality
     */
    HolidayPackages.reviews = {
        
        // Initialize reviews
        init: function() {
            this.initReviewForm();
            this.initHelpfulVotes();
            this.initLoadMoreReviews();
        },

        // Initialize review form
        initReviewForm: function() {
            const reviewForm = document.querySelector('.hp-review-form');
            if (!reviewForm) return;

            reviewForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitReview();
            });

            // Initialize star rating
            this.initStarRating();
        },

        // Initialize star rating
        initStarRating: function() {
            const starRatings = document.querySelectorAll('.hp-star-rating');
            
            starRatings.forEach(rating => {
                const stars = rating.querySelectorAll('.hp-star');
                const input = rating.querySelector('input[type="hidden"]');
                
                stars.forEach((star, index) => {
                    star.addEventListener('click', () => {
                        const value = index + 1;
                        input.value = value;
                        this.updateStarRating(rating, value);
                    });
                    
                    star.addEventListener('mouseenter', () => {
                        this.updateStarRating(rating, index + 1, true);
                    });
                });
                
                rating.addEventListener('mouseleave', () => {
                    this.updateStarRating(rating, input.value);
                });
            });
        },

        // Update star rating display
        updateStarRating: function(rating, value, hover = false) {
            const stars = rating.querySelectorAll('.hp-star');
            
            stars.forEach((star, index) => {
                star.classList.remove('active', 'hover');
                if (index < value) {
                    star.classList.add(hover ? 'hover' : 'active');
                }
            });
        },

        // Submit review
        submitReview: function() {
            const form = document.querySelector('.hp-review-form');
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.hp-submit-review');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            
            HolidayPackages.utils.ajax({
                task: 'submitReview',
                ...Object.fromEntries(formData)
            }, (data) => {
                HolidayPackages.utils.showMessage('Thank you! Your review has been submitted.', 'success');
                form.reset();
                this.resetStarRatings();
                
                // Refresh reviews list
                this.loadReviews();
            }, (error) => {
                HolidayPackages.utils.showMessage(error, 'danger');
            }).finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Review';
            });
        },

        // Reset star ratings
        resetStarRatings: function() {
            const starRatings = document.querySelectorAll('.hp-star-rating');
            starRatings.forEach(rating => {
                rating.querySelectorAll('.hp-star').forEach(star => {
                    star.classList.remove('active', 'hover');
                });
                const input = rating.querySelector('input[type="hidden"]');
                if (input) input.value = '';
            });
        },

        // Initialize helpful votes
        initHelpfulVotes: function() {
            document.addEventListener('click', (e) => {
                if (e.target.closest('.hp-helpful-btn')) {
                    e.preventDefault();
                    const btn = e.target.closest('.hp-helpful-btn');
                    const reviewId = btn.dataset.reviewId;
                    const helpful = btn.dataset.helpful === '1';
                    
                    this.voteHelpful(reviewId, helpful, btn);
                }
            });
        },

        // Vote helpful
        voteHelpful: function(reviewId, helpful, btn) {
            HolidayPackages.utils.ajax({
                task: 'voteHelpful',
                review_id: reviewId,
                helpful: helpful ? 1 : 0
            }, (data) => {
                // Update vote counts
                const countSpan = btn.querySelector('.vote-count');
                if (countSpan) {
                    countSpan.textContent = data.count;
                }
                
                btn.classList.add('voted');
                btn.disabled = true;
            });
        },

        // Load more reviews
        initLoadMoreReviews: function() {
            const loadMoreBtn = document.querySelector('.hp-load-more-reviews');
            if (!loadMoreBtn) return;

            loadMoreBtn.addEventListener('click', () => {
                const offset = document.querySelectorAll('.hp-review').length;
                
                HolidayPackages.utils.ajax({
                    task: 'loadMoreReviews',
                    offset: offset
                }, (data) => {
                    if (data.reviews.length > 0) {
                        const reviewsContainer = document.querySelector('.hp-reviews-list');
                        reviewsContainer.insertAdjacentHTML('beforeend', data.html);
                        
                        if (data.reviews.length < data.limit) {
                            loadMoreBtn.style.display = 'none';
                        }
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                });
            });
        },

        // Load reviews
        loadReviews: function() {
            const packageId = document.querySelector('[data-package-id]')?.dataset.packageId;
            if (!packageId) return;

            HolidayPackages.utils.ajax({
                task: 'getReviews',
                package_id: packageId
            }, (data) => {
                const reviewsContainer = document.querySelector('.hp-reviews-list');
                if (reviewsContainer) {
                    reviewsContainer.innerHTML = data.html;
                }
            });
        }
    };

    /**
     * Initialize everything when DOM is ready
     */
    function init() {
        // Initialize based on current page
        const body = document.body;
        
        if (body.classList.contains('hp-packages-page')) {
            HolidayPackages.packages.init();
        }
        
        if (body.classList.contains('hp-booking-page')) {
            HolidayPackages.booking.init();
        }
        
        if (body.classList.contains('hp-package-page')) {
            HolidayPackages.reviews.init();
        }
        
        // Initialize common features
        initCommonFeatures();
    }

    /**
     * Initialize common features
     */
    function initCommonFeatures() {
        // Initialize tooltips
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Initialize tabs
        const tabLinks = document.querySelectorAll('.hp-tab-nav a');
        tabLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remove active from all tabs
                document.querySelectorAll('.hp-tab-nav a').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.hp-tab-pane').forEach(p => p.classList.remove('active'));
                
                // Add active to clicked tab
                link.classList.add('active');
                const targetPane = document.querySelector(link.getAttribute('href'));
                if (targetPane) {
                    targetPane.classList.add('active');
                }
            });
        });

        // Initialize smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Initialize back to top button
        const backToTopBtn = document.querySelector('.hp-back-to-top');
        if (backToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTopBtn.style.display = 'block';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            });

            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();