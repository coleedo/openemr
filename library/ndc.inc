<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This is the include for searching or grabbing ndc information
/*******************************************************************/

require_once("{$GLOBALS['srcdir']}/sql.inc");

function ndc_get_list_id( $find )  //get_drug_list_id
{
    $sql="SELECT list_id, name FROM ndc_drug_list";
    $find = mysql_real_escape_string($find);
    $sql.=" WHERE name LIKE '%$find%'";
    $row = sqlQuery($sql);
    $id = $row['list_id'];

    return $id;
}

function ndc_search_by_name( $find )       // get_list
{
   $sql="SELECT d.list_id, d.name, d.strength, d.unit, d.rx_otc," .
            " CONCAT_WS('-',d.label_code,d.product_code,p.code) AS ndc_number" .
            " FROM ndc_drug_list AS d LEFT JOIN ndc_packages AS p ON p.list_id = d.list_id";
    if( $find != "" ) {
        $find = mysql_real_escape_string($find);
        $sql.=" WHERE name LIKE '%$find%'";
    }
    $sql.=" ORDER BY name";
    $results = sqlStatement($sql);

    return $results;

}

function ndc_get_ndc( $drug_id, $drug = '' )    // get_ndc_code
{
   $sql = "SELECT CONCAT_WS('-',label_code,product_code) AS ndc_code";
   $sql .= " FROM ndc_drug_list";
    if( $drug_id == 0 && $drug != '' ) {
        $find = mysql_real_escape_string($drug);
        $sql.=" WHERE name LIKE '%$find%'";
    }else{
        $sql .= " WHERE list_id = ".$drug;
    }
    $results = sqlQuery($sql);

    return $results;
}
?>
