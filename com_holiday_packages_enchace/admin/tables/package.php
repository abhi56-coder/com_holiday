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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\CMS\Versioning\VersionableTableInterface;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Joomla\CMS\Application\ApplicationHelper;

/**
 * Package table
 *
 * @since  2.0.0
 */
class HolidaypackagesTablePackage extends Table implements VersionableTableInterface, TaggableTableInterface
{
    use TaggableTableTrait;

    /**
     * Indicates that columns fully support the NULL value in the database
     *
     * @var    boolean
     * @since  2.0.0
     */
    protected $_supportNullValue = true;

    /**
     * The type alias for this content type.
     *
     * @var    string
     * @since  2.0.0
     */
    public $typeAlias = 'com_holidaypackages.package';

    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  Database connector object
     *
     * @since   2.0.0
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->tagsHelper = new TagsHelper();
        
        parent::__construct('#__hp_packages', 'id', $db);

        // Set the alias since the column is called alias
        $this->setColumnAlias('published', 'published');
    }

    /**
     * Method to compute the default name of the asset.
     * The default name is in the form table_name.id
     * where id is the value of the primary key of the table.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return $this->typeAlias . '.' . (int) $this->$k;
    }

    /**
     * Method to return the title to use for the asset table.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    protected function _getAssetTitle()
    {
        return $this->title;
    }

    /**
     * Method to get the parent asset under which to register this one.
     * By default, all assets are registered to the ROOT node with ID,
     * which will default to 1 if none exists.
     * The extended class can define a table and id to lookup.  If the
     * asset does not exist it will be created.
     *
     * @param   Table   $table  A Table object for the asset parent.
     * @param   integer $id     Id to look up
     *
     * @return  integer
     *
     * @since   2.0.0
     */
    protected function _getAssetParentId(Table $table = null, $id = null)
    {
        $assetId = null;

        // This is a package under a category.
        if ($this->category_id)
        {
            // Build the query to get the asset id for the category.
            $query = $this->_db->getQuery(true)
                ->select($this->_db->quoteName('asset_id'))
                ->from($this->_db->quoteName('#__hp_categories'))
                ->where($this->_db->quoteName('id') . ' = ' . (int) $this->category_id);

            // Get the asset id from the database.
            $this->_db->setQuery($query);

            if ($result = $this->_db->loadResult())
            {
                $assetId = (int) $result;
            }
        }

        // Return the asset id.
        if ($assetId)
        {
            return $assetId;
        }
        else
        {
            return parent::_getAssetParentId($table, $id);
        }
    }

    /**
     * Overloaded bind function to pre-process the params.
     *
     * @param   array  $array   Named array
     * @param   mixed  $ignore  An optional array or space separated list of properties
     *                          to ignore while binding.
     *
     * @return  mixed  Null if operation was satisfactory, otherwise returns an error
     *
     * @see     Table::bind
     * @since   2.0.0
     */
    public function bind($array, $ignore = '')
    {
        $input = Factory::getApplication()->getInput();
        $task = $input->getString('task', '');

        if (($task == 'save' || $task == 'apply') && (!Factory::getUser()->authorise('core.edit.state', 'com_holidaypackages.package.' . $array['id']) && $array['published'] == 1))
        {
            $array['published'] = 0;
        }

        // Support for alias field: alias
        if (empty($array['alias']))
        {
            if (empty($array['title']))
            {
                $array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d-H-i-s'));
            }
            else
            {
                $array['alias'] = OutputFilter::stringURLSafe($array['title']);
            }
        }
        else
        {
            $array['alias'] = OutputFilter::stringURLSafe($array['alias']);
        }

        // Support for multiple or not foreign key field: category_id
        if (!empty($array['category_id']))
        {
            if (is_array($array['category_id']))
            {
                $array['category_id'] = implode(',', $array['category_id']);
            }
            else if (strrpos($array['category_id'], ',') != false)
            {
                $array['category_id'] = explode(',', $array['category_id']);
            }
        }
        else
        {
            $array['category_id'] = '';
        }

        // Handle JSON fields
        $jsonFields = array(
            'gallery', 'inclusions', 'exclusions', 'highlights', 'itinerary',
            'faqs', 'availability_calendar', 'departure_dates', 'departure_cities',
            'seasonal_pricing', 'tags'
        );

        foreach ($jsonFields as $field)
        {
            if (isset($array[$field]))
            {
                if (is_array($array[$field]) || is_object($array[$field]))
                {
                    $array[$field] = json_encode($array[$field]);
                }
                else if (is_string($array[$field]) && !empty($array[$field]))
                {
                    // Validate JSON
                    json_decode($array[$field]);
                    if (json_last_error() !== JSON_ERROR_NONE)
                    {
                        $array[$field] = json_encode(array($array[$field]));
                    }
                }
            }
        }

        // Handle pricing calculations
        if (isset($array['price_adult']) && isset($array['discount_percentage']) && $array['discount_percentage'] > 0)
        {
            $array['discount_amount'] = ($array['price_adult'] * $array['discount_percentage']) / 100;
        }

        return parent::bind($array, $ignore);
    }

    /**
     * Overloaded check function
     *
     * @return  boolean
     *
     * @see     Table::check
     * @since   2.0.0
     */
    public function check()
    {
        try
        {
            parent::check();
        }
        catch (\Exception $e)
        {
            $this->setError($e->getMessage());
            return false;
        }

        // Check for valid name
        if (trim($this->title) == '')
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_TITLE_REQUIRED'));
            return false;
        }

        // Check for existing name
        $query = $this->_db->getQuery(true)
            ->select($this->_db->quoteName('id'))
            ->from($this->_db->quoteName('#__hp_packages'))
            ->where($this->_db->quoteName('title') . ' = ' . $this->_db->quote($this->title))
            ->where($this->_db->quoteName('id') . ' <> ' . (int) $this->id);

        $this->_db->setQuery($query);

        if ($this->_db->loadResult())
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_TITLE_EXISTS'));
            return false;
        }

        // Check alias for uniqueness
        if (!empty($this->alias))
        {
            $query = $this->_db->getQuery(true)
                ->select($this->_db->quoteName('id'))
                ->from($this->_db->quoteName('#__hp_packages'))
                ->where($this->_db->quoteName('alias') . ' = ' . $this->_db->quote($this->alias))
                ->where($this->_db->quoteName('id') . ' <> ' . (int) $this->id);

            $this->_db->setQuery($query);

            if ($this->_db->loadResult())
            {
                $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_ALIAS_EXISTS'));
                return false;
            }
        }

        // Validate pricing
        if (isset($this->price_adult) && $this->price_adult < 0)
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_ADULT_PRICE'));
            return false;
        }

        if (isset($this->price_child) && $this->price_child < 0)
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_CHILD_PRICE'));
            return false;
        }

        // Validate duration
        if (isset($this->duration_days) && $this->duration_days < 1)
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_DURATION'));
            return false;
        }

        // Validate discount percentage
        if (isset($this->discount_percentage) && ($this->discount_percentage < 0 || $this->discount_percentage > 100))
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_DISCOUNT'));
            return false;
        }

        // Validate rating
        if (isset($this->rating) && ($this->rating < 0 || $this->rating > 5))
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_INVALID_RATING'));
            return false;
        }

        // Set created date if new record
        if (!(int) $this->id)
        {
            $this->created = Factory::getDate()->toSql();
        }

        // Set modified date
        $this->modified = Factory::getDate()->toSql();

        // Set created_by if not set
        if (empty($this->created_by))
        {
            $this->created_by = Factory::getUser()->id;
        }

        // Set modified_by
        $this->modified_by = Factory::getUser()->id;

        return true;
    }

    /**
     * Method to store a row
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   2.0.0
     */
    public function store($updateNulls = true)
    {
        $date = Factory::getDate()->toSql();
        $user = Factory::getUser();

        $this->modified = $date;
        $this->modified_by = $user->get('id');

        if (!(int) $this->created)
        {
            $this->created = $date;
        }

        if (empty($this->created_by))
        {
            $this->created_by = $user->get('id');
        }

        // Verify that the alias is unique
        $table = Table::getInstance('Package', 'HolidaypackagesTable', array('dbo' => $this->_db));

        if ($table->load(array('alias' => $this->alias)) && ($table->id != $this->id || $this->id == 0))
        {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_ALIAS_EXISTS'));
            return false;
        }

        return parent::store($updateNulls);
    }

    /**
     * Method to set the publishing state for a row or list of rows in the database
     * table.  The method respects checked out rows by other users and will attempt
     * to checkin rows that it can after adjustments are made.
     *
     * @param   mixed    $pks     An optional array of primary key values to update.  If not set the instance property value is used.
     * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
     * @param   integer  $userId  The user ID of the user performing the operation.
     *
     * @return  boolean  True on success; false if $pks is empty.
     *
     * @since   2.0.0
     */
    public function publish($pks = null, $state = 1, $userId = 0)
    {
        // Initialize variables.
        $k = $this->_tbl_key;

        // Sanitize input.
        ArrayHelper::toInteger($pks);
        $userId = (int) $userId;
        $state = (int) $state;

        // If there are no primary keys set check to see if the instance key is set.
        if (empty($pks))
        {
            if ($this->$k)
            {
                $pks = array($this->$k);
            }
            else
            {
                // Nothing to set publishing state on, return false.
                return false;
            }
        }

        // Build the WHERE clause for the primary keys.
        $where = $k . '=' . implode(' OR ' . $k . '=', $pks);

        // Determine if there is checkin support for the table.
        if (!property_exists($this, 'checked_out') || !property_exists($this, 'checked_out_time'))
        {
            $checkin = '';
        }
        else
        {
            $checkin = ' AND (checked_out = 0 OR checked_out = ' . (int) $userId . ')';
        }

        // Update the publishing state for rows with the given primary keys.
        $query = $this->_db->getQuery(true)
            ->update($this->_db->quoteName($this->_tbl))
            ->set($this->_db->quoteName('published') . ' = ' . (int) $state)
            ->where('(' . $where . ')' . $checkin);

        $this->_db->setQuery($query);

        try
        {
            $this->_db->execute();
        }
        catch (\Exception $e)
        {
            $this->setError($e->getMessage());
            return false;
        }

        // If checkin is supported and all rows were adjusted, check them in.
        if ($checkin && (count($pks) == $this->_db->getAffectedRows()))
        {
            // Checkin each row.
            foreach ($pks as $pk)
            {
                $this->checkin($pk);
            }
        }

        // If the Table instance value is in the list of primary keys that were set, set the instance.
        if (in_array($this->$k, $pks))
        {
            $this->published = $state;
        }

        return true;
    }

    /**
     * Define a namespaced asset name for inclusion in the #__assets table
     *
     * @return  string  The asset name
     *
     * @see     Table::_getAssetName
     * @since   2.0.0
     */
    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return $this->typeAlias . '.' . (int) $this->$k;
    }

    /**
     * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
     *
     * @param   Table   $table  Table name
     * @param   integer  $id     Id
     *
     * @see     Table::_getAssetParentId
     *
     * @return  mixed  The id on success, false on failure.
     *
     * @since   2.0.0
     */
    protected function _getAssetParentId(Table $table = null, $id = null)
    {
        // We will retrieve the parent-asset from the Asset-table
        $assetParent = Table::getInstance('Asset');

        // Default: if no asset-parent can be found we take the global asset
        $assetParentId = $assetParent->getRootId();

        // The item has the component as asset-parent
        $assetParent->loadByName('com_holidaypackages');

        // Return the found asset-parent-id
        if ($assetParent->id)
        {
            $assetParentId = $assetParent->id;
        }

        return $assetParentId;
    }

    /**
     * Get the type alias for the history table
     *
     * @return  string  The alias as described above
     *
     * @since   2.0.0
     */
    public function getTypeAlias()
    {
        return $this->typeAlias;
    }

    /**
     * Method to return the title to use for the asset table.  In
     * tracking the assets a title is kept for each asset so that there is some
     * context available in a unified access manager.  Usually this would just
     * return $this->title or $this->name or whatever is being used for the
     * primary name of the row. You should override this as necessary if a custom title is needed.
     *
     * @return  string  The string to use as the title in the asset table.
     *
     * @since   2.0.0
     */
    protected function _getAssetTitle()
    {
        return $this->title;
    }

    /**
     * Method to get the asset-id of the item
     *
     * @return  int
     *
     * @since   2.0.0
     */
    protected function _getAssetId(): int
    {
        // Initialise variables.
        $k = $this->_tbl_key;
        $id = (int) $this->$k;

        if (!$id)
        {
            return 0;
        }

        // Get the asset_id of this item
        $query = $this->_db->getQuery(true)
            ->select($this->_db->quoteName('asset_id'))
            ->from($this->_db->quoteName($this->_tbl))
            ->where($this->_db->quoteName($k) . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        $this->_db->setQuery($query);
        $result = $this->_db->loadResult();

        return (int) $result;
    }
}