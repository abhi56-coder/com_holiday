/**
 * Holiday Packages Component - Admin JavaScript
 * Version 2.0.0
 * Modern admin interface interactions for Joomla 5
 */

(function() {
    'use strict';

    // Main Admin object
    window.HolidayPackagesAdmin = window.HolidayPackagesAdmin || {};

    // Configuration
    HolidayPackagesAdmin.config = {
        ajaxUrl: 'index.php?option=com_holidaypackages&task=ajax',
        token: document.querySelector('input[name="' + Joomla.getOptions('csrf.token', '') + '"]')?.value || '',
        loadingClass: 'hp-loading'
    };

    /**
     * Utility functions
     */
    HolidayPackagesAdmin.utils = {
        
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

        // Show notification
        showNotification: function(message, type = 'success') {
            // Use Joomla's notification system
            if (window.Joomla && Joomla.renderMessages) {
                const messages = {};
                messages[type] = [message];
                Joomla.renderMessages(messages);
            } else {
                // Fallback to simple alert
                alert(message);
            }
        },

        // AJAX request
        ajax: function(data, callback, errorCallback) {
            data.token = HolidayPackagesAdmin.config.token;
            
            fetch(HolidayPackagesAdmin.config.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success !== false) {
                    if (callback) callback(result.data, result.message);
                } else {
                    if (errorCallback) {
                        errorCallback(result.message);
                    } else {
                        HolidayPackagesAdmin.utils.showNotification(result.message, 'error');
                    }
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                const message = 'An error occurred. Please try again.';
                if (errorCallback) {
                    errorCallback(message);
                } else {
                    HolidayPackagesAdmin.utils.showNotification(message, 'error');
                }
            });
        },

        // Format currency
        formatCurrency: function(amount, currency = 'USD') {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },

        // Confirm action
        confirm: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    };

    /**
     * Dashboard functionality
     */
    HolidayPackagesAdmin.dashboard = {
        
        charts: {},

        // Initialize dashboard
        init: function() {
            this.loadStats();
            this.initCharts();
            this.initQuickActions();
        },

        // Load statistics
        loadStats: function() {
            HolidayPackagesAdmin.utils.ajax({
                task: 'getDashboardStats'
            }, (data) => {
                this.updateStats(data);
            });
        },

        // Update statistics display
        updateStats: function(stats) {
            Object.keys(stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    const valueEl = element.querySelector('.hp-stat-value');
                    const changeEl = element.querySelector('.hp-stat-change');
                    
                    if (valueEl) {
                        // Animate number counting
                        this.animateNumber(valueEl, stats[key].value);
                    }
                    
                    if (changeEl && stats[key].change !== undefined) {
                        const change = stats[key].change;
                        changeEl.textContent = `${change > 0 ? '+' : ''}${change}%`;
                        changeEl.className = `hp-stat-change ${change > 0 ? 'positive' : 'negative'}`;
                    }
                }
            });
        },

        // Animate number counting
        animateNumber: function(element, targetValue) {
            const startValue = 0;
            const duration = 1000;
            const startTime = performance.now();
            
            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
                element.textContent = currentValue.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }
            
            requestAnimationFrame(updateNumber);
        },

        // Initialize charts
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }

            this.initRevenueChart();
            this.initBookingsChart();
            this.initPackagesChart();
        },

        // Initialize revenue chart
        initRevenueChart: function() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            HolidayPackagesAdmin.utils.ajax({
                task: 'getRevenueChartData'
            }, (data) => {
                this.charts.revenue = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Revenue',
                            data: data.values,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return HolidayPackagesAdmin.utils.formatCurrency(value);
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        // Initialize bookings chart
        initBookingsChart: function() {
            const ctx = document.getElementById('bookingsChart');
            if (!ctx) return;

            HolidayPackagesAdmin.utils.ajax({
                task: 'getBookingsChartData'
            }, (data) => {
                this.charts.bookings = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#48bb78',
                                '#ed8936',
                                '#f56565',
                                '#667eea'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            });
        },

        // Initialize packages chart
        initPackagesChart: function() {
            const ctx = document.getElementById('packagesChart');
            if (!ctx) return;

            HolidayPackagesAdmin.utils.ajax({
                task: 'getPackagesChartData'
            }, (data) => {
                this.charts.packages = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Bookings',
                            data: data.values,
                            backgroundColor: '#667eea',
                            borderColor: '#5a67d8',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
        },

        // Initialize quick actions
        initQuickActions: function() {
            const quickActions = document.querySelectorAll('.hp-quick-action');
            
            quickActions.forEach(action => {
                action.addEventListener('click', (e) => {
                    e.preventDefault();
                    const actionType = action.dataset.action;
                    this.executeQuickAction(actionType);
                });
            });
        },

        // Execute quick action
        executeQuickAction: function(actionType) {
            switch (actionType) {
                case 'new-package':
                    window.location.href = 'index.php?option=com_holidaypackages&task=package.add';
                    break;
                case 'new-destination':
                    window.location.href = 'index.php?option=com_holidaypackages&task=destination.add';
                    break;
                case 'export-bookings':
                    this.exportData('bookings');
                    break;
                case 'send-newsletter':
                    this.showNewsletterModal();
                    break;
                default:
                    console.warn('Unknown quick action:', actionType);
            }
        },

        // Export data
        exportData: function(type) {
            window.location.href = `index.php?option=com_holidaypackages&task=export&type=${type}&${HolidayPackagesAdmin.config.token}=1`;
        },

        // Show newsletter modal
        showNewsletterModal: function() {
            // This would open a modal for newsletter composition
            alert('Newsletter feature would be implemented here');
        }
    };

    /**
     * List management functionality
     */
    HolidayPackagesAdmin.listManager = {
        
        // Initialize list management
        init: function() {
            this.initBulkActions();
            this.initQuickEdit();
            this.initFilters();
            this.initSorting();
        },

        // Initialize bulk actions
        initBulkActions: function() {
            const bulkActionBtn = document.querySelector('.hp-bulk-action-btn');
            const checkboxes = document.querySelectorAll('.hp-item-checkbox');
            const selectAllCheckbox = document.querySelector('.hp-select-all');
            
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', (e) => {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = e.target.checked;
                    });
                    this.updateBulkActionButton();
                });
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    this.updateBulkActionButton();
                });
            });
            
            if (bulkActionBtn) {
                bulkActionBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.showBulkActionModal();
                });
            }
        },

        // Update bulk action button
        updateBulkActionButton: function() {
            const checkedBoxes = document.querySelectorAll('.hp-item-checkbox:checked');
            const bulkActionBtn = document.querySelector('.hp-bulk-action-btn');
            
            if (bulkActionBtn) {
                if (checkedBoxes.length > 0) {
                    bulkActionBtn.disabled = false;
                    bulkActionBtn.textContent = `Actions (${checkedBoxes.length} selected)`;
                } else {
                    bulkActionBtn.disabled = true;
                    bulkActionBtn.textContent = 'Bulk Actions';
                }
            }
        },

        // Show bulk action modal
        showBulkActionModal: function() {
            const checkedBoxes = document.querySelectorAll('.hp-item-checkbox:checked');
            if (checkedBoxes.length === 0) return;
            
            const actions = [
                { id: 'publish', label: 'Publish', icon: 'fas fa-check' },
                { id: 'unpublish', label: 'Unpublish', icon: 'fas fa-times' },
                { id: 'feature', label: 'Feature', icon: 'fas fa-star' },
                { id: 'delete', label: 'Delete', icon: 'fas fa-trash', danger: true }
            ];
            
            let modalHtml = `
                <div class="hp-modal-overlay">
                    <div class="hp-modal">
                        <div class="hp-modal-header">
                            <h3 class="hp-modal-title">Bulk Actions</h3>
                            <button class="hp-modal-close">&times;</button>
                        </div>
                        <div class="hp-modal-body">
                            <p>Select an action to apply to ${checkedBoxes.length} selected items:</p>
                            <div class="hp-bulk-actions-grid">
            `;
            
            actions.forEach(action => {
                modalHtml += `
                    <button class="hp-btn ${action.danger ? 'hp-btn-danger' : 'hp-btn-primary'} hp-bulk-action" 
                            data-action="${action.id}">
                        <i class="${action.icon}"></i> ${action.label}
                    </button>
                `;
            });
            
            modalHtml += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Add event listeners
            const modal = document.querySelector('.hp-modal-overlay');
            const closeBtn = modal.querySelector('.hp-modal-close');
            const actionBtns = modal.querySelectorAll('.hp-bulk-action');
            
            closeBtn.addEventListener('click', () => {
                modal.remove();
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            actionBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const action = btn.dataset.action;
                    this.executeBulkAction(action, checkedBoxes);
                    modal.remove();
                });
            });
        },

        // Execute bulk action
        executeBulkAction: function(action, checkboxes) {
            const ids = Array.from(checkboxes).map(cb => cb.value);
            const view = this.getCurrentView();
            
            let confirmMessage = `Are you sure you want to ${action} ${ids.length} items?`;
            if (action === 'delete') {
                confirmMessage = `Are you sure you want to delete ${ids.length} items? This action cannot be undone.`;
            }
            
            HolidayPackagesAdmin.utils.confirm(confirmMessage, () => {
                HolidayPackagesAdmin.utils.ajax({
                    task: 'bulkOperation',
                    operation: action,
                    ids: ids,
                    view: view
                }, (data, message) => {
                    HolidayPackagesAdmin.utils.showNotification(message, 'success');
                    location.reload();
                }, (error) => {
                    HolidayPackagesAdmin.utils.showNotification(error, 'error');
                });
            });
        },

        // Get current view
        getCurrentView: function() {
            const url = new URL(window.location);
            return url.searchParams.get('view') || 'packages';
        },

        // Initialize quick edit
        initQuickEdit: function() {
            const quickEditBtns = document.querySelectorAll('.hp-quick-edit');
            
            quickEditBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const id = btn.dataset.id;
                    const field = btn.dataset.field;
                    const currentValue = btn.dataset.value;
                    
                    this.showQuickEditModal(id, field, currentValue);
                });
            });
        },

        // Show quick edit modal
        showQuickEditModal: function(id, field, currentValue) {
            let inputHtml = '';
            
            switch (field) {
                case 'published':
                    inputHtml = `
                        <select class="hp-form-control" id="quick-edit-value">
                            <option value="1" ${currentValue == 1 ? 'selected' : ''}>Published</option>
                            <option value="0" ${currentValue == 0 ? 'selected' : ''}>Unpublished</option>
                        </select>
                    `;
                    break;
                case 'featured':
                    inputHtml = `
                        <select class="hp-form-control" id="quick-edit-value">
                            <option value="1" ${currentValue == 1 ? 'selected' : ''}>Featured</option>
                            <option value="0" ${currentValue == 0 ? 'selected' : ''}>Not Featured</option>
                        </select>
                    `;
                    break;
                default:
                    inputHtml = `<input type="text" class="hp-form-control" id="quick-edit-value" value="${currentValue}">`;
            }
            
            const modalHtml = `
                <div class="hp-modal-overlay">
                    <div class="hp-modal">
                        <div class="hp-modal-header">
                            <h3 class="hp-modal-title">Quick Edit ${field}</h3>
                            <button class="hp-modal-close">&times;</button>
                        </div>
                        <div class="hp-modal-body">
                            <div class="hp-form-group">
                                <label class="hp-form-label">New Value:</label>
                                ${inputHtml}
                            </div>
                        </div>
                        <div class="hp-modal-footer">
                            <button class="hp-btn hp-btn-primary hp-save-quick-edit">Save</button>
                            <button class="hp-btn hp-btn-outline hp-cancel-quick-edit">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            const modal = document.querySelector('.hp-modal-overlay');
            const closeBtn = modal.querySelector('.hp-modal-close');
            const saveBtn = modal.querySelector('.hp-save-quick-edit');
            const cancelBtn = modal.querySelector('.hp-cancel-quick-edit');
            const input = modal.querySelector('#quick-edit-value');
            
            // Focus input
            input.focus();
            
            // Event listeners
            [closeBtn, cancelBtn].forEach(btn => {
                btn.addEventListener('click', () => {
                    modal.remove();
                });
            });
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
            
            saveBtn.addEventListener('click', () => {
                const newValue = input.value;
                this.saveQuickEdit(id, field, newValue);
                modal.remove();
            });
            
            // Save on Enter key
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const newValue = input.value;
                    this.saveQuickEdit(id, field, newValue);
                    modal.remove();
                }
            });
        },

        // Save quick edit
        saveQuickEdit: function(id, field, value) {
            const view = this.getCurrentView();
            
            HolidayPackagesAdmin.utils.ajax({
                task: 'quickEdit',
                id: id,
                field: field,
                value: value,
                view: view
            }, (data, message) => {
                HolidayPackagesAdmin.utils.showNotification(message, 'success');
                location.reload();
            }, (error) => {
                HolidayPackagesAdmin.utils.showNotification(error, 'error');
            });
        },

        // Initialize filters
        initFilters: function() {
            const filterForm = document.querySelector('.hp-filters-form');
            if (!filterForm) return;
            
            const filterInputs = filterForm.querySelectorAll('select, input');
            
            filterInputs.forEach(input => {
                input.addEventListener('change', () => {
                    filterForm.submit();
                });
            });
        },

        // Initialize sorting
        initSorting: function() {
            const sortableHeaders = document.querySelectorAll('.hp-sortable');
            
            sortableHeaders.forEach(header => {
                header.addEventListener('click', (e) => {
                    e.preventDefault();
                    const field = header.dataset.field;
                    const currentDir = header.dataset.direction || 'asc';
                    const newDir = currentDir === 'asc' ? 'desc' : 'asc';
                    
                    // Update URL and reload
                    const url = new URL(window.location);
                    url.searchParams.set('filter_order', field);
                    url.searchParams.set('filter_order_Dir', newDir);
                    window.location.href = url.toString();
                });
            });
        }
    };

    /**
     * Form enhancements
     */
    HolidayPackagesAdmin.forms = {
        
        // Initialize form enhancements
        init: function() {
            this.initImageUpload();
            this.initRichTextEditors();
            this.initDatePickers();
            this.initColorPickers();
            this.initDependentFields();
        },

        // Initialize image upload
        initImageUpload: function() {
            const uploadAreas = document.querySelectorAll('.hp-image-upload');
            
            uploadAreas.forEach(area => {
                const input = area.querySelector('input[type="file"]');
                const preview = area.querySelector('.hp-image-preview');
                
                area.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    area.classList.add('dragover');
                });
                
                area.addEventListener('dragleave', () => {
                    area.classList.remove('dragover');
                });
                
                area.addEventListener('drop', (e) => {
                    e.preventDefault();
                    area.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        this.handleImageUpload(files[0], preview);
                    }
                });
                
                area.addEventListener('click', () => {
                    input.click();
                });
                
                input.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        this.handleImageUpload(e.target.files[0], preview);
                    }
                });
            });
        },

        // Handle image upload
        handleImageUpload: function(file, preview) {
            if (!file.type.startsWith('image/')) {
                HolidayPackagesAdmin.utils.showNotification('Please select a valid image file', 'error');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                if (preview) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
                }
            };
            reader.readAsDataURL(file);
        },

        // Initialize rich text editors
        initRichTextEditors: function() {
            // This would integrate with Joomla's editor system
            const textareas = document.querySelectorAll('.hp-rich-editor');
            textareas.forEach(textarea => {
                // Initialize editor based on Joomla's editor configuration
                if (window.Joomla && Joomla.editors) {
                    // Use Joomla's editor system
                }
            });
        },

        // Initialize date pickers
        initDatePickers: function() {
            const dateInputs = document.querySelectorAll('input[type="date"], .hp-datepicker');
            
            dateInputs.forEach(input => {
                // Add date picker enhancements if needed
                input.addEventListener('change', (e) => {
                    // Validate date if needed
                });
            });
        },

        // Initialize color pickers
        initColorPickers: function() {
            const colorInputs = document.querySelectorAll('input[type="color"], .hp-colorpicker');
            
            colorInputs.forEach(input => {
                // Add color picker enhancements
            });
        },

        // Initialize dependent fields
        initDependentFields: function() {
            const dependentFields = document.querySelectorAll('[data-depends-on]');
            
            dependentFields.forEach(field => {
                const dependsOn = field.dataset.dependsOn;
                const dependsValue = field.dataset.dependsValue;
                const masterField = document.querySelector(`[name="${dependsOn}"]`);
                
                if (masterField) {
                    const checkVisibility = () => {
                        const currentValue = masterField.value;
                        if (dependsValue) {
                            field.style.display = currentValue === dependsValue ? 'block' : 'none';
                        } else {
                            field.style.display = currentValue ? 'block' : 'none';
                        }
                    };
                    
                    masterField.addEventListener('change', checkVisibility);
                    checkVisibility(); // Initial check
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
        
        if (body.classList.contains('hp-dashboard')) {
            HolidayPackagesAdmin.dashboard.init();
        }
        
        // Initialize common features
        HolidayPackagesAdmin.listManager.init();
        HolidayPackagesAdmin.forms.init();
        
        // Initialize global features
        initGlobalFeatures();
    }

    /**
     * Initialize global admin features
     */
    function initGlobalFeatures() {
        // Initialize tooltips
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Initialize confirmation dialogs
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-confirm]')) {
                e.preventDefault();
                const element = e.target.closest('[data-confirm]');
                const message = element.dataset.confirm || 'Are you sure?';
                
                HolidayPackagesAdmin.utils.confirm(message, () => {
                    if (element.tagName === 'A') {
                        window.location.href = element.href;
                    } else if (element.tagName === 'BUTTON' && element.form) {
                        element.form.submit();
                    }
                });
            }
        });

        // Auto-save functionality
        const autoSaveForms = document.querySelectorAll('[data-auto-save]');
        autoSaveForms.forEach(form => {
            let saveTimeout;
            
            form.addEventListener('input', () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    // Auto-save implementation
                    console.log('Auto-saving form...');
                }, 2000);
            });
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();