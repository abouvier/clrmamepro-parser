<?php

declare(strict_types=1);

namespace Abouvier\Clrmamepro;

/**
 * @psalm-api
 */
interface ParserInterface
{
	public function parse(string $input): array;

	public function validate(string $input): bool;
}
