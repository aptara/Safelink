<?php
ob_start();
require_once('../config.php');
global $DB, $USER,$OUTPUT,$CFG, $PAGE;

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/report/outline/locallib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

require_login();

$id      = optional_param('courseid', 2 ,PARAM_INT);
$sort = optional_param('sort', 'lastname', PARAM_RAW);
$dir  = optional_param('dir', 'ASC', PARAM_ALPHA);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);
$showall  = optional_param('showall', 0, PARAM_INT);
$download         = optional_param('download', 0, PARAM_INT);

if (isguestuser()) {  // Force them to see system default, no editing allowed
    // If guests are not allowed my moodle, send them to front page.
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }

    $userid = null;
    $USER->editing = $edit = 0;  // Just in case
    $context = context_system::instance();
    //$PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
    $header = "$SITE->shortname: $strmymoodle (GUEST)";
    $pagetitle = $header;

} else {        // We are trying to view or edit our own My Moodle page
    $userid = $USER->id;  // Owner of the page
    $context = context_user::instance($USER->id);
    //$PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = fullname($USER);
    $pagetitle = 'Live Log';
}
 $context = context_system::instance();
// Get the My Moodle page info.  Should always return something unless the database is broken.
//if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
//    print_error('mymoodlesetup');
//}
// Start setting up the page

$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/userreports/live_log.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
//$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading('Live Log');

