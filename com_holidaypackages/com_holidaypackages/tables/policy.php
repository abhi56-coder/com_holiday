<?php
defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

class HolidaypackagesTablePolicy extends Table
{
    public function __construct(&$db)
    {
        parent::__construct('n4gvg__holiday_policies', 'id', $db);
    }

    public function check()
    {
        if (trim($this->title) == '') {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_TITLE_REQUIRED'));
            return false;
        }

        if (empty($this->package_id)) {
            $this->setError(Text::_('COM_HOLIDAYPACKAGES_ERROR_PACKAGE_ID_REQUIRED'));
            return false;
        }

        return true;
    }
}