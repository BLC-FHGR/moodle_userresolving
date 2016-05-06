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
 * User resolving with Avtice Directory
 * This tool reads an input file of userid to suspend;
 * The userid shoud be separated by a delimiter the default is ,
 *
 * @package    tool
 * @subpackage userresolving with Active Directory
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
 *  Suspends an user by userid
 *
 *  @param int  $userid id of the user to suspend
 *  @return int returns 0 if successfully finished
 *  			returns 1 if unsuccessfully
 *
 *  */
function suspend_user_by_id($userid){
	global $DB;		
	require_once(__DIR__.'../../../../../user/lib.php');
	
	$return = 0;
	$table = 'user';
	$conditions = array('id'=>$userid);	
	
	if (is_numeric($userid)){
		//mtrace($userid . " ");
		
		try {
			if ($user = $DB->get_record($table,$conditions)){

				$user->suspended = 1;		
				user_update_user($user, false, false);
				
			}else {
				cli_problem("user with id ". $userid . " doesn't exist" );
				$return = 1;
			}
		
		} catch (Exception $err) {
			cli_problem($err->getMessage());
			$return = 1;
		}
		
	}
	else {
		cli_problem("no numeric value:".$userid);
		$return = 1;
	}


	return $return;
}


/**
 * Reads CSV file and return it as an array
 * 
 * @param string  $file name of the file to be read
 * @param string  $delimieter used delimieter of the csv file (,) is the default
 * @return int return array array of userid
 *  */
function reads_csv_file($file,$delimieter=","){

	$array = array();

	// open file and read it
	try {			
		if (($file_handle = fopen($file, "r")) !== FALSE) {
			while (($data = fgetcsv($file_handle , 0, $delimieter)) !== FALSE) {
				$array = array_merge($array, $data);
			}
			fclose($file_handle);
		}
		else {
			cli_problem("cannot open file:" . $file);
		}		
	} catch (Exception $e) {
		cli_problem($err->getMessage());
	}	
	
	return $array;
}

/**
 *  Suspends a list of usersids
 *
 *  @param arry  $userid id of the user to suspend
 *  @return int return 0 if successfully finished
 *  			return 1 if partial successfull
 *
 *  */
function suspend_users($useridarray){

	$countsuspended = 0;
	$countsuspendedusers = 0;
	$return = 0;
	
	foreach ($useridarray as $element){
		if (is_array($element)){
			//array of user id
			foreach ($element as $value){
				$countsuspendedusers++;
				if (suspend_user_by_id($value)===0){
					$countsuspended++;
				}else{
					$return = 1;
				}

			}
			
		}else {
			// user id value
			$countsuspendedusers++;
			if (suspend_user_by_id($element)===0){
				$countsuspended++;
			}else {
				$return = 1;
			}
		}
	}
	
	$info  = $countsuspended . " users of ". $countsuspendedusers . " users suspended!";
	mtrace($info);
	
	return $return;
}

/**
 * programm flow
 *
 */

mtrace("USERSUSPEND SCRIPT started...".PHP_EOL);

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help'=>false, 'filename'=>false), array('h'=>'help','f'=>'filename'));

if ($unrecognized) {
	$unrecognized = implode("\n  ", $unrecognized);
	cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
	$help =
	"This admin tool script reads a csv-file and suspends the listed numeric user ids

Options:
-h, --help            	Print out this help
-f=filename, --filename	Name of the file to be read


Example:
\$ sudo -u www-data /usr/bin/php moodle/admin/tool/userresolving/cli/usersuspend.php -f=/var/www/moodle/temp/userlist.csv

";

	echo $help;
	die;
}

if ($options['filename']){
	$filename = $options['filename'];
} else
{
	cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}


// BEGIN
$csvarray = reads_csv_file($filename,$UR_CFG->syncfile_delimieter);
debugging("read " . count($csvarray) . " user id values");
$returnvalue = suspend_users($csvarray);

mtrace("USERSUSPEND SCRIPT finished".PHP_EOL);

exit($returnvalue);
