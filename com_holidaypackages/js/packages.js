/**
 * Modern JavaScript for Holiday Packages Component
 * MakeMyTrip Inspired Interactive Features
 * 
 * @package    com_holidaypackages
 * @version    2.0.0
 * @author     Joomla Component Developer
 * @copyright  Copyright (C) 2024
 * @license    GNU General Public License version 2 or later
 */

'use strict';

class HolidayPackagesManager {
    constructor() {
        this.settings = window.packageSettings || {};
        this.currentFilters = this.settings.initialParams || {};
        this.filterElements = {};
        this.debounceTimeout = null;
        this.isLoading = false;

        this.init();
    }

    /**
     * Initialize the package manager
     */
    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupMobileOptimizations();
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Search form interactions
        this.setupSearchFormListeners();
        
        // Filter interactions
        this.setupFilterListeners();
        
        // Tab interactions
        this.setupTabListeners();
        
        // Sort interactions
        this.setupSortListeners();
        
        // Mobile interactions
        this.setupMobileListeners();
        
        // Accessibility improvements
        this.setupAccessibilityFeatures();
    }

    /**
     * Setup search form event listeners
     */
    setupSearchFormListeners() {
        // Dropdown toggles
        const customButtons = document.querySelectorAll('.custom-button');
        customButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                this.toggleDropdown(e.target);
            });
        });

        // Starting from city dropdown
        const startingFromOptions = document.querySelectorAll('#starting-from-dropdown .option');
        startingFromOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                this.selectStartingCity(e.target);
            });
        });

        // Destination dropdown
        const destinationOptions = document.querySelectorAll('#destination-dropdown .option');
        destinationOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                this.selectDestination(e.target);
            });
        });

        // Date picker
        this.initDatePicker();

        // Rooms and guests
        this.setupRoomsGuestsHandlers();

        // Search and clear buttons
        const searchBtn = document.getElementById('search-btn');
        const clearBtn = document.getElementById('explore-btn');

        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.performSearch());
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearAllFilters());
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.form-group')) {
                this.closeAllDropdowns();
            }
        });
    }

    /**
     * Setup filter event listeners
     */
    setupFilterListeners() {
        // Duration range slider
        const durationSlider = document.getElementById('duration-range-slider');
        if (durationSlider) {
            durationSlider.addEventListener('input', (e) => {
                this.updateDurationRange(e.target.value);
            });
        }

        // Budget range sliders
        const minPriceSlider = document.getElementById('min-price-slider');
        const maxPriceSlider = document.getElementById('max-price-slider');

        if (minPriceSlider && maxPriceSlider) {
            minPriceSlider.addEventListener('input', () => {
                this.updateBudgetRange();
            });
            maxPriceSlider.addEventListener('input', () => {
                this.updateBudgetRange();
            });
        }

        // Checkbox filters
        const filterCheckboxes = document.querySelectorAll('input[type="checkbox"][data-filter]');
        filterCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.debouncedApplyFilters();
            });
        });

        // Clear individual filter tags
        const clearTags = document.querySelectorAll('.clear-tag');
        clearTags.forEach(tag => {
            tag.addEventListener('click', (e) => {
                this.clearIndividualFilter(e.target);
            });
        });

        // Clear all filters
        const clearAllBtn = document.getElementById('clear-all-filters');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }
    }

    /**
     * Setup tab event listeners
     */
    setupTabListeners() {
        const tabLinks = document.querySelectorAll('.nav-link[data-tab-filter]');
        tabLinks.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(e.target);
            });
        });
    }

    /**
     * Setup sort event listeners
     */
    setupSortListeners() {
        const sortSelect = document.getElementById('sort-by-select');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.updateSort(e.target.value);
            });
        }
    }

    /**
     * Setup mobile-specific event listeners
     */
    setupMobileListeners() {
        // Touch gestures for mobile
        let touchStartY = 0;
        
        document.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        });

        document.addEventListener('touchmove', (e) => {
            const touchEndY = e.touches[0].clientY;
            const deltaY = touchStartY - touchEndY;

            // Close dropdowns on swipe up
            if (deltaY > 50) {
                this.closeAllDropdowns();
            }
        });

        // Handle screen orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleResponsiveChanges();
            }, 100);
        });
    }

    /**
     * Setup accessibility features
     */
    setupAccessibilityFeatures() {
        // Keyboard navigation for dropdowns
        const customButtons = document.querySelectorAll('.custom-button');
        customButtons.forEach(button => {
            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleDropdown(button);
                }
            });
        });

        // Focus management for modals
        const popup = document.getElementById('customize-popup');
        if (popup) {
            popup.addEventListener('focus', () => {
                this.trapFocus(popup);
            });
        }

        // Announce filter changes to screen readers
        this.setupAriaLiveRegion();
    }

    /**
     * Initialize components
     */
    initializeComponents() {
        this.initDatePicker();
        this.initializeFilterStates();
        this.setupLazyLoading();
        this.initializeTooltips();
    }

    /**
     * Initialize Flatpickr date picker
     */
    initDatePicker() {
        const dateInput = document.getElementById('start-date-value');
        const dateButton = document.getElementById('start-date');

        if (dateInput && dateButton && window.flatpickr) {
            flatpickr(dateInput, {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                onChange: (selectedDates, dateStr) => {
                    dateButton.textContent = dateStr || 'Select Date';
                    this.currentFilters.start_date = dateStr;
                }
            });

            dateButton.addEventListener('click', () => {
                dateInput._flatpickr.open();
            });
        }
    }

    /**
     * Setup rooms and guests handlers
     */
    setupRoomsGuestsHandlers() {
        // Counter buttons
        const counterButtons = document.querySelectorAll('.counter button');
        counterButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const action = button.textContent.includes('+') ? 1 : -1;
                const type = button.getAttribute('onclick')?.includes('adults') ? 'adults' : 'children';
                const roomId = button.closest('.room-section')?.dataset.roomId || 1;
                this.updateCounter(button, type, action, roomId);
            });
        });

        // Add room button
        const addRoomBtn = document.querySelector('.add-room-btn');
        if (addRoomBtn) {
            addRoomBtn.addEventListener('click', () => this.addRoom());
        }

        // Apply button
        const applyBtn = document.querySelector('.apply-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', () => this.applyRoomsGuests());
        }
    }

    /**
     * Toggle dropdown visibility
     */
    toggleDropdown(button) {
        const dropdown = button.nextElementSibling;
        const isActive = dropdown.classList.contains('active');
        
        // Close all dropdowns first
        this.closeAllDropdowns();
        
        if (!isActive && dropdown) {
            dropdown.classList.add('active');
            button.setAttribute('aria-expanded', 'true');
            dropdown.setAttribute('aria-hidden', 'false');
            
            // Focus first option
            const firstOption = dropdown.querySelector('.option');
            if (firstOption) {
                firstOption.focus();
            }
        }
    }

    /**
     * Close all dropdowns
     */
    closeAllDropdowns() {
        const dropdowns = document.querySelectorAll('.dropdown.active');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
            dropdown.setAttribute('aria-hidden', 'true');
            
            const button = dropdown.previousElementSibling;
            if (button) {
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * Select starting city
     */
    selectStartingCity(option) {
        const city = option.dataset.city;
        const button = document.getElementById('starting-from');
        
        if (button) {
            button.textContent = city || 'Select City';
            this.currentFilters.starting_from = city;
        }
        
        this.closeAllDropdowns();
        this.announceChange('Starting city updated');
    }

    /**
     * Select destination
     */
    selectDestination(option) {
        const destinationId = option.dataset.id;
        const destinationText = option.textContent.trim();
        const button = document.getElementById('destination');
        
        if (button) {
            button.textContent = destinationText || 'Select Destination';
            button.dataset.id = destinationId;
            this.currentFilters.id = destinationId;
        }
        
        this.closeAllDropdowns();
        this.announceChange('Destination updated');
        
        // Reload filters for new destination
        if (destinationId) {
            this.loadDestinationFilters(destinationId);
        }
    }

    /**
     * Update duration range
     */
    updateDurationRange(value) {
        const display = document.getElementById('duration-range-display');
        const minDuration = this.settings.minDuration || 1;
        
        if (display) {
            display.textContent = `${minDuration}-${value}N`;
        }
        
        this.currentFilters.duration_range = `${minDuration}-${value}`;
        this.debouncedApplyFilters();
    }

    /**
     * Update budget range
     */
    updateBudgetRange() {
        const minSlider = document.getElementById('min-price-slider');
        const maxSlider = document.getElementById('max-price-slider');
        const minDisplay = document.getElementById('min-price-display');
        const maxDisplay = document.getElementById('max-price-display');

        if (minSlider && maxSlider && minDisplay && maxDisplay) {
            let minValue = parseInt(minSlider.value);
            let maxValue = parseInt(maxSlider.value);

            // Ensure min doesn't exceed max
            if (minValue > maxValue) {
                minValue = maxValue - 1000;
                minSlider.value = minValue;
            }

            // Ensure max doesn't go below min
            if (maxValue < minValue) {
                maxValue = minValue + 1000;
                maxSlider.value = maxValue;
            }

            minDisplay.textContent = `₹${this.formatNumber(minValue)}`;
            maxDisplay.textContent = `₹${this.formatNumber(maxValue)}`;

            this.currentFilters.min_price = minValue;
            this.currentFilters.max_price = maxValue;

            this.debouncedApplyFilters();
        }
    }

    /**
     * Update counter for rooms and guests
     */
    updateCounter(button, type, action, roomId) {
        const counter = button.parentElement;
        const display = counter.querySelector('.count-display');
        const currentValue = parseInt(display.textContent) || 0;
        const newValue = Math.max(0, currentValue + action);
        
        // Validation rules
        if (type === 'adults' && newValue < 1) return;
        if (type === 'adults' && newValue > 4) return;
        
        const roomSection = button.closest('.room-section');
        const adultsCount = parseInt(roomSection.querySelector('.guest-type:first-child .count-display').textContent) || 1;
        const childrenCount = parseInt(roomSection.querySelector('.guest-type:last-child .count-display').textContent) || 0;
        
        if (type === 'children' && (adultsCount + newValue) > 4) return;
        
        display.textContent = newValue;
        
        // Update button states
        this.updateCounterButtonStates(roomSection);
        
        // Show/hide child age section
        if (type === 'children') {
            this.toggleChildAgeSection(roomSection, newValue);
        }
        
        this.announceChange(`${type} count updated to ${newValue}`);
    }

    /**
     * Update counter button states
     */
    updateCounterButtonStates(roomSection) {
        const adultsCount = parseInt(roomSection.querySelector('.guest-type:first-child .count-display').textContent) || 1;
        const childrenCount = parseInt(roomSection.querySelector('.guest-type:last-child .count-display').textContent) || 0;
        const totalGuests = adultsCount + childrenCount;
        
        const minusAdults = roomSection.querySelector('.guest-type:first-child .counter button:first-child');
        const plusAdults = roomSection.querySelector('.guest-type:first-child .counter button:last-child');
        const minusChildren = roomSection.querySelector('.guest-type:last-child .counter button:first-child');
        const plusChildren = roomSection.querySelector('.guest-type:last-child .counter button:last-child');
        
        // Adults validation
        minusAdults.disabled = adultsCount <= 1;
        plusAdults.disabled = totalGuests >= 4;
        
        // Children validation
        minusChildren.disabled = childrenCount <= 0;
        plusChildren.disabled = totalGuests >= 4;
    }

    /**
     * Toggle child age section
     */
    toggleChildAgeSection(roomSection, childrenCount) {
        const ageSection = roomSection.querySelector('.child-age-section');
        const ageContainer = roomSection.querySelector('.age-selectors-container');
        
        if (childrenCount > 0) {
            ageSection.style.display = 'block';
            this.generateChildAgeSelectors(ageContainer, childrenCount);
        } else {
            ageSection.style.display = 'none';
            ageContainer.innerHTML = '';
        }
    }

    /**
     * Generate child age selectors
     */
    generateChildAgeSelectors(container, count) {
        container.innerHTML = '';
        
        for (let i = 0; i < count; i++) {
            const selector = document.createElement('div');
            selector.className = 'age-selector';
            selector.innerHTML = `
                <select name="child_age_${i}" aria-label="Age of child ${i + 1}">
                    <option value="">Age</option>
                    ${Array.from({length: 12}, (_, j) => `<option value="${j}">${j} yr${j !== 1 ? 's' : ''}</option>`).join('')}
                </select>
            `;
            container.appendChild(selector);
        }
    }

    /**
     * Add new room
     */
    addRoom() {
        const container = document.getElementById('room-sections-container');
        const roomCount = container.children.length;
        const newRoomId = roomCount + 1;
        
        if (roomCount >= 4) return; // Max 4 rooms
        
        const newRoom = this.createRoomSection(newRoomId);
        container.appendChild(newRoom);
        
        // Update add button state
        if (roomCount >= 3) {
            document.querySelector('.add-room-btn').disabled = true;
        }
        
        this.announceChange(`Room ${newRoomId} added`);
    }

    /**
     * Create new room section
     */
    createRoomSection(roomId) {
        const roomSection = document.createElement('div');
        roomSection.className = 'room-section';
        roomSection.dataset.roomId = roomId;
        
        roomSection.innerHTML = `
            <div class="room-header">
                <h4><i class="fas fa-bed"></i> ROOM ${roomId}</h4>
                <button class="remove-room-btn" onclick="removeRoom(${roomId})" ${roomId === 1 ? 'style="display: none;"' : ''}>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="guest-types">
                <div class="guest-type">
                    <label><i class="fas fa-user"></i> Adults <br>(12+ yrs)</label>
                    <div class="counter">
                        <button type="button" onclick="updateCounter(this, 'adults', -1, ${roomId})">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="count-display">1</span>
                        <button type="button" onclick="updateCounter(this, 'adults', 1, ${roomId})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="guest-type">
                    <label><i class="fas fa-child"></i> Child <br>(0-11 yrs)</label>
                    <div class="counter">
                        <button type="button" onclick="updateCounter(this, 'children', -1, ${roomId})" disabled>
                            <i class="fas fa-minus"></i>
                        </button>
                        <span class="count-display">0</span>
                        <button type="button" onclick="updateCounter(this, 'children', 1, ${roomId})">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="child-age-section" style="display: none;">
                <div class="age-header">
                    <label>Child Ages</label>
                    <span class="help-text">Age on last travel day</span>
                </div>
                <div class="age-selectors-container"></div>
            </div>
        `;
        
        return roomSection;
    }

    /**
     * Apply rooms and guests selection
     */
    applyRoomsGuests() {
        const roomSections = document.querySelectorAll('.room-section');
        let totalRooms = roomSections.length;
        let totalAdults = 0;
        let totalChildren = 0;
        
        roomSections.forEach(section => {
            const adults = parseInt(section.querySelector('.guest-type:first-child .count-display').textContent) || 1;
            const children = parseInt(section.querySelector('.guest-type:last-child .count-display').textContent) || 0;
            totalAdults += adults;
            totalChildren += children;
        });
        
        // Update button text
        const button = document.getElementById('rooms-guests');
        if (button) {
            let text = `<i class="fas fa-bed"></i> ${totalRooms} Room${totalRooms > 1 ? 's' : ''}`;
            text += ` <i class="fas fa-user"></i> ${totalAdults} Guest${totalAdults > 1 ? 's' : ''}`;
            if (totalChildren > 0) {
                text += ` <i class="fas fa-child"></i> ${totalChildren} Child${totalChildren > 1 ? 'ren' : ''}`;
            }
            button.innerHTML = text;
        }
        
        // Update current filters
        this.currentFilters.rooms = totalRooms;
        this.currentFilters.adults = totalAdults;
        this.currentFilters.children = totalChildren;
        
        this.closeAllDropdowns();
        this.announceChange('Room and guest selection applied');
    }

    /**
     * Switch tab
     */
    switchTab(tabElement) {
        // Remove active class from all tabs
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        
        // Add active class to clicked tab
        tabElement.classList.add('active');
        tabElement.setAttribute('aria-selected', 'true');
        
        // Update filter
        this.currentFilters.tab_filter = tabElement.dataset.tabFilter;
        
        this.applyFilters();
        this.announceChange(`Tab switched to ${tabElement.textContent.trim()}`);
    }

    /**
     * Update sort
     */
    updateSort(sortValue) {
        this.currentFilters.sort = sortValue;
        this.applyFilters();
        this.announceChange(`Sort changed to ${sortValue}`);
    }

    /**
     * Clear individual filter
     */
    clearIndividualFilter(clearButton) {
        const filterType = clearButton.dataset.filter;
        const filterValue = clearButton.dataset.value;
        
        // Clear from current filters
        if (filterType === 'budget') {
            this.currentFilters.min_price = this.settings.minPrice;
            this.currentFilters.max_price = this.settings.maxPrice;
            this.resetBudgetSliders();
        } else if (Array.isArray(this.currentFilters[filterType])) {
            const index = this.currentFilters[filterType].indexOf(filterValue);
            if (index > -1) {
                this.currentFilters[filterType].splice(index, 1);
            }
        } else {
            this.currentFilters[filterType] = '';
        }
        
        this.applyFilters();
        this.announceChange(`${filterType} filter cleared`);
    }

    /**
     * Clear all filters
     */
    clearAllFilters() {
        // Reset all filters to initial state
        this.currentFilters = {
            ...this.settings.initialParams,
            starting_from: '',
            id: this.settings.initialDestinationId,
            start_date: '',
            rooms: 1,
            adults: 1,
            children: 0,
            duration_range: '',
            flights: '',
            min_price: this.settings.minPrice,
            max_price: this.settings.maxPrice,
            hotel_category: [],
            cities: [],
            package_type: [],
            special_package: [],
            sort: 'popular',
            tab_filter: 'all'
        };
        
        // Reset UI elements
        this.resetAllUIElements();
        
        // Apply cleared filters
        this.applyFilters();
        
        this.announceChange('All filters cleared');
    }

    /**
     * Reset all UI elements
     */
    resetAllUIElements() {
        // Reset dropdowns
        document.getElementById('starting-from').textContent = 'Select City';
        document.getElementById('start-date').textContent = 'Select Date';
        
        // Reset checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset sliders
        this.resetBudgetSliders();
        
        const durationSlider = document.getElementById('duration-range-slider');
        if (durationSlider) {
            durationSlider.value = this.settings.maxDuration;
            this.updateDurationRange(this.settings.maxDuration);
        }
        
        // Reset sort
        const sortSelect = document.getElementById('sort-by-select');
        if (sortSelect) {
            sortSelect.value = 'popular';
        }
        
        // Reset tabs
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.classList.remove('active');
            tab.setAttribute('aria-selected', 'false');
        });
        document.querySelector('.nav-link[data-tab-filter="all"]')?.classList.add('active');
    }

    /**
     * Reset budget sliders
     */
    resetBudgetSliders() {
        const minSlider = document.getElementById('min-price-slider');
        const maxSlider = document.getElementById('max-price-slider');
        
        if (minSlider && maxSlider) {
            minSlider.value = this.settings.minPrice;
            maxSlider.value = this.settings.maxPrice;
            this.updateBudgetRange();
        }
    }

    /**
     * Debounced apply filters
     */
    debouncedApplyFilters() {
        clearTimeout(this.debounceTimeout);
        this.debounceTimeout = setTimeout(() => {
            this.applyFilters();
        }, 300);
    }

    /**
     * Apply filters and reload content
     */
    applyFilters() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();
        
        // Build query parameters
        const params = new URLSearchParams();
        Object.keys(this.currentFilters).forEach(key => {
            if (this.currentFilters[key] && this.currentFilters[key] !== '') {
                if (Array.isArray(this.currentFilters[key])) {
                    this.currentFilters[key].forEach(value => {
                        params.append(`${key}[]`, value);
                    });
                } else {
                    params.set(key, this.currentFilters[key]);
                }
            }
        });
        
        // Perform AJAX request
        const url = `${this.settings.baseUrl}&${params.toString()}`;
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            this.updatePackageContent(html);
            this.updateURL(url);
        })
        .catch(error => {
            console.error('Error applying filters:', error);
            this.showErrorState();
        })
        .finally(() => {
            this.isLoading = false;
            this.hideLoadingState();
        });
    }

    /**
     * Perform search
     */
    performSearch() {
        this.applyFilters();
        this.announceChange('Search performed');
    }

    /**
     * Show loading state
     */
    showLoadingState() {
        const packageContent = document.querySelector('.package-content-wrapper');
        if (packageContent) {
            packageContent.classList.add('loading');
        }
        
        // Add loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p>Loading packages...</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    /**
     * Hide loading state
     */
    hideLoadingState() {
        const packageContent = document.querySelector('.package-content-wrapper');
        if (packageContent) {
            packageContent.classList.remove('loading');
        }
        
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    /**
     * Show error state
     */
    showErrorState() {
        const packageContent = document.querySelector('.package-content-wrapper');
        if (packageContent) {
            packageContent.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                    <h3>Error Loading Packages</h3>
                    <p>We're having trouble loading packages. Please try again.</p>
                    <button class="btn" onclick="location.reload()">Retry</button>
                </div>
            `;
        }
    }

    /**
     * Update package content
     */
    updatePackageContent(html) {
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        // Update package grid
        const newPackageContent = newDoc.querySelector('.package-content-wrapper');
        const currentPackageContent = document.querySelector('.package-content-wrapper');
        
        if (newPackageContent && currentPackageContent) {
            currentPackageContent.innerHTML = newPackageContent.innerHTML;
        }
        
        // Update selected filters display
        const newFiltersDisplay = newDoc.querySelector('.selected-filters-wrapper');
        const currentFiltersDisplay = document.querySelector('.selected-filters-wrapper');
        
        if (newFiltersDisplay && currentFiltersDisplay) {
            currentFiltersDisplay.innerHTML = newFiltersDisplay.innerHTML;
        }
        
        // Re-initialize lazy loading
        this.setupLazyLoading();
    }

    /**
     * Update URL without page reload
     */
    updateURL(url) {
        if (history.pushState) {
            history.pushState(null, '', url);
        }
    }

    /**
     * Load destination-specific filters
     */
    loadDestinationFilters(destinationId) {
        // Implementation for loading destination-specific filter options
        // This would make an AJAX call to get updated filter options
    }

    /**
     * Setup mobile optimizations
     */
    setupMobileOptimizations() {
        // Handle window resize
        window.addEventListener('resize', () => {
            this.handleResponsiveChanges();
        });
        
        // Initial setup
        this.handleResponsiveChanges();
    }

    /**
     * Handle responsive changes
     */
    handleResponsiveChanges() {
        const isMobile = window.innerWidth < 768;
        
        if (isMobile) {
            // Mobile-specific optimizations
            this.optimizeForMobile();
        } else {
            // Desktop optimizations
            this.optimizeForDesktop();
        }
    }

    /**
     * Optimize for mobile
     */
    optimizeForMobile() {
        // Implement mobile-specific optimizations
        const filterSection = document.querySelector('.filter-section');
        if (filterSection) {
            filterSection.classList.add('mobile-filters');
        }
    }

    /**
     * Optimize for desktop
     */
    optimizeForDesktop() {
        // Implement desktop-specific optimizations
        const filterSection = document.querySelector('.filter-section');
        if (filterSection) {
            filterSection.classList.remove('mobile-filters');
        }
    }

    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        // Implementation for tooltips if needed
    }

    /**
     * Initialize filter states from URL or saved preferences
     */
    initializeFilterStates() {
        // Set initial filter states based on current filters
        this.updateUIFromFilters();
    }

    /**
     * Update UI elements based on current filters
     */
    updateUIFromFilters() {
        // Update checkboxes
        Object.keys(this.currentFilters).forEach(filterKey => {
            if (Array.isArray(this.currentFilters[filterKey])) {
                this.currentFilters[filterKey].forEach(value => {
                    const checkbox = document.querySelector(`input[name="${filterKey}[]"][value="${value}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
        });
        
        // Update range sliders
        if (this.currentFilters.min_price && this.currentFilters.max_price) {
            this.resetBudgetSliders();
        }
    }

    /**
     * Setup ARIA live region for announcements
     */
    setupAriaLiveRegion() {
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        liveRegion.id = 'aria-live-region';
        document.body.appendChild(liveRegion);
    }

    /**
     * Announce changes to screen readers
     */
    announceChange(message) {
        const liveRegion = document.getElementById('aria-live-region');
        if (liveRegion) {
            liveRegion.textContent = message;
        }
    }

    /**
     * Trap focus within modal
     */
    trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
        );
        
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusableElement) {
                        lastFocusableElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusableElement) {
                        firstFocusableElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }

    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.holidayPackagesManager = new HolidayPackagesManager();
});

// Global functions for inline handlers (backward compatibility)
window.updateCounter = function(button, type, action, roomId) {
    if (window.holidayPackagesManager) {
        window.holidayPackagesManager.updateCounter(button, type, action, roomId);
    }
};

window.removeRoom = function(roomId) {
    const roomSection = document.querySelector(`.room-section[data-room-id="${roomId}"]`);
    if (roomSection && roomId > 1) {
        roomSection.remove();
        
        // Re-enable add room button
        const addRoomBtn = document.querySelector('.add-room-btn');
        if (addRoomBtn) {
            addRoomBtn.disabled = false;
        }
        
        if (window.holidayPackagesManager) {
            window.holidayPackagesManager.announceChange(`Room ${roomId} removed`);
        }
    }
};

window.addRoom = function() {
    if (window.holidayPackagesManager) {
        window.holidayPackagesManager.addRoom();
    }
};

window.applyRoomsGuests = function() {
    if (window.holidayPackagesManager) {
        window.holidayPackagesManager.applyRoomsGuests();
    }
};