<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;

class HolidaypackagesControllerPackage extends FormController
{
    protected $view_list = 'packages';

    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->post->get('jform', [], 'array');
        $model = $this->getModel('Package');
        $context = 'com_holidaypackages.edit.package';

        $destinationId = (int) ($data['destination_id'] ?? $input->getInt('destination_id', 0));

        // Ensure subform data is properly formatted
        if (isset($data['departure_city']) && is_array($data['departure_city'])) {
            foreach ($data['departure_city'] as &$departure) {
                if (isset($departure['departure_city']) && $departure['departure_city'] == '-1' && !empty($departure['other_city'])) {
                    $departure['departure_city'] = $departure['other_city'];
                }
                unset($departure['other_city']); // Remove other_city from saved data
            }
        }

        $form = $model->getForm($data, false);
        if (!$form) {
            $app->enqueueMessage($model->getError() ?: 'Failed to load the form', 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=package&layout=edit&id=' . ((int) $data['id']) . ($destinationId ? '&destination_id=' . $destinationId : ''), false));
        }

        $validData = $model->validate($form, $data);

        if ($validData === false) {
            foreach ($model->getErrors() as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState($context . '.data', $data);
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=package&layout=edit&id=' . ((int) $data['id']) . ($destinationId ? '&destination_id=' . $destinationId : ''), false));
        }

        if ($model->save($validData)) {
            $task = $this->getTask();
            $newId = (int) $model->getState($model->getName() . '.id');

            switch ($task) {
                case 'apply':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=package&layout=edit&id=' . $newId . ($destinationId ? '&destination_id=' . $destinationId : '');
                    break;
                case 'save2new':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=package&layout=edit' . ($destinationId ? '&destination_id=' . $destinationId : '');
                    break;
                case 'save2copy':
                    $validData['id'] = 0;
                    if ($model->save($validData)) {
                        $newId = (int) $model->getState($model->getName() . '.id');
                        $redirectUrl = 'index.php?option=com_holidaypackages&view=package&layout=edit&id=' . $newId . ($destinationId ? '&destination_id=' . $destinationId : '');
                    } else {
                        $this->setRedirect(
                            Route::_('index.php?option=com_holidaypackages&view=package&layout=edit&id=' . $data['id'] . ($destinationId ? '&destination_id=' . $destinationId : ''), false),
                            $model->getError(),
                            'error'
                        );
                        return false;
                    }
                    break;
                default:
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=packages' . ($destinationId ? '&destination_id=' . $destinationId : '');
            }

            $this->setRedirect(Route::_($redirectUrl, false), 'Item saved successfully');
        } else {
            $app->setUserState($context . '.data', $data);
            $this->setRedirect(
                Route::_('index.php?option=com_holidaypackages&view=package&layout=edit&id=' . ((int) $data['id']) . ($destinationId ? '&destination_id=' . $destinationId : ''), false),
                $model->getError() ?: 'Error saving item',
                'error'
            );
        }

        $app->setUserState($context . '.data', null);
    }

    public function cancel($key = null)
    {
        $destinationId = $this->input->getInt('destination_id', 0);

        return $this->setRedirect(
            Route::_('index.php?option=com_holidaypackages&view=packages' . ($destinationId ? '&destination_id=' . $destinationId : ''), false)
        );
    }

    public function apply()
    {
        return $this->save();
    }

    public function save2copy()
    {
        return $this->save();
    }

    public function save2new()
    {
        return $this->save();
    }
}