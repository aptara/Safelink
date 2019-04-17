<?php

require_once('config.php');

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Tracfone stores training: Contact Us - Support");
$PAGE->set_heading("Blank page");
$PAGE->set_url($CFG->wwwroot.'/contact-us.php');

echo $OUTPUT->header(); ?>
<style>
.content-contactus {
    min-height: 31em;
    padding: 20px;
}
#region-main.span9 {
    border: 1px solid #ccc;
    margin: 10px 0 20px;
	background: #eee none repeat scroll 0 0;
    width: 100%;
}
</style>
<div class="content-contactus">
<div style=" text-align: center;;">
<h1>Contact Us – Support</h1>
<p>Get support by Phone, Email.</p>
</div>
<h2>We’re here to help.</h2>
<div>
<table class="table" style="font-size: 17px;">
  <thead>
    <tr>
      <th>Office Hours</th>
      <th>Contact Deatils</th>
       <th>Technical Support (Application Support)</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>From 9 AM to 5 PM EST <br> (7 days a week)</td>
      <td><strong>US Toll Free Number:</strong> 1-844-244-1157 <br> <strong>Email:</strong> tfslsupport@aptaracorp.zohosupport.com</td>
      <td>12 Noon EST Monday-Friday<br> (Excluding weekends and holidays)</td>
    </tr>
    
  </tbody>
</table>
<div class="support-box">
	<h4>Help Desk Support Details</h4>
	<p>Support Email: tfslsupport@aptaracorp.zohosupport.com</p> 
	<p>Support US Toll Free Number : 1-844-244-1157 </p>
	<p>Support Availability : 9am to 5pm EST (7 days a week)</p>
	 
	<h5>Support via ticketing system will cover the issues below:</h5>
	<ul>
	<li>LMS application is down</li>
	<li>Login issue with users</li>
	<li>Password Reset issue in LMS application</li>
	<li>Any LMS functionality not working properly</li>
	<li>Application hang or slowness issue</li>
	</ul>
	 
	<h5>To reset password, follow the steps below:</h5>
	<ul>
	<li>Kindly visit to the login page (<a href="<?php echo $CFG->wwwroot;?>/login/"><?php echo $CFG->wwwroot;?>/login/</a>)</li>
	<li>Click on Forgot your username or password? Link</li>
	<li>Enter your Username or  Email and click on search button.</li>
	<li>If you have supplied the correct username or email then password reset instruction (link) will be send to your registered email where you can reset the password of your account.</li>
	</ul>
</div>
</div>
</div>
<?php
echo $OUTPUT->footer();
?>
