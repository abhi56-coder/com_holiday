<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('bootstrap.loadCss');
HTMLHelper::_('behavior.formvalidator');

$form = $this->form;
$item = $this->item;
?>

<div class="holidaypackages-edit">
    <form action="<?php echo Route::_('index.php?option=com_holidaypackages&task=editdashboard.save'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
        <div class="form-horizontal">
            <?php echo $form->renderField('id'); ?>
            <?php echo $form->renderField('title'); ?>
            <?php echo $form->renderField('category'); ?> <!-- updated from 'link' to 'category' -->
            <?php echo $form->renderField('published'); ?>
        </div>

        <input type="hidden" name="task" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<script>
Joomla.submitbutton = function(task) {
    if (task == 'editdashboard.cancel' || document.formvalidator.isValid(document.getElementById('adminForm'))) {
        Joomla.submitform(task, document.getElementById('adminForm'));
    }
};
</script>