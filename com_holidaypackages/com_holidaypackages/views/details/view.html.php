<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class HolidaypackagesViewDetails extends HtmlView {
    protected $items;
    protected $pagination;
    protected $state;
    protected $form;

    public function display($tpl = null) {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        if ($this->items === false) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEMS_FOUND'), 'error');
            return false;
        }

        if (empty($this->items)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_NO_ITEMS_FOUND'), 'warning');
        }

        // Load the form for edit view
        if ($this->getLayout() == 'edit') {
            $this->form = $this->get('Form');
        }

        $this->addToolbar();
        $this->sidebar = HTMLHelper::_('sidebar.render');
        parent::display($tpl);
    }

    protected function addToolbar() {
        $app = Factory::getApplication();
        $layout = $this->getLayout();

        if ($layout == 'edit') {
            ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_EDIT_DETAIL'), 'stack');
            ToolbarHelper::apply('detail.apply');
            ToolbarHelper::save('detail.save');
            ToolbarHelper::cancel('detail.cancel');
        } else {
            ToolbarHelper::title(Text::_('COM_HOLIDAYPACKAGES_DETAILS'), 'stack');
            ToolbarHelper::addNew('detail.add');
            ToolbarHelper::editList('detail.edit');
            ToolbarHelper::deleteList('', 'details.delete');
        }
    }
}