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


class HolidaypackagesViewPackages extends HtmlView
{
    public $items;
    public $pagination;
    public $state;
    public $destination_id;
    public $destination_title;
    public $filterForm;
    public $activeFilters;
    public $category;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->destination_id = $app->input->getInt('destination_id', $this->state->get('filter.destination_id', 0));
        $this->category       = $app->input->get('category', '', 'cmd'); // category value from URL

        if ($this->destination_id) {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('n4gvg__holiday_destinations'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->destination_id);
            $db->setQuery($query);
            $this->destination_title = $db->loadResult() ?: Text::_('COM_HOLIDAYPACKAGES_UNKNOWN_DESTINATION');
        } else {
            $this->destination_title = Text::_('COM_HOLIDAYPACKAGES_ALL_DESTINATIONS');
        }

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
        ToolbarHelper::title(Text::sprintf('COM_HOLIDAYPACKAGES_PACKAGES_FOR_DESTINATION', $this->destination_title), 'stack');

     

        $canDo = ContentHelper::getActions('com_holidaypackages');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('packages.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('icon-ellipsis-h')
                ->buttonClass('btn btn-action')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();
            $childBar->publish('packages.publish')->listCheck(true);
            $childBar->unpublish('packages.unpublish')->listCheck(true);
            $childBar->archive('packages.archive')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('packages.trash')->listCheck(true);
            }
        }

        if ($canDo->get('core.edit')) {
            $toolbar->standardButton('edit')
                ->text('JTOOLBAR_EDIT')
                ->task('packages.edit')
                ->listCheck(true);
        }

        if ($canDo->get('core.delete')) {
            $toolbar->delete('packages.delete')
                ->text('JTOOLBAR_DELETE')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('packages.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin')) {
            $toolbar->preferences('com_holidaypackages');
        }
   // Add Back button with category parameter
        $categoryParam = '&category=national';
        ToolbarHelper::back('JTOOLBAR_BACK', Route::_('index.php?option=com_holidaypackages&view=destinations' . $categoryParam, false));
        HTMLHelper::_('sidebar.setAction', 'index.php?option=com_holidaypackages&view=packages&destination_id=' . $this->destination_id);
    }

    protected function getSortFields()
    {
        return array(
            'p.id'              => Text::_('JGRID_HEADING_ID'),
            'p.title'           => Text::_('COM_HOLIDAYPACKAGES_FIELD_TITLE_LABEL'),
            'destination_title' => Text::_('COM_HOLIDAYPACKAGES_FIELD_DESTINATION_LABEL'),
            'p.duration'        => Text::_('COM_HOLIDAYPACKAGES_FIELD_DURATION_LABEL'),
            'p.price'           => Text::_('COM_HOLIDAYPACKAGES_FIELD_PRICE_LABEL'),
            'p.published'       => Text::_('JSTATUS')
        );
    }
}
