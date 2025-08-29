<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;

class HolidaypackagesViewPolicy extends HtmlView
{
    protected $form;
    protected $item;
    protected $state;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $app = Factory::getApplication();
        $packageId = $app->input->getInt('package_id', 0);
        $destinationId = $app->input->getInt('destination_id', 0);
        $this->state->set('filter.package_id', $packageId);
        $this->state->set('filter.destination_id', $destinationId);

        // Bind the item data to the form
        if ($this->form && $this->item) {
            $this->form->bind($this->item);
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

        $packageId = $this->state->get('filter.package_id', 0);
        $destinationId = $this->state->get('filter.destination_id', 0);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_NEW_POLICY') : Text::_('COM_HOLIDAYPACKAGES_EDIT_POLICY'),
            'stack'
        );

        if ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew)) {
            ToolbarHelper::apply('policy.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('policy.save', 'JTOOLBAR_SAVE');
            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('policy.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
        }

        $cancelLink = Route::_(
            'index.php?option=com_holidaypackages&view=policies&package_id=' . $packageId . '&destination_id=' . $destinationId,
            false
        );
        ToolbarHelper::cancel('policy.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);
    }
}