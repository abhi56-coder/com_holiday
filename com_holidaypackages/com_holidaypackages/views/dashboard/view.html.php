<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;

class HolidaypackagesViewDashboard extends HtmlView
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
        ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_TRAVELING_PACKAGES'), 'stack holidaypackages');

        $canDo = ContentHelper::getActions('com_holidaypackages');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('dashboard.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('dashboard.publish')->listCheck(true);
            $childBar->unpublish('dashboard.unpublish')->listCheck(true);
            $childBar->archive('dashboard.archive')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('dashboard.trash')->listCheck(true);
            }
        }

        if ($canDo->get('core.edit')) {
            $toolbar->edit('dashboard.edit')
                ->text('JTOOLBAR_EDIT')
                ->listCheck(true);
        }

        if ($canDo->get('core.delete')) {
            $toolbar->delete('dashboard.delete')
                ->text('JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('dashboard.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        // ✅ Add "Contacts" button
        $toolbar->appendButton('Standard', 'user', 'Contacts', 'dashboard.contacts', false);

        // ✅ Add custom "Add Cities" button with Joomla default plus icon
        if ($canDo->get('core.create')) {
            ToolbarHelper::custom(
                'dashboard.addcities',       // Task
                'icon-plus',                 // Joomla default plus icon
                '',                          // No alt icon
                'COM_HOLIDAYPACKAGES_ADD_CITIES', // Button Text
                false
            );
        }

        // Options button
        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->preferences('com_holidaypackages');
        }

        HTMLHelper::_('sidebar.setAction', 'index.php?option=com_holidaypackages&view=dashboard');
    }

    protected function getSortFields()
    {
        return [
            'd.id'        => Text::_('JGRID_HEADING_ID'),
            'd.title'     => Text::_('JGLOBAL_TITLE'),
            'd.category'  => Text::_('COM_HOLIDAYPACKAGES_CATEGORY'),
            'd.published' => Text::_('JSTATUS')
        ];
    }
}
