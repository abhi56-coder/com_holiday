<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

class HolidaypackagesControllerDestination extends FormController
{
    protected $view_list = 'destinations';

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
        $model = $this->getModel('Destination');
        $context = 'com_holidaypackages.edit.destination';

        $category = $this->getCategoryFromInput();

        $form = $model->getForm($data, false);
        if (!$form) {
            $app->enqueueMessage($model->getError() ?: Text::_('JERROR_NO_FORM_FOUND'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . ((int) $data['id']) . $category, false));
        }

        $validData = $model->validate($form, $data);

        if ($validData === false) {
            foreach ($model->getErrors() as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState($context . '.data', $data);
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . ((int) $data['id']) . $category, false));
        }

        if ($model->save($validData)) {
            $task = $this->getTask();
            $newId = (int) $model->getState($model->getName() . '.id');

            switch ($task) {
                case 'apply':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . $newId . $category;
                    break;
                case 'save2new':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=destination&layout=edit' . $category;
                    break;
                case 'save2copy':
                    $validData['id'] = 0;
                    if ($model->save($validData)) {
                        $newId = (int) $model->getState($model->getName() . '.id');
                        $redirectUrl = 'index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . $newId . $category;
                    } else {
                        $this->setRedirect(
                            Route::_('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . $data['id'] . $category, false),
                            $model->getError(),
                            'error'
                        );
                        return false;
                    }
                    break;
                default:
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=destinations' . $category;
            }

            $this->setRedirect(Route::_($redirectUrl, false), Text::_('COM_HOLIDAYPACKAGES_DESTINATION_SAVED'));
        } else {
            $app->setUserState($context . '.data', $data);
            $this->setRedirect(
                Route::_('index.php?option=com_holidaypackages&view=destination&layout=edit&id=' . ((int) $data['id']) . $category, false),
                $model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_SAVING'),
                'error'
            );
        }

        $app->setUserState($context . '.data', null);
    }

    public function cancel($key = null)
    {
        $category = $this->getCategoryFromInput();
        return $this->setRedirect(
            Route::_('index.php?option=com_holidaypackages&view=destinations' . $category, false)
        );
    }

    public function apply($key = null)
    {
        return $this->save($key);
    }

    public function save2copy($key = null)
    {
        return $this->save($key);
    }

    public function save2new($key = null)
    {
        return $this->save($key);
    }

    private function getCategoryFromInput()
    {
        $input = Factory::getApplication()->input;
        $category = trim($input->getString('category', ''));

        return $category !== '' ? '&category=' . urlencode($category) : '';
    }
}