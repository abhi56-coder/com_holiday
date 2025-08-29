<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelPackage extends AdminModel
{
    protected $context = 'com_holidaypackages.package';

    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    public function getTable($type = 'Package', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        $pk = $pk ?: $app->input->getInt('id', (int) $app->getUserState('com_holidaypackages.edit.package.id', 0));

        if ($pk > 0) {
            $table = $this->getTable();
            if (!$table->load($pk)) {
                $this->setError(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND', $pk));
                return false;
            }

            // Fetch additional related data (e.g., destination title)
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('d.title', 'destination_title'))
                ->from($db->quoteName('n4gvg__holiday_destinations', 'd'))
                ->where($db->quoteName('d.id') . ' = ' . (int) $table->destination_id);
            $db->setQuery($query);
            $table->destination_title = $db->loadResult() ?: Text::_('COM_HOLIDAYPACKAGES_UNKNOWN_DESTINATION');

            $item = $table->getProperties();
            $item['destination_id'] = (int) $item['destination_id'];

            // Process departure_city string into subform-compatible array with IDs
            if (!empty($item['departure_city'])) {
                $cityNames = array_map('trim', explode(',', $item['departure_city']));
                $item['departure_city'] = [];
                foreach ($cityNames as $cityName) {
                    $query = $db->getQuery(true)
                        ->select($db->quoteName('id'))
                        ->from($db->quoteName('n4gvg__cities'))
                        ->where($db->quoteName('name') . ' = ' . $db->quote($cityName))
                        ->where($db->quoteName('published') . ' = 1');
                    $db->setQuery($query);
                    $cityId = $db->loadResult();
                    $item['departure_city'][] = [
                        'departure' => [
                            'departure_city' => $cityId ? $cityId : -1,
                            'other_city' => $cityId ? '' : $cityName
                        ]
                    ];
                }
            } else {
                $item['departure_city'] = []; // Empty array for new or no data
            }

            return (object) $item;
        }

        // For new record (pk = 0 or null), return a new item with default values
        $table = $this->getTable();
        $item = $table->getProperties();
        $item['id'] = 0;
        $item['destination_id'] = $app->getUserState('com_holidaypackages.edit.package.data.destination_id', $app->input->getInt('destination_id', 0));
        $item['title'] = '';
        $item['image'] = '';
        $item['duration'] = '';
        $item['price'] = '';
        $item['special_package'] = '';
        $item['departure_date'] = '';
        $item['departure_city'] = []; 
        $item['member'] = '';          
        $item['package_type'] = '';    
        $item['flight_included'] = 0;  
        return (object) $item;
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.package', 'package', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }

        $item = $this->getItem();
        if ($item && $loadData) {
            $app = Factory::getApplication();
            // $app->enqueueMessage('Debug: Bound item - ' . print_r($item, true), 'notice');
            $form->bind($item);
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.package.data', []);
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    public function save($data)
    {
        $table = $this->getTable();
        $key = $table->getKeyName();
        $pk = !empty($data[$key]) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

        // Debug: Log the incoming data
        $app = Factory::getApplication();
        // $app->enqueueMessage('Debug: Data received - ' . print_r($data, true), 'notice');

        // Process departure_city to save city names as a comma-separated string
        if (isset($data['departure_city']) && is_array($data['departure_city'])) {
            $cityNames = [];
            $db = $this->getDbo();

            foreach ($data['departure_city'] as $entry) {
                if (isset($entry['departure']['departure_city'])) {
                    $cityId = (int) $entry['departure']['departure_city'];
                    $otherCity = trim($entry['departure']['other_city'] ?? '');

                    if ($cityId === -1 && $otherCity !== '') {
                        $cityNames[] = $otherCity;
                    } elseif ($cityId > 0) {
                        $query = $db->getQuery(true)
                            ->select($db->quoteName('name'))
                            ->from($db->quoteName('n4gvg__cities'))
                            ->where($db->quoteName('id') . ' = ' . $db->quote($cityId))
                            ->where($db->quoteName('published') . ' = 1');
                        $db->setQuery($query);
                        $cityName = $db->loadResult();
                        if ($cityName) {
                            $cityNames[] = $cityName;
                        }
                    }
                }
            }

            // Save as comma-separated string, truncated to fit varchar(255) if necessary
            $data['departure_city'] = implode(', ', $cityNames);
            if (strlen($data['departure_city']) > 255) {
                $data['departure_city'] = substr($data['departure_city'], 0, 252) . '...';
            }
        } elseif (!isset($data['departure_city'])) {
            $data['departure_city'] = ''; // Default to empty string if not set
        }

        // Process special_package as JSON
        if (isset($data['special_package']) && is_array($data['special_package'])) {
            $data['special_package'] = json_encode($data['special_package']);
        }
        if (isset($data['package_type']) && $data['package_type'] === '__none__') {
    $data['package_type'] = '';
}

        if ($pk > 0) {
            if (!$table->load($pk)) {
                $this->setError($table->getError());
                $app->enqueueMessage('Error: Failed to load table - ' . $table->getError(), 'error');
                return false;
            }
        }

        // Bind the data
        if (!$table->bind($data)) {
            $this->setError('Binding error: ' . $table->getError());
            $app->enqueueMessage('Binding error: ' . $table->getError(), 'error');
            return false;
        }

        // Check the data
        if (!$table->check()) {
            $this->setError('Check error: ' . $table->getError());
            $app->enqueueMessage('Check error: ' . $table->getError(), 'error');
            return false;
        }
        

        // Store the data
        if (!$table->store()) {
            $this->setError('Store error: ' . $table->getError());
            $app->enqueueMessage('Store error: ' . $table->getError(), 'error');
            return false;
        }
        

        $this->setState($this->getName() . '.id', $table->id);
        return true;

    }

    public function delete(&$pks)
    {
        $pks = (array) $pks;
        $table = $this->getTable();

        foreach ($pks as $pk) {
            if (!$table->load($pk)) {
                $this->setError($table->getError());
                return false;
            }

            if (!$table->delete($pk)) {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }
}