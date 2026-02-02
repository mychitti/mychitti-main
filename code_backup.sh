# ===============================
# CONFIG
# ===============================
DATE=$(date +"%Y-%m-%d")
PROJECT_DIR="/var/www/html/mychitti"
BACKUP_DIR="/root/backups"

# ===============================
# CODE BACKUP
# ===============================
tar \
--exclude="$PROJECT_DIR/storage/app/public" \
--exclude="$PROJECT_DIR/storage/framework" \
--exclude="$PROJECT_DIR/storage/logs" \
--exclude="$PROJECT_DIR/vendor" \
--exclude="$PROJECT_DIR/bootstrap/cache" \
--exclude="$PROJECT_DIR/node_modules" \
-czf $BACKUP_DIR/code/code-$DATE.tar.gz \
$PROJECT_DIR

# ===============================
# UPLOAD TO R2
# ===============================
rclone copy $BACKUP_DIR/code/code-$DATE.tar.gz r2:mychittibackup/code

# ===============================
# RETENTION - KEEP LAST 15 FILES
# ===============================
# Local cleanup (code)
ls -tp $BACKUP_DIR/code/code-*.tar.gz | grep -v '/$' | tail -n +16 | xargs -r rm --

# R2 cleanup (code)
rclone ls r2:mychittibackup/code | sort -k2 | head -n -15 | awk '{print $2}' | xargs -I {} rclone delete r2:mychittibackup/code/{}

