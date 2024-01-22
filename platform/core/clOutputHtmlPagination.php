<?php

require_once PATH_CORE . '/clPaginationBase.php';
require_once PATH_FUNCTION . '/fOutputHtml.php';

class clOutputHtmlPagination extends clPaginationBase implements ifPagination {

	public $aParams = array();

	private $oRouter;

	public function __construct( $oDao, $aParams = array() ) {
		$aParams += array(
			'firstLast' => true,
			'firstLastType' => 'numbers',
			'goTo' => false,
			'numbers' => true,
			'numbersAmount' => 10,
			'numbersType' => 'sliding',
			'prevNext' => true,
			'total' => true,
			'currentPage' => isset( $_GET['page'] ) ? $_GET['page'] : null,
			'stringSingular' => _( 'Item' ),
			'stringPlural' => _( 'Items' ),
			'entries' => null,
			'selectEntries' => null,
			'hideOnEmpty' => false
		);

		if( isset($_GET['page']) && ($_GET['page'] == 'all') ) {
			$aParams['entries'] = 0;
			$aParams['currentPage'] = null;
		}

		$this->aParams = $aParams;
		$this->oRouter = clRegistry::get( 'clRouter' );

		$this->start( $oDao, $aParams['currentPage'], $aParams['entries'] );
	}

	public function render() {
		$this->end();
		if( $this->aParams['hideOnEmpty'] === true && $this->iPageMax <= 1 ) return;
		if( $this->iEntriesTotal < 1 ) return;
		$sOutput = '';
		if( $this->aParams['selectEntries'] !== null ) $sOutput .= $this->renderSelectEntries();

		$iStartItem = ( ($this->iCurrentPage - 1) * $this->aParams['entries'] ) + 1;
		$iEndItem = $iStartItem + $this->aParams['entries'] - 1;
		if( $iEndItem > $this->iEntriesTotal ) $iEndItem = $this->iEntriesTotal;

		if( $this->aParams['total'] ) $sOutput .= '
			<p style="clear:left" class="entriesTotal">' . $iStartItem . '-' . $iEndItem . ' ' . _( 'av' ) . ' ' . $this->iEntriesTotal . ' </p>';

		// if( $this->iPageMax > 1 ) {
			$sOutput .= '<ul itemscope itemtype="http://schema.org/pagination" class="paginationList">';
			if( $this->aParams['goTo'] ) $sOutput .= $this->renderGoTo();
			// if( $this->aParams['firstLast'] ) $sOutput .= $this->renderFirst();
			if( $this->aParams['prevNext'] ) $sOutput .= $this->renderPrevious();
			// if( $this->aParams['numbers'] ) $sOutput .= $this->renderNumbers();
			if( $this->aParams['prevNext'] ) $sOutput .= $this->renderNext();
			// if( $this->aParams['firstLast'] ) $sOutput .= $this->renderLast();
			// $sOutput .= '<li class="viewall"><a href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=all" class="ajax">' . _( 'All' ) . '</a></li>';
			$sOutput .= '</ul>';
		// }

		$sOutput = '<div class="pagination">' . $sOutput . '</div>';

		return $sOutput;
	}

	public function renderFirst() {
		return $this->iCurrentPage > 1 ? '
			<li class="first"><a href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=1&amp;' . stripGetStr( array('page') ) . '" class="ajax"><i class="fas fa-angle-double-left"></i></a></li>' : '';
	}

	public function renderLast() {
		return $this->iCurrentPage < $this->iPageMax ? '
			<li class="last"><a href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=' . $this->iPageMax . '&amp;' . stripGetStr( array('page') ) . '" class="ajax"><i class="fas fa-angle-double-right"></i></a></li>' : '';
	}

