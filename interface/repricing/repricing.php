<?php

// Copyright (C) 2015 Tony McCormick <tony@mi-squared.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once(dirname(__FILE__) . "/../globals.php");
require_once("api/RepricingAPI.php");

?>

<html>
    <head>
        <title><?php echo xlt('EOB/Repricing') ?></title>

        <?php if (function_exists('html_header_show')) html_header_show(); ?>
        <link rel=stylesheet href="<?php echo $css_header; ?>" type="text/css">
        <link rel='stylesheet' href='<?php echo $GLOBALS['webroot'] ?>/library/css/jquery-ui-1.8.21.custom.css' type='text/css'/>
        <link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.css" media="screen" />
        <link rel=stylesheet href="www/css/repricing_entry.css?v=<?php echo rand();?>" type="text/css">
        <link rel='stylesheet' href='<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.css' type='text/css'/>

        <script type="text/javascript" src="<?php echo $GLOBALS['webroot']; ?>/library/dynarch_calendar.js"></script>
        <?php require_once($GLOBALS['srcdir'].'/dynarch_calendar_en.inc.php'); ?>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot']; ?>/library/dynarch_calendar_setup.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot']; ?>/library/textformat.js"></script>

        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/q.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.8.21.custom.min.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.pack.js"></script>
        <script type="text/javascript" src="www/js/DateFormat.js?v=<?php echo rand();?>"></script>
        <script type="text/javascript" src="www/js/view.js?v=<?php echo rand();?>"></script>
        <script>
            var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';
            var encounterID = <?php echo $encounter ? $encounter : -1 ?>;
            var repricingView = new RepricingView(<?php echo $_SESSION['pid']?>, encounterID, mypcc);
        </script>
    </head>

    <body class="body_top">

        <!--           -->
        <!-- summary   -->
        <!--           -->
        <section id="j-claim-summary" class="j-claim">
            <table>
                <tr>
                    <td class="j-label">Claim type:</td>
                    <td class="j-field" id="j-claim-type">
                        <select id="j-claim-type-selection">
                        </select>
                    </td>

                    <td class="j-label">Provider:</td>
                    <td class="j-field">
                        <input id="j-provider" type="text" value="">
                        <a href="#modal-add-provider" id="j-btn-add-provider" ><img src="<?php echo $GLOBALS['webroot'] ?>/images/add.png"></a>
                    </td>

                    <td class="j-label">Patient:</td>
                    <td class="j-field" id="j-patient-name">--</td>
                </tr>

                <tr>
                    <td class="j-label">Date of service:</td>
                    <td>
                        <input type='text' name='j-claim-date' id='j-claim-date' class="j-date-field"
                               onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
                               title='yyyy-mm-dd' />
                        <img src='<?php echo $GLOBALS['webroot']; ?>/interface/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                             id='j-claim-date-btn' border='0' alt='[?]' style='cursor:pointer'
                             title='Click here to choose a date' >
                    </td>

                    <td class="j-label">Received date:</td>
                    <td>
                        <input type='text' name='j-received-date' id='j-received-date' class="j-date-field"
                               onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
                               title='yyyy-mm-dd' />
                        <img src='<?php echo $GLOBALS['webroot']; ?>/interface/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                             id='j-received-date-btn' border='0' alt='[?]' style='cursor:pointer'
                             title='Click here to choose a date' >
                    </td>

                    <td class="j-label">Status:</td>
                    <td class="j-field">
                        <select id="j-eob-statuses">
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="j-label">Primary Payor:</td>
                    <td class="j-field" id="j-payor-primary">
                        <select id="j-payor-primary-selection">
                        </select>
                    </td>

                    <td class="j-label">Note:</td>
                    <td colspan="2">
                        <input type="text" id="j-eob-note">
                    </td>
                </tr>

                <tr>
                    <td class="j-label">Total billed:</td>
                    <td class="j-field">
                        <input id="j-total-billed" type="text">
                    </td>

                    <td class="j-label">Balance/<br>Net pay:</td>
                    <td class="j-field">
                        <input id="j-remaining-balance-net-pay" type="text">
                    </td>

                    <td></td>
                    <td></td>
                </tr>

            </table>
        </section>


        <!--                -->
        <!-- transactions   -->
        <!--                -->
        <section id="j-claim-detail-list" class="j-claim">
            <table cellspacing="3px" cellpadding="3px">
                <thead class="j-claim-detail-entry-header">
                    <th>&nbsp;</th>
                    <th>Service Date</th>
                    <th>Service Code</th>
                    <th>Service Description</th>
                    <th>Charge</th>
                    <th>Allowed</th>
                    <th>Primary</th>
                    <th>Secondary</th>
                </thead>

            </table>
        </section>

        <section id="j-claim-controls" >
            <button id="j-btn-add-service">Add service</button>
            <button id="j-btn-add-save-claim">Save</button>
            <button id="j-btn-add-cancel">Cancel</button>
        </section>

        <!--           -->
        <!-- templates -->
        <!-- (hidden)  -->
        <table class="j-template j-claim-detail-entry">
            <tr class="j-claim-detail-entry-row">
                <td>
                    <img src="<?php echo $GLOBALS['webroot'] ?>/images/deleteBtn.png" class="j-claim-entry-delete-btn">
                </td>
                <td>
                    <input type='text'
                           class="j-claim-detail-date j-date-field"
                           name=''
                           id=''
                           title='yyyy-mm-dd' />
                    <img src='<?php echo $GLOBALS['webroot']; ?>/interface/pic/show_calendar.gif'
                         align='absbottom'
                         width='24'
                         height='22'
                         id=''
                         border='0' alt='[?]' style='cursor:pointer'
                         class="j-claim-detail-date-btn"
                         title='Click here to choose a date' >
                </td>
                <td>
                    <input type="text" value="" class="j-service-code">
                </td>
                <td class="j-claim-entry-description">
                    --
                </td>
                <td>
                    <input type="text" value="" class="j-service-charge j-charge-field">
                </td>
                <td>
                    <input type="text" value="" class="j-service-allowed j-charge-field">
                </td>
                <td>
                    <input type="text" value="" class="j-service-indemnity j-charge-field">
                </td>
                <td>
                    <input type="text" value="" class="j-service-employee j-charge-field">
                </td>
            </tr>
        </table>

    </body>


</html>
