<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;

class HolidaypackagesModelPolicy extends AdminModel
{
    public function getTable($type = 'Policy', $prefix = 'HolidaypackagesTable', $config = [])
    {
        return Table::getInstance($type, $prefix, $config);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.policy', 'policy', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'));
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_holidaypackages.edit.policy.data', []);

        if (empty($data)) {
            $data = $this->getItem();
            $app = Factory::getApplication();
            $packageId = $app->input->getInt('package_id', 0);

            if ($packageId && empty($data->package_id)) {
                $data->package_id = $packageId;
            }

            if ($packageId && empty($data->destination_id)) {
                $db = $this->getDbo();
                $query = $db->getQuery(true)
                    ->select($db->quoteName('destination_id'))
                    ->from($db->quoteName('n4gvg__holiday_packages'))
                    ->where($db->quoteName('id') . ' = ' . (int)$packageId);
                $db->setQuery($query);
                $destinationId = $db->loadResult();
                $data->destination_id = $destinationId ?: null;
            }
        }

        return $data;
    }

    public function getItem($pk = null)
    {
        $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName() . '.id');
        $table = $this->getTable();

        if ($pk > 0) {
            if ($table->load($pk)) {
                $data = new \stdClass();
                $data->id = $table->id;
                $data->package_id = $table->package_id;
                $data->destination_id = $table->destination_id;
                $data->title = $table->title;
                $data->description = $table->description;
                return $data;
            } else {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_ITEM_NOT_FOUND'));
                return false;
            }
        }

        $data = new \stdClass();
        $app = Factory::getApplication();
        $data->id = 0;
        $data->package_id = $app->input->getInt('package_id', 0);
        $data->destination_id = 0; // Will be overridden by loadFormData if package_id exists
        $data->title = '';
        $data->description = '';
        return $data;
    }

    public function save($data)
    {
        if (empty($data['destination_id']) && !empty($data['package_id'])) {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('destination_id'))
                ->from($db->quoteName('n4gvg__holiday_packages'))
                ->where($db->quoteName('id') . ' = ' . (int)$data['package_id']);
            $db->setQuery($query);
            $data['destination_id'] = (int)$db->loadResult();
        }

        $table = $this->getTable();
        $key = $table->getKeyName();
        $pk = (!empty($data[$key])) ? $data[$key] : (int)$this->getState($this->getName() . '.id');

        if ($pk > 0) {
            $table->load($pk);
        }

        if (!$table->bind($data)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        $this->setState($this->getName() . '.id', $table->$key);
        return true;
    }

    public function delete(&$pks)
    {
        $pks = (array) $pks;
        $table = $this->getTable();

        foreach ($pks as $pk) {
            if (!$table->delete($pk)) {
                $this->setError($table->getError());
                return false;
            }
        }
        return true;
    }
}