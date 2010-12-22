<?php
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

include_once('../globals.php');
include_once('../../library/auth.inc');
include_once('../../library/relayhealth.inc.php');

if( $GLOBALS['rh_api'] ) {

    $rhid = trim($_SESSION['rh_api_id']);
    $wsdl = trim($GLOBALS['RHmsgwsdl']);
    $location = trim($GLOBALS['RHmsglocation']);
    $partner = trim($GLOBALS['RHPartnerName']);
    $appname = trim($GLOBALS['RHApplicationName']);
    $apppass = trim($GLOBALS['RHApplicationPassword']);

    $client = new SoapClient($wsdl, array(
    'classmap' => $classmap,
    'trace' => 1
    ));

    $rh = new RelayHealthHeader();
    $rh->PartnerName =      $partner;
    $rh->ApplicationName =      $appname;
    $rh->ApplicationPassword =  $apppass;
    $header = new SoapHeader("http://api.relayhealth.com/7.4/RelayHealthMessage", 'RelayHealthHeader', $rh, 1);

    $users = array($rhid);
    $call = new CountMessages();
    $call->partnerUserId = $rhid;
    $call->UserIds = $users;
    $params = new SoapVar($call, SOAP_ENC_OBJECT, "CountMessages", "http://api.relayhealth.com/7.4/RelayHealthMessage");

    try {
        $token = $client->__soapCall("CountMessages",
            array('partnerUserId' => $call),
            array(
                'location' => $location,
                'uri' => 'http://api.relayhealth.com/7.4/RelayHealthMessage'
            ),
            array($header));
    } catch (Exception $e) {
        //echo "<!-- ".$e." -->";

    }

    if($token->MessageCount->MessageCount->Unread)
        print('<a href="../jump_to_RelayHealth.php?dest=inbox" target="_new" class="rhesuites" id="rh_link">e-messages('.$token->MessageCount->MessageCount->Unread.')</a>');
    else
        print('<a href="../jump_to_RelayHealth.php?dest=inbox" target="_new" class="rhesuites" id="rh_link">e-messages(0)</a>');
}
?>
