<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\Operation\TemplateOperation;
use MediaWiki\Extension\ParserPower\Operation\WikitextOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Extension\ParserPower\Function\ParserFunctionBase;

/**
 * Parser function for mapping list values (#listmap).
 */
class ListMapFunction extends ParserFunctionBase {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'listmap';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		return ListUtils::PARAM_OPTIONS;
	}

	/**
	 * This function performs the value changing operation for the listmap function.
	 *
	 * @param WikitextOperation $operation Operation to apply.
	 * @param bool $keepEmpty True to keep empty values once the operation applied, false to remove empty values.
	 * @param array $inValues Array with the input values.
	 * @param string $fieldSep Separator between fields, if any.
	 * @return array The function output.
	 */
	protected function mapList(
		WikitextOperation $operation,
		bool $keepEmpty,
		array $inValues,
		string $fieldSep = ''
	): array {
		$fieldLimit = $operation->getFieldLimit();

		$outValues = [];
		foreach ( $inValues as $i => $inValue ) {
			$outValue = $operation->apply( ListUtils::explodeValue( $fieldSep, $inValue, $fieldLimit ), $i + 1 );
			if ( $outValue !== '' || $keepEmpty ) {
				$outValues[] = $outValue;
			}
		}

		return $outValues;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 'list' );
		$inSep = $inList !== '' ? $params->get( 'insep' ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		$template = $params->get( 'template' );
		$fieldSep = $params->get( 'fieldsep' );

		$sortMode = ListUtils::decodeSortMode( $params->get( 'sortmode' ) );
		$sortOptions = $sortMode > 0 ? ListUtils::decodeSortOptions( $params->get( 'sortoptions' ) ) : 0;
		$sorter = new ListSorter( $sortOptions );

		$duplicates = ListUtils::decodeDuplicates( $params->get( 'duplicates' ) );

		if ( $duplicates & ListUtils::DUPLICATES_PRESTRIP ) {
			$inValues = array_unique( $inValues );
		}

		if ( $template !== '' ) {
			if ( $sortMode & ListUtils::SORTMODE_PRE ) {
				$inValues = $sorter->sort( $inValues );
			}

			$operation = new TemplateOperation( $parser, $frame, $template );
			$outValues = $this->mapList( $operation, true, $inValues, $fieldSep );

			if ( $sortMode & ( ListUtils::SORTMODE_POST | ListUtils::SORTMODE_COMPAT ) ) {
				$outValues = $sorter->sort( $outValues );
			}
		} else {
			$indexToken = $params->get( 'indextoken' );
			$tokenSep = $fieldSep !== '' ? $params->get( 'tokensep' ) : '';
			$tokens = ListUtils::explodeToken( $tokenSep, $params->get( 'token' ) );
			$pattern = $params->get( 'pattern' );

			if (
				( $indexToken !== '' && $sortMode & ListUtils::SORTMODE_COMPAT ) ||
				$sortMode & ListUtils::SORTMODE_PRE
			) {
				$inValues = $sorter->sort( $inValues );
			}

			$operation = new PatternOperation( $parser, $frame, $pattern, $tokens, $indexToken );
			$outValues = $this->mapList( $operation, false, $inValues, $fieldSep );

			if (
				( $indexToken === '' && $sortMode & ListUtils::SORTMODE_COMPAT ) ||
				$sortMode & ListUtils::SORTMODE_POST
			) {
				$outValues = $sorter->sort( $outValues );
			}
		}

		if ( $duplicates & ListUtils::DUPLICATES_POSTSTRIP ) {
			$outValues = array_unique( $outValues );
		}

		$count = count( $outValues );
		if ( $count === 0 ) {
			return ParserPower::evaluateUnescaped( $parser, $frame, $params->get( 'default' ) );
		}

		if ( $count > 1 ) {
			$outSep = $params->get( 'outsep' );
			if ( $params->isDefined( 'outconj' ) ) {
				$outConj = $params->get( 'outconj' );
				if ( $outConj !== $outSep ) {
					$outConj = ' ' . trim( $outConj ) . ' ';
				}
			}
		}
		$outList = ListUtils::implode( $outValues, $outSep ?? '', $outConj ?? null );

		$countToken = $params->get( 'counttoken' );
		$intro = $params->get( 'intro' );
		$outro = $params->get( 'outro' );
		$outList = ListUtils::applyIntroAndOutro( $intro, $outList, $outro, $countToken, $count );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
