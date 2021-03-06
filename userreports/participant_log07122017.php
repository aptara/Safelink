<?php
ob_start();
require_once('../config.php');
global $DB, $USER,$OUTPUT,$CFG, $PAGE;

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/report/outline/locallib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

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
    $context = context_system::instance();
	
    //$PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = fullname($USER);
    $pagetitle = 'Participant Log';
}
// Get the My Moodle page info.  Should always return something unless the database is broken.
//if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
//    print_error('mymoodlesetup');
//}
// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/userreports/participant_log.php', $params);
$PAGE->requires->js_init_call( 'M.local_iomad_dashboard.init');
$PAGE->blocks->add_region('content');
// Set tye pagetype correctly.
$PAGE->set_pagetype('local-iomad-dashboard-index');
$PAGE->set_pagelayout('mydashboard');
$PAGE->blocks->add_region('content');
//$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading('Participant Log');

echo $OUTPUT->header();
$content =$OUTPUT->heading('Participant Log');


		$baseurl = new moodle_url('/userreports/participant_log.php', array('courseid'=>2));
		$table = new html_table();
		$table->wrap = array('nowrap','nowrap','nowrap');
		//$table->width="100%";
		$table->attributes['class'] = 'admintable generaltable table detailTable usersMu table-striped';
		//$table->align = array('left');
		$header = array(get_string("fullname"),get_string("email"),get_string("company"));
		$sql="select mu.* , comp.name as companyname,dept.name as department from {user} mu 
		inner join {company_users} cu on mu.id = cu.userid 
		
		inner join {company} comp on comp.id = cu.companyid
		inner join {department} dept on dept.id = cu.departmentid and dept.company = comp.id
		inner join {role_assignments} ra on mu.id = ra.userid 
		inner join  {user_enrolments} mue on mue.userid = mu.id 
		inner join  {enrol} me on me.id = mue.enrolid 
		where ra.roleid = ? and mu.deleted = ? and  me.courseid  in (select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ? and shared = 0)) ";
		if(user_has_role_assignment($USER->id, DEPARTADMIN))
		{
			
			 $sql.=" and  comp.id = ".(string)$USER->company->id. " and cu.departmentid in (select cu1.departmentid from {company_users} cu1 where cu1.userid =".(string)$USER->id.") ";
		}
		else if(!is_siteadmin() && user_has_role_assignment($USER->id, CLIENTADMIN))
		{
			$sql.=" and  comp.id = ".(string)$USER->company->id;
		}
		$sql.= " order by ".$sort." ".$dir;
		$limit = " limit " .($page *$perpage)." ,". $perpage;
		if($showall == 1 || $download == 1)
		{
			$users = $DB->get_records_sql($sql, array(STUDENTROLE,0,(int) $USER->company->id   ));
		}
		else
		{
			$users = $DB->get_records_sql($sql.$limit, array(STUDENTROLE,0,(int) $USER->company->id   ));
		}
		$users = $DB->get_records_sql($sql, array(STUDENTROLE,0,(int) $USER->company->id   ));
		
		
		 
		$course = $DB->get_record('course', array('id'=>$id));
		$modinfo = get_fast_modinfo($course);
		$mods = $modinfo->get_cms();
		$courserenderer = $PAGE->get_renderer('core', 'course');
	
		$completioninfo = new completion_info($course);

		$sections = $modinfo->get_section_info_all();
		$modinfosections = $modinfo->get_sections();
		$activity = array();
		foreach ($sections as $key => $section) 
		{
				$sectionvisible =  $modinfo->get_section_info($key)->__get('uservisible');
				if(!$sectionvisible)
				{
					continue;
				}
					$sectionvisible =  $modinfo->get_section_info($key)->__get('visible');
				if(!$sectionvisible)
				{
					continue;
				}
			if ($section->uservisible) 
			{
					if (!empty($modinfosections[$section->section])) 
					{
						foreach ($modinfosections[$section->section] as $cmid)
						{
							$cm = $modinfo->cms[$cmid];
						//	echo $cm->get_formatted_name();
							if(in_array($cm->get_formatted_name() ,array( "News forum","Announcements")))
							{
								continue;
							}
							
							$header[]= $cm->get_formatted_name();
							$activities[] = $cm;
						}
					}
			}
		}
		
		$table->head  = $header;
		foreach($users as $user)
		{
			$data_arr = array(fullname($user),$user->email,$user->companyname);
			reset($activities);
			
			$sql = "select g.id from {groups} g inner join {groups_members} gm on g.id = gm.groupid where courseid=? and userid=?";
			$rs_group_check = $DB->get_record_sql($sql,array($course->id,$user->id));
			$modinfo = get_fast_modinfo($course->id,$user->id);
			foreach($activities as $activity)
			{			
				$rs_check = $DB->get_record('logstore_standard_log',array('courseid'=>$activity->course,'userid'=>$user->id,'contextinstanceid'=>$activity->id));

				
				
				
				$sectioninfo = $modinfo->get_section_info($activity->section);
				
					
				if(is_object($sectioninfo) && empty($sectioninfo->__get('available')))
				{
					$data_arr[] ="-";
				}
				else
				if(!empty($rs_check->id ))
				{
					$data_arr[] ="Yes";
				}
				else
				{
					$data_arr[] ="No";
				}
				
			}
			$table->data[] = $data_arr;
		}
		$data  = $table->data;
         if($download == 0)
		{
			$activityurl =  new moodle_url('/userreports/participant_log.php', array('courseid'=>2,'download'=>1));
			$content .="<div style='text-align:right;width:100%'><a href='".$activityurl."'>".get_string('download')."</a></div>";
			$showall_url = new moodle_url('/userreports/participant_log.php', array('courseid'=>2, 'showall'=>1));
			if($showall == 0)
			{
				$content .="<div style='text-align: right;'><a href='".$showall_url."'>Show All</a></div><br>";
			}
		}
		$data = $table->data;
		if($showall == 1 || $download == 1)
		{
			$content .= html_writer::table($table);
		}
		else
		{
			$tdata = array_chunk($table->data,$perpage);
			if(count($tdata[$page]) > 0)
			$table->data = 	$tdata[$page];
			$content .= html_writer::table($table);	
			if($showall == 0)
			{
				$content .=  $OUTPUT->paging_bar(count($data), $page, $perpage, $baseurl);	
				
			}
		}
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
			
          echo $content;
    }
		
                
echo $OUTPUT->footer();