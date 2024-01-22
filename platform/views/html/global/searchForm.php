<?php

// Wearch variables. Priority:
// 1. POST
// 2. GET
// 3. SESSION
if( empty($_POST['searchQuery']) ) {
	if( !empty($_GET['searchQuery']) ) {
	  $_POST['searchQuery'] = $_GET['searchQuery'];
	} else if( !empty($_SESSION['auctionSearch']['searchQuery']) ) {
	  $_POST['searchQuery'] = $_SESSION['auctionSearch']['searchQuery'];
	}
}

if( empty($_POST['searchModules']) ) {
	if( !empty($_GET['searchModules']) ) {
	  $_POST['searchModules'] = (array) $_GET['searchModules'];
	} else if( !empty($_SESSION['auctionSearch']['searchModules']) ) {
	  $_POST['searchModules'] = $_SESSION['auctionSearch']['searchModules'];
	} else {
		$_POST['searchModules'] = array_keys( GLOBAL_SEARCH_MODULES );
	}
}

// Store in session (called auctionsearch by tradition)
if( !empty($_POST['searchQuery']) ) {
  $_SESSION['auctionSearch']['searchQuery'] = $_POST['searchQuery'];
}
if( !empty($_POST['searchModules']) ) {
  $_SESSION['auctionSearch']['searchModules'] = $_POST['searchModules'];
}


$aFormDataDict = [
	'formSearch' => [
		'searchQuery' => [
			'title' => _( 'Vad söker du?' ),
			'fieldAttributes' => [
				'class' => 'search'
			]
		],
		'searchModules' => [
			'title' => _( 'Sök i' ),
			'type' => 'arraySet',
			'appearance' => 'full',
			'values' => arrayToSingle( GLOBAL_SEARCH_MODULES, 'key', 'title' ),
			'fieldAttributes' => [
				'class' => 'horizontal'
			]
		],
		'searchResult' => [
			'type' => 'hidden',
			'value' => 'full'
		],
		'frmSearch' => [
			'type' => 'hidden',
			'value' => true
		]
	]
];

$aClass = [ 'newForm' ];
$sResultUrl = $oRouter->getPath( 'guestInfo-d4a2e826542000eb39e648ea3dafdc84' );

// Ajax form searches all modules
if( !empty($_GET['ajax']) ) {
	unset( $aFormDataDict['formSearch']['searchModules'] );

	if( DEFCON_LEVEL > 4 ) {
		$aFormDataDict['formSearch']['searchResult']['value'] = 'preview';
	}

  // Preview load searches all modules
  unset( $_POST['searchModules'] );

} else {
	$aClass[] = 'framed';
}

$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
$oOutputHtmlForm->init( $aFormDataDict, array(
	'method' => 'post',
	'action' => $sResultUrl,
	'attributes' => array(
    'class' => implode( ' ', $aClass )
  ),
	'placeholders' => false,
	'data' => $_POST,
	'buttons' => array(
		'submit' => array(
      'content' => _( 'Sök' )
    )
	)
) );

echo '
	<div class="view global searchForm ' . ( !empty($_GET['ajax']) ? 'preview' : '' ) . '">
		' . $oOutputHtmlForm->render() . '
	</div>';

if( DEFCON_LEVEL > 4 ) {
	echo '
		<script>
			if (typeof searchFunctionIsLoaded === "undefined") {
		    var searchFunctionIsLoaded = false;
			}

			if( !searchFunctionIsLoaded ) {
				$( document).on( "keyup", "#searchQuery", function() {
					searchFunctionIsLoaded = true;
					var searchQuery = $( this ).val();

					if( searchQuery.length > 2 ) {
						var formObj = $( this ).parents(".view.global.searchForm");
						var resultContainer = formObj.siblings(".view.global.searchResult");
						var formValues = formObj.children("form").serialize();

	          // Set loading mode on resultContainer
	          resultContainer.addClass( "searching" );

						$.post( "?ajax=1&view=global/searchResult.php", formValues, function(data) {
							resultContainer.replaceWith( data );
	            resultContainer.removeClass( "searching" );
						} );
					}
				} );
			}

			$( function() {
				$(".searchForm #searchQuery").focus();
				$("#searchQuery").keyup();

				$("input").change( function() {
					if( $(this).attr("name") == "searchModules[]" ) {
						$("#searchQuery").keyup();
					}
				})
			} );
		</script>';
}
