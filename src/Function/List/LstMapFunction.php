<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListSorter;
use MediaWiki\Extension\ParserPower\ListUtils;
use MediaWiki\Extension\ParserPower\Operation\PatternOperation;
use MediaWiki\Extension\ParserPower\ParameterParser;
use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Extension\ParserPower\ParserPowerConfig;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

/**
 * Parser function for mapping list values from a pattern (#lstmap).
 */
final class LstMapFunction extends ListMapFunction {

	/**
	 * @var bool Whether patterns and tokens should be expanded after token replacements.
	 */
	private bool $useLegacyExpansion;

	/**
	 * @param ParserPowerConfig $config
	 */
	public function __construct( ParserPowerConfig $config ) {
		$this->useLegacyExpansion = $config->get( 'LstmapExpansionCompat' );
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstmap';
	}

	/**
	 * @inheritDoc
	 */
	public function allowsNamedParams(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSpec(): array {
		$legacyExpansionFlags = $this->useLegacyExpansion ? [ 'novars' => true ] : [];
		return [
			ListUtils::PARAM_OPTIONS['list'],
			ListUtils::PARAM_OPTIONS['insep'],
			array_merge( ListUtils::PARAM_OPTIONS['token'], [ 'default' => 'x' ], $legacyExpansionFlags ),
			array_merge( ListUtils::PARAM_OPTIONS['pattern'], [ 'default' => 'x' ], $legacyExpansionFlags ),
			ListUtils::PARAM_OPTIONS['outsep'],
			[],
			ListUtils::PARAM_OPTIONS['sortoptions']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function execute( Parser $parser, PPFrame $frame, ParameterParser $params ): string {
		$inList = $params->get( 0 );
		$inSep = $inList !== '' ? $params->get( 1 ) : '';
		$inSep = $parser->getStripState()->unstripNoWiki( $inSep );
		$inValues = ListUtils::explode( $inSep, $inList );

		if ( count( $inValues ) === 0 ) {
			return '';
		}

		$token = $params->get( 2 );
		$pattern = $params->get( 3 );

		$sortMode = ListUtils::decodeSortMode( $params->get( 5 ) );
		$sortOptions = $sortMode > 0 ? ListUtils::decodeSortOptions( $params->get( 6 ) ) : 0;
		$sorter = new ListSorter( $sortOptions );

		if ( $sortMode & ListUtils::SORTMODE_PRE ) {
			$inValues = $sorter->sort( $inValues );
		}

		$operation = new PatternOperation( $parser, $frame, $pattern, [ $token ] );
		$outValues = $this->mapList( $operation, false, $inValues, '' );

		if ( $sortMode & ( ListUtils::SORTMODE_COMPAT | ListUtils::SORTMODE_POST ) ) {
			$outValues = $sorter->sort( $outValues );
		}

		$outSep = count( $outValues ) > 1 ? $params->get( 4 ) : '';
		$outList = ListUtils::implode( $outValues, $outSep );

		return ParserPower::evaluateUnescaped( $parser, $frame, $outList );
	}
}
