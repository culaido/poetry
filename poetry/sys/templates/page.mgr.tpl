<?php load_tpl('sysbar.tpl'); ?>

<div id="page" class="container">
	<div id="header">
		<?php load_tpl('header.tpl'); ?>
	</div

	<div id="main" class='base1 clearfix'>
		<div id="content" class='row clearfix'>
			<nav id="mbox" class="col-md-3 " role="navigation"><?php Html::renderRegion('mbox'); ?></nav>
			<div id="xbox" class="col-md-9"><?php Html::renderRegion('xbox'); ?></div>
		</div>
	</div>
</div>

<footer id='footer' class='clearfix'>
	<?php load_tpl('footer.tpl'); ?>
</footer>