<?php
// Copyright (C) 2005-2010 Rod Roark <rod@sunsetsystems.com>
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

require_once("../../globals.php");
require_once("$srcdir/lists.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/acl.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/../custom/code_types.inc.php");
require_once("$srcdir/csv_like_join.php");
if( $GLOBALS['ndc_enable_directory'] ) {
    require_once("$srcdir/ndc.inc");
}


if ($ISSUE_TYPES['football_injury']) {
  // Most of the logic for the "football injury" issue type comes from this
  // included script.  We might eventually refine this approach to support
  // a plug-in architecture for custom issue types.
  require_once("$srcdir/football_injury.inc.php");
}
if ($ISSUE_TYPES['ippf_gcac']) {
  // Similarly for IPPF issues.
  require_once("$srcdir/ippf_issues.inc.php");
}

$diagnosis_types = array();
foreach ($code_types as $code => $data) {
	if ($data['diag']) {
		array_push($diagnosis_types, $code);
	}
}
if (count($diagnosis_types) < 1) {
	$diagnosis_type = 'ICD9';
} else {
	$diagnosis_type = csv_like_join($diagnosis_types);
}

$issue = $_REQUEST['issue'];
$thispid = 0 + (empty($_REQUEST['thispid']) ? $pid : $_REQUEST['thispid']);
$info_msg = "";

// A nonempty thisenc means we are to link the issue to the encounter.
$thisenc = 0 + (empty($_REQUEST['thisenc']) ? 0 : $_REQUEST['thisenc']);

// A nonempty thistype is an issue type to be forced for a new issue.
$thistype = empty($_REQUEST['thistype']) ? '' : $_REQUEST['thistype'];

$thisauth = acl_check('patients', 'med');
if ($issue && $thisauth != 'write') die("Edit is not authorized!");
if ($thisauth != 'write' && $thisauth != 'addonly') die("Add is not authorized!");

$tmp = getPatientData($thispid, "squad, DATE_FORMAT(DOB,'%m/%d/%Y') as pdob, CONCAT_WS(', ',lname, fname) AS pname");
$patient_info = $tmp['pname']. " " .$tmp['pdob'];
if ($tmp['squad'] && ! acl_check('squads', $tmp['squad']))
  die("Not authorized for this squad!");

function QuotedOrNull($fld) {
  if ($fld) return "'$fld'";
  return "NULL";
}

function rbvalue($rbname) {
  $tmp = $_POST[$rbname];
  if (! $tmp) $tmp = '0';
  return "'$tmp'";
}

function cbvalue($cbname) {
  return $_POST[$cbname] ? '1' : '0';
}

function invalue($inname) {
  return (int) trim($_POST[$inname]);
}

function txvalue($txname) {
  return "'" . trim($_POST[$txname]) . "'";
}

function rbinput($name, $value, $desc, $colname) {
  global $irow;
  $ret  = "<input type='radio' name='$name' value='$value'";
  if ($irow[$colname] == $value) $ret .= " checked";
  $ret .= " />$desc";
  return $ret;
}

function rbcell($name, $value, $desc, $colname) {
 return "<td width='25%' nowrap>" . rbinput($name, $value, $desc, $colname) . "</td>\n";
}

