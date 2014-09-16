Datachore: Google AppEngine Datastore ODM library
=================================================

[![Build Status](https://travis-ci.org/pwhelan/datachore.svg?branch=master)](https://travis-ci.org/pwhelan/datachore)
[![Coverage Status](https://coveralls.io/repos/pwhelan/datachore/badge.png?branch=master)](https://coveralls.io/r/pwhelan/datachore?branch=master)

ODM library for Google Datastore. This library has the advantage of supporting
the protocol via the SDK and uses the local instance either locally or on the
Appengine servers.

Requirements
------------

  * AppEngine SDK 1.9.10+
  * Optional but Recommended: Composer

Installing
----------

The easiest way to work with Datachore is installing it via composer.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

1. Add the following to your composer.json file.

```json
{
    // ...
    "require": {
        "datachore/datachore": "0.0.1" // Most recent tagged version
    },
    // ...
}
```

2. Run `composer install`.

3. Include Composer's autoload file.

```php
<?php
// ....

require 'vendor/autoload.php';
```

Getting Started
---------------

### Creating model files

To create a new Entity kind you need to back it with a class extended from
Datachore\Model. Here is the mail model used in the tests, model\Test:

```php
<?php

namespace model;

use \Datachore\Type;

class Test extends \Datachore\Model
{
	protected $properties = [
		'name'		=> Type::String,
		'ref'		=> Type::Key,
		'counter'	=> Type::Integer,
		'datetime'	=> Type::Timestamp,
		'price'		=> Type::Double,
		'is_deleted'	=> Type::Boolean,
		'description'	=> Type::Blob
	];
}
```

The Key property usually refers to a model\Reference:

```php
<?php

namespace model;

use Datachore\Type;

class Reference extends Test
{
}
```

You can either manually load these model files or create them in the subdirectory
model and use a psr-4 loader that is setup in your composer.json file.

```json
{
	//...
	"autoload": {
		"psr-4": {
			"model\\": "model/"
		}
	}
	// ...
}
```

### Inserting your first Entity

To insert a new entity into the Datastore create a new instance of a model, set
the properties then call save.

```php
<?php

$test = new model\Test;
$test->name = "Testerson Tester";
$test->save();
```

Saving a reference to another entity is as simple as saving it to a property of
the type Key.

```php
<?php

$ref = new model\Reference;
$ref->name = "Robin";
$ref->save();

$test = new model\Test;
$test->name = "Batman";
$test->ref = $ref;
$test->save();
```

### Finding your Entities

Finding an entity is as easy as calling the chainable method where followed by
get (or first if searching for a single entity).

```php
<?php

$tests = model\Test::where('counter', '<=', 5)->get();

// Find Batman by name...
$batman = model\Test::where('name', '==', 'Batman')->first();

// Or the company he keeps
$robin = model\Test::where('name', '==', 'Robin')->first();
$batman = model\Test::where('ref', '==', $robin)->first();

```
