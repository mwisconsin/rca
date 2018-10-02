#!/bin/bash

DB_HOST="rca-main-db.myridersclub.com"
DB_USER="ridersclubdb"
DB_NAME="ridersclubmain"
DB_PASS="Pi=3.1416"
BACKUP_PATH="/home/ridersclubuser/db_backup"
FILENAME="$(date +%F)"

mysqldump -h$DB_HOST -u$DB_USER --databases $DB_NAME --opt --password=$DB_PASS > $BACKUP_PATH/$FILENAME.sql

gzip $BACKUP_PATH/$FILENAME.sql


