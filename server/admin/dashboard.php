<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Personas Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****
	
	require_once '../lib/personas_constants.php';
	require_once '../lib/storage.php';
	require_once '../lib/user.php';

	
	
	$result = "";
	$title = "Admin | Dashboard";

	try 
	{
		$user = new PersonaUser();
		$user->authenticate();
		$user->force_signin(1);
		if (!$user->has_approval_privs())
		{
			$_errors['error'] = 'This account does not have privileges for this operation. Please <a href="/signin?action=logout">log in</a> with an account that does.';
			include '../templates/user_error.php';
			exit;
		}

		$db = new PersonaStorage();

		$pending = $db->get_pending_persona_count();
		$edits = $db->get_pending_edits_count();
		$total = $db->get_active_persona_count();
		$log_user = null;
		if (array_key_exists('log_user', $_GET) && $user->has_admin_privs())
		{
			$log_user = $_GET['log_user'];
		}
		$logs = $db->get_detailed_admin_logs(25, $log_user);
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}

	include '../templates/header.php';
?>
<body class="admin">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include '../templates/nav.php'; ?>

<div id="status">
    <h2>Personas Status</h2>

    <div id="stats">
        <p><strong>Total Active Personas:</strong> <?= $total ?></p>
        <p><strong>New Pending Personas:</strong> <a href="pending.php" target="_blank"><?= $pending ?></a></p>
        <p><strong>Personas Awaiting Edit Approval:</strong> <a href="editing.php" target="_blank"><?= $edits ?></a></p>
    </div>
    
    <div id="log">
        <h3>Recent admin activity</h3>
        <ul>
            <?php foreach($logs as $log): 
            	$log_url = substr($log['action'], 0, 8) == 'Rejected' ? ('/admin/pending.php?id=' .  $log['id']) :  ('/persona/' . $log['id']);
            ?>
                <li>(<a href="dashboard.php?log_user=<?=$log['username']; ?>"><?= $log['username'] ?></a>) <a href="<?= $log_url ?>"><?= $log['name'] ?></a>: <?=$log['action'];?> </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<p>

<p><hr><p>
<a href="/store/dynamic/gallery/All/" target="_blank">Edit/Pull Personas</a>
<?php
	if ($user->has_admin_privs())
	{
?>
<form action="dashboard.php" method=GET>
Lookup by partial persona name: <input type=text name=partial_persona value="<?= htmlspecialchars($_GET['partial_persona']) ?>">
<br>
Lookup by partial username: <input type=text name=partial_username value="<?= htmlspecialchars($_GET['partial_username']) ?>">
<br>
Lookup by partial email: <input type=text name=partial_email value="<?= htmlspecialchars($_GET['partial_email']) ?>">
<br>
<input type="submit">
</form>
<p><hr><p>
<form action="dashboard.php" method=GET>
Flush memcache value: <input type=text name=flush_memcache value="">
<br>
<?php
		if (array_key_exists('flush_memcache', $_GET))
		{
			$db->flush_memcache($_GET['flush_memcache']);
			echo htmlspecialchars($_GET['flush_memcache']) . " flushed";
		}
?>
<br><input type="submit">
</form>

<p>

<?php
		if (array_key_exists('flush_memcache', $_GET))
		{
			$db->flush_memcache($_GET['log_id']);
		}
		

		if (array_key_exists('log_id', $_GET))
		{
			$results = $db->get_log_by_persona_id($_GET['log_id']);
			echo "Log for persona " . $_GET['log_id'];
			echo "<table border=1 cellpadding=10>";
			echo "<tr><th>Date</th><th>Username</th><th>Action</th></tr>";
			
            $i = 0;
			foreach ($results as $log)
			{	
			    $class = ($i % 2 == 0) ? 'even' : 'odd';
			    				
				echo "<tr class=\"$class\"><td>" . $log['date'] . "</td>";
				echo "<td>" . $log['username'] . "</td><td>" . $log['action'] . "</td></tr>";
				$i++;
			}
			echo "</table><p>";
			
		}
		
		if (array_key_exists('partial_persona', $_GET))
		{
			$status_map = array('Pending', 'Active', 'Rejected', 'Legal');
			$results = $db->find_persona_by_name($_GET['partial_persona']);
			echo "<p>Persona results that matched " . $_GET['partial_persona'];
			echo "<table border=1 cellpadding=10>";
			echo "<tr><th>Persona Name</th><th>Persona Author</th><th>Persona Status</th><th>Submitted</th><th>Approved</th></tr>";
			
			$i = 0;
			foreach ($results as $persona)
			{
			    $class = ($i % 2 == 0) ? 'even' : 'odd';
			    
				if ($persona['status'] == 1)
					$url = "/delete/" . $persona['id'];
				else
					$url = "/admin/pending.php?id=" . $persona['id'];
					
				echo "<tr class=\"$class\"><td><a href=\"$url\" target=\"_blank\">" . $persona['name'] . "</a></td>";
				echo "<td>" . $persona['author'] . "</td><td><a href=\"dashboard.php?partial_persona=" . htmlspecialchars($_GET['partial_persona']) . "&log_id=" . $persona['id']. "\">" . $status_map[$persona['status']] . "</a></td>";
				echo "<td>" . $persona['submit'] . "</td><td>" . $persona['approve'] . "</td></tr>";
				$i++;
			}
			echo "</table>";
		}
		
		if (array_key_exists('username', $_GET))
		{
			$status_map = array('Pending', 'Active', 'Rejected', 'Legal');
			$results = $db->get_all_submissions($_GET['username']);
			echo "<p>Results for " . $_GET['username'];
			echo "<table border=1 cellpadding=10>";
			echo "<tr><th>Persona Name</th><th>Persona Author</th><th>Persona Status</th><th>Submitted</th><th>Approved</th></tr>";
			
			$i = 0;
			foreach ($results as $persona)
			{
			    $class = ($i % 2 == 0) ? 'even' : 'odd';
			    
				if ($persona['status'] == 1)
					$url = "/delete/" . $persona['id'];
				else
					$url = "/admin/pending.php?id=" . $persona['id'];
					
				echo "<tr class=\"$class\"><td><a href=\"$url\" target=\"_blank\">" . $persona['name'] . "</a></td>";
				echo "<td>" . $persona['author'] . "</td><td><a href=\"dashboard.php?username=" . $_GET['username'] . "&log_id=" . $persona['id']. "\">" . $status_map[$persona['status']] . "</a></td>";
				echo "<td>" . $persona['submit'] . "</td><td>" . $persona['approve'] . "</td></tr>";
				$i++;
			}
			echo "</table>";
		}
		
		if (array_key_exists('partial_email', $_GET) || array_key_exists('partial_username', $_GET))
		{
			$status_map = array('Disabled', 'Active', 'Approver', 'Admin');
			$results = $user->find_user($_GET['partial_username'], $_GET['partial_email']);
			echo "<table border=1 cellpadding=10>";
			echo "<tr><th>Username</th><th>Email</th><th>User Status</th></tr>";
			
			$i =0;
			foreach ($results as $user)
			{
			    $class = ($i % 2 == 0) ? 'even' : 'odd';
			    
				$url = "dashboard.php?username=" . $user['username'];
				echo "<tr class=\"$class\"><td><a href=\"$url\">" . $user['username'] . "</a></td>";
				echo "<td>" . $user['email'] . "</td><td>" . $status_map[$user['status']] . "</td></tr>";
				$i++;
			}
			echo "</table>";
		}
		
		

	}
?>
</div></div>
<?php include '../templates/footer.php'; ?>
</body>
</html>
