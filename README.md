# userresolving

This admin tool checks the database users if there is an existing entry in the Active Directory.

It marks non existing users in the Active Directory in an aditional field as 'n'.
a CSV file will be generated in the configured location.

Options:
-v, --verbose         	Print verbose progress information
-h, --help            	Print out this help
-f=filename, --filename	name of the file to be created (can be set in config.php)
-n, --noheader		  	writes no headerrow on top of the CSV file

Example:
\$ sudo -u www-data /usr/bin/php moodle/admin/tool/cli/userresolving.php

The tool is customised for our needs that the HTW Chur. 
Only works with the userid of your home organisations (fh-htwchur.ch).

Place your service configuration in the config.php .

# usersuspend

This admin tool script reads a csv-file and suspends the listed numeric user ids

Options:
-h, --help            	Print out this help
-f=filename, --filename	Name of the file to be read


Example:
sudo -u www-data /usr/bin/php moodle/admin/tool/cli/usersuspend.php -f=/var/www/moodle/temp/userlist.csv

A CSV file like the following example:
4711;323;3234;5553;44332


# Config

// URI of the JSON webservice to check the username
$UR_CFG->user_ad_check_url = "https://my.servcie..";

// directory to write the output file
$UR_CFG->syncfile_dir = "/var/www/moodle/temp";

// filename (write)
$UR_CFG->syncfile_name = "moodle_ad_sync.csv";

// CSV delimiter (read and write)
$UR_CFG->syncfile_delimieter = ";";

# Webservice
The webservice response of the check is a JSON response like the following line: 
{"sid":"2460181390-1097845571-6701207438-99750@fh-htwchur.ch","name":"Muster Tobias","mail":"Tobias.Muster@htwchur.ch"}

# CSV File
A CSV file will be created with the following content:
id;username;firstname;lastname;email;firstaccess;lastaccess;lastlogin;timecreated;timemodified;suspended;existsinad

Example:
5;2460181390-1097856571-6701207445-5634@fh-htwchur.ch;Tobias;Muster;tobias.muster@htwchur.ch;"2011-05-27 15:21:05";"2014-12-18 21:28:43";"2014-12-18 21:27:16";"1970-01-01 01:00:00";"2011-09-27 13:35:56";0;n

The last column states if the username exists in the requested directory ("y") or not ("n")

# Installation

To install please proceed as follows:

1.Get this full folder and rename it to userresolving.
2.Move the folder to MOODLEROOT/admin/tool
3.Authenticate as administrator on your Moodle installation and click on Notifications.
4.Click on Ok and finish the installation


## License

The code is released as under GPL3.