// Given an issue type as a string, compute its index.
function issueTypeIndex($tstr) {
  global $ISSUE_TYPES;
  $i = 0;
  foreach ($ISSUE_TYPES as $key => $value) {
    if ($key == $tstr) break;
    ++$i;
  }
  return $i;
}
// If we are saving, then save and close the window.
//
if ($_POST['form_save']) 
{

$form_drug_id = "";
$form_ingredient = "";
// set text_type to the string that should go in the database
$i = 0;
$text_type = "unknown";
if( $_POST['original_type'] == 5 ) {
    $text_type = 'escript';
}else{
    foreach ($ISSUE_TYPES as $key => $value) {
      if ($i == intval($_POST['form_type']) ) {
        $text_type = $key;
      }
      $i++;
    }
}

// set up title and comments for the primary record
// supporting the new medication and drug allergy functionality
switch( $text_type ) {
    case "medication":
        // use ndc drug for title and ndc number and sig from discrete fields for comments
        if( $GLOBALS['ndc_lookup_enabled'] )
            $form_title = $drug;
        if( $GLOBALS['ndc_options_enabled'] )
            $form_comments = "Take ".$_POST['ndc_dosage']." in ".ucwords(strtolower($_POST['ndc_form']))." as ".ucwords(strtolower($_POST['ndc_route']))." per ".$_POST['ndc_interval'];
            $form_drug_id = str_replace("-","",$_POST['drug_ndc']);
    break;
    case "allergy":
        $form_comments = $_POST['form_comments'];
        // use ndc drug for title and ndc number and ndc_formulation for comments
        if( $_POST['drug_allergy'] == "on" ) {
            if( $GLOBALS['ndc_lookup_enabled'] )
                $form_title = $drug;
            if( $GLOBALS['ndc_options_enabled'] ) {
                if( $_POST['drug_ndc'] != "" )
                    $form_drug_id = str_replace("-","",$_POST['drug_ndc']);
                if( $_POST['form_ingredient'] != "" )
                    $form_ingredient = trim($_POST['form_ingredient']);
            }
        }
    break;
    default:
        $form_title = $_POST['form_title'];
        $form_comments = $_POST['form_comments'];
    break;
}
   if ($i++ == $_POST['form_type']) $text_type = $key;

  $form_begin = fixDate($_POST['form_begin'], '');
  $form_end   = fixDate($_POST['form_end'], '');

$listids = array();
  if ($text_type == 'football_injury') {
    $form_injury_part = $_POST['form_injury_part'];
    $form_injury_type = $_POST['form_injury_type'];
  }
  else {
    $form_injury_part = $_POST['form_medical_system'];
    $form_injury_type = $_POST['form_medical_type'];
  }

// determine type of save for a primary record of medication, drug allergy or the original title
$save_method = "";
switch( $_POST['form_save'] ) {
    case "Save As New":
        // save as new record
        $save_method = "insert";
      break;
    case "Save & Add Another":
        // if(issue) update else add then keep window for adding more
        if( !$_REQUEST['issue'] )
            $save_method = "insert";
        else
            $save_method = "update";
      break;
    case "Update Existing":
        // saves changes to existing record
        if( $_REQUEST['issue'] )
            $save_method = "update";
      break;
    default:
        // replaces the old Save method - since title is readonly,
        // if no issue was passed in, insert
        // update if the form type was not changed, otherwise insert
        // if issue was passed in, but additional titles were added, do not update
        if( !$_REQUEST['issue'] ) {
            $save_method = "insert";
        }elseif( $text_type == 'escript' ) {
            $save_method = "update";
        }else{
            // if issue was passed in, and form_type was changed, insert
            // if issue was passed in, and additional titles were not entered, update
            // if types are the same, but there are additional titles, do not save primary record
            if( $_POST['form_type'] != $_POST['original_type'] ) {
                $save_method = "insert";
            }elseif( empty($num_add_titles) ) {
                $save_method = "update";
            }
        }
      break;
}


switch( $save_method ) {
  case "update":
      $cur_issue = $_REQUEST['issue'];
      if( $cur_issue )
          $listids[] = $cur_issue;
      $update = "UPDATE lists SET " .
      "type = '" . $text_type . "', " .
      "title = '" . $form_title . "', " .
      "comments = '" . $form_comments . "', " .
      "begdate = " . QuotedOrNull($form_begin) . ", " .
      "enddate = " . QuotedOrNull($form_end) . ", " .
      "returndate = "   . QuotedOrNull($form_return)  . ", "  .
      "diagnosis = '" . $_POST['form_diagnosis'] . "', " .
      "occurrence = '" . $_POST['form_occur'] . "', " .
      "classification = '" . $_POST['form_classification'] . "', " .
      "reinjury_id = '" . $_POST['form_reinjury_id']  . "', " .
      "referredby = '" . $_POST['form_referredby'] . "', " .
      "extrainfo = '"   . $_POST['form_missed']       . "', " .
      "injury_grade = '" . $_POST['form_injury_grade'] . "', " .
      "injury_part = '" . $form_injury_part           . "', " .
      "injury_type = '" . $form_injury_type           . "', " .
      "outcome = '"     . $_POST['form_outcome']      . "', " .
      "destination = '" . $_POST['form_destination']   . "', "  .
      "reaction = '" . trim($_POST['form_reaction'])   . "', "  .
      "ingredient = '" . $form_ingredient   . "', "  .
      "drug_id = '" . $form_drug_id   . "' "  .
      "WHERE id = '".$_REQUEST['issue']."'";
    $result = sqlStatement($update);
    if ($text_type == "medication" && enddate != '') {
      sqlStatement('UPDATE prescriptions SET '
        . 'medication = 0 where patient_id = ' . $thispid
        . " and upper(trim(drug)) = '" . strtoupper($_POST['form_title']) . "' "
        . ' and medication = 1' );
    }
    break;
  case "insert":

    $insert = "INSERT INTO lists ( " .
      "date, pid, type, title, activity, comments, begdate, enddate, returndate, " .
      "diagnosis, occurrence, classification, referredby, extrainfo, user, groupname, " .
      "outcome, destination, reinjury_id, injury_grade, injury_part, injury_type, " .
      "reaction, ingredient, drug_id " .
      ") VALUES ( " .
      "NOW(), " .
      $pid                                . ", " .
      "'" . $text_type                    . "', " .
      "'" . $form_title                   . "', " .
      "1, "                               .  
      "'" . $form_comments                . "', " .
      QuotedOrNull($form_begin)           . ", "  .
      QuotedOrNull($form_end)             . ", "  .
      QuotedOrNull($form_return)          . ", "  .
      "'" . $_POST['form_diagnosis']      . "', " .
      "'" . $_POST['form_occur']          . "', " .
      "'" . $_POST['form_classification'] . "', " .
      "'" . $_POST['form_referredby']     . "', " .
      "'" . $_POST['form_missed']         . "', " .
      "'" . $_SESSION['authUser']         . "', " .
      "'" . $_SESSION['authProvider']     . "', " .
      "'" . $_POST['form_outcome']        . "', " .
      "'" . $_POST['form_destination']    . "', " .
      "'" . $_POST['form_reinjury_id']    . "', " .
      "'" . $_POST['form_injury_grade']   . "', " .
      "'" . $form_injury_part             . "', " .
      "'" . $form_injury_type             . "', " .
      "'" . trim($_POST['form_reaction']) . "', " .
      "'" . $form_ingredient . "', " .
      "'" . $form_drug_id                 . "' "  .
      ")";
    $cur_issue = sqlInsert($insert);
    $list_id = $cur_issue;
    if( $list_id )
        $listids[] = $list_id;
    else
        $info_msg = "Original title could not be inserted as a new record";

    break;
}


  if ($text_type == 'football_injury') issue_football_injury_save($issue);
  if ($text_type == 'ippf_gcac'      ) issue_ippf_gcac_save($issue);
  if ($text_type == 'contraceptive'  ) issue_ippf_con_save($issue);

  // If requested, link the issue to a specified encounter.
  if ($thisenc) {
    $query = "INSERT INTO issue_encounter ( " .
      "pid, list_id, encounter " .
      ") VALUES ( " .
      "'$thispid', '$issue', '$thisenc'" .
    ")";
    sqlStatement($query);
  }

if( !empty($listids) ) {
    require_once("$srcdir/outbox.inc");
    foreach( $listids as $i )
    {
	    if( $GLOBALS['rh_patient'] ) {
	      queueMessage('ADT', $pid,'lists',$i);
	    }
	    if( $GLOBALS['rh_summary'] && ($text_type == 'medication' || $text_type == 'allergy') ) {
		queueMessage('CCD', $pid,'lists',$i);
	    }
    }
}
  $tmp_title = $ISSUE_TYPES[$text_type][2] . ": $form_begin " .
   substr($_POST['form_title'], 0, 40);

  // Close this window and redisplay the updated list of issues.
  //
  echo "<html><body><script language='JavaScript'>\n";
  if ($info_msg) echo " alert('$info_msg');\n";
  echo " if ( opener ) { opener.location.reload(); } else { parent.location.reload(); } \n";
  echo " if (parent.refreshIssue) parent.refreshIssue($issue,'$tmp_title'); if ( parent.$ ) parent.$.fn.fancybox.close();\n";
  if( $_POST['form_save'] != 'Save & Add Another' )
      echo " window.close();\n";
  echo "</script></body></html>\n";
    $issue = "";
    if( $_POST['form_save'] != 'Save & Add Another' ) {
        $save_date = $_POST['begdate'];
        $_POST = "";
        //unset($_POST):
        exit();
    }

}  // end if SAVE 

