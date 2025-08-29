<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class HolidaypackagesControllerDashboard extends FormController
{
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    public function add()
    {
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit', false));
        return true;
    }

    public function edit($key = null, $urlVar = null)
    {
        $cid = $this->input->get('cid', array(), 'array');
        $id = (int)(count($cid) ? $cid[0] : 0);

        if ($id) {
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . $id, false));
        } else {
            $this->setMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        }
        return true;
    }

    public function save($key = null, $urlVar = null)
    {
        $data = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $id = $this->input->getInt('id');

        if ($model->save($data)) {
            $msg = Text::_('COM_HOLIDAYPACKAGES_ITEM_SAVED');
            $this->setMessage($msg, 'success');
        } else {
            $msg = $model->getError();
            $this->setMessage($msg, 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . $id, false));
            return false;
        }

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        return true;
    }

    public function publish()
    {
        $cid = $this->input->get('cid', array(), 'array');
        $model = $this->getModel();
        if ($model->publish($cid, 1)) {
            $this->setMessage(Text::_('PUBLISHED'), 'message');
        } else {
            $this->setMessage($model->getError(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        return true;
    }

    public function unpublish()
    {
        $cid = $this->input->get('cid', array(), 'array');
        $model = $this->getModel();
        if ($model->publish($cid, 0)) {
            $this->setMessage(Text::_('UNPUBLISHED'), 'message');
        } else {
            $this->setMessage($model->getError(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        return true;
    }


        public function trash()
    {
        $cid = $this->input->get('cid', array(), 'array');
        $model = $this->getModel();
        if ($model->publish($cid, -2)) {
            $this->setMessage(Text::_('TRASHED'), 'message');
        } else {
            $this->setMessage($model->getError(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        return true;
    }

           public function  archive()
    {
        $cid = $this->input->get('cid', array(), 'array');
        $model = $this->getModel();
        if ($model->publish($cid, 2)) {
            $this->setMessage(Text::_('ARCHIVED'), 'message');
        } else {
            $this->setMessage($model->getError(), 'error');
        }
        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
        return true;
    }
    public function delete()
{
    $cid = $this->input->get('cid', array(), 'array'); // Get selected item IDs
    $model = $this->getModel();

    if (empty($cid)) {
        $this->setMessage(Text::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
    } else {
        if ($model->delete($cid)) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ITEM_DELETED'), 'success');
        } else {
            $this->setMessage($model->getError(), 'error');
        }
    }

    $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
    return true;
}

public function contacts()
{
    $this->setRedirect('index.php?option=com_holidaypackages&view=travelinquiries');
}

public function addcities()
    {
        $this->setRedirect('index.php?option=com_holidaypackages&view=cities');
    }


}