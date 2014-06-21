<?php load_tpl('sysbar.tpl'); ?>
<div id="page" class="container">
	<div id="header">
		<?php load_tpl('header.tpl'); ?>
	</div>

	<div id="main" class='base2 clearfix'>

		<div id="content" class='row row-offcanvas row-offcanvas-left clearfix'>
			<div id="xbox" class="col-xs-12">

				<?php Html::renderRegion('xbox'); ?>
			</div>
		</div>

	</div>
</div>

<footer id='footer' class='clearfix'>
	<?php load_tpl('footer.tpl'); ?>
</footer>