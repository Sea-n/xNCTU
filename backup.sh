#!/bin/bash
cd "`dirname $0`"

# Several times a day
mysqldump xnctu |gzip > backup/database/database-`date +%Y%m%d-%H%M`.sql.gz
