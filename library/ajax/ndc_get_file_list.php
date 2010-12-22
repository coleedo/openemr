<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This will refresh the ndc file list
/*******************************************************************/

include_once("../../interface/globals.php");
include_once("{$GLOBALS['srcdir']}/ndc_capture.inc");

if(!$ndc) return;

$display_files = array();
if( is_dir($dir) && $handle = opendir($dir)) {
    while (false !== ($filename = readdir($handle))) {
        if ($filename != "." && $filename != ".." && substr($filename,0,5) != "load_" )
        {
           $lfilename = strtolower($filename);
           $file = $dir."/".$filename;
           $check = explode(".",$lfilename);
           if( in_array($check[0],array_keys($ndc_info)) )
               $display_files[] = "<tr><td>".$ndc_info[$check[0]]['title']."</td><td>".filesize($file)."</td><td>".@date("Y-m-d H:i:s",filemtime($file))."</td></tr>\n";
        }
    }
    closedir($handle);
}
?>
        <table cellspacing='0' align='center'>
          <tr class='ndc_head'>
            <th>File data</th>
            <th>Size</th>
            <th>Last Updated</th>
          </tr>
<?php
if (!empty($display_files)) {
   foreach( $display_files as $row ) {
       echo $row;
   }
}
?>
        </table>