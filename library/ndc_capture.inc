<?php
/*******************************************************************/
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
// This file is included in the ndc file/data processing scripts
/*******************************************************************/

$dir = $GLOBALS['fileroot']."/contrib/ndc_directory";

$ndc_info = array();
$ndc_info['schedule'] = array('title' => "Schedule", 'dir' => "$dir", 'origin' => "SCHEDULE.TXT", 'filename' => "load_schedule.txt", 'table' => "ndc_schedule", 'required' => 0);
$ndc_info['tblunit'] = array('title' => "Unit Codes", 'dir' => "$dir", 'origin' => "TBLUNIT.TXT", 'filename' => "load_tblunit.txt", 'table' => "ndc_unit",	'required' => 1);
$ndc_info['tbldosag'] = array('title' => "Dosage Codes", 'dir' => "$dir", 'origin' => "TBLDOSAG.TXT", 'filename' => "load_tbldosag.txt", 'table' => "ndc_dosage", 'required' => 1);
$ndc_info['tblroute'] = array('title' => "Route Codes", 'dir' => "$dir", 'origin' => "TBLROUTE.TXT", 'filename' => "load_tblroute.txt", 'table' => "ndc_route", 'required' => 1);
$ndc_info['routes'] = array('title' => "Routes of Administration", 'dir' => "$dir", 'origin' => "ROUTES.TXT", 'filename' => "load_routes.txt", 'table' => "ndc_list_route", 'required' => 1);
$ndc_info['doseform'] = array('title' => "Doseforms", 'dir' => "$dir", 'origin' => "DOSEFORM.TXT", 'filename' => "load_doseform.txt", 'table' => "ndc_list_dosage", 'required' => 1);
$ndc_info['firms'] = array('title' => "Firm Names", 'dir' => "$dir", 'origin' => "FIRMS.TXT", 'filename' => "load_firms.txt", 'table' => "ndc_firms", 'required' => 0);
$ndc_info['applicat'] = array('title' => "New Drug Applications", 'dir' => "$dir", 'origin' => "APPLICAT.TXT", 'filename' => "load_applicat.txt", 'table' => "ndc_new_app", 'required' => 0);
$ndc_info['formulat'] = array('title' => "Formulations", 'dir' => "$dir", 'origin' => "FORMULAT.TXT", 'filename' => "load_formulat.txt", 'table' => "ndc_formulations", 'required' => 1);
$ndc_info['packages'] = array('title' => "Packages", 'dir' => "$dir", 'origin' => "PACKAGES.TXT", 'filename' => "load_packages.txt", 'table' => "ndc_packages", 'required' => 1);
$ndc_info['listings'] = array('title' => "Drug List", 'dir' => "$dir", 'origin' => "LISTINGS.TXT", 'filename' => "load_listings.txt", 'table' => "ndc_drug_list", 'required' => 1);
$ndc_info['ndc_code_list'] = array('title' => "Code List", 'dir' => "", 'filename' => "", 'table' => "ndc_code_list", 'required' => 1);

$ndc_code_list = "CREATE TABLE if NOT EXISTS `ndc_code_list` (
`list_id` int(7) NOT NULL,
`ndc_number` int(11) NOT NULL,
`ndc_code` char(14) NULL,
KEY (list_id),
KEY (ndc_number),
KEY (ndc_code)
) TYPE=MyISAM;";

$ndc_drug_list = "CREATE TABLE if NOT EXISTS `ndc_drug_list` (
`list_id` int(7) NOT NULL,
`label_code` char(6) NOT NULL,
`product_code` char(4) NOT NULL,
`strength` char(10) NULL,
`unit` char(10) NULL,
`rx_otc` char(1) NOT NULL,
`name` char(100) NOT NULL,
PRIMARY KEY (list_id)
) TYPE=MyISAM;";

$ndc_packages = "CREATE TABLE IF NOT EXISTS `ndc_packages` (
`pkg_id` int(8) NOT NULL auto_increment,
`list_id` int(8) NOT NULL,
`code` char(2) NULL,
`size` char(25) NOT NULL,
`type` char(25) NOT NULL,
PRIMARY KEY (pkg_id),
KEY (list_id)
) TYPE=MyISAM;";

$ndc_formulations = "CREATE TABLE IF NOT EXISTS `ndc_formulations` (
`form_id` int(8) NOT NULL auto_increment,
`list_id` int(8) NOT NULL,
`strength` char(10) NULL,
`unit` char(5) NULL,
`ingredient_name` char(100) NOT NULL,
PRIMARY KEY (form_id),
KEY (list_id)
) TYPE=MyISAM;";

