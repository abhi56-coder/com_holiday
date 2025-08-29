<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;

class HolidaypackagesViewItinerary extends HtmlView
{
    public $item;
    public $form;
    public $state;
    protected $packageId;
    protected $destinationId;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $this->packageId = $input->getInt('package_id', 0);
        $this->destinationId = $input->getInt('destination_id', 0);

        if ($this->packageId == 0) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            $app->redirect(
                Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $this->packageId . '&destination_id=' . $this->destinationId, false)
            );
            return false;
        }

        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $this->state = $this->get('State');

        if (empty($this->item) || $this->item === false) {
            $this->item = new \stdClass();
            $this->item->id = 0;
            $this->item->package_id = $this->packageId;
            $this->item->destination_id = $this->destinationId;
            $this->item->day_number = 0;
            $this->item->date = '';
            $this->item->place_name = '';
            $this->item->structured_details_json = json_encode([]);
        } elseif (is_array($this->item)) {
            $this->item = (object) $this->item;
        }

        if (isset($this->item->structured_details_json) && is_array($this->item->structured_details_json)) {
            $this->item->structured_details_json = json_encode($this->item->structured_details_json);
        }

        $this->item->package_id = $this->packageId;
        $this->item->destination_id = $this->destinationId;

        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        if (!$this->form) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
            return false;
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

        $packageId = (int) $this->packageId;
        $destinationId = (int) $this->destinationId;

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_ITINERARY_NEW') : Text::_('COM_HOLIDAYPACKAGES_ITINERARY_EDIT'),
            'stack'
        );

        if ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew)) {
            ToolbarHelper::apply('itinerary.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('itinerary.save', 'JTOOLBAR_SAVE');
            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('itinerary.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew && $canDo->get('core.create')) {
                ToolbarHelper::save2copy('itinerary.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_(
            'index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId,
            false
        );
        ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);

        ToolbarHelper::cancel('itinerary.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        $doc = Factory::getDocument();
        $js = "
            document.addEventListener('DOMContentLoaded', function() {
                let cancelBtn = document.querySelector('button[data-bs-original-title=\"Close\"], button[data-bs-original-title=\"Cancel\"]');
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = '$cancelLink';
                    });
                }
            });
        ";
        $doc->addScriptDeclaration($js);
    }
}
