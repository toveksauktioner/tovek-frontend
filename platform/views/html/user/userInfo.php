<?php

$sUserHomepageUrl = $oRouter->getPath( 'userHomepage' );
$sUserLogin = $oRouter->getPath( 'userLogin' );

echo '
  <div class="view user userInfo">
		<a href="' . $sUserLogin . '?returnTo=' . $oRouter->sPath . '" class="popupLink button narrow small gray" id="loginBtn"><i class="fas fa-user"></i><span class="extended">&nbsp;' . _( 'Logga in' ) . '</span></a>
		<a href="' . $sUserHomepageUrl . '" class="button small narrow submit" id="userBtn" style="display:none;"><i class="fas fa-user"></i><span class="extended">&nbsp;</span></a>
	</div>';
