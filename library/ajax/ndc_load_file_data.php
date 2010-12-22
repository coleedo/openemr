<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This file loads the data from the selected data file
// for the NDC directory
/*******************************************************************/

require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/ndc_capture.inc");
$dir = $GLOBALS['fileroot']."/contrib/ndc_directory";
$dest = $GLOBALS['fileroot']."/contrib/ndc_dir.zip";
$file_select = trim($_REQUEST['file_select']);

if( $file_select != "" ) {
    $file_parts = explode(".",$file_select);
    $key = strtolower($file_parts[0]);
    if( in_array( $key, array_keys($ndc_info)) ) {
	    if( $file_parts[1] != "sql" ) {
		$copy_from = $dir."/".$file_select;
		$copy_to = $dir."/".$ndc_info[$key]['filename'];
		$renamed = copy($copy_from,$copy_to);
		// touch file to refresh load time in emr
		touch($copy_to);
		// check for file errors
		$filename = $ndc_info[$key]['filename'];
		$file = $ndc_info[$key]['dir']."/".$filename;
		if( !file_exists( $file ) )
		    $error = "The file ".strtolower($file_select)." doesn't exist";
		if( !$fp = fopen($file,'r') )
		    $error = "Cannot open file";
	    }
	    // check for data errors
	    $table = $ndc_info[$key]['table'];
	    if( array_search($table,$list_tables) === false ) {
		$created = sqlStatement($ndc_tables[$table]);
		if( !$created )
		    $error = "Table does not exist and could not be created";
	    }
	    // remove old records
	    $purge = "DELETE FROM " . $table;
	    sqlStatement($purge);
    }else{
	$error = "No item was found matching " . strtolower($file_select);
    }

    if( !$error ) {
        switch( $key ) {
          case "schedule": //"schedule.txt":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL:1-7 Linking field to LISTINGS.
                // SCHEDULE NOT NULL NUM(1) COL:9

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','code');
                $fieldlen = array(7,1);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                $dup = " ON DUPLICATE KEY UPDATE code = VALUES(code)";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."')";
                    $i++;
                }
                break;
          case "tblunit":
                // schema copied from fda website
                // UNIT CHAR(15) COL:1-15
                // TRANSLATION CHAR(100) COL:17-115

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('unit_code','translation');
                $fieldlen = array(15,100);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                $dup = " ON DUPLICATE KEY UPDATE translation = VALUES(translation)";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,15)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,16)));
                    $values[$i] = " VALUES ('".$records[0]."','".$records[1]."')";
                    $i++;

                }
                break;
          case "tblroute":
                // schema copied from fda website
                // ROUTE CHAR(3) COL:1-3
                // TRANSLATION CHAR(100) COL:5-104

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('route_code','translation');
                $fieldlen = array(3,100);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                $dup = " ON DUPLICATE KEY UPDATE translation = VALUES(translation)";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,3)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,4)));
                    $values[$i] = " VALUES ('".$records[0]."','".$records[1]."')";
                    $i++;
                }
                break;
          case "tbldosag":
                // schema copied from fda website
                // DOSEFORM CHAR(3) COL:1-3
                // TRANSLATION CHAR(100) COL:5-104

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('dosage_code','translation');
                $fieldlen = array(3,100);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                $dup = " ON DUPLICATE KEY UPDATE translation = VALUES(translation)";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,3)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,4)));
                    $values[$i] = " VALUES ('".$records[0]."','".$records[1]."')";
                    $i++;
                }
                break;
          case "doseform":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL:1-7
                // DOSEFORM NULL CHAR(3) COL:9-11
                // DOSAGE_NAME NULL CHAR(240) COL:13-252

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','code','name');
                $fieldlen = array(7,3,240);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,3)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,12,240)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."')";
                    $i++;
                }
                break;
          case "routes":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL:1-7
                // ROUTE_CODE NULL CHAR(3) COL:9-11
                // ROUTE_NAME NULL CHAR(240) COL:13-252

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','code','name');
                $fieldlen = array(7,3,240);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,3)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,12,240)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."')";
                    $i++;
                }
                break;
          case "firms":
                // schema copied from fda website
                // LBLCODE  NOT NULL NUM(6) COL:1-6
                // FIRM_NAME NOT NULL CHAR(65) COL:8-72
                // ADDR_HEADER NULL CHAR(40) COL:74-113
                // STREET NULL CHAR(40) COL:115-154
                // PO_BOX NULL CHAR(9) COL:156-164
                // FOREIGN_ADDR NULL CHAR(40) COL:166-205
                // CITY NULL CHAR(30) COL:207-236
                // STATE NULL CHAR(2) COL:238-239
                // ZIP NULL CHAR(9) COL:241-249
                // PROVINCE NULL CHAR(30) COL:251-280
                // COUNTRY_NAME NOT NULL CHAR(40) COL:282-321

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('label_code','name','addr_head','address','po_box','addr_foreign','city','state','zip','province','country');
                $fieldlen = array(6,65,40,40,9,40,30,2,9,30,40);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,6)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,7,65)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,73,40)));
                    $records[3] = mysql_real_escape_string(trim(substr($line,114,40)));
                    $records[4] = mysql_real_escape_string(trim(substr($line,155,9)));
                    $records[5] = mysql_real_escape_string(trim(substr($line,165,40)));
                    $records[6] = mysql_real_escape_string(trim(substr($line,206,30)));
                    $records[7] = mysql_real_escape_string(trim(substr($line,237,2)));
                    $records[8] = mysql_real_escape_string(trim(substr($line,240,9)));
                    $records[9] = mysql_real_escape_string(trim(substr($line,250,30)));
                    $records[10] = mysql_real_escape_string(trim(substr($line,281,40)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."','".$records[3]."','".$records[4]."','".$records[5]."','".$records[6]."','".$records[7]."','".$records[8]."','".$records[9]."','".$records[10]."')";
                    $i++;
                }
                break;
          case "applicat":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL:1-7
                // APPL_NO NULL CHAR(6) COL:9-14
                // PROD_NO NULL CHAR(3) COL:16-18

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','app_no','prod_no');
                $fieldlen = array(7,6,3);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,6)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,15,3)));

                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."')";
                    $i++;
                }
                break;
          case "formulat":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL: 1-7
                // STRENGTH NULL CHAR(10) COL: 9-18
                // UNIT NULL CHAR(5) COL: 20-24
                // INGREDIENT_NAME NOT NULL CHAR(100) COL: 26-125

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','strength','unit','ingredient_name');
                $fieldlen = array(7,10,5,100);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,10)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,19,5)));
                    $records[3] = mysql_real_escape_string(trim(substr($line,25,100)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."','".$records[3]."')";
                    $i++;
                }
                break;
          case "packages":
                // schema copied from fda website
                // LISTING_SEQ_NO NOT NULL NUM(7) COL: 1-7
                // PKGCODE NULL CHAR(2) COL: 9-10
                // PACKSIZE NOT NULL CHAR(25) COL: 12-36
                // PACKTYPE NOT NULL CHAR(25) COL: 38-62

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','code','size','type');
                $fieldlen = array(7,2,25,25);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,2)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,11,25)));
                    $records[3] = mysql_real_escape_string(trim(substr($line,37,25)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."','".$records[3]."')";
                    $i++;
                }
                break;
          case "listings":
                // schema copied from fda website
                // LISTING_SEQ_NO   NOT NULL   NUM(7)  COL: 1-7
                // LBLCODE          NOT NULL   CHAR(6) COL: 9-14
                // PRODCODE NOT NULL CHAR(4) COL: 16-19
                // STRENGTH NULL CHAR(10) COL: 21-30
                // UNIT NULL CHAR(10) COL: 32-41
                // RX_OTC NOT NULL CHAR(1) COL: 43
                // TRADENAME NOT NULL CHAR(100) COL: 45-144

                $i = 0;
                $records = array();
                $values = array();
                $fields = array('list_id','label_code','product_code','strength','unit','rx_otc','name');
                $fieldlen = array(7,6,4,10,10,1,100);
                $num_fields = count($fields);
                $insert = "INSERT INTO $table (".implode(",",$fields).") ";
                while ($line = fgets($fp,4096)) {
                    $records[0] = mysql_real_escape_string(trim(substr($line,0,7)));
                    $records[1] = mysql_real_escape_string(trim(substr($line,8,6)));
                    $records[2] = mysql_real_escape_string(trim(substr($line,15,4)));
                    $records[3] = mysql_real_escape_string(trim(substr($line,20,10)));
                    $records[4] = mysql_real_escape_string(trim(substr($line,31,10)));
                    $records[5] = mysql_real_escape_string(trim(substr($line,42,1)));
                    $records[6] = mysql_real_escape_string(trim(substr($line,44,100)));
                    $values[$i] = " VALUES (".$records[0].",'".$records[1]."','".$records[2]."','".$records[3]."','".$records[4]."','".$records[5]."','".$records[6]."')";
                    $i++;
                }
                break;

          case "ndc_code_list":
                // if both > 0, create ndc_code_list table and load it
                $tables[] = $ndc_info['listings']['table'];
                $tables[] = $ndc_info['packages']['table'];
                $ready = true;
                foreach( $tables as $a => $name ) {
                    if( !array_search($name,$list_tables) ) {
                        $ready = false;
                        $error = "Drug List(listings.txt) and Packages(packages.txt) must be loaded to create the NDC numbers data";
                    }
                }

                if( $ready ) {
                    foreach( $tables as $a => $name ) {
                        $query = "SELECT count(list_id) AS count FROM ".$name;
                        $row = sqlQuery($query);
                        if( $row['count'] < 1 ) {
                            $ready = false;
                            $error = "Drug List(listings.txt) and Packages(packages.txt) must be loaded to create the NDC numbers data";
                        }
                    }
                }
                if( $ready ) {
                    // create ndc_codes_list
                    $created = false;
                    $table = 'ndc_code_list';
                    if( array_search($table,$list_tables) === false )
                        $created = sqlStatement($ndc_tables[$table]);
                    else
                        $created = true;
                    if( $created ) {
                        $query = "SELECT list_id, CONCAT_WS('-',label_code,product_code) AS ndc_code FROM ndc_drug_list ORDER BY list_id";
                        $results = sqlStatement($query);
                        $i = 0;
                        $values = array();
                        $insert = "INSERT INTO ndc_code_list (list_id,ndc_number,ndc_code)";
                        while( $row = sqlFetchArray($results) ) {
                            $ndc_number = str_replace("-","",$row['ndc_code']);
                            $ndc_number = str_replace("*","0",$ndc_number);
                            $values[$i] = " VALUES (".$row['list_id'].",'".$ndc_number."','".$row['ndc_code']."')";
                            $i++;
                        }
                    }else{
                        $error = "The ndc codes table could not be created.";
                    }
                }

                break;

          default:
                $error = "No item was found matching " . strtolower($file_select);
                break;
        }

	if( $file_parts[1] != "sql" ) {
	    if(!fclose($fp))
		$errors[] = "Can't close file ".$filename;
	}
        $results = array();
        $results['filename'] = $ndc_info[$key]['title']; //strtolower($file_select);
        $results['tablename'] = $table;
        $results['count'] = count($values);
        //$results['fields'] = implode(", ",$fields);
        foreach( $values as $key => $string ) {
            $sql = $insert . $string . $dup;
            sqlInsert($sql);
        }
    }

    if( $error && $required_load ) {
        echo $error;
    }elseif( $error ) {
        echo "The data could not be loaded. <br>".$error;
    }elseif( $required_load ){
	echo "success";
    }else{
?>
        <table cellspacing='0' align='center'>
         <tr>
           <td width='100' align='right' nowrap><b>Data Loaded:</b></td>
           <td><input type='text' size='40' name='load_att1' id='load_att1' readonly value='<?php echo $results['filename'];?>' style='width:100%' /></td>
         </tr>
         <tr>
           <td width='100' align='right' nowrap><b>Table Loaded:</b></td>
           <td><input type='text' size='40' name='load_att2' id='load_att2' readonly value='<?php echo $results['tablename'];?>' style='width:100%' /></td>
         </tr>
         <tr>
           <td width='100' align='right' nowrap><b># of Records:</b></td>
           <td><input type='text' size='40' name='load_att3' id='load_att3' readonly value='<?php echo $results['count'];?>' style='width:100%' /></td>
         </tr>
        </table>
<?php
	echo $errors[0];
    }
}
?>
