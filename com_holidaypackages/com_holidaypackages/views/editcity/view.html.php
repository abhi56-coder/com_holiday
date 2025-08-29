<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;

class HolidaypackagesViewEditcity extends HtmlView
{
    protected $form;
    protected $item;
    protected $state;

    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (empty($this->item) || $this->item === false) {
            $this->item = new \stdClass();
            $this->item->id = 0;
            $this->item->name = '';
            $this->item->published = 1;
        } elseif (is_array($this->item)) {
            $this->item = (object) $this->item;
        }

        if (count($errors = $this->get('Errors'))) {
            $app->enqueueMessage(implode("\n", $errors), 'error');
            return false;
        }

        if (!$this->form) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
            $app->redirect(Route::_('index.php?option=com_holidaypackages&view=cities', false));
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

        $app->input->set('hidemainmenu', true);

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_ADD_NEW_CITY') : Text::_('COM_HOLIDAYPACKAGES_EDIT_CITY'),
            'pencil-2 city'
        );

        if ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew)) {
            ToolbarHelper::apply('editcity.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('editcity.save', 'JTOOLBAR_SAVE');
            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('editcity.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew && $canDo->get('core.create')) {
                ToolbarHelper::save2copy('editcity.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_('index.php?option=com_holidaypackages&view=cities', false);
        ToolbarHelper::cancel('editcity.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

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