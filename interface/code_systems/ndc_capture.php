<?php
/*******************************************************
This file allows the user to manage the NDC directory
Download archive, create tables, load data
View filesize, table cont and the date/time of the last updates
********************************************************/
require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/ndc_capture.inc");

?>
<html>
<head>
<?php html_header_show();?>
<title><?php ($file_submit) ? "NDC Directory : ".$file_select." Results" : "NDC Directory"; ?></title>
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

<style type="text/css" title="mystyles" media="all">
<!--
td {
    vertical-align:Top;
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
div.ndc {
    vertical-align:Top;
}
div.ndc table {
    vertical-align:Top;
    horizontal-align:Center;
}
div.ndc td {
    vertical-align:Top;
    horizontal-align:Center;
}
div.header {
    margin:0px;
    padding:3px;
    text-align:Left;
    background:#fff;
    border:solid 1px #c3c3c3;
    font-size:10pt;
    font-family:helvetica;
}
div.file_list,#list_heading {
    margin:0px;
    width:394px;
    padding:4px;
    horizontal-align:Center;
    vertical-align:Top;
    text-align:Left;
    background:#fff;
    border-top:solid 1px #c3c3c3;
    border-left:solid 1px #c3c3c3;
    border-right:solid 1px #c3c3c3;
    font-size:10pt;
    font-family:helvetica;
}
div.file_list {
    background:#ffd;
    vertical-align:Top;
    horizontal-align:Center;
    height:257px;
    border-bottom:solid 1px #c3c3c3;
}
div.file_list tr {
    background:#ddd;
    font-size:9pt;
    font-family:helvetica;
}
div.file_list th {
    background:#ddd;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.file_list td {
    border-top: 1px solid #ddd;
    background:#fff;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.file_select,div.file_heading {
    margin:0px;
    width:346px;
    padding:4px;
    vertical-align:Top;
    text-align:Left;
    background:#fff;
    border-top:solid 1px #c3c3c3;
    border-left:solid 1px #c3c3c3;
    border-right:solid 1px #c3c3c3;
    font-size:10pt;
    font-family:helvetica;
}
div.file_select{
    background:#ffd;
    vertical-align:Top;
    height:145px;
    border-bottom:solid 1px #c3c3c3;
}
div.file_select tr {
    background:#ddd;
    font-size:9pt;
    font-family:helvetica;
}

div.file_select th {
    background:#ddd;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.file_select td {
    //border-top: 1px solid #ddd;
    background:#ffd;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.file_results,.results_heading{
    margin:0px;
    width:346px;
    padding:4px;
    vertical-align:Top;
    text-align:Left;
    background:#fff;
    border-top:solid 1px #c3c3c3;
    border-left:solid 1px #c3c3c3;
    border-right:solid 1px #c3c3c3;
    font-size:10pt;
    font-family:helvetica;
    display:none;
}
div.file_results
{
    background:#ffd;
    vertical-align:Top;
    height:73px;
    border-bottom:solid 1px #c3c3c3;
    //display:none;
}
div.file_results tr {
    background:#ddd;
    font-size:9pt;
    font-family:helvetica;
}
div.file_results th {
    background:#ddd;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.file_results td {
    //border-top: 1px solid #ddd;
    background:#ffd;
    vertical-align:Top;
    padding-top:2px;
    padding-bottom:2px;
    padding-left:6px;
    padding-right:6px;
    font-size:9pt;
    font-family:helvetica;
}
div.update_results {
    font-size:8pt;
    font-family:helvetica;
}
#data_load {
    vertical-align:middle;
}

.tooltip {
  display:none;
  background:transparent url(../../images/tooltip/white.png);
  font-size:12px;
  height:50px;
  width:200px;
  padding:25px;
  color:#fff;
}
-->
</style>
<script type="text/javascript" src="../../library/js/jquery-1.2.2.min.js"></script>
<script language="JavaScript">
<?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

// Check for errors when the form is submitted.
function validate() {
 //var f = document.forms[0];
 index = document.getElementById('file_select').selectedIndex;
 alert(f.file_select.selectedIndex.value);
 if( document.getElementById('file_select').options[index].text == "") {
    alert('Please select a file');
    return false;
  }

 top.restoreSession();
 return true;
 document.forms[0].submit();
}
</script>
</head>

<body class="body_top" style="padding-right:0.5em">
<div id='ndc'>
<form method='post' name='ndcform' id='ndcform' action='ndc_capture.php'>

<table>
  <tr>
    <td colspan='2'>
      <div class='header'>National Drug Code Directory</div>
    </td>
  </tr>
    <tr>
      <td>
        <div class='file_heading'>Load Data From File</div>
        <div class='file_select' style='display:';>
        <table border='0' width='100%'>
          <tr>
            <td align='left' valign='middle' nowrap><b><?php xl('Select','e'); ?> :</b>
                <select name="file_select" id="file_select" />
<?php
            if( $_POST['file_select'] )
                echo "    <option selected value=".$_POST['file_select'].">".strtolower($_POST['file_select'])."</option\n";
            else
                echo "    <option selected value=> </option\n";
            foreach( $ndc_files as $file => $title ) {
              echo "    <option value=".$file.">".$title."</option>\n";
            }
