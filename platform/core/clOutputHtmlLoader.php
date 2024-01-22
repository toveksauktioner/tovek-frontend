<?php

require_once PATH_CORE . '/clPaginationBase.php';
require_once PATH_FUNCTION . '/fOutputHtml.php';

class clOutputHtmlLoader extends clPaginationBase implements ifPagination {

	public $aParams = array();
	
	private $iCurrentTotal;
	private $iLoadsTotal;

	public function __construct( $oDao, $aParams = array() ) {
		$aParams += array(
			'entries' => null,
			'stringSingular' => _( 'Item' ),
			'stringPlural' => _( 'Items' ),
			'selectEntries' => null,
			'hideOnEmpty' => false,
			'useAjax' => null
		);
		
		if( !empty($aParams['currentPage']) && ($aParams['currentPage'] == 'all') ) {
			$aParams['currentPage'] = 1;
			$aParams['entries'] = null;
		}
		
		// Current page cannot be zero or null in this case
		$aParams['currentPage'] = !empty($aParams['currentPage']) ? $aParams['currentPage'] : 1;
		
		$this->aParams = $aParams;
		
		// Use pagination base, but always start from one
		// and then end at entries multiplied by current page.
		$this->iCurrentTotal = $aParams['entries'] * $aParams['currentPage'];
		$this->start( $oDao, 1, $this->iCurrentTotal );
		
		
	}

	public function render() {
		$this->loaderEnd();
		
		$sOutput = '';
		if( !empty($this->aParams['entries']) ) {
			$sOutput .= '
				<ul class="loaderList">
					' . $this->renderPrevious() . '
					' . $this->renderNext() . '
					' . $this->renderViewAll() . '
				</ul>';
		}
		
		return '<div class="loader">' . $sOutput . '</div>';
	}

	public function renderPrevious() {
		$iPreviousPage = $this->aParams['currentPage'] - 1;
		
		// Some different end circumstances
		if( $iPreviousPage < 1 ) return;
		
		if( $this->aParams['useAjax'] !== null ) {
			// Ajax based
			return '<li class="previous"><a href="?page=' . $iPreviousPage . '&amp;' . stripGetStr( array('page') ) . '" class="ajaxRefreshView" data-ajax-view="' . $this->aParams['useAjax'] . '">' . _( 'Show less' ) . '</a></li>';
		} else {
			// None ajax
			return '<li class="previous"><a href="?page=' . $iPreviousPage . '&amp;' . stripGetStr( array('page') ) . '">' . _( 'Show less' ) . '</a></li>';
		}
		
		return false;
	}
	
	public function renderNext() {
		$iNextPage = $this->aParams['currentPage'] + 1;
		
		// Some different end circumstances
		if( $this->aParams['hideOnEmpty'] === true && $this->iPageMax <= 1 ) return;
		if( $this->iEntriesTotal < 1 ) return;
		if( $iNextPage > $this->iLoadsTotal ) return;
		
		if( $this->aParams['useAjax'] !== null ) {
			// Ajax based
			return '<li class="next"><a href="?page=' . $iNextPage . '&amp;' . stripGetStr( array('page') ) . '" class="ajaxRefreshView" data-ajax-view="' . $this->aParams['useAjax'] . '">' . _( 'Show more' ) . '</a></li>';
		} else {
			// None ajax
			return '<li class="next"><a href="?page=' . $iNextPage . '&amp;' . stripGetStr( array('page') ) . '">' . _( 'Show more' ) . '</a></li>';
		}
		
		return false;
	}

	public function renderViewAll() {
		if( $this->aParams['useAjax'] !== null ) {
			// Ajax based
			return '<li class="viewall"><a href="?page=all&amp;' . stripGetStr( array('page') ) . '" class="ajaxRefreshView" data-ajax-view="' . $this->aParams['useAjax'] . '">' . _( 'View all' ) . '</a></li>';
		} else {
			// None ajax
			return '<li class="viewall"><a href="?page=all&amp;' . stripGetStr( array('page') ) . '">' . _( 'View all' ) . '</a></li>';
		}
		
		return false;
	}
	
	public function loaderEnd() {
		$this->end();		
		
		if( !empty($this->aParams['entries']) ) {
			$this->iLoadsTotal = ceil( $this->iEntriesTotal / $this->aParams['entries'] );
		} else {
			$this->iLoadsTotal = $this->iEntriesTotal;
		}
	}

}