<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

class HolidaypackagesControllerPolicy extends FormController
{
    protected $view_list = 'policies';

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function add()
    {
        $app = Factory::getApplication();
        $packageId = $app->input->getInt('package_id', 0);
        $destinationId = $app->input->getInt('destination_id', 0);
        $app->setUserState('com_holidaypackages.edit.policy.data', ['package_id' => $packageId, 'destination_id' => $destinationId]);

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=policy&layout=edit&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        return true;
    }

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $data = $this->input->post->get('jform', [], 'array');
        $model = $this->getModel();
        $context = 'com_holidaypackages.edit.policy';

        $packageId = (int) ($data['package_id'] ?? $app->input->getInt('package_id', 0));
        $destinationId = (int) ($data['destination_id'] ?? $app->input->getInt('destination_id', 0));

        $form = $model->getForm($data, false);
        if (!$form) {
            $this->setMessage($model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=policies&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        $validData = $model->validate($form, $data);
        if ($validData === false) {
            foreach ($model->getErrors() as $error) {
                $this->setMessage($error, 'warning');
            }
            $app->setUserState($context . '.data', $data);
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=policy&layout=edit&id=' . (int)$data['id'] . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        if ($model->save($validData)) {
            $task = $this->getTask();
            $newId = (int) $model->getState($model->getName() . '.id');

            switch ($task) {
                case 'apply':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=policy&layout=edit&id=' . $newId . '&package_id=' . $packageId . '&destination_id=' . $destinationId;
                    break;
                case 'save2new':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=policy&layout=edit&package_id=' . $packageId . '&destination_id=' . $destinationId;
                    break;
                default:
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=policies&package_id=' . $packageId . '&destination_id=' . $destinationId;
            }

            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_POLICY_SAVED'));
            $this->setRedirect(Route::_($redirectUrl, false));
        } else {
            $app->setUserState($context . '.data', $data);
            $this->setMessage($model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_SAVING'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=policy&layout=edit&id=' . (int)$data['id'] . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        $app->setUserState($context . '.data', null);
        return true;
    }

    public function apply()
    {
        return $this->save();
    }

    public function save2new()
    {
        return $this->save();
    }

    public function cancel($key = null)
    {
        $app = Factory::getApplication();
        $data = $this->input->post->get('jform', [], 'array');
        $packageId = $this->input->getInt('package_id', isset($data['package_id']) ? (int)$data['package_id'] : 0);
        $destinationId = $this->input->getInt('destination_id', isset($data['destination_id']) ? (int)$data['destination_id'] : 0);

        $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=policies&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        return true;
    }
}