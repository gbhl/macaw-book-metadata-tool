<form action="#" method="post" id="metadata_form" onSubmit="oBook.metadataChange(this); return false;">
	<h3>Page Metadata <span id="sequence_number"></span></h3>
	<div id="metadata_overlay"></div>

	<?php if (count($metadata_modules) > 1) { ?>
		<div id="metadata_tabs" class="yui-navset">
			<ul class="yui-nav">
				<?php $c = 1;
				foreach ($metadata_modules as $m) {
					echo '<li'.($c == 1 ? ' class="selected"' : '' ).'><a href="#tab'.$c++.'"><em>'.str_replace('_', ' ', $m).'</em></a></li>';
				} ?>
			</ul>
			<div class="yui-content">
				<?php $c = 1;
				foreach ($metadata_modules as $m) {
					echo '<div id="tab'.$c++.'">';
					require_once($base_directory.'/plugins/metadata/'.$m.'.php');
					echo '</div>';
				} ?>
			</div>
		</div>
	<?php
	} else {
		require_once($base_directory.'/plugins/metadata/'.$metadata_modules[0].'.php');
	}
	?>
</form>
