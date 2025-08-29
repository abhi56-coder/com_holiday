<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class HolidaypackagesControllerDetail extends FormController {
    protected $view_list = 'details';

    public function save($key = null, $urlVar = null) {
        $this->checkToken();
        $model = $this->getModel();
        $data = $this->input->post->get('jform', array(), 'array');

        if ($model->save($data)) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_DETAIL_SAVED'));
        } else {
            $this->setMessage($model->getError(), 'error');
        }

        $this->setRedirect('index.php?option=com_holidaypackages&view=details');
    }

    public function apply($key = null, $urlVar = null) {
        $this->checkToken();
        $model = $this->getModel();
        $data = $this->input->post->get('jform', array(), 'array');

        if ($id = $model->save($data)) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_DETAIL_SAVED'));
            $this->setRedirect('index.php?option=com_holidaypackages&view=detail&layout=edit&id=' . $id);
        } else {
            $this->setMessage($model->getError(), 'error');
            $this->setRedirect('index.php?option=com_holidaypackages&view=details');
        }
    }

    public function save2new($key = null, $urlVar = null) {
        $this->checkToken();
        $model = $this->getModel();
        $data = $this->input->post->get('jform', array(), 'array');

        if ($model->save($data)) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_DETAIL_SAVED'));
            $this->setRedirect('index.php?option=com_holidaypackages&view=detail&layout=edit');
        } else {
            $this->setMessage($model->getError(), 'error');
            $this->setRedirect('index.php?option=com_holidaypackages&view=details');
        }
    }

    public function cancel($key = null) {
        $this->checkToken();
        $this->setRedirect('index.php?option=com_holidaypackages&view=details');
    }
}