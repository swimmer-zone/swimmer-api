<!doctype html>
<html>
	<head>
		<title>API Control Panel</title>
		<link rel="stylesheet" type="text/css" href="/cp/assets/styles.css">
	</head>
	<body>
		<header>
			<h1>API Control Panel</h1>
		</header>
		<aside>
			<menu>
			</menu>
		</aside>
		<section>
			<?php include($action . '.php'); ?>
		</section>
		<footer>
			&copy; <a href="https://swimmer.zone">https://swimmer.zone</a> 2021 / <?= date('Y'); ?>
		</footer>
	</body>
</html>