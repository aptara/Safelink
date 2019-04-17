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

require_once(dirname(__FILE__) . '/includes/header-front.php');

$left = theme_bcu_get_block_side();

$hasfootnote = (!empty($PAGE->theme->settings->footnote));
$haslogo = (!empty($PAGE->theme->settings->logo));
$hasp1 = (!empty($PAGE->theme->settings->p1));
$hasp2 = (!empty($PAGE->theme->settings->p2));
$hasp3 = (!empty($PAGE->theme->settings->p3));
$hasp4 = (!empty($PAGE->theme->settings->p4));
$hasp5 = (!empty($PAGE->theme->settings->p5));

$haspcap1 = (!empty($PAGE->theme->settings->p1cap));
$haspcap2 = (!empty($PAGE->theme->settings->p2cap));
$haspcap3 = (!empty($PAGE->theme->settings->p3cap));
$haspcap4 = (!empty($PAGE->theme->settings->p4cap));
$haspcap5 = (!empty($PAGE->theme->settings->p5cap));

$hasmarket1 = (!empty($PAGE->theme->settings->market1));
$hasmarket2 = (!empty($PAGE->theme->settings->market2));
$hasmarket3 = (!empty($PAGE->theme->settings->market3));
$hasmarket4 = (!empty($PAGE->theme->settings->market4));
?>
 
<?php
if (!empty($PAGE->theme->settings->infobox2)) {
?>
 <div id="themessage" class="container">
	<div id="themessage-internal"><div class="row-fluid">
	
		<?php echo $OUTPUT->get_setting('infobox2', 'format_html');; ?>
		
	</div></div>
</div>
<?php
}
?>
	<div class="row">
		<div class="col-md-12 h-mid-cont">
		<ul class="cb-slideshow">
		 <li><span>Image 04</span><div> </div></li>
		    <li><span>Image 02</span><div> </div></li>
			
			 <li><span>Image 05</span><div> </div></li>
            
            
        </ul>
			<!--<div class="text-area">
				<h2>California`s</h2>
				<h1>Free <span class="txt-yellow">Phone</span> Program</h1>
				<h3>Free Android <span class="txt-yellow">&</span> Unlimited Talk <span class="txt-orange">&</span> Text<br />
				500MB of Free Data</h3>
				Every Month!<br />
				<a href="#">Plan Offers</a>

			</div>-->
			<div class="customer-box">
				<h2>Welcome to SafeLink Wireless</h2>
				
				<?php if (isloggedin()) { 
				echo "<p>Hey <strong>".$USER->username."</strong>! Visit your dashboard.</p>";
				?>
				<a href="<?php p($CFG->wwwroot) ?>/my" class="btn2" >My Account</a>
				<?php } else { ?>
				<p>Login using your credentials to access the portal for Street Team. </p>
				<a href="<?php p($CFG->wwwroot) ?>/login" class="btn"> Login </a>	
				<?php } ?>
			</div>
		</div>
	</div>
<div class="outercont">
    <div id="page-content" class="row-fluid">
        <!--<section>
            <?php echo $OUTPUT->main_content('');?>
        </section>-->
    </div>
    </div>
<?php 
require_once(dirname(__FILE__) . '/includes/footer.php');