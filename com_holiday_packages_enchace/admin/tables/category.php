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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Nested;

/**
 * Category Table Class for Holiday Packages Enhanced Component
 * 
 * Handles hierarchical category structure using nested set model
 * for efficient tree operations.
 */
class HolidayPackagesTableCategory extends Nested
{
    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     */
    protected $_supportNullValue = false;

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__hp_categories', 'id', $db);

        // Set the alias to be the same as the special database driver alias
        $this->setColumnAlias('published', 'published');
    }

    /**
     * Method to bind an associative array or object to the Table instance.
     *
     * @param   array|object  $src     An associative array or object to bind to the Table instance
     * @param   array|string  $ignore  An optional array or space separated list of properties to ignore while binding
     * @return  boolean  True on success
     */
    public function bind($src, $ignore = [])
    {
        // Convert array to object if needed
        if (is_array($src)) {
            $src = (object) $src;
        }

        // Generate alias from title if not provided
        if (empty($src->alias) && !empty($src->title)) {
            $src->alias = ApplicationHelper::stringURLSafe($src->title);
        }

        // Ensure alias is unique
        if (!empty($src->alias)) {
            $src->alias = $this->generateUniqueAlias($src->alias, $src->id ?? 0);
        }

        // Clean description HTML
        if (!empty($src->description)) {
            $src->description = trim($src->description);
        }

        // Set creation date if new record
        if (empty($src->created) && empty($src->id)) {
            $src->created = Factory::getDate()->toSql();
        }

        // Always set modified date
        $src->modified = Factory::getDate()->toSql();

        // Handle published state
        if (!isset($src->published)) {
            $src->published = 1;
        }

        return parent::bind($src, $ignore);
    }

    /**
     * Method to perform sanity checks on the Table instance properties
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database
     */
    public function check()
    {
        try {
            parent::check();
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // Check required fields
        if (empty($this->title)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_TITLE_REQUIRED'));
            return false;
        }

        // Generate alias if empty
        if (empty($this->alias)) {
            $this->alias = ApplicationHelper::stringURLSafe($this->title);
        }

        // Ensure alias is unique
        $this->alias = $this->generateUniqueAlias($this->alias, $this->id ?? 0);

        // Validate parent category
        if (!empty($this->parent_id)) {
            if ($this->parent_id == $this->id) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_SELF_PARENT'));
                return false;
            }

            if (!$this->validateParentCategory()) {
                return false;
            }
        } else {
            $this->parent_id = 0;
        }

        // Clean meta data
        if (!empty($this->meta_title)) {
            $this->meta_title = trim($this->meta_title);
        }

        if (!empty($this->meta_description)) {
            $this->meta_description = trim($this->meta_description);
        }

        if (!empty($this->meta_keywords)) {
            $this->meta_keywords = trim($this->meta_keywords);
        }

        return true;
    }

    /**
     * Method to store a row in the database
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null
     * @return  boolean  True on success
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate();

        // Set creation date if new record
        if (empty($this->id)) {
            if (empty($this->created)) {
                $this->created = $date->toSql();
            }
        }

        // Always update modified date
        $this->modified = $date->toSql();

        // Store using nested set functionality
        $result = parent::store($updateNulls);

        if ($result) {
            // Handle post-save operations
            $this->handlePostSaveOperations();
        }

        return $result;
    }

    /**
     * Method to delete a row from the database table
     *
     * @param   mixed  $pk  An optional primary key value to delete
     * @return  boolean  True on success
     */
    public function delete($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        // Load the row if not already loaded
        if (!$this->load($pk)) {
            return false;
        }

        // Check if category has children
        if ($this->hasChildren($pk)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_HAS_CHILDREN'));
            return false;
        }

        // Handle package reassignment
        $this->reassignPackages($pk);

        return parent::delete($pk);
    }

    /**
     * Generate unique alias
     *
     * @param   string  $alias  Proposed alias
     * @param   int     $id     Category ID (0 for new)
     * @return  string  Unique alias
     */
    protected function generateUniqueAlias(string $alias, int $id = 0): string
    {
        $db = $this->_db;
        $originalAlias = $alias;
        $counter = 1;

        do {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__hp_categories'))
                ->where($db->quoteName('alias') . ' = ' . $db->quote($alias));

            if ($id > 0) {
                $query->where($db->quoteName('id') . ' != ' . (int) $id);
            }

            $exists = $db->setQuery($query)->loadResult();

            if ($exists > 0) {
                $alias = $originalAlias . '-' . $counter;
                $counter++;
            }
        } while ($exists > 0);

        return $alias;
    }

    /**
     * Validate parent category
     *
     * @return  boolean  True if valid
     */
    protected function validateParentCategory(): bool
    {
        $db = $this->_db;

        // Check if parent exists and is published
        $query = $db->getQuery(true)
            ->select(['id', 'published', 'lft', 'rgt'])
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('id') . ' = ' . (int) $this->parent_id);

        $parent = $db->setQuery($query)->loadObject();

        if (!$parent) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_PARENT_NOT_FOUND'));
            return false;
        }

        // For existing categories, check if we're not moving to a descendant
        if (!empty($this->id) && !empty($parent->lft) && !empty($parent->rgt)) {
            $query = $db->getQuery(true)
                ->select(['lft', 'rgt'])
                ->from($db->quoteName('#__hp_categories'))
                ->where($db->quoteName('id') . ' = ' . (int) $this->id);

            $current = $db->setQuery($query)->loadObject();

            if ($current && $parent->lft >= $current->lft && $parent->rgt <= $current->rgt) {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_CATEGORY_DESCENDANT_PARENT'));
                return false;
            }
        }

        return true;
    }

    /**
     * Check if category has children
     *
     * @param   int  $categoryId  Category ID
     * @return  boolean  True if has children
     */
    protected function hasChildren(int $categoryId): bool
    {
        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('parent_id') . ' = ' . (int) $categoryId);

        $count = $db->setQuery($query)->loadResult();
        
        return $count > 0;
    }

    /**
     * Reassign packages to parent category when deleting
     *
     * @param   int  $categoryId  Category ID being deleted
     * @return  void
     */
    protected function reassignPackages(int $categoryId): void
    {
        $db = $this->_db;

        // Move packages to parent category or set to NULL
        $parentId = $this->parent_id ?: null;

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__hp_packages'))
            ->set($db->quoteName('category_id') . ' = ' . ($parentId ? (int) $parentId : 'NULL'))
            ->where($db->quoteName('category_id') . ' = ' . (int) $categoryId);

        $db->setQuery($query)->execute();
    }

    /**
     * Handle post-save operations
     *
     * @return  void
     */
    protected function handlePostSaveOperations(): void
    {
        // Update package counts
        $this->updatePackageCount();
    }

    /**
     * Update package count for this category
     *
     * @return  void
     */
    protected function updatePackageCount(): void
    {
        if (empty($this->id)) {
            return;
        }

        $db = $this->_db;
        
        // Count published packages in this category
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_packages'))
            ->where($db->quoteName('category_id') . ' = ' . (int) $this->id)
            ->where($db->quoteName('published') . ' = 1');

        $count = $db->setQuery($query)->loadResult();

        // Update category with package count (if we add this field to the table)
        // This could be used for display purposes
    }

    /**
     * Get category path as breadcrumbs
     *
     * @param   mixed  $pk  Primary key
     * @return  array  Category path
     */
    public function getCategoryPath($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return [];
        }

        $db = $this->_db;
        
        // Get current category
        $query = $db->getQuery(true)
            ->select(['lft', 'rgt'])
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('id') . ' = ' . (int) $pk);

        $current = $db->setQuery($query)->loadObject();

        if (!$current) {
            return [];
        }

        // Get all parents
        $query = $db->getQuery(true)
            ->select(['id', 'title', 'alias', 'level'])
            ->from($db->quoteName('#__hp_categories'))
            ->where($db->quoteName('lft') . ' <= ' . (int) $current->lft)
            ->where($db->quoteName('rgt') . ' >= ' . (int) $current->rgt)
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('lft') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Get category children
     *
     * @param   mixed    $pk        Primary key
     * @param   boolean  $direct    Get only direct children
     * @param   boolean  $published Only published categories
     * @return  array    Child categories
     */
    public function getCategoryChildren($pk = null, bool $direct = true, bool $published = true)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return [];
        }

        $db = $this->_db;
        
        if ($direct) {
            // Get direct children only
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__hp_categories'))
                ->where($db->quoteName('parent_id') . ' = ' . (int) $pk);
        } else {
            // Get all descendants using nested set
            $query = $db->getQuery(true)
                ->select(['c2.*'])
                ->from($db->quoteName('#__hp_categories', 'c1'))
                ->innerJoin(
                    $db->quoteName('#__hp_categories', 'c2') . ' ON ' .
                    $db->quoteName('c2.lft') . ' > ' . $db->quoteName('c1.lft') . ' AND ' .
                    $db->quoteName('c2.rgt') . ' < ' . $db->quoteName('c1.rgt')
                )
                ->where($db->quoteName('c1.id') . ' = ' . (int) $pk);
        }

        if ($published) {
            $query->where($db->quoteName('published') . ' = 1');
        }

        $query->order($db->quoteName('lft') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Move category to new position
     *
     * @param   int  $categoryId   Category to move
     * @param   int  $targetId     Target category
     * @param   string $position  Position relative to target ('before', 'after', 'first-child', 'last-child')
     * @return  boolean  True on success
     */
    public function moveCategory(int $categoryId, int $targetId, string $position = 'after'): bool
    {
        try {
            // Load the category to move
            if (!$this->load($categoryId)) {
                return false;
            }

            // Determine new parent and ordering based on position
            switch ($position) {
                case 'first-child':
                    $newParentId = $targetId;
                    $referenceId = 0;
                    break;
                    
                case 'last-child':
                    $newParentId = $targetId;
                    $referenceId = -1;
                    break;
                    
                case 'before':
                case 'after':
                    // Get target's parent
                    $db = $this->_db;
                    $query = $db->getQuery(true)
                        ->select($db->quoteName('parent_id'))
                        ->from($db->quoteName('#__hp_categories'))
                        ->where($db->quoteName('id') . ' = ' . (int) $targetId);
                    
                    $newParentId = $db->setQuery($query)->loadResult();
                    $referenceId = $targetId;
                    break;
                    
                default:
                    return false;
            }

            // Update parent_id
            $this->parent_id = $newParentId;

            // Use nested set moveByReference if available, or implement custom logic
            return $this->moveByReference($referenceId, $position, $categoryId);

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Get category statistics
     *
     * @param   mixed  $pk  Primary key
     * @return  object|null  Category statistics
     */
    public function getCategoryStats($pk = null)
    {
        $k = $this->_tbl_key;
        $pk = is_null($pk) ? $this->$k : $pk;

        if (empty($pk)) {
            return null;
        }

        $db = $this->_db;
        
        $query = $db->getQuery(true)
            ->select([
                'COUNT(DISTINCT p.id) as package_count',
                'COUNT(DISTINCT child.id) as child_categories',
                'SUM(p.bookings_count) as total_bookings',
                'AVG(p.rating) as average_rating'
            ])
            ->from($db->quoteName('#__hp_packages', 'p'))
            ->leftJoin($db->quoteName('#__hp_categories', 'child') . ' ON child.parent_id = ' . (int) $pk)
            ->where($db->quoteName('p.category_id') . ' = ' . (int) $pk)
            ->where($db->quoteName('p.published') . ' = 1');

        return $db->setQuery($query)->loadObject();
    }
}