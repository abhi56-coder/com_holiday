<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('behavior.formvalidator');
?>

<form action="<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_holidaypackages&layout=edit&id=' . (int) $this->item->id); ?>" 
      method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="row-fluid">
        <div class="span10 form-horizontal">
            <?php echo $this->form->renderFieldset('basic'); ?>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>