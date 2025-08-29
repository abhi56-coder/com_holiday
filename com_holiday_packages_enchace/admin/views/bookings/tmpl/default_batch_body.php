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

/** @var HolidayPackagesViewBookings $this */

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="control-group">
                <div class="control-label">
                    <label id="batch-status-lbl" for="batch-status">
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_BATCH_STATUS_LABEL'); ?>
                    </label>
                </div>
                <div class="controls">
                    <select name="batch[status]" class="form-select" id="batch-status">
                        <option value=""><?php echo Text::_('COM_HOLIDAYPACKAGES_KEEP_ORIGINAL_STATUS'); ?></option>
                        <option value="pending"><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_PENDING'); ?></option>
                        <option value="confirmed"><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_CONFIRMED'); ?></option>
                        <option value="cancelled"><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_CANCELLED'); ?></option>
                        <option value="completed"><?php echo Text::_('COM_HOLIDAYPACKAGES_BOOKING_STATUS_COMPLETED'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="control-group">
                <div class="control-label">
                    <label id="batch-package-lbl" for="batch-package">
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_BATCH_PACKAGE_LABEL'); ?>
                    </label>
                </div>
                <div class="controls">
                    <select name="batch[package_id]" class="form-select" id="batch-package">
                        <option value=""><?php echo Text::_('COM_HOLIDAYPACKAGES_KEEP_ORIGINAL_PACKAGE'); ?></option>
                        <?php 
                        // In a real implementation, you would load available packages here
                        // For now, we'll add a placeholder
                        ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="control-group">
                <div class="control-label">
                    <label id="batch-notes-lbl" for="batch-notes">
                        <?php echo Text::_('COM_HOLIDAYPACKAGES_BATCH_NOTES_LABEL'); ?>
                    </label>
                </div>
                <div class="controls">
                    <textarea name="batch[notes]" class="form-control" id="batch-notes" rows="3" 
                              placeholder="<?php echo Text::_('COM_HOLIDAYPACKAGES_BATCH_NOTES_PLACEHOLDER'); ?>"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>