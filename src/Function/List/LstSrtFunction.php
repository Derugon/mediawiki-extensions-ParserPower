<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Function\List;

use MediaWiki\Extension\ParserPower\ListUtils;

/**
 * Parser function for sorting list values from an identity pattern (#lstsrt).
 */
final class LstSrtFunction extends ListSortFunction {

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return 'lstsrt';
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
		return [
			...ListUtils::PARAM_OPTIONS,
			0 => 'list',
			1 => 'insep',
			2 => 'outsep',
			3 => 'sortoptions'
		];
	}
}