$irow = array();
if ($issue)
  $irow = sqlQuery("SELECT * FROM lists WHERE id = $issue");
else if ($thistype)
  $irow['type'] = $thistype;

$type_index = 0;

if (!empty($irow['type'])) {
  foreach ($ISSUE_TYPES as $key => $value) {
    if ($key == $irow['type']) break;
    ++$type_index;
  }

}

if( $irow['type'] != "allergy" && $irow['type'] != "medication" ) {
    $comments = $irow['comments'];

}elseif( $GLOBALS['ndc_options_enabled'] )
{
        $comments = "";
        $ndc_dosage = "1";
        $drug_allergy = false;
        // Ex. Med comments: "Take dosage in form form as route route per interval";
            if( $irow['type'] == "medication" ) {
                $sig = trim($irow['comments']);
                // Ex. sig = "Take dosage in form form as route route per interval";
                $take = substr($sig,0,4); // Take
                if( $take == "Take" ) {
                    $tmp1 = substr($sig,5); // dosage in form form as route route per interval
                    $tmp2 = explode(" in ",$tmp1);  // form form as route route per interval
                    $ndc_dosage = trim($tmp2[0]);
                    $tmp3 = explode(" as ",$tmp2[1]); // route route per interval
                    $ndc_form = trim($tmp3[0]);
                    $tmp4 = explode(" per ",$tmp3[1]);  // interval
                    $ndc_route = trim($tmp4[0]);
                    $ndc_interval = trim($tmp4[1]);
                }
            }elseif( $irow['type'] == "allergy" ) {
                if( trim($irow['drug_id']) != "") 
                { 
                    $drug_allergy = true;
                }  
            }

}elseif( $irow['type'] == "medication" ) {
    $comments = $irow['comments'];
}
?>
<html>
<head>
<?php html_header_show();?>

<title><?php echo $issue ? xl('Edit') : xl('Add New'); ?><?php xl('Issue','e',' '); ?> - <?php xl($patient_info,'e'); ?></title>
<link rel="stylesheet" href='<?php echo $css_header ?>' type='text/css'>

<style>

td, input, select, textarea {
 font-family: Arial, Helvetica, sans-serif;
 font-size: 10pt;
}

div.section {
 border: solid;
 border-width: 1px;
 border-color: #0000ff;
 margin: 0 0 0 10pt;
 padding: 5pt;
}

</style>

<style type="text/css">@import url(../../../library/dynarch_calendar.css);</style>
<link rel="stylesheet" href="../../../interface/themes/jquery.autocomplete.css" type="text/css">
<style type="text/css" title="mystyles" media="all">
<!--
td {
    font-size:8pt;
    font-family:helvetica;
}
input {
    font-size:8pt;
    font-family:helvetica;
}
select {
    font-size:8pt;
    font-family:helvetica;
}
textarea {
    font-size:8pt;
    font-family:helvetica;
}
-->
</style>
<script type="text/javascript" src="../../../library/dynarch_calendar.js"></script>
<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
<script type="text/javascript" src="../../../library/dynarch_calendar_setup.js"></script>
<script type="text/javascript" src="../../../library/textformat.js"></script>
<script type="text/javascript" src="../../../library/dialog.js"></script>
<script type="text/javascript" src="../../../library/js/jquery-1.2.2.min.js"></script>
<script type="text/javascript" src="../../../library/js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="../../../library/js/jquery.dimensions.pack.js"></script>
<script type="text/javascript" src="../../../library/js/jquery.autocomplete.pack.js"></script>

<script language="JavaScript">

 var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

 var aitypes = new Array(); // issue type attributes
 var aopts   = new Array(); // Option objects
