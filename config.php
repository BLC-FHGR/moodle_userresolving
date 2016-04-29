<?php

unset($UR_CFG);  // Ignore this line
global $UR_CFG;  // This is necessary here for PHPUnit execution
$UR_CFG = new stdClass();

// URI of the JSON webservice to check the username
$UR_CFG->user_ad_check_url = "https://www.htwchur.ch....";
// directory to write the output file
$UR_CFG->syncfile_dir = "/var/www/moodle/temp";
// filename
$UR_CFG->syncfile_name = "moodle_ad_sync.csv";
// CSV delimiter
$UR_CFG->syncfile_delimieter = ";";