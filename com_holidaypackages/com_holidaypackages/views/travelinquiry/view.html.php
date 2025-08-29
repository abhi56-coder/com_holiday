<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;

class HolidaypackagesViewTravelinquiry extends HtmlView
{
    protected $form;
    protected $item;
    protected $state;
    protected $category;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (empty($this->form)) {
            $app->enqueueMessage(Text::_('JERROR_NO_FORM_FOUND'), 'error');
            return false;
        }

        if ($this->item === false) {
            $app->enqueueMessage(Text::_('JERROR_NO_ITEM_FOUND'), 'error');
            return false;
        }

        // Get category from URL
        $input = $app->input;
        $categoryFromUrl = $input->getString('category', '');
        $this->category = $categoryFromUrl;

        // Set form value if it's a new item
        if (empty($this->item->id) && !empty($categoryFromUrl)) {
            $this->form->setValue('destination', null, $categoryFromUrl); // Assuming 'destination' matches category
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $isNew = ($this->item->id == 0);
        $canDo = ContentHelper::getActions('com_holidaypackages');

        $app->input->set('hidemainmenu', true);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_TRAVEL_INQUIRY_NEW') : Text::_('COM_HOLIDAYPACKAGES_TRAVEL_INQUIRY_EDIT'),
            'user'
        );

        if ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew)) {
            ToolbarHelper::apply('travelinquiry.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('travelinquiry.save', 'JTOOLBAR_SAVE');
            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('travelinquiry.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew && $canDo->get('core.create')) {
                ToolbarHelper::save2copy('travelinquiry.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_(
            'index.php?option=com_holidaypackages&view=travelinquiries' . ($this->category ? '&category=' . urlencode($this->category) : ''),
            false
        );
        ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);
        ToolbarHelper::cancel('travelinquiry.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}