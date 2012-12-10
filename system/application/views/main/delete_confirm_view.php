<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>Macaw</title>
	<? $this->load->view('global/head_view') ?>
	<script type="text/javascript">
		/* Initialization script, called when the page is ready */
		var FIELDS = {
			submitDelete: function(action, val) {
				if (val == 'confirm') {
					Dom.get('save_action').value = 'confirm';
				}
				Dom.get('confirm_form').submit();				
			},

			initDeleteConfirm: function() {
				var obtnConfirm = new YAHOO.widget.Button("btnConfirm");
				obtnConfirm.on('click', FIELDS.submitDelete, 'confirm');
	
				var obtnCancel = new YAHOO.widget.Button("btnCancel");
				obtnCancel.on('click', FIELDS.submitDelete, 'cancel');			
			}		
		};

		YAHOO.util.Event.onDOMReady(FIELDS.initDeleteConfirm);
	</script>
</head>
<body class="yui-skin-sam">
	<? $this->load->view('global/header_view') ?>
	<? $this->load->view('global/error_messages_view') ?>

	<div id="edit">
			<h1>Confirm Delete Item</h1>
			<form action="<? echo $this->config->item('base_url'); ?>main/delete" method="post" id="confirm_form" enctype="multipart/form-data">
			<input type="hidden" name="action" id="save_action" value="cancel">
			<input type="hidden" name="barcode" value="<? echo($identifier) ?>">
			
			
			<div id="deletewarning">
				<h3>You are about to delete this item!</h3>

				<h2>
					<table cellspacing="0" cellpadding="2">
						<tr>
							<td class="fieldname">Identifier</td>
							<td><? echo($identifier) ?></td>
						</tr>
						<tr>
							<td class="fieldname">Title:</td>
							<td><? echo($title) ?></td>
						</tr>
						<tr>
							<td class="fieldname">Organization:</td>
							<td><? echo($organization) ?></td>
						</tr>
					</table>
				</h3>

				<h4>This item has <? echo($database_rows) ?> database records and  <? echo($file_count) ?> files.</h4>
	
				<h4>Are you sure you want to continue?</h4>

				<p><input type="checkbox" name="backup" value="1" checked> Make a backup of the item before deleting.<br>(You will be able to download the item on the next page)</p>
			</div>
			
		</form>
		<div class="savebutton">
			<button id="btnCancel">Cancel</button>
			<button id="btnConfirm">Yes, Delete This Item</button>
		</div>

	</div>
	<? $this->load->view('global/footer_view') ?>
</body>
</html>
