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
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Dashboard view class.
 *
 * @since  2.0.0
 */
class HolidaypackagesViewDashboard extends HtmlView
{
    /**
     * The statistics data
     *
     * @var    array
     * @since  2.0.0
     */
    protected $stats;

    /**
     * The recent bookings data
     *
     * @var    array
     * @since  2.0.0
     */
    protected $recentBookings;

    /**
     * The popular packages data
     *
     * @var    array
     * @since  2.0.0
     */
    protected $popularPackages;

    /**
     * The component parameters
     *
     * @var    Registry
     * @since  2.0.0
     */
    protected $params;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     *
     * @since   2.0.0
     */
    public function display($tpl = null)
    {
        // Get data from the model
        $model = $this->getModel();
        $this->stats = $model->getStats();
        $this->recentBookings = $model->getRecentBookings(10);
        $this->popularPackages = $model->getPopularPackages(5);
        $this->params = $model->getParams();

        // Check for errors
        if (count($errors = $this->get('Errors')))
        {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        // Set the toolbar
        $this->addToolbar();

        // Add the sidebar
        HolidaypackagesHelper::addSubmenu('dashboard');
        $this->sidebar = HTMLHelper::_('sidebar.render');

        // Load required assets
        $this->loadAssets();

        return parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function addToolbar()
    {
        $canDo = HolidaypackagesHelper::getActions();

        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_DASHBOARD_TITLE'), 'dashboard');

        if ($canDo->get('core.admin') || $canDo->get('core.options'))
        {
            ToolbarHelper::preferences('com_holidaypackages');
        }

        if ($canDo->get('core.create'))
        {
            ToolbarHelper::addNew('package.add', Text::_('COM_HOLIDAYPACKAGES_TOOLBAR_NEW_PACKAGE'));
        }

        // Custom buttons
        $bar = Factory::getApplication()->getDocument()->getToolbar();
        
        if ($canDo->get('holidaypackages.manage.bookings'))
        {
            $bar->appendButton('Link', 'list', Text::_('COM_HOLIDAYPACKAGES_TOOLBAR_BOOKINGS'), 
                'index.php?option=com_holidaypackages&view=bookings');
        }
        
        if ($canDo->get('holidaypackages.view.reports'))
        {
            $bar->appendButton('Link', 'chart', Text::_('COM_HOLIDAYPACKAGES_TOOLBAR_REPORTS'), 
                'index.php?option=com_holidaypackages&view=reports');
        }

        ToolbarHelper::help('JHELP_COMPONENTS_HOLIDAYPACKAGES_DASHBOARD');
    }

    /**
     * Load required CSS and JavaScript assets
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function loadAssets()
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();

        // Load Chart.js for analytics
        $wa->registerAndUseScript('chart-js', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', [], ['defer' => true]);
        
        // Load dashboard specific CSS and JS
        $wa->registerAndUseStyle('com_holidaypackages.dashboard', 'media/com_holidaypackages/css/dashboard.css');
        $wa->registerAndUseScript('com_holidaypackages.dashboard', 'media/com_holidaypackages/js/dashboard.js', [], ['defer' => true]);

        // Pass data to JavaScript
        $dashboardData = [
            'stats' => $this->stats,
            'ajaxUrl' => 'index.php?option=com_holidaypackages&task=ajax&' . Factory::getSession()->getFormToken() . '=1',
            'baseUrl' => 'index.php?option=com_holidaypackages'
        ];

        $wa->addInlineScript('
            window.HolidayPackagesDashboard = ' . json_encode($dashboardData) . ';
        ', [], [], ['chart-js']);
    }

    /**
     * Get the formatted currency value
     *
     * @param   float   $amount    The amount
     * @param   string  $currency  The currency code
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function formatCurrency($amount, $currency = null)
    {
        if (!$currency)
        {
            $currency = $this->params->get('default_currency', 'USD');
        }

        return HolidaypackagesHelper::formatCurrency($amount, $currency);
    }

    /**
     * Get the status badge HTML
     *
     * @param   string  $status  The status
     * @param   string  $type    The type (booking, payment, etc.)
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function getStatusBadge($status, $type = 'booking')
    {
        switch ($type)
        {
            case 'booking':
                return HolidaypackagesHelper::getBookingStatusBadge($status);
            case 'payment':
                return HolidaypackagesHelper::getPaymentStatusBadge($status);
            default:
                return HolidaypackagesHelper::getStatusBadge($status);
        }
    }

    /**
     * Get progress percentage
     *
     * @param   int  $current  Current value
     * @param   int  $target   Target value
     *
     * @return  int
     *
     * @since   2.0.0
     */
    public function getProgressPercentage($current, $target)
    {
        if ($target == 0) return 0;
        
        return min(100, round(($current / $target) * 100));
    }
}