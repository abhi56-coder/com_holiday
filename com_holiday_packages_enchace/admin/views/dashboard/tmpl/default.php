<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages.admin
 * @version     2.0.0
 * @author      Holiday Packages Team
 * @copyright   Copyright (C) 2024 Holiday Packages. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

$user = Factory::getUser();
$app = Factory::getApplication();

?>

<div class="hp-admin hp-dashboard">
    <!-- Dashboard Header -->
    <div class="hp-admin-header">
        <h1 class="hp-admin-title">
            <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_TITLE'); ?>
        </h1>
        <p class="hp-admin-subtitle">
            <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_WELCOME'); ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="hp-dashboard-stats">
        <div class="hp-stat-card packages" data-stat="packages">
            <div class="hp-stat-header">
                <div class="hp-stat-info">
                    <h3 class="hp-stat-value"><?php echo $this->stats['total_packages'] ?? 0; ?></h3>
                    <p class="hp-stat-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_TOTAL_PACKAGES'); ?></p>
                </div>
                <div class="hp-stat-icon">
                    <i class="fas fa-suitcase-rolling"></i>
                </div>
            </div>
            <?php if (isset($this->stats['packages_change'])): ?>
            <div class="hp-stat-change <?php echo $this->stats['packages_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $this->stats['packages_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs($this->stats['packages_change']); ?>% <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_THIS_MONTH'); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="hp-stat-card bookings" data-stat="bookings">
            <div class="hp-stat-header">
                <div class="hp-stat-info">
                    <h3 class="hp-stat-value"><?php echo $this->stats['total_bookings'] ?? 0; ?></h3>
                    <p class="hp-stat-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_TOTAL_BOOKINGS'); ?></p>
                </div>
                <div class="hp-stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <?php if (isset($this->stats['bookings_change'])): ?>
            <div class="hp-stat-change <?php echo $this->stats['bookings_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $this->stats['bookings_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs($this->stats['bookings_change']); ?>% <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_THIS_MONTH'); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="hp-stat-card revenue" data-stat="revenue">
            <div class="hp-stat-header">
                <div class="hp-stat-info">
                    <h3 class="hp-stat-value"><?php echo $this->formatCurrency($this->stats['total_revenue'] ?? 0); ?></h3>
                    <p class="hp-stat-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_REVENUE'); ?></p>
                </div>
                <div class="hp-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <?php if (isset($this->stats['revenue_change'])): ?>
            <div class="hp-stat-change <?php echo $this->stats['revenue_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $this->stats['revenue_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs($this->stats['revenue_change']); ?>% <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_THIS_MONTH'); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="hp-stat-card customers" data-stat="customers">
            <div class="hp-stat-header">
                <div class="hp-stat-info">
                    <h3 class="hp-stat-value"><?php echo $this->stats['total_customers'] ?? 0; ?></h3>
                    <p class="hp-stat-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_TOTAL_CUSTOMERS'); ?></p>
                </div>
                <div class="hp-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <?php if (isset($this->stats['customers_change'])): ?>
            <div class="hp-stat-change <?php echo $this->stats['customers_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <i class="fas fa-arrow-<?php echo $this->stats['customers_change'] >= 0 ? 'up' : 'down'; ?>"></i>
                <?php echo abs($this->stats['customers_change']); ?>% <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_THIS_MONTH'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="hp-dashboard-charts">
        <!-- Revenue Chart -->
        <div class="hp-chart-card">
            <h3 class="hp-chart-title"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_REVENUE_CHART'); ?></h3>
            <div class="hp-chart-container" style="height: 300px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Booking Status Chart -->
        <div class="hp-chart-card">
            <h3 class="hp-chart-title"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_BOOKING_STATUS'); ?></h3>
            <div class="hp-chart-container" style="height: 300px;">
                <canvas id="bookingsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Recent Activity -->
    <div class="row">
        <div class="col-md-6">
            <!-- Quick Actions -->
            <div class="hp-chart-card">
                <h3 class="hp-chart-title"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_QUICK_ACTIONS'); ?></h3>
                <div class="hp-quick-actions-grid">
                    <?php if ($user->authorise('core.create', 'com_holidaypackages')): ?>
                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=package.add'); ?>" 
                       class="hp-btn hp-btn-primary hp-quick-action" data-action="new-package">
                        <i class="fas fa-plus"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_NEW_PACKAGE'); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($user->authorise('core.create', 'com_holidaypackages')): ?>
                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=destination.add'); ?>" 
                       class="hp-btn hp-btn-success hp-quick-action" data-action="new-destination">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_NEW_DESTINATION'); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($user->authorise('holidaypackages.manage.bookings', 'com_holidaypackages')): ?>
                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=bookings'); ?>" 
                       class="hp-btn hp-btn-warning hp-quick-action">
                        <i class="fas fa-calendar-alt"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_VIEW_BOOKINGS'); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($user->authorise('holidaypackages.view.reports', 'com_holidaypackages')): ?>
                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=reports'); ?>" 
                       class="hp-btn hp-btn-info hp-quick-action">
                        <i class="fas fa-chart-bar"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_REPORTS'); ?>
                    </a>
                    <?php endif; ?>

                    <?php if ($user->authorise('core.admin', 'com_holidaypackages')): ?>
                    <button class="hp-btn hp-btn-outline hp-quick-action" data-action="export-bookings">
                        <i class="fas fa-download"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_EXPORT_DATA'); ?>
                    </button>
                    <?php endif; ?>

                    <button class="hp-btn hp-btn-outline hp-quick-action" data-action="send-newsletter">
                        <i class="fas fa-envelope"></i>
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_SEND_NEWSLETTER'); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Recent Bookings -->
            <div class="hp-chart-card">
                <h3 class="hp-chart-title"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_RECENT_BOOKINGS'); ?></h3>
                <?php if (!empty($this->recentBookings)): ?>
                <div class="hp-recent-list">
                    <?php foreach ($this->recentBookings as $booking): ?>
                    <div class="hp-recent-item">
                        <div class="hp-item-info">
                            <h4><?php echo HTMLHelper::_('string.truncate', $booking->package_title, 30); ?></h4>
                            <p class="text-muted">
                                <?php echo $booking->customer_name; ?> • 
                                <?php echo HTMLHelper::_('date', $booking->created, Text::_('DATE_FORMAT_LC4')); ?>
                            </p>
                        </div>
                        <div class="hp-item-meta">
                            <?php echo $this->getStatusBadge($booking->booking_status, 'booking'); ?>
                            <div class="hp-item-amount"><?php echo $this->formatCurrency($booking->total_amount, $booking->currency); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="hp-chart-footer">
                    <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=bookings'); ?>" 
                       class="hp-btn hp-btn-sm hp-btn-outline">
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_VIEW_ALL_BOOKINGS'); ?>
                    </a>
                </div>
                <?php else: ?>
                <div class="hp-empty-state">
                    <i class="fas fa-calendar-times hp-empty-icon"></i>
                    <p><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_NO_RECENT_BOOKINGS'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Popular Packages -->
    <?php if (!empty($this->popularPackages)): ?>
    <div class="hp-chart-card">
        <h3 class="hp-chart-title"><?php echo Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_POPULAR_PACKAGES'); ?></h3>
        <div class="hp-table-wrapper">
            <table class="hp-table table table-striped">
                <thead>
                    <tr>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_PACKAGE_TITLE'); ?></th>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_DESTINATION'); ?></th>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKINGS'); ?></th>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_REVENUE'); ?></th>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_RATING'); ?></th>
                        <th><?php echo Text::_('COM_HOLIDAYPACKAGES_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->popularPackages as $package): ?>
                    <tr>
                        <td>
                            <div class="hp-package-info">
                                <?php if ($package->image): ?>
                                <img src="<?php echo HTMLHelper::_('cleanImageURL', $package->image); ?>" 
                                     alt="<?php echo $this->escape($package->title); ?>"
                                     class="hp-package-thumb">
                                <?php endif; ?>
                                <div>
                                    <h5><?php echo $this->escape($package->title); ?></h5>
                                    <?php if ($package->featured): ?>
                                    <span class="hp-status-badge featured"><?php echo Text::_('COM_HOLIDAYPACKAGES_FEATURED'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $this->escape($package->destination_title); ?></td>
                        <td>
                            <strong><?php echo $package->booking_count; ?></strong>
                            <span class="text-muted"><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKINGS'); ?></span>
                        </td>
                        <td><?php echo $this->formatCurrency($package->total_revenue, $package->currency); ?></td>
                        <td>
                            <?php if ($package->rating > 0): ?>
                            <div class="hp-rating-display">
                                <?php echo HolidaypackagesHelper::getStarRating($package->rating, 5, 'hp-rating-sm'); ?>
                                <span class="hp-rating-text"><?php echo number_format($package->rating, 1); ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-muted"><?php echo Text::_('COM_HOLIDAYPACKAGES_NO_RATINGS'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=package.edit&id=' . $package->id); ?>" 
                                   class="btn btn-sm btn-outline-primary" title="<?php echo Text::_('JACTION_EDIT'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo Route::_('index.php?option=com_holidaypackages&view=bookings&filter[package_id]=' . $package->id); ?>" 
                                   class="btn btn-sm btn-outline-info" title="<?php echo Text::_('COM_HOLIDAYPACKAGES_VIEW_BOOKINGS'); ?>">
                                    <i class="fas fa-calendar-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <?php if (!empty($this->sidebar)): ?>
    <div id="j-sidebar-container" class="span2">
        <?php echo $this->sidebar; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.hp-package-thumb {
    width: 50px;
    height: 35px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 10px;
}

.hp-package-info {
    display: flex;
    align-items: center;
}

.hp-package-info h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.hp-rating-display {
    display: flex;
    align-items: center;
    gap: 5px;
}

.hp-rating-sm {
    font-size: 12px;
}

.hp-rating-text {
    font-size: 13px;
    color: #666;
}

.hp-recent-list {
    max-height: 400px;
    overflow-y: auto;
}

.hp-recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.hp-recent-item:last-child {
    border-bottom: none;
}

.hp-item-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.hp-item-info p {
    margin: 0;
    font-size: 12px;
}

.hp-item-meta {
    text-align: right;
}

.hp-item-amount {
    font-weight: 600;
    color: #2c3e50;
    margin-top: 5px;
}

.hp-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.hp-empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.hp-quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.hp-chart-footer {
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}
</style>