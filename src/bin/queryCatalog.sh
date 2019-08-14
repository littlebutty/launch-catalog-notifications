#!/bin/bash

SCRIPT=`realpath $0`;
SCRIPTPATH=`dirname $SCRIPT`;

EXEC="php $PHPPATH $SCRIPTPATH/queryCatalog.php";
$EXEC;

exit;