<?php
	session_start();
	if (!isset($_SESSION['user_id'])) {
		header("Location: index.html");
	} else {
		require("./models/User.class.php");
		$user = new User($_SESSION['user_id']);
		$_SESSION['user'] = $user;
	}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>Homepage</title>
		<link href="navi.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript">
			function active(){
				var searchBar = document.getElementById('searchBar');
				
				if(searchBar.value == 'Search...'){
					searchBar.value = ''
					searchBar.placeholder = 'Search...'
				}
			}
			function inactive(){
				var searchBar = document.getElementById('searchBar');
				
				if(searchBar.value == ''){
					searchBar.value = 'Search...'
					searchBar.placeholder = ''
				}
			}
		</script>
	</head>
	<body>
		<div id="Maindiv">
			<div id="navdiv">
				<!--
				<form action="search.php" method="post">
					<input type="text" id="searchBar" placeholder="" value="Search..." maxlength="30" autocomplete="off" onmousedown="active();" onblur="inactive();"/><input type="submit" id="searchBtn" value="Go!" />
				</form>
				-->

				<ul>
					<h1><?php echo $_SESSION['user']->getF_name()." ".$_SESSION['user']->getS_name(); ?></h1>
					<li><a href="logout.php">LogOut</a></li>
					<li><a href="task_history.php">View Task History</a></li>
					<li><a href="my_tasks.php">View My Tasks</a></li>
					<li><a href="task_creation.php">Create Task</a></li>
				</ul>
			</div>
		</div>

		<div id="info">
			<h3>Univesity ID</h3>
			<p><?php echo $_SESSION['user']->getId(); ?><p>
			<br>
			<h3>Email</h3>
			<p><?php echo $_SESSION['user']->getEmail(); ?></p>
			<br>
			<h3>Subject</h3>
			<p><?php echo $_SESSION['user']->getSubject(); ?></p>
			<br>
			<h3>Reputation Points</h3>
			<p><?php echo $_SESSION['user']->getPoints(); ?></p>
		</div>

		<div class="claimed_tasks">
			<h2>Claimed Tasks</h2>
			<div class="tasks">
				<?php
					require("/connect.php");

					/* 
					Get the title and task id of each task 
					where the task has been claimed by you,
					while at the same time, the task deadline has not expired.
					*/

					$result = $dbh->prepare("SELECT t.Task_ID, t.Title, DATEDIFF(dead.Sub_D, NOW()) as DIFF
											 FROM (Tasks t JOIN Task_Status s ON t.Task_ID = s.Task_ID)
											      JOIN Deadlines dead ON t.Task_ID = dead.Task_ID
											 WHERE (Claimant = :id AND Status = 'CLAIMED') AND dead.Sub_D >= CURDATE()
											 ORDER BY dead.Sub_D;");
					$result->bindParam(':id', $_SESSION['user_id']);
					$result->execute();

					while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

						// If there is a task that has been expired for submission
						// change its status and deduct 30 rep points
						if ($row['DIFF'] > 0) {
							echo "<p><a href='task_details.php?task_id=".$row['Task_ID']."&claimed=1&expired=0'>".$row['Title']."</a><br>".$row['DIFF']." days left!</p>";
							echo "<hr>";
						} else {
							echo "<p><a href='task_details.php?task_id=".$row['Task_ID']."&claimed=1&expired=1'>".$row['Title']."</a>: ".$row['DIFF']." days left!</p>";
							echo "<hr>";
						}
						
					}

					$dbh = null;

				?>
			</div>
			

			
	</div>

	<div class="task_stream">
		<h2>Tasks To Claim</h2>
		<div class="tasks">

			<?php
				require("/connect.php");
				/*
				Get the task id and task title of each task
				where the task does not belong to you and is available to be claimed.
				The deadline for claiming that task should not have been reached.
				*/
				$result = $dbh->prepare("SELECT Tasks.Task_ID, Tasks.Title, DATEDIFF(Claim_D, NOW()) as DIFF
										 FROM (Tasks JOIN Task_Status ON Tasks.Task_ID = Task_Status.Task_ID)
										 	JOIN Deadlines ON Tasks.Task_ID = Deadlines.Task_ID
										 WHERE (Owner <> :id AND Status = 'PENDING_CLAIM') AND Claim_D >= CURDATE();");
				$result->bindParam(':id', $_SESSION['user_id']);
				$result->execute();

				
				while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
					echo "<p><a href='task_details.php?task_id=".$row['Task_ID']."&claim=1'>".$row['Title']."</a><br>".$row['DIFF']." days left to claim!</p>";
					echo "<hr>";
				}

				$dbh = null;

			?>
			
		</div>
		
	</div>

	</body>
</html>