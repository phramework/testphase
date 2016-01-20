# testphase
API testing environment build for phramework and RESTful APIs

[![Coverage Status](https://coveralls.io/repos/phramework/testphase/badge.svg?branch=master&service=github)](https://coveralls.io/github/phramework/testphase?branch=master) [![Build Status](https://travis-ci.org/phramework/testphase.svg?branch=master)](https://travis-ci.org/phramework/testphase)
[![StyleCI](https://styleci.io/repos/46678784/shield)](https://styleci.io/repos/46678784)

## Usage
Require package using composer

```bash
composer require phramework/testphase
```

### Execute tests written in JSON files using command line

```
./vendon/bin/testphase help -b ./bootstrap.php -d ./tests-directory/
```

Inside your `bootstrap.php` file you MAY use the `Testphase::setBase` method to set the base url of your API. For example: `Testphase::setBase('http://localhost/myapp/api/');`

### Execute tests in PHP scripts

```php
$test = (new Testphase(
    'posts/notFound',
    'GET',
    [
        'Accept: application/json'
    ]
))
->expectStatusCode(404)
->expectJSON()
->run();
```

## Development
### Install

```bash
composer update
```

### Lint and test code

```bash
composer lint
composer test
```

Testing relies on [JSONPlaceholder](http://jsonplaceholder.typicode.com/) service.

### Generate documentation

```bash
composer doc
```

## License
Copyright 2015 - 2016 Xenofon Spafaridis

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

```
http://www.apache.org/licenses/LICENSE-2.0
```

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
