<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Removed unnecessary JLoader registration as Joomla autoloads ToolbarHelper

?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=policy.save&id=' . (int)$this->item->id . '&package_id=' . (int)$this->state->get('filter.package_id') . '&destination_id=' . (int)$this->state->get('filter.destination_id')); ?>" 
      method="post" 
      name="adminForm" 
      id="adminForm" 
      class="form-validate">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3><?php echo $this->item->id ? Text::_('COM_HOLIDAYPACKAGES_EDIT_POLICY') : Text::_('COM_HOLIDAYPACKAGES_NEW_POLICY'); ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($this->form && $this->item && is_object($this->item)) : ?>
                        <?php echo $this->form->renderFieldset('details'); ?>
                    <?php else : ?>
                        <p><?php echo Text::_('COM_HOLIDAYPACKAGES_ERROR_NO_DATA'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>