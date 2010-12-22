<?php
$ignoreAuth = TRUE;
require_once("../../interface/globals.php");
require_once("../../library/sql.inc");
class BASEAPI 
{

	public         $aMessages        = array();
	public         $sSQL             = '';
	private        $rawResults;
	public $aValidRequests = array
	(
		'sample_request' => array
		(
			'method' => 'sampleRequest',
			'required_parameters' => array('sampleRequiredParam' => 'sRequiredSample'),
			'optional_parameters' => array('sampleOptionalParam' => 'iOptionalSample'),
			'format_parameters' => array('sRequiredSample' => 'string', 'iOptionalSample' => 'integer')
		)
	);

	function __construct() 
	{
		// try to force no cache (yeah, like with all things caching, this doesn't always work..)
		header("Expires: Mon, 1 Jan 1995 12:00:00 GMT");
		header("Cache-Control: no-cache");
		header("Pragma: no-cache");
		if(trim($_REQUEST['a']) == '' || $GLOBALS['phydee_key'] != trim($_REQUEST['a'])) // we did not supply a key or the key supplied is invalid
				die('ERROR:UNAUTHORIZED_KEY'.$_REQUEST['a']);
		if(trim($_REQUEST['p']) == '' || $GLOBALS['phydee'] != trim($_REQUEST['p'])) // we did not supply a key or the key supplied is invalid
				die('ERROR:UNAUTHORIZED_IDENTIFICATION'.$_REQUEST['p']);
		if(trim($_REQUEST['i']) == '' || $_SERVER['REMOTE_ADDR'] != trim($_REQUEST['i'])) // we did not supply a key or the key supplied is invalid
				die('ERROR:UNAUTHORIZED_LOCATION'.$_REQUEST['i']);
		if(trim($_REQUEST['r']) == '' || trim($this->aValidRequests[$_REQUEST['r']]['method']) == '') // we did not supply an action or the one we supplied is not allowed
			die('ERROR:INVALID_REQUEST');
		$aActionArguments = array();
		if(count($this->aValidRequests[$_REQUEST['r']]['required_parameters']) > 0) // make sure we have all required arguments
		{
			foreach($this->aValidRequests[$_REQUEST['r']]['required_parameters'] as $sURLArgument => $sActionArgument)
			{
				$sURLArgumentValue = trim($_REQUEST[$sURLArgument]);
				if($sURLArgumentValue == '')
					die('ERROR:FAILED_REQUIRED_ARGUMENT:"' . $sURLArgument . '"');
				$aActionArguments[$sActionArgument] = $sURLArgumentValue;
			}
		}
		if(count($this->aValidRequests[$_REQUEST['r']]['optional_parameters']) > 0) // lump in any optional arguments
		{
			foreach($this->aValidRequests[$_REQUEST['r']]['optional_parameters'] as $sURLArgument => $sActionArgument)
			{
				$sURLArgumentValue = trim($_REQUEST[$sURLArgument]);
				if($sURLArgumentValue != '')
					$aActionArguments[$sActionArgument] = $sURLArgumentValue;
			}
		}
		$sAction = $this->aValidRequests[$_REQUEST['r']]['method'];
		if(false === $this->validateData($aActionArguments, $this->aValidRequests[$_REQUEST['r']]['format_parameters']))
		{
			$iResultCode = '0';
		}
		else
		{
			if(false === ($mActionResult = $this->$sAction($aActionArguments)))
				$iResultCode = '0';
			else
				$iResultCode = '1';
		}

		if( $_REQUEST['s'] ) // simple response (no headers, only payload 
		{
			$mResult = array( 
				'result_payload' => $mActionResult,
			);
		}else{
			$mResult = array(
				'result_code' => $iResultCode,
				'result_messages' => $this->aMessages,
				'result_sql' => $this->sSQL,
				'result_payload' =>$mActionResult,
			);
		}
		switch($_REQUEST['f']) // process requested encoding
		{
			case 'print_r' :
				echo('<pre>');
				print_r($mResult);
				echo('</pre>');
			break;
			case 'delimited' :
				// loop through $mResult creating a comma delimited row for each array segment
				foreach( $mResult as $segment ) 
				{
					//print_r($segment);
					foreach( $segment as $section => $row )
					{
						//echo "SECTION:".$section;
						//print_r($row);
						echo $section.",";
/*
						foreach( $row as $column => $data )
						{
							$data.","
						}
*/
						if( is_array($row) )
							$columns = implode(",",$row);
						else
							$columns = $row;
						echo $columns."\n";
					}
				}
			break;
			case 'serialized' :
				echo(serialize($mResult));
			break;
			default :
				echo var_dump($mResult);
			break;
		}
	}

