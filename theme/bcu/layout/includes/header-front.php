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
 * Version details
 *
 * @package    theme
 * @subpackage bcu
 * @copyright  2014 Birmingham City University <michael.grant@bcu.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
// Fixed header is determined by the individual layouts.
if(!ISSET($fixedheader)) {
    $fixedheader = false;
}
theme_bcu_initialise_zoom($PAGE);
$setzoom = theme_bcu_get_zoom();

theme_bcu_initialise_full($PAGE);
$setfull = theme_bcu_get_full();

$left = (!right_to_left());  // To know if to add 'pull-right' and 'desktop-first-column' classes in the layout for LTR.

$hasmiddle = $PAGE->blocks->region_has_content('middle', $OUTPUT);
$hasfootnote = (!empty($PAGE->theme->settings->footnote));
$haslogo = (!empty($PAGE->theme->settings->logo));

// Get the HTML for the settings bits.
$html = theme_bcu_get_html_for_settings($OUTPUT, $PAGE);

if (right_to_left()) {
    $regionbsid = 'region-bs-main-and-post';
} else {
    $regionbsid = 'region-bs-main-and-pre';
}

echo $OUTPUT->doctype();
?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
    <link href='//fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'>
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
<!-- Loading Bootstrap -->
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/css/bootstrap.min.css">
	<!-- Optional theme -->
	<link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/css/bootstrap-theme.min.css">
	
	<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script type="text/javascript" defer="defer"  src="<?php echo $CFG->themeurl.$CFG->theme;?>/js/jquery.min.js"></script>
	
	<script type="text/javascript" defer="defer"  src="<?php echo $CFG->themeurl.$CFG->theme;?>/js/jquery-ui.min.js"></script>
	
	<!-- Latest compiled and minified JavaScript -->
	<script type="text/javascript" defer="defer"  src="<?php echo $CFG->themeurl.$CFG->theme;?>/js/bootstrap.min.js"></script>
	<script type="text/javascript" defer="defer"  src="<?php echo $CFG->themeurl.$CFG->theme;?>/js/custom.js"></script>
	<!-- font awesome -->
    <link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/css/hover-min.css">
	<link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/style/style.css" type="text/css" media="screen,projection">
	<link rel="stylesheet" href="<?php echo $CFG->themeurl.$CFG->theme;?>/style/style1.css" type="text/css" media="screen,projection">
	<link rel="shortcut icon" href="<?php echo $CFG->themeurl.$CFG->theme;?>/images/favicon.png">
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,800,700italic,800italic' rel='stylesheet' type='text/css'>
	
</head>

<body <?php echo $OUTPUT->body_attributes(array('two-column', $setzoom)); ?>>
<div class="container">
<?php echo $OUTPUT->standard_top_of_body_html() ?>
<div id="page" class="container-fluid <?php echo "$setfull"; ?>">
    
<?php if (core\session\manager::is_loggedinas()) { ?>
<div class="customalert">
	<div class="container">
		<?php echo $OUTPUT->login_info(); ?>
	</div>
</div>

<?php
} else if (!empty($PAGE->theme->settings->alertbox)) {
?>
<div class="customalert">
	<div class="container">
		<?php echo $OUTPUT->get_setting('alertbox', 'format_html');; ?>
	</div>
</div>
<?php
}
?>
	<div class="row logo-strip">
		<div class="col-md-6 col-sm-6 logo">
			<?php if ($haslogo) { ?>
				<a href="<?php p($CFG->wwwroot) ?>"><?php echo "<img src='".$PAGE->theme->setting_file_url('logo', 'logo')."' alt='logo' id='logo' />"; echo "</a>";
			} else { ?>
				<a href="<?php p($CFG->wwwroot) ?>"><img src="<?php echo $OUTPUT->pix_url('2xlogo', 'theme')?>" id="logo"></a>
			<?php } ?>
		</div>
		<div class="col-md-6 col-sm-6 Rsec">
			<div class="lang"><?php
				if (empty($PAGE->layout_options['langmenu']) || $PAGE->layout_options['langmenu']) {
					echo $OUTPUT->lang_menu();
				}
            ?></div>
			<div>
				<form action="<?php p($CFG->wwwroot) ?>/course/search.php">
					<div class="search grey-box bg-white clear-fix">
						<input placeholder="<?php echo get_string("searchcourses")?>" accesskey="6" class="search_tour bg-white no-border left search-box__input ui-autocomplete-input form-control" type="text" name="search" id="search-1" autocomplete="off">
						<button type="submit"></button>
					</div>
				</form>
			</div>
			<div class="toplinks">
			<div class="home-nav">
				<div class="nav-head">
					<button type="button" class="navbar-toggle collapsed">
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
				</div>
				<div class="Hnav-cont">
				<?php 
				echo $OUTPUT->navigation_menu(); ?>
                            <?php 
                            if (isloggedin()) { 
                                                 echo ""; 
                                                 }
                                              else
                                              {
												echo $OUTPUT->custom_menu();
                                                  
                                              }
                            
                            ?>
                                
                            <div id="edittingbutton" class="pull-right breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>




            </div>
            </div>
			</div>
			 
		</div>
	</div>
