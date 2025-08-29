<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Holidaypackages component helper.
 */
class HolidaypackagesHelper
{
    /**
     * Configure the submenu.
     *
     * @param   string  $vName  The name of the active view.
     *
     * @return  void
     */
    public static function addSubmenu($vName = 'packages')
    {
        HTMLHelper::_('sidebar.addEntry',
            Text::_('COM_HOLIDAYPACKAGES_SUBMENU_PACKAGES'),
            'index.php?option=com_holidaypackages&view=packages',
            $vName == 'packages'
        );

        HTMLHelper::_('sidebar.addEntry',
            Text::_('COM_HOLIDAYPACKAGES_SUBMENU_DESTINATIONS'),
            'index.php?option=com_holidaypackages&view=destinations',
            $vName == 'destinations'
        );
    }
}