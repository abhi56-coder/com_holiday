<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages.admin
 * @version     2.0.0
 * @author      Holiday Packages Team
 * @copyright   Copyright (C) 2024 Holiday Packages. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Holiday Packages records.
 *
 * @since  2.0.0
 */
class HolidaypackagesModelPackages extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @see     \Joomla\CMS\MVC\Model\BaseDatabaseModel
     * @since   2.0.0
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'p.id',
                'title', 'p.title',
                'alias', 'p.alias',
                'category_id', 'p.category_id',
                'destination_id', 'p.destination_id',
                'package_type', 'p.package_type',
                'travel_style', 'p.travel_style',
                'price_adult', 'p.price_adult',
                'duration_days', 'p.duration_days',
                'rating', 'p.rating',
                'featured', 'p.featured',
                'hot_deal', 'p.hot_deal',
                'trending', 'p.trending',
                'published', 'p.published',
                'created', 'p.created',
                'created_by', 'p.created_by',
                'ordering', 'p.ordering',
                'category_title',
                'destination_title',
                'author_name'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   Elements order
     * @param   string  $direction  Order direction
     *
     * @return  void
     *
     * Note. Calling getState in this method will result in recursion.
     * @since   2.0.0
     */
    protected function populateState($ordering = 'p.id', $direction = 'desc')
    {
        $app = Factory::getApplication();

        $forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout'))
        {
            $this->context .= '.' . $layout;
        }

        // Adjust the context to support forced languages.
        if ($forcedLanguage)
        {
            $this->context .= '.' . $forcedLanguage;
        }

        // List state information.
        parent::populateState($ordering, $direction);

        // Force a language.
        if (!empty($forcedLanguage))
        {
            $this->setState('filter.language', $forcedLanguage);
        }
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     * @since   2.0.0
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.destination_id');
        $id .= ':' . $this->getState('filter.package_type');
        $id .= ':' . $this->getState('filter.travel_style');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . $this->getState('filter.hot_deal');
        $id .= ':' . $this->getState('filter.trending');
        $id .= ':' . $this->getState('filter.language');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery
     *
     * @since   2.0.0
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'DISTINCT p.id, p.title, p.alias, p.category_id, p.destination_id, p.short_description, ' .
                'p.image, p.gallery, p.duration_days, p.duration_nights, p.package_type, p.travel_style, ' .
                'p.price_adult, p.price_child, p.currency, p.discount_percentage, p.rating, p.review_count, ' .
                'p.featured, p.hot_deal, p.trending, p.published, p.created, p.created_by, p.modified, ' .
                'p.modified_by, p.ordering, p.checked_out, p.checked_out_time'
            )
        );
        $query->from($db->quoteName('#__hp_packages', 'p'));

        // Join over the categories.
        $query->select($db->quoteName('c.title', 'category_title'))
              ->join('LEFT', $db->quoteName('#__hp_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('p.category_id'));

        // Join over the destinations.
        $query->select($db->quoteName('d.title', 'destination_title'))
              ->join('LEFT', $db->quoteName('#__hp_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('p.destination_id'));

        // Join over the users for the checked out user.
        $query->select($db->quoteName('uc.name', 'editor'))
              ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('p.checked_out'));

        // Join over the asset groups.
        $query->select($db->quoteName('ag.title', 'access_level'))
              ->join('LEFT', $db->quoteName('#__viewlevels', 'ag') . ' ON ' . $db->quoteName('ag.id') . ' = ' . $db->quoteName('p.access'));

        // Join over the users for the author.
        $query->select($db->quoteName('ua.name', 'author_name'))
              ->join('LEFT', $db->quoteName('#__users', 'ua') . ' ON ' . $db->quoteName('ua.id') . ' = ' . $db->quoteName('p.created_by'));

        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published))
        {
            $query->where($db->quoteName('p.published') . ' = :published')
                  ->bind(':published', $published, ParameterType::INTEGER);
        }
        elseif ($published === '')
        {
            $query->where('(' . $db->quoteName('p.published') . ' = 0 OR ' . $db->quoteName('p.published') . ' = 1)');
        }

        // Filter by category.
        $categoryId = $this->getState('filter.category_id');

        if (is_numeric($categoryId))
        {
            $query->where($db->quoteName('p.category_id') . ' = :categoryId')
                  ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
        }

        // Filter by destination.
        $destinationId = $this->getState('filter.destination_id');

        if (is_numeric($destinationId))
        {
            $query->where($db->quoteName('p.destination_id') . ' = :destinationId')
                  ->bind(':destinationId', $destinationId, ParameterType::INTEGER);
        }

        // Filter by package type.
        $packageType = $this->getState('filter.package_type');

        if (!empty($packageType))
        {
            $query->where($db->quoteName('p.package_type') . ' = :packageType')
                  ->bind(':packageType', $packageType);
        }

        // Filter by travel style.
        $travelStyle = $this->getState('filter.travel_style');

        if (!empty($travelStyle))
        {
            $query->where($db->quoteName('p.travel_style') . ' = :travelStyle')
                  ->bind(':travelStyle', $travelStyle);
        }

        // Filter by featured status.
        $featured = $this->getState('filter.featured');

        if (is_numeric($featured))
        {
            $query->where($db->quoteName('p.featured') . ' = :featured')
                  ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Filter by hot deal status.
        $hotDeal = $this->getState('filter.hot_deal');

        if (is_numeric($hotDeal))
        {
            $query->where($db->quoteName('p.hot_deal') . ' = :hotDeal')
                  ->bind(':hotDeal', $hotDeal, ParameterType::INTEGER);
        }

        // Filter by trending status.
        $trending = $this->getState('filter.trending');

        if (is_numeric($trending))
        {
            $query->where($db->quoteName('p.trending') . ' = :trending')
                  ->bind(':trending', $trending, ParameterType::INTEGER);
        }

        // Filter by search in title and description.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            if (stripos($search, 'id:') === 0)
            {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('p.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            }
            else
            {
                $search = '%' . str_replace(' ', '%', trim($search)) . '%';
                $query->where(
                    '(' . $db->quoteName('p.title') . ' LIKE :search1'
                    . ' OR ' . $db->quoteName('p.alias') . ' LIKE :search2'
                    . ' OR ' . $db->quoteName('p.short_description') . ' LIKE :search3'
                    . ' OR ' . $db->quoteName('p.description') . ' LIKE :search4)'
                )
                ->bind([':search1', ':search2', ':search3', ':search4'], $search);
            }
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'p.id');
        $orderDirn = $this->state->get('list.direction', 'DESC');

        if ($orderCol && $orderDirn)
        {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
        }

        return $query;
    }

    /**
     * Get the filter form
     *
     * @param   array    $data      data
     * @param   boolean  $loadData  load current data
     *
     * @return  \JForm|boolean  The \JForm object or false on error
     *
     * @since   2.0.0
     */
    public function getFilterForm($data = array(), $loadData = true)
    {
        $form = parent::getFilterForm($data, $loadData);

        if ($form)
        {
            // Add category filter options
            $categories = $this->getCategoryOptions();
            $form->setFieldAttribute('category_id', 'option', $categories, 'filter');

            // Add destination filter options
            $destinations = $this->getDestinationOptions();
            $form->setFieldAttribute('destination_id', 'option', $destinations, 'filter');
        }

        return $form;
    }

    /**
     * Method to get an array of category options for the filters
     *
     * @return  array  An array of category options
     *
     * @since   2.0.0
     */
    protected function getCategoryOptions()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('title'));

        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }

        array_unshift($options, (object) array('value' => '', 'text' => Text::_('JOPTION_SELECT_CATEGORY')));

        return $options;
    }

    /**
     * Method to get an array of destination options for the filters
     *
     * @return  array  An array of destination options
     *
     * @since   2.0.0
     */
    protected function getDestinationOptions()
    {
        $db = $this->getDbo();
        $query = $db->getQuery(true)
            ->select('id AS value, title AS text')
            ->from($db->quoteName('#__hp_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('title'));

        $db->setQuery($query);

        try {
            $options = $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }

        array_unshift($options, (object) array('value' => '', 'text' => Text::_('JOPTION_SELECT_DESTINATION')));

        return $options;
    }

    /**
     * Method to get packages statistics
     *
     * @return  array  Statistics data
     *
     * @since   2.0.0
     */
    public function getStatistics()
    {
        $db = $this->getDbo();
        $stats = array();

        // Total packages
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'));
        $db->setQuery($query);
        $stats['total_packages'] = (int) $db->loadResult();

        // Published packages
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $stats['published_packages'] = (int) $db->loadResult();

        // Featured packages
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('featured') . ' = 1')
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $stats['featured_packages'] = (int) $db->loadResult();

        // Hot deals
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('hot_deal') . ' = 1')
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $stats['hot_deals'] = (int) $db->loadResult();

        return $stats;
    }

    /**
     * Method to change the published state of one or more records.
     *
     * @param   array    $pks    A list of the primary keys to change.
     * @param   integer  $value  The value of the published state.
     *
     * @return  boolean  True on success.
     *
     * @since   2.0.0
     */
    public function publish(&$pks, $value = 1)
    {
        $table = $this->getTable();
        $pks = (array) $pks;

        foreach ($pks as $i => $pk)
        {
            if ($table->load($pk))
            {
                if (!$this->canEditState($table))
                {
                    // Prune items that you can't change.
                    unset($pks[$i]);
                    Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'notice');
                }
            }
        }

        // Attempt to change the state of the records.
        if (!$table->publish($pks, $value, Factory::getUser()->get('id')))
        {
            $this->setError($table->getError());
            return false;
        }

        return true;
    }

    /**
     * Method to test whether a record can have its state changed.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to change the state of the record.
     *
     * @since   2.0.0
     */
    protected function canEditState($record)
    {
        $user = Factory::getUser();

        // Check for existing package.
        if (!empty($record->id))
        {
            return $user->authorise('core.edit.state', 'com_holidaypackages.package.' . (int) $record->id);
        }

        // Default to component settings if no package ID.
        return $user->authorise('core.edit.state', 'com_holidaypackages');
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $type    The table name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     *
     * @since   2.0.0
     * @throws  \Exception
     */
    public function getTable($type = 'Package', $prefix = 'HolidaypackagesTable', $config = array())
    {
        return Table::getInstance($type, $prefix, $config);
    }

    /**
     * Method to delete one or more records.
     *
     * @param   array  &$pks  An array of record primary keys.
     *
     * @return  boolean  True if successful, false if an error occurs.
     *
     * @since   2.0.0
     */
    public function delete(&$pks)
    {
        $pks = (array) $pks;
        $table = $this->getTable();

        // Check if any packages have bookings
        if (!$this->checkBookingRelations($pks))
        {
            return false;
        }

        // Iterate the items to delete each one.
        foreach ($pks as $i => $pk)
        {
            if ($table->load($pk))
            {
                if ($this->canDelete($table))
                {
                    $context = $this->option . '.' . $this->name;

                    // Trigger the before delete event.
                    $result = Factory::getApplication()->triggerEvent('onContentBeforeDelete', array($context, $table));

                    if (in_array(false, $result, true))
                    {
                        $this->setError($table->getError());
                        return false;
                    }

                    // Multilanguage: if associated, delete the item in the _associations table
                    if ($this->associationsContext && Associations::isEnabled() && !empty($table->language))
                    {
                        $db = $this->getDbo();
                        $query = $db->getQuery(true)
                            ->select($db->quoteName(array('key', 'id')))
                            ->from($db->quoteName('#__associations'))
                            ->where($db->quoteName('context') . ' = :context')
                            ->where($db->quoteName('id') . ' = :id')
                            ->bind(':context', $this->associationsContext)
                            ->bind(':id', $table->id, ParameterType::INTEGER);

                        $db->setQuery($query);
                        $associations = $db->loadAssocList('id');

                        if (!empty($associations))
                        {
                            foreach ($associations as $association)
                            {
                                $query = $db->getQuery(true)
                                    ->delete($db->quoteName('#__associations'))
                                    ->where($db->quoteName('context') . ' = :context')
                                    ->where($db->quoteName('key') . ' = :key')
                                    ->bind(':context', $this->associationsContext)
                                    ->bind(':key', $association['key']);

                                $db->setQuery($query);
                                $db->execute();
                            }
                        }
                    }

                    if (!$table->delete($pk))
                    {
                        $this->setError($table->getError());
                        return false;
                    }

                    // Trigger the after event.
                    Factory::getApplication()->triggerEvent('onContentAfterDelete', array($context, $table));
                }
                else
                {
                    // Prune items that you can't change.
                    unset($pks[$i]);
                    $error = $this->getError();

                    if ($error)
                    {
                        Factory::getApplication()->enqueueMessage($error, 'warning');
                        return false;
                    }
                    else
                    {
                        Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'warning');
                        return false;
                    }
                }
            }
            else
            {
                $this->setError($table->getError());
                return false;
            }
        }

        return true;
    }

    /**
     * Method to test whether a record can be deleted.
     *
     * @param   object  $record  A record object.
     *
     * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
     *
     * @since   2.0.0
     */
    protected function canDelete($record)
    {
        if (!empty($record->id))
        {
            if ($record->published != -2)
            {
                return false;
            }

            $user = Factory::getUser();

            return $user->authorise('core.delete', 'com_holidaypackages.package.' . (int) $record->id);
        }

        return false;
    }

    /**
     * Check if packages have booking relations
     *
     * @param   array  $pks  Package IDs
     *
     * @return  boolean  True if safe to delete
     *
     * @since   2.0.0
     */
    protected function checkBookingRelations($pks)
    {
        $db = $this->getDbo();
        
        // Check for active bookings
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_bookings'))
            ->where($db->quoteName('package_id') . ' IN (' . implode(',', ArrayHelper::toInteger($pks)) . ')')
            ->where($db->quoteName('booking_status') . ' IN (' . $db->quote('Pending') . ',' . $db->quote('Confirmed') . ')');

        $db->setQuery($query);
        $activeBookings = (int) $db->loadResult();

        if ($activeBookings > 0)
        {
            $this->setError(Text::plural('COM_HOLIDAYPACKAGES_ERROR_PACKAGES_HAVE_ACTIVE_BOOKINGS', $activeBookings));
            return false;
        }

        return true;
    }
}