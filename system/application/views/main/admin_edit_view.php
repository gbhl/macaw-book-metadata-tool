<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Macaw</title>
	<?php $this->load->view('global/head_view') ?>
	<script type="text/javascript">
    /* Initialization script, called when the page is ready */
		function init() {
      MessageBox.init();
			var obtnSave = new YAHOO.widget.Button("btnSave");
			obtnSave.on('click', FIELDS.submit, 'save');
		}
		YAHOO.util.Event.onDOMReady(init);
		var FIELDS = {};
		FIELDS.submit = function(action, val) {
			Dom.get('edit_form').submit();
		}

	</script>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<?php $this->load->view('global/error_messages_view') ?>

	<div id="edit">
			<h1>Admin Edit Item</h1>
			<form action="<?php echo $this->config->item('base_url'); ?>main/admin_edit_save" method="post" id="edit_form" enctype="multipart/form-data">
			<input type="hidden" name="id" value="<?php echo($id) ?>">
			<input type="hidden" name="action" id="save_action" value="save">

			<table id="newfields">
        <tr class="row">
					<td colspan="2" class="fieldname" style="color: #900;font-size:120%;text-align:center">Use with caution. Incorrect changes <em>will</em> have unintended side effects.</td>
				</tr>
        <tr class="row">
					<td class="fieldname">Identifier:</td>
					<td>
						<?php echo($identifier) ?>&nbsp;&nbsp;&nbsp;&nbsp;(<strong>Macaw ID:</strong> <?php echo($id) ?>)
					</td>
				</tr>
				<tr class="row">
					<td class="fieldname">Item Status</td>
					<td>
            <select name="new_export_status" id="new_export_status">
              <?php 
                $current = '';
                foreach ($all_statuses as $k => $v) {
                  if ($v == $status_code) { 
                    $current = $k;
                    echo "<option value=\"$v\" selected=\"selected\">$k</option>";
                  } else {
                    echo "<option value=\"$v\">$k</option>";
                  }
                } 
              ?>
            </select>
						<?php echo('Current: '.$current); ?>
					</td>
				</tr>
				<tr class="row">
					<td class="fieldname">Item Export Status</td>
					<td>
            <table>
              <?php foreach ($export_modules as $export) { ?>
                <tr>
                  <td><?php echo $export['module_name']; ?></td>
                  <td>
                    <select name="new_<?php echo $export['module_name']; ?>">
                    <?php 
                      foreach ($export['statuses'] as $k => $v) {
                        if ($v == $export['current']) { 
                          echo "<option value=\"$v\" selected=\"selected\">$k</option>";
                        } else {
                          echo "<option value=\"$v\">$k</option>";
                        }
                      } 
                    ?>
                    </select>
                    <?php echo('Current: '.$export['current']); ?>
                  </td>
                </tr>
              <?php } ?>
            </table>

					</td>
				</tr>
			</table> 
		</form>
		<div class="savebutton">
			<button id="btnSave">Save</button>
		</div>
	</div>
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
