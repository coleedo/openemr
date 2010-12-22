<?php
include_once("base_api.class.php");
class PHYAPI extends BASEAPI
{
	public $issues = array('0' => 'problem', '1' => 'allergy', '2' => 'medication');
	public $msg_to_issue_types = array
	( 
		'ADT' => array('1'), 
		'CCD' => array('1','2') 
	);
	public $msg_type_data_map = array
	(
		'ADT' => array('user' => 1, 'patient' => 1, 'provider' => 1, 'issue' => '1'), 
		'CCD' => array('user' => 1, 'patient' => 1, 'provider' => 1, 'issue' => '1'), 
		'RDE' => array('user' => 1, 'patient' => 1, 'provider' => 1, 'issue' => 'none'), 
		'MDM' => array('user' => 1, 'patient' => 1, 'provider' => 1, 'issue' => 'none')
	); 
	public $aValidHL7 = array
	(
		'RDE' => array('msg_id' => 'integer', 'msg_type' => 'string', 'msg_date' => 'datetime', 'msg_recipient' => 'string', 'msg_sender' => 'string', 'msg_source' => 'string', 'msg_facility' => 'string')  
	);

	public $aValidRequests = array
	(
		'request_map' => array('method' => 'requestMap',),
		'get_users' => array
		(
			'method' => 'getUserList',  
			'required_parameters' => array('p' => 'iPhyId'),
			'format_parameters' => array('iPhyId' => 'integer'),
		),
		'get_response' => array
		(
			'method' => 'getClientResponse', 
			'required_parameters' => array('p' => 'iPhyId'),
			'format_parameters' => array('iPhyId' => 'integer'),
		),
		'get_outbox' => array
		(
			'method' => 'retrieveOutboundMessage', 
			'required_parameters' => array('p' => 'iPhyId'),
			'format_parameters' => array('iPhyId' => 'integer'),
		),
		'deliver_inbox' => array
		(
			'method' => 'deliverInboundMessage',
			'required_parameters' => array('p' => 'iPhyId', 'b' => 'aMessage'),
			'optional_parameters' => array('issue' => 'aIssue', 'pnote' => 'aPnote'),
			'format_parameters' => array('iPhyId' => 'integer', 'aMessage' => 'string'),
		),
	);

	function requestMap($aInput)
	{
		return $this->aValidRequests;
	}
    
    // returns array user list to the server for user sync, requests new user id's
    function getUserList($aInput)
    {
	// return list of users with ssi id's (id,name,username,ssi,email,specialty,facility)    
	$users = array();
	$query = "SELECT id, username, lname, fname, email, ssi_relayhealth FROM users WHERE ssi_relayhealth != 0";
	$results = sqlStatement($query);
        while($row = mysql_fetch_array($results,MYSQL_ASSOC)) {
            $users[] = $row;
        }
	$this->sSQL[] = $query;
	return $users;
    }

    // test the client/server connection, returns array
    function getClientResponse($aInput)
    {
        $verify = $GLOBALS['RHApplicationName']."_".$GLOBALS['phydee'];
        $phy = array('verify' => $verify );
        return $phy;
    }

    // read the outbox for queued messages, call the appropriate methods
    // to retrieve the required data and formats for return array
    // adt only gets created for demographics update or for allergy (diagnosis?) update
    function retrieveOutboundMessage()
    {
	$msg_type = "";
	$issue_type = "";
        $message = array();
	$user = array();
	$patient = array();
	$provider = array();
	$issue = array();
        $outbox = array();
	$queue = $this->countQueuedOutbox();

	if( $queue > 0 ) 
	{

		// ex: lists.216
		$query = "SELECT e_id, e_type, e_mtid, e_date, e_sender, e_source, e_destination, e_facility, e_env_code, e_method, e_provider_id, e_pid, e_mrn, e_inbox FROM e_outbox WHERE e_status = 1 limit 1";
		$this->sSQL[] = $query;
		$result = sqlStatement($query);
		$message = mysql_fetch_array($result,MYSQL_ASSOC);

		$data_map = $this->msg_type_data_map[trim($message['e_type'])]; 
		if( $data_map['user'] ) 
			$user = $this->getUserData($message['e_sender']);
		if( $data_map['patient'] ) 
			$patient = $this->getPatientData($message['e_pid']);
		if( $data_map['provider'] ) 
			$provider = $this->getProviderData($message['e_provider_id']);
		if( $data_map['issue'] != "none" ) 
                {
                        $spring = explode(".",$message['e_inbox']);
                        $table = $spring[0];
                        $id = $spring[1];
                        if( $table == 'lists' && $id != "" )
                                $issue = $this->getIssueData($id,$message['e_type']);
                }


	}
	$outbox = array('message' => $message, 'patient' => $patient, 'provider' => $provider, 'user' => $user, 'issue' => $issue, 'queue' => $queue); 
	return $outbox;
    }

