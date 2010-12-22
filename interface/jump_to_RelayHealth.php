<?php
// Copyright (C) 2008 Phyaura, LLC <info@phyaura.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

include_once('globals.php');
include_once('../library/auth.inc');
include_once('../library/relayhealth.inc.php');
$e = false;
$found = false;

if( $GLOBALS['rh_api'] != "" && $_SESSION['rh_api_id'] != "" && $GLOBALS['rh_api_aaid'] != "" )
    $found = true;

if( $found )
{
    $rhid = trim($_SESSION['rh_api_id']);
    $aaid = trim($GLOBALS['rh_api_aaid']);
    $wsdl = trim($GLOBALS['RHssiwsdl']);
    $location = trim($GLOBALS['RHssilocation']);
    $partner = trim($GLOBALS['RHPartnerName']);
    $appname = trim($GLOBALS['RHApplicationName']);
    $apppass = trim($GLOBALS['RHApplicationPassword']);

    // make soap call to RH
    $client = new SoapClient($wsdl, array(
        'classmap' => $classmap,
        'trace' => 1
    ));

    $rh = new RelayHealthHeader();
    $rh->PartnerName =      $partner;
    $rh->ApplicationName =      $appname;
    $rh->ApplicationPassword =  $apppass;
    $header = new SoapHeader("http://api.relayhealth.com/7.3/SSI", 'RelayHealthHeader', $rh, 1);

    switch( trim($_REQUEST['dest']) )
    {
      case "inbox":
            $call = new ViewInbox();
            $call->partnerUserId = $rhid;
            $params = new SoapVar($call, SOAP_ENC_OBJECT, "ViewInbox", "http://api.relayhealth.com/7.3/SSI");

            try {
                $token = $client->__soapCall("ViewInbox",
                    array('partnerUserId' => $call),
                    array(
                        'location' => $location,
                        'uri' => 'http://api.relayhealth.com/7.3/SSI'
                    ),
                    array($header));
            } catch (Exception $e) {
                //echo "<pre>error $e\n";
            }
            //header("Location: ". $token->Url);
        break;
      case "renewals":
            $call = new ViewRenewals();
            $call->partnerUserId = $rhid;
            $params = new SoapVar($call, SOAP_ENC_OBJECT, "ViewRenewals", "http://api.relayhealth.com/7.3/SSI");

            try {
                $token = $client->__soapCall("ViewRenewals",
                    array('partnerUserId' => $call),
                    array(
                        'location' => $location,
                        'uri' => 'http://api.relayhealth.com/7.3/SSI'
                    ),
                    array($header));
            } catch (Exception $e) {
                //echo "<pre>error $e\n";
            }
            //header("Location: ". $token->Url);
        break;
      case "escripts":

        $pub = sqlStatement("SELECT pubpid FROM patient_data WHERE pid = $pid");
        while ($row = sqlFetchArray($pub)) {
            $pubpid = $row['pubpid'];
        }

            $call = new StartPrescription();
            $call->partnerUserId = $rhid;
            $call->identifierType = 'MRN';
            $call->patientId = $pubpid;
            $call->assigningAuthority = $aaid;
            $params = new SoapVar($call, SOAP_ENC_OBJECT, "StartPrescription", "http://api.relayhealth.com/7.3/SSI");

            try {
                $token = $client->__soapCall("StartPrescription",
                    array('partnerUserId' => $call),
                    array(
                        'location' => $location,
                        'uri' => 'http://api.relayhealth.com/7.3/SSI'
                    ),
                    array($header));
            } catch (Exception $e) {
                //echo "<pre>error $e\n";
            }
            //header("Location: ". $token->Url);
        break;

      case "home":
      default:
            $call = new ViewWelcome();
            $call->partnerUserId = $rhid;
            $params = new SoapVar($call, SOAP_ENC_OBJECT, "ViewWelcome", "http://api.relayhealth.com/7.3/SSI");

            try {
                $token = $client->__soapCall("ViewWelcome",
                    array('partnerUserId' => $call),
                    array(
                        'location' => $location,
                        'uri' => 'http://api.relayhealth.com/7.3/SSI'
                    ),
                    array($header));
            } catch (Exception $e) {
                //echo "<pre>error $e\n";
            }
            //header("Location: ". $token->Url);
        break;

    }
}

if( $found && !$e )
    header("Location: ". $token->Url);
else
    header("Location: https://app.relayhealth.com");
?>
