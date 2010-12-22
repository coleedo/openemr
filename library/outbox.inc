<?php
/*******************************************************************/
// Copyright (C) 2010 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
/*******************************************************************/
require_once("{$GLOBALS['srcdir']}/sql.inc");

$out_msg_types = array(
  'ADT',
  'CCD');


// this function can be updated to send to facilities using other methods and message types
function queueMessage($type, $pid = 0, $source_type = '', $source_id = '', $method = 'A08', $status = 1)
{
    $inbox = '';
    if( $source_type != '' && $source_id != '' )
	    $inbox = strtolower($source_type).".".$source_id;
    $recipient = '0';
    $sender = $GLOBALS['phydee'];
    $source = "101";
    $dest = "100";
    //$sender = $GLOBALS['rh_practice_id'];
    $facility = $GLOBALS['rh_practice_id'];
    $env = $GLOBALS['rh_api_env'];
    if( $_SESSION['rh_api_id'] != "" ) 
	    $provider = $_SESSION['rh_api_id'];
    else
    	    $provider = 0;
    $pub = "SELECT pubpid FROM patient_data WHERE pid = $pid";
    $row = sqlQuery($pub);
    $pubpid = $row['pubpid'];
    if( $type == 'CCD' ) $method = 'HL7';

    $query = "INSERT INTO e_outbox (e_type,e_date,e_sender,e_recipient,e_source,e_destination,e_facility,e_env_code,e_method,e_status,e_pid,e_mrn,e_provider_id,e_attending_id,e_inbox) VALUES ('$type', NOW(),'$sender','$recipient','$source','$dest','$facility','$env','$method',$status,$pid,$pubpid,$provider,$provider,'$inbox')";
    return sqlInsert($query); 
}

?>
