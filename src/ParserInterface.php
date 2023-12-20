<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>
//
// SPDX-License-Identifier: Apache-2.0

namespace Abouvier\Clrmamepro;

/**
 * @psalm-api
 */
interface ParserInterface
{
	public function parse(string $input): array;

	public function validate(string $input): bool;
}
