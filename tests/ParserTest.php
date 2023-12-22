<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>
//
// SPDX-License-Identifier: Apache-2.0

namespace Abouvier\Clrmamepro\Tests;

use Abouvier\Clrmamepro\Exception\ParserException;
use Abouvier\Clrmamepro\Parser;
use Abouvier\Clrmamepro\ParserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-api
 */
#[CoversClass(Parser::class)]
final class ParserTest extends TestCase
{
	public function testCreateParser(): ParserInterface
	{
		$parser = Parser::create();
		static::assertInstanceOf(ParserInterface::class, $parser);

		return $parser;
	}

	#[Depends('testCreateParser')]
	#[DataProvider('invalidInputProvider')]
	public function testValidateInvalidInput(string $input, ParserInterface $parser): void
	{
		static::assertFalse($parser->validate($input));
	}

	/**
	 * @param array<string, mixed> $_expected
	 */
	#[Depends('testCreateParser')]
	#[DataProvider('validInputProvider')]
	public function testValidateValidInput(array $_expected, string $input, ParserInterface $parser): void
	{
		static::assertTrue($parser->validate($input));
	}

	#[Depends('testCreateParser')]
	#[DataProvider('invalidInputProvider')]
	public function testParseInvalidInput(string $input, ParserInterface $parser): void
	{
		$this->expectException(ParserException::class);
		$parser->parse($input);
	}

	/**
	 * @param array<string, mixed> $expected
	 */
	#[Depends('testCreateParser')]
	#[DataProvider('validInputProvider')]
	public function testParseValidInput(array $expected, string $input, ParserInterface $parser): void
	{
		static::assertSame($expected, $parser->parse($input));
	}

	/**
	 * @return list<list{string}>
	 */
	public static function invalidInputProvider(): array
	{
		return [
			[''],
			[' '],
			['game'],
			['game()'],
			['game ()'],
			['game ( )'],
			['game(name)'],
			['game (name)'],
			['game ( name)'],
			['game ( name )'],
			['game(name value)'],
			['game (name value)'],
			['game ( name value)'],
			['game (name value )'],
			['"game" ( name value )'],
			['game ( "name" value )'],
			['"game" ( "name" value )'],
			// ['game ( name () )'], // valid ?
			['game ( name ( ) )'],
			['game ( name ( name ) )'],
		];
	}

