<?php

$aTplSections = array(
	'{tplMisc}',
	'{tplMain}'
);

?>
	<div class="layout misc">
		{tplNotification}
		<aside id="aside">
			{tplMisc}
		</aside>
		<main id="main" role="main">
			{tplMain}
		</main>
	</div>