<header class="navbar navbar-inverse">
	<div class="container">
		<div class="navbar-header">

			<button style='width:95px; padding:3px 10px; margin-right:5px;' class="navbar-toggle clearfix" type="button" data-toggle="collapse" data-target=".navbar-collapse">
				<div class='pull-left' style='margin-top:5px'>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</div>
				<div class='text-right' style='color:#fff'>MENU</div>
			</button>
			
			<div id="info"><?php Html::sysInfo(); ?></div>
		</div>

		<nav class="collapse navbar-collapse">
			<div id="menu"><?php Html::sysMenu(); ?></div>
		</nav>
	</div>
</header>

<?php
	if (profile::$id == 0) {
	
		echo "<style> button.navbar-toggle {display:none} </style>";
	
	} 

?>

