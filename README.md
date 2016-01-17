# testphase
API testing environment build for phramework and RESTful APIs

## Usage

```
./vendon/bin/testphase help -b ./bootstrap.php -d ./tests-directory/
```

Inside your `bootstrap.php` file you should use the `Testphase::setBase` method to set the base url of your api. For example `Testphase::setBase('http://localhost/myapp/api');`

## Development
### Install

```bash
composer update
```

### Test and lint code

```bash
composer lint
composer test
```

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
