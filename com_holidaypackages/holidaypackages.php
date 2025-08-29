<?php
defined('_JEXEC') or die;

// Get an instance of the controller
$controller = JControllerLegacy::getInstance('Holidaypackages');

// Perform the request task
$controller->execute(JFactory::getApplication()->input->get('task'));

// Redirect if set by the controller
$controller->redirect();
