<?php

if( !empty($_GET['layoutKey']) ) {
	$oRouter->oDao->setLang( $GLOBALS['langIdEdit'] );
	$aRouterData = current( $oRouter->readByLayout($_GET['layoutKey'], 'routePath') );

	if( !empty($aRouterData) ) {
		$oNavigation = clRegistry::get( 'clNavigation', PATH_MODULE . '/navigation/models' );
		$oNavigation->setGroupKey( 'guest' );
		$oNavigation->oDao->setLang( $GLOBALS['langIdEdit'] );

		$aNavigationMatches = $oNavigation->readByUrl($aRouterData['routePath'], array(
			'navigationId'
		) );

		if( !empty($aNavigationMatches) ) {
			$sNavigationDeletion = '
			<fieldset class="fieldGroup navigation">
				<legend><span>Navigation</span></legend>
				<p>' . sprintf( _('This page occurs %d time(s) in the navigation'), count($aNavigationMatches) ) . '</p>';

			foreach( $aNavigationMatches as &$aNavigation ) {
				// Read tree by node in path
				$aTree = $oNavigation->readTreeByNode( $aNavigation['navigationId'], array(
					'navigationId',
					'navigationTitle',
					'navigationUrl',
					'navigationLeft',
					'navigationRight'
				) );

				$sNavigationDeletion .= '
				<ul class="navTree">';

				$iPreviousDepth = 0;
				if( !empty($aTree) ) {
					foreach( $aTree as &$entry ) {
						$aClass = array();
						if( $entry['navigationId'] == $aNavigation['navigationId']  ) {
							$aClass[] = 'current';
						}

						if( $entry['depth'] > $iPreviousDepth ) {
							$aClass[] = 'subFirst';

							$sNavigationDeletion .= '
							<ul>';
						} elseif(  $entry['depth'] < $iPreviousDepth  ) {
							$aCount[$iPreviousDepth] = 0;
							$sNavigationDeletion .= str_repeat( '
							</ul>
							</li>', $iPreviousDepth - $entry['depth'] );
						}

						$sNavigationDeletion .= '
							<li' . ( !empty($aClass) ? ' class="' . implode(' ', $aClass) . '"' : '' )
							. '><a href="' . $entry['navigationUrl'] .'" target="_blank">'
							. $entry['navigationTitle'] . '</a>';

						if( $entry['navigationId'] == $aNavigation['navigationId']  ) {
							$sNavigationDeletion .= '
								<span class="controls">
									<a href="?event=deleteNavigation&amp;deleteNavigation[]=' . $entry['navigationId']
									. '&amp;deleteNavigation[]=guest&amp;' . stripGetStr( array('event', 'deleteNavigation') )
									. '" class="icon iconDelete" title="' . _( 'Do you really want to delete this item?' )
									. '"><span>' . _('Delete') . '</span></a>
								</span>';
						}

						$sNavigationDeletion .= ($entry['navigationRight'] - $entry['navigationLeft']) === 1 ? '</li>' : '';
						$iPreviousDepth = $entry['depth'];
					}
					$sNavigationDeletion .= str_repeat( '
							</ul>
							</li>', $iPreviousDepth );
				}

				$sNavigationDeletion .= '
					</ul>';
			}

			$sNavigationDeletion .= '
				</fieldset>';

			echo $sNavigationDeletion;
		}
	}
}