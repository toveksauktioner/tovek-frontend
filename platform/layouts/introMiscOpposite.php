<?php

$aTplSections = array(
	'{tplIntro}',
	'{tplMain}',
	'{tplMisc}'
);

?>
	<div class="layout introMiscOpposite">
		{tplNotification}
		<section id="intro">
			{tplIntro}
		</section>
		<main id="main" role="main">
			{tplMain}
		</main>
		<aside id="aside">
			{tplMisc}
		</aside>
	</div>