<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;


class HolidaypackagesViewPolicies extends HtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;
    public $packageId;
    public $destinationId;
    private $packageName;

    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->filterForm = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $input = Factory::getApplication()->getInput();
        $this->packageId = $input->getInt('package_id');
        $this->destinationId = $input->getInt('destination_id');
        $this->packageName = $this->getPackageName();

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors));
        }

        // Set the selected value in the filter form
        if ($this->filterForm && $this->state->get('filter.published') !== null) {
            $this->filterForm->setValue('published', 'filter', $this->state->get('filter.published'));
        } elseif ($this->filterForm) {
            $this->filterForm->setValue('published', 'filter', '');
        }

        $this->addToolbar();
        $this->sidebar = HTMLHelper::_('sidebar.render');
        HTMLHelper::_('searchtools.form', '#adminForm');

        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $toolbar = \Joomla\CMS\Toolbar\Toolbar::getInstance('toolbar');
        $title = $this->packageName ? Text::_('COM_HOLIDAYPACKAGES_POLICIES') . ': ' . $this->packageName : Text::_('COM_HOLIDAYPACKAGES_POLICIES');
        ToolbarHelper::title($title, 'stack');

        $canDo = ContentHelper::getActions('com_holidaypackages');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('policy.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('policies.publish')->listCheck(true);
            $childBar->unpublish('policies.unpublish')->listCheck(true);
            $childBar->archive('policies.archive')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('policies.trash')->listCheck(true);
            }
        }

        ToolbarHelper::editList('policy.edit');
        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'policies.delete');
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('policies.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin')) {
            $toolbar->preferences('com_holidaypackages');
        }

        // Add Back button with dynamic destination_id
        $backLink = Route::_('index.php?option=com_holidaypackages&view=packages&filter[destination_id]=' . (int) $this->destinationId, false);
        ToolbarHelper::back('JTOOLBAR_BACK', $backLink);
        HTMLHelper::_('sidebar.setAction', 'index.php?option=com_holidaypackages&view=policies');
    }

    protected function getSortFields()
    {
        return array(
            'id' => Text::_('JGRID_HEADING_ID'),
            'title' => Text::_('COM_HOLIDAYPACKAGES_TITLE'),
            'image' => Text::_('COM_HOLIDAYPACKAGES_FIELD_IMAGE_LABEL'),
            'published' => Text::_('JSTATUS')
        );
    }

    private function getPackageName()
    {
        if ($this->packageId) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('n4gvg__holiday_packages'))
                ->where($db->quoteName('id') . ' = ' . (int)$this->packageId);
            $db->setQuery($query);
            $packageName = $db->loadResult();
            return $packageName ?: '';
        }
        return '';
    }
}