<?php
 // "Clickoptions" is a feature by Mark Leeds that provides for one-click
 // access to preselected lists of issues in each category.  Here we get
 // the issue titles from the user-customizable file and write JavaScript
 // statements that will build an array of arrays of Option objects.
 //
 $clickoptions = array();
 if (is_file($GLOBALS['OE_SITE_DIR'] . "/clickoptions.txt"))
  $clickoptions = file($GLOBALS['OE_SITE_DIR'] . "/clickoptions.txt");
 $i = 0;
 foreach ($ISSUE_TYPES as $key => $value) {
  echo " aitypes[$i] = " . $value[3] . ";\n";
  echo " aopts[$i] = new Array();\n";
  foreach($clickoptions as $line) {
   $line = trim($line);
   if (substr($line, 0, 1) != "#") {
    if (strpos($line, $key) !== false) {
     $text = addslashes(substr($line, strpos($line, "::") + 2));
     echo " aopts[$i][aopts[$i].length] = new Option('$text', '$text', false, false);\n";
    }
   }
  }
  ++$i;
 }
?>

<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 // React to selection of an issue type.  This loads the associated
 // shortcuts into the selection list of titles, and determines which
 // rows are displayed or hidden.
 function newtype(index) {
  var f = document.forms[0];
  var theopts = f.form_titles.options;
  theopts.length = 0;
  var i = 0;
  if( aopts && aopts[index] && aopts[index].length > 0 ) {
    for (i = 0; i < aopts[index].length; ++i) {
     theopts[i] = aopts[index][i];
    }
  }

  orig_type = document.getElementById('original_type').value;
  document.getElementById('row_titles').style.display = i ? '' : 'none';
  // Show or hide various rows depending on issue type, except do not
  // hide the comments or referred-by fields if they have data.
  var emed = '<?=$GLOBALS['rh_medication']?>';
  if( emed && index == 5 ) {
        document.getElementById('row_sig').style.display = '';
        document.getElementById('more_saves').style.display = 'none';
  }else{

      var comdisp = (aitypes[index] == 1) ? 'none' : '';
      var revdisp = (aitypes[index] == 1) ? '' : 'none';
      var injdisp = (aitypes[index] == 2) ? '' : 'none';
      var nordisp = (aitypes[index] == 0) ? '' : 'none';
      document.getElementById('row_sig').style.display = 'none';
      document.getElementById('row_enddate'       ).style.display = comdisp;
      document.getElementById('row_active'        ).style.display = revdisp;
      document.getElementById('row_diagnosis'     ).style.display = comdisp;
      document.getElementById('row_occurrence'    ).style.display = comdisp;
      document.getElementById('row_classification').style.display = injdisp;
      document.getElementById('row_referredby'    ).style.display = (f.form_referredby.value) ? '' : comdisp;
      document.getElementById('row_comments'      ).style.display = (f.form_comments.value  ) ? '' : comdisp;

      var ndc = '<?php echo $GLOBALS['ndc_lookup_enabled'];?>';
      var ndcop = '<?php echo $GLOBALS['ndc_options_enabled'];?>';
      switch( index ) {
          case 2:
              if( ndc ) {
                  document.getElementById('row_drug_allergy').style.display = 'none';
                  document.getElementById('row_drug_spec').style.display = 'none';
                  document.getElementById('row_reaction').style.display = 'none';
                  document.getElementById('row_title').style.display = 'none';
                  document.getElementById('form_save').style.display = 'none';
                  document.getElementById('row_drug_lookup').style.display = '';
                  document.getElementById('more_saves').style.display = '';
                  if( ndcop ) {
                      document.getElementById('row_comments').style.display = 'none';
                      document.getElementById('row_drug_options').style.display = '';
                  }else{
                      document.getElementById('row_comments').style.display = '';
                      document.getElementById('row_drug_options').style.display = 'none';
                  }
              }
              if( orig_type != 2 )
                  document.getElementById('row_comments').value = "";
          break;
          case 1:
                  document.getElementById('row_drug_allergy').style.display = '';
                  document.getElementById('row_reaction').style.display = '';
                  document.getElementById('row_comments').style.display = 'none';
<?php
if($drug_allergy)
    echo "                  document.getElementById('drug_allergy').checked = true;\n";
  if ($ISSUE_TYPES['football_injury']) {
    // Generate more of these for football injury fields.
    issue_football_injury_newtype();
  }
  if ($ISSUE_TYPES['ippf_gcac'] && !$_POST['form_save']) {
    // Generate more of these for gcac and contraceptive fields.
    if (empty($issue) || $irow['type'] == 'ippf_gcac'    ) issue_ippf_gcac_newtype();
    if (empty($issue) || $irow['type'] == 'contraceptive') issue_ippf_con_newtype();
    // if (empty($issue) || $irow['type'] == 'ippf_srh'     ) issue_ippf_srh_newtype();
  }
?>
                  if( ndc && document.getElementById('drug_allergy').checked ) {
                      document.getElementById('row_title').style.display = 'none';
                      document.getElementById('row_drug_options').style.display = 'none';
                      document.getElementById('form_save').style.display = 'none';
                      document.getElementById('row_drug_lookup').style.display = '';
                      document.getElementById('more_saves').style.display = '';
                      if( ndcop ) {
                          document.getElementById('row_drug_spec').style.display = '';
                      }else{
                          document.getElementById('row_drug_spec').style.display = 'none';
                      }
                  }else{
                      document.getElementById('row_drug_lookup').style.display = 'none';
                      document.getElementById('row_drug_options').style.display = 'none';
                      document.getElementById('row_drug_spec').style.display = 'none';
                      document.getElementById('more_saves').style.display = 'none';
                      document.getElementById('row_title').style.display = '';
                      document.getElementById('form_save').style.display = '';
                  }
          break;
          default:
              document.getElementById('row_drug_lookup').style.display = 'none';
              document.getElementById('row_drug_options').style.display = 'none';
              document.getElementById('row_drug_allergy').style.display = 'none';
              document.getElementById('row_drug_spec').style.display = 'none';
              document.getElementById('row_reaction').style.display = 'none';
              document.getElementById('more_saves').style.display = 'none';
              document.getElementById('row_title').style.display = '';
              document.getElementById('form_save').style.display = '';
                  if( orig_type != index )
                      document.getElementById('row_comments').value = "";
          break;
      }


    <?php if ($GLOBALS['athletic_team']) { ?>
      document.getElementById('row_returndate').style.display = comdisp;
	  document.getElementById('row_injury_grade'  ).style.display = injdisp;
	  document.getElementById('row_injury_part'   ).style.display = injdisp;
	  document.getElementById('row_injury_type'   ).style.display = injdisp;
	  document.getElementById('row_medical_system').style.display = nordisp;
	  document.getElementById('row_medical_type'  ).style.display = nordisp;
	  // Change label text of 'title' row depending on issue type:
	  document.getElementById('title_diagnosis').innerHTML = '<b>' +
	   (index == <?php echo issueTypeIndex('allergy'); ?> ?
	   '<?php echo xl('Allergy') ?>' :
	   (index == <?php echo issueTypeIndex('general'); ?> ?
	   '<?php echo xl('Title') ?>' :
	   '<?php echo xl('Text Diagnosis') ?>')) +
	   ':</b>';
	<?php } else { ?>
	  document.getElementById('row_referredby'    ).style.display = (f.form_referredby.value) ? '' : comdisp;
	    <?php } ?>
	    <?php
	  if ($ISSUE_TYPES['football_injury']) {
	    // Generate more of these for football injury fields.
	    issue_football_injury_newtype();
	  }
	  if ($ISSUE_TYPES['ippf_gcac'] && !$_POST['form_save']) {
	    // Generate more of these for gcac and contraceptive fields.
	    if (empty($issue) || $irow['type'] == 'ippf_gcac'    ) issue_ippf_gcac_newtype();
	    if (empty($issue) || $irow['type'] == 'contraceptive') issue_ippf_con_newtype();
	  }
	    ?>

   }

 }

 // If a clickoption title is selected, copy it to the title field.
 function set_text() {
  var f = document.forms[0];
  f.drug.value = f.form_titles.options[f.form_titles.selectedIndex].text;
  f.form_title.value = f.form_titles.options[f.form_titles.selectedIndex].text;
  f.form_titles.selectedIndex = -1;
 }

 // Process click on Delete link.
 function deleteme() {
  dlgopen('../deleter.php?issue=<?php echo $issue ?>', '_blank', 500, 450);
  return false;
 }

 // Called by the deleteme.php window on a successful delete.
 function imdeleted() {
  closeme();
 }

 function closeme() {
    if ( parent.$ ) parent.$.fn.fancybox.close();
    window.close();
 }

 // Called when the Active checkbox is clicked.  For consistency we
 // use the existence of an end date to indicate inactivity, even
 // though the simple verion of the form does not show an end date.
 function activeClicked(cb) {
  var f = document.forms[0];
  if (cb.checked) {
   f.form_end.value = '';
  } else {
   var today = new Date();
   f.form_end.value = '' + (today.getYear() + 1900) + '-' +
    (today.getMonth() + 1) + '-' + today.getDate();
  }
 }

