<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This will fetch the table count and the file attributes
// using the load_ file to get the time of last table load
/*******************************************************************/

include_once("../../interface/globals.php");
include_once("{$GLOBALS['srcdir']}/sql.inc");

$file_select = trim($_REQUEST['file_select']);
if( $file_select != "" ) {
    $dir = $GLOBALS['fileroot']."/contrib/ndc_directory";
    $dest = $GLOBALS['fileroot']."/contrib/ndc_dir.zip";
    $count = 0;

    $ndc_tables['schedule.txt']['data'] = "Schedule";
    $ndc_tables['schedule.txt']['table'] = "ndc_schedule";
    $ndc_tables['tblunit.txt']['data'] = "Unit Codes";
    $ndc_tables['tblunit.txt']['table'] = "ndc_unit";
    $ndc_tables['tbldosag.txt']['data'] = "Dosage Codes";
    $ndc_tables['tbldosag.txt']['table'] = "ndc_dosage";
    $ndc_tables['tblroute.txt']['data'] = "Route Codes";
    $ndc_tables['tblroute.txt']['table'] = "ndc_route";
    $ndc_tables['routes.txt']['data'] = "Routes of Administration";
    $ndc_tables['routes.txt']['table'] = "ndc_list_route";
    $ndc_tables['doseform.txt']['data'] = "Doseform";
    $ndc_tables['doseform.txt']['table'] = "ndc_list_dosage";
    $ndc_tables['firms.txt']['data'] = "Firm Names";
    $ndc_tables['firms.txt']['table'] = "ndc_firms";
    $ndc_tables['applicat.txt']['data'] = "New Applications";
    $ndc_tables['applicat.txt']['table'] = "ndc_new_app";
    $ndc_tables['formulat.txt']['data'] = "Formulations";
    $ndc_tables['formulat.txt']['table'] = "ndc_formulations";
    $ndc_tables['packages.txt']['data'] = "Packages";
    $ndc_tables['packages.txt']['table'] = "ndc_packages";
    $ndc_tables['listings.txt']['data'] = "Drug List";
    $ndc_tables['listings.txt']['table'] = "ndc_drug_list";

    $sql = "SELECT count(*) AS count FROM";
    $table = $ndc_tables[trim(strtolower($file_select))]['table'];
    $find = mysql_real_escape_string($table);
    $sql .= " $find";
    $row = sqlQuery($sql);
    $count = $row['count'];

    if ($handle = opendir($dir)) {
        while (false !== ($filename = readdir($handle))) {
            if ($filename == $file_select) {
               $lfilename = strtolower($filename);
               $len = strlen($filename);
               $check = substr($lfilename,0,$len-4);
               $origfile = $dir."/".$filename;
               $newfile = $dir."/load_".$lfilename;
               if( !file_exists($newfile) ) {
                   $newfile = $origfile;
               }else{
                   $file_info['modified'] = @date("Y-m-d H:i:s",filemtime($newfile));
                   $file_info['accessed'] = @date("Y-m-d H:i:s",fileatime($newfile));
               }
               $file_info['name'] = $filename;
               $file_info['size'] = filesize($newfile);
               echo "<table border='0' width='100%'>\n";
               echo "<tr><td width='100' align='right' nowrap><b>Table Count:</b></td><td>\n";
               echo "<input type='text' size='40' name='file_att1' id='file_att1' readonly value='".$count."' style='width:100%' /></td></tr>\n";
               echo "<tr><td width='100' align='right' nowrap><b>Data Type:</b></td><td>\n";
               echo "<input type='text' size='40' name='file_att2' id='file_att2' readonly value='".$ndc_tables[trim(strtolower($file_select))]['data']."' style='width:100%' /></td></tr>\n";
               echo "<tr><td width='100' align='right' nowrap><b>File Size:</b></td><td>\n";
               echo "<input type='text' size='40' name='file_att3' id='file_att3' readonly value='".$file_info['size']."' style='width:100%' /></td></tr>\n";
               echo "<tr><td width='100' align='right' nowrap><b>Previous Load:</b></td><td>\n";
               echo "<input type='text' size='40' name='file_att4' id='file_att4' readonly value='".$file_info['modified']."' style='width:100%' /></td></tr></table>\n";

               break;
            }
        }
        closedir($handle);
    }
}
?>
