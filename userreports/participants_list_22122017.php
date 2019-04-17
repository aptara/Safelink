<?php
ob_start();
require_once('../config.php');
global $DB, $USER,$OUTPUT,$CFG, $PAGE;

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/report/outline/locallib.php');
require_once($CFG->dirroot.'/calendar/lib.php');

require_login();

$baseurl = new moodle_url('/userreports/participants_list.php', array('courseid'=>0));

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
    $pagetitle = 'Participant List';
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
$PAGE->set_heading('Participant List');

		
echo $OUTPUT->header();
$content =$OUTPUT->heading('Participant List');
$sort = optional_param('sort', 'lastname', PARAM_RAW);
$dir  = optional_param('dir', 'ASC', PARAM_ALPHA);
$showall  = optional_param('showall', 0, PARAM_INT);
$download         = optional_param('download', 0, PARAM_INT);
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);
$firstname = $lastname ='';
$override = new stdClass();
$override->firstname = 'firstname';
$override->lastname = 'lastname';
if($download ==1)
{
	$PAGE->set_pagelayout('exportpopup');
}
$fullnamelanguage = get_string('fullnamedisplay', '', $override);
if (($CFG->fullnamedisplay == 'firstname lastname') or
($CFG->fullnamedisplay == 'firstname') or
        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
        $fullnamedisplay = "$firstname / $lastname";
        if ($sort == "name") { // If sort has already been set to something else then ignore.
                $sort = "firstname";
        }
} else { // ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'lastname firstname').
        $fullnamedisplay = "$lastname / $firstname";
        if ($sort == "name") { // This should give the desired sorting based on fullnamedisplay.
                $sort = "lastname";
        }
}

if((!isset($USER->company->id ) || empty($USER->company->id )) && !is_siteadmin())
{
	
	$rec_role = $DB->get_record('company_users',array('suspended'=>0,'userid'=>$USER->id));
	$USER->company = new stdclass();
	$USER->company->id = $rec_role->companyid;
}


$admins = get_admins();
$adminstr = implode(",",array_keys($admins));
$sql="select mu.* , comp.name as companyname,me.courseid,dept.name as department from {user} mu 
		
		inner join {company_users} cu on mu.id = cu.userid 
		inner join {company} comp on comp.id = cu.companyid
		inner join {department} dept on dept.id = cu.departmentid and dept.company = comp.id
		inner join {role_assignments} ra on mu.id = ra.userid 
		inner join  {user_enrolments} mue on mue.userid = mu.id 
		inner join  {enrol} me on me.id = mue.enrolid where ra.roleid = ? and mu.deleted = ? and  me.courseid  in (select iocourse.courseid from {iomad_courses} iocourse left join {company_course} cocourse on cocourse.courseid = iocourse.courseid where shared = 1 or (cocourse.companyid = ".(int) $USER->company->id." and shared = 0))";
		
		if(user_has_role_assignment($USER->id, DEPARTADMIN))
		{
			
			
			 $sql.=" and  comp.id = ?  and cu.departmentid in (select cu1.departmentid from {company_users} cu1 where cu1.userid =?) ";
		    $sql.="	  order by ".$sort." ".$dir;
			
			$users = $DB->get_records_sql($sql, array(STUDENTROLE,0,$USER->company->id,$USER->id));
			
		}
		else
		if(!is_siteadmin() && user_has_role_assignment($USER->id, CLIENTADMIN) )
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
		


