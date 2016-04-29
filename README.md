# userresolving

This admin tool checks the database users if there is an existing entry in the Active Directory.

It marks non existing users in the Active Directory in an aditional field as "n".
a CSV file will be generated in the configured location.

The tool is customised for our needs that the HTW Chur. 
Only works with the userid of your home organisations (fh-htwchur.ch).

Place your service configuration in the config.php .

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

