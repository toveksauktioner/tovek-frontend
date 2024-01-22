<?php

$aTplSections = array(
	'{tplIntro}',
	'{tplSidebarLeft}',
	'{tplMain}',
	'{tplSidebarRight}'
);

?>
	<div class="layout introMainThreeCol">
		{tplNotification}
		<section id="intro">
			{tplIntro}
		</section>
		<aside id="sidebarLeft" class="col sidebar left">
			{tplSidebarLeft}
		</aside>
		<main id="main" role="main" class="col middle">
			{tplMain}
		</main>
		<aside id="sidebarRight" class="col sidebar right">
			{tplSidebarRight}
		</aside>
	</div>