    function getUserData($userid)
    {
        $query = "SELECT id, username, lname, fname, email, ssi_relayhealth AS ssi FROM users WHERE id = ".$userid;
	$this->sSQL[] = $query;
        $data = sqlQuery($query);
	return $data;
    }
        
    function getUserDataBySSI($ssi)
    {
        $query = "SELECT id, username, lname, fname, email, ssi_relayhealth AS ssi FROM users WHERE ssi_relayhealth = ".$ssi;
	$this->sSQL[] = $query;
        $data = sqlQuery($query);
	return $data;
    }
        
    function getPatientData($id, $id_type = 'PID')
    {
	$patient = array();
	if( $id_type == 'PID' ) 
		$query = "SELECT pid, pubpid as mrn, lname, fname, mname, DOB as dob, street, postal_code as zip, city, state, country_code, phone_home as phone, sex, email FROM patient_data WHERE pid = ".$id;
	else
		$query = "SELECT pid, pubpid as mrn, lname, fname, mname, DOB as dob, street, postal_code as zip, city, state, country_code, phone_home as phone, sex, email FROM patient_data WHERE pubpid = ".$id;
        $result = sqlStatement($query);
	$patient = mysql_fetch_array($result,MYSQL_ASSOC);
	$state = trim($patient['state']);
	$patient['state'] = substr($state,0,2);
	$zip = trim($patient['zip']);
	$patient['zip'] = substr($zip,0,5);
	$sex = trim($patient['sex']);
	$patient['sex'] = substr($sex,0,1);
	$this->sSQL[] = $query;
	return $patient;
    }

    function getProviderIdBySSI($ssi)
    {
        // get provider id
        $query = "SELECT id FROM users WHERE ssi_relayhealth = $ssi";
	$this->sSQL[] = $query;
        $result = sqlQuery($query);
	return $result['id'];
    }

    function getProviderData($id)
    {
        // get provider info by userid 
        $query = "SELECT id, lname, fname, ssi_relayhealth AS ssi FROM users WHERE ssi_relayhealth = $id";
	$this->sSQL[] = $query;
        $result = sqlQuery($query);
	return $result;
    }

    function getProviderDataBySSI($ssi)
    {
        // get provider info by ssi 
        $query = "SELECT id, lname, fname, ssi_relayhealth AS ssi FROM users WHERE ssi_relayhealth = $ssi";
	$this->sSQL[] = $query;
        $result = sqlQuery($query);
	return $result;
    }

    // issue data for both ADT and CCD
    function getIssueData($source, $msg_type)
    {        
	$issue = array();
	$spring = explode(".",$source);
	$table = $spring[0];
	$id = $spring[1];
        $query = "SELECT type, title, diagnosis, comments, reaction, drug_id, ingredient FROM lists WHERE id = ".$id." AND (type = 'allergy' OR diagnosis != '') GROUP BY type, title, diagnosis";
	$this->sSQL[] = $query;
        $result = sqlStatement($query);
	$row = mysql_fetch_array($result,MYSQL_ASSOC);

        $list_type = trim($row['type']);
        $comments = "";
        $ndc_code = "";
        $reaction = "";
        switch( $list_type )
        {
            case "allergy":
                $title = trim($row['title']);
                $diagnosis = trim($row['diagnosis']);
                $comments = trim($row['comments']);
                $ndc_code = trim($row['drug_id']);
                $reaction = trim($row['reaction']);
            break;
            case "medication":
                $title = trim($row['title']);
                $diagnosis = trim($row['diagnosis']);
                $comments = trim($row['comments']);
                $ndc_code = trim($row['drug_id']);
            break;
        }

	$issue['type'] = $list_type;
	$issue['title'] = $title;
	$issue['comments'] = $comments;
	$issue['drug_id'] = $ndc_code;
	$issue['reaction'] = $ndc_reaction;
	$issue['diagnosis'] = $diagnosis;

	return $issue;
    }

