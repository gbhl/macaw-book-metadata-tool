<?php
	// Reminder: The site configuration is available in the $config variable.
	$page_types =array(
		'Appendix', 'Blank', 'Chart', 'Cover', 'Errata', 'Foldout',
		'Illustration', 'Index', 'Issue Start', 'List of Illustrations', 'Map', 
		'Table of Contents', 'Text', 'Title Page', 'Specimen'
	);
	$piece_types = array('Issue', 'No.', 'Part', 'Suppl.');
	$page_sides = array('Left (verso)', 'Right (recto)');

	function option($val) {
		return '<option value="'.$val.'">'.$val.'</option>';
	}
	function quote($val) {
		return '"'.$val.'"';
	}
?>


<style type="text/css">
.yui-dt-col-extra {
	white-space:nowrap;
}
</style>

	<table border="0" cellspacing="0" cellpadding="3" width="100%" style="height: 146px">
		<tr>
			<td width="50%" valign="top">
				<table border="0" cellspacing="0" cellpadding="3" width="100%">
					<tr>
						<td ><label for="page_number">Page&nbsp;Prefix</label></td>
						<td  id="tdPagePrefix">
							<input type="text" name="page_prefix" id="page_prefix" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();" title="Page Number Prefix">
							Num:&nbsp;<input type="text" style="width:3em;" name="page_number" id="page_number" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();" title="Page Number Value">
							<input type="checkbox" name="page_number_implicit" id="page_number_implicit" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();" title="Implied Page Number?"> <em id="page_number_implicit_text">impl.</em>
							<img src="<?php echo $this->config->item('base_url'); ?>images/icons/application_form_edit.png" id="btnShowPagesDlg" class="icon">&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearPageNumber" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';">
						</td>
					</tr>
					<tr>
						<td nowrap>
						<label for="page_type">Page&nbsp;Type:&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" id="btnShowAddPageTypeDlg" class="icon">&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearPageType" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';"></label><br>
						<a id="page-type-defs" href="https://s.si.edu/BHLpagetypes" title="Page Type Definitions" target="_blank">(Page Type Defintions)</a>
						</td>
						<td>
							<div id="page_types"></div>
							<div class="clear"><!-- --></div>
						</td>
					</tr>
					<tr>
						<td><label for="year">Year:</label></td>
						<td>
							<input type="text" maxlength="4" name="year" id="year" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();">&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearYear" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';">
						</td>
					</tr>
					<tr>
						<td><label for="volume">Level&nbsp;1:</label></td>
						<td id="tdYearVolume">
							<input type="text" name="volume" id="volume" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();">&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearVolume" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';">
						</td>
					</tr>
					<tr>
						<td nowrap><label for="piece">Level&nbsp;2:</label></td>
						<td>
              <input type="text" name="piece_text" id="piece_text" value="" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();">&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearPieceText" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';">
						</td>
					</tr>
					<tr>
						<td><label for="page_side">Page&nbsp;Side:</label></td>
						<td>
							<select id="page_side" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();">
								<option value=""></option>
								<?php echo(implode('', array_map('option', $page_sides))); ?>
							</select>&nbsp;<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearPageSide" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';">
						</td>
					</tr>
				</table>
			</td>
			<td width="50%" valign="top">
				<table border="0" cellspacing="0" cellpadding="3" width="100%">
					<tr>
						<td>
							<label for="notes">Macaw&nbsp;Notes&nbsp;about&nbsp;Page&nbsp;(will&nbsp;not&nbsp;export&nbsp;to&nbsp;IA):</label><br>
							<textarea name="notes" id="notes" onChange="YAHOO.macaw.Standard_Metadata.metadataChange(this);" onFocus="focusOn(this);" onBlur="focusOff();"></textarea>
						</td>
					</tr>
				</table>
				<input id="foobar" style="display:none">
			</td>
		</tr>
	</table>
	<div id="dlgHTMLSelectPageType" style="display: none">
		Type: <select id="selPageType" onChange="YAHOO.macaw.Standard_Metadata.evtAddPageType(this);" onFocus="focusOn(this);" onBlur="focusOff();">
			<option value=""></option>
			<?php echo(implode('', array_map('option', $page_types))); ?>
		</select>
	</div>
	<div id="dlgPageNumbering" style="display: none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">
			<strong>Prefix:</strong>
			<input type="text" id="page_number_prefix" size="30" maxlength="128" value="Page"><br>

			<strong>Numbering:</strong>
			<select id="page_number_style">
				<option value="arabic">1, 2, 3, 4...</option>
				<option value="roman">I, II, III, IV...</option>
				<option value="roman_lower">i, ii, iii, iv...</option>
				<option value="roman_long">I, II, III, IIII...</option>
				<option value="roman_lower_long">i, ii, iii, iiii...</option>
			</select><br>

			<blockquote>
				<table border="0" width="100%" cellspacing="0" cellpadding="0">
					<tr>
						<td>Start Counting At: </td>
						<td><input type="text" id="page_number_start" size="3" maxlength="5" value="1"></td>
					</tr>
					<tr>
						<td>Increment By: </td>
						<td><input type="text" id="page_number_increment" size="3" maxlength="5" value="1"></td>
					</tr>
					<tr>
						<td>Pages Per Image: </td>
						<td><input type="text" id="pages_per_image" size="3" maxlength="5" value="1"></td>
					</tr>
				</table>
				<input type="hidden" id="pages_per_image" value="1">
			</blockquote>
		</div>

		Implied Page Number <input type="checkbox" id="implicit" value="1" onFocus="focusOn(this);" onBlur="focusOff();">
	</div>
