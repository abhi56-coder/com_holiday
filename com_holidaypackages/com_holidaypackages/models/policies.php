<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

class HolidaypackagesModelPolicies extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'package_id', 'title', 'search', 'published'
            ];
        }
        parent::__construct($config);
        $this->context = 'com_holidaypackages.policies';
    }

    protected function populateState($ordering = 'id', $direction = 'asc')
    {
        parent::populateState($ordering, $direction);

        $app = Factory::getApplication('administrator');

        // Search Filter
        $search = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Published Status Filter
        $published = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published === '*' || $published === '' ? null : (int)$published);

        // Package ID Filter
        $package_id = $app->input->getInt('package_id', 0);
        $this->setState('filter.package_id', $package_id);
    }

    protected function getListQuery()
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('n4gvg__holiday_policies'));

        // Filter: Package ID
        $package_id = $this->getState('filter.package_id');
        if ($package_id) {
            $query->where($db->quoteName('package_id') . ' = ' . (int) $package_id);
        }

        // Filter: Published
        $published = $this->getState('filter.published');
        if ($published !== null && is_numeric($published)) {
            $query->where($db->quoteName('published') . ' = ' . (int) $published);
        }

        // Filter: Search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = '%' . $db->escape($search, true) . '%';
            $query->where('(' . $db->quoteName('title') . ' LIKE ' . $db->quote($search) . ' OR ' . $db->quoteName('description') . ' LIKE ' . $db->quote($search) . ')');
        }

        // Ordering
        $orderCol = $this->state->get('list.ordering', 'id');
        $orderDirn = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

    public function getItems()
    {
        return parent::getItems();
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_holidaypackages.policy', 'policy', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $data = $app->getUserState('com_holidaypackages.edit.policy.data', []);

        if (empty($data)) {
            $id = $app->input->getInt('id', 0);
            if ($id) {
                $db = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('n4gvg__holiday_policies'))
                    ->where($db->quoteName('id') . ' = ' . (int) $id);
                $db->setQuery($query);
                $data = $db->loadObject();
            } else {
                $data = new \stdClass();
                $data->package_id = $app->input->getInt('package_id', 0);
            }
        }

        return $data;
    }

    public function publish($policyIds)
    {
        return $this->changeStatus($policyIds, 1);
    }

    public function unpublish($policyIds)
    {
        return $this->changeStatus($policyIds, 0);
    }

    public function archive($policyIds)
    {
        return $this->changeStatus($policyIds, 2);
    }

    public function trash($policyIds)
    {
        return $this->changeStatus($policyIds, -2);
    }

    private function changeStatus($ids, $status)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_policies'))
            ->set($db->quoteName('published') . ' = ' . (int) $status)
            ->where('id IN (' . implode(',', array_map('intval', $ids)) . ')');
        $db->setQuery($query);
        return $db->execute();
    }

    public function delete(&$pks)
    {
        $table = Table::getInstance('Policies', 'HolidaypackagesTable');

        foreach ($pks as $pk) {
            if ($table->load($pk)) {
                if (!$table->delete($pk)) {
                    $this->setError($table->getError());
                    return false;
                }
            } else {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }
}