$ndc_new_app = "CREATE TABLE IF NOT EXISTS `ndc_new_app` (
`new_id` int(8) NOT NULL auto_increment,
`list_id` int(8) NOT NULL,
`app_no` char(6) NULL,
`prod_no` char(3) NULL,
PRIMARY KEY (new_id),
KEY (list_id)
) TYPE=MyISAM;";

$ndc_firms = "CREATE TABLE IF NOT EXISTS `ndc_firms` (
`firm_id` int(8) NOT NULL auto_increment,
`label_code` char(6) NOT NULL,
`name` char(65) NOT NULL,
`addr_head` char(40) NULL,
`address` char(40) NULL,
`po_box` char(9) NULL,
`addr_foreign` char(40) NULL,
`city` char(30) NULL,
`state` char(2) NULL,
`zip` char(9) NULL,
`province` char(30) NULL,
`country` char(40) NOT NULL,
PRIMARY KEY (firm_id),
KEY (label_code)
) TYPE=MyISAM;";

$ndc_list_dosage = "CREATE TABLE IF NOT EXISTS `ndc_list_dosage` (
`dosage_id` int(8) NOT NULL auto_increment,
`list_id` int(8) NOT NULL,
`code` char(3) NOT NULL,
`name` char(240) NULL,
PRIMARY KEY (dosage_id),
KEY (list_id)
) TYPE=MyISAM;";

$ndc_list_route = "CREATE TABLE IF NOT EXISTS `ndc_list_route` (
`route_id` int(8) NOT NULL auto_increment,
`list_id` int(8) NOT NULL,
`code` char(3) NOT NULL,
`name` char(240) NULL,
PRIMARY KEY (route_id),
KEY (list_id)
) TYPE=MyISAM;";

$ndc_dosage = "CREATE TABLE IF NOT EXISTS `ndc_dosage` (
`dosage_code` char(3) NOT NULL,
`translation` char(100) NULL,
PRIMARY KEY (dosage_code)
) TYPE=MyISAM;";

$ndc_route = "CREATE TABLE IF NOT EXISTS `ndc_route` (
`route_code` char(3) NOT NULL,
`translation` char(100) NULL,
PRIMARY KEY (route_code)
) TYPE=MyISAM;";

$ndc_unit = "CREATE TABLE if NOT EXISTS `ndc_unit` (
`unit_code` char(15) NOT NULL,
`translation` char(100) NULL,
PRIMARY KEY (unit_code)
) TYPE=MyISAM;";

$ndc_schedule = "CREATE TABLE IF NOT EXISTS `ndc_schedule` (
`list_id` int(8) NOT NULL,
`code` int(1) NOT NULL,
PRIMARY KEY (list_id)
) TYPE=MyISAM;";

$ndc_tables = array('ndc_code_list' => $ndc_code_list,'ndc_drug_list' => $ndc_drug_list,'ndc_packages' => $ndc_packages,'ndc_formulations' => $ndc_formulations,'ndc_new_app' => $ndc_new_app,'ndc_firms' => $ndc_firms,'ndc_list_dosage' => $ndc_list_dosage,'ndc_list_route' => $ndc_list_route,'ndc_dosage' => $ndc_dosage,'ndc_route' => $ndc_route,'ndc_unit' => $ndc_unit,'ndc_schedule' => $ndc_schedule);

// get ndc table list
$list_tables = array();
$result = sqlStatement("SHOW TABLES LIKE 'ndc_%'");
while($row=mysql_fetch_row($result)) {
    //echo "Table: {$row[0]}<br>\n";
    $list_tables[] = $row[0];
}

$load_exists = false;
$ndc_files = array();
if( is_dir($dir) && $handle = opendir($dir)) {
    while (false !== ($filename = readdir($handle))) {
        if( substr($filename,0,5) == "load_" ) {
            $load_exists = true;
        }elseif( $filename != "." && $filename != ".." )
        {
           $lfilename = strtolower($filename);
           $check = explode(".",$lfilename);
           if( in_array($check[0],array_keys($ndc_info)) )
           $ndc_files[$filename] = $ndc_info[$check[0]]['title'];
        }
    }
    closedir($handle);
   //$ndc_files['ndc_code_list'] = $ndc_info['ndc_code_list']['title'];
}
?>
