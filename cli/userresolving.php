<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * User resolving with Active Directory
 *
 * @package    tool
 * @subpackage user resolving with Active Directory
 * @copyright 2016, HTW chur {@link http://www.htwchur.ch}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Always inform Moodle that a cli script is running.
define('CLI_SCRIPT', true);



// moodle config
require(__DIR__.'../../../../../config.php');
global $CFG;

// include the local configuration
require(__DIR__.'/../config.php');
global $UR_CFG;


require_once($CFG->libdir.'/clilib.php');

// Detect moodle
defined('MOODLE_INTERNAL') || die();


/**
 *  getting Shibboleth users from the moodle database
 *  
 *  @param int  $utctime array of users with the field username as shibboletid
 *  @return array returns an array of the user data with the fields:
 *  id, username, firstname, lastname, email, firstaccess, lastaccess, lastlogin, timecreated, timemodified, suspended
 *  as an object
 *  */
function get_shib_user_list(){
	global $DB;
	
	// db query
	$table = 'user';
	$conditions = array('auth' => 'shibboleth', 'deleted' => '0');
	$fields = 'id, username, firstname, lastname, email, from_unixtime(firstaccess) firstaccess, from_unixtime(lastaccess) lastaccess,
			from_unixtime(lastlogin) lastlogin, from_unixtime(timecreated) timecreated, from_unixtime(timemodified) timemodified, suspended';
	try {
		$result = $DB->get_records($table,$conditions,null,$fields);	

	} catch (Exception $e) {
		cli_problem($err->getMessage());
	}
		
	return $result;
}


/**
 * checks the shibboleth users if they exists in the Active Directory
 * @param array  $userlist array of users with the field username as shibboletid
 * @return array returns an array of the user data whith the fields 
 *  id, username, firstname, lastname, email, firstaccess, lastaccess, lastlogin, timecreated, timemodified, suspended, existsinad
 *  as an array
 *  */
function check_user_list_with_ad($userlist){
	global $UR_CFG;
	
	$returnarray = array();	
	
	foreach ($userlist as $key => $record){
		
		$respval = array();
		$request_uri = $UR_CFG->user_ad_check_url . $record->username;
		
		// Webservice Request
		try{
			$response = file_get_contents($request_uri);
		}
		catch (Exception $err)
			{
				cli_problem($err->getMessage());
		}
		
		// decode resposne and add to return array
		if (!empty($response))
		{
			try
			{
				$respval = json_decode($response, true);
				$value_exists = "n";
				
				// check if user is in the response
				If (!empty($respval) and (!empty($respval["sid"]))
						and ($respval["sid"] = $record->username)) {	
					$value_exists = "y";
				}
				
				//$addvalue = array("exists_in_ad",$exists_value);
				$record->existsinad = $value_exists;
				//array_merge($record,$addvalue);					
				array_push($returnarray,(array) $record);
			}
			catch (Exception $err)
			{
				cli_problem($err->getMessage());
			}
		}			
	}
	
	return $returnarray;
}

/**
 * writes an array in a CSV file
 * @param array  $userlist array of users with the field username as shibboletid
 * @param string  $directory name of the directory to write the file
 * @param string  $filename name of the file to be written
 * @param string  $delimieter used delimieter of the csv file (,) is the default
 * @return int return 0 if successfully finished
 *  */
function write_csv_file($array,$directory,$filename="moodle_ad_sync.csv",$delimieter=",",$header = true){
	
	$return = 0;
	if (!is_array($array))
	{
		cli_problem("no data to write in file");
		$return = 1;
	}
	
	if ($return == 0){
			
		// create header row
		if ($header){
			
			$first_array = current($array);
			$headerarray = array();
			
			if (!is_array($first_array))
			{
				cli_problem("no data to write in file");
				$return = 1;
			}
			
			if ($return == 0){
				$headerarray = array();			
			
				foreach($first_array as $key => $value)
				{
					$headerarray[$key] = $key;
				}
				
				array_unshift($array,$headerarray);
			}
		}
		
		// write file
		$file_handle = fopen($directory."/".$filename , "w");
		debugging("File opended for output: ".$directory."/".$filename ,DEBUG_DEVELOPER);
		
		foreach ($array as $recordarray) {
			fputcsv($file_handle , $recordarray , $delimieter);
		}
		
		debugging("File have been written to: ".$directory."/".$filename ,DEBUG_DEVELOPER);
		
		if (!fclose($file_handle)) {
			cli_error("cannot close file");
		}
	}
		
	return $return;
}

/**	
 * programm flow
 *
 */

// add args
echo "USERRESOLVING SCRIPT started...".PHP_EOL;
// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('verbose'=>false, 'help'=>false, 'filename'=>false, 'noheader'=>false), array('v'=>'verbose', 'h'=>'help','f'=>'filename','n'=>'noheader'));

if ($unrecognized) {
	$unrecognized = implode("\n  ", $unrecognized);
	cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
	$help =
	"This admin tool checks the database users if there is an existing entry in the Active Directory.

It marks non existing users in the Active Directory in an aditional field as 'n'.
a CSV file will be generated in the configured location.

Options:
-v, --verbose         	Print verbose progress information
-h, --help            	Print out this help
-f=filename, --filename	name of the file to be created (can be set in config.php)
-n, --noheader		  	writes no headerrow on top of the CSV file

Example:
\$ sudo -u www-data /usr/bin/php moodle/admin/tool/cli/userresolving.php

";

	echo $help;
	die;
}

$filename = $UR_CFG->syncfile_name;
if ($options['filename']){
	$filename = $options['filename'];
}
$header = true;
if ($options['noheader']){
	$header = false;
}

// BEGIN
$result = get_shib_user_list();
$checkesresult = check_user_list_with_ad($result);
//debugging("check_user_list_with_ad() done...",DEBUG_DEVELOPER);
write_csv_file($checkesresult,$UR_CFG->syncfile_dir,$filename,$UR_CFG->syncfile_delimieter,$header);
echo "USERRESOLVING SCRIPT finished".PHP_EOL;

exit(0);




