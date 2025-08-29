<?php
/**
 * @package     Holiday Packages
 * @subpackage  com_holidaypackages
 * @version     2.0.0
 * @author      Holiday Packages Team
 * @copyright   Copyright (C) 2024 Holiday Packages. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\HTML\HTMLHelper;

// Register the component helper
JLoader::register('HolidaypackagesHelper', JPATH_COMPONENT . '/helpers/holidaypackages.php');

// Load component CSS and JavaScript
$wa = Factory::getApplication()->getDocument()->getWebAssetManager();

// Load Bootstrap 5 and component styles
$wa->useStyle('bootstrap.css');
$wa->registerAndUseStyle('com_holidaypackages.frontend', 'media/com_holidaypackages/css/frontend.css');

// Load component JavaScript
$wa->useScript('bootstrap.js');
$wa->registerAndUseScript('com_holidaypackages.frontend', 'media/com_holidaypackages/js/frontend.js');

// Load FontAwesome for icons
HTMLHelper::_('stylesheet', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

// Initialize the controller
$controller = BaseController::getInstance('Holidaypackages');

try {
    $controller->execute(Factory::getApplication()->getInput()->get('task'));
    $controller->redirect();
} catch (Exception $e) {
    // Log the error and show a user-friendly message
    Factory::getApplication()->getLogger()->error('Holiday Packages Error: ' . $e->getMessage());
    Factory::getApplication()->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_GENERAL'), 'error');
}