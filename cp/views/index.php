<!doctype html>
<html>
	<head>
		<title>API Control Panel</title>
		<link rel="stylesheet" type="text/css" href="/cp/assets/styles.css">
	</head>
	<body>
		<header>
			<a href="https://swimmer.zone">
				<svg viewBox="0 0 800 290" id="logo">
					<defs>
						<linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
							<stop style="stop-color:#ffffff" offset="0" />
							<stop style="stop-color:#dddddd" offset="0.6" stop-opacity="1">
								  <animate attributeName="offset" dur="5s" values="0.7;0.4;0.7" repeatCount="indefinite" />
							</stop>
							<stop style="stop-color:#ffffff" offset="1" />
						</linearGradient>
					</defs>

					<polygon fill="url(#gradient)" class="s" points="10,110 110,110 110,130 50,130 50,140 110,140 110,190 90,210 10,210 10,190 50,190 50,170 10,170" />
					<polygon fill="url(#gradient)" class="w" points="120,110 150,110 150,160 170,160 170,130 175,130 175,110 176,110 176,130 180,130 180,110 180,160 200,160 200,110 250,110 250,190 230,210 120,210" />
					<g class="i">
						<polygon fill="url(#gradient)" points="280,45 285,40 295,40 300,45 295,58 300,65 280,65 285,58" />
						<polygon fill="url(#gradient)" points="280,70 300,70 300,85 280,85" />
						<polygon fill="url(#gradient)" points="280,90 300,90 300,105 280,105" />
						<polygon fill="url(#gradient)" points="260,110 320,110 320,190 300,210 260,210" />
					</g>
					<polygon fill="url(#gradient)" class="m" points="330,110 460,110 460,190 440,210 410,210 410,160 390,160 390,190 386,190 386,210 385,210 385,190 380,190 380,160 360,160 360,210 330,210" />
					<polygon fill="url(#gradient)" class="m" points="470,110 600,110 600,190 580,210 550,210 550,160 530,160 530,190 526,190 526,210 525,210 525,190 520,190 520,160 500,160 500,210 470,210" />
					<polygon fill="url(#gradient)" class="e" points="610,110 710,110 710,170 670,170 670,140 690,140 690,130 670,130 670,190 710,190 710,190 690,210 610,210" />
					<polygon fill="url(#gradient)" class="r" points="720,110 820,110 820,130 780,130 780,210 720,210" />
				</svg>
			</a>
			<h1>API Control Panel</h1>
		</header>
		<aside>
			<ul>
				<?php foreach ($menu as $item): ?>
					<li><a href="/cp/<?= $item; ?>"><?= ucfirst($item); ?></a></li>
				<?php endforeach; ?>
			</ul>
			<p>
				&copy; 2021 / <?= date('Y'); ?>
			</p>
		</aside>
		<section>
			<?php include($action . '.php'); ?>
		</section>
	</body>
</html>