    function getPatientId($mrn)
    {
	// get pid from ext id
	$query = "SELECT pid FROM patient_data WHERE pubpid = ".$mrn;
	$this->sSQL[] = $query;
        $result = sqlStatement($query);
	$patient = mysql_fetch_array($result,MYSQL_ASSOC);
	return $patient['pid'];
    }

    function findPatient($params)
    {
	$patient = array();

	// format search params
	$fname = trim(strtolower($params[3]));
	$lname = trim(strtolower($params[4]));
	$dob = trim(strtolower($params[5]));
	$dob = date('yyyy-MM-dd', strtotime($dob));
	$zip = trim(substr($params[7],0,5));
	$sex = trim(strtolower($params[6]));

        //$query = "SELECT pid, pubpid as mrn, LOWER(lname), LOWER(fname), DOB as dob, LOWER(street), postal_code as zip, LOWER(city), LOWER(state), LOWER(LEFT(sex,1)), email FROM patient_data WHERE sex LIKE '".$sex."%' AND DOB = '".$dob."' AND postal_code = '".$zip."' AND lname LIKE '%".$lname."%' AND fname LIKE '%".$fname."%'";
	$this->sSQL[] = $query;
        $result = sqlStatement($query);
	$patient = mysql_fetch_array($result,MYSQL_ASSOC);
	$count = mysql_num_rows($result);
	if( $count > 1 )
		return false;
	else
		return $patient[pid];	
    }

    // creates an inbox record for incoming messages, call the appropriate methods

    function deliverInboundMessage($aInput)
    {
	$pid = 0;
	$mrn = 0;
	$parts = array();
        $data = explode("infotype,",$aInput['aMessage']);
	foreach( $data as $k => $row ) 
	{
		if( $row != "" ) {
			$parts = explode(",",$row);
			switch( trim($parts[0]) )
			{
			    case "inbox":
				$inboxRow = $row;
				break;
			    case "patient": // 2 
				$patientRow = $row;
				break;
			    case "note": // 3
				$pnoteRow = $row;
				break;
			    case "issue":
				$issueRow = $row;
				break;
			}
		}
	}

	$inbox = explode(",",$inboxRow);
	$pnote = explode(",",$pnoteRow);
	$issue = explode(",",$issueRow);
	$inpatient = explode(",",$patientRow);

	if( strstr($inpatient[1],"PI") ) 
	{
		$get_id = explode("~",$inpatient[1]);
		$mrn = $get_id[1];
		$patient = $this->getPatientData($mrn,'MRN');
		$pid = $patient['pid'];
	}else{
		$pid = $this->findPatient($patientRow);
		$patient = $this->getPatientData($pid,'PID');
		$mrn = $patient['mrn'];
	}
	
	// depending on the type of message, call the methods to insert the required data
	// this can be extended for attachments, documents, etc.
	$msg_date = strtotime($inbox[4]);
	if( !date($msg_date) ) 
	{
		$msg_date = date("Y-m-d H:i:s");
	}else{
		$msg_date = date("Y-m-d H:i:s",$msg_date);
	}
	$inbox[4] = $msg_date;

        $query = "INSERT INTO e_inbox (e_extid, e_mtid, e_type, e_date, e_recipientid, e_senderid, e_source, e_destination, e_facility, e_env_code, e_method, e_status, e_pid, e_mrn, e_pv1, e_attending_id, e_referring_id, e_inbox) values (" . $inbox[1] . ", 1, '" . $inbox[3] . "', '" . $inbox[4] . "', " . $inbox[5] . ", " . $inbox[6] . ", '" . $inbox[7] . "', '" . $inbox[8] . "', '" . $inbox[9] . "', '" . $inbox[10] . "', '" . $inbox[11] . "', '" . $inbox[12] . "', " . $pid . ", '" . $mrn . "', '" . $inbox[14] . "', '" . $inbox[15] . "', '" . $inbox[16] . "', '" . $inbox[17] . "')";

        $msg_insert_id = sqlInsert($query);
	$this->sSQL[] = $query;

	$table = "";

    // need to add get provider
	$provider_ssi = $inbox[15];
	$user = $this->getUserDataBySSI($provider_ssi);

	$userid = $user['id'];
	$username = $user['username'];
	$provider_name = $inbox[14];
	$provider = explode(" ",$provider_name);
	$provider_fname = $provider[0]; 
	$provider_lname = $provider[1]; 
	$provider_short = strtoupper(substr($provider_fname,0,1)).".".ucfirst($provider_lname);

	switch( $inbox[3] )
	{
	    case "RDE":
		$table = "lists";
		if( is_array($issue) && !empty($issue) )
			$issue[5] = $provider_ssi;
			if( $pid != "" ) 
				$issue[9] = $pid;
			( $username != "" ) ? $issue[10] = $username : $issue[10] = $provider_short;
			$newId = $this->insertIssueData($issue);
		break;
	    case "MDM":
		$table = "pnotes";
		$patient_short = strtoupper(substr($patient['fname'],0,1)).".".ucfirst($patient['lname']);
		if( $username != "" )
		{
			$pnote_to_from = "(".$patient_short." to ".$username.")";
		}else{
			$pnote_to_from = $patient_short; 
		}	
		if( is_array($pnote) && !empty($pnote) )
		{
			$pnote[2] = str_replace("pnote_to_from",$pnote_to_from,$pnote[2]);
			if( $pid != "" ) 
				$pnote[3] = $pid;
			( $username != "" ) ? $pnote[4] = $username : $pnote[4] = $provider_short;
			( $username != "" ) ? $pnote[7] = $username : $pnote[7] = $provider_short;
			$newId = $this->insertPnoteData($pid, $pnote);
		}
		break;
	}

	$dest = $table.".".$newId;

	$sql = "UPDATE e_inbox SET e_inbox = '$dest' AND e_status = 0 WHERE e_id = ".$msg_insert_id;  
	$this->sSQL[] = $sql;
        $return = sqlStatement($sql);
	return $return;
    }

