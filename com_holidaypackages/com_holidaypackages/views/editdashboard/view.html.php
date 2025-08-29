<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Router\Route;

class HolidaypackagesViewEditdashboard extends HtmlView
{
    protected $form;
    protected $item;
    protected $state;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');
        $this->state = $this->get('State');

        if (empty($this->form)) {
            throw new Exception('Form not loaded', 500);
        }
        if (empty($this->item)) {
            $this->item = $this->getModel()->getTable();
        }

        HTMLHelper::_('bootstrap.loadCss');
        HTMLHelper::_('jquery.framework');

        $this->addToolbar();
        parent::display($tpl);
    }

    protected function addToolbar()
    {
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        $isNew = ($this->item->id == 0);
        $canDo = ContentHelper::getActions('com_holidaypackages');

        ToolbarHelper::title(
            $isNew ? Text::_('COM_HOLIDAYPACKAGES_NEW_ITEM') : Text::_('COM_HOLIDAYPACKAGES_EDIT_ITEM'),
            'pencil'
        );

        if ($canDo->get('core.edit') || ($canDo->get('core.create') && $isNew)) {
            ToolbarHelper::apply('editdashboard.apply', 'JTOOLBAR_APPLY');
            ToolbarHelper::save('editdashboard.save', 'JTOOLBAR_SAVE');
            if ($canDo->get('core.create')) {
                ToolbarHelper::save2new('editdashboard.save2new', 'JTOOLBAR_SAVE_AND_NEW');
            }
            if (!$isNew && $canDo->get('core.create')) {
                ToolbarHelper::save2copy('editdashboard.save2copy', 'JTOOLBAR_SAVE_AS_COPY');
            }
        }

        $cancelLink = Route::_('index.php?option=com_holidaypackages&view=dashboard', false);
        ToolbarHelper::back('JTOOLBAR_BACK', $cancelLink);
        ToolbarHelper::cancel('editdashboard.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

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