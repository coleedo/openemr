<?php
/*************************************************************
display escripts from lists table
*************************************************************/
include_once("../../globals.php");
include_once("$srcdir/sql.inc");

// set the default sort method for the list of escripts
if (!$sortby) { $sortby = 'status'; }

// set the default value of 'administered_by'
if (!$administered_by && !$administered_by_id) {
    $stmt = "select concat(lname,', ',fname) as full_name ".
            " from users where ".
            " id='".$_SESSION['authId']."'";
    $row = sqlQuery($stmt);
    $administered_by = $row['full_name'];
}
?>
<html>
<head>
<?php html_header_show();?>

<!-- supporting javascript code -->
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>

<!-- page styles -->
<link rel="stylesheet" href="<?php echo $css_eprescribing;?>" type="text/css">

<script language="JavaScript">
// required to validate date text boxes
var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';
</script>
</head>
<body class="body">

<?php
if (!$GLOBALS['concurrent_layout']) { ?>
    <a href="patient_summary.php" target="Main" onclick="top.restoreSession()">
    <span class="typehead"><?php xl('E-scripts','e'); ?></span>
    <span class=back><?php echo $tback;?></span></a>
<?php
}

$order = "asc";

if( !$_GET['sortby'] )
    $order_by = "activity DESC, begdate DESC, date DESC";
else
    $order_by = $_GET['sortby'] . " " . $order .", begdate DESC, date DESC";
?>


    <table border=0 cellpadding=3 cellspacing=3>

    <!-- some columns are sortable -->
    <tr class="escript_list_head">
    <th nowrap>
        <a href="javascript:top.restoreSession();location.href='escripts.php?sortby=title&order=<?=$order?>';" title="Sort by E-script"><?php xl('E-script','e'); ?></a>
        <span class='small' style='align:left; font-family:arial'><?php if ($sortby == 'title') { echo 'v'; } ?></span>
    </td>
    <th nowrap><?php xl('Dosage','e'); ?></td>
    <th  nowrap style='align:left'>
        <a href="javascript:top.restoreSession();location.href='escripts.php?sortby=begdate&order=<?=$order?>';" title="Sort by Start Date"><?php xl('Start Date','e'); ?></a>
        <span class='small' style='align:left; font-family:arial'><?php if ($sortby == 'begdate') { echo 'v'; } ?></span>
    </td>
    <th  nowrap style='align:center'>
        <a href="javascript:top.restoreSession();location.href='escripts.php?sortby=extrainfo&order=<?=$order?>';" title="Sort by Refills"><?php xl('Refills','e'); ?></a>
        <span id='small' style='font-family:arial'><?php if ($sortby == 'extrainfo') { echo 'v'; } ?></span>
    </td>
    <th  nowrap style='align:center'>
        <a href="javascript:top.restoreSession();location.href='escripts.php?sortby=activity&order=<?=$order?>';" title="Sort by Active"><?php xl('Active','e'); ?></a>
        <span class='small' style='font-family:arial'><?php if($sortby == 'activity') { echo 'v'; } ?></span>
    </td>
    <th  nowrap style='align:right'>
        <a href="javascript:top.restoreSession();location.href='escripts.php?sortby=provider&order=<?=$order?>';" title="Sort by Provider"><?php xl('Provider','e'); ?></a>
        <span class='small' style='font-family:arial'><?php if ($sortby == 'provider') { echo 'v'; } ?></span>
    </td>

    </tr>

<?php
        $sql = "SELECT l.title, l.comments, l.begdate, l.extrainfo, l.activity, concat(u.lname,', ',u.fname) as provider
        FROM  lists l
        LEFT JOIN users u on l.user = u.id
        WHERE l.pid = " .
                mysql_real_escape_string($pid) .
                " AND l.type = 'escript'" .
                " ORDER BY " . mysql_real_escape_string($order_by);

        $active = "";
        $result = sqlStatement($sql);
        while($row = sqlFetchArray($result)) {

        // encount is used to toggle the color of the table-row output below
        ++$encount;
        $bgclass = (($encount & 1) ? "escript_list" : "escript_list_alt");
        echo "<tr class='$bgclass' id='".$row["id"]."'>";

            ($row["activity"]) ? $active = "Yes" : $active = "No";
            $info = $row['extrainfo'];
            if($info != "")
            {
                if( $get_refills = strpos($info,'Refills:') )
                {
                    $refills = substr($info,$get_refills+8);
                }
            }
            echo "<td align=left>" . $row["title"] . "</td>";
            echo "<td align=left>" . $row["comments"] . "</td>";
            echo "<td align=center>" . $row["begdate"] . "</td>";
            echo "<td align=center>" . $refills . "</td>";
            echo "<td align=center>" . $active . "</td>";
            echo "<td align=right nowrap>" . $row["provider"] . "</td>";
            echo "</tr>";
        }
?>

    </table>
</body>
</html>
