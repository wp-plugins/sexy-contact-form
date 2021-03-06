<?php 
global $wpdb;

$task = isset($_REQUEST['task']) ? $_REQUEST['task'] : '';
$ids = isset($_REQUEST['ids']) ?  $_REQUEST['ids'] : array();
$filter_state = isset($_REQUEST['filter_state']) ? (int) $_REQUEST['filter_state'] : 2;
$filter_type = isset($_REQUEST['filter_type']) ? (int) $_REQUEST['filter_type'] : 0;
$filter_form = isset($_REQUEST['filter_form']) ? (int) $_REQUEST['filter_form'] : 0;
$filter_search = isset($_REQUEST['filter_search']) ? stripslashes(str_replace(array('\'','"'), '', trim($_REQUEST['filter_search']))) : '';

//unpublish task
if($task == 'unpublish') {
	if(is_array($ids)) {
		foreach($ids as $id) {
			$idk = (int)$id;
			if($idk != 0) {
				$sql = "UPDATE ".$wpdb->prefix."sexy_fields SET `published` = '0' WHERE `id` = '".$idk."'";
				$wpdb->query($sql);
			}
		}
	}
}
//publish task
if($task == 'publish') {
	if(is_array($ids)) {
		foreach($ids as $id) {
			$idk = (int)$id;
			if($idk != 0) {
				$sql = "UPDATE ".$wpdb->prefix."sexy_fields SET `published` = '1' WHERE `id` = '".$idk."'";
				$wpdb->query($sql);
			}
		}
	}
}
//delete task
if($task == 'delete') {
	if(is_array($ids)) {
		foreach($ids as $id) {
			$idk = (int)$id;
			if($idk != 0) {
				$sql = "DELETE FROM ".$wpdb->prefix."sexy_fields WHERE `id` = '".$idk."'";
				$wpdb->query($sql);
			}
		}
	}
}

//get the rows
$sql = 
		"
			SELECT 
				sp.id,
				sp.name,
				sp.published,
				sp.ordering,
				sf.name AS form, 
				sf.id AS form_id, 
				sf.id_template as id_template,
				st.name AS type, 
				st.id AS type_id
			FROM ".$wpdb->prefix."sexy_fields  sp
			LEFt JOIN ".$wpdb->prefix."sexy_forms AS sf ON sf.id=sp.id_form
			LEFT JOIN  ".$wpdb->prefix."sexy_field_types AS st ON st.id=sp.id_type 
			WHERE 1 
		";

if($filter_state == 1)
	$sql .= " AND sp.published = '1'";
elseif($filter_state == 0)
	$sql .= " AND sp.published = '0'";

if($filter_search != '') {
	if (stripos($filter_search, 'id:') === 0) {
		$sql .= " AND sp.id = " . (int) substr($filter_search, 3);
	}
	else {
		$sql .= " AND sp.name LIKE '%".$filter_search."%'";
	}
}
if($filter_type != 0) {
	$sql .= " AND sp.id_type = '".$filter_type."'";
}
if($filter_form != 0) {
	$sql .= " AND sp.id_form = '".$filter_form."'";
}

$sql .= " ORDER BY `form_id`,`ordering`,`id` ASC";
$rows = $wpdb->get_results($sql);

//get types
$types_sql = "SELECT `id`, `name` FROM `".$wpdb->prefix."sexy_field_types`";
$type_rows = $wpdb->get_results($types_sql);
//get forms
$forms_sql = "SELECT `id`, `name`, `id_template` FROM `".$wpdb->prefix."sexy_forms` order by `ordering`,`name` ";
$forms_rows = $wpdb->get_results($forms_sql);
?>
<form action="admin.php?page=sexyfields" method="post" id="wpscf_form">
<div style="overflow: hidden;margin: 0 0 10px 0;">
	<div style="float: left;">
		<select id="wpscf_filter_state" class="wpscf_select" name="filter_state">
			<option value="2" <?php if($filter_state == 2) echo 'selected="selected"';?> >Select Status</option>
			<option value="1"<?php if($filter_state == 1) echo 'selected="selected"';?> >Published</option>
			<option value="0"<?php if($filter_state == 0) echo 'selected="selected"';?> >Unpublished</option>
		</select>
		<select id="wpscf_filter_type" class="wpscf_select" name="filter_type">
			<option value="0">Select Type</option>
			<?php 
				foreach($type_rows as $type) {
					$selected = $filter_type == $type->id ? 'selected="selected"' : '';
					echo '<option value="'.$type->id.'" '.$selected.'>'.$type->name.'</option>';
				}
			?>
		</select>
		<select id="wpscf_filter_form" class="wpscf_select" name="filter_form">
			<option value="0">Select Form</option>
			<?php 
				foreach($forms_rows as $form) {
					$selected = $filter_form == $form->id ? 'selected="selected"' : '';
					echo '<option value="'.$form->id.'" '.$selected.'>'.$form->name.'</option>';
				}
			?>
		</select>
		<input type="search" placeholder="Filter items by name" value="<?php echo $filter_search;?>" id="wpscf_filter_search" name="filter_search">
		<button id="wpscf_filter_search_submit" class="button-primary">Search</button>
		<a href="admin.php?page=sexyfields"  class="button">Reset</a>
	</div>
	<div style="float:right;">
		<a href="admin.php?page=sexyfields&act=new" id="wpscf_add" class="button-primary">New</a>
		<button id="wpscf_edit" class="button button-disabled wpscf_disabled" title="Please make a selection from the list, to activate this button">Edit</button>
		<button id="wpscf_publish_list" class="button button-disabled wpscf_disabled" title="Please make a selection from the list, to activate this button">Publish</button>
		<button id="wpscf_unpublish_list" class="button button-disabled wpscf_disabled" title="Please make a selection from the list, to activate this button">Unpublish</button>
		<button id="wpscf_delete" class="button button-disabled wpscf_disabled" title="Please make a selection from the list, to activate this button">Delete</button>
	</div>
