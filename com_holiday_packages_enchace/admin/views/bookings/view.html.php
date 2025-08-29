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
 * Bookings List View for Holiday Packages Enhanced Component
 * 
 * Displays list of bookings with filtering, search, and bulk operations
 */
class HolidayPackagesViewBookings extends HtmlView
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
     * Category data
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

        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_BOOKINGS_TITLE'), 'calendar-check');

        // Add toolbar buttons based on permissions
        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('booking.add');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('bookings.confirm', 'COM_HOLIDAYPACKAGES_TOOLBAR_CONFIRM');
            ToolbarHelper::unpublish('bookings.cancel', 'COM_HOLIDAYPACKAGES_TOOLBAR_CANCEL');
            ToolbarHelper::custom('bookings.complete', 'checkbox', 'checkbox', 'COM_HOLIDAYPACKAGES_TOOLBAR_COMPLETE', true);
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

        ToolbarHelper::help('JHELP_COMPONENTS_HOLIDAYPACKAGES_BOOKINGS');
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

        // Load additional scripts for bookings management
        $this->document->addScriptDeclaration('
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof HolidayPackagesAdmin !== "undefined") {
                    HolidayPackagesAdmin.bookings.init();
                }
            });
        ');
    }

    /**
     * Get the filter form
     *
     * @return  Form
     */
    public function getFilterForm()
    {
        return $this->filterForm;
    }

    /**
     * Get booking status badge HTML
     *
     * @param   string  $status  Booking status
     * @return  string  HTML badge
     */
    public function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning">' . Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_PENDING') . '</span>',
            'confirmed' => '<span class="badge bg-success">' . Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_CONFIRMED') . '</span>',
            'cancelled' => '<span class="badge bg-danger">' . Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_CANCELLED') . '</span>',
            'completed' => '<span class="badge bg-primary">' . Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_COMPLETED') . '</span>',
            'refunded' => '<span class="badge bg-secondary">' . Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_REFUNDED') . '</span>'
        ];

        return $badges[$status] ?? '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>';
    }

    /**
     * Get payment status badge HTML
     *
     * @param   float   $totalAmount  Total booking amount
     * @param   float   $paidAmount   Paid amount
     * @param   string  $status       Booking status
     * @return  string  HTML badge
     */
    public function getPaymentStatusBadge($totalAmount, $paidAmount, $status)
    {
        if ($status === 'cancelled' || $status === 'refunded') {
            return '<span class="badge bg-secondary">N/A</span>';
        }

        $paidAmount = (float) $paidAmount;
        $totalAmount = (float) $totalAmount;

        if ($paidAmount >= $totalAmount) {
            return '<span class="badge bg-success">' . Text::_('COM_HOLIDAYPACKAGES_PAYMENT_PAID') . '</span>';
        } elseif ($paidAmount > 0) {
            return '<span class="badge bg-warning">' . Text::_('COM_HOLIDAYPACKAGES_PAYMENT_PARTIAL') . '</span>';
        } else {
            return '<span class="badge bg-danger">' . Text::_('COM_HOLIDAYPACKAGES_PAYMENT_UNPAID') . '</span>';
        }
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
     * Get traveler count display
     *
     * @param   int  $adults    Number of adults
     * @param   int  $children  Number of children
     * @param   int  $infants   Number of infants
     * @return  string  Formatted traveler count
     */
    public function getTravelerCount($adults, $children, $infants)
    {
        $parts = [];
        
        if ($adults > 0) {
            $parts[] = $adults . ' ' . Text::_('COM_HOLIDAYPACKAGES_ADULTS');
        }
        
        if ($children > 0) {
            $parts[] = $children . ' ' . Text::_('COM_HOLIDAYPACKAGES_CHILDREN');
        }
        
        if ($infants > 0) {
            $parts[] = $infants . ' ' . Text::_('COM_HOLIDAYPACKAGES_INFANTS');
        }

        return implode(', ', $parts);
    }

    /**
     * Get booking duration display
     *
     * @param   string  $startDate  Start date
     * @param   string  $endDate    End date
     * @return  string  Duration display
     */
    public function getBookingDuration($startDate, $endDate)
    {
        if (empty($startDate) || empty($endDate)) {
            return '';
        }

        $start = Factory::getDate($startDate);
        $end = Factory::getDate($endDate);
        $diff = $end->diff($start);

        if ($diff->days == 0) {
            return Text::_('COM_HOLIDAYPACKAGES_SAME_DAY');
        } elseif ($diff->days == 1) {
            return '1 ' . Text::_('COM_HOLIDAYPACKAGES_DAY');
        } else {
            return $diff->days . ' ' . Text::_('COM_HOLIDAYPACKAGES_DAYS');
        }
    }

    /**
     * Check if booking can be edited
     *
     * @param   object  $item  Booking item
     * @return  boolean  True if editable
     */
    public function canEdit($item)
    {
        $user = Factory::getUser();
        
        // Check core edit permission
        if (!$user->authorise('core.edit', 'com_holidaypackages')) {
            return false;
        }

        // Don't allow editing completed bookings
        if (in_array($item->status, ['completed', 'refunded'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if booking status can be changed
     *
     * @param   object  $item       Booking item
     * @param   string  $newStatus  Target status
     * @return  boolean  True if status can be changed
     */
    public function canChangeStatus($item, $newStatus)
    {
        $user = Factory::getUser();
        
        if (!$user->authorise('core.edit.state', 'com_holidaypackages')) {
            return false;
        }

        $currentStatus = $item->status;
        
        // Define allowed status transitions
        $allowedTransitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'cancelled' => [], // Cannot change from cancelled
            'completed' => ['refunded'],
            'refunded' => [] // Cannot change from refunded
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    /**
     * Get quick action buttons for booking
     *
     * @param   object  $item  Booking item
     * @return  string  HTML buttons
     */
    public function getQuickActions($item)
    {
        $html = '';
        $user = Factory::getUser();

        if (!$user->authorise('core.edit.state', 'com_holidaypackages')) {
            return $html;
        }

        $bookingId = $item->id;
        $status = $item->status;

        // Status-specific quick actions
        switch ($status) {
            case 'pending':
                if ($this->canChangeStatus($item, 'confirmed')) {
                    $html .= '<button type="button" class="btn btn-sm btn-success me-1" onclick="HolidayPackagesAdmin.bookings.changeStatus(' . $bookingId . ', \'confirmed\')" title="' . Text::_('COM_HOLIDAYPACKAGES_CONFIRM_BOOKING') . '">';
                    $html .= '<span class="icon-check"></span></button>';
                }
                
                if ($this->canChangeStatus($item, 'cancelled')) {
                    $html .= '<button type="button" class="btn btn-sm btn-danger me-1" onclick="HolidayPackagesAdmin.bookings.changeStatus(' . $bookingId . ', \'cancelled\')" title="' . Text::_('COM_HOLIDAYPACKAGES_CANCEL_BOOKING') . '">';
                    $html .= '<span class="icon-times"></span></button>';
                }
                break;

            case 'confirmed':
                if ($this->canChangeStatus($item, 'completed')) {
                    $html .= '<button type="button" class="btn btn-sm btn-primary me-1" onclick="HolidayPackagesAdmin.bookings.changeStatus(' . $bookingId . ', \'completed\')" title="' . Text::_('COM_HOLIDAYPACKAGES_COMPLETE_BOOKING') . '">';
                    $html .= '<span class="icon-checkbox"></span></button>';
                }
                break;

            case 'completed':
                if ($this->canChangeStatus($item, 'refunded')) {
                    $html .= '<button type="button" class="btn btn-sm btn-warning me-1" onclick="HolidayPackagesAdmin.bookings.processRefund(' . $bookingId . ')" title="' . Text::_('COM_HOLIDAYPACKAGES_PROCESS_REFUND') . '">';
                    $html .= '<span class="icon-undo"></span></button>';
                }
                break;
        }

        // View details button (always available)
        $html .= '<a href="' . 'index.php?option=com_holidaypackages&task=booking.edit&id=' . $bookingId . '" class="btn btn-sm btn-outline-primary" title="' . Text::_('COM_HOLIDAYPACKAGES_VIEW_DETAILS') . '">';
        $html .= '<span class="icon-eye"></span></a>';

        return $html;
    }
}