<?php
/**
 * Holiday Packages Enhanced Component
 * 
 * @package     HolidayPackages
 * @subpackage  Administrator
 * @author      Your Name
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Packages List View for Holiday Packages Enhanced Component
 * 
 * Displays list of holiday packages with filtering, search, and bulk operations
 */
class HolidayPackagesViewPackages extends HtmlView
{
    /**
     * The search tools form
     *
     * @var    Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     */
    public $activeFilters;

    /**
     * Package data
     *
     * @var    array
     */
    protected $items;

    /**
     * Pagination object
     *
     * @var    Pagination
     */
    protected $pagination;

    /**
     * Model state data
     *
     * @var    \Joomla\CMS\Object\CMSObject
     */
    protected $state;

    /**
     * Is this view an Empty State
     *
     * @var   boolean
     */
    private $isEmptyState = false;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     * @return  mixed  A string if successful, otherwise an Error object
     */
    public function display($tpl = null)
    {
        // Get data from the model
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors
        if (count($errors = $this->get('Errors'))) {
            throw new Exception(implode("\n", $errors), 500);
        }

        // Check if we have items or if it's an empty state
        if (empty($this->items) && $this->isEmptyState = $this->get('IsEmptyState')) {
            $this->setLayout('emptystate');
        }

        // Add the toolbar
        $this->addToolbar();

        // Load required assets
        $this->loadAssets();

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar
     *
     * @return  void
     */
    protected function addToolbar(): void
    {
        $canDo = ContentHelper::getActions('com_holidaypackages');
        $user  = Factory::getUser();

        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_PACKAGES_TITLE'), 'cube');

        // Add toolbar buttons based on permissions
        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('package.add');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('packages.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('packages.unpublish', 'JTOOLBAR_UNPUBLISH', true);
            ToolbarHelper::archiveList('packages.archive');
            ToolbarHelper::checkin('packages.checkin');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            ToolbarHelper::deleteList('JGLOBAL_CONFIRM_DELETE', 'packages.delete', 'JTOOLBAR_EMPTY_TRASH');
        } elseif ($canDo->get('core.edit.state')) {
            ToolbarHelper::trash('packages.trash');
        }

        // Batch operations
        if ($canDo->get('core.edit.state')) {
            HTMLHelper::_('bootstrap.modal', '.modal');
            
            $title = Text::_('JTOOLBAR_BATCH');
            $dhtml = "<button type=\"button\" data-bs-toggle=\"modal\" data-bs-target=\"#collapseModal\" class=\"btn btn-sm btn-secondary\">
                        <span class=\"icon-copy\" aria-hidden=\"true\"></span>
                        $title
                      </button>";
            
            $toolbar->appendButton('Custom', $dhtml, 'batch');
        }

        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_holidaypackages');
        }

        ToolbarHelper::help('JHELP_COMPONENTS_HOLIDAYPACKAGES_PACKAGES');
    }

