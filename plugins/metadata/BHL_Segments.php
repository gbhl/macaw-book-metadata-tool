<?php
	$segment_genres = array(
		array("1", "Article"),
		array("2", "Book"),
		array("3", "BookItem"),
		array("4", "Chapter"),
		array("8", "Conference"),
		array("6", "Issue"),
		array("5", "Journal"),
		array("14", "Letter"),
		array("9", "Preprint"),
		array("7", "Proceeding"),
		array("13", "Thesis"),
		array("11", "Treatment"),
		array("10", "Unknown"));
		
	$segment_identifiers = array(
		array("6", "Abbreviation"),
		array("31", "BioLib.cz"),
		array("16", "BioStor"),
		array("7", "BPH"),
		array("20", "Catalogue of Life"),
		array("10", "CODEN"),
		array("9", "DDC"),
		array("5", "DLC"),
		array("18", "EOL"),
		array("30", "EUNIS"),
		array("28", "GBIF Taxonomic Backbone"),
		array("19", "GNI"),
		array("13", "GPO"),
		array("24", "Index Fungorum"),
		array("34", "Index to Organism Names"),
		array("26", "Interim Reg. of Marine/Nonmarine Genera"),
		array("3", "ISBN"),
		array("2", "ISSN"),
		array("22", "ITIS"),
		array("36", "JSTOR"),
		array("14", "MARC001"),
		array("12", "NAL"),
		array("17", "NameBank"),
		array("23", "NCBI"),
		array("11", "NLM"),
		array("35", "OAI"),
		array("1", "OCLC"),
		array("37", "Soulsby"),
		array("33", "The International Plant Names Index"),
		array("8", "TL2"),
		array("32", "Tropicos"),
		array("25", "Union 4"),
		array("15", "VIAF"),
		array("21", "Wikispecies"),
		array("4", "WonderFetch"),
		array("27", "WoRMS"),
		array("29", "ZooBank"));
		
	function createOption($option) {
		return "<option value=\"{$option[0]}\">{$option[1]}</option>'";
	}
?>


<style type="text/css">
	#bhl-segments {line-height: 200%;}
	#bhl-segments textarea {height:30px;width: 80%;}

	#bhl-segments td label img {
		cursor: pointer;
		padding-bottom: 5px;
	}
	#bhl-segments td input,
	#bhl-segments td select {
		width: 100%;
		box-sizing: border-box;
		-webkit-box-sizing:border-box;
		-moz-box-sizing: border-box;
	}
	
	#bhl-segments .list {
		vertical-align: top;
	}
	#bhl-segments .list ul {
		margin: 0;
	}
	#bhl-segments .author-list {
		background-color: #FFFFFF;
		border: 1px solid #000000;
		height: 100px;
		overflow-y: scroll;
		padding: 5px 3px;
	}
	#bhl-segments .author-list li {
		margin: -5px 0 -5px 0px;
	}
	#bhl-segments .identifier-list li,
	#bhl-segments .keyword-list li {
		display: inline;
		float: left;
		padding-right: 10px;
	}
	
	#bhl-segments .remove-button {
		background: url(../images/icons/delete_grey.png) no-repeat;
		cursor: pointer;
		float: left;
		height: 16px;
		width: 16px;
		margin: 4px 4px 0 0;
	}
	
	#bhl-segments .remove-button:hover {
		background: url(../images/icons/delete.png) no-repeat;
	}
		
	#bhl-segments .yui-dt-col-extra {
		white-space:nowrap;
	}
	
	.yui-skin-sam .yui-ac-container,
	.yui-skin-sam .yui-ac-content ul {
		width: 100%;
	}
	#bhl-segments .yui-skin-sam .yui-ac-content {
		width: 100%;
		border: 1px solid #808080;
	}
</style>

