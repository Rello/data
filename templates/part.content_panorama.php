<?php
/**
 * Analytics
 *
 * SPDX-FileCopyrightText: 2024 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

?>

<style>
    .panoramaHeaderRow {
        padding-right: 120px;
        padding-top: 20px;
        padding-left: 20px;
    }

    .panoramaHeader {
        font-weight: bold;
        font-size: 22px;
    }

    .panoramaSubHeaderRow {
        padding-bottom: 10px;
        padding-left: 20px;
    }

    .panoramaSubHeader {
        font-size: 16px;
    }

    .panoramaHeaderRow .editable,
    .panoramaSubHeaderRow .editable {
        padding: 1px;
    }

    .panoramaSubHeader[contenteditable="true"],
    .panoramaHeader[contenteditable="true"] {
        border: 1px dashed blue;
        padding: initial;
        cursor: text;
        margin: initial;
        background-color: initial;
        width: initial;
        height: initial;
        min-height: initial;
    }

    .container {
        width: 100%;
        height: 100%;
        overflow: hidden;
        margin-top: -72px;
        padding-top: 72px;
    }

    .pages {
        display: flex;
        width: 200%;
        transition: margin-left 0.5s ease;
        height: calc(100% - 60px);
        margin: 10px;
    }

    .flex-container {
        width: 100%;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .flex-row {
        flex: 1;
        display: flex;
    }

    .flex-item {
        flex: 1;
        padding: 25px;
        position: relative;
    }

    .textContainer {
        flex-direction: column;
        display: flex;
        height: 100%;
        justify-content: center;
        padding-left: 50px;
    }

    .textContainer ul {
        list-style: initial;
        padding-left: 40px;
    }

    .textContainer h1 {
        font-weight: bold;
        font-size: 25px;
    }

    .hintBox {
        display: flex;
        height: 100%;
        justify-content: center;
        align-items: center;
    }

    .bg-azure {
        background-color: azure;
    }

    .navigation {
        z-index: 9999;
        position: fixed;
        bottom: 20px;
        right: 20px;
        font-size: 24px;
    }

    .nav-item {
        margin: 0 10px;
        cursor: pointer;
        font-weight: 500;
    }

    .nav-item:hover {
        opacity: 1;
    }

    .nav-item.disabled {
        opacity: 0.1;
        cursor: not-allowed;
    }

    .nav-item.highlighted {
        color: var(--color-warning);
        opacity: 0.8;
    }

    #optionBtnContainer {
        z-index: 9999;
        position: relative;
        top: 10px;
        right: 20px;
        font-size: 24px;
    }

    .addPage {
        z-index: 9999;
        position: fixed;
        bottom: 40px;
        right: 20px;
        font-size: 24px;
    }

    .editMenuContainer {
        position: fixed;
        width: 250px;
        height: 250px;
        top: 40%;
        left: 50%;
        z-index: 1000;
    }

    .editMenu {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .menu-item {
        position: absolute;
        width: 60px;
        height: 60px;
        text-align: center;
        line-height: 60px;
        background-color: var(--color-info);
        color: white;
        border-radius: 50%;
        cursor: pointer;
        /* You'll need to adjust the following transform values for each item
        transform: translate(0px, 0px); */
    }

    .menu-item:hover {
        background-color: var(--color-info-hover);
    }

    .editMenu .close-menu-item {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        /* Additional styles for the close button if necessary */
    }

    /* Modal styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1001; /* Sit on top */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgb(0, 0, 0); /* Fallback color */
        background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
    }

    .modal-content {
        width: 300px;
        height: 400px;
        top: 40%;
        left: 50%;
        background-color: #fefefe;
        padding: 20px;
        border: 1px solid #888;
        position: relative;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .overlay {
        position: absolute;
        top: 20px;
        left: 20px;
        right: 20px;
        bottom: 20px;
        background-color: rgba(0, 0, 0, 0.4); /* Semi-transparent overlay */
        z-index: 5; /* Ensure it's above content but below menu */
        align-items: center;
        display: flex;
        justify-content: center;
        cursor: pointer;
    }

    .overlay.active {
        background-color: rgba(0, 0, 0, 0.1); /* Semi-transparent overlay */
    }

    .overlayText {
        background-color: white;
        padding: 5px; /* Adjust padding as needed */
        cursor: pointer;
    }

    #reportSelectorContainer {
        max-height: 280px; /* Adjust the max height as needed */
        overflow-y: auto; /* Enables vertical scrolling when content overflows */
    }

    .reportSelectorItem {
        padding: 5px;
    }

    .reportSelectorItem:hover {
        cursor: pointer;
        background-color: var(--color-primary-element-hover);
        color: var(--color-primary-text);
    }

    /* Layout Selector */
    .layoutModal {
        position: fixed;
        z-index: 10;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .layoutModalContent {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 60%;
    }

    .layoutModalGrid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .layoutModalGridCell {
        width: calc(25% - 20px); /* Adjust based on gap size */
        border: 1px solid #ddd;
        padding: 5px;
        box-sizing: border-box;
    }

    .layoutModalGridCell:hover {
        cursor: pointer;
        background-color: var(--color-primary-light-hover);
    }

    .layoutModalName {
        text-align: center;
        margin-top: 5px;
        font-size: 14px;
        color: #333;
    }

    .layoutModalHeader {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
    }

    .layoutModalTitle {
        font-size: 20px;
        font-weight: bold;
    }

    .layoutModalGridPreview {
        /* Additional styling for layout preview */
        height: 100px; /* Set a fixed height for previews */
        overflow: hidden; /* Hide overflow */
    }

    .layoutModalGridPreview .flex-item {
        padding: 0;
        margin: 2px;
        border: 1px solid #888;
    }

    .heading-anchor,
    .text-readonly-bar {
        display: none !important;
    }
</style>
<div id="analytics-content" hidden>
    <div id="optionBtnContainer" class="edit">
        <div id="optionBtn" class="analytics-options icon-analytics-more has-tooltip"
             title="<?php p($l->t('Options')); ?>"></div>
        <div id="fullscreenToggle" class="analytics-options icon-analytics-fullscreen"></div>
    </div>

    <div class="panoramaHeaderRow">
        <div id="panoramaHeader" class="panoramaHeader editable"></div>
    </div>

    <div id="editMenuContainer" class="editMenuContainer" style="display:none;">
        <div class="editMenu" id="editMenu">
            <div class="menu-item" data-modal="modalReport">Report</div>
            <div class="menu-item" data-modal="modalText">Text</div>
            <!--<div class="menu-item" data-modal="modal3">Empty</div>-->
            <div class="menu-item" data-modal="modalPicture">Picture</div>
            <div class="menu-item close-menu-item" data-modal="close">X</div>
        </div>
    </div>

    <!-- Modals for the edit menu -->
    <div>
        <div id="modalReport" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Choose a report</h2><br>
                <div id="reportSelectorContainer"></div>
            </div>
        </div>
        <div id="modalText" class="modal">
            <div class="modal-content" style="width: 700px; height: 500px; top: 30%; left: 40%;">
                <span class="close">&times;</span>
                <h2>Enter a free text</h2><br>
                <textarea id="textInputContent" hidden></textarea>
                <div id="textInput" style="width: 649px;height: 330px;overflow: hidden;"></div>
                <br>
                <button type="button" id="textInputButton">save</button>
            </div>
        </div>
        <div id="modal3" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Platzhalter für Später</h2>
            </div>
        </div>
        <div id="modalPicture" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Choose a picture</h2><br>
                <span class="userGuidance">Select a picture from Nextcloud</span>
                <br><br>
                <button type="button" id="pictureInputButton">Choose</button>
            </div>
        </div>
    </div>

    <div id="navBtnContainer" class="navigation">
        <span class="nav-item" id="prevBtn"><</span>
        <span class="nav-item" id="nextBtn">></span>
    </div>

    <div id="optionMenu" class="popovermenu" style="top: 50px; right: 10px;">
        <ul id="optionMenuMain">
            <li>
                <button id="optionMenuEdit">
                    <span id="optionMenuEditIcon" class="icon-rename"></span>
                    <span id="optionMenuEditText"></span>
                </button>
            </li>
            <li>
                <button id="optionMenuLayout">
                    <span class="icon-analytics-drilldown"></span>
                    <span><?php p($l->t('Change layout')); ?></span>
                </button>
            </li>
            <li>
                <button id="optionMenuDeletePage">
                    <span class="icon-delete"></span>
                    <span><?php p($l->t('Delete current page')); ?></span>
                </button>
            </li>
            <li>
                <button id="optionMenuPdf">
                    <span class="icon-analytics-pdf"></span>
                    <span><?php p($l->t('Export as PDF')); ?></span>
                </button>
            </li>

        </ul>
    </div>

    <!-- LayoutChooser -->
    <div id="layoutModal" class="layoutModal" style="display:none;">
        <div class="layoutModalContent">
            <div class="layoutModalHeader">
                <span class="layoutModalTitle">Select Layout</span>
                <span id="layoutModalClose" class="close">&times;</span>
            </div>
            <div id="layoutModalGrid" class="layoutModalGrid">
                <!-- Layout previews will be dynamically inserted here -->
            </div>
        </div>
    </div>

    <div class="pages" id="panoramaPages">
    </div>
    <div id="byAnalytics" class="byAnalytics" style="display: none;">
        <img id="byAnalyticsImg" style="width: 33px; margin-right: 7px;" src="<?php echo \OC::$server->getURLGenerator()->imagePath('analytics', 'app-color.svg') ?>">
        <span style="font-size: 12px; line-height: 14px;">created with<br>Analytics</span>
    </div>
</div>