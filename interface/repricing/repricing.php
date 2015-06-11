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

?>

<html>
    <head>
<!--        <meta http-equiv="refresh" content="2">-->

        <?php if (function_exists('html_header_show')) html_header_show(); ?>
        <link rel=stylesheet href="<?php echo $css_header; ?>" type="text/css">
        <title><?php echo xlt('EOB/Repricing') ?></title>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.js"></script>
        <script type="text/javascript" src="www/js/view.js"></script>
        <script type="text/javascript" src="www/js/main.js"></script>
        <link rel=stylesheet href="www/css/repricing_entry.css" type="text/css">
    </head>

    <body class="body_top">

        <section id="j-claim-summary" class="j-claim">
            <table>
                <tr>
                    <td class="j-label">Patient:</td>
                    <td class="j-field">Aron Racho</td>

                    <td class="j-label">Provider:</td>
                    <td class="j-field">
                        <input id="j-provider" type="text" value="Darth Maul">
                    </td>
                </tr>
            </table>
        </section>

        <section id="j-claim-detail-list" class="j-claim">

            <table>
                <thead>
                    <th>Service Date</th>
                    <th>Service Code</th>
                    <th>Service Description</th>
                    <th>Charge</th>
                </thead>

                <tr>
                    <td>
                        1/1/2005
                    </td>
                    <td>
                        <input type="text" value="97004">
                    </td>
                    <td>
                        Blah blah blah blah blah dynamic
                    </td>
                    <td>
                        <input type="text" value="123.40" >
                    </td>
                </tr>

            </table>

        </section>

        <section id="j-claim-controls" class="j-claim">
            <button id="j-btn-add-service">Add service</button>
        </section>


        <!--           -->
        <!-- templates -->
        <!--           -->
        <table class="j-template j-claim-detail-entry">
            <tr>
                <td>
                    1/1/2005
                </td>
                <td>
                    <input type="text" value="97004" class="j-service-code">
                </td>
                <td>
                    Blah blah blah blah blah dynamic
                </td>
                <td>
                    <input type="text" value="123.40" class="j-service-charge">
                </td>
            </tr>
        </table>

    </body>


</html>
