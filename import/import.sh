#/bin/bash

cd /home/otislejourdain/public_html

drush/drush php-script import/data-import.php
drush/drush image-flush --all

