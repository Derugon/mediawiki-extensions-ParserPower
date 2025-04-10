<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

use MediaWiki\Extension\ParserPower\ParserPower;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\PPFrame;

final class PatternOperation implements WikitextOperation {
	public function __construct(
		private readonly Parser $parser,
		private readonly PPFrame $frame,
		private string $pattern = '',
		private array $tokens = [],
		private string $indexToken = ''
	) {
	}

	public function apply( array $fields, ?int $index = null ): string {
		$result = $this->pattern;
		if ( $result === '' ) {
			return $fields[0];
		}

		if ( $index !== null ) {
			$result = ParserPower::applyPattern( (string)$index, $this->indexToken, $result );
		}

		foreach ( $this->tokens as $i => $token ) {
			$result = ParserPower::applyPattern( $fields[$i] ?? '', $token, $result );
		}

		$result = ParserPower::unescape( $result );
		return ParserPower::evaluateUnescaped( $this->parser, $this->frame, $result, ParserPower::WITH_ARGS );
	}
}
