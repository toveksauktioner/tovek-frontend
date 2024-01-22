<?php

$aTplSections = array(
	'{tplIntro}',
	'{tplMain}'
);

?>
	<div class="layout introMain">
		{tplNotification}
		<section id="intro">
			{tplIntro}
		</section>
		<main id="main" role="main">
			{tplMain}
		</main>
	</div>