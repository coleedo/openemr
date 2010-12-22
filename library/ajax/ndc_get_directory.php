<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This file allows pulls down and installs the archive file
// containing the the NDC directory data files. The archive is
// uncompressed, and the db tables are created if they don't already exist
/*******************************************************************/

include_once("../../interface/globals.php");
include_once("{$GLOBALS['srcdir']}/sql.inc");
include_once("{$GLOBALS['srcdir']}/ndc_capture.inc");
include_once("{$GLOBALS['srcdir']}/formdata.inc.php");

if(!$ndc) return;
if(!$GLOBALS['ndc_zip_url']) return;

$dir = $GLOBALS['fileroot']."/contrib/ndc_directory";
$dest = $GLOBALS['fileroot']."/contrib/ndc_dir.zip";
if( !is_dir($dir) )
    @mkdir($dir,0777,1);

if(!copy($GLOBALS['ndc_zip_url'],$dest))
{
    echo " : Error! Please check the NDC globals and permissions on the contrib directory and try again";
    //$error = " : ".$errors['type']." ".print_r(error_get_last());
} else {
    if( !file_exists($dest))
      $error = " : File does not exist";
    else
      if( !chmod($dest,0777)) {
        $error = " : File not changed";
      }else{
        $unzip = 'unzip -jo '.$dest.' -d '.$dir;
        if( !shell_exec($unzip) ) {
          $error = " : Archive could not be unzipped";
        }else{
            if(is_dir($dir)) {
                if($dh = opendir($dir)) {
                    while (false !== ($filename = readdir($dh))) {
                        if ($filename != "." && $filename != ".." && substr($filename,0,5) != "load_" ) {
                            $chmod = $dir.'/'.$filename;
                            @chmod($chmod,0777);
                            $touch = $dir.'/'.$filename;
                            @touch($touch);
                        }
                    }
                    closedir($dh);
                    $unlink = $dir.'/APPLICAT.TXT';
                    if( is_file($unlink) )
                        unlink(trim($unlink));
                    $unlink = $dir.'/FIRMS.TXT';
                    if( is_file($unlink) )
                        unlink(trim($unlink));
                    $unlink = $dir.'/REG_SITES.TXT';
                    if( is_file($unlink) )
                        unlink(trim($unlink));
                    $unlink = $dir.'/SCHEDULE.TXT';
                    if( is_file($unlink) )
                        unlink(trim($unlink));

		    $sql = "SELECT count(*) AS count FROM ndc_code_list";
		    $row = sqlQuery($sql);
		    $count = $row['count'];
		    if( $count < 1 )
                        echo "2";
		    else 
 		        echo "1";
                }else{
                    $error = " : Can't open directory";
                }
            }else{
                $error = " : Can't find directory";
            }

        }
      }
}
if( $error )
    echo $error;
?>