// This is for callback by the find-code popup.
// Appends to or erases the current list of diagnoses.
function set_related(codetype, code, selector, codedesc) {
 var f = document.forms[0];
 var s = f.form_diagnosis.value;
 if (code) {
  if (s.length > 0) s += ';';
  s += codetype + ':' + code;
 } else {
  s = '';
 }
 f.form_diagnosis.value = s;
}

// This invokes the find-code popup.
function sel_diagnosis() {
 dlgopen('../encounter/find_code_popup.php?codetype=<?php echo $diagnosis_type ?>', '_blank', 500, 400);
}

// Check for errors when the form is submitted.
function validate() {
    var f = document.forms[0];
    var type = 0;

    for( i = 0; i < document.forms[0].form_type.length; i++ )
    {
        if( document.forms[0].form_type[i].checked == true )
        type = document.forms[0].form_type[i].value;
    }
    if( type == 2 ) {
        if( !f.drug.value ) {
            alert('Please enter a drug title!');
            return false;
        }
    }else if( type == 1 ) {
        if( document.getElementById['drug_allergy'].checked ) {
            if( !f.drug.value ) {
                alert('Please enter a drug title!');
                return false;
            }
        }else{
            if( !f.form_title.value) {
                alert('Please enter a title!');
                return false;
            }
        }
    }else{
        if (! f.form_title.value) {
	  alert("<?php xl('Please enter a title!','e'); ?>");
            return false;
        }
    }
    top.restoreSession();
    return true;
}


// Supports customizable forms (currently just for IPPF).
function divclick(cb, divid) {
 var divstyle = document.getElementById(divid).style;
 if (cb.checked) {
  divstyle.display = 'block';
 } else {
  divstyle.display = 'none';
 }
 return true;
}

</script>

</head>

<body class="body_top" style="padding-right:0.5em">

<form method='post' name='theform' id='theform' action='add_edit_issue.php?issue=<?php echo $issue ?>&thisenc=<?php echo $thisenc ?>'
 onsubmit='return validate()'>

<table border='0' width='100%'>

 <tr>
  <td valign='top' width='1%' nowrap><b><?php xl('Type','e'); ?>:</b></td>
  <td>
