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
// if (isset($_POST['saveData']) AND $can_read AND $can_write) {
//     $label = trim(strip_tags($_POST['label']));  
//     // check form validity
//     if (empty($label)) {
//         utility::jsToastr(__('Create Slide'), __('Field or Label can\'t be empty'), 'error');
//         exit();
//     } else {
//         $data['primary_table'] = $dbs->escape_string(trim($_POST['table']));        
//         $data['label'] = ucwords($dbs->escape_string($label));
//         $data['type'] = $dbs->escape_string(trim($_POST['type']));   
//         $data['is_public'] = $dbs->escape_string(trim($_POST['is_public']));  
//         $data['class'] = $dbs->escape_string(trim($_POST['class']));  
//         $data['note'] = $dbs->escape_string(trim($_POST['note']));
//         $data['width'] = '100';
//         $data['data'] = NULL;
//         if($data['type'] == 'choice' || $data['type'] == 'checklist' || $data['type'] == 'dropdown'){
//             if(isset($_POST['data'])){
//                 foreach ($_POST['data'] as $key => $value) {
//                     if($value==''){
//                         utility::jsToastr(__('Create Slide'), __('Data List can\'t be empty'), 'error');
//                 exit();
//                     }
//                     $arr[$key] = array($key,$value);
//                 }
//             $data['data'] = $dbs->escape_string(serialize($arr));
//             }else{
//                 utility::jsToastr(__('Create Slide'), __('Data List can\'t be empty'), 'error');
//                 exit();
//             }
//         } 

//         // create sql op object
//         $sql_op = new simbio_dbop($dbs);
//         if (isset($_POST['updateRecordID'])) {
//             /* UPDATE RECORD MODE */
//             // filter update record ID
//             $updateRecordID = $dbs->escape_string(trim($_POST['updateRecordID']));
//             //get last field table
//             $_q = $dbs->query("SELECT primary_table,dbfield,label FROM mst_custom_field WHERE field_id=".$updateRecordID);
//             if($_q->num_rows){
//                 $_d = $_q->fetch_row();
//                 if($_d[0]!=$data['primary_table']){
//                     @$dbs->query("ALTER TABLE `".$_d[0]."_custom` DROP ".$_d[1]);
//                 }
//             }
//             // update the data
//             $update = $sql_op->update('mst_custom_field', $data, 'field_id=\''.$updateRecordID.'\'');
//             if ($update) {
//                 utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' update custom field ('.$_d[2].'->'.$data['label'].') on '.$_d[0], $_d[0].' custom', 'Update');
//                 utility::jsToastr(__('Custom Field'), __('New Custom Field Successfully Updated'), 'success');
//                 echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(parent.jQuery.ajaxHistory[0].url);</script>';
//             } else { 
//                 utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' can not update custom field ('.$_d[2].') '. $sql_op->error, $_d[0].' custom', 'Fail');
//                 utility::jsToastr(__('Custom Field'),__('Custom Field Data FAILED to Updated. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error, 'error'); }
//             exit();
//         } else {
//             /* INSERT RECORD MODE */
//             // insert the data
//             $data['dbfield'] = 'cf_'.substr(md5(microtime()),rand(0,26),5);
//             $insert = $sql_op->insert('mst_custom_field', $data);
//             if ($insert) {
//                 utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' create custom field ('.$data['label'].') on '. $data['primary_table'], $data['primary_table'] .' custom', 'Add');
//                 utility::jsToastr(__('Custom Field'), __('New Custom Field Successfully Saved'), 'success');
//                 echo '<script type="text/javascript">parent.jQuery(\'#mainContent\').simbioAJAX(\''.$_SERVER['PHP_SELF'].'\');</script>';
//             } else { 
//                 utility::writelogs($dbs, 'staff', $_SESSION['uid'], 'System', $_SESSION['realname'].' can not create custom field ('.$data['label'].'): '. $sql_op->error , $data['primary_table'] .' custom', 'Fail');
//                 utility::jsToastr(__('Custom Field'),__('Custom Field Data Data FAILED to Save. Please Contact System Administrator')."\nDEBUG : ".$sql_op->error, 'error'); }
//             exit();
//         }
//     }
//     exit();
// } else if (isset($_POST['itemID']) AND !empty($_POST['itemID']) AND isset($_POST['itemAction'])) {
//     if (!($can_read AND $can_write)) {
//         die();
//     }
//     /* DATA DELETION PROCESS */
//     $sql_op = new simbio_dbop($dbs);
//     $failed_array = array();
//     $error_num = 0;
//     if (!is_array($_POST['itemID'])) {
//         // make an array
//         $_POST['itemID'] = array($dbs->escape_string(trim($_POST['itemID'])));
//     }
//     // loop array
//     foreach ($_POST['itemID'] as $itemID) {
//         $itemID = $dbs->escape_string(trim($itemID));
//         //get dbfield name
//         $dbfield_q = $dbs->query("SELECT dbfield,primary_table,label FROM mst_custom_field WHERE field_id=".$itemID);
//         $field = $dbfield_q->fetch_row();
//         //drop field
//         @$dbs->query("ALTER TABLE ".$field[1]."_custom DROP ".$field[0]."");   
//         if (!$sql_op->delete('mst_custom_field', "field_id='$itemID'")) {
//             $error_num++;
//         }
//     }
    // error alerting
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
    