    function insertPnoteData($pid, $pnote) 
    {
	$date = date("Y-m-d H:i:s",strtotime($pnote[1]));
	$pnote[1] = $date;
	$note = $date . " " . $pnote[2];
	$pnote[2] = $note;
        $query = "INSERT INTO pnotes (
		date, 
		body, 
		pid, 
		user, 
		activity, 
		title, 
		assigned_to, 
		deleted
	) values (
		'" . $pnote[1] . "', 
		'" . $pnote[2] . "', 
		" . $pnote[3] . ", 
		'" . $pnote[4] . "', 
		" . $pnote[5] . ", 
		'" . $pnote[6] . "', 
		'" . $pnote[7] . "', 
		" . $pnote[8] . " 
	)";
	$this->sSQL[] = $query;
        $insert_id = sqlInsert($query);
	return $insert_id;
    }

    function insertIssueData($issue)
    {
	$date = date("Y-m-d H:i:s",strtotime($issue[1]));
	$start = date("Y-m-d",strtotime($issue[4]));
	$issue[1] = $date;
	$issue[4] = $start;
        $query = "INSERT INTO lists (date, type, title, begdate, referredby, extrainfo, activity, comments, pid, user 
	) values (
		'" . $issue[1] . "', '" . $issue[2] . "', '" . $issue[3] . "', '" . $issue[4] . "', '" . $issue[5] . "', '" . $issue[6] . "', " . $issue[7] . ", '" . $issue[8] . "', " . $issue[9] . ", '" . $issue[10] . "' 
	)";

	$this->sSQL[] = $query;
        $insert_id = sqlInsert($query);
	return $insert_id;
    }

    function countQueuedOutbox()
    {
        $query = "SELECT count(e_id) AS queue FROM e_outbox WHERE e_status = 1";
	$this->sSQL[] = $query;
        $row = sqlQuery($query);
	return $row['queue'];
    }

    // format data.. provide data, initial data type, destination data type 
    function formatData( $data, $type, $return )
    {
        switch( $return ) {
                case "integer":
                     $result = $data+0;
                     break;
        }
        return $result;
    }
}
?>