?>
                </select>
                <input type='button' name='file_submit' id='file_submit' value='<?php xl('Load','e'); ?>' /> <img src='../../images/ajax-loader.gif' id='data_load' border='0' style='display:none' />
            </td>
          </tr>
        </table>
        <div id='file_info'></div>
        </div>
    </td>
    <td rowspan='2'>
        <div id='list_heading'><img src='../../images/downbtn.gif' id='file_update' border='0' align='left' valign='text' hspace='2' title='It may take several minutes to download the files, create the tables and load the data. The 3 main tables will be loaded automatically.' style='display:' /><img src='../../images/ajax-loader.gif' id='file_load' border='0' align='left' valign='text' hspace='2' style='display:none' />Download Latest NDC Files<div id='list_result' style='display:inline'></div></div>
        <div class='file_list'></div>
    </td>
  </tr>
  <tr>
    <td>
      <div class='results_heading'>Results</div>
      <div class='file_results'></div>
    </td>
  </tr>
</table>
</form>
</div>

<script type="text/javascript">
   var success = " : Completed Successfully";
   var data_loading = "<img src='../../images/ajax-loader.gif' id='required_load' border='0' align='left' valign='text' hspace='1' style='display:none' /> Loading required data... this may take several minutes.";
   var data_exists = "Required data has been loaded.";
   var data_error = "Error loading required data... ";
   var error_response = "";
$(document).ready(function(){
  loadFileList();
  $("#file_select").change(function(){
    filename = $(":selected").val();
    htmlobj=$.ajax({url:"../../library/ajax/ndc_get_file_info.php?file_select="+filename,async:false});
    $("#file_info").html(htmlobj.responseText);
    $(".results_heading").hide();
    $(".file_results").hide();
  });
  $("#file_submit").click(function(){
    $("#data_load").show();
    filename = $("#file_select").val();
    htmlobj=$.ajax({url:"../../library/ajax/ndc_load_file_data.php?file_select="+filename,async:false});
    $("#data_load").hide();
    $(".results_heading").show();
    $(".file_results").show();
    $(".file_results").html(htmlobj.responseText);
  });

  $("#file_update").click(function(){
    $("#file_update").hide();
    $("#file_load").show();
    $("#list_result").empty();
    htmlobj=$.ajax({url:"../../library/ajax/ndc_get_directory.php?ndc=true",async:false});
    $(".results_heading").show();
    $(".file_results").show();
    if( htmlobj.responseText == "2" )
    {
	    $("#list_result").html(success);
	    loadFileList();
	    $(".file_results").html(data_loading);
<?php
$i = 0;
foreach( $ndc_info as $key => $data )
{
    if( $data['required'] )
    {
	$i++;
	$lowkey = strtolower($key);
        if( $key != 'ndc_code_list' && $key != 'listings' && $key != 'packages' )
	{
     		echo "htmlobj".$i."=$.ajax({url:'../../library/ajax/ndc_load_file_data.php?required_load=true&file_select=".$data['origin']."',async:true}); \n"; 
	        echo "error_response = htmlobj".$i.".responseText; \n";
	}
    }
}
?>
	    htmlobjdrugs=$.ajax({url:'../../library/ajax/ndc_load_file_data.php?required_load=true&file_select=LISTINGS.TXT',async:false});  
	    if( htmlobjdrugs.readyState == 4 )
	    {
		    if( htmlobjdrugs.responseText != "success" ) 
		    {
			    $(".file_results").html(data_error+htmlobjdrugs.responseText);
		    }else{  
			    htmlobjpackages=$.ajax({url:'../../library/ajax/ndc_load_file_data.php?required_load=true&file_select=PACKAGES.TXT',async:false});  
			    if( htmlobjpackages.readyState == 4 ) 
			    {
				    if( htmlobjpackages.responseText != "success" )
				    {
					    $(".file_results").html(data_error+htmlobjpackages.responseText);
				    }else{ 
					    htmlobjcodes=$.ajax({url:'../../library/ajax/ndc_load_file_data.php?required_load=true&file_select=ndc_code_list.sql',async:false});  
					    if( htmlobjcodes.readyState == 4 )
					    {
						    if( htmlobjcodes.responseText == "success" )
						    {
							    $(".file_results").html(data_exists);
						    }else{
							    $(".file_results").html(data_error+htmlobjcodes.responseText);
						    }
					    }
				    }
			    }
		    }
	    }

    }else if( htmlobj.responseText == "1" ) {
	    $("#list_result").html(success);
	    loadFileList();
	    $(".file_results").html(data_exists);
    }else{
	    $(".file_results").html(htmlobj.responseText);
    }
    if( error_response != "" ) 
	    $(".file_results").html(data_error+error_response);
    $("#file_load").hide();
    $("#file_update").show();
  });

});
function loadFileList() {
    listobj=$.ajax({url:"../../library/ajax/ndc_get_file_list.php?ndc=true",async:false});
    $(".file_list").html(listobj.responseText);
} 

</script>
</body>
</html>
