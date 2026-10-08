Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	var loadingPDF = false;
	var fileCounter = 0;
	var dropzoneInstance = null;

	// Get CSRF token from meta tags
	function getCSRFToken() {
		var csrfNameEl = document.querySelector('meta[name="csrf-name"]');
		var csrfTokenEl = document.querySelector('meta[name="csrf-token"]');
		var csrfName = csrfNameEl ? csrfNameEl.getAttribute('content') : null;
		var csrfToken = csrfTokenEl ? csrfTokenEl.getAttribute('content') : null;
		return { name: csrfName, value: csrfToken };
	}

	// Initialize Dropzone
	dropzoneInstance = new Dropzone('form#dropzoneForm', {
		autoProcessQueue: false,
		maxFilesize: 1073741824,
		parallelUploads: 3,
		uploadMultiple: false,
		acceptedFiles: '.png,.tiff,.tif,.jp2,.pdf,.jpg,.jpeg,.webp',
		addRemoveLinks: false,
		timeout: 300000,
		createImageThumbnails: true
	});

	console.log('Dropzone initialized');

	// Initialize existing files on load
	loadExistingFiles();

	// Handle file added
	dropzoneInstance.on('addedfile', function(file) {
		fileCounter++;
		file.counter = fileCounter;
		console.log('File added:', file.name, 'Counter:', file.counter);
		updateStartButtonState();
	});

	// Handle file removed
	dropzoneInstance.on('removedfile', function(file) {
		console.log('File removed:', file.name);
		updateStartButtonState();
	});

	// Handle sending file
	dropzoneInstance.on('sending', function(file, xhr, formData) {
		console.log('Sending file:', file.name);
		// Add sequence number
		var sequence = document.getElementById('sequence').value;
		formData.append('sequence', sequence);
		formData.append('counter[]', file.counter);

		// Add CSRF token
		var csrf = getCSRFToken();
		if (csrf.name && csrf.value) {
			formData.append(csrf.name, csrf.value);
			console.log('CSRF token added:', csrf.name);
		} else {
			console.warn('CSRF token not found!');
		}
	});

	// Handle successful upload
	dropzoneInstance.on('success', function(file, response) {
		console.log('Upload success:', response);
		if (response && response.files && response.files[0]) {
			var fileInfo = response.files[0];
			if (response.reload) {
				if (!loadingPDF) {
					loadingPDF = true;
					showPDFMessage('Status: ' + response.message);
					setTimeout(function() {
						dropzoneInstance.removeAllFiles(true);
						loadExistingFiles();
					}, 3000);
				}
			}
		}
	});

	// Handle error
	dropzoneInstance.on('error', function(file, errorMessage, xhr) {
		console.error('Upload error:', errorMessage);
		if (xhr) {
			console.error('XHR response:', xhr);
		}
	});

	// Handle upload complete
	dropzoneInstance.on('complete', function(file) {
		if (dropzoneInstance.getUploadingFiles().length === 0) {
			if (!loadingPDF) {
				updateActionButtons();
				document.getElementById('cancelUpload').disabled = true;
			}
		}
	});

	// Handle queue complete
	dropzoneInstance.on('queuecomplete', function() {
		console.log('Queue complete');
		if (!loadingPDF) {
			loadExistingFiles();
			updateActionButtons();
			document.getElementById('cancelUpload').disabled = true;
		}
	});

	// Button handlers
	document.getElementById('startUpload').addEventListener('click', function(e) {
		e.preventDefault();
		console.log('Start upload clicked, queued files:', dropzoneInstance.getQueuedFiles().length);
		if (dropzoneInstance.getQueuedFiles().length > 0) {
			document.getElementById('uploadProgress').style.display = 'block';
			document.getElementById('startUpload').disabled = true;
			document.getElementById('cancelUpload').disabled = false;
			dropzoneInstance.processQueue();
		} else {
			alert('Please select files first');
		}
	});

	document.getElementById('cancelUpload').addEventListener('click', function(e) {
		e.preventDefault();
		console.log('Cancel upload clicked');
		dropzoneInstance.removeAllFiles(true);
		document.getElementById('uploadProgress').style.display = 'none';
		document.getElementById('progressBar').style.width = '0%';
		document.getElementById('progressText').textContent = '0%';
		updateStartButtonState();
	});

	// Track upload progress
	dropzoneInstance.on('uploadprogress', function(file, progress, bytesSent) {
		var totalFiles = dropzoneInstance.files.filter(f => f.status === Dropzone.UPLOADING || f.status === Dropzone.QUEUED).length;
		if (totalFiles === 0) totalFiles = 1;

		var totalProgress = 0;
		dropzoneInstance.files.forEach(function(f) {
			if (f.upload && f.status !== Dropzone.SUCCESS) {
				totalProgress += f.upload.progress || 0;
			}
		});

		var avgProgress = totalProgress / totalFiles;
		document.getElementById('progressBar').style.width = avgProgress + '%';
		document.getElementById('progressText').textContent = Math.round(avgProgress) + '%';
	});

	// Metadata button
	document.querySelector('.btn-metadata').addEventListener('click', function(e) {
		e.preventDefault();
		window.location.href = '/scan/review';
	});

	// Missing pages button
	document.querySelector('.btn-missing').addEventListener('click', function(e) {
		e.preventDefault();
		window.location.href = '/scan/missing/insert';
	});

	function updateStartButtonState() {
		var hasFiles = dropzoneInstance.getQueuedFiles().length > 0;
		document.getElementById('startUpload').disabled = !hasFiles;
	}

	function updateActionButtons() {
		var metadataBtn = document.querySelector('.btn-metadata');
		var missingBtn = document.querySelector('.btn-missing');

		if (hasMissingPages) {
			missingBtn.style.display = 'inline-block';
			metadataBtn.style.display = 'none';
		} else {
			metadataBtn.style.display = 'inline-block';
			missingBtn.style.display = 'none';
		}
	}

	function showPDFMessage(message) {
		var pdfMessage = document.getElementById('pdfmessage');
		pdfMessage.textContent = message;
		pdfMessage.style.display = 'block';
	}

	function hidePDFMessage() {
		var pdfMessage = document.getElementById('pdfmessage');
		pdfMessage.style.display = 'none';
	}

	function loadExistingFiles() {
		console.log('Loading existing files');
		fetch('/scan/do_upload/', {
			method: 'GET',
			headers: {
				'Accept': 'application/json'
			}
		})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('HTTP error, status = ' + response.status);
			}
			return response.json();
		})
		.then(function(result) {
			console.log('Files loaded:', result);
			displayExistingFiles(result.files || []);

			if (result.reload) {
				loadingPDF = true;
				showPDFMessage('Status: ' + result.message);
				setTimeout(function() {
					loadExistingFiles();
				}, 3000);
			} else {
				if (loadingPDF) {
					loadingPDF = false;
					hidePDFMessage();
					updateActionButtons();
				}
			}
		})
		.catch(function(error) {
			console.error('Error loading files:', error);
		});
	}

	function displayExistingFiles(files) {
		var filesList = document.getElementById('filesList');
		filesList.innerHTML = '';

		if (!files || files.length === 0) {
			filesList.innerHTML = '<p style="color: #999; padding: 10px;">No existing files</p>';
			return;
		}

		var html = '<table class="table table-striped"><tbody>';
		files.forEach(function(file) {
			html += '<tr>';
			html += '<td>' + escapeHtml(file.name) + '</td>';
			html += '<td style="text-align: right; width: 100px;">' + formatFileSize(file.size) + '</td>';
			html += '</tr>';
		});
		html += '</tbody></table>';
		filesList.innerHTML = html;
	}

	function formatFileSize(bytes) {
		if (bytes === 0) return '0 Bytes';
		var k = 1024;
		var sizes = ['Bytes', 'KB', 'MB', 'GB'];
		var i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
});
