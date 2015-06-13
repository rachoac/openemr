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

        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/q.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.8.21.custom.min.js"></script>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.pack.js"></script>
        <script type="text/javascript" src="www/js/view.js?v=<?php echo rand();?>"></script>
        <script>
            var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';
            var repricingView = new RepricingView(<?php echo $_SESSION['pid']?>, mypcc);
        </script>
    </head>

    <body class="body_top">

        <!--           -->
        <!-- summary   -->
        <!--           -->
        <section id="j-claim-summary" class="j-claim">
            <table>
                <tr>
                    <td class="j-label">Patient:</td>
                    <td class="j-field" id="j-patient-name">--</td>

                    <td class="j-label">Provider:</td>
                    <td class="j-field">
                        <input id="j-provider" type="text" value="" data-provider-id="">
                        <a href="#modal-add-provider" id="j-btn-add-provider" ><img src="<?php echo $GLOBALS['webroot'] ?>/images/add.png"></a>
                    </td>
                </tr>

                <tr>
                    <td class="j-label">Claim date:</td>
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
                </thead>

            </table>
        </section>

        <section id="j-claim-controls" class="j-claim">
            <button id="j-btn-add-service">Add service</button>
        </section>

        <!--           -->
        <!-- modals    -->
        <!-- (hidden)  -->
        <div style="display: none">
            <div id="modal-add-provider" class="j-modal">
                <h2>Create provider</h2>
                <table>
                    <tr>
                        <td>
                            First name
                        </td>
                        <td class="j-field">
                            <input type="text" id="j-provider-fname">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Middle name
                        </td>
                        <td class="j-field">
                            <input type="text" id="j-provider-mname">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Last name
                        </td>
                        <td class="j-field">
                            <input type="text" id="j-provider-lname"
                        </td>
                    </tr>
                    <tr>
                        <td>
                            NPI
                        </td>
                        <td class="j-field">
                            <input type="text" id="j-provider-npi">
                        </td>
                    </tr>
                </table>

                <section class="j-modal-nav">
                    <button id="j-btn-save-provider">Save</button>
                </section>
            </div>
        </div>

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
                    <input type="text" value="" class="j-service-charge">
                </td>
            </tr>
        </table>

    </body>


</html>
