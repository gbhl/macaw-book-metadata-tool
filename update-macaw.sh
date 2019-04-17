#!/bin/sh
MACAW_PATH=
WWW_USER=

if [ ! $MACAW_PATH ] 
then
	echo "Please edit this file and set the variable MACAW_PATH."
	echo "This should be the path to the index.php file for your macaw installation."
	echo "Example: /var/www/htdocs"
	exit
fi

if [ ! $MACAW_PATH ] 
then
	echo "Please edit this file and set the variable WWW_USER."
	echo "This should be the user that your web server runs as."
	echo "For apache, look in the httpd.conf for the User setting."
	exit
fi

if [ ! `command -v curl` ]
then
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
echo "Changing ownership of Macaw files to $WWW_USER"
chown -R $WWW_USER $MACAW_PATH
echo "Update complete!"
popd
