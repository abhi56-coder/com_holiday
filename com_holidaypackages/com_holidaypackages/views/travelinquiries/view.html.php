<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;

class HolidaypackagesViewTravelinquiries extends HtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

   public function display($tpl = null)
{
    $this->items = $this->get('Items');
    $this->pagination = $this->get('Pagination');
    $this->state = $this->get('State');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');

    if (count($errors = $this->get('Errors'))) {
        throw new \Exception(implode("\n", $errors), 500);
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
        $toolbar = Toolbar::getInstance('toolbar');
        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_TRAVEL_INQUIRIES'), 'user');

        $canDo = ContentHelper::getActions('com_holidaypackages');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('travelinquiries.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('travelinquiries.publish')->listCheck(true);
            $childBar->unpublish('travelinquiries.unpublish')->listCheck(true);
            $childBar->archive('travelinquiries.archive')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('travelinquiries.trash')->listCheck(true);
            }
        }

        if ($canDo->get('core.edit')) {
            $toolbar->edit('travelinquiries.edit')
                ->text('JTOOLBAR_EDIT')
                ->listCheck(true);
        }

        if ($canDo->get('core.delete')) {
            $toolbar->delete('travelinquiries.delete')
                ->text('JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
        ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_holidaypackages&view=dashboard');

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('travelinquiries.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }


        // Options button
        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_holidaypackages');
        }

        HTMLHelper::_('sidebar.setAction', 'index.php?option=com_holidaypackages&view=travelinquiries');
    }

    protected function getSortFields()
    {
        return [
            'd.id' => Text::_('JGRID_HEADING_ID'),
            'd.first_name' => Text::_('JGLOBAL_FIRST_NAME'),
            'd.last_name' => Text::_('JGLOBAL_LAST_NAME'),
            'd.city' => Text::_('JGLOBAL_CITY'),
            'd.state' => Text::_('JGLOBAL_STATE'),
            'd.destination' => Text::_('JGLOBAL_DESTINATION'),
            'd.budget' => Text::_('JGLOBAL_BUDGET'),
            'd.travelers' => Text::_('JGLOBAL_TRAVELERS'),
            'd.departure_city' => Text::_('JGLOBAL_DEPARTURE_CITY'),
            'd.insurance' => Text::_('JGLOBAL_INSURANCE'),
            'd.published' => Text::_('JSTATUS')
        ];
    }
}