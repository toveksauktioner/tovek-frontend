<?php

$aTplSections = array(
	'{tplMain}',
	'{tplMisc}'
);

?>
	<div class="layout miscOpposite">
		{tplNotification}
		<main id="main" role="main">
			{tplMain}
		</main>
		<aside id="aside">
			{tplMisc}
		</aside>
	</div>
