<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Scan | Upload | Macaw</title>

	<?php $this->load->view('global/head_view') ?>

</head>
<body>	
<?php $this->load->view('global/header_view') ?>
<form name="placeholder">
	<hidden name="bookid" value="<?php echo( $this->session->userdata('barcode')); ?>"></hidden>
</form>

<div id="uploadcontrols">
	<h1>Upload Pages</h1>
	<div id="uploadcontrolsbody">
		<p>
			Upload PDFs or image files for this item to the Macaw server.<br><br>
			The maximum size for each file is <strong><?php echo($upload_max_filesize) ?></strong>
		</p>
		<div id="uiElements" style="display:inline;">
				<div id="uploaderContainer">
					<div id="uploaderOverlay" style="position:absolute; z-index:2"></div>
					<div id="selectFilesLink" style="z-index:1"><a id="selectLink" href="#">Select Files</a></div>			
					<div id="uploadFilesLink"><a id="uploadLink" onClick="upload(); return false;" href="#">Upload Files</a></div>
				</div>				
		</div>	
		<div id="simUploads" style="display:none;"> Number of simultaneous uploads:
			<select id="simulUploads">
				<option value="1">1</option>
				<option value="2">2</option>
				<option value="3">3</option>
				<option value="4">4</option>
			</select>
		</div>
		<div id="uploadComplete">
			<input type="checkbox" id="chkStartImport"/>
			<label for="chkStartImport"> Start import when upload is complete</label>
		</div>
	</div>
</div>	

<div id="dataTableContainer" class="uploaddatacontainer"></div>

<script type="text/javascript">
	YAHOO.util.Event.onDOMReady(function () { 
		MessageBox.init();
		var uiLayer = YAHOO.util.Dom.getRegion('selectLink');
		var overlay = YAHOO.util.Dom.get('uploaderOverlay');
		YAHOO.util.Dom.setStyle(overlay, 'width', uiLayer.right-uiLayer.left + "px");
		YAHOO.util.Dom.setStyle(overlay, 'height', uiLayer.bottom-uiLayer.top + "px");
	});
	//tracking variables for the number of files to be uploaded, and the actual number uploaded.
	var totalFiles = 0;
	var totalFileUploaded = 0;
	
	// Custom URL for the uploader swf file (same folder).
	YAHOO.widget.Uploader.SWFURL = sBaseUrl + "/inc/swf/uploader.swf";
	
	// Instantiate the uploader and write it to its placeholder div.
	var uploader = new YAHOO.widget.Uploader( "uploaderOverlay" );
	
	// Add event listeners to various events on the uploader.
	// Methods on the uploader should only be called once the 
	// contentReady event has fired.
	
	uploader.addListener('contentReady', handleContentReady);
	uploader.addListener('fileSelect', onFileSelect)
	uploader.addListener('uploadProgress', onUploadProgress);
	uploader.addListener('uploadComplete', onUploadComplete);
	uploader.addListener('uploadCompleteData', onUploadResponse);
	uploader.addListener('uploadError', onUploadError);
