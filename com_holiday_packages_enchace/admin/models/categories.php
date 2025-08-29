<?php
/**
 * Holiday Packages Enhanced Component
 * 
 * @package     HolidayPackages
 * @subpackage  Administrator
 * @author      Your Name
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

/**
 * Admin Categories List Model for Holiday Packages Enhanced Component
 * 
 * Manages package categories including hierarchical structure,
 * package counts, and category-based filtering.
 */
class HolidayPackagesModelCategories extends ListModel
{
    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'c.id',
                'title', 'c.title',
                'alias', 'c.alias',
                'published', 'c.published',
                'ordering', 'c.ordering',
                'parent_id', 'c.parent_id',
                'level', 'c.level',
                'created', 'c.created',
                'package_count',
                'search'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state
     *
     * @param   string  $ordering   Column to order by
     * @param   string  $direction  Direction to order by
     * @return  void
     */
    protected function populateState($ordering = 'c.lft', $direction = 'asc')
    {
        // Load the filter search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Load filter by published status
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        // Load filter by parent category
        $parentId = $this->getUserStateFromRequest($this->context . '.filter.parent_id', 'filter_parent_id', '', 'int');
        $this->setState('filter.parent_id', $parentId);

        // Load filter by level
        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '', 'int');
        $this->setState('filter.level', $level);

        // Load the default list behavior
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state
     *
     * @param   string  $id  A prefix for the store id
     * @return  string  A store id
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.parent_id');
        $id .= ':' . $this->getState('filter.level');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data
     *
     * @return  DatabaseQuery
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select category fields with package counts
        $query->select([
            'c.*',
            'parent.title as parent_title',
            'COUNT(DISTINCT p.id) as package_count',
            'COUNT(DISTINCT child.id) as child_count'
        ]);

        $query->from($db->quoteName('#__hp_categories', 'c'));
        
        // Join with parent category
        $query->leftJoin($db->quoteName('#__hp_categories', 'parent') . ' ON c.parent_id = parent.id');
        
        // Join with packages
        $query->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON c.id = p.category_id AND p.published = 1');
        
        // Join with child categories
        $query->leftJoin($db->quoteName('#__hp_categories', 'child') . ' ON c.id = child.parent_id');

