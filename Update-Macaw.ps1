# Define variables
$MACAW_PATH = "C:\inetpub\wwwroot" # Change this for your installation, no trailing slash
$TEMP_PATH = New-TemporaryFile | % { Remove-Item $_; New-Item -ItemType Directory -Path $_ }

# Note may need to run: Set-ExecutionPolicy -ExecutionPolicy RemoteSigned
# before running this script.   


# Check if MACAW_PATH is set
if (-not $MACAW_PATH) {
    Write-Host "Please edit this file and set the variable MACAW_PATH."
    Write-Host "This should be the path to the index.php file for your Macaw installation."
    Write-Host "Example: C:\inetpub\wwwroot"
    Exit
}


# Change to a temporary directory and clean out any old macaw files
Write-Host "Changing to temporary directory..."
Push-Location -Path "$TEMP_PATH" 

# Get latest Macaw code
Write-Host "Getting latest code from GitHub..."
Invoke-WebRequest -Uri "https://github.com/gbhl/macaw-book-metadata-tool/archive/master.zip" -OutFile "master.zip"

# Expand the ZIP file
Write-Host "Unzipping code from GitHub..."
Expand-Archive -Path "master.zip" -DestinationPath "."

# Copy files to the web root
Set-Location -Path "macaw-book-metadata-tool-master"
Write-Host "Copying new code into Macaw installation at $MACAW_PATH..."
Copy-Item -Path "*" -Destination "$MACAW_PATH" -Recurse -Force

# Update file permissions
Write-Host "Changing group ownership of Macaw files to IIS_USRS"
$accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule("IIS_IUSRS", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow")
$acl = Get-ACL "$MACAW_PATH"
$acl.AddAccessRule($accessRule)
Set-ACL -Path "$MACAW_PATH" -ACLObject $acl

# Return from whence we came
Pop-Location

# Clean up
Write-Host "Deleting temporary files"
Remove-Item -Recurse -Force "$TEMP_PATH"

Write-Host "Update complete!"
Pop-Location