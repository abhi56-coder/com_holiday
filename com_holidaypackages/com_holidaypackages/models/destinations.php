<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

class HolidaypackagesModelDestinations extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'id',
                'title', 'title',
                'image', 'image',
                'price', 'price',
                'published', 'published',
                'image_type', 'image_type'
            ];
        }

        parent::__construct($config);
    }

    public function publish($destinationIds)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_destinations'))
            ->set($db->quoteName('published') . ' = 1')
            ->where('id IN (' . implode(',', array_map('intval', $destinationIds)) . ')');
        $db->setQuery($query)->execute();
    }

    public function unpublish($destinationIds)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_destinations'))
            ->set($db->quoteName('published') . ' = 0')
            ->where('id IN (' . implode(',', array_map('intval', $destinationIds)) . ')');
        $db->setQuery($query)->execute();
    }

    public function archive($destinationIds)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_destinations'))
            ->set($db->quoteName('published') . ' = 2')
            ->where('id IN (' . implode(',', array_map('intval', $destinationIds)) . ')');
        $db->setQuery($query)->execute();
    }

    public function trash($destinationIds)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_destinations'))
            ->set($db->quoteName('published') . ' = -2')
            ->where('id IN (' . implode(',', array_map('intval', $destinationIds)) . ')');
        $db->setQuery($query)->execute();
    }

   protected function populateState($ordering = 'id', $direction = 'ASC')
{
    $app = Factory::getApplication();

    // Get the category from the URL
    $inputCategory = $app->input->get('category', '', 'string');
    $this->setState('filter.category', $inputCategory);

    // Keep other filters
    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
    $this->setState('filter.search', $search);

    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
    $this->setState('filter.published', $published);

    parent::populateState($ordering, $direction);
}


    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.image_type');

        return parent::getStoreId($id);
    }

    protected function getListQuery()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        $query->select($this->getState('list.select', 'DISTINCT a.*'))
              ->from($db->quoteName('n4gvg__holiday_destinations', 'a'));

        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('a.published = ' . (int) $published);
        } elseif ($published === '') {
            $query->where('a.published IN (0, 1, 2, -2)');
        }

        // Filter by search
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('a.title LIKE ' . $search);
            }
        }
        // Filter by category
$category = $this->getState('filter.category');
if (!empty($category)) {
    $query->where('a.category = ' . $db->quote($category));
}


        // Filter by image type
        $imageType = $this->getState('filter.image_type');
        if (!empty($imageType)) {
            $imageType = $db->quote('%' . $db->escape($imageType, true) . '%');
            $query->where('a.image LIKE ' . $imageType);
        }

        // Add ordering
        $ordering = $this->getState('list.ordering', 'id');
        $direction = $this->getState('list.direction', 'ASC');
        if ($ordering) {
            $query->order($db->escape($ordering . ' ' . $direction));
        }

        return $query;
    }

    public function changeStatus($cid, $value)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->update($db->quoteName('n4gvg__holiday_destinations'))
            ->set($db->quoteName('published') . ' = ' . (int) $value)
            ->where($db->quoteName('id') . ' = ' . (int) $cid);

        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error changing status: ' . $e->getMessage(), 'error');
            return false;
        }
    }
 
  public function delete(&$pks)
{
    $db = Factory::getDbo();

    foreach ($pks as $pk)
    {
        // First: Get all package IDs linked with this destination
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('n4gvg__holiday_packages'))
            ->where($db->quoteName('destination_id') . ' = ' . (int) $pk);
        $db->setQuery($query);
        $packageIds = $db->loadColumn();

        if (!empty($packageIds)) {
            // Delete related itineraries
            $query = $db->getQuery(true)
                ->delete($db->quoteName('n4gvg__holiday_itineraries'))
                ->where($db->quoteName('package_id') . ' IN (' . implode(',', $packageIds) . ')');
            $db->setQuery($query)->execute();

            // Delete related policies
            $query = $db->getQuery(true)
                ->delete($db->quoteName('n4gvg__holiday_policies'))
                ->where($db->quoteName('package_id') . ' IN (' . implode(',', $packageIds) . ')');
            $db->setQuery($query)->execute();

            // Delete from holiday_packages
            $query = $db->getQuery(true)
                ->delete($db->quoteName('n4gvg__holiday_packages'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', $packageIds) . ')');
            $db->setQuery($query)->execute();
        }

        // Finally delete from destinations
        $table = JTable::getInstance('Destination', 'HolidaypackagesTable');
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