	function validateData(&$aInput, $aFormats)
	{
		if(is_array($aFormats))
		{
			foreach($aFormats as $sArgument => $sDataType)
			{
				if($aInput[$sArgument] != '')
				{
					switch($sDataType)
					{
						// ============================================================== //
						// =[ MD5HASH ]================================================== //
						// ============================================================== //
						case 'md5hash' :
							$aInput[$sArgument] = trim($aInput[$sArgument]);
							if(strlen($aInput[$sArgument]) == 32 && false === strpos($aInput[$sArgument], ','))
							{
								//do nothing because this should be a valid md5 hash (though we could use a regex to strip out all non num/char above)
							}
							elseif(strlen($aInput[$sArgument]) >= 32 && false !== strpos($aInput[$sArgument], ','))
							{
								$aTmp = split(',', $aInput[$sArgument]);
								$aTmp2 = array();
								foreach($aTmp as $sGUID)
									if(strlen($sGUID) == 32)
										$aTmp2[] = $sGUID;
									else
										$this->aMessages[] = 'NOTICE:BAD_MD5HASH_IN_LIST:"' . $sGUID . '"';
								if(count($aTmp2) == 0)
								{
									$this->aMessages[] = 'ERROR:BAD_GUID:"' . $aInput[$sArgument] . '"';
									return false;
								}
								$aInput[$sArgument] = implode(',', $aTmp2);
								unset($aTmp);
								unset($aTmp2);
							}
							else
							{
								$this->aMessages[] = 'ERROR:INVALID_MD5HASH:"' . $aInput[$sArgument] . '"';
								return false;
							}
						break;
						// ============================================================== //
						// =[ STRING ]=================================================== //
						// ============================================================== //
						case 'string' :
							$aInput[$sArgument] = trim($aInput[$sArgument]);
						break;
						// ============================================================== //
						// =[ BOOL ]===================================================== //
						// ============================================================== //
						case 'bool' :
							$aInput[$sArgument] = trim($aInput[$sArgument]);
							if($aInput[$sArgument] == '1' || $aInput[$sArgument] == 'true' || $aInput[$sArgument] == 'yes')
								$aInput[$sArgument] = true;
							else
								$aInput[$sArgument] = false;
						break;
						// ============================================================== //
						// =[ DATETIME ]================================================= //
						// ============================================================== //
						case 'datetime' :
							$aInput[$sArgument] = trim($aInput[$sArgument]);
							if(($iTS = strtotime($aInput[$sArgument])) <= 0)
							{
								$this->aMessages[] = 'ERROR:BAD_DATETIME:"' . $aInput[$sArgument] . '"';
								return false;
							}
							$aInput[$sArgument] = date('Y-m-d H:i:s', $iTS);
						break;
						// ============================================================== //
						// =[ INTEGER ]================================================== //
						// ============================================================== //
						case 'integer' :
							$aInput[$sArgument] = trim($aInput[$sArgument]);
							if(is_numeric($aInput[$sArgument]))
								$aInput[$sArgument] = round($aInput[$sArgument]);
							else
							{
								$this->aMessages[] = 'ERROR:BAD_INTEGER:"' . $aInput[$sArgument] . '"';
								return false;
							}
						break;
						// ============================================================== //
						// =[ ARRAY ]================================================= //
						// ============================================================== //
						case 'array' :
							if( !is_array($aInput[$sArgument]) || empty($aInput[$sArgument]) )
							{
								$this->aMessages[] = 'ERROR:BAD_ARRAY:"' . $aInput[$sArgument] . '"';
								return false;
							}
						break;
						// ============================================================== //
						// =[ DEFAULT ]================================================== //
						// ============================================================== //
						default :
							$this->aMessages[] = 'ERROR:INVALID_FILTER_TYPE:"' . $sDataType . '"';
							return false;
						break;
					}
				}
			}
		}
	}

	function buildResultArray($inResult) 
	{
		$this->rawResults = $inResult;
		if(false === $this->rawResults)
		{
			$this->aMessages[] = 'SQL:"' . $sSQL . '"';
			return false;
		}
		while($aResult = mysql_fetch_array($this->rawResults, MYSQL_ASSOC))
		{
			$aResults[] = $aResult; 
		}
		if(count($aResults) == 0)
			$this->aMessages[] = 'NOTICE:NO_RESULTS_FOUND';
		return $aResults;
	}

	function getQueryTotal()
	{
		if($this->rawResults)
			return mysql_num_rows($this->rawResults);
		return 0;
	}

	function sampleRequest($aInput)
	{
		return "this is a test";
		return $aInput;
	}

}
?>
