#!/bin/bash
cd "`dirname $0`"

# Several times a day
cp sqlite.db backup/sqlite-`date +%Y%m%d-%H%M`.db

# Daily
tar czf backup/img-`date +%Y%m%d`.tgz img
