<?php

if( !empty($_SESSION['customer']) ) {
    
    echo '
        <ul>
            <li><a class="ajax" href="' . $oRouter->getPath( 'userAccount' ) . '">' . _( 'My account' ) . '</a></li>
            <li><a class="ajax" href="' . $oRouter->getPath( 'userOrders' ) . '">' . _( 'Order history' ) . '</a></li>
            <li><a href="' . $oRouter->getPath( 'userLogout' ) . '">' . _( 'Logout' ) . '</a></li>
        </ul>';   
    
} elseif( !empty($_SESSION['userId']) ) {
    
    echo '
        <ul>
            <li>' . _( 'You are logged in, but not as an costumer' ) . '</li>
            <li><a href="' . $oRouter->getPath( 'userLogout' ) . '">' . _( 'Logout' ) . '</a></li>
        </ul>';
    
} else {
    
    echo '
        <ul>
            <li><a class="ajax" href="' . $oRouter->getPath( 'userLogin' ) . '">' . _( 'Login' ) . '</a></li>
            <li><a class="ajax" href="' . $oRouter->getPath( 'guestCustomerSignup' ) . '">' . _( 'Sign up' ) . '</a></li>
        </ul>';   

}