    /**
     * Load required assets (CSS/JS)
     *
     * @return  void
     */
    protected function loadAssets(): void
    {
        $wa = $this->document->getWebAssetManager();

        // Load component assets
        $wa->useStyle('com_holidaypackages.admin')
           ->useScript('com_holidaypackages.admin');

        // Load Bootstrap components
        $wa->useScript('bootstrap.modal');

        // Load additional scripts for packages management
        $this->document->addScriptDeclaration('
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof HolidayPackagesAdmin !== "undefined") {
                    HolidayPackagesAdmin.packages.init();
                }
            });
        ');
    }

    /**
     * Get package rating display
     *
     * @param   float  $rating      Package rating
     * @param   int    $reviewCount Review count
     * @return  string  HTML rating display
     */
    public function getRatingDisplay($rating, $reviewCount)
    {
        $rating = (float) $rating;
        $reviewCount = (int) $reviewCount;
        
        if ($rating <= 0 || $reviewCount === 0) {
            return '<span class="text-muted">' . Text::_('COM_HOLIDAYPACKAGES_NO_REVIEWS') . '</span>';
        }

        $fullStars = floor($rating);
        $hasHalfStar = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

        $html = '<div class="rating-display">';
        $html .= '<span class="rating-stars">';
        
        // Full stars
        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<span class="icon-star text-warning"></span>';
        }
        
        // Half star
        if ($hasHalfStar) {
            $html .= '<span class="icon-star-half text-warning"></span>';
        }
        
        // Empty stars
        for ($i = 0; $i < $emptyStars; $i++) {
            $html .= '<span class="icon-star text-muted"></span>';
        }
        
        $html .= '</span>';
        $html .= '<span class="rating-text ms-2">';
        $html .= number_format($rating, 1) . ' (' . $reviewCount . ')';
        $html .= '</span>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Get package price display
     *
     * @param   float   $price        Package price
     * @param   string  $currency     Currency code
     * @param   float   $originalPrice Original price (for discounts)
     * @return  string  HTML price display
     */
    public function getPriceDisplay($price, $currency = 'INR', $originalPrice = null)
    {
        $price = (float) $price;
        $originalPrice = $originalPrice ? (float) $originalPrice : null;
        
        $html = '<div class="price-display">';
        
        if ($originalPrice && $originalPrice > $price) {
            // Show discounted price
            $discount = round((($originalPrice - $price) / $originalPrice) * 100);
            $html .= '<span class="text-decoration-line-through text-muted me-2">';
            $html .= $this->formatCurrency($originalPrice, $currency);
            $html .= '</span>';
            $html .= '<span class="fw-bold text-success">';
            $html .= $this->formatCurrency($price, $currency);
            $html .= '</span>';
            $html .= '<span class="badge bg-danger ms-2">' . $discount . '% OFF</span>';
        } else {
            $html .= '<span class="fw-bold">';
            $html .= $this->formatCurrency($price, $currency);
            $html .= '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Format currency value
     *
     * @param   float   $amount    Amount to format
     * @param   string  $currency  Currency code
     * @return  string  Formatted currency
     */
    public function formatCurrency($amount, $currency = 'INR')
    {
        $amount = (float) $amount;
        
        switch ($currency) {
            case 'INR':
                return '₹' . number_format($amount, 2);
            case 'USD':
                return '$' . number_format($amount, 2);
            case 'EUR':
                return '€' . number_format($amount, 2);
            default:
                return $currency . ' ' . number_format($amount, 2);
        }
    }

    /**
     * Get package availability status
     *
     * @param   object  $package  Package object
     * @return  string  HTML availability status
     */
    public function getAvailabilityStatus($package)
    {
        $now = Factory::getDate();
        
        // Check availability dates
        if (!empty($package->available_from)) {
            $availableFrom = Factory::getDate($package->available_from);
            if ($now < $availableFrom) {
                return '<span class="badge bg-warning">' . 
                       Text::sprintf('COM_HOLIDAYPACKAGES_AVAILABLE_FROM', HTMLHelper::_('date', $package->available_from, 'Y-m-d')) . 
                       '</span>';
            }
        }
        
        if (!empty($package->available_to)) {
            $availableTo = Factory::getDate($package->available_to);
            if ($now > $availableTo) {
                return '<span class="badge bg-danger">' . Text::_('COM_HOLIDAYPACKAGES_AVAILABILITY_EXPIRED') . '</span>';
            }
        }

        // Check capacity if specified
        if (!empty($package->max_capacity)) {
            $bookedCapacity = (int) ($package->current_bookings ?? 0);
            $remainingCapacity = $package->max_capacity - $bookedCapacity;
            
            if ($remainingCapacity <= 0) {
                return '<span class="badge bg-danger">' . Text::_('COM_HOLIDAYPACKAGES_FULLY_BOOKED') . '</span>';
            } elseif ($remainingCapacity <= 5) {
                return '<span class="badge bg-warning">' . 
                       Text::sprintf('COM_HOLIDAYPACKAGES_LIMITED_AVAILABILITY', $remainingCapacity) . 
                       '</span>';
            }
        }

        return '<span class="badge bg-success">' . Text::_('COM_HOLIDAYPACKAGES_AVAILABLE') . '</span>';
    }

    /**
     * Get package image thumbnail
     *
     * @param   string  $images  JSON encoded images
     * @return  string  HTML image thumbnail
     */
    public function getImageThumbnail($images)
    {
        if (empty($images)) {
            return '<div class="package-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">
                        <span class="icon-image text-muted"></span>
                    </div>';
        }

        $imageArray = json_decode($images, true);
        if (!$imageArray || !is_array($imageArray) || empty($imageArray[0])) {
            return '<div class="package-thumbnail bg-light d-flex align-items-center justify-content-center" style="width: 60px; height: 40px;">
                        <span class="icon-image text-muted"></span>
                    </div>';
        }

        $primaryImage = $imageArray[0];
        $imageUrl = is_array($primaryImage) ? $primaryImage['url'] : $primaryImage;
        
        return '<img src="' . htmlspecialchars($imageUrl) . '" alt="Package thumbnail" class="package-thumbnail rounded" style="width: 60px; height: 40px; object-fit: cover;">';
    }

    /**
     * Get booking statistics for package
     *
     * @param   object  $package  Package object
     * @return  string  HTML booking statistics
     */
    public function getBookingStats($package)
    {
        $html = '<div class="booking-stats small">';
        
        if (!empty($package->bookings_count)) {
            $html .= '<div class="text-success">';
            $html .= '<span class="icon-calendar"></span> ';
            $html .= Text::sprintf('COM_HOLIDAYPACKAGES_BOOKINGS_COUNT', $package->bookings_count);
            $html .= '</div>';
        }
        
        if (!empty($package->total_revenue)) {
            $html .= '<div class="text-primary">';
            $html .= '<span class="icon-wallet"></span> ';
            $html .= Text::sprintf('COM_HOLIDAYPACKAGES_REVENUE', $this->formatCurrency($package->total_revenue));
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Check if package can be edited
     *
     * @param   object  $item  Package item
     * @return  boolean  True if editable
     */
    public function canEdit($item)
    {
        $user = Factory::getUser();
        
        if (!empty($item->checked_out)) {
            return $item->checked_out == $user->id;
        }

        return $user->authorise('core.edit', 'com_holidaypackages');
    }

    /**
     * Get quick action buttons for package
     *
     * @param   object  $item  Package item
     * @return  string  HTML buttons
     */
    public function getQuickActions($item)
    {
        $html = '';
        $user = Factory::getUser();
        $canEdit = $this->canEdit($item);
        $canEditState = $user->authorise('core.edit.state', 'com_holidaypackages');

        // Edit button
        if ($canEdit) {
            $html .= '<a href="' . 'index.php?option=com_holidaypackages&task=package.edit&id=' . $item->id . '" class="btn btn-sm btn-outline-primary me-1" title="' . Text::_('JACTION_EDIT') . '">';
            $html .= '<span class="icon-edit"></span></a>';
        }

        // Duplicate button
        if ($user->authorise('core.create', 'com_holidaypackages')) {
            $html .= '<button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="HolidayPackagesAdmin.packages.duplicate(' . $item->id . ')" title="' . Text::_('COM_HOLIDAYPACKAGES_DUPLICATE_PACKAGE') . '">';
            $html .= '<span class="icon-copy"></span></button>';
        }

        // Preview button (if published)
        if ($item->published == 1) {
            $previewUrl = 'index.php?option=com_holidaypackages&view=package&id=' . $item->id;
            $html .= '<a href="' . $previewUrl . '" target="_blank" class="btn btn-sm btn-outline-info me-1" title="' . Text::_('COM_HOLIDAYPACKAGES_PREVIEW_PACKAGE') . '">';
            $html .= '<span class="icon-eye"></span></a>';
        }

        // Bookings button (if has bookings)
        if (!empty($item->bookings_count)) {
            $bookingsUrl = 'index.php?option=com_holidaypackages&view=bookings&filter[package_id]=' . $item->id;
            $html .= '<a href="' . $bookingsUrl . '" class="btn btn-sm btn-outline-success me-1" title="' . Text::_('COM_HOLIDAYPACKAGES_VIEW_BOOKINGS') . '">';
            $html .= '<span class="icon-calendar"></span></a>';
        }

        return $html;
    }
}