<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;


class HolidaypackagesViewPackage extends HtmlView {
    protected $form;
    protected $item;

    public function display($tpl = null) {
        // Get the model
        $model = $this->getModel('Package');
        if ($model === null) {
            Factory::getApplication()->enqueueMessage('Failed to load the Package model', 'error');
            return false;
        }

        // Get the form and item
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if ($this->form === null) {
            Factory::getApplication()->enqueueMessage('Failed to load the form', 'error');
            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar() {
        $isNew = ($this->item->id == 0);
        $destinationId = Factory::getApplication()->input->getInt('destination_id', 0);

        // Fetch destination title using destination_id from item
        $destinationTitle = $this->getDestinationName((int) $this->item->destination_id);

        // Heading Title
        $title = $isNew
            ? Text::_('COM_HOLIDAYPACKAGES_PACKAGES_NEW')
            : Text::_('COM_HOLIDAYPACKAGES_PACKAGES_EDIT') . ' - ' . ($destinationTitle ?: 'Unknown Destination');

        ToolbarHelper::title($title, 'stack');

        if ($isNew || Factory::getApplication()->getIdentity()->authorise('core.edit', 'com_holidaypackages')) {
            ToolbarHelper::apply('package.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('package.save', 'JTOOLBAR_SAVE');
            if ($isNew) {
                ToolbarHelper::save2new('package.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew) {
                ToolbarHelper::save2copy('package.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_(
            'index.php?option=com_holidaypackages&view=packages' . ($destinationId ? '&destination_id=' . $destinationId : ''),
            false
        );
                ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);

        ToolbarHelper::cancel('package.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }

    private function getDestinationName($destinationId) {
        if ($destinationId) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('title'))
                ->from($db->quoteName('n4gvg__holiday_destinations'))
                ->where($db->quoteName('id') . ' = ' . (int) $destinationId);
            $db->setQuery($query);
            return $db->loadResult() ?: '';
        }
        return '';
    }
}