//$columns = array("allcheckbox","name","lastaccess","report");
$columns = array(get_string("fullname"),get_string("email"),get_string("company"),get_string("department"),get_string("lastaccess"));
$section_arr = Array();
foreach($courses as $course)
{
	$modinfo = get_fast_modinfo($course);
	$mods = $modinfo->get_cms();
	
	$completioninfo = new completion_info($course);
	$sections = $modinfo->get_section_info_all();
	$modinfosections = $modinfo->get_sections();
	foreach ($modinfo->sections as $sectionnum=>$section)
	{
		$sectioninfo = $modinfo->get_section_info($sectionnum);
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
		
		if($modinfo->get_section_info($sectionnum)->__get('name') =='')
		{
			continue;
		}
		
		if(!in_array($modinfo->get_section_info($sectionnum)->__get('name'),$columns))
		{
			
			$section_arr[] = $modinfo->get_section_info($sectionnum)->__get('name');
			
				foreach ($section as $cmid)
				{
					$cm = $modinfo->cms[$cmid];
					$modulename = get_string('modulename', $cm->modname);
					
					
					if($modulename =='SCORM package' || $modulename =='Paquete SCORM')
					{
			
						$columns[] = $course->fullname.": ".$cm->name;
					}
				}
		}
	}
}


    $table = new html_table();
    $table->attributes['class'] = 'admintable generaltable table detailTable usersMu table-striped';
    $table->align = array('left','left','center','center','center','center','center','center','center','center');
    //$table->size = array('20%','35%','15%', '5%', '5%','20%');
	
	

   // $table->colclasses  = array('_lastcol',($sort == 'lastname' ? 'selctedcol' : $selname),$sellastaccess,'_lastcol');

   // $table->head  = array_merge($columns,Array('Action'));	
	$table->head  = $columns;
	//print_object($table->head);

    $table->align[] = 'left';
    $table->width="100%";
	
	reset($users);
    if(count($users) > 0)
    {
            foreach ($users as $user)
            {
                
				if (!empty($user->lastaccess)) 
				{
					if ($user->lastaccess) {
						$datestring = userdate($user->lastaccess)."&nbsp; (".format_time(time() - $user->lastaccess).")";
					} else {
						$datestring = get_string("never");
					}
				}
				$row1 =array();
				$row1 = array(fullname($user),$user->email,$user->companyname,$user->department,$datestring);
				$cnt = 1;
				foreach($table->head as $header)
				{
					if($cnt > count($row1))
					{
						$row1[] = '';
					}
					$cnt++;
				}
				
				$courses = enrol_get_users_courses($user->id);
				
				foreach($courses as $course)
				{
					$modinfo = get_fast_modinfo($course);
					$mods = $modinfo->get_cms();
					
					$completioninfo = new completion_info($course);
					$sections = $modinfo->get_section_info_all();
					$modinfosections = $modinfo->get_sections();
					$cnt = 1;
					
					foreach ($modinfo->sections as $sectionnum=>$section)
					{
						$found = false;	
						$sectioninfo = $modinfo->get_section_info($sectionnum);
						
						$sectionvisible =  $modinfo->get_section_info($sectionnum)->__get('uservisible');
						if(!$sectionvisible)
						{
							continue;
						}
						
						if(!isset($modinfo->get_section_info($sectionnum)->available))
						{
							continue;
						}
						$sectionvisible =  $modinfo->get_section_info($sectionnum)->available;
						if(isset($sectionvisible) && $sectionvisible != 1)
						{
							continue;
						}
						foreach ($section as $cmid)
						{
							$cm = $modinfo->cms[$cmid];
							$modulename = get_string('modulename', $cm->modname);
							
							
							if($modulename =='SCORM package' || $modulename =='Paquete SCORM')
							{
								$sql = "SELECT sst.* "
											. "  from {scorm} s INNER JOIN {scorm_scoes_track} sst ON s.id = sst.scormid "
											." WHERE  s.id = ? and  sst.element = ? "
												. " AND sst.userid = ? ORDER BY sst.timemodified DESC LIMIT 1";

								$result_con = $DB->get_record_sql($sql, array( $cm->instance,'cmi.core.score.raw', $user->id));
								//print_object($result_con );
								//exit;
								$sectionname = $modinfo->get_section_info($sectionnum)->__get('name');
								if(isset($result_con->id))
								{
									
									$search = array_search($course->fullname.": ".$cm->name,$table->head);
									$page_arr = explode("_",$result_con->value);
									$arr_cnt = $result_con;
									$completionper =$result_con->value;
												
									$row1[$search] = $completionper."%";
									
									
									//$completionper =10;
								}
											
							}
						}
					}
				}
				$table->data[] = $row1; 
			}
    }
    else
    {
            $cell1 = new html_table_cell();
            $cell1->text = get_string("nousers");
            $cell1->colspan = count($table->head);
            $cell1->align = "center"; 
            $row1 = new html_table_row(); 
            $row1->cells[] = $cell1; 
            $table->rowclasses[0] = 'border_bottom_A5EBFF'; 	
            $table->data = array($row1); 
    }

    if($download == 0)
    {
            $activityurl =  new moodle_url('/userreports/participants_list.php', array('courseid'=>2,'download'=>1));
            $content .="<div style='text-align:right;width:100%'><a href='".$activityurl."'>".get_string('download')."</a></div>";
            $showall_url = new moodle_url('/userreports/participants_list.php', array('action'=>'base','mode'=>'showuser', 'showall'=>1));
            if($showall == 0)
            {
                    $content .="<div style='text-align: right;'><a href='".$showall_url."'>Show All</a></div><br>";
            }
    }
	if($showall == 1 || $download == 1 )
		{
			$content .= html_writer::table($table);
		}
		else
		{
			$data = $table->data;
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

                     //echo $OUTPUT->custom_block_region('content');

echo $OUTPUT->footer();