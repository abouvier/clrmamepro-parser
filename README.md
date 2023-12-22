<!--
SPDX-FileCopyrightText: 2023 Alexandre Bouvier <contact@amb.tf>

SPDX-License-Identifier: CC0-1.0
-->

# clrmamepro-parser

Parser for ClrMamePro DAT files, using [Parle](https://github.com/weltling/parle) PHP extension.

## Installation

```bash
$ composer require abouvier/clrmamepro-parser
```

## Example

```bash
$ cat "Microsoft - Xbox - BIOS Datfile (7) (2010-09-13).dat"
```

```
clrmamepro (
    name "Microsoft - Xbox - BIOS Images"
    description "Microsoft - Xbox - BIOS Images (7) (2010-09-13)"
    category Console
    version 2010-09-13
    author "Jackal | redump.org"
)

game (
    name "xbox-3944"
    description "Xbox v1.0 (Kernel Version 1.00.3944.01)"
    rom ( name xbox-3944.bin size 1048576 crc 32a9ecb6 md5 e8b39b98cf775496c1c76e4f7756e6ed sha1 67054fc88bda94e33e86f1b19be60efec0724fb6 )
)

game (
    name "xbox-4034"
    description "Xbox v1.0 (Kernel Version 1.00.4034.01)"
    rom ( name xbox-4034.bin size 1048576 crc 0d6fc88f md5 b49a417511b2dbb485aa255a32a319d1 sha1 ab676b712204fb1728bf89f9cd541a8f5a64ab97 )
)

game (
    name "xbox-4817"
    description "Xbox v1.1 (Kernel Version 1.00.4817.01)"
    rom ( name xbox-4817.bin size 1048576 crc 3f30863a md5 430b3edf0f1ea5c77f47845ed3cbd22b sha1 dc955bd4d3ca71e01214a49e5d0aba615270c03c )
)
```

```bash
$ cat example.php
```

```php
<?php

use Abouvier\Clrmamepro\Exception\ParserException;
use Abouvier\Clrmamepro\Parser;

require __DIR__.'/vendor/autoload.php';

try {
    print_r(Parser::create()->parse(file_get_contents('Microsoft - Xbox - BIOS Datfile (7) (2010-09-13).dat')));
} catch (ParserException $e) {
    fprintf(STDERR, "%s\n", $e->getMessage());
    exit(1);
}
```

```bash
$ php example.php
```

```
Array
(
    [clrmamepro] => Array
        (
            [0] => Array
                (
                    [name] => Microsoft - Xbox - BIOS Images
                    [description] => Microsoft - Xbox - BIOS Images (7) (2010-09-13)
                    [category] => Console
                    [version] => 2010-09-13
                    [author] => Jackal | redump.org
                )

        )

    [game] => Array
        (
            [0] => Array
                (
                    [name] => xbox-3944
                    [description] => Xbox v1.0 (Kernel Version 1.00.3944.01)
                    [rom] => Array
                        (
                            [0] => Array
                                (
                                    [name] => xbox-3944.bin
                                    [size] => 1048576
                                    [crc] => 32a9ecb6
                                    [md5] => e8b39b98cf775496c1c76e4f7756e6ed
                                    [sha1] => 67054fc88bda94e33e86f1b19be60efec0724fb6
                                )

                        )

                )

            [1] => Array
                (
                    [name] => xbox-4034
                    [description] => Xbox v1.0 (Kernel Version 1.00.4034.01)
                    [rom] => Array
                        (
                            [0] => Array
                                (
                                    [name] => xbox-4034.bin
                                    [size] => 1048576
                                    [crc] => 0d6fc88f
                                    [md5] => b49a417511b2dbb485aa255a32a319d1
                                    [sha1] => ab676b712204fb1728bf89f9cd541a8f5a64ab97
                                )

                        )

                )

            [2] => Array
                (
                    [name] => xbox-4817
                    [description] => Xbox v1.1 (Kernel Version 1.00.4817.01)
                    [rom] => Array
                        (
                            [0] => Array
                                (
                                    [name] => xbox-4817.bin
                                    [size] => 1048576
                                    [crc] => 3f30863a
                                    [md5] => 430b3edf0f1ea5c77f47845ed3cbd22b
                                    [sha1] => dc955bd4d3ca71e01214a49e5d0aba615270c03c
                                )

                        )

                )

        )

)
```
