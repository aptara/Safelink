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
    $pagetitle = 'Grader Log';
}
 $context = context_system::instance();
// Get the My Moodle page info.  Should always return something unless the database is broken.
//if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
//    print_error('mymoodlesetup');
//}
// Start setting up the page
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/userreports/participants_list.php', $params);
$PAGE->requires->js_init_call( 'M.local_iomad_dashboard.init');
$PAGE->blocks->add_region('content');
// Set tye pagetype correctly.
$PAGE->set_pagetype('local-iomad-dashboard-index');
$PAGE->set_pagelayout('mydashboard');
//$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading('Grader Log');

echo $OUTPUT->header();
$content =$OUTPUT->heading('Grader Log');

		$courseid = $id      = optional_param('courseid',0, PARAM_INT);
		$sort = optional_param('sort', 'lastname', PARAM_RAW);
		$dir  = optional_param('dir', 'ASC', PARAM_ALPHA);
		$page         = optional_param('page', 0, PARAM_INT);
		$perpage      = optional_param('perpage', 10, PARAM_INT);
        $showall  = optional_param('showall', 0, PARAM_INT);
        $download  = optional_param('download', 0, PARAM_INT);
		$baseurl = new moodle_url('/userreports/grader_log.php', array('courseid'=>2));
		$table = new html_table();
		$table->width="100%";
		$table->attributes['class'] = 'admintable generaltable table detailTable usersMu table-striped';
		$table->align = array('left','left','center');
		
		if((!isset($USER->company->id ) || empty($USER->company->id )) && !is_siteadmin())
		{
			
			$rec_role = $DB->get_record('company_users',array('suspended'=>0,'userid'=>$USER->id));
			$USER->company = new stdclass();
			$USER->company->id = $rec_role->companyid;
		}
	
		//$content .= $OUTPUT->paging_bar(count($logs), $page, $perpage, $baseurl);
		$sql="select mu.* , comp.name as companyname from {user} mu 
		
		inner join {company_users} cu on mu.id = cu.userid 
		inner join {company} comp on comp.id = cu.companyid
		inner join {role_assignments} ra on mu.id = ra.userid 
		inner join  {user_enrolments} mue on mue.userid = mu.id 
		inner join  {enrol} me on me.id = mue.enrolid 
		where ra.roleid = ? and mu.deleted = ?
		and me.courseid  in (select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ? and shared = 0))  ";
		
		//print_object($USER->company->id);
		
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
			$users = $DB->get_records_sql($sql, array(STUDENTROLE,0,(int) $USER->company->id  ));
		}
		else
		{
			$users = $DB->get_records_sql($sql.$limit, array(STUDENTROLE,0,(int) $USER->company->id  ));
		}
		
		$users_ = $DB->get_records_sql($sql, array(STUDENTROLE,0,(int) $USER->company->id ));
		
                if($showall == 1)
                {
                    $users = $DB->get_records_sql($sql , array(STUDENTROLE,0,(int) $USER->company->id));
                }
                else
                {

                    $users = $DB->get_records_sql($sql.$limit , array(STUDENTROLE,0,(int) $USER->company->id));
                }
		$sql = "SELECT gi.*,course.fullname
                  FROM {grade_items} gi INNER JOIN
                       {grade_grades} g ON g.itemid = gi.id
					   inner join {course} course on gi.courseid =course.id
                 WHERE   gi.courseid in  (select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ? and shared = 0)) and (itemname is not NULL) and itemname !='' order by course.fullname,gi.sortorder,itemname ";
		$grade_items =$DB->get_records_sql($sql,array((int) $USER->company->id));
		
		$table->head  = array( get_string('name'),get_string('email'),get_string('company'));
		foreach($grade_items as $grade_item)
		{
			$table->head[] = $grade_item->fullname.": ".$grade_item->itemname;
		}
		$data = array();
		$sql_grade = "SELECT g.*
		  FROM {grade_items} gi INNER JOIN
			   {grade_grades} g ON g.itemid = gi.id
		 WHERE    (itemname is not NULL) and itemname !='' and userid =? and gi.id= ? order by sortorder,itemname";
		foreach($users_ as $user)
		{
			$data_arr = array(fullname($user),$user->email,$user->companyname);
			reset($grade_items);
			foreach($grade_items as $grade_item)
			{
				$gradeitem =$DB->get_record_sql($sql_grade,array($user->id,$grade_item->id));
				
				$sql = "SELECT * ,
								(
								SELECT VALUE FROM {scorm_scoes_track} AS m1 WHERE scormid = msst.scormid AND element = 'cmi.core.lesson_status' AND userid=msst.userid
								AND  scoid=msst.scoid AND attempt = msst.attempt
								) AS status,
								(
								SELECT VALUE FROM {scorm_scoes_track} AS m2 WHERE scormid = msst.scormid AND element = 'cmi.core.score.raw' AND userid=msst.userid
								AND  scoid=msst.scoid AND attempt = msst.attempt
								) AS score,
								(
								SELECT VALUE FROM {scorm_scoes_track} AS m2 WHERE scormid = msst.scormid AND element = 'cmi.core.score.max' AND userid=msst.userid
								AND  scoid=msst.scoid AND attempt = msst.attempt
								) AS maxscore,
								(
								SELECT VALUE FROM {scorm_scoes_track} AS m3 WHERE scormid = msst.scormid AND element = 'cmi.core.total_time' AND userid=msst.userid
								AND  scoid=msst.scoid AND attempt = msst.attempt
								) AS timespend
								FROM {scorm_scoes_track}  msst WHERE scormid = ? AND element = ? and userid = ? order by id desc";
								$rec_student = $DB->get_record_sql($sql, array($grade_item->iteminstance,'x.start.time',$user->id));
				
				//print_object($rec_student );
				if(isset($rec_student->maxscore))
				$gradeitem->rawgrademax = $rec_student->maxscore;
				
				if(!empty($gradeitem->rawgrade) && !empty($gradeitem->rawgrademax))
				{
					$data_arr[] = sprintf("%01.2f",$gradeitem->rawgrade)."/".sprintf("%01.2f",$gradeitem->rawgrademax);
				}
				else
				{
					$data_arr[] = "-";
				}
			}
			$data[] = $data_arr;
		}
		//$table->data =  $data;
		
                $showall_url = new moodle_url('/userreports/grader_log.php', array('courseid'=>2, 'showall'=>1));
                if($showall == 0 && $download  == 0)
                {
                    $tdata = array_chunk($data,$perpage);
                    if(count($tdata[$page]) > 0)
                    $table->data = 	$tdata[$page];
                   // $content .="<div style='text-align: right;'><a href='".$showall_url."'>Show All</a></div><br>";
                }
                else
                {
                    $table->data = 	$data;
                }
               if($download == 0)
				{
					$activityurl =  new moodle_url('/userreports/grader_log.php', array('courseid'=>2,'download'=>1));
					$content .="<div style='text-align:right;width:100%'><a href='".$activityurl."'>".get_string('download')."</a></div>";
					$showall_url = new moodle_url('/userreports/grader_log.php', array('courseid'=>2, 'showall'=>1));
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
						$content .= $OUTPUT->paging_bar(count($data), $page, $perpage, $baseurl);		
					}
					
				}
				echo $content;
                                echo $OUTPUT->footer();