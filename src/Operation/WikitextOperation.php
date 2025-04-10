<?php

/** @license GPL-2.0-or-later */

namespace MediaWiki\Extension\ParserPower\Operation;

interface WikitextOperation {
	function apply( array $fields, ?int $index = null ): string;
}