        // Filter by search term
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('c.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '(' . $db->quoteName('c.title') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.alias') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.description') . ' LIKE ' . $search . ')'
                );
            }
        }

        // Filter by published status
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('c.published') . ' = :published')
                  ->bind(':published', $published, ParameterType::INTEGER);
        }

        // Filter by parent category
        $parentId = $this->getState('filter.parent_id');
        if (!empty($parentId)) {
            if ($parentId == -1) {
                // Root categories only
                $query->where($db->quoteName('c.parent_id') . ' = 0');
            } else {
                $query->where($db->quoteName('c.parent_id') . ' = :parentId')
                      ->bind(':parentId', $parentId, ParameterType::INTEGER);
            }
        }

        // Filter by level
        $level = $this->getState('filter.level');
        if (!empty($level)) {
            $query->where($db->quoteName('c.level') . ' = :level')
                  ->bind(':level', $level, ParameterType::INTEGER);
        }

        // Group by category to get correct counts
        $query->group([
            'c.id', 'c.title', 'c.alias', 'c.description', 'c.image', 'c.parent_id', 
            'c.level', 'c.lft', 'c.rgt', 'c.published', 'c.ordering', 'c.meta_title', 
            'c.meta_description', 'c.meta_keywords', 'c.created', 'c.modified', 
            'parent.title'
        ]);

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'c.lft');
        $orderDirn = $this->state->get('list.direction', 'asc');

        // Handle special ordering cases
        if ($orderCol === 'package_count') {
            $query->order('COUNT(DISTINCT p.id) ' . $db->escape($orderDirn));
        } elseif ($orderCol === 'parent_title') {
            $query->order($db->quoteName('parent.title') . ' ' . $db->escape($orderDirn));
        } else {
            $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        }

        return $query;
    }

    /**
     * Get category statistics
     *
     * @return  object  Statistics data
     */
    public function getCategoryStats(): object
    {
        $db = $this->getDatabase();

        // Total categories by status
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as total_categories',
                'SUM(published) as published_categories',
                'COUNT(CASE WHEN parent_id = 0 THEN 1 END) as root_categories'
            ])
            ->from($db->quoteName('#__hp_categories'));
        $totals = $db->setQuery($query)->loadObject();

        // Categories with most packages
        $query = $db->getQuery(true)
            ->select([
                'c.title',
                'COUNT(p.id) as package_count'
            ])
            ->from($db->quoteName('#__hp_categories', 'c'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON c.id = p.category_id AND p.published = 1')
            ->where($db->quoteName('c.published') . ' = 1')
            ->group('c.id')
            ->order('package_count DESC')
            ->setLimit(10);
        $popularCategories = $db->setQuery($query)->loadObjectList();

        // Categories without packages
        $query = $db->getQuery(true)
            ->select([
                'c.*'
            ])
            ->from($db->quoteName('#__hp_categories', 'c'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON c.id = p.category_id')
            ->where($db->quoteName('p.id') . ' IS NULL')
            ->where($db->quoteName('c.published') . ' = 1')
            ->order($db->quoteName('c.title') . ' ASC');
        $emptyCategories = $db->setQuery($query)->loadObjectList();

        // Category hierarchy depth
        $query = $db->getQuery(true)
            ->select('MAX(level) as max_depth')
            ->from($db->quoteName('#__hp_categories'));
        $maxDepth = $db->setQuery($query)->loadResult();

        return (object) [
            'total_categories' => (int) $totals->total_categories,
            'published_categories' => (int) $totals->published_categories,
            'root_categories' => (int) $totals->root_categories,
            'max_depth' => (int) $maxDepth,
            'popular_categories' => $popularCategories,
            'empty_categories' => $emptyCategories
        ];
    }

    /**
     * Get category tree for hierarchical display
     *
     * @param   int  $rootId  Root category ID (0 for all)
     * @return  array  Category tree
     */
    public function getCategoryTree(int $rootId = 0): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'c.*',
                'COUNT(p.id) as package_count'
            ])
            ->from($db->quoteName('#__hp_categories', 'c'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON c.id = p.category_id AND p.published = 1')
            ->where($db->quoteName('c.published') . ' = 1');

        if ($rootId > 0) {
            $query->where($db->quoteName('c.lft') . ' >= (SELECT lft FROM #__hp_categories WHERE id = :rootId)')
                  ->where($db->quoteName('c.rgt') . ' <= (SELECT rgt FROM #__hp_categories WHERE id = :rootId)')
                  ->bind(':rootId', $rootId, ParameterType::INTEGER);
        }

        $query->group('c.id')
              ->order(['c.lft ASC']);

        $categories = $db->setQuery($query)->loadObjectList();
        
        return $this->buildTree($categories);
    }

    /**
     * Build hierarchical tree from flat category list
     *
     * @param   array  $categories  Flat category list
     * @param   int    $parentId    Parent category ID
     * @return  array  Hierarchical tree
     */
    protected function buildTree(array $categories, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $category->children = $this->buildTree($categories, $category->id);
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    /**
     * Get parent categories for dropdown
     *
     * @param   int  $excludeId  Category ID to exclude (for editing)
     * @return  array  Parent categories
     */
    public function getParentCategories(int $excludeId = 0): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'id',
                'title',
                'level',
                'parent_id'
            ])
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('published') . ' = 1')
            ->order(['lft ASC']);

        if ($excludeId > 0) {
            // Exclude the category and its descendants
            $query->where($db->quoteName('id') . ' != :excludeId')
                  ->where('NOT (lft >= (SELECT lft FROM #__hp_categories WHERE id = :excludeId2) AND rgt <= (SELECT rgt FROM #__hp_categories WHERE id = :excludeId3))')
                  ->bind(':excludeId', $excludeId, ParameterType::INTEGER)
                  ->bind(':excludeId2', $excludeId, ParameterType::INTEGER)
                  ->bind(':excludeId3', $excludeId, ParameterType::INTEGER);
        }

        $categories = $db->setQuery($query)->loadObjectList();
        
        // Add root option
        array_unshift($categories, (object) [
            'id' => 0,
            'title' => Text::_('COM_HOLIDAYPACKAGES_CATEGORY_ROOT'),
            'level' => 0,
            'parent_id' => 0
        ]);

        return $categories;
    }

    /**
     * Rebuild category tree (left/right values)
     *
     * @return  boolean  True on success
     */
    public function rebuildTree(): bool
    {
        try {
            $db = $this->getDatabase();
            
            // Reset all lft/rgt values
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_categories'))
                ->set([
                    $db->quoteName('lft') . ' = 0',
                    $db->quoteName('rgt') . ' = 0',
                    $db->quoteName('level') . ' = 0'
                ]);
            $db->setQuery($query)->execute();

            // Rebuild tree starting from root
            $this->rebuildNode(0, 1);
            
            return true;

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Rebuild tree node recursively
     *
     * @param   int  $parentId  Parent category ID
     * @param   int  $left      Left value
     * @return  int  Next left value
     */
    protected function rebuildNode(int $parentId, int $left): int
    {
        $db = $this->getDatabase();
        
        // Get all children of this parent
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('parent_id') . ' = :parentId')
            ->order($db->quoteName('ordering') . ', ' . $db->quoteName('title'))
            ->bind(':parentId', $parentId, ParameterType::INTEGER);

        $children = $db->setQuery($query)->loadColumn();
        
        $right = $left + 1;
        
        foreach ($children as $childId) {
            $right = $this->rebuildNode($childId, $right);
        }
        
        if ($parentId > 0) {
            // Update this node's values
            $level = $this->getCategoryLevel($parentId);
            
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_categories'))
                ->set([
                    $db->quoteName('lft') . ' = :left',
                    $db->quoteName('rgt') . ' = :right',
                    $db->quoteName('level') . ' = :level'
                ])
                ->where($db->quoteName('id') . ' = :parentId')
                ->bind(':left', $left, ParameterType::INTEGER)
                ->bind(':right', $right, ParameterType::INTEGER)
                ->bind(':level', $level, ParameterType::INTEGER)
                ->bind(':parentId', $parentId, ParameterType::INTEGER);
                
            $db->setQuery($query)->execute();
        }
        
        return $right + 1;
    }

    /**
     * Get category level by traversing parents
     *
     * @param   int  $categoryId  Category ID
     * @return  int  Category level
     */
    protected function getCategoryLevel(int $categoryId): int
    {
        $db = $this->getDatabase();
        
        $level = 1;
        $currentId = $categoryId;
        
        while ($currentId > 0) {
            $query = $db->getQuery(true)
                ->select($db->quoteName('parent_id'))
                ->from($db->quoteName('#__hp_categories'))
                ->where($db->quoteName('id') . ' = :currentId')
                ->bind(':currentId', $currentId, ParameterType::INTEGER);
                
            $parentId = $db->setQuery($query)->loadResult();
            
            if ($parentId > 0) {
                $level++;
                $currentId = $parentId;
            } else {
                break;
            }
        }
        
        return $level;
    }

    /**
     * Move category to different parent
     *
     * @param   int  $categoryId  Category ID to move
     * @param   int  $newParentId New parent ID
     * @return  boolean  True on success
     */
    public function moveCategory(int $categoryId, int $newParentId): bool
    {
        $db = $this->getDatabase();
        
        try {
            $db->transactionStart();
            
            // Update parent_id
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__hp_categories'))
                ->set($db->quoteName('parent_id') . ' = :newParentId')
                ->where($db->quoteName('id') . ' = :categoryId')
                ->bind(':newParentId', $newParentId, ParameterType::INTEGER)
                ->bind(':categoryId', $categoryId, ParameterType::INTEGER);
                
            $db->setQuery($query)->execute();
            
            // Rebuild tree to fix lft/rgt values
            $this->rebuildTree();
            
            $db->transactionCommit();
            return true;
            
        } catch (Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Delete categories and handle package reassignment
     *
     * @param   array  $pks  Category IDs to delete
     * @return  boolean  True on success
     */
    public function delete($pks)
    {
        $db = $this->getDatabase();
        $pks = (array) $pks;

        try {
            $db->transactionStart();

            foreach ($pks as $pk) {
                // Check if category has children
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__hp_categories'))
                    ->where($db->quoteName('parent_id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $hasChildren = $db->setQuery($query)->loadResult();

                if ($hasChildren > 0) {
                    throw new Exception(Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_HAS_CHILDREN', $pk));
                }

                // Move packages to parent category or uncategorized
                $query = $db->getQuery(true)
                    ->select($db->quoteName('parent_id'))
                    ->from($db->quoteName('#__hp_categories'))
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $parentId = $db->setQuery($query)->loadResult() ?: null;

                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__hp_packages'))
                    ->set($db->quoteName('category_id') . ' = :parentId')
                    ->where($db->quoteName('category_id') . ' = :pk')
                    ->bind(':parentId', $parentId, ParameterType::INTEGER)
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query)->execute();

                // Delete category
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_categories'))
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query)->execute();
            }

            // Rebuild tree after deletions
            $this->rebuildTree();

            $db->transactionCommit();
            return true;

        } catch (Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
}