curl https://rclone.org/install.sh | sudo bash
rclone version
rclone config
n/s/q> n
name> r2
Storage> s3
provider> Cloudflare
env_auth> (empty)
access key id
secret key
region> auto
endpoint> https://3318fb042558ddf6cf85c10c23d5e2d0.r2.cloudflarestorage.com

Edit advanced config?
y) Yes
n) No (default)
y/n> n

rclone ls r2:

mkdir -p /root/backups

nano /root/backups/backup.sh (see code snippets from backup.sh)

<!-- make file  executable  -->
chmod +x /root/backup_to_r2.sh 
chmod +x /root/backups/backup.sh

<!-- run file  -->
/root/backups/backup.sh (in db server)
/root/backup_to_r2.sh (in mychitti server)

<!-- set cron (for automatic backup ) -->
 crontab -e
 0 2 */2 * * /root/backups/backup.sh >> /root/backups/backup.log 2>&1 ( database server)
 0 2 */2 * * /root/backup_to_r2.sh >> /root/backups/backup.log 2>&1 ( mychitti server)