//	uploader.addListener('uploadStart', onUploadStart);
//	uploader.addListener('uploadCancel', onUploadCancel);
// 	uploader.addListener('rollOver', handleRollOver);
// 	uploader.addListener('rollOut', handleRollOut);
// 	uploader.addListener('click', handleClick);
	
	// Variable for holding the filelist.
	var fileList;

	// ===========================================================
	// When contentReady event is fired, you can call methods on the uploader.
	// ===========================================================
	function handleContentReady () {
		// Allows the uploader to send log messages to trace, as well as to YAHOO.log
		uploader.setAllowLogging(true);
		
		// Allows multiple file selection in "Browse" dialog.
		uploader.setAllowMultipleFiles(true);
		
		// New set of file filters.
		var ff = new Array(
			{description:"Images", extensions:"*.jpg;*.png;*.gif;*.jp2;*.jpf;*.jpeg;*.tif;*.tiff"},
			{description:"PDF", extensions:"*.pdf;*.PDF"}
		);
		
		// Apply new set of file filters to the uploader.
		uploader.setFileFilters(ff);
	}
	
	// ===========================================================
	// Actually uploads the files. In this case,
	// uploadAll() is used for automated queueing and upload 
	// of all files on the list.
	// You can manage the queue on your own and use "upload" instead,
	// if you need to modify the properties of the request for each
	// individual file.
	// ===========================================================
	function upload() {
		totalFileUploaded = 0;
		if (fileList != null) {
			oForm = document.forms["placeholder"];
			var hiddenbook = document.getElementsByName("bookid")[0].attributes.value;
			var hiddenbookvalue = hiddenbook.value;		
			//var postvariable = {macaw_session:document.cookie.match(/macaw_session=[^;]+/), bookid:"VicNatVol3"};
			var postvariable = {macaw_session:document.cookie.match(/macaw_session=[^;]+/), bookid:hiddenbookvalue};
			//var hiddenbookid = oForm.elements["bookid"];
			//var bookidvalue = hiddenbookid.value;
			//postvariable.bookid = bookidvalue;
			
			//postvariable.Cookie = document.cookie;
			
			//Should disable upload and select files at this point.
			
			uploader.setSimUploadLimit(parseInt(document.getElementById("simulUploads").value));
			uploader.uploadAll(sBaseUrl + "/scan/do_batch_upload", "POST", postvariable, "Filedata");			
		}	
	}
	
	// ===========================================================
	// Fired when the user selects files in the "Browse" dialog
	// and clicks "Ok".
	// ===========================================================
	function onFileSelect(event) {
		if('fileList' in event && event.fileList != null) {
			fileList = event.fileList;
			createDataTable(fileList);
		}
	}
	
	// ===========================================================
	// ===========================================================
	function createDataTable(entries) {
		rowCounter = 0;
		this.fileIdHash = {};
		this.dataArr = [];
		for(var i in entries) {
			var entry = entries[i];
			entry["progress"] = "<div style='height:8px;width:100%;background-color:#CCC;'><\/div>";
			dataArr.unshift(entry);
		}
		//Get count of all files to be uploaded to know when upload is complete.
		totalFiles = dataArr.length;
			for (var j = 0; j < dataArr.length; j++) {
			this.fileIdHash[dataArr[j].id] = j;
		}
		
		var myColumnDefs = [
			{key:"name", label: "File Name", sortable:false},
			{key:"size", label: "Size", sortable:false},
			{key:"progress", label: "Upload progress", sortable:false}
		];
		
		this.myDataSource = new YAHOO.util.DataSource(dataArr);
		this.myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
		this.myDataSource.responseSchema = {
			fields: ["id","name","created","modified","type", "size", "progress"]
		};
		
		this.singleSelectDataTable = new YAHOO.widget.DataTable("dataTableContainer",
			myColumnDefs, this.myDataSource, {
			caption:"Files to Upload",
			selectionMode:"single"
		});
	}
	
	// ===========================================================
	// Do something on each file's upload progress event.
	// ===========================================================
	function onUploadProgress(event) {
		rowNum = fileIdHash[event["id"]];
		prog = Math.round(100*(event["bytesLoaded"]/event["bytesTotal"]));
		progbar = "<div style='height:8px;width:100%;background-color:#CCC;'><div style='height:8px;background-color:#4F7E97;width:" + prog + "%;'><\/div><\/div>";
		singleSelectDataTable.updateRow(rowNum, {name: dataArr[rowNum]["name"], size: dataArr[rowNum]["size"], progress: progbar});	
	}
	
	// ===========================================================
	// Do something when each file's upload is complete.
	// ===========================================================
	function onUploadComplete(event) {
		rowNum = fileIdHash[event["id"]];
		prog = Math.round(100*(event["bytesLoaded"]/event["bytesTotal"]));
		progbar = "<div style='height:8px;width:100%;background-color:#CCC;'><div style='height:8px;background-color:#4F7E97;width:100%'><\/div><\/div>";
		singleSelectDataTable.updateRow(rowNum, {name: dataArr[rowNum]["name"], size: dataArr[rowNum]["size"], progress: progbar});
	}
	
	// ===========================================================
	// Do something if a file upload throws an error.
	// (When uploadAll() is used, the Uploader will
	// attempt to continue uploading.
	// ===========================================================
	function onUploadError(event) {
	
	}
	
	// ===========================================================
	// Do something when data is received back from the server.
	// ===========================================================
	function onUploadResponse(event) {
		totalFileUploaded = totalFileUploaded + 1;
		if (totalFileUploaded == totalFiles){		
			if (document.getElementById("chkStartImport").checked){
				window.location.replace(sBaseUrl + "/scan/monitor?start=1");
			} else {
				alert("All Files Uploaded");
			}	
		}
	}
</script>

</body>
