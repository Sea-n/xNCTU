#!/bin/bash
cd "`dirname $0`"

# Several times a day
mysqldump xnctu |gzip > backup/database-`date +%Y%m%d-%H%M`.sql.gz

# Daily
tar czf backup/img-`date +%Y%m%d`.tgz img
