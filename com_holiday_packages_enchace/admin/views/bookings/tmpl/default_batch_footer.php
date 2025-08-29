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

use Joomla\CMS\Language\Text;

?>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
    <?php echo Text::_('JCANCEL'); ?>
</button>
<button type="submit" onclick="Joomla.submitbutton('bookings.batch');" class="btn btn-primary">
    <?php echo Text::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>