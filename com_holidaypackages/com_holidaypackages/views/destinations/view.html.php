<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;


class HolidaypackagesViewDestinations extends HtmlView
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
            throw new \Exception(implode("\n", $errors));
        }

        $this->addToolbar();
        $this->sidebar = HTMLHelper::_('sidebar.render');
        HTMLHelper::_('searchtools.form', '#adminForm');

        parent::display($tpl);
    }
protected function addToolbar()
{
    $toolbar = Toolbar::getInstance('toolbar');
    ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_DESTINATIONS'), 'stack');

    $canDo = ContentHelper::getActions('com_holidaypackages');

    $category = $this->state->get('filter.category');
    $categoryParam = $category ? '&category=' . urlencode($category) : '';

    if ($canDo->get('core.create')) {
        $toolbar->addNew('destinations.add', 'JTOOLBAR_NEW');
    }

    if ($canDo->get('core.edit.state')) {
        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);

        $childBar = $dropdown->getChildToolbar();
        $childBar->publish('destinations.publish')->listCheck(true);
        $childBar->unpublish('destinations.unpublish')->listCheck(true);
        $childBar->archive('destinations.archive')->listCheck(true);

        if ($this->state->get('filter.published') != -2) {
            $childBar->trash('destinations.trash')->listCheck(true);
        }
    }

    ToolbarHelper::editList('destinations.edit');
    if ($canDo->get('core.delete')) {
        ToolbarHelper::deleteList('', 'destinations.delete');
    }

    if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
        $toolbar->delete('destinations.delete')
            ->text('JTOOLBAR_EMPTY_TRASH')
            ->message('JGLOBAL_CONFIRM_DELETE')
            ->listCheck(true);
    }

    if ($canDo->get('core.admin')) {
        $toolbar->preferences('com_holidaypackages');
    }

        // Add Back button
        ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_holidaypackages&view=dashboard' . $categoryParam, false));

    HTMLHelper::_('sidebar.setAction', 'index.php?option=com_holidaypackages&view=destinations' . $categoryParam);
}


    protected function getSortFields()
    {
        return array(
            'id' => Text::_('JGRID_HEADING_ID'),
            'title' => Text::_('COM_HOLIDAYPACKAGES_FIELD_TITLE_LABEL'),
            'price' => Text::_('COM_HOLIDAYPACKAGES_FIELD_PRICE_LABEL'),
            'image' => Text::_('COM_HOLIDAYPACKAGES_FIELD_IMAGE_LABEL'),
            'published' => Text::_('JSTATUS')
        );
    }
}