<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

class HolidaypackagesViewCities extends HtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $filterForm;
    public $activeFilters;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        // ✅ Set filter published value
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
        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_CITIES'), 'building');

        $canDo = ContentHelper::getActions('com_holidaypackages');

        // Add New
        if ($canDo->get('core.create')) {
            $toolbar->addNew('cities.add');
        }

        // Edit
        if ($canDo->get('core.edit')) {
            $toolbar->edit('cities.edit')
                ->text('JTOOLBAR_EDIT')
                ->listCheck(true);
        }

        // Publish / Unpublish / Archive / Trash
        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('cities.publish')->listCheck(true);
            $childBar->unpublish('cities.unpublish')->listCheck(true);
            $childBar->archive('cities.archive')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('cities.trash')->listCheck(true);
            }
        }

        // Delete
        if ($canDo->get('core.delete')) {
            $toolbar->delete('cities.delete')
                ->text('JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        // Empty Trash
        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('cities.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        // Preferences
        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            ToolbarHelper::preferences('com_holidaypackages');
        }

        // Back button
        ToolbarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_holidaypackages&view=dashboard');
    }

    protected function getSortFields()
    {
        return [
            'c.id'        => Text::_('JGRID_HEADING_ID'),
            'c.name'      => Text::_('COM_HOLIDAYPACKAGES_CITY_NAME'),
            'c.published' => Text::_('JSTATUS')
        ];
    }
}
