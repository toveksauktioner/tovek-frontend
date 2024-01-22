<?php

$aTplSections = array(
	'{tplIntro}',
	'{tplMain}',
	'{tplMisc}'
);

?>
	<div class="layout introMisc">
		{tplNotification}
		<section id="intro">
			{tplIntro}
		</section>
		<aside id="aside">
			{tplMisc}
		</aside>
		<main id="main" role="main">
			{tplMain}
		</main>
	</div>