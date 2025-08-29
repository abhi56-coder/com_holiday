<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

class HolidaypackagesControllerEditdashboard extends FormController
{
    protected $view_list = 'dashboard'; // Defines the default list view for redirects

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->post->get('jform', [], 'array');
        $model = $this->getModel('Editdashboard');
        $context = 'com_holidaypackages.edit.editdashboard';

        $form = $model->getForm($data, false);
        if (!$form) {
            $app->enqueueMessage($model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . ((int) $data['id']), false));
        }

        $validData = $model->validate($form, $data);

        if ($validData === false) {
            foreach ($model->getErrors() as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState($context . '.data', $data);
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . ((int) $data['id']), false));
        }

        if ($model->save($validData)) {
            $task = $this->getTask();
            $newId = (int) $model->getState($model->getName() . '.id');

            switch ($task) {
                case 'apply':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . $newId;
                    break;
                case 'save2new':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=editdashboard&layout=edit';
                    break;
                case 'save2copy':
                    $validData['id'] = 0;
                    if ($model->save($validData)) {
                        $newId = (int) $model->getState($model->getName() . '.id');
                        $redirectUrl = 'index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . $newId;
                    } else {
                        $this->setRedirect(
                            Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . $data['id'], false),
                            $model->getError(),
                            'error'
                        );
                        return false;
                    }
                    break;
                default:
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=dashboard';
            }

            $this->setRedirect(Route::_($redirectUrl, false), Text::_('COM_HOLIDAYPACKAGES_ITEM_SAVED'));
        } else {
            $app->setUserState($context . '.data', $data);
            $this->setRedirect(
                Route::_('index.php?option=com_holidaypackages&view=editdashboard&layout=edit&id=' . ((int) $data['id']), false),
                $model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_SAVING'),
                'error'
            );
        }

        $app->setUserState($context . '.data', null);
    }

    public function cancel($key = null)
    {
        return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=dashboard', false));
    }

    public function apply($key = null, $urlVar = null)
    {
        return $this->save($key, $urlVar);
    }

    public function save2copy($key = null, $urlVar = null)
    {
        return $this->save($key, $urlVar);
    }

    public function save2new($key = null, $urlVar = null)
    {
        return $this->save($key, $urlVar);
    }
}