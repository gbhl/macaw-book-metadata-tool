# Dropzone.js Migration Guide

## Overview
This document describes the migration from jQuery File Upload to Dropzone.js for the image upload functionality in the scan module.

## Changes Made

### 1. New Upload View
- **File**: `system/application/views/scan/upload_view_dropzone.php`
- Replaced jQuery File Upload with Dropzone.js
- Updated jQuery from 1.11.3 to 3.6.0 (CDN)
- Updated Dropzone from CDN (v5.9.3)

### 2. New Dropzone Configuration
- **File**: `js/main-dropzone.js`
- Handles all Dropzone initialization and events
- Preserves all original functionality:
  - File counters for ordering
  - Sequence number tracking
  - CSRF token passing
  - PDF detection and polling
  - Existing file listing
  - Progress tracking
  - Action button management

### 3. Custom Styling
- **File**: `css/dropzone-custom.css`
- Provides Bootstrap-compatible styling
- Responsive design
- Visual feedback for drag/drop and upload states

### 4. Controller Update
- **File**: `system/application/controllers/scan.php`
- Updated `upload()` function to use `upload_view_dropzone` instead of `upload_view_jquery`

## Features Preserved

✅ Drag and drop file upload
✅ Click to select files
✅ File counter tracking
✅ Sequence number passing
✅ CSRF token protection
✅ PDF processing with polling
✅ Existing files display
✅ Progress bar
✅ Cancel button
✅ Page metadata button
✅ Insert missing pages button
✅ Parallel uploads (3 concurrent)
✅ 1GB file size limit
✅ Proper error handling

## New Features

✨ Modern jQuery 3.x compatibility
✨ No jQuery dependency for Dropzone
✨ Better browser support
✨ Cleaner, more maintainable code
✨ Modern Fetch API for file listing
✨ Improved UX with visual feedback

## Compatibility

- **Browsers**: All modern browsers (Chrome, Firefox, Safari, Edge)
- **jQuery**: Works with jQuery 3.6.0 and newer
- **No breaking changes**: Existing backend code remains unchanged

## Testing Checklist

- [ ] Upload a single image file
- [ ] Upload multiple images at once (drag and drop)
- [ ] Cancel an upload in progress
- [ ] Upload a PDF and verify processing message appears
- [ ] Verify "Enter Page Metadata" button appears after upload
- [ ] Verify "Insert Missing Pages" button appears for books with existing pages
- [ ] Test with large files (approaching 1GB limit)
- [ ] Test with invalid file types (should show error)
- [ ] Verify CSRF token validation (should not get token mismatch errors)
- [ ] Verify existing files are loaded on page load
- [ ] Test on mobile browsers

## Reverting to jQuery File Upload (if needed)

If you need to revert to the old jQuery File Upload version:

1. Change the view in `controllers/scan.php`:
   ```php
   $this->load->view('scan/upload_view_jquery', $data);
   ```

2. The old files are still in place:
   - `system/application/views/scan/upload_view_jquery.php`
   - `js/main.js`

## Performance Notes

- Dropzone.js is smaller than jQuery File Upload (reduces dependencies)
- Modern jQuery 3.x is more performant than 1.11.3
- No change to backend processing or upload speed

## Troubleshooting

### Issue: CSRF token validation fails
- Verify meta tags are present in page source: `<meta name="csrf-name">` and `<meta name="csrf-token">`
- Check browser console for JavaScript errors

### Issue: Files not uploading
- Check network tab in browser DevTools
- Verify upload directory permissions
- Check server logs for backend errors

### Issue: Existing files not loading
- Verify `/scan/do_upload/` endpoint is accessible
- Check CSRF token in GET request (should be sent as query parameter or header)

## Future Improvements

- Consider using Uppy.js for even more modern implementation
- Add drag-and-drop zone highlighting
- Add file type validation on client side
- Add retry logic for failed uploads
