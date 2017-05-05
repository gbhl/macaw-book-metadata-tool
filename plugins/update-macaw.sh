#!/bin/sh

MACAW_PATH=
WWW_USER=

if [ -n $MACAW_PATH ]; then
    echo "ERROR! Please edit this script and set the MACAW_PATH variable to the directory that contains macaw."
    exit 1
fi

if [ -n $WWW_USER ]; then
    echo "ERROR! Please edit this script and set the WWW_USER variable to the user that your web server runs as."
    exit 1
fi


echo CHDIR
pushd /tmp
echo CLEAN
rm -fr macaw-book-metadata-tool-master
echo CURL
curl -O -L https://github.com/cajunjoel/macaw-book-metadata-tool/archive/master.zip
echo UNZIP
unzip -q master.zip
cd macaw-book-metadata-tool-master
echo COPY
sudo cp -a * $MACAW_PATH/. 
echo CHOWN
chown -R $WWW_USER $MACAW_PATH
popd

