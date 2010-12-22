<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This will get form/dosage, formulation/ingredient, route,
// strength/unit, interval for ndc
/*******************************************************************/

include_once("../../interface/globals.php");
include_once("{$GLOBALS['srcdir']}/sql.inc");

if(!$drug || !$option) return;
$drug = mysql_real_escape_string($drug);

switch( $option ) {
  case "form":
    $sql="SELECT name FROM ndc_list_dosage WHERE list_id = ".$drug." ORDER BY name";
    $result = sqlStatement($sql);
    if( count($result) > 1 )
        echo "<option value=''> </option>\n";
    while( $row = sqlFetchArray($result) ) {
        echo "<option value='".$row['name']."'>".ucwords(strtolower($row['name']))."</option>\n";
    }
    break;
  case "route":
    $sql="SELECT name FROM ndc_list_route WHERE list_id = ".$drug." ORDER BY name";
    $result = sqlStatement($sql);
    if( count($result) > 1 )
        echo "<option value=''> </option>\n";
    while( $row = sqlFetchArray($result) ) {
        echo "<option value='".$row['name']."'>".ucwords(strtolower($row['name']))."</option>\n";
    }
    break;
  case "formulation":
    $sql="SELECT ingredient_name AS name FROM ndc_formulations WHERE list_id = ".$drug." ORDER BY ingredient_name";
    $result = sqlStatement($sql);
    echo "<option value=''> </option>\n";
    while( $row = sqlFetchArray($result) ) {
        echo "<option value='".$row['name']."'>".ucwords(strtolower($row['name']))."</option>\n";
    }
    break;
  case "strength":
    $sql="SELECT CONCAT_WS(' ',strength,unit) AS name FROM ndc_drug_list WHERE name = '".$drug."' GROUP BY strength, unit ORDER BY strength, unit";
    $result = sqlStatement($sql);
    if( count($result) > 1 )
        echo "<option value=''> </option>\n";
    while( $row = sqlFetchArray($result) ) {
        echo "<option value='".$row['name']."'>".ucwords(strtolower($row['name']))."</option>\n";
    }
    break;
  case "interval":
    $sql="SELECT title FROM list_options WHERE list_id = 'drug_interval' ORDER BY seq";
    $result = sqlStatement($sql);
    if( count($result) > 1 )
        echo "<option value=''> </option>\n";
    while( $row = sqlFetchArray($result) ) {
        echo "<option value='".$row['title']."'>".$row['title']."</option>\n";
    }
    break;
}
?>