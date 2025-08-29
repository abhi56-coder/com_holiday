<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages.site
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
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

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
                'created', 'p.created',
                'ordering', 'p.ordering',
                'random'
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
    protected function populateState($ordering = 'p.ordering', $direction = 'ASC')
    {
        $app = Factory::getApplication();
        $params = $app->getParams();

        // Search
        $search = $app->input->getString('search', '');
        $this->setState('filter.search', $search);

        // Category filter
        $categoryId = $app->input->getInt('category_id', 0);
        if (!$categoryId) {
            $categoryId = $app->input->getInt('catid', 0);
        }
        $this->setState('filter.category_id', $categoryId);

        // Destination filter
        $destinationId = $app->input->getInt('destination_id', 0);
        $this->setState('filter.destination_id', $destinationId);

        // Package type filter
        $packageType = $app->input->getString('package_type', '');
        $this->setState('filter.package_type', $packageType);

        // Travel style filter
        $travelStyle = $app->input->getString('travel_style', '');
        $this->setState('filter.travel_style', $travelStyle);

        // Price range filters
        $minPrice = $app->input->getFloat('min_price', 0);
        $maxPrice = $app->input->getFloat('max_price', 0);
        $this->setState('filter.min_price', $minPrice);
        $this->setState('filter.max_price', $maxPrice);

        // Duration filters
        $minDuration = $app->input->getInt('min_duration', 0);
        $maxDuration = $app->input->getInt('max_duration', 0);
        $this->setState('filter.min_duration', $minDuration);
        $this->setState('filter.max_duration', $maxDuration);

        // Rating filter
        $minRating = $app->input->getFloat('min_rating', 0);
        $this->setState('filter.min_rating', $minRating);

        // Special filters
        $featured = $app->input->getInt('featured', -1);
        if ($featured >= 0) {
            $this->setState('filter.featured', $featured);
        }

        $hotDeal = $app->input->getInt('hot_deal', -1);
        if ($hotDeal >= 0) {
            $this->setState('filter.hot_deal', $hotDeal);
        }

        // Departure date filter
        $departureDate = $app->input->getString('departure_date', '');
        $this->setState('filter.departure_date', $departureDate);

        // Pagination
        $limit = $params->get('packages_per_page', 12);
        $limitstart = $app->input->getInt('limitstart', 0);
        $this->setState('list.limit', $limit);
        $this->setState('list.start', $limitstart);

        // Ordering
        $orderCol = $app->input->get('filter_order', $ordering);
        $orderDirn = $app->input->get('filter_order_Dir', $direction);

        // Validate ordering
        if (!in_array($orderCol, $this->filter_fields)) {
            $orderCol = $ordering;
        }

        if (!in_array(strtoupper($orderDirn), array('ASC', 'DESC'))) {
            $orderDirn = $direction;
        }

        $this->setState('list.ordering', $orderCol);
        $this->setState('list.direction', $orderDirn);

        // Set component parameters
        $this->setState('params', $params);

        parent::populateState($ordering, $direction);
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
        $id .= ':' . $this->getState('filter.category_id');
        $id .= ':' . $this->getState('filter.destination_id');
        $id .= ':' . $this->getState('filter.package_type');
        $id .= ':' . $this->getState('filter.travel_style');
        $id .= ':' . $this->getState('filter.min_price');
        $id .= ':' . $this->getState('filter.max_price');
        $id .= ':' . $this->getState('filter.min_duration');
        $id .= ':' . $this->getState('filter.max_duration');
        $id .= ':' . $this->getState('filter.min_rating');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . $this->getState('filter.hot_deal');
        $id .= ':' . $this->getState('filter.departure_date');
        $id .= ':' . $this->getState('list.ordering');
        $id .= ':' . $this->getState('list.direction');
        $id .= ':' . $this->getState('list.start');
        $id .= ':' . $this->getState('list.limit');

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
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $user = Factory::getUser();

        // Select the required fields from the table.
        $query->select([
            'p.id',
            'p.title',
            'p.alias', 
            'p.category_id',
            'p.destination_id',
            'p.short_description',
            'p.image',
            'p.duration_days',
            'p.duration_nights',
            'p.package_type',
            'p.travel_style',
            'p.price_adult',
            'p.price_child',
            'p.currency',
            'p.discount_percentage',
            'p.discount_amount',
            'p.rating',
            'p.review_count',
            'p.featured',
            'p.hot_deal',
            'p.trending',
            'p.created',
            'p.highlights',
            'p.inclusions'
        ]);
        $query->from($db->quoteName('#__hp_packages', 'p'));

        // Join over the categories.
        $query->select($db->quoteName('c.title', 'category_title'))
              ->select($db->quoteName('c.color', 'category_color'))
              ->join('LEFT', $db->quoteName('#__hp_categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('p.category_id'));

        // Join over the destinations.
        $query->select($db->quoteName('d.title', 'destination_title'))
              ->select($db->quoteName('d.country', 'destination_country'))
              ->select($db->quoteName('d.image', 'destination_image'))
              ->join('LEFT', $db->quoteName('#__hp_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('p.destination_id'));

        // Join to check if user has this package in wishlist
        if ($user->id) {
            $query->select('CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END AS in_wishlist')
                  ->join('LEFT', $db->quoteName('#__hp_wishlist', 'w') . ' ON ' . $db->quoteName('w.package_id') . ' = ' . $db->quoteName('p.id') . 
                         ' AND ' . $db->quoteName('w.customer_id') . ' = ' . (int) $user->id);
        } else {
            $query->select('0 AS in_wishlist');
        }

        // Filter by published state
        $query->where($db->quoteName('p.published') . ' = 1');
        $query->where($db->quoteName('c.published') . ' = 1');
        $query->where($db->quoteName('d.published') . ' = 1');

        // Filter by publish dates
        $nowDate = $db->quote(Factory::getDate()->toSql());
        $query->where('(' . $db->quoteName('p.publish_up') . ' IS NULL OR ' . $db->quoteName('p.publish_up') . ' <= ' . $nowDate . ')')
              ->where('(' . $db->quoteName('p.publish_down') . ' IS NULL OR ' . $db->quoteName('p.publish_down') . ' >= ' . $nowDate . ')');

        // Filter by category
        $categoryId = $this->getState('filter.category_id');
        if ($categoryId) {
            $query->where($db->quoteName('p.category_id') . ' = :categoryId')
                  ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
        }

        // Filter by destination
        $destinationId = $this->getState('filter.destination_id');
        if ($destinationId) {
            $query->where($db->quoteName('p.destination_id') . ' = :destinationId')
                  ->bind(':destinationId', $destinationId, ParameterType::INTEGER);
        }

        // Filter by package type
        $packageType = $this->getState('filter.package_type');
        if ($packageType) {
            $query->where($db->quoteName('p.package_type') . ' = :packageType')
                  ->bind(':packageType', $packageType);
        }

        // Filter by travel style
        $travelStyle = $this->getState('filter.travel_style');
        if ($travelStyle) {
            $query->where($db->quoteName('p.travel_style') . ' = :travelStyle')
                  ->bind(':travelStyle', $travelStyle);
        }

        // Filter by price range
        $minPrice = $this->getState('filter.min_price');
        if ($minPrice > 0) {
            $query->where($db->quoteName('p.price_adult') . ' >= :minPrice')
                  ->bind(':minPrice', $minPrice, ParameterType::FLOAT);
        }

        $maxPrice = $this->getState('filter.max_price');
        if ($maxPrice > 0) {
            $query->where($db->quoteName('p.price_adult') . ' <= :maxPrice')
                  ->bind(':maxPrice', $maxPrice, ParameterType::FLOAT);
        }

        // Filter by duration
        $minDuration = $this->getState('filter.min_duration');
        if ($minDuration > 0) {
            $query->where($db->quoteName('p.duration_days') . ' >= :minDuration')
                  ->bind(':minDuration', $minDuration, ParameterType::INTEGER);
        }

        $maxDuration = $this->getState('filter.max_duration');
        if ($maxDuration > 0) {
            $query->where($db->quoteName('p.duration_days') . ' <= :maxDuration')
                  ->bind(':maxDuration', $maxDuration, ParameterType::INTEGER);
        }

        // Filter by minimum rating
        $minRating = $this->getState('filter.min_rating');
        if ($minRating > 0) {
            $query->where($db->quoteName('p.rating') . ' >= :minRating')
                  ->bind(':minRating', $minRating, ParameterType::FLOAT);
        }

        // Filter by featured
        $featured = $this->getState('filter.featured');
        if ($featured !== null && $featured >= 0) {
            $query->where($db->quoteName('p.featured') . ' = :featured')
                  ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Filter by hot deal
        $hotDeal = $this->getState('filter.hot_deal');
        if ($hotDeal !== null && $hotDeal >= 0) {
            $query->where($db->quoteName('p.hot_deal') . ' = :hotDeal')
                  ->bind(':hotDeal', $hotDeal, ParameterType::INTEGER);
        }

        // Filter by search in title and description
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $search = (int) substr($search, 3);
                $query->where($db->quoteName('p.id') . ' = :search')
                      ->bind(':search', $search, ParameterType::INTEGER);
            } else {
                $search = '%' . str_replace(' ', '%', $db->escape($search, true)) . '%';
                $query->where(
                    '(' . $db->quoteName('p.title') . ' LIKE :search1'
                    . ' OR ' . $db->quoteName('p.short_description') . ' LIKE :search2'
                    . ' OR ' . $db->quoteName('d.title') . ' LIKE :search3'
                    . ' OR ' . $db->quoteName('c.title') . ' LIKE :search4)'
                )
                ->bind([':search1', ':search2', ':search3', ':search4'], $search);
            }
        }

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'p.ordering');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        // Handle special ordering cases
        if ($orderCol === 'random') {
            $query->order('RAND()');
        } else {
            $query->order($db->escape($orderCol . ' ' . $orderDirn));
            
            // Secondary ordering for consistency
            if ($orderCol !== 'p.id') {
                $query->order($db->quoteName('p.id') . ' ASC');
            }
        }

        return $query;
    }

    /**
     * Method to get an array of data items.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   2.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        if (!$items) {
            return array();
        }

        // Process items to add computed properties
        foreach ($items as &$item) {
            // Calculate effective price after discount
            $item->effective_price = $item->price_adult;
            if ($item->discount_percentage > 0) {
                $item->effective_price = $item->price_adult - $item->discount_amount;
            }

            // Parse JSON fields
            if ($item->highlights) {
                $item->highlights_array = json_decode($item->highlights, true) ?: array();
            } else {
                $item->highlights_array = array();
            }

            if ($item->inclusions) {
                $item->inclusions_array = json_decode($item->inclusions, true) ?: array();
            } else {
                $item->inclusions_array = array();
            }

            // Generate URL
            $item->url = HolidaypackagesHelper::getPackageUrl($item);
        }

        return $items;
    }

    /**
     * Get categories for filtering
     *
     * @return  array  List of categories
     *
     * @since   2.0.0
     */
    public function getCategories()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select(['id', 'title', 'color', 'icon_class'])
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ', ' . $db->quoteName('title'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }
    }

    /**
     * Get destinations for filtering
     *
     * @return  array  List of destinations
     *
     * @since   2.0.0
     */
    public function getDestinations()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select(['id', 'title', 'country', 'image'])
            ->from($db->quoteName('#__hp_destinations'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('country') . ', ' . $db->quoteName('title'));

        $db->setQuery($query);

        try {
            return $db->loadObjectList();
        } catch (RuntimeException $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return array();
        }
    }

    /**
     * Get price range for filtering
     *
     * @return  object  Min and max prices
     *
     * @since   2.0.0
     */
    public function getPriceRange()
    {
        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->select(['MIN(price_adult) as min_price', 'MAX(price_adult) as max_price'])
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        try {
            return $db->loadObject();
        } catch (RuntimeException $e) {
            return (object) array('min_price' => 0, 'max_price' => 10000);
        }
    }

    /**
     * Add package to user's wishlist
     *
     * @param   int  $userId     User ID
     * @param   int  $packageId  Package ID
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function addToWishlist($userId, $packageId)
    {
        if (!$userId || !$packageId) {
            return false;
        }

        $db = $this->getDbo();
        
        // Check if already in wishlist
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__hp_wishlist'))
            ->where($db->quoteName('customer_id') . ' = :userId')
            ->where($db->quoteName('package_id') . ' = :packageId')
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':packageId', $packageId, ParameterType::INTEGER);

        $db->setQuery($query);

        if ($db->loadResult()) {
            return true; // Already in wishlist
        }

        // Add to wishlist
        $wishlistItem = (object) array(
            'customer_id' => $userId,
            'package_id' => $packageId,
            'created' => Factory::getDate()->toSql()
        );

        try {
            return $db->insertObject('#__hp_wishlist', $wishlistItem);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Remove package from user's wishlist
     *
     * @param   int  $userId     User ID
     * @param   int  $packageId  Package ID
     *
     * @return  boolean  True on success
     *
     * @since   2.0.0
     */
    public function removeFromWishlist($userId, $packageId)
    {
        if (!$userId || !$packageId) {
            return false;
        }

        $db = $this->getDbo();
        
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__hp_wishlist'))
            ->where($db->quoteName('customer_id') . ' = :userId')
            ->where($db->quoteName('package_id') . ' = :packageId')
            ->bind(':userId', $userId, ParameterType::INTEGER)
            ->bind(':packageId', $packageId, ParameterType::INTEGER);

        $db->setQuery($query);

        try {
            $db->execute();
            return true;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
}