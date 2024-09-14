<?php
/**
 * Analytics
 *
 * SPDX-FileCopyrightText: 2019-2022 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

?>
<div id="analytics-content" hidden>
    <input type="hidden" name="sharingToken" value="<?php p($_['token']); ?>" id="sharingToken">
    <input type="hidden" name="dataset" value="" id="datasetId">
    <input type="hidden" name="advanced" value="false" id="advanced">
    <input type="hidden" name="panorama" value="false" id="panorama">
	<?php print_unescaped($this->inc('part.menu')); ?>
    <span id="reportHeader" class="reportHeader"></span>
    <span id="reportSubHeader" class="reportSubHeader" hidden></span>
    <div id="reportPlaceholder"></div>
    <div id="chartContainer">
    </div>
    <div id="chartLegendContainer">
        <div id="chartLegend" class="icon icon-menu"><?php p($l->t('Legend')); ?></div>
    </div>
    <div id="tableSeparatorContainer"></div>
    <table id="tableContainer"></table>
    <div id="noDataContainer" hidden>
        <?php p($l->t('No data found')); ?>
    </div>
    <div style="margin-bottom: 65px"></div>
    <div id="byAnalytics" class="byAnalytics" style="display: none;">
        <img id="byAnalyticsImg" style="width: 33px; margin-right: 7px;margin-left: 10px;" src="<?php echo \OC::$server->getURLGenerator()->imagePath('analytics', 'app-color.svg') ?>">
        <span style="font-size: 12px; line-height: 14px;">created with<br>Analytics</span>
    </div>
</div>
<div id="analytics-loading" style="width:100%; padding: 100px 5%;" hidden>
    <div style="text-align:center; padding-top:100px" class="get-metadata icon-loading"></div>
</div>