</div>
<table class="widefat" >
	<thead>
		<tr>
			<th nowrap align="center" style="width: 30px;text-align: center;"><input type="checkbox" name="toggle" value="" id="wpscf_check_all" /></th>
			<th nowrap align="center" style="width: 30px;text-align: center;">Order</th>
			<th nowrap align="center" style="width: 30px;text-align: center;">Status</th>
			<th nowrap align="left" style="text-align: left;padding-left: 22px;">Name</th>
			<th nowrap align="left" style="text-align: left;">Type</th>
			<th nowrap align="left" style="text-align: left;">Form</th>
			<th nowrap align="center" style="width: 30px;text-align: center;">Id</th>
		</tr>
	</thead>
<tbody id="wpscf_sortable" table_name="<?php echo $wpdb->prefix;?>sexy_fields" reorder_type="reorder_list">
<?php        
			$k = 0;
			for($i=0; $i < count( $rows ); $i++) {
				$row = $rows[$i];
?>
				<tr class="row<?php echo $k; ?> ui-state-default" id="option_li_<?php echo $row->id; ?>_<?php echo $row->form_id; ?>">
					<td nowrap valign="middle" align="center" style="vertical-align: middle;width: 30px;" >
						<input style="margin-left: 8px;" type="checkbox" id="cb<?php echo $i; ?>" class="wpscf_row_ch" name="ids[]" value="<?php echo $row->id; ?>" />
					</td>
					<td valign="middle" align="center" style="vertical-align: middle;width: 30px;">
						<div class="wpscf_reorder"></div>
					</td>
					<td valign="middle" align="center" style="vertical-align: middle;width: 30px;">
						<?php if($row->published == 1) {?>
						<a href="#" class="wpscf_unpublish" wpscf_id="<?php echo $row->id; ?>">
							<img src="<?php echo plugins_url( '../images/published.png' , __FILE__ );?>" alt="^" border="0" title="Published" />
						</a>
						<?php } else {?>
						<a href="#" class="wpscf_publish" wpscf_id="<?php echo $row->id; ?>">
							<img src="<?php echo plugins_url( '../images/unpublished.png' , __FILE__ );?>" alt="v" border="0" title="Unpublished" />
						</a>
						<?php }?>
					</td>
					<td valign="middle" align="left" style="vertical-align: middle;padding-left: 22px;" now_w="1">
						<a href="admin.php?page=sexyfields&act=edit&id=<?php echo $row->id;?>"><?php echo $row->name; ?></a>
					</td>
					<td valign="middle" align="left" style="vertical-align: middle;" now_w="1">
						<?php echo $row->type;?>
					</td>
					<td valign="middle" align="left" style="vertical-align: middle;" now_w="1">
						<a href="admin.php?page=sexyforms&act=edit&id=<?php echo $row->form_id;?>"><?php echo $row->form; ?></a>
					</td>
					<td valign="middle" align="center" style="vertical-align: middle;width: 30px;">
						<?php echo $row->id; ?>
					</td>
				</tr>
<?php
				$k = 1 - $k;
			} // for
?>
</tbody>
</table>
<input type="hidden" name="task" value="" id="wpscf_task" />
<input type="hidden" name="ids[]" value="" id="wpscf_def_id" />
</form>