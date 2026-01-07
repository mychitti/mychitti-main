#!/bin/bash
(crontab -l | grep -v "/usr/bin/php /home/mychitti/public_html/artisan dm:disbursement") | crontab -
(crontab -l ; echo "18 11 * * * /usr/bin/php /home/mychitti/public_html/artisan dm:disbursement") | crontab -
(crontab -l | grep -v "/usr/bin/php /home/mychitti/public_html/artisan store:disbursement") | crontab -
(crontab -l ; echo "18 11 * * * /usr/bin/php /home/mychitti/public_html/artisan store:disbursement") | crontab -
