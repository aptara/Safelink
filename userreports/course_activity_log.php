<?php
ob_start();
require_once('../config.php');
global $DB, $USER,$OUTPUT,$CFG, $PAGE;

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/report/outline/locallib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

require_login();


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
    $pagetitle = 'Course Activity Log';
}
 $context = context_system::instance();
// Get the My Moodle page info.  Should always return something unless the database is broken.
//if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
//    print_error('mymoodlesetup');
//}
// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/userreports/course_activity_log.php', $params);
$PAGE->requires->js_init_call( 'M.local_iomad_dashboard.init');
$PAGE->blocks->add_region('content');
// Set tye pagetype correctly.
$PAGE->set_pagetype('local-iomad-dashboard-index');
$PAGE->set_pagelayout('mydashboard');
$PAGE->blocks->add_region('content');
//$PAGE->set_subpage($currentpage->id);
$PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
$PAGE->set_title($pagetitle);
$PAGE->set_heading('Course Activity Log');

echo $OUTPUT->header();
$content =$OUTPUT->heading('Course Activity Log');

if((!isset($USER->company->id ) || empty($USER->company->id )) && !is_siteadmin())
{
	
	$rec_role = $DB->get_record('company_users',array('suspended'=>0,'userid'=>$USER->id));
	$USER->company = new stdclass();
	$USER->company->id = $rec_role->companyid;
}
	
	
		$sql = "select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ".(int) $USER->company->id." and shared = 0)";
		
		$defaultcourseid = array_shift($DB->get_records_sql($sql,array()));


		$id   		= optional_param('courseid', $defaultcourseid->courseid,PARAM_INT);
		$sort 		= optional_param('sort', 'lastname', PARAM_RAW);
		$dir  		= optional_param('dir', 'ASC', PARAM_ALPHA);
		$page       = optional_param('page', 0, PARAM_INT);
		$perpage    = optional_param('perpage', 10, PARAM_INT);
		$showall  = optional_param('showall', 0, PARAM_INT);
        $download  = optional_param('download', 0, PARAM_INT);
		
		
		
		$baseurl = new moodle_url('/userreports/course_activity_log.php', array('courseid'=>$defaultcourseid->courseid));
		$table = new html_table();
		$table->width="100%";
		$table->attributes['class'] = 'generaltable boxaligncenter sites_table inner_table';
		$table->align = array('left','center','left');
		$course = $DB->get_record('course', array('id'=>$id));
		$context = context_course::instance($course->id);
		//require_capability('report/outline:view', $context);
		// Trigger an activity report viewed event.
		$event = \report_outline\event\activity_report_viewed::create(array('context' => $context));
		$event->trigger();
		$stractivity       = get_string('activity');
		$strlast           = get_string('lastaccess');
		$strreports        = get_string('reports');
		$strviews          = get_string('views');
		$strviewscompleted          = get_string('completedvisit');
		$struniqueviews          = get_string('uniquevisit');
		$table->head = array($stractivity,$strviews ,$strviewscompleted,$struniqueviews,$strlast );
		
		
		$activity = array();
		$prevsecctionnum = 0;
		
		$sql="select mu.* from {user} mu inner join {role_assignments} ra on mu.id = ra.userid 
		inner join  {user_enrolments} mue on mue.userid = mu.id 
		inner join {company_users} cu on mu.id = cu.userid 
		inner join {company} comp on comp.id = cu.companyid
		inner join  {enrol} me on me.id = mue.enrolid 
		where ra.roleid = ? and mu.deleted = ?  ";
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
		$user_ = array_keys($users);
		if(!(is_siteadmin() || user_has_role_assignment($USER->id, CLIENTADMIN)))
		{
			$courses = $DB->get_records('course', Array());
		}
		else
		{
			$sql="select course.*  from {course} course inner join {iomad_courses} iocourse on iocourse.courseid = course.id left join {company_course} cocourse on cocourse.courseid =  course.id and  cocourse.courseid = iocourse.courseid where shared = 1 or (
			cocourse.companyid = ? and shared = 0)";
			$courses = $DB->get_records_sql($sql, Array((string)$USER->company->id));
		}
		
		
		foreach($courses as $course)
		{
			$modinfo = get_fast_modinfo($course);
			$mods = $modinfo->get_cms();
			$courserenderer = $PAGE->get_renderer('core', 'course');
		
			$completioninfo = new completion_info($course);

			$sections = $modinfo->get_section_info_all();
			$modinfosections = $modinfo->get_sections();
			list($uselegacyreader, $useinternalreader, $minloginternalreader, $logtable) = report_outline_get_common_log_variables();
			$showlastaccess = true;
			if ($useinternalreader) {
				// Check if we need to show the last access.
				$sqllasttime = '';
				if ($showlastaccess) {
					$sqllasttime = ", MAX(timecreated) AS lasttime, count( distinct userid) as uniqueview";
				}
				if(count($user_) > 0)
				{
				 $sql = "SELECT contextinstanceid as cmid, COUNT('x') AS numviews $sqllasttime,
						(select count(*) from {course_modules_completion} cmc where cmc.coursemoduleid = contextinstanceid and cmc.userid in (".implode(",",$user_ ).") and completionstate = ".COMPLETION_COMPLETE.") as cmc
						  FROM {" . $logtable . "} l
						 WHERE courseid = :courseid
						 and userid in (".implode(",",$user_ ).")
						   AND anonymous = 0
						   AND crud = 'r'
						   AND contextlevel = :contextmodule
					  GROUP BY contextinstanceid";
				
				$params = array('courseid' => $course->id, 'contextmodule' => CONTEXT_MODULE);
				$v = $DB->get_records_sql($sql, $params);
				}
				
				//print_object($v);

				if (empty($views)) {
					$views = $v;
				} else {
					// Merge two view arrays.
					foreach ($v as $key => $value) {
						if (isset($views[$key]) && !empty($views[$key]->numviews)) {
							$views[$key]->numviews += $value->numviews;
							if ($value->lasttime > $views[$key]->lasttime) {
								$views[$key]->lasttime = $value->lasttime;
							}
						} else {
							$views[$key] = $value;
						}
					}
				}
			}
			foreach ($modinfo->sections as $sectionnum=>$section) {
				foreach ($section as $cmid) {
					
					$sectionvisible =  $modinfo->get_section_info($sectionnum)->__get('uservisible');
					if(!$sectionvisible)
					{
						continue;
					}
					$sectionvisible =  $modinfo->get_section_info($sectionnum)->__get('visible');
					if(!$sectionvisible)
					{
						continue;
					}
						
					$cm = $modinfo->cms[$cmid];
					
					
					$dimmed = $cm->visible ? '' : 'class="dimmed"';
					$modulename = get_string('modulename', $cm->modname);
					if($modulename == 'Forum')
					{
						continue;
					}
					
					if ($prevsecctionnum != $sectionnum) 
					{
						$sectionrow = new html_table_row();
						$sectionrow->attributes['class'] = 'section';
						$sectioncell = new html_table_cell();
						$sectioncell->colspan = count($table->head);

						$sectiontitle = get_section_name($course, $sectionnum);

						$sectioncell->text = $OUTPUT->heading($course->fullname.": ".$sectiontitle, 3);
						$sectionrow->cells[] = $sectioncell;
						$table->data[] = $sectionrow;

						$prevsecctionnum = $sectionnum;
					}
					

					$reportrow = new html_table_row();
					$activitycell = new html_table_cell();
					$activitycell->attributes['class'] = 'activity';

					$activityicon = $OUTPUT->pix_icon('icon', $modulename, $cm->modname, array('class'=>'icon'));

					$attributes = array();
					if (!$cm->visible) {
						$attributes['class'] = 'dimmed';
					}

					//$activitycell->text = $activityicon . html_writer::link("$CFG->wwwroot/mod/$cm->modname/view.php?id=$cm->id", format_string($cm->name), $attributes);
					$activitycell->text =format_string($cm->name);

					$reportrow->cells[] = $activitycell;

					$numviewscell = new html_table_cell();
					$numviewscell->attributes['class'] = 'numviews';

					if (!empty($views[$cm->id]->numviews)) {
						$numviewscell->text = $views[$cm->id]->numviews;
					} else {
						$numviewscell->text = '-';
					}

					$reportrow->cells[] = $numviewscell;
					
					/*$completion_arr = $DB->get_records('course_modules_completion', array('coursemoduleid'=>$cm->id,'completionstate'=>COMPLETION_COMPLETE));*/
					
					
					
					
					
					$numviewscell = new html_table_cell();
					$numviewscell->attributes['class'] = 'numviews';
					$numviewscell->text = $views[$cm->id]->cmc;
					$reportrow->cells[] = $numviewscell;
					
					$numviewscell = new html_table_cell();
					$numviewscell->attributes['class'] = 'numviews';

					if (!empty($views[$cm->id]->uniqueview)) {
						$numviewscell->text = $views[$cm->id]->uniqueview;
					} else {
						$numviewscell->text = '-';
					}
					

					$reportrow->cells[] = $numviewscell;

						if ($showlastaccess)
							{
						$lastaccesscell = new html_table_cell();
						$lastaccesscell->attributes['class'] = 'lastaccess';

						if (isset($views[$cm->id]->lasttime)) {
							$timeago = format_time(time() - $views[$cm->id]->lasttime);
							$lastaccesscell->text = userdate($views[$cm->id]->lasttime)." ($timeago)";
						}
						$reportrow->cells[] = $lastaccesscell;
					}
					$table->data[] = $reportrow;
				}
			}
		}
		$table->align= array('left','center','center','left');
		 if($download == 0)
		{
			$activityurl =  new moodle_url('/userreports/course_activity_log.php', array('courseid'=>2,'download'=>1));
			$content .="<div style='text-align:right;width:100%'><a href='".$activityurl."'>".get_string('download')."</a></div>";
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

			//$content .= $OUTPUT->paging_bar(count($users_), $page, $perpage, $baseurl);
			
		}
		
		
		
		echo $content;
                echo $OUTPUT->footer();