	public static function validInputProvider(): \Generator
	{
		yield 'minimalist unquoted' => [
			[
				'game' => [
					[
						'name' => 'invaders',
					],
				],
			],
			'game ( name invaders )',
		];

		yield 'minimalist quoted' => [
			[
				'game' => [
					[
						'name' => 'invaders',
					],
				],
			],
			'game ( name "invaders" )',
		];

		yield 'overwrite value with value' => [
			[
				'game' => [
					[
						'name' => 'invaders',
					],
				],
			],
			'game ( name space name invaders )',
		];

		yield 'overwrite value with array' => [
			[
				'game' => [
					[
						'name' => [
							[
								'name' => 'invaders',
							],
						],
					],
				],
			],
			'game ( name space name ( name invaders ) )',
		];

		yield 'overwrite array with value' => [
			[
				'game' => [
					[
						'name' => 'invaders',
					],
				],
			],
			'game ( name ( name space ) name invaders )',
		];

		yield 'numeric keys with numeric values' => [
			[
				'1' => [
					[
						'2' => '3',
						'4' => '5',
					],
				],
			],
			'1 ( 2 3 4 "5" )',
		];

		yield 'escaped and empty strings' => [
			[
				'game' => [
					[
						'name' => '"m\"dr"',
					],
					[
						'name' => ' ',
					],
					[
						'name' => '',
					],
				],
			],
			<<<'DATA'
				game ( name "\"m\\"dr\"" )
				game ( name " " )
				game ( name "" )
			DATA,
		];

		yield 'verysimple file' => [
			[
				'header' => [
					[
						'lol' => 'mdr',
					],
				],
				'game' => [
					[
						'name' => 'invaders',
						'description' => 'Space Invaders',
						'year' => '1978',
						'rom' => [
							[
								'name' => 'invaders.h',
								'size' => '1',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.h',
								'size' => '2',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.h',
								'size' => '3',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.h',
								'size' => '4',
								'crc' => '734f5ad8',
							],
						],
						'manufacturer' => 'Midway',
					],
					[
						'name' => 'invadpt2',
						'description' => 'Space Invaders Part II (Taito)',
						'year' => '1980',
						'manufacturer' => 'Taito',
					],
				],
			],
			<<<'DATA'
				header (
					lol mdr
				)

				game (
					name invaders
					description "Space Invaders"
					year 1978
					rom ( name invaders.h size 1 crc 734f5ad8 )
					rom ( name invaders.h size 2 crc 734f5ad8 )
					rom ( name invaders.h size 3 crc 734f5ad8 )
					rom ( name invaders.h size 4 crc 734f5ad8 )
					manufacturer "Midway"
				)

				game (
					name invadpt2
					description "Space Invaders Part II (Taito)"
					year 1980
					manufacturer "Taito"
				)
			DATA,
		];

		yield 'nested sections' => [
			[
				's1' => [
					[
						's2' => [
							[
								's3' => [
									[
										'name' => 'invaders.h',
										's4' => [
											[
												'a' => 'b',
											],
										],
										'crc' => '734f5ad8',
									],
								],
							],
						],
					],
				],
			],
			<<<'DATA'
				s1 (
					s2 (
						s3 ( name invaders.h s4 ( a b ) crc 734f5ad8 )
					)
				)
			DATA,
		];

		yield 'simple file' => [
			[
				'game' => [
					[
						'rom1' => [
							[
								'name' => 'invaders.h',
								'size' => '0',
								'crc' => '734f5ad8',
								'test' => [
									[
										'name' => 'LOL',
									],
								],
							],
						],
						'name' => 'invaders',
						'description' => 'Space Invaders',
						'year' => '1978',
						'rom' => [
							[
								'name' => 'invaders.h',
								'size' => '2',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.h',
								'size' => '3',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.h',
								'size' => '4',
								'crc' => '734f5ad8',
							],
						],
						'manufacturer' => 'Midway',
					],
					[
						'name' => 'invadpt2',
						'description' => 'Space Invaders Part II (Taito)',
						'year' => '1980',
						'manufacturer' => 'Taito',
					],
				],
			],
			<<<'DATA'
				game (
					rom1 ( name invaders.h size 0 crc 734f5ad8 test ( name LOL ) )
					name invaders
					description "Space Invaders"
					year 1978
					rom lol
					rom mdr
					rom ( name invaders.h size 1 crc 734f5ad8 )
					rom bbq
					rom ( name invaders.h size 2 crc 734f5ad8 )
					rom ( name invaders.h size 3 crc 734f5ad8 )
					rom ( name invaders.h size 4 crc 734f5ad8 )
					manufacturer "Midway"
				)

				game (
					name invadpt2
					description "Space Invaders Part II (Taito)"
					year 1980
					manufacturer "Taito"
				)
			DATA,
		];

		yield 'debug' => [
			[
				'game' => [
					[
						'rom' => [
							[
								'name' => 'invaders.1',
								'size' => '111',
							],
						],
						'lol' => 'mdr',
						'rom2' => [
							[
								'name2' => 'invaders.2',
								'size2' => '222',
							],
						],
						'bbq' => 'BBQ',
					],
					[
						'rom' => [
							[
								'name' => 'invaders.1',
								'size' => '111',
							],
						],
						'lol' => 'mdr',
						'rom2' => [
							[
								'name2' => 'invaders.2',
								'size2' => '222',
							],
						],
						'bbq' => 'BBQ',
					],
					[
						'name' => '3',
					],
				],
			],
			<<<'DATA'
				game (
					rom ( name invaders.1 size 111 )
					lol "mdr"
					rom2 ( name2 invaders.2 size2 222 )
					bbq BBQ
				)

				game (
					rom ( name invaders.1 size 111 )
					lol mdr
					rom2 ( name2 invaders.2 size2 222 )
					bbq BBQ
				)

				game (
					name 3
				)
			DATA,
		];

		yield 'simple2' => [
			[
				'game' => [
					[
						'rom1' => [
							[
								'name' => 'invaders.h',
								'size' => '0',
								'crc' => '734f5ad8',
								'test' => [
									[
										'name' => 'LOL',
									],
								],
							],
						],
						'name' => 'invaders',
						'manufacturer' => 'Midway',
					],
				],
			],
			<<<'DATA'
				game (
					rom1 ( name invaders.h size 0 crc 734f5ad8 test ( name LOL ) )
					name invaders
					manufacturer "Midway"
				)
			DATA,
		];

		yield 'simple4' => [
			[
				's1' => [
					[
						'n1' => 'v1',
						's2' => [
							[
								'n11' => 'v11',
								's3' => [
									[
										'n111' => 'v111',
										's4' => [
											[
												'n1111' => 'v1111',
												'n2222' => 'v2222',
											],
										],
										'n222' => 'v222',
									],
								],
								'n22' => 'v22',
							],
						],
						'n2' => 'v2',
					],
				],
			],
			<<<'DATA'
				s1 (
					n1 v1
					s2 (
						n11 v11
						s3 (
							n111 v111
							s4 (
								n1111 v1111
								n2222 v2222
							)
							n222 v222
						)
						n22 v22
					)
					n2 v2
				)
			DATA,
		];

		yield 'parser' => [
			[
				'game' => [
					[
						'name' => 'invaders',
						'description' => 'Space Invaders',
						'year' => '1978',
						'manufacturer' => 'Midway',
						'rom' => [
							[
								'name' => 'invaders.h',
								'size' => '2048',
								'crc' => '734f5ad8',
							],
							[
								'name' => 'invaders.g',
								'size' => '2048',
								'crc' => '6bfaca4a',
							],
							[
								'name' => 'invaders.f',
								'size' => '2048',
								'crc' => '0ccead96',
							],
							[
								'name' => 'invaders.e',
								'size' => '2048',
								'crc' => '14e538b0',
								'section1' => [
									[
										'attribute1' => 'value1',
										'section1a' => [
											[
												'attribute1a' => 'value1a',
											],
										],
									],
									[
										'attribute1' => 'value1',
									],
								],
								'section2' => [
									[
										'attribute2' => 'value2',
									],
								],
								'lol' => 'mdr',
							],
						],
					],
				],
			],
			<<<'DATA'
				game (
					name invaders
					description "Space Invaders"
					year 1978
					manufacturer "Midway"
					rom ( name invaders.h size 2048 crc 734f5ad8 )
					rom ( name invaders.g size 2048 crc 6bfaca4a )
					rom ( name invaders.f size 2048 crc 0ccead96 )
					rom (
						name invaders.e
						size 2048
						crc 14e538b0
						section1 (
							attribute1 value1
							section1a ( attribute1a value1a )
						)
						section1 ( attribute1 value1 )
						section2 ( attribute2 value2 )
						lol mdr
					)
				)
			DATA,
		];
	}
}
