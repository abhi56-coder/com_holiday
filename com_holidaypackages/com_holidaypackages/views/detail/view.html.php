<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class HolidaypackagesViewDetail extends HtmlView {
    protected $form;
    protected $item;

    public function display($tpl = null) {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if (empty($this->form)) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_FORM_NOT_FOUND'), 'error');
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar() {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);

        ToolbarHelper::title($isNew ? Text::_('COM_HOLIDAYPACKAGES_DETAIL_NEW') : Text::_('COM_HOLIDAYPACKAGES_DETAIL_EDIT'), 'stack');
        ToolbarHelper::apply('detail.apply');
        ToolbarHelper::save('detail.save');
        // ToolbarHelper::save2new('detail.save2new');
        ToolbarHelper::cancel('detail.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}