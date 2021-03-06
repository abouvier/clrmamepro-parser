<?php

declare(strict_types=1);

namespace Abouvier\Clrmamepro;

use Parle\Lexer;
use Parle\Parser as ParleParser;
use Parle\Token;
use SplFixedArray;

final class Parser implements ParserInterface
{
    private Lexer $lexer;
    private ParleParser $parser;
    private SplFixedArray $productions;

    public function __construct()
    {
        $this->parser = new ParleParser();
        $this->parser->token("'('");
        $this->parser->token("')'");
        $this->parser->token('QUOTED_STRING');
        $this->parser->token('STRING');
        $this->productions = new SplFixedArray(8);
        $this->productions[0] = $this->parser->push('START', 'SECTIONS');
        $this->productions[1] = $this->parser->push('SECTIONS', "STRING '(' ATTRIBUTES ')'");
        $this->productions[2] = $this->parser->push('SECTIONS', 'SECTIONS SECTIONS');
        $this->productions[3] = $this->parser->push('ATTRIBUTES', 'STRING VALUE');
        $this->productions[4] = $this->parser->push('ATTRIBUTES', "STRING '(' ATTRIBUTES ')'");
        $this->productions[5] = $this->parser->push('ATTRIBUTES', 'ATTRIBUTES ATTRIBUTES');
        $this->productions[6] = $this->parser->push('VALUE', 'QUOTED_STRING');
        $this->productions[7] = $this->parser->push('VALUE', 'STRING');
        $this->parser->build();
        $this->lexer = new Lexer();
        $this->lexer->push('[(]', $this->parser->tokenId("'('"));
        $this->lexer->push('[)]', $this->parser->tokenId("')'"));
        $this->lexer->push('["]([^"]|\\\\["])*["]', $this->parser->tokenId('QUOTED_STRING'));
        $this->lexer->push('[^\s]{-}["]+', $this->parser->tokenId('STRING'));
        $this->lexer->push('[\s]+', Token::SKIP);
        $this->lexer->build();
    }

    public function parse(string $input): array
    {
        $attributes = [];
        $depth = 1;
        $sections = [];
        $string = '';
        for (
            $this->parser->consume($input, $this->lexer);
            ParleParser::ACTION_ACCEPT != $this->parser->action;
            $this->parser->advance()
        ) {
            switch ($this->parser->action) {
                case ParleParser::ACTION_ERROR:
                    throw new ParserException('Parse error.');
                    break;
                case ParleParser::ACTION_REDUCE:
                    switch ($this->parser->reduceId) {
                        case $this->productions[1]:
                        case $this->productions[4]:
                            $section = [];
                            foreach (array_splice($attributes, -$depth) as [$name, $value]) {
                                if (is_array($value)) {
                                    $section[$name][] = $value;
                                } else {
                                    $section[$name] = $value;
                                }
                            }
                            if ($this->parser->reduceId == $this->productions[4]) {
                                $attributes[] = [
                                    $this->parser->sigil(0),
                                    $section,
                                ];
                            } else {
                                $sections[$this->parser->sigil(0)][] = $section;
                            }
                            $depth = 1;
                            break;
                        case $this->productions[3]:
                            $attributes[] = [
                                $this->parser->sigil(0),
                                $string,
                            ];
                            break;
                        case $this->productions[5]:
                            $depth++;
                            break;
                        case $this->productions[6]:
                            $string = str_replace('\"', '"', substr($this->parser->sigil(0), 1, -1));
                            break;
                        case $this->productions[7]:
                            $string = $this->parser->sigil(0);
                            break;
                    }
                    break;
            }
        }

        return $sections;
    }

    public function validate(string $input): bool
    {
        return $this->parser->validate($input, $this->lexer);
    }
}
