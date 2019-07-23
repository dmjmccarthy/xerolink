# Xero Link
### PHP Configuration / Extensions
In development, required the following to make PHP work:
+ memory_limit = 1G
+ extension_dir = "ext"
+ extension=curl
+ extension=gd2
+ extension=mbstring
+ extension=openssl
+ extension=pdo_mysql
+ extension=pdo_sqlite
+ extension=sockets
+ cacert.pem from https://curl.haxx.se/docs/caextract.html
+ curl.cainfo="C:/PHP7/cacert.pem"
+ openssl.cafile="C:/PHP7/cacert.pem"
+ php_pdo_sqlsrv_73_nts_x64