<?php
 $index = 0;
 foreach ($ISSUE_TYPES as $value) {
  if ($issue || $thistype) {
    if ($index == $type_index) {
      echo $value[1];
      echo "<input type='hidden' name='form_type' value='$index'>\n";
    }
  } else {

    echo "   <input type='radio' name='form_type' id='form_type' value='$index' onclick='newtype($index)'";
    if ($index == $type_index) echo " checked";
    if( $index == 5 || $type_index == 5 ) echo " disabled ";  // for viewing escripts
    echo " />" . $value[1] . "&nbsp;\n";
  }
  ++$index;
 }
?>
  </td>
 </tr>

 <tr id='row_titles'>
  <td valign='top' nowrap>&nbsp;</td>
  <td valign='top'>
   <select name='form_titles' size='4' onchange='set_text()'>
   </select><br />
   <?php xl('(Select one of these, or type your own title)','e'); ?>
  </td>
 </tr>
 <tr id='row_drug_allergy' style='display:none'>
  <td valign='top' nowrap> </td>
  <td valign='middle'>
    <input type='checkbox' <?php if($drug_allergy) echo "checked "; ?> id='drug_allergy' name='drug_allergy'> Check for drug allergies
  </td>
 </tr>

  <tr id='row_title'>
  <td valign='middle' nowrap><b><?php xl('Title','e'); ?>:</b></td>
  <td>
   <input type='text' size='40' name='form_title' id='form_title' <? if($issue) echo " readonly "; ?> value='<?php echo $irow['title'] ?>' style='width:100%' />
   <input type='hidden' name='original_type' id='original_type' value='<?php echo $type_index; ?>' />
  </td>
  <tr id='row_sig' style='display:none'>
  <td valign='middle' nowrap><b><?php xl('Sig','e'); ?>:</b></td>
  <td>
   <input type='text' size='40' name='sig' id='sig' readonly value='<?php echo $irow['comments'] ?>' style='width:100%' />
  </td>
 </tr>
  <tr id='row_drug_lookup' style='display:none'>
  <td valign='middle' nowrap><b><?php xl('Drug','e'); ?>:</b></td>
  <td>
   <input type='text' size='40' name='drug' id='drug' value='<?php echo $irow['title'] ?>' style='width:100%'>
   <input type='hidden' name='drug_id' id='drug_id' value='' />
   <input type='hidden' name='drug_ndc' id='drug_ndc' value='<?php echo $irow['drug_id']; ?>' />
  </td>
 </tr>

<tr id='row_drug_options' style='display:none'>
    <td COLSPAN="1" ALIGN="LEFT" VALIGN="MIDDLE" ><b><?php xl('Take','e'); ?></b></td>
    <td COLSPAN="2" ALIGN="LEFT" VALIGN="MIDDLE" >
        <input TYPE="TEXT" NAME="ndc_dosage" id="ndc_dosage" SIZE="2" MAXLENGTH="10" VALUE="<?php echo $ndc_dosage; ?>"/>
        <?php xl('in','e'); ?>
        <select name="ndc_form" id="ndc_form" cols="10">
        <option selected value='<?php echo strtoupper($ndc_form); ?>'><?php echo $ndc_form; ?></option></select>
        <select name="ndc_route" id="ndc_route" cols="10">
        <option selected value='<?php echo strtoupper($ndc_route); ?>'><?php echo $ndc_route; ?></option></select>
        <select name="ndc_interval" id="ndc_interval" cols="5">
        <option selected value='<?php echo $ndc_interval; ?>'><?php echo $ndc_interval; ?></option></select><br>
    </td>
</tr>
<tr id='row_drug_spec' style='display:none'>
    <td COLSPAN="1" ALIGN="LEFT" VALIGN="MIDDLE" ><b><?php xl('Ingredient','e'); ?>:</b></td>
    <td COLSPAN="2" ALIGN="LEFT" VALIGN="MIDDLE" >
        <select id='form_ingredient' name='form_ingredient' cols='12'>
        <option selected value='<?php echo $irow['ingredient']; ?>'><?php echo ucwords(strtolower($irow['ingredient'])); ?></option></select> (optional)
    </td>
</tr>


 <tr id='row_diagnosis'>
  <td valign='top' nowrap><b><?php xl('Diagnosis Code','e'); ?>:</b></td>
  <td>
   <input type='text' size='50' name='form_diagnosis'
    value='<?php echo $irow['diagnosis'] ?>' onclick='sel_diagnosis()'
    title='<?php xl('Click to select or change diagnoses','e'); ?>'
    style='width:100%' readonly />
  </td>
 </tr>

 <!-- For Athletic Teams -->

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_injury_grade'>
  <td valign='top' nowrap><b><?php xl('Grade of Injury','e'); ?>:</b></td>
  <td>
<?php
echo generate_select_list('form_injury_grade', 'injury_grade', $irow['injury_grade'], '');
?>
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_injury_part'>
  <td valign='top' nowrap><b><?php xl('Injured Body Part','e'); ?>:</b></td>
  <td>
<?php
echo generate_select_list('form_injury_part', 'injury_part', $irow['injury_part'], '');
?>
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_injury_type'>
  <td valign='top' nowrap><b><?php xl('Injury Type','e'); ?>:</b></td>
  <td>
<?php
echo generate_select_list('form_injury_type', 'injury_type', $irow['injury_type'], '');
?>
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_medical_system'>
  <td valign='top' nowrap><b><?php xl('Medical System','e'); ?>:</b></td>
  <td>
<?php
echo generate_select_list('form_medical_system', 'medical_system', $irow['injury_part'], '');
?>
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_medical_type'>
  <td valign='top' nowrap><b><?php xl('Medical Type','e'); ?>:</b></td>
  <td>
