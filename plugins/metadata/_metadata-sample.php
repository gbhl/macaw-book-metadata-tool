<style type="text/css">
#SAMPLE {line-height: 200%;}
#SAMPLE textarea {height:30px;width: 80%;}
</style>

<div id="SAMPLE">
	<strong>Enter your metadata below</strong><br/>
	Name: <input type="text" id="SAMPLE_name" onChange="YAHOO.macaw.SAMPLE.metadataChange(this);"><br/>
	Type: <select id="SAMPLE_type" onChange="YAHOO.macaw.SAMPLE.metadataChange(this);">
			<option></option>
			<option>Animal</option>
			<option>Vegetable</option>
			<option>Fungus</option>
			<option>Mineral</option>
			<option>Other</option>
		  </select><br/>
	Text: <textarea id="SAMPLE_text" onChange="YAHOO.macaw.SAMPLE.metadataChange(this);"></textarea>
</div>