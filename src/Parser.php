<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>
//
// SPDX-License-Identifier: Apache-2.0

namespace Abouvier\Clrmamepro;

use Abouvier\Clrmamepro\Exception\ParserException;
use Parle\Lexer;
use Parle\Parser as ParleParser;
use Parle\Token;

/**
 * @psalm-api
 */
final class Parser implements ParserInterface
{
	private Lexer $lexer;
	private ParleParser $parser;

	/**
	 * @var \SplFixedArray<int>
	 */
	private \SplFixedArray $productions;

	public function __construct()
	{
		$this->parser = new ParleParser();
		$this->parser->token("'('");
		$this->parser->token("')'");
		$this->parser->token('QUOTED_STRING');
		$this->parser->token('STRING');
		$this->productions = \SplFixedArray::fromArray([
			$this->parser->push('CLRMAMEPRO', 'SECTIONS'),
			$this->parser->push('SECTIONS', "NAME '(' ATTRIBUTES ')'"),
			$this->parser->push('SECTIONS', "SECTIONS NAME '(' ATTRIBUTES ')'"),
			$this->parser->push('ATTRIBUTES', "NAME '(' ATTRIBUTES ')'"),
			$this->parser->push('ATTRIBUTES', "ATTRIBUTES NAME '(' ATTRIBUTES ')'"),
			$this->parser->push('ATTRIBUTES', 'NAME VALUE'),
			$this->parser->push('ATTRIBUTES', 'ATTRIBUTES NAME VALUE'),
			$this->parser->push('VALUE', 'QUOTED_STRING'),
			$this->parser->push('VALUE', 'STRING'),
			$this->parser->push('NAME', 'STRING'),
		]);
		$this->parser->build();
		$this->lexer = new Lexer();
		$this->lexer->push('[(]', $this->parser->tokenId("'('"));
		$this->lexer->push('[)]', $this->parser->tokenId("')'"));
		$this->lexer->push('["]([^"]|\\\\["])*["]', $this->parser->tokenId('QUOTED_STRING'));
		$this->lexer->push('[^\s]{-}["]+', $this->parser->tokenId('STRING'));
		$this->lexer->push('[\s]+', Token::SKIP);
		$this->lexer->build();
	}

	public static function create(): static
	{
		return new static();
	}

	public function parse(string $input): array
	{
		$attributes = [];
		$clrmamepro = [];
		/** @var \SplStack<string> $names */
		$names = new \SplStack();
		$sections = [];
		/** @var \SplStack<string> $values */
		$values = new \SplStack();
		for (
			$this->parser->consume($input, $this->lexer);
			ParleParser::ACTION_ACCEPT != $this->parser->action;
			$this->parser->advance()
		) {
			switch ($this->parser->action) {
				case ParleParser::ACTION_ERROR:
					throw new ParserException('Parse error.');
				case ParleParser::ACTION_REDUCE:
					switch ($this->parser->reduceId) {
						case $this->productions[1]:
						case $this->productions[2]:
							$section = array_pop($sections) ?? [];
							$section = array_merge($section, $attributes);
							$clrmamepro[$names->pop()][] = $section;
							$attributes = [];
							break;
						case $this->productions[3]:
						case $this->productions[4]:
							$name = $names->pop();
							$section = array_pop($sections);
							if (isset($section[$name]) and is_array($section[$name])) {
								$section[$name][] = $attributes;
							} else {
								$section[$name] = [$attributes];
							}
							$attributes = $section;
							break;
						case $this->productions[5]:
							if ($attributes) {
								$sections[] = $attributes;
							}
							$attributes = [$names->pop() => $values->pop()];
							break;
						case $this->productions[6]:
							$attributes[$names->pop()] = $values->pop();
							break;
						case $this->productions[7]:
							$values->push(str_replace('\"', '"', substr($this->parser->sigil(0), 1, -1)));
							break;
						case $this->productions[8]:
							$values->push($this->parser->sigil(0));
							break;
						case $this->productions[9]:
							$names->push($this->parser->sigil(0));
							break;
					}
					break;
			}
		}

		return $clrmamepro;
	}

	public function validate(string $input): bool
	{
		return $this->parser->validate($input, $this->lexer);
	}
}