<?php
echo generate_select_list('form_medical_type', 'medical_type', $irow['injury_type'], '');
?>
  </td>
 </tr>

 <!-- End For Athletic Teams -->
 <tr>
  <td valign='top' nowrap><b><?php xl('Begin Date','e'); ?>:</b></td>
  <td>

   <input type='text' size='10'  <? if($type_index == 5) echo " readonly "; ?> name='form_begin' id='form_begin'
    value='<?php echo $irow['begdate'] ?>'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
    title='<?php xl('yyyy-mm-dd date of onset, surgery or start of medication','e'); ?>' />
   <img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
    id='img_begin' border='0' alt='[?]' style='cursor:pointer'
    title='<?php xl('Click here to choose a date','e'); ?>' />
  </td>
 </tr>

 <tr id='row_enddate'>
  <td valign='top' nowrap><b><?php xl('End Date','e'); ?>:</b></td>
  <td>
   <input type='text' size='10' name='form_end' id='form_end'
    value='<?php echo $irow['enddate'] ?>'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
    title='<?php xl('yyyy-mm-dd date of recovery or end of medication','e'); ?>' />
   <img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
    id='img_end' border='0' alt='[?]' style='cursor:pointer'
    title='<?php xl('Click here to choose a date','e'); ?>' />
    &nbsp;(<?php xl('leave blank if still active','e'); ?>)
  </td>
 </tr>

 <tr id='row_active'>
  <td valign='top' nowrap><b><?php xl('Active','e'); ?>:</b></td>
  <td>
   <input type='checkbox' name='form_active' <? if($type_index != 5) echo " readonly "; ?> value='1' <?php echo $irow['enddate'] ? "" : "checked"; ?>
    onclick='activeClicked(this);'
    title='<?php xl('Indicates if this issue is currently active','e'); ?>' />
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_returndate'>
  <td valign='top' nowrap><b><?php xl('Returned to Play','e'); ?>:</b></td>
  <td>
   <input type='text' size='10' name='form_return' id='form_return'
    value='<?php echo $irow['returndate'] ?>'
    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
    title='<?php xl('yyyy-mm-dd date returned to play','e'); ?>' />
   <img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
    id='img_return' border='0' alt='[?]' style='cursor:pointer'
    title='<?php xl('Click here to choose a date','e'); ?>' />
    &nbsp;(<?php xl('leave blank if still active','e'); ?>)
  </td>
 </tr>

 <tr id='row_occurrence'<?php if( $type_index == 5 ) echo " style='display:none'"; ?>>
  <td valign='top' nowrap><b><?php xl('Occurrence','e'); ?>:</b></td>
  <td>
   <?php
    // Modified 6/2009 by BM to incorporate the occurrence items into the list_options listings
    generate_form_field(array('data_type'=>1,'field_id'=>'occur','list_id'=>'occurrence','empty_title'=>'SKIP'), $irow['occurrence']);
   ?>	
  </td>
 </tr>

 <tr id='row_classification'<?php if( $type_index == 5 ) echo " style='display:none'"; ?>>
  <td valign='top' nowrap><b><?php xl('Classification','e'); ?>:</b></td>
  <td>
   <select name='form_classification'>
<?php
 foreach ($ISSUE_CLASSIFICATIONS as $key => $value) {
  echo "   <option value='$key'";
  if ($key == $irow['classification']) echo " selected";
  echo ">$value\n";
 }
?>
   </select>
  </td>
 </tr>

 <tr<?php if (! $GLOBALS['athletic_team']) echo " style='display:none;'"; ?> id='row_missed'>
  <td valign='top' nowrap><b><?php xl('Re-Injury?','e'); ?>:</b></td>
  <td>
   <select name='form_reinjury_id'>
    <option value='0'><?php echo xl('No'); ?></option>
<?php
  $pres = sqlStatement(
   "SELECT id, begdate, title " .
   "FROM lists WHERE " .
   "pid = '$thispid' AND " .
   "type = 'football_injury' AND " .
   "activity = 1 " .
   "ORDER BY begdate DESC"
  );
  while ($prow = sqlFetchArray($pres)) {
    echo "   <option value='" . $prow['id'] . "'";
    if ($prow['id'] == $irow['reinjury_id']) echo " selected";
    echo ">" . $prow['begdate'] . " " . $prow['title'] . "\n";
  }
?>
   </select>
  </td>
 </tr>

 <tr id='row_referredby'<?php if ($GLOBALS['athletic_team']) echo " style='display:none;'"; ?>>
  <td valign='top' nowrap><b><?php if($type_index == 5){ xl('Provider','e'); }else{ xl('Referred by','e'); } ?>:</b></td>
  <td>
   <input type='text' size='40' <? if($type_index == 5) echo " readonly "; ?> name='form_referredby' value='<?php echo $irow['referredby'] ?>'
    style='width:100%' title='<?php xl('Referring physician and practice','e'); ?>' />
  </td>
 </tr>

 <tr id='row_comments'<?php if( $type_index == 5 ) echo " style='display:none'"; ?>>
  <td valign='top' nowrap><b><?php xl('Comments','e'); ?>:</b></td>
  <td>
   <textarea name='form_comments' id='form_comments' rows='4' cols='40' wrap='virtual' style='width:100%'><?php echo $comments; ?></textarea>
  </td>
 </tr>

 <tr id='row_reaction' style='display:none'>
  <td valign='top' nowrap><b><?php xl('Reaction','e'); ?>:</b></td>
  <td>
   <textarea name='form_reaction' id='form_reaction' rows='4' cols='40' wrap='virtual' style='width:100%'><?php echo $irow['reaction']; ?></textarea>
  </td>
 </tr>

 <tr<?php if ($GLOBALS['athletic_team'] || $GLOBALS['ippf_specific'] || $type_index == 5 ) echo " style='display:none;'"; ?>>
  <td valign='top' nowrap><b><?php xl('Outcome','e'); ?>:</b></td>
  <td>	
   <?php
    // Modified 6/2009 by BM to incorporate the outcome items into the list_options listings
    generate_form_field(array('data_type'=>1,'field_id'=>'outcome','list_id'=>'outcome','empty_title'=>'SKIP'), $irow['outcome']);
   ?>
  </td>
 </tr>

 <tr<?php if ($GLOBALS['athletic_team'] || $GLOBALS['ippf_specific'] || $type_index == 5 ) echo " style='display:none;'"; ?>>
  <td valign='top' nowrap><b><?php xl('Destination','e'); ?>:</b></td>
  <td>
