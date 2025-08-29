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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var HolidayPackagesViewBookings $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('table.columns')
   ->useScript('multiselect');

$user      = Factory::getUser();
$userId    = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
$saveOrder = $listOrder === 'b.ordering';

if ($saveOrder && !empty($this->items)) {
    $saveOrderingUrl = 'index.php?option=com_holidaypackages&task=bookings.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
    HTMLHelper::_('draggablelist.draggable');
}
?>

<form action="<?php echo Route::_('index.php?option=com_holidaypackages&view=bookings'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table table-striped table-hover" id="bookingsList">
                        <caption class="visually-hidden">
                            <?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKINGS_TABLE_CAPTION'); ?>
                            <span id="orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>
                            <span id="filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
                        </caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col" class="w-1 text-center d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', '', 'b.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-sort'); ?>
                                </th>
                                <th scope="col" class="w-1 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'b.status', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_BOOKING_REFERENCE', 'b.booking_reference', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_CUSTOMER', 'customer_name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_PACKAGE', 'package_title', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_TRAVEL_DATE', 'b.start_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_HOLIDAYPACKAGES_TRAVELERS'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_AMOUNT', 'b.total_amount', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-lg-table-cell">
                                    <?php echo Text::_('COM_HOLIDAYPACKAGES_PAYMENT_STATUS'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_HOLIDAYPACKAGES_BOOKING_DATE', 'b.booking_date', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-10">
                                    <?php echo Text::_('COM_HOLIDAYPACKAGES_ACTIONS'); ?>
                                </th>
                                <th scope="col" class="w-3 d-none d-lg-table-cell">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'b.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody<?php if ($saveOrder) : ?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="true"<?php endif; ?>>
                            <?php foreach ($this->items as $i => $item) :
                                $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || is_null($item->checked_out);
                                $canChange  = $user->authorise('core.edit.state', 'com_holidaypackages') && $canCheckin;
                                $canEdit    = $this->canEdit($item);
                                ?>
                                <tr class="row<?php echo $i % 2; ?>" data-draggable-group="<?php echo $item->package_id ?? '0'; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->booking_reference); ?>
                                    </td>
                                    
                                    <td class="text-center d-none d-md-table-cell">
                                        <?php
                                        $iconClass = '';
                                        if (!$canChange) {
                                            $iconClass = ' inactive';
                                        } elseif (!$saveOrder) {
                                            $iconClass = ' inactive" title="' . Text::_('JORDERINGDISABLED');
                                        }
                                        ?>
                                        <span class="sortable-handler<?php echo $iconClass; ?>">
                                            <span class="icon-ellipsis-v" aria-hidden="true"></span>
                                        </span>
                                        <?php if ($canChange && $saveOrder) : ?>
                                            <input type="text" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order hidden">
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php echo $this->getStatusBadge($item->status); ?>
                                    </td>
                                    
                                    <td>
                                        <div class="fw-bold">
                                            <?php if ($canEdit) : ?>
                                                <a href="<?php echo Route::_('index.php?option=com_holidaypackages&task=booking.edit&id=' . (int) $item->id); ?>" title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->booking_reference); ?>">
                                                    <?php echo $this->escape($item->booking_reference); ?>
                                                </a>
                                            <?php else : ?>
                                                <?php echo $this->escape($item->booking_reference); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small text-muted d-md-none">
                                            <?php echo $this->escape($item->customer_name); ?>
                                            <br><?php echo $this->escape($item->package_title); ?>
                                        </div>
                                    </td>
                                    
                                    <td class="d-none d-md-table-cell">
                                        <div class="fw-bold"><?php echo $this->escape($item->customer_name); ?></div>
                                        <?php if (!empty($item->customer_email)) : ?>
                                            <div class="small text-muted">
                                                <a href="mailto:<?php echo $this->escape($item->customer_email); ?>" class="text-decoration-none">
                                                    <?php echo $this->escape($item->customer_email); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item->customer_phone)) : ?>
                                            <div class="small text-muted">
                                                <a href="tel:<?php echo $this->escape($item->customer_phone); ?>" class="text-decoration-none">
                                                    <?php echo $this->escape($item->customer_phone); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell">
                                        <div class="fw-bold"><?php echo $this->escape($item->package_title); ?></div>
                                        <?php if (!empty($item->destination)) : ?>
                                            <div class="small text-muted"><?php echo $this->escape($item->destination); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($item->duration)) : ?>
                                            <div class="small text-muted"><?php echo $this->escape($item->duration); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="d-none d-md-table-cell">
                                        <div class="fw-bold">
                                            <?php echo HTMLHelper::_('date', $item->start_date, Text::_('DATE_FORMAT_LC4')); ?>
                                        </div>
                                        <?php if ($item->start_date !== $item->end_date) : ?>
                                            <div class="small text-muted">
                                                <?php echo Text::_('COM_HOLIDAYPACKAGES_TO'); ?>
                                                <?php echo HTMLHelper::_('date', $item->end_date, Text::_('DATE_FORMAT_LC4')); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="small text-muted">
                                            <?php echo $this->getBookingDuration($item->start_date, $item->end_date); ?>
                                        </div>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo $this->getTravelerCount($item->adults, $item->children, $item->infants); ?>
                                    </td>
                                    
                                    <td class="d-none d-md-table-cell">
                                        <div class="fw-bold"><?php echo $this->formatCurrency($item->total_amount); ?></div>
                                        <?php if ($item->paid_amount > 0) : ?>
                                            <div class="small text-muted">
                                                <?php echo Text::_('COM_HOLIDAYPACKAGES_PAID'); ?>: <?php echo $this->formatCurrency($item->paid_amount); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo $this->getPaymentStatusBadge($item->total_amount, $item->paid_amount, $item->status); ?>
                                    </td>
                                    
                                    <td class="d-none d-md-table-cell">
                                        <div class="small">
                                            <?php echo HTMLHelper::_('date', $item->booking_date, Text::_('DATE_FORMAT_LC4')); ?>
                                        </div>
                                        <?php if (!empty($item->modified)) : ?>
                                            <div class="small text-muted">
                                                <?php echo Text::_('JGLOBAL_FIELD_MODIFIED_LABEL'); ?>:
                                                <?php echo HTMLHelper::_('date', $item->modified, Text::_('DATE_FORMAT_LC4')); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php echo $this->getQuickActions($item); ?>
                                    </td>
                                    
                                    <td class="d-none d-lg-table-cell">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php // Load the batch processing form ?>
                    <?php if ($user->authorise('core.edit.state', 'com_holidaypackages')) : ?>
                        <?php echo HTMLHelper::_(
                            'bootstrap.renderModal',
                            'collapseModal',
                            [
                                'title'  => Text::_('COM_HOLIDAYPACKAGES_BATCH_OPTIONS'),
                                'footer' => $this->loadTemplate('batch_footer'),
                            ],
                            $this->loadTemplate('batch_body')
                        ); ?>
                    <?php endif; ?>

                    <?php // Load the pagination ?>
                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>

<?php // Status change confirmation modal ?>
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusChangeModalLabel"><?php echo Text::_('COM_HOLIDAYPACKAGES_CONFIRM_STATUS_CHANGE'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="statusChangeMessage"></p>
                <div class="mb-3" id="refundAmountGroup" style="display: none;">
                    <label for="refundAmount" class="form-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_REFUND_AMOUNT'); ?></label>
                    <input type="number" class="form-control" id="refundAmount" step="0.01" min="0">
                </div>
                <div class="mb-3" id="reasonGroup" style="display: none;">
                    <label for="statusChangeReason" class="form-label"><?php echo Text::_('COM_HOLIDAYPACKAGES_REASON'); ?></label>
                    <textarea class="form-control" id="statusChangeReason" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange"><?php echo Text::_('JCONFIRM'); ?></button>
            </div>
        </div>
    </div>
</div>