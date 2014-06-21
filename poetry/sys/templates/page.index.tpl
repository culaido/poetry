<?php load_tpl('sysbar.tpl'); ?>
<div id="page" class="container">
	<div id="header">
		<?php load_tpl('header.tpl'); ?>
	</div>

	<div id="main" class='base2 clearfix'>

		<div id="content" class='row row-offcanvas row-offcanvas-left clearfix'>
			<nav id="mbox" class="col-xs-3 col-sm-3 col-md-3 sidebar-offcanvas" id="sidebar" role="navigation">

				<p class="visible-xs">
					<button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas"><i class="glyphicon glyphicon-chevron-left"></i></button>
				</p>

				<?php Html::renderRegion('mbox'); ?>
			</nav>

			<div id="xbox" class="col-xs-12 col-sm-9 col-md-9">

				<p class="pull-left visible-xs">
					<button type="button" class="btn btn-primary btn-xs" data-toggle="offcanvas"><i class="glyphicon glyphicon-chevron-right"></i></button>
				</p>

				<?php Html::renderRegion('xbox'); ?>
				
				<?php Html::renderRegion('intro'); ?>
			
			</div>
		</div>
	</div>
</div>

<footer id='footer' class='clearfix'>
	<?php load_tpl('footer.tpl'); ?>
</footer>