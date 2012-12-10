<style type="text/css">
#generic {line-height: 200%;}
#generic textarea {height:30px;width: 80%;}
</style>

<div id="generic">
	<strong>Enter your metadata below</strong><br/>
	Name: <input type="text" id="generic_name" onChange="YAHOO.macaw.Generic.metadataChange(this);"><br/>
	Type: <select id="generic_type" onChange="YAHOO.macaw.Generic.metadataChange(this);">
			<option></option>
			<option>Animal</option>
			<option>Vegetable</option>
			<option>Fungus</option>
			<option>Mineral</option>
			<option>Other</option>
		  </select><br/>
	Text: <textarea id="generic_text" onChange="YAHOO.macaw.Generic.metadataChange(this);"></textarea>
</div>