<?php if (true) { ?>
   <input type='text' size='40' name='form_destination' value='<?php echo $irow['destination'] ?>'
    style='width:100%' title='GP, Secondary care specialist, etc.' />
<?php } else { // leave this here for now, please -- Rod ?>
   <?php echo rbinput('form_destination', '1', 'GP'                 , 'destination') ?>&nbsp;
   <?php echo rbinput('form_destination', '2', 'Secondary care spec', 'destination') ?>&nbsp;
   <?php echo rbinput('form_destination', '3', 'GP via physio'      , 'destination') ?>&nbsp;
   <?php echo rbinput('form_destination', '4', 'GP via podiatry'    , 'destination') ?>
<?php } ?>
  </td>
 </tr>

</table>

<?php
  if ($ISSUE_TYPES['football_injury']) {
    issue_football_injury_form($issue);
  }
  if ($ISSUE_TYPES['ippf_gcac']) {
    if (empty($issue) || $irow['type'] == 'ippf_gcac')
      issue_ippf_gcac_form($issue, $thispid);
    if (empty($issue) || $irow['type'] == 'contraceptive')
      issue_ippf_con_form($issue, $thispid);
  }
?>

<center>
<p>
<div id='more_saves'>
<input type='submit' name='form_save' id='form_save1' value='<?php xl('Save As New','e'); ?>' />
&nbsp;
<?php if($issue) { ?>
<input type='submit' name='form_save' id='form_save2' value='<?php xl('Update Existing','e'); ?>' />
&nbsp;
<?php } ?>
<input type='submit' name='form_save' id='form_save3' value='<?php xl('Save & Add Another','e'); ?>' />
</p><p>
</div>

<input type='submit' name='form_save' id='form_save' value='<?php xl('Save','e'); ?>' />

<?php if ($issue && acl_check('admin', 'super')) { ?>
&nbsp;
<input type='button' value='<?php xl('Delete','e'); ?>' style='color:red;display:inline' onclick='deleteme()' />
<?php } ?>

&nbsp;
<input type='button' value='<?php xl('Cancel','e'); ?>' style='display:inline' onclick='window.close()' />

</p>
</center>

</form>
<script type="text/javascript">
 newtype(<?php echo $type_index ?>);
 Calendar.setup({inputField:"form_begin", ifFormat:"%Y-%m-%d", button:"img_begin"});
 Calendar.setup({inputField:"form_end", ifFormat:"%Y-%m-%d", button:"img_end"});
 Calendar.setup({inputField:"form_return", ifFormat:"%Y-%m-%d", button:"img_return"});

$().ready(function() {
    var ndc = '<?php echo $GLOBALS['ndc_lookup_enabled'];?>';
    var ndcop = '<?php echo $GLOBALS['ndc_options_enabled'];?>';
    $("#drug").autocomplete('../../../library/ajax/ndc_drug_lookup.php',
                            {
                            width: 450,
                            scrollHeight: 100,
                            selectFirst: true
                            });

    // capture the drug list id and ndc number
    $('#drug').result(function(event, data, formatted) {
        data = !data ? "" : data[1];
        drugid = data.split('#');
        $('#drug_id').val( !data ? "" : drugid[0]);
        $('#drug_ndc').val( !data ? "" : drugid[1]);
        drug = !data ? "" : drugid[2];
        if( ndcop ) {
            if( $('input[name=drug_allergy]').is(':checked') )
            {   // ge ingredient from drug allergy
                ndcformulation=$.ajax({url:"../../../library/ajax/ndc_get_options.php?option=formulation&drug="+drugid[0],async:false});
                $("#form_ingredient").html(ndcformulation.responseText);
            }else{
                // get drug options for selected drug
                ndcform=$.ajax({url:"../../../library/ajax/ndc_get_options.php?option=form&drug="+drugid[0],async:false});
                $("#ndc_form").html(ndcform.responseText);
                ndcroute=$.ajax({url:"../../../library/ajax/ndc_get_options.php?option=route&drug="+drugid[0],async:false});
                $("#ndc_route").html(ndcroute.responseText);
                ndcinterval=$.ajax({url:"../../../library/ajax/ndc_get_options.php?option=interval&drug="+drugid[0],async:false});
                $("#ndc_interval").html(ndcinterval.responseText);
            }
        }
    });

    $("#drug").focus();

    // change options for drug allergies
    $("#drug_allergy").change(function(){
      if( ndc ) {
        if( $('input[name=drug_allergy]').is(':checked') ) {
            $('#row_title').hide();
            $('#form_save').hide();
            $('#row_drug_lookup').show();
            $('#more_saves').show();
           if( ndcop ) {
                $('#row_drug_spec').show();
           }else{
                $('#row_drug_spec').hide();
           }
        }else{
            $('#row_drug_lookup').hide();
            $('#row_drug_spec').hide();
            $('#more_saves').hide();
            $('#row_title').show();
            $('#form_save').show();
        }
      }
    });

});



</script>
</body>
</html>
