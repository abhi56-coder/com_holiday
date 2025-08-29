<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;


class HolidaypackagesViewDestination extends HtmlView
{
    protected $form;
    protected $item;
    protected $state;
    protected $category;

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $this->state = $this->get('State');

        if (empty($this->form)) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_FORM_FOUND'), 'error');
            return false;
        }

        if ($this->item === false) {
            Factory::getApplication()->enqueueMessage(Text::_('JERROR_NO_ITEM_FOUND'), 'error');
            return false;
        }

        // Get category from URL or item
        $input = Factory::getApplication()->input;
        $categoryFromUrl = $input->getString('category', '');
        $this->category = $categoryFromUrl; // pass to template

        // Set form value if it's a new item
        if (empty($this->item->id) && !empty($categoryFromUrl)) {
            $this->form->setValue('category', null, $categoryFromUrl);
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        Factory::getApplication()->input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_DESTINATION_NEW') : Text::_('COM_HOLIDAYPACKAGES_DESTINATION_EDIT'),
            'stack'
        );

        if ($isNew || Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_holidaypackages')) {
            ToolbarHelper::apply('destination.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('destination.save', 'JTOOLBAR_SAVE');
            if ($isNew) {
                ToolbarHelper::save2new('destination.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew) {
                ToolbarHelper::save2copy('destination.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_(
            'index.php?option=com_holidaypackages&view=destinations' . ($this->category ? '&category=' . urlencode($this->category) : ''),
            false
        );
        ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);
        ToolbarHelper::cancel('destination.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}