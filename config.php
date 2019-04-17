<?php  // Moodle configuration file
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'safelink_iomad_prod';
$CFG->dbuser    = 'safelink_dbuser';
$CFG->dbpass    = '?A)skm35t4VQ';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
);
//$CFG->dbsessions = false
$CFG->wwwroot   = 'http://www.safelinktraining.com';
//$CFG->wwwroot   = 'http://safelinktraining.com';
$CFG->dataroot  = '/home/safelink/moodle_safelink_iomad_data';
$CFG->admin     = 'admin';
$CFG->themeurl = $CFG->wwwroot .'/theme/';

define("STUDENTROLE",5);
define("CLIENTADMIN",12);
define("DEPARTADMIN",13); 


$CFG->directorypermissions = 0777;

require_once(dirname(__FILE__) . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
