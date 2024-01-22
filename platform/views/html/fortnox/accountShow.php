<?php

$oFortnoxTool = clFactory::create( 'clFortnoxTool', PATH_MODULE . '/fortnox/models' );

if(
    empty(FORTNOX_ACCESS_TOKEN) &&
    !empty(FORTNOX_CLIENT_ID) &&
    !empty(FORTNOX_CLIENT_SECRET) &&
    !empty(FORTNOX_AUTH_CODE)
) {
    /**
     * Get access token
     */
    if( !$oFortnoxTool->finishAccountCreation() ) {
        $oNotification = clRegistry::get( 'clNotificationHandler' );
        $oNotification->set( array(
            'dataError' => _( 'Problem with access token' )
        ) );
    } else {
        // Re-build module
        $oFortnoxTool = clFactory::create( 'clFortnoxTool', PATH_MODULE . '/fortnox/models' );
    }
}

echo '
    <div class="view fortnox account">
        <h1>' . _( 'Fortnox account' ) . '</h1>
        <section>
            <h2>' . _( 'Account information' ) . '</h2>
            <dl>
                <dt>' . _( 'Username' ) . ':</dt> <dd>' . FORTNOX_ACCESS_ACCOUNT_USERNAME . '</dd>
                <dt>' . _( 'API user' ) . ':</dt> <dd>' . FORTNOX_ACCOUNT_USERNAME . '</dd>
            </dl>
            <br />
            <dl>
                <dt>' . _( 'App ID used' ) . ':</dt> <dd>' . FORTNOX_APP_ID . '</dd>
                <dt>' . _( 'Content type' ) . ':</dt> <dd>' . FORTNOX_CONTENT_TYPE . '</dd>
                <dt>' . _( 'Content accept' ) . ':</dt> <dd>' . FORTNOX_ACCEPTS . '</dd>
                <dt>' . _( 'API endpoint' ) . ':</dt> <dd>' . FORTNOX_ENDPOINT . '</dd>
            </dl>
            <hr />
            <h2>' . _( 'Access status' ) . '</h2>
            <dl>
                <dt>' . _( 'Status' ) . ':</dt> <dd>' . (empty(FORTNOX_ACCESS_TOKEN) ? '<span class="inactive">' . _( 'No access' ) . '</span>' : '<span class="active">' . _( 'Access granted' ) . '</span>' ) . '</dd>
            </dl>
        </section>
    </div>';
    
$oTemplate->addStyle( array(
    'key' => 'fortnoxCss',
    'content' => '
        .view.fortnox.account section dl dt { clear: left; float: left; width: 11em; }
        .view.fortnox.account section dl dd { float: left; width: auto; }
        .view.fortnox.account section dl:after { content: ""; display: block; clear: both; height: 0; }
    '
) );