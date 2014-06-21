<?php load_tpl('sysbar.tpl'); ?>
<div id="page" class="container">
	<div id="header">
		<?php load_tpl('header.tpl'); ?>
	</div>

	<div id="main" class='base2 clearfix'>

		<div><?php Html::renderRegion('title'); ?></div>
	
		<div id="content" class='row row-offcanvas row-offcanvas-left clearfix'>


			<div id="xbox" class="col-xs-12 col-sm-9 col-md-9">

				<?php Html::renderRegion('xbox'); ?>

				<div class="row clearfix">
					<div id="xboxL" class="col-xs-12 col-md-7">
						<?php Html::renderRegion('xboxL'); ?>
					</div>

					<div id="xboxR" class="col-xs-12 col-md-5">
						<?php Html::renderRegion('xboxR'); ?>
					</div>
				</div>

				<div id="xboxBottom" style="margin-top:20px">
					<?php Html::renderRegion('xboxBottom'); ?>
				</div>
			</div>
			
			<nav id="mbox" class="col-xs-3 col-sm-3 col-md-3 sidebar-offcanvas" id="sidebar" role="navigation">
				<?php Html::renderRegion('mbox'); ?>
			</nav>			
			
		</div>

	</div>
</div>

<footer id='footer' class='clearfix'>
	<?php load_tpl('footer.tpl'); ?>
</footer>