<div id="bhl-segments">
	<table border="0" cellspacing="0" cellpadding="3" width="100%" style="height: 146px">
		<tr>
			<td width="55%" valign="top">
				<table border="0" cellspacing="0" cellpadding="3" width="100%">
					<tr>
						<td><label for="segment_title">Title:</label></td>
						<td colspan="3">
							<input type="text" id="segment_title" onChange="YAHOO.macaw.BHL_Segments.metadataChange(this);" title="Segment Title">
						</td>
					</tr>
					<tr>
						<td><label for="segment_external_url">External URL:</label></td>
						<td>
							<input type="text" id="segment_external_url" onChange="YAHOO.macaw.BHL_Segments.metadataChange(this);" title="Segment Title">
						</td>
						<td><label for="segment_download_url">Download URL:</label></td>
						<td>
							<input type="text" id="segment_download_url" onChange="YAHOO.macaw.BHL_Segments.metadataChange(this);" title="Segment Title">
						</td>
					</tr>
					<tr>
						<td><label for="segment_genre">Genre:</label></td>
						<td>
							<select id="segment_genre" onChange="YAHOO.macaw.BHL_Segments.metadataChange(this);">
								<option value=""></option>
								<?php echo(implode('', array_map('createOption', $segment_genres))); ?>
							</select>
						</td>
						<td><label for="segment_doi">DOI:</label></td>
						<td>
							<input type="text" id="segment_doi" onChange="YAHOO.macaw.BHL_Segments.metadataChange(this);" title="Segment Title">
						</td>
					</tr>
					<tr>
						<td id="tdIdentifier" colspan="2" width="25%">
							<label for="segment_identifiers">Identifiers: 
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" id="btnShowIdentifierDlg" class="icon">
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearIdentifierType" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';"></label>
							</label>
						</td>
						<td id="tdKeyword" colspan="2" width="25%">
							<label for="segment_keywords">Keywords: 
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" id="btnShowKeywordDlg" class="icon">
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearKeywordType" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';"></label>
							</label>
						</td>						
					</tr>
					<tr>
						<td colspan="2" class="list">
							<ul id="segment_identifiers" class="identifier-list"></ul>
						</td>
						<td colspan="2" class="list">
							<ul id="segment_keywords" class="keyword-list"></ul>
						</td>						
					</tr>
				</table>
			</td>
			<td width="45%" valign="top" height="100%">
				<table border="0" cellspacing="0" cellpadding="3" width="100%">
					<tr>
						<td id="tdAuthor">
							<label for="segment_authors">Authors: 
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/add.png" id="btnShowAuthorDlg" class="icon">
								<img src="<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png" id="btnClearAuthorType" class="icon" onMouseOver="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete.png';" onMouseOut="this.src='<?php echo $this->config->item('base_url'); ?>images/icons/page_white_delete_grey.png';"></label>
							</label>
						</td>
					</tr>
					<tr>
						<td class="list">
							<ul id="segment_authors" class="author-list"></ul>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<div id="dlgAuthor" style="display:none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">
			<table border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr>
					<td width="70%">
						<strong>Name:</strong> <a ID="segment_author_source" target="_blank" style="visibility: hidden;"></a>
						<div>
							<input type="text" id="segment_author_name">
							<div id="segment_author_name_autocomplete"></div>
						</div>
					</td>
					<td width="30%">
						<strong>Dates:</strong>
						<input type="text" id="segment_author_dates">
					</td>
				</tr>
			</table>
			
			<table border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<strong>Identifier Type:</strong> <br>
						<select id="segment_author_identifier_type">
							<option value=""></option>
							<?php echo(implode('', array_map('createOption', $segment_identifiers))); ?>
						</select>
					</td>
					<td>
						<strong>Value:</strong>
						<input type="text" id="segment_author_identifier_value">
					</td>
				</tr>
			</table>
		</div>
	</div>
	<div id="dlgIdentifier" style="display:none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">
			<strong>Type:</strong><br>
			<select id="segment_identifier_type">
				<option value=""></option>
				<?php echo(implode('', array_map('createOption', $segment_identifiers))); ?>
			</select><br>
			<strong>Value:</strong><br>
			<input type="text" id="segment_identifier_value"><br>
		</div>
	</div>
	<div id="dlgKeyword" style="display:none;margin-top:-200px">
		<div style="border: 1px solid #999; padding: 5px; line-height: 1.7; margin: 0 0 5px;">
			<strong>Keyword:</strong><br>
			<input type="text" id="segment_keyword"><br>
		</div>
	</div>
</div>