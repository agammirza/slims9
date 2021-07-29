<?php
/**
 * Copyright (C) 2007,2008  Arie Nugraha (dicarve@yahoo.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

/* Custom Field Management section */
/* Modified Heru Subekti (heroe.soebekti@gmail.com) */

// key to authenticate
define('INDEX_AUTH', '1');
// key to get full database access
define('DB_ACCESS', 'fa');

require '../../../sysconfig.inc.php';
// IP based access limitation
require LIB.'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-masterfile');
// start the session
require SB.'admin/default/session.inc.php';
require SB.'admin/default/session_check.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_DB/datagrid/simbio_dbgrid.inc.php';
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// privileges checking
$can_read = utility::havePrivilege('system', 'r');
$can_write = utility::havePrivilege('system', 'w');

if (!$can_read && $_SESSION['uid'] != 1) {
    die('<div class="errorBox">'.__('You don\'t have enough privileges to access this area!').'</div>');
}

/* custom field update process */
if (isset($_POST['saveData']) AND $can_read AND $can_write) {
    $label = trim(strip_tags($_POST['label']));  
    // check form validity
    if (empty($label)) {
        utility::jsToastr(__('Custom Field'), __('Field or Label can\'t be empty'), 'error');
        exit();
    } else {
        $data['primary_table'] = $dbs->escape_string(trim($_POST['table']));        
        $data['label'] = ucwords($dbs->escape_string($label));
        $data['type'] = $dbs->escape_string(trim($_POST['type']));   
        $data['is_public'] = $dbs->escape_string(trim($_POST['is_public']));  
        $data['class'] = $dbs->escape_string(trim($_POST['class']));  
        $data['note'] = $dbs->escape_string(trim($_POST['note']));
        $data['width'] = '100';
        $data['data'] = NULL;
        if($data['type'] == 'choice' || $data['type'] == 'checklist' || $data['type'] == 'dropdown'){
            if(isset($_POST['data'])){
                foreach ($_POST['data'] as $key => $value) {
                    if($value==''){
                        utility::jsToastr(__('Custom Field'), __('Data List can\'t be empty'), 'error');
                exit();
                    }
                    $arr[$key] = array($key,$value);
                }
            $data['data'] = $dbs->escape_string(serialize($arr));
            }else{
                utility::jsToastr(__('Custom Field'), __('Data List can\'t be empty'), 'error');
                exit();
            }
        } 

        // create sql op object
        $sql_op = new simbio_dbop($dbs);
        if (isset($_POST['updateRecordID'])) {
            /* UPDATE RECORD MODE */
            // filter update record ID
            $updateRecordID = $dbs->escape_string(trim($_POST['updateRecordID']));
            //get last field table
            $_q = $dbs->query("SELECT primary_table,dbfield,label FROM mst_custom_field WHERE field_id=".$updateRecordID);
            if($_q->num_rows){
                $_d = $_q->fetch_row();
                if($_d[0]!=$data['primary_table']){
                    @$dbs->query("ALTER TABLE `".$_d[0]."_custom` DROP ".$_d[1]);
                }
            }
            // update the data
            $update = $sql_op->update('mst_custom_field', $data, 'field_id=\''.$updateRecordID.'\'');
            if ($update) {
                utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' update custom field ('.$_d[2].'->'.$data['label'].') on '.$_d[0], $_d[0].' custom', 'Update');
                utility::jsToastr(__('Custom Field'), __('New Custom Field Successfully Updated'), 'success');
                echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(parent.jQuery.ajaxHistory[0].url);</script>';
            } else { 
                utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' can not update custom field ('.$_d[2].') '. $sql_op->error, $_d[0].' custom', 'Fail');
                utility::jsToastr(__('Custom Field'),__('Custom Field Data FAILED to Updated. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error, 'error'); }
            exit();
        } else {
            /* INSERT RECORD MODE */
            // insert the data
            $data['dbfield'] = 'cf_'.substr(md5(microtime()),rand(0,26),5);
            $insert = $sql_op->insert('mst_custom_field', $data);
            if ($insert) {
                utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' create custom field ('.$data['label'].') on '. $data['primary_table'], $data['primary_table'] .' custom', 'Add');
                utility::jsToastr(__('Custom Field'), __('New Custom Field Successfully Saved'), 'success');
                echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\');</script>';
            } else { 
                utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' can not create custom field ('.$data['label'].'): '. $sql_op->error , $data['primary_table'] .' custom', 'Fail');
                utility::jsToastr(__('Custom Field'),__('Custom Field Data Data FAILED to Save. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error, 'error'); }
            exit();
        }
    }
    exit();
} else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
    if (!($can_read AND $can_write)) {
        die();
    }
    // /* DATA DELETION PROCESS */
    // $sql_op = new simbio_dbop($dbs);
    // $failed_array = array();
    // $error_num = 0;
    // if (!is_array($_POST['itemID'])) {
    //     // make an array
    //     $_POST['itemID'] = array($dbs->escape_string(trim($_POST['itemID'])));
    // }
    // // loop array
    // foreach ($_POST['itemID'] as $itemID) {
    //     $itemID = $dbs->escape_string(trim($itemID));
    //     //get dbfield name
    //     $dbfield_q = $dbs->query("SELECT dbfield,primary_table,label FROM mst_custom_field WHERE field_id=".$itemID);
    //     $field = $dbfield_q->fetch_row();
    //     //drop field
    //     @$dbs->query("ALTER TABLE ".$field[1]."_custom DROP ".$field[0]."");   
    //     if (!$sql_op->delete('mst_custom_field', "field_id='$itemID'")) {
    //         $error_num++;
    //     }
    // }
    // // error alerting
    // if ($error_num == 0) {
    //     utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' remove custom field '.$field[2].' with id '.$itemID, $field[1]. ' custom', 'Delete');
    //     utility::jsToastr(__('Custom Field'), __('All Data Successfully Deleted'), 'success');
    //     echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    // } else {
    //     utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' can not remove custom field '.$field[2].' with id '.$itemID, $field[1]. ' custom', 'Fail');
    //     utility::jsToastr(__('Custom Field'), __('Some or All Data NOT deleted successfully!\nPlease contact system administrator'), 'warning');
    //     echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'?'.$_POST['lastQueryStr'].'\');</script>';
    // }
    // exit();
}
/* custom field update process end */

/* search form */
?>
<div class="menuBox">
<div class="menuBoxInner masterFileIcon">
	<div class="per_title">
	    <h2><?php echo __('Slide'); ?></h2>
  </div>
	<div class="sub_section">
	  <div class="btn-group">
      <a href="<?php echo MWB; ?>system/slide.php" class="btn btn-default"><?php echo __('Field List'); ?></a>
      <a href="<?php echo MWB; ?>system/create_slide.php" class="btn btn-default"><?php echo __('Add New Slide'); ?></a>
	  </div>
    
  </div>
</div>
</div>

<!-- /* search form end */
/* main content */ -->



<div class="container">
<!-- 
<table cellspacing="0" cellpadding="5" class="datagrid-action-bar" style="width: 100%;"><tbody><tr><td><input type="button" onclick="chboxFormSubmit('datagrid', 'Are You Sure Want to DELETE Selected Data?')" value="Delete Selected Data" class="s-btn btn btn-danger"> <input type="button" value="Check All" class="check-all button btn btn-default"> <input type="button" value="Uncheck All" class="uncheck-all button btn btn-default"> </td></tr></tbody></table> -->

<table class="table table-striped">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">Delete</th>
      <th scope="col">Edit</th>
      <th scope="col">Images</th>
      <th scope="col">Last Update</th>
    </tr>
  </thead>
  <tbody>
     <td></td>
     <td></td>
  </tbody>
</table>
</div>



<script type="text/javascript">


</script>



