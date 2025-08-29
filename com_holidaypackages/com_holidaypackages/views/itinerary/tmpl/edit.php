<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$input = Factory::getApplication()->getInput();
$packageId = $input->getInt('package_id', 0);
$destinationId = $input->getInt('destination_id', 0);
$itemId = $input->getInt('id', 0);

if ($packageId == 0) {
    Factory::getApplication()->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_PACKAGE_ID'), 'error');
    Factory::getApplication()->redirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&destination_id=' . (int)$destinationId, false));
    return false;
}

$form = $this->form;

if (!$form) {
    Factory::getApplication()->enqueueMessage(Text::_('COM_HOLIDAYPACKAGES_ERROR_FORM_NOT_FOUND'), 'error');
    Factory::getApplication()->redirect(Route::_('index.php?option=com_holidaypackages&view=itineraries&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId, false));
    return false;
}

// Bind item data to form
$form->bind($this->item);
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=itinerary.save&id=' . (int)($this->item->id ?? 0) . '&package_id=' . (int)$packageId . '&destination_id=' . (int)$destinationId); ?>" 
      method="post" 
      name="adminForm" 
      id="adminForm" 
      class="form-validate form-horizontal">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <fieldset class="adminform">
                        <?php foreach ($this->form->getFieldset('package') as $field) : ?>
                            <div class="control-group">
                                <div class="control-label"><?php echo $field->label; ?></div>
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                    <?php if ($field->description) : ?>
                                        <small class="form-text text-muted"><?php echo Text::_($field->description); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="jform[id]" value="<?php echo (int)($this->item->id ?? 0); ?>" />
    <input type="hidden" name="jform[package_id]" value="<?php echo (int)$packageId; ?>" />
    <input type="hidden" name="jform[destination_id]" value="<?php echo (int)$destinationId; ?>" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>