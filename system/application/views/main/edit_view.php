<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Macaw</title>
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		/* Initialization script, called when the page is ready */
		function init() {
			MessageBox.init();

			var obtnSave = new YAHOO.widget.Button("btnSave");
			obtnSave.on('click', FIELDS.submit, 'save');

			var obtnDelete = new YAHOO.widget.Button("btnDelete");
			obtnDelete.on('click', FIELDS.submit, 'delete');
			
			var obtnAddField = new YAHOO.widget.Button("btnAddField");
			obtnAddField.on('click', FIELDS.addField);

			// Add event handlers to mark the page as modified.
			var fields = YAHOO.util.Dom.getElementsByClassName('txt-fieldname');
			for (i=0; i<fields.length; i++) {
				YAHOO.util.Event.addListener(fields[i], "change", FIELDS.setModified);
			}
			var fields = YAHOO.util.Dom.getElementsByClassName('txt-fieldvalue');
			for (i=0; i<fields.length; i++) {
				YAHOO.util.Event.addListener(fields[i], "change", FIELDS.setModified);
			}

			// Add the event to cancel window close if there are changes.
			window.onbeforeunload = function () {
				if (FIELDS.modified) {
					return "You have unsaved changes.";
				}
			}
		}
		YAHOO.util.Event.onDOMReady(init);
		var FIELDS = {};
		FIELDS.newCount = 1;
		FIELDS.submit = function(action, val) {
			if (val == 'delete') {
				Dom.get('save_action').value = 'delete';
				FIELDS.modified = 0;
				Dom.get('edit_form').submit();				
			} else {
				if (FIELDS.validate()) {
					FIELDS.modified = 0;
					Dom.get('edit_form').submit();
				}
			}
		}
		FIELDS.modified = 0;
		FIELDS.setModified = function() {
			FIELDS.modified = 1;
		}
		FIELDS.addField = function() {
			var tbl = Dom.get('newfields');
			FIELDS.newCount++;

			// Create a new table row
			// SCS 02 July 2012  - Changed to reflect new layout - spans instead of table rows
			
			var tr = Dom.get(document.createElement('tr'));
			tr.id = 'newfields_'+FIELDS.newCount;
			Dom.addClass(tr,'row');
			// Create and style new table cells
			var td1 = Dom.get(document.createElement('td'));
			Dom.addClass(td1, 'fieldname');
			var td2 = Dom.get(document.createElement('td'));
			var td3 = Dom.get(document.createElement('td'));
			var td4 = Dom.get(document.createElement('td')); // this may not be needed as buttons combined into one span
			
			// Create a new text entry field
			var txtFn = Dom.get(document.createElement('input'));
			txtFn.name = 'new_fieldname_' + FIELDS.newCount;
			txtFn.id = 'new_fieldname_' + FIELDS.newCount;
			txtFn.type = 'text';
			txtFn.class = 'txt-fieldname';
			YAHOO.util.Event.addListener(txtFn, "change", FIELDS.setModified);

			// Create a new text entry field
			var txtVl = Dom.get(document.createElement('input'));
			txtVl.name = 'new_value_' + FIELDS.newCount;
			txtVl.id = 'new_value_' + FIELDS.newCount;
			txtVl.type = 'text';
			txtFn.class = 'txt-fieldvalue';
			YAHOO.util.Event.addListener(txtVl, "change", FIELDS.setModified);

			// Create a new file upload field
			var txtFl = Dom.get(document.createElement('input'));
			txtFl.name = 'new_value_' + FIELDS.newCount + '_file';
			txtFl.id = 'new_value_' + FIELDS.newCount + '_file';
			txtFl.type = 'file';
			Dom.setStyle(txtFl, 'display', 'none');
			txtFn.class = 'txt-fieldvalue';
			YAHOO.util.Event.addListener(txtFl, "change", FIELDS.setModified);
					
			// Create a new anchor element
			var a1 = Dom.get(document.createElement('a'));
			a1.href = 'javascript:FIELDS.deleteField(\'newfields_'+FIELDS.newCount+'\')';

			var a2 = Dom.get(document.createElement('a'));
			a2.href = 'javascript:FIELDS.toggleNewField(\''+FIELDS.newCount+'\')';
		
			// Create a new delete image
			var img1 = document.createElement('img');
			img1.src = sBaseUrl+'/images/icons/delete.png';
			img1.border = 0;
			img1.width = 16;
			img1.height = 16;
			img1.title = 'Delete metadata field';
			
			var img2 = document.createElement('img');
			img2.src = sBaseUrl+'/images/icons/page_add.png';
			img2.id = 'upload_toggle_' + FIELDS.newCount;
			img2.border = 0;
			img2.width = 16;
			img2.height = 16;
			img2.title = 'Upload File';
			
			// Hook it all together
			a1.appendChild(img1);
			a2.appendChild(img2);
			//Delete link not in 4th span any more
		//	td4.appendChild(a1);
			td3.appendChild(a2);
			//delete link added to third span
			td3.appendChild(a1);
			td2.appendChild(txtVl);
			td2.appendChild(txtFl);
			td1.appendChild(txtFn);
			tr.appendChild(td1);
			tr.appendChild(td2);
			tr.appendChild(td3);
			//tr.appendChild(td4);
			tbl.appendChild(tr);
		}
		
		FIELDS.deleteField = function(id) {
			var el = Dom.get(id);
			var parent = el.parentNode;
			parent.removeChild(el);
		}

		FIELDS.toggleNewField = function(id) {
			if (Dom.getStyle('new_value_' + id, 'display') == 'none') {
				Dom.setStyle('new_value_' + id, 'display', 'inline');
				Dom.setStyle('new_value_' + id + '_file', 'display', 'none');
				Dom.setAttribute('upload_toggle_' + id,'src',sBaseUrl+'/images/icons/page_add.png');
				Dom.setAttribute('upload_toggle_' + id,'title','Upload File');
				Dom.get('new_value_' + id + '_file').value = '';
			} else {
				Dom.setStyle('new_value_' + id, 'display', 'none');
				Dom.setStyle('new_value_'  + id + '_file', 'display', 'inline');
				Dom.setAttribute('upload_toggle_' + id,'src',sBaseUrl+'/images/icons/text_allcaps.png');			
				Dom.setAttribute('upload_toggle_' + id,'title','Input text');
				
				Dom.get('new_value_' + id).value = '';
			}
		}
		
		FIELDS.validate = function() {
			var fields = document.getElementsByTagName('input');
			var m;
			var found = false;
			for (i=0; i < fields.length; i++) {
				if (m = fields[i].name.match(/^new_fieldname_(\d+)$/)) {
			     	Dom.setStyle(fields[i], 'background-color', '#fff');
					var elValue = Dom.get('new_value_'+m[1]);
					var elFile = Dom.get('new_value_'+m[1]);
					if (fields[i].value == '' && (elValue.value != '' || elFile.value != '')) {
						found = true;
						Dom.setStyle(fields[i], 'background-color','#fcc');
					}
				}
			}
			if (found) {
				General.showErrorMessage('Please enter a fieldname for all metadata values.');
				return false;
			}
			return true;
		}
	</script>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>
	<? $this->load->view('global/error_messages_view') ?>

	<div id="edit">
		<? if ($new) { ?>
			<h1 >Add Item</h1>
			<form action="<? echo $this->config->item('base_url'); ?>main/add_save" method="post" id="edit_form" enctype="multipart/form-data">
		<? } else { ?>
			<h1>Edit Item</h1>
			<form action="<? echo $this->config->item('base_url'); ?>main/edit_save" method="post" id="edit_form" enctype="multipart/form-data">
			<input type="hidden" name="id" value="<? echo($id) ?>">
			<input type="hidden" name="action" id="save_action" value="save">
		<? } ?>

			<table id="newfields">
				<tr class="row">
					<td class="fieldname">Identifier:</td>
					<td>
					<? if ($new) { ?>
						<input type="text" name="identifier"  value="<? echo($identifier) ?>">
					<? } else { ?>
						<? echo($identifier) ?>
					<? } ?>
					</td>
				</tr>
				<tr class="row">
					<td class="fieldname">Organization:</td>
					<td><? echo($organization) ?></td>
				</tr>
				<? if ($is_qa_user) { ?>
				<tr class="row">
					<td class="fieldname">Needs QA: </td>
					<td><input type="checkbox" name="needs_qa" id="needs_qa" value="1" <? if ($needs_qa) { echo("checked"); } ?>> This item will be reviewed for Quality Assurance</td>
				</tr>
				<? } else { ?>
				<tr class="row">
					<td class="fieldname">Needs QA:</td>
					<td>This item <? if ($needs_qa) { echo("will"); } else { echo("will not"); } ?> be reviewed for Quality Assurance</td>
				</tr>
				<? } ?>
				<tr class="row">
					<td class="fieldname">Copyright:</td>
					<td>
						<select name="copyright[]">
						<?php	foreach ($copyright_values as $c) {?>
							<option value="<?php echo($c['value']) ?>" <?php echo ($c['value'] == $copyright ? 'selected' : ''); ?>><?php echo($c['title']) ?></option>
						<?php } ?>
						</select>
					</td>
				</tr>
					
				<?  $c = 0;
					foreach ($metadata as $i) { ?>
					<tr class="row" id="existing_field_<? echo($c) ?>">
						<td class="fieldname"><? echo($i['fieldname']); ?>:</td>
						<td>
						<? if (strlen($i['value']) > 100) { ?>
						<textarea name="<? echo($i['fieldname']); ?>[]" rows="5" cols="83" class="txt-fieldvalue"><? echo(htmlspecialchars($i['value'])); ?></textarea>
					</td>
					<td>
						<a href="javascript:FIELDS.deleteField('existing_field_<? echo($c); ?>')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/delete.png" title="Delete field"></a>
						<? } else { ?>
						<input type="text" name="<? echo($i['fieldname']); ?>[]"  value="<? echo(htmlspecialchars($i['value'])); ?>" class="txt-fieldvalue">
					</td>
					<td>
						<a href="javascript:FIELDS.deleteField('existing_field_<? echo($c); ?>')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/delete.png" title="Delete field"></a>
						<? } ?>
						</td>
					</tr>
				<?  $c++;
						}
					$counter = 900;
					foreach ($missing_metadata as $mod => $fields) {
						foreach ($fields as $f) {
							$counter++;
				?>
						<tr class="row" id="newfields_<? echo($counter); ?>">
							<td class="fieldname"><input type="text" name="new_fieldname_<? echo($counter); ?>" maxlength="32" value="<? echo($f); ?>" class="txt-fieldname"></td>
							<td>
								<input type="text" name="new_value_<? echo($counter); ?>"	   id="new_value_<? echo($counter); ?>"  value="" class="txt-fieldvalue">
								<input type="file" name="new_value_<? echo($counter); ?>_file"  id="new_value_<? echo($counter); ?>_file" style="display:none" class="txt-fieldvalue">
							</td>
							<td>
								<a href="javascript:FIELDS.toggleNewField('<? echo($counter); ?>')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/page_add.png" title="Upload File" id="upload_toggle_<? echo($counter); ?>"></a>
								<a href="javascript:FIELDS.deleteField('newfields_<? echo($counter); ?>')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/delete.png" title="Delete field"></a>
							</td>
						</tr>
				<?
						}
				} ?>
				<tr class="row" id="newfields_1">
					<td class="fieldname"><input type="text" name="new_fieldname_1"  maxlength="32" value="" class="txt-fieldname"></td>
					<td>
						<input type="text" name="new_value_1"	   id="new_value_1"  value="" class="txt-fieldvalue">
						<input type="file" name="new_value_1_file"  id="new_value_1_file" style="display:none" class="txt-fieldvalue">
					</td>
					<td>
						<a href="javascript:FIELDS.toggleNewField('1')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/page_add.png" title="Upload File" id="upload_toggle_1"></a><a href="javascript:FIELDS.deleteField('newfields_1')"><img src="<? echo $this->config->item('base_url'); ?>images/icons/delete.png" title="Delete field"></a>
					</td>
				</tr>
			</table> <!-- close of div = newfields -->
			<div class="addbutton"><button id="btnAddField">Add New Row</button></div>
		</form>
		<div class="savebutton">
			<button id="btnSave">Save</button>
			<? if ($is_admin || $is_local_admin) { ?>
			<button id="btnDelete">Delete Item</button>
			<? } ?>
		</div>

	</div>
	<? $this->load->view('global/footer_view') ?>
</body>
</html>
