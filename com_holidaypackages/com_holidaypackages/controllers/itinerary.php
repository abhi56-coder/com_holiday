<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;

class HolidaypackagesControllerItinerary extends FormController
{
    protected $view_list = 'itineraries';

    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->post->get('jform', [], 'array');
        $model = $this->getModel('Itinerary');
        $context = 'com_holidaypackages.edit.itinerary';

        $packageId = (int) ($data['package_id'] ?? $input->getInt('package_id', 0));
        $destinationId = (int) ($data['destination_id'] ?? $input->getInt('destination_id', 0));

        if ($packageId <= 0) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id')
            ->from('n4gvg__holiday_packages')
            ->where('id = ' . $db->quote($packageId));
        $db->setQuery($query);

        if (!$db->loadResult()) {
            $app->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_PACKAGE_ID'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        $data['package_id'] = $packageId;
        $data['destination_id'] = $destinationId;

        $form = $model->getForm($data, false);
        if (!$form) {
            $app->enqueueMessage($model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . ((int) $data['id']) . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        $validData = $model->validate($form, $data);

        if ($validData === false) {
            foreach ($model->getErrors() as $error) {
                $app->enqueueMessage($error instanceof \Exception ? $error->getMessage() : $error, 'warning');
            }

            $app->setUserState($context . '.data', $data);
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . ((int) $data['id']) . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false));
        }

        if ($model->save($validData)) {
            $task = $this->getTask();
            $newId = (int) $model->getState($model->getName() . '.id');

            switch ($task) {
                case 'apply':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . $newId . '&package_id=' . $packageId . '&destination_id=' . $destinationId;
                    break;
                case 'save2new':
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=itinerary&layout=edit&package_id=' . $packageId . '&destination_id=' . $destinationId;
                    break;
                case 'save2copy':
                    $validData['id'] = 0;
                    if ($model->save($validData)) {
                        $newId = (int) $model->getState($model->getName() . '.id');
                        $redirectUrl = 'index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . $newId . '&package_id=' . $packageId . '&destination_id=' . $destinationId;
                    } else {
                        $this->setRedirect(
                            Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . $data['id'] . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false),
                            $model->getError(),
                            'error'
                        );
                        return false;
                    }
                    break;
                default:
                    $redirectUrl = 'index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId;
            }

            $this->setRedirect(Route::_($redirectUrl, false), Text::_('COM_HOLIDAYPACKAGES_ITINERARY_SAVED'));
        } else {
            $app->setUserState($context . '.data', $data);
            $this->setRedirect(
                Route::_('index.php?option=com_holidaypackages&view=itinerary&layout=edit&id=' . ((int) $data['id']) . '&package_id=' . $packageId . '&destination_id=' . $destinationId, false),
                $model->getError() ?: Text::_('COM_HOLIDAYPACKAGES_ERROR_SAVING'),
                'error'
            );
        }

        $app->setUserState($context . '.data', null);
    }

    public function cancel($key = null)
    {
        $packageId = $this->input->getInt('package_id', 0);
        $destinationId = $this->input->getInt('destination_id', 0);

        if ($packageId <= 0) {
            $this->setMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
            return $this->setRedirect(Route::_('index.php?option=com_holidaypackages&view=itineraries', false));
        }

        return $this->setRedirect(
            Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . $packageId . '&destination_id=' . $destinationId, false)
        );
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

    public function getFields()
    {
        $app = Factory::getApplication();
        $dayType = $app->input->getString('day_type', 'activity');
        $id = $app->input->getInt('id', 0);

        $model = $this->getModel('Itinerary');
        $form = $model->getForm();

        $itemData = [];
        if ($id) {
            $item = $model->getItem($id);
            $itemData['structured_details_json'] = $item->structured_details_json ?? [];
            $itemData['days_status'] = $item->days_status ?? $dayType;
            $statusMap = [
                'TRANSFER' => 'transfer_sections',
                'SIGHTSEEING' => 'sightseeing_sections',
                'RESORT' => 'resort_sections',
                'ACTIVITY' => 'activity_sections',
                'MEAL' => 'meal_sections',
            ];
            if (isset($statusMap[$item->days_status])) {
                $itemData[$statusMap[$item->days_status]] = $item->{$statusMap[$item->days_status]} ?? [];
            }
        } else {
            $itemData['days_status'] = $dayType;
            $itemData['structured_details_json'] = [];
        }

        $form->bind($itemData);
        $html = $form->getInput('structured_details_json');

        echo json_encode([
            'success' => true,
            'html' => $html
        ]);

        $app->close();
    }

    public function saveHolidayItinerary()
    {
        $this->checkToken('request');

        $app = Factory::getApplication();
        $input = $app->input;
        $jsonData = $input->get('structured_details_json', '', 'RAW');
        $id = $input->getInt('id', 0);

        try {
            $dataToSave = json_decode($jsonData, true);
            if (!$dataToSave || !$id) {
                throw new \RuntimeException('Invalid data or missing itinerary ID.');
            }

            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->update($db->quoteName('n4gvg__holiday_itineraries'))
                ->set($db->quoteName('structured_details_json') . ' = ' . $db->quote(json_encode($dataToSave)))
                ->where($db->quoteName('id') . ' = ' . (int) $id);
            $db->setQuery($query);
            $db->execute();

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        $app->close();
    }

    public function getLastDay() {}
    public function getDetailForm() {}
}