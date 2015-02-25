Datachore: Google AppEngine Datastore ODM library
=================================================

[![Build Status](https://travis-ci.org/pwhelan/datachore.svg?branch=0.2.3)](https://travis-ci.org/pwhelan/datachore)
[![Coverage Status](https://coveralls.io/repos/pwhelan/datachore/badge.png?branch=0.2.3)](https://coveralls.io/r/pwhelan/datachore?branch=0.2.3)

ODM library for Google Datastore. This library has the advantage of supporting
the protocol via the SDK and uses the local instance either locally or on the
Appengine servers.

This makes this library much faster than libraries accessing the Datastore via the Google API
and also makes local development dead easy.

Features
--------

  * Auto Indexer: automatically creates an index.yaml file for GAE.
  * Direct Access to the API: uses the remote api proxy, just like the python
    environment to assure the fastest access and lowest latency.

Requirements
------------

  * AppEngine SDK 1.9.10+
  * Optional but Recommended: Composer

Installing
----------

The easiest way to work with Datachore is installing it via composer. This works
just as well with Google App Engine if you check out your dependencies locally
then deploy your code.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

1. Add the following to your composer.json file.

```json
{
    "require": {
        "datachore/datachore": "dev-master"
    },
}
```

It is preferable to use a specific version, ie: 0.2.0 over dev-master. Minor
versions will contain major changes, at least under the hood, until version 1.0.0.


2. Run `composer install`.

This pulls in a local copy of datachore into the vendor sub directory for you.

3. Include Composer's autoload file.

```php
<?php
// ....

require 'vendor/autoload.php';
```

This code will autoload Datachore when it is invoked, nice huh?

Getting Started
---------------

Datachore uses an API similar to Python on Google App Engine where there is a
class representing an Entity Kind. This class has properties that define their
type.

To initialize Datachore with the SDK/Appengine first you need to choose the 
API/Datastore it will use. Most people will just use the RemoteApiProxy, which 
can be initialized with this simple line:

```php
<?php
$datastore = new Datachore\Datastore\GoogleRemoteApi;
```

Datachore will now automatically use $datastore implicitly for all connections.

In the future other APIs may be supported, such as the GData API.

The features Datachore supports so far is:

  * Types:
    * Boolean
    * Integer
    * Double
    * String
    * Set (equivalent to Lists for Python, named Set to avoid keyword collision).
    * Key
    * Timestamp

### Creating model files

To create a new Entity kind you need to back it with a class extended from
Datachore\Model. Here is the main model used in the tests; model\Test.

```php
<?php

namespace model;

use \Datachore\Type;

class Test extends \Datachore\Model
{
        // Defines the type for each property. This is mandatory.
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

The Key property usually refers to a model\Reference. In the tests the model
model\Reference is used.

```php
<?php

namespace model;

use Datachore\Type;

class Reference extends Test
{
        protected $properties = [
                'name' => Type::String
        ];
}
```

You can either manually load these model files or create them in a subdirectory
and use a psr-4 loader that is setup in your composer.json file. In this example
we use the subdirectory model.

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

### Using the AutoIndexer

To use the AutoIndexer it is simply a matter of invoking 
Datachore::ActivateAutoIndexer() then invoking Datachore::dumpIndex() once
any quieres to be indexed have been called. 

The AutoIndexer can be used to avoid any missing index errors on the live system,
so use it!

Here is an example of how to use it in your Front Controller (if you use Silex):

```php
<?php

$app = new Silex\Application
class Environment
{
        private static $is_app_engine = null;
        const GAE_APP_ID = 'Google App Engine';
        
        
        // This function is made necessary by the following problems:
        //   * GCS cannot agree on when which URL; public or image serving works,
        //     one works locally the other one @Google... And worse, some work
        //     but only as downloads, some combinations work perfectly others
        //     flat out do not respond.
        public static function isAppEngine()
        {
                if (self::$is_app_engine === null)
                {
                        self::$is_app_engine = 
                                substr($_SERVER['SERVER_SOFTWARE'], 0, strlen(self::GAE_APP_ID)) == self::GAE_APP_ID;
                }
                
                return self::$is_app_engine;
        }
}

if (!Environment::isAppEngine())
{
        Datachore\Datachore::ActivateAutoIndexer();
        $app->after(function() {
                Datachore\Datachore::dumpIndex();
        });        
}

```

Enjoy!
