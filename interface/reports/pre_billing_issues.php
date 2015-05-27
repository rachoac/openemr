<?php
// Copyright (C) 2015 Tony McCormick <tony@mi-squared.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

$fake_register_globals=false;
$sanitize_all_escapes=true;

require_once("api/PreBillingIssuesAPI.php");
require_once(dirname(__FILE__) . "/../globals.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/htmlspecialchars.inc.php");

//////////////////////////////////////////////////////////////////////////////////////////////////////////
// main
//////////////////////////////////////////////////////////////////////////////////////////////////////////

$reportData = computeReport();

//////////////////////////////////////////////////////////////////////////////////////////////////////////
// private
//////////////////////////////////////////////////////////////////////////////////////////////////////////

function computeReport() {
    $preBillingIssuesAPI = new PreBillingIssuesAPI();
    $reportData = array();
    $reportData['encountersMissingProvider'] = $preBillingIssuesAPI->findEncountersMissingProvider();
    $reportData['patientInsuranceMissingSubscriberFields'] = $preBillingIssuesAPI->findPatientInsuranceMissingSubscriberFields();
    $reportData['patientInsuranceMissingSubscriberRelationship'] = $preBillingIssuesAPI->findPatientInsuranceMissingSubscriberRelationship();
    $reportData['patientInsuranceMissingInsuranceFields'] = $preBillingIssuesAPI->findPatientInsuranceMissingInsuranceFields();

    return $reportData;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////
// render page - main
//////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
<html>
    <head>
        <?php if (function_exists('html_header_show')) html_header_show(); ?>
        <link rel=stylesheet href="<?php echo $css_header; ?>" type="text/css">
        <title><?php xla('Pre-billing Issues Report', 'e') ?></title>
        <style type="text/css">
            .highlight {
                color: white;
                text-decoration: underline;
                background-color: black;
            }
        </style>
        <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.js"></script>
        <script language="javascript">
            var concurrentLayout = <?php echo $GLOBALS['concurrent_layout'] ? true : false ?>;
            
            function toencounter(pid, pubpid, pname, enc, datestr, dobstr) {
                dobstr = dobstr ? dobstr : '';
                top.restoreSession();
                if ( concurrentLayout ) {
                    var othername = (window.name == 'RTop') ? 'RBot' : 'RTop';
                    parent.left_nav.setPatient(pname,pid,pubpid,'',dobstr);
                    parent.left_nav.setEncounter(datestr, enc, othername);
                    parent.left_nav.setRadio(othername, 'enc');
                    parent.frames[othername].location.href =
                    '../patient_file/encounter/encounter_top.php?set_encounter='
                        + enc + '&pid=' + pid;
                } else {
                     location.href = '../patient_file/encounter/patient_encounter.php?set_encounter='
                        + enc + '&pid=' + pid;
                }
            }
            
            function topatient(pid, pubpid, pname, enc, datestr, dobstr) {
                dobstr = dobstr ? dobstr : '';
                top.restoreSession();
                if ( concurrentLayout ) {
                    var othername = (window.name == 'RTop') ? 'RBot' : 'RTop';
                    parent.left_nav.setPatient(pname,pid,pubpid,'',dobstr);
                    parent.frames[othername].location.href =
                     '../patient_file/summary/demographics_full.php?pid=' + pid;
                } else {
                      location.href = '../patient_file/summary/demographics_full.php?pid=' + pid;
                }
            }
            
            $(document).ready(function () {
                $(".reportrow").mouseover(function () {
                    $(this).addClass("highlight");
                });
                $(".reportrow").mouseout(function () {
                    $(this).removeClass("highlight");
                });
                $(".encrow").click(function () {
                    toencounter(
                        $(this).attr('pid'),
                        $(this).attr('pubpid'),
                        $(this).attr('pname'),
                        $(this).attr('encid'),
                        $(this).attr('encdate'),
                        $(this).attr('pdob')
                    );
                });
                $(".ptrow").click(function () {
                    topatient(
                        $(this).attr('pid'),
                        $(this).attr('pubpid'),
                        $(this).attr('pname'),
                        $(this).attr('encid'),
                        $(this).attr('encdate'),
                        $(this).attr('pdob')
                    );
                });
                
                $(".reportrow").attr('title', '<?php echo xla('Click through to correct this record', 'e') ?>');
            });

        </script>
    </script>

</head>

<body class="body_top">
    <span class='title'><?php xla('Report', 'e'); ?> - <?php xla('Pre-billing Issues', 'e'); ?></span>
    
    <p>
        <?php xla('Use this report to discover billing errors. You may click through each row to drill into the record that requires an update.', 'e') ?>
    </p>

    <h5><?php xla('Encounters without rendering provider', 'e') ?></h5>
    <div id="report_results">
        <table>
            <thead>
               <th>&nbsp;<?php xla('Patient Name','e')?></th>
               <th>&nbsp;<?php xla('Encounter Date','e')?></th>
            </thead>

            <?php foreach ($reportData['encountersMissingProvider'] as $index => $row) { ?>
                <tr class='encrow reportrow' 
                    bgcolor='<?php echo $index % 2 == 0 ? "#ffdddd" : "#ddddff" ?>'
                    pid='<?php echo attr($row['Pt ID']) ?>' 
                    pubpid='<?php echo attr($row['Pub Pt ID']) ?>' 
                    pname='<?php echo attr($row['LName'] . ', ' . $row['FName']) ?>' 
                    encid='<?php echo attr(oeFormatShortDate($row['Enc ID'])) ?>'
                    encdate='<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($row['Encounter Date'])))) ?>'
                    pdob='<?php echo attr($row['Pt DOB']) ?>' 
                >
                    <td class='detail'><?php echo attr($row['LName'] . ', ' . $row['FName']) ?></td>
                    <td class='detail'><?php echo htmlspecialchars(oeFormatShortDate(date("Y-m-d", strtotime($row['Encounter Date']))), ENT_QUOTES) ?></td>
                </tr>
            <?php } ?>
        </table>

        <h5><?php xla('Incomplete patient insurance subscriber fields', 'e') ?></h5>
        <table>
            <thead>
               <th>&nbsp;<?php xla('Patient Name','e')?></th>
               <th>&nbsp;<?php xla('Insurance Type','e')?></th>
               <th>&nbsp;<?php xla('Subscriber Relationship','e')?></th>
               <th>&nbsp;<?php xla('Errors','e')?></th>            
            </thead>
            
            <?php foreach ($reportData['patientInsuranceMissingSubscriberFields'] as $index => $row) { ?>
                <tr class='ptrow reportrow' 
                    bgcolor='<?php echo $index % 2 == 0 ? "#ffdddd" : "#ddddff" ?>'
                    pid='<?php echo attr($row['Pt ID']) ?>' 
                    pubpid='<?php echo attr($row['Pub Pt ID']) ?>' 
                    pname='<?php echo attr($row['LName'] . ', ' . $row['FName']) ?>' 
                    encid='<?php echo attr($row['Enc ID']) ?>'
                    encdate='<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($row['Encounter Date'])))) ?>'
                    pdob='<?php echo attr(oeFormatShortDate($row['Pt DOB'])) ?>' 
                >
                    <td class='detail'><?php echo attr($row['LName'] . ', ' . $row['FName']) ?></td>
                    <td class='detail'><?php echo attr($row['Insurance Type']) ?></td>
                    <td class='detail'><?php echo attr($row['Subscriber Relationship']) ?></td>
                    <td class='detail'>
                        <?php foreach ($row['decodedErrors'] as $error) { ?>
                            <?php echo xla($error, 'e') ?> <br>
                        <?php } ?>
                    </td>                    
                </tr>
            <?php } ?>
        </table>

        <h5><?php xla('Incomplete patient insurance missing subscriber relationship', 'e') ?></h5>
        <table>
            <thead>
               <th>&nbsp;<?php xla('Patient Name','e')?></th>
               <th>&nbsp;<?php xla('Insurance Type','e')?></th>
            </thead>
            <?php foreach ($reportData['patientInsuranceMissingSubscriberRelationship'] as $index => $row) { ?>
                <tr class='ptrow reportrow' 
                    bgcolor='<?php echo $index % 2 == 0 ? "#ffdddd" : "#ddddff" ?>'
                    pid='<?php echo attr($row['Pt ID']) ?>' 
                    pubpid='<?php echo attr($row['Pub Pt ID']) ?>' 
                    pname='<?php echo attr($row['LName'] . ', ' . $row['FName']) ?>' 
                    encid='<?php echo attr($row['Enc ID']) ?>'
                    encdate='<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($row['Encounter Date'])))) ?>'
                    pdob='<?php echo attr(oeFormatShortDate($row['Pt DOB'])) ?>' 
                >
                    <td class='detail'><?php echo attr($row['LName'] . ', ' . $row['FName']) ?></td>
                    <td class='detail'><?php echo attr($row['Insurance Type']) ?></td>
                </tr>
            <?php } ?>
        </table>

        <h5><?php xla('Incomplete patient insurance', 'e') ?></h5>
        <table>
            <thead>
               <th>&nbsp;<?php xla('Patient Name','e')?></th>
               <th>&nbsp;<?php xla('Insurance Type','e')?></th>
               <th>&nbsp;<?php xla('Errors','e')?></th>
            </thead>            
            <?php foreach ($reportData['patientInsuranceMissingInsuranceFields'] as $index => $row) { ?>
                <tr class='ptrow reportrow' 
                    bgcolor='<?php echo $index % 2 == 0 ? "#ffdddd" : "#ddddff" ?>'
                    pid='<?php echo attr($row['Pt ID']) ?>' 
                    pubpid='<?php echo attr($row['Pub Pt ID']) ?>' 
                    pname='<?php echo attr($row['LName'] . ', ' . $row['FName']) ?>' 
                    encid='<?php echo attr($row['Enc ID']) ?>'
                    encdate='<?php echo attr(oeFormatShortDate(date("Y-m-d", strtotime($row['Encounter Date'])))) ?>'
                    pdob='<?php echo attr(oeFormatShortDate($row['Pt DOB'])) ?>' 
                >
                    <td class='detail'><?php echo attr($row['LName'] . ', ' . $row['FName']) ?></td>
                    <td class='detail'><?php echo attr($row['Insurance Type']) ?></td>
                    <td class='detail'>
                        <?php foreach ($row['decodedErrors'] as $error) { ?>
                            <?php echo xla($error, 'e') ?> <br>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>