	public function renderNext() {
		$iNextPage = $this->iCurrentPage + 1;
		if( $iNextPage > $this->iPageMax ) {
			return '
				<li class="next"><a rel="next" href="#" class="ajax disabled"><i class="fas fa-angle-right"></i></a></li>';
		} else {
			return '
				<li class="next"><a rel="next" href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=' . $iNextPage . '&amp;' . stripGetStr( array('page') ) . '" class="ajax"><i class="fas fa-angle-right"></i></a></li>';
		}
	}

	public function renderPrevious() {
		$iPreviousPage = $this->iCurrentPage - 1;
		if( $iPreviousPage < 1 ) {
			return '
				<li class="previous"><a rel="prev" href="#" class="ajax disabled"><i class="fas fa-angle-left"></i></a></li>';
		} else {
			return '
				<li class="previous"><a rel="prev" href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=' . $iPreviousPage . '&amp;' . stripGetStr( array('page') ) . '" class="ajax"><i class="fas fa-angle-left"></i></a></li>';
		}
	}

	public function renderGoTo() {
		return '
		<form action="' . $this->oRouter->sPath . '" method="get">
			<label for="page">' . _( 'Go to page' ) . '</label>
			<input title="' . _( 'Go to page' ) . '" id="page" name="page" type="text" class="text">
			<button type="submit">' . _( 'Go' ) . '</button>
		</form>';
	}

	public function renderNumbers() {
		$sOutput = '';

		$iNumbersAmount = $this->aParams['numbersAmount'] > $this->iPageMax ? $this->iPageMax : $this->aParams['numbersAmount'];

		if( $this->aParams['numbersType'] == 'jumping' ) {

		} else {
			$iNumberStart = ceil( $this->iCurrentPage - $iNumbersAmount / 2 );
			if( $iNumberStart < 1 ) $iNumberStart = 1;

			$iNumberEnd = $iNumberStart + $iNumbersAmount - 1;
			if( $iNumberEnd > $this->iPageMax ) $iNumberEnd = $this->iPageMax;

			if( ($iNumberEnd - $iNumberStart) < $iNumbersAmount ) $iNumberStart = $iNumberEnd - $iNumbersAmount + 1;
		}

		for( $i = $iNumberStart; $i <= $iNumberEnd; $i++ ) {
			$sOutput .= '
			<li' . ( $this->iCurrentPage == $i ? ' class="active"' : '' ) . '><a href="' . htmlspecialchars( $this->oRouter->sPath ) . '?page=' . $i . '&amp;' . stripGetStr( array('page') ) . '" class="ajax">' . $i . '</a></li>';
		}

		return $sOutput;
	}

	public function renderSelectEntries() {
		if( !is_array($this->aParams['selectEntries']) ) {
			$aSelectNumbers = array(
				30,
				60,
				90,
				120
			);
		} else {
			$aSelectNumbers = $this->aParams['selectEntries'];
		}

		$sOuput = '
		<form action="' . htmlspecialchars( $this->oRouter->sPath ) . '" method="get" class="selectEntries">
			<label for="entries">' . _( 'Entries per page' ) . '</label>
			<select title="' . _( 'Entries per page' ) . '" id="entries" name="entries">';

		foreach( $aSelectNumbers as $iEntries ) {
			$sOuput .= '
			<option value="' . $iEntries . '"' . ( !empty($_GET['entries']) && $_GET['entries'] == $iEntries ? 'selected="selected"' : '' ) . '>' . $iEntries . '</option>';
		}

		$sOuput .= '
			</select>';

		$sHidden = '';
		$aToStrip = array(
			'ajax',
			'section',
			'view',
			'layout',
			'entries',
			'page',
			'event'
		);
		$aQueries = array_diff_key( $_GET, array_flip($aToStrip) );
		foreach( $aQueries as $key => $value ) {
			if( $key == 'page' ) continue;
			$sHidden .= '<input type="hidden" name="' . $key . '" value= "' . $value . '" />';
		}


		$sOuput .= '
			' . $sHidden . '
			<button type="submit">' . _( 'Go' ) . '</button>
		</form>';

		return $sOuput;
	}

	public function renderTotals() {

	}

}
