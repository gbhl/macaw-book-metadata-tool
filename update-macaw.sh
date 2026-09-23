#!/bin/sh
MACAW_PATH=
WWW_USER=
WWW_GROUP=
SAFE_USER=
SAFE_GROUP=

if [ ! $MACAW_PATH ]; then
    echo "Please edit this file and set the variable MACAW_PATH."
    echo "This should be the path to the index.php file for your macaw installation."
    echo "Example: /var/www/htdocs"
    exit
fi

if [ ! $WWW_USER ] || [ ! $WWW_GROUP ] || [ ! $SAFE_USER ] || [ ! $SAFE_GROUP ]; then
    echo ""
    echo "Please edit this file and set the variables WWW_USER/WWW_GROUP and SAFE_USER/SAFE_GROUP"
    echo ""
    echo "WWW_USER/WWW_GROUP should be the user that your web server runs as. For apache, look in the httpd.conf for the User setting. Usually 'apache' or 'www-data'"
    echo ""
    echo "SAFE_USER/SAFE_GROUP is usually 'nobody' and 'nobody' or 'nogroup' but should be a user other than the web server (and not root)."
    exit
fi

if [ ! `command -v curl` ]; then
    echo "curl is not installed. This script requires it."
    exit
fi

echo "Changing to temporary directory..."
pushd /tmp
rm -fr macaw-book-metadata-tool-*

echo "Getting latest code from GitHub..."
curl -o master.zip -L https://github.com/gbhl/macaw-book-metadata-tool/archive/master.zip

echo "Unzipping code from github..."
unzip -q master.zip
cd macaw-book-metadata-tool-master

echo "Copying new code into Macaw installation at $MACAW_PATH..."
sudo cp -a * $MACAW_PATH/.

echo "Changing ownership of Macaw files to $SAFE_USER:$SAFE_GROUP"
chown -R $SAFE_USER:$SAFE_GROUP $MACAW_PATH

echo "Changing ownership of Macaw log files to $WWW_USER:$WWW_GROUP"
chown -R $WWW_USER:$WWW_GROUP $MACAW_PATH/system/application/logs

echo "Updating file permissions (Step 1)"
chmod -R 755 $MACAW_PATH

echo "Updating file permissions (Step 2, this may take a while)"
find $MACAW_PATH -mount -type f -exec chmod 644 {} \;

# Cleanup
rm -fr macaw-book-metadata-tool-*
echo "Update complete!"
popd