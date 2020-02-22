#!/bin/bash
cd "`dirname $0`"

cp sqlite.db backup/`date +%Y%m%d-%H%M`.json