// }
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
      <a href="<?php echo MWB; ?>system/slider.php" class="btn btn-default"><?php echo __('Field List'); ?></a>
      <a href="<?php echo MWB; ?>system/create_slide.php" class="btn btn-default"><?php echo __('Add New Slide'); ?></a>
	  </div>
    
  </div>
</div>
</div>
<?php
/* search form end */
/* main content */


?>

<form name="mainForm" id="mainForm" class="simbio_form_maker" method="post" action="#" target="submitExec" enctype="multipart/form-data">
	<input type="hidden" name="csrf_token" value="14ff7cab765731bacc748154f119e96aa2d2d18900f0e751ed1a75356ab4ef05">
	<input type="hidden" name="form_name" value="mainForm">
	<table cellspacing="0" cellpadding="3" style="width: 100%;">
		<tbody>
			<tr>
				<td>
					<input type="submit" class="s-btn btn btn-primary" name="saveData" value="Save">&nbsp;</td>
				<td class="edit-link-area">&nbsp;&nbsp;</td>
			</tr>
		</tbody>
	</table>
	<table id="dataList" class="s-table table">
		<tbody>
            <tr row="3" style="cursor: pointer;">
                <td width="20%" valign="top" class="alterCell font-weight-bold">Slide Image</td>
                <td width="1%" valign="top" class="alterCell font-weight-bold">:</td>
                <td width="79%" class="alterCell2">
                    <div class="custom-file col-3">
                        <input type="file" name="image" id="image" value="" class="custom-file-input">
                        <label class="custom-file-label" for="customFile">Choose file</label>
                    </div>
                    <div class="mt-2 ml-2">Maximum 500 KB</div>
                    <script>
                        $('.custom-file input').on('change',function(){
                            //get the file name
                            const fileName = $(this).val();
                            //replace the "Choose a file" label
                            $(this).next('.custom-file-label').html(fileName);
                        });
                    </script>
                </td>
            </tr>
            <tr id="simbioFormRowtable" row="0" style="cursor: pointer;">
				<td width="20%" valign="top" class="alterCell font-weight-bold">Slide Transition</td>
				<td width="1%" valign="top" class="alterCell font-weight-bold">:</td>
				<td width="79%" class="alterCell2">
					<select name="table" id="table" class="form-control col-3">
						<option value="" selected="">Fade</option>
						<option value="">Slide Left</option>
                        <option value="">Slide Right</option>
                        <option value="">Slide Up</option>
                        <option value="">Slide Down</option>
                        <option value="">Zoom In</option>
                        <option value="">Burn</option>
                        <option value="">Flash</option>
					</select>
				</td>
			</tr>
            <tr id="simbioFormRowtable" row="0" style="cursor: pointer;">
				<td width="20%" valign="top" class="alterCell font-weight-bold">Slide Animation</td>
				<td width="1%" valign="top" class="alterCell font-weight-bold">:</td>
				<td width="79%" class="alterCell2">
					<select name="table" id="table" class="form-control col-3">
						<option value="" selected="">None</option>
						<option value="">Random</option>
                        <option value="">Kenburns</option>
                        <option value="">Kenburns Up</option>
                        <option value="">Kenburns Down</option>
                        <option value="">Kenburns Left</option>
                        <option value="">Kenburns Right</option>
                        <option value="">Kenburns Up-Left</option>
                        <option value="">Kenburns Up-Right</option>
                        <option value="">Kenburns Down-Left</option>
                        <option value="">Kenburns Down-Right</option>
					</select>
                </td>
			</tr>
			
		</tbody>
	</table>
	<table cellspacing="0" cellpadding="3" style="width: 100%;">
		<tbody>
			<tr>
				<td>
					<input type="submit" class="s-btn btn btn-primary" name="saveData" value="Save">&nbsp;</td>
				<td class="edit-link-area">&nbsp;&nbsp;</td>
			</tr>
		</tbody>
	</table>
</form>






