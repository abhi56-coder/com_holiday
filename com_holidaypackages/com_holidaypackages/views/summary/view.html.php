<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Pagination\Pagination;

class HolidaypackagesViewSummary extends HtmlView {
    protected $destinations;
    protected $pagination;
    protected $state;
    protected $filterForm;
    protected $activeFilters;
    protected $input; // Add input property for accessing request data

    public function display($tpl = null) {
        // Explicitly load the model
        $model = $this->getModel('Summary');
        if ($model === null) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_MODEL_NOT_FOUND'), 'error');
            return false;
        }

        // Get data from the model
        $this->destinations = $this->get('Destinations');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Get the input object for accessing request data
        $this->input = Factory::getApplication()->input;

        // Check for errors
        if ($this->state === null) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_STATE_NOT_FOUND'), 'warning');
            $this->state = new \Joomla\CMS\Object\CMSObject();
            $this->state->set('list.ordering', 'id');
            $this->state->set('list.direction', 'ASC');
        }

        if ($this->destinations === null) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_ITEMS_NOT_FOUND'), 'warning');
            $this->destinations = array();
        }

        if ($this->pagination === null) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_PAGINATION_NOT_FOUND'), 'warning');
            $this->pagination = new Pagination(0, 0, 0);
        }

        // Set up the sidebar
        $this->addSidebar();

        // Add the toolbar (only for the default layout)
        if ($this->getLayout() !== 'details') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }

    protected function addSidebar() {
        // Ensure the helper class is loaded
        JLoader::register('HolidaypackagesHelper', JPATH_ADMINISTRATOR . '/components/com_holidaypackages/helpers/holidaypackages.php');

        // Add submenu
        HolidaypackagesHelper::addSubmenu('summary');
        $this->sidebar = HTMLHelper::_('sidebar.render');

        // Add filter options for published state
        HTMLHelper::_('sidebar.addFilter',
            Text::_('JOPTION_SELECT_PUBLISHED'),
            'filter_published',
            HTMLHelper::_('select.options', HTMLHelper::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
        );
    }

    protected function addToolbar() {
        $user = Factory::getUser();
        $canDo = \Joomla\CMS\Helper\ContentHelper::getActions('com_holidaypackages');

        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_SUMMARY'), 'stack');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('destination.add');
        }

        if ($canDo->get('core.edit')) {
            ToolbarHelper::editList('destination.edit');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            ToolbarHelper::deleteList(Text::_('JTOOLBAR_DELETE_CONFIRM'), 'summary.delete', 'JTOOLBAR_DELETE');
        }
    }
}