echo $OUTPUT->header();
$content =$OUTPUT->heading('Live Log');

		if((!isset($USER->company->id ) || empty($USER->company->id )) && !is_siteadmin())
		{
			
			$rec_role = $DB->get_record('company_users',array('suspended'=>0,'userid'=>$USER->id));
			$USER->company = new stdclass();
			$USER->company->id = $rec_role->companyid;
		}
		
		 
		$sql="select mu.* from {user} mu inner join {role_assignments} ra on mu.id = ra.userid 
		inner join {company_users} cu on mu.id = cu.userid 
		inner join {company} comp on comp.id = cu.companyid
		inner join  {user_enrolments} mue on mue.userid = mu.id 
		inner join  {enrol} me on me.id = mue.enrolid 
		where ra.roleid = ? and mu.deleted = ?  and  me.courseid  in (select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ".(int) $USER->company->id." and shared = 0))";
		if(user_has_role_assignment($USER->id, DEPARTADMIN))
		{
			
			 $sql.=" and  comp.id = ".(string)$USER->company->id. " and cu.departmentid in (select cu1.departmentid from {company_users} cu1 where cu1.userid =".(string)$USER->id.") ";
			
		}
		else if(!is_siteadmin() && user_has_role_assignment($USER->id, CLIENTADMIN))
		{
			$sql.=" and  comp.id = ?";
			$sql.="	  order by ".$sort." ".$dir;
			$users = $DB->get_records_sql($sql, array(STUDENTROLE,0,$USER->company->id));
		}	
		else
		{
			$sql.="	  order by ".$sort." ".$dir;
			$users = $DB->get_records_sql($sql, array(STUDENTROLE,0));
		}
		//$users = $DB->get_records_sql($sql, array(STUDENTROLE,0)); 
		$user_ = array_keys($users);
		$user_[] = $USER->id;
		//if($livelog == false)
		{
			$time = time() - 3600;
		}
		/*else
		{
			$time = 0;
		}*/

		$context = context_system::instance();
		$sql = "select * from {logstore_standard_log} where userid in (".implode(",",$user_).")  and timecreated > ? order by timecreated desc ";
		$limit = " limit ".($page *$perpage)." ,". $perpage;
                if($showall == 1 || $download == 1)
                {
                    $logs = $DB->get_records_sql($sql, array($time  ));
                }
                else
                {
                    $logs = $DB->get_records_sql($sql.$limit, array($time  ));
                }
		
		$logs_ = $DB->get_records_sql($sql, array($time ));
		
		$baseurl = new moodle_url('/userreports/live_log.php', array());
		$table = new html_table();
		$table->width="100%";
		$table->attributes['class'] = 'admintable generaltable table detailTable usersMu table-striped';
		$table->align = array('center');
		$table->head  = array( get_string('time'),
                get_string('fullnameuser'),
                get_string('eventrelatedfullnameuser', 'report_log'),
                get_string('eventcontext', 'report_log'),
                get_string('eventcomponent', 'report_log'),
                get_string('eventname'),
                get_string('description'),
                get_string('eventorigin', 'report_log'),
                get_string('ip_address'));
			if(count($logs) > 0)
			{
				foreach ($logs as $log)
				{
					$user = $DB->get_record('user',array('id'=>$log->userid));
					if (($log->component === 'core') || ($log->component === 'legacy')) 
					{
						$plugin =  get_string('coresystem');
					} 
					else if (get_string_manager()->string_exists('pluginname', $log->component)) 
					{
						$plugin = get_string('pluginname', $log->component);
					} 
					else 
					{
						$plugin = $log->component;
					}
					$contextname = $forusername = '';
					if($log->relateduserid > 0)
					{
						$realuser = $DB->get_record('user',array('id'=>$log->relateduserid));
						$forusername = fullname($realuser);
					}
					$recenttimestr = get_string('strftimerecent', 'core_langconfig');
					$time = userdate($log->timecreated, $recenttimestr);
					$context_ = context::instance_by_id($log->contextid, IGNORE_MISSING);
					if ($context_)
					{						
						$contextname = $context_->get_context_name(true);
						if ($url = $context_->get_url())
						{
							//$contextname = html_writer::link($url, $contextname);
						}
						$contextname = $contextname;
					} 
					else
					{
						$contextname = get_string('other');
					}
					$data = array();
					 $extra = array('origin' => $log->origin, 'ip' => $log->ip, 'realuserid' => $log->realuserid);
					$data = (array)$log;
					$id = $data['id'];
					$data['other'] = unserialize($data['other']);
					if ($data['other'] === false) {
						$data['other'] = array();
					}
					unset($data['origin']);
					unset($data['ip']);
					unset($data['realuserid']);
					unset($data['id']);

					$event = \core\event\base::restore($data, $extra); 
					$table->data[] = array($time,fullname($user),$forusername,$contextname,$plugin,$event->get_name(),$event->get_description(),$log->origin,$log->ip);
				}
				//$table->data = $data;
			}
			else
			{
				$cell1 = new html_table_cell();
				$cell1->text = get_string("nousers"); 
				$cell1->colspan = 9; 
				$cell1->align = "center"; 
				$row1 = new html_table_row(); 
				$row1->cells[] = $cell1; 
				$table->rowclasses[0] = 'border_bottom_A5EBFF'; 	
				$table->data = array($row1); 
			}
		if($download == 0)
		{
			$activityurl =  new moodle_url('/userreports/live_log.php', array('action'=>'base','mode'=>'livelog','download'=>1));
			$content .="<div style='text-align:right;width:100%'><a href='".$activityurl."'>".get_string('download')."</a></div>";
			$showall_url = new moodle_url('/blocks/kentucky/index.php', array('action'=>'base','mode'=>'livelog', 'showall'=>1));
			if($showall == 0)
			{
				$content .="<div style='text-align: right;'><a href='".$showall_url."'>Show All</a></div><br>";
			}
		}
		$content .= html_writer::table($table);
		if($download == 1)
		{
			ob_get_clean();
			$excelfilename     = basename(__FILE__, ".php").date("ymdHis").".xls";
			header("Content-Type: application/vnd.ms-excel\n");
			header("Content-Disposition: attachment; filename=$excelfilename");
			header("Expires: 0");
			header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
			header("Pragma: public");
			echo '<style> td {border:1px solid #000;} th {background:#ddd;}</style>'.$content;
			exit;
		}	
		else
		{

			if($showall == 0)
			{
				$content .= $OUTPUT->paging_bar(count($table->data), $page, $perpage, $baseurl);		
			}
			
		}
		echo $content;
                
                echo $OUTPUT->footer();