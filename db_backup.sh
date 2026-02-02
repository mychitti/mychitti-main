#!/bin/bash

# ===============================
# CONFIG
# ===============================
DATE=$(date +"%Y-%m-%d")

BASE_DIR="/root/backups"
DB_DIR="$BASE_DIR/database"

DB_NAME="mychitti_main"
DB_USER="mychitti_main"
DB_PASS="JamalS@876457881P"   # put password here

RCLONE_REMOTE="r2"
RCLONE_BUCKET="mychittibackup"

# ===============================
# PREPARE DIRECTORIES
# ===============================
mkdir -p "$DB_DIR"

# ===============================
# DATABASE BACKUP
# ===============================
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_DIR/db-$DATE.sql"

if [ $? -ne 0 ]; then
  echo " Database backup failed"
  exit 1
fi

# ===============================
# UPLOAD TO R2
# ===============================
rclone copy "$DB_DIR/db-$DATE.sql" "$RCLONE_REMOTE:$RCLONE_BUCKET/database/"

# ===============================
# RETENTION - KEEP LAST 15 FILES
# ===============================

# Local cleanup
ls -1t "$DB_DIR"/db-*.sql | tail -n +16 | xargs -r rm --

# R2 cleanup
rclone lsf "$RCLONE_REMOTE:$RCLONE_BUCKET/database/" \
  | sort -r \
  | tail -n +16 \
  | xargs -I {} rclone delete "$RCLONE_REMOTE:$RCLONE_BUCKET/database/{}"
