# SMW Central Validator

The lightweight form validation component used by [SMW Central](https://www.smwcentral.net). Aims to provide a friendly and flexible API at a minimal runtime cost.

* [Installation](#installation)
* [Example](#example)
* [Usage](#usage)
  * [Basic usage](#basic-usage)
  * [Shorthand types](#shorthand-types)
  * [Bailing](#bailing)
  * [Message bags](#message-bags)
  * [Manual validation](#manual-validation)
  * [CSRF tokens](#csrf-tokens)
  * [Messages](#messages)
  * [Shorthand configuration](#shorthand-configuration)
* [Tests](#tests)
* [License](#license)
* [Credits](#credits)

## Installation

Install with Composer. Requires PHP 7.3+ with the JSON and mbstring extensions.

```
$ composer require smwcentral/validator
```

## Example

```php
use SMWCentral\Validation\Validator;

$validator = new Validator($_POST);
$recipient = $validator->string('recipient')->value();
$subject = $validator->string('subject')->between(1, 255)->value();
$text = $validator->string('text')->between(1, 65535)->value();

$isRecipientValid = false; /* validate manually somehow */

if(!$isRecipientValid){
    $validator->errors()->add('recipient', 'not_found');
}

if($validator->passes()){
    // use data
}
```

## Usage

### Basic usage

Construct a `Validator`, passing it an array of fields that may be validated, usually just `$_POST`.

```php
use SMWCentral\Validation\Validator;

$validator = new Validator($_POST);
```

Use `retrieve()` to create a `Variable` object that represents a given field. By default, fields are required and will create a validation error if no value exists. Pass a second argument to specify a default value.

```php
$id = $validator->retrieve('id'); // required
$description = $validator->retrieve('description', ''); // will default to an empty string
```

The first validation rule called on each `Variable` must specify the data type. All other validation rules will throw a `LogicException` until the type is specified.

The field's value is coerced to its given data type. A validation error is added if this isn't possible.

```php
$variable->string();
$variable->integer();
$variable->float();
$variable->boolean();
$variable->array();
```

After specifying a data type, call other validation rules as appropriate. All methods on the `Variable` can be chained.

Validation rules that deal with the "size" of the value use the number itself for integers and floats or the length of strings and arrays.

```php
$variable->size($exactSize);
$variable->min($min);
$variable->max($max);
$variable->between($min, $max);
$variable->in($arrayOfPossibleValues, $useStrictComparison = true);
```

Finally, use `value()` to get the value of the field. It's guaranteed to be whichever data type the `Variable` was specified to use (or `null` if the field is optional). None of the other validation rules are necessarily met until you check the result of the validation.

```php
$value = $variable->value();
```

Use the `passes()` method on the `Validator` to check if all validation rules have succeeded. Use `errors()` to get a `MessageBag` with errors for each field.

```php
if($validator->passes()){
    // all values are guaranteed to follow the validation rules
    // continue form action
}else{
    $errors = $validator->errors();

    // show errors to the user
}
```

### Shorthand types

The `Validator` has shorthand methods that retrieve a variable and set its data type at once. You will practically always use these instead of `retrieve()`.

```php
$validator->string($name, $default = null);
$validator->integer($name, $default = null);
$validator->float($name, $default = null);
$validator->boolean($name, $default = null);
$validator->array($name, $default = null);

// is equivalent to

$validator->retrieve($name, $default = null)->string();
$validator->retrieve($name, $default = null)->integer();
$validator->retrieve($name, $default = null)->float();
$validator->retrieve($name, $default = null)->boolean();
$validator->retrieve($name, $default = null)->array();
```

A complete chain usually looks like this:

```php
// Field `description` is an optional string between 0 and 65535
// characters. Its value will be stored in `$description`.
$description = $validator->string('description', '')->between(0, 65535)->value();
```

### Bailing

By default, all validation rules called on a `Variable` will run. In the end, there may be multiple validation errors. Use the `bail()` method to ignore validation rules for a `Variable` after the first failure.

```php
// If the value is shorter than 5 characters, both `min()` and `between()`
// will create a validation error.
$variable->min(5)->between(8, 10)->value();

// If the value is shorter than 5 characters, only `min()` will create a
// validation error. `between()` (and anything after it) will be ignored.
$variable->bail()->min(5)->between(8, 10)->value();
```

### Message bags

Validation errors are collected in a `MessageBag` that can be accessed with the `errors()` method of a `Validator`. `MessageBag`s hold any number of `Message` objects for any number of fields.

```php
$errors = $validator->errors();

// Methods that check all Messages
$errors->all();
$errors->count();
$errors->isEmpty();

// Methods that operate on specific fields
$errors->except(['fields', 'to', 'exclude']);
$errors->get('field');
$errors->getFirst('field');

// `MessageBag`s can be counted and encoded to JSON
count($errors);
json_encode($errors);
```

### Manual validation

`Variable`s only provide basic validation rules. You will often need to validate the data further. Retrieve the type-casted field value with `value()`, then run any logic you need. Add your own validation errors with the `add()` method of the `MessageBag`.

For finer control over the validation error (e.g. to provide extra arguments), you can also pass an instance of `Message` to `errors()->add()`.

```php
use SMWCentral\Validation\Message;

$recipient = $validator->string('recipient')->value();

$recipientID = findUserID($recipient);

if(!$recipientID){
    $validator->errors()->add('recipient', 'not_found');

    // is equivalent to

    $validator->errors()->add('recipient', new Message('recipient', 'not_found'));
}

if($validator->passes()){
    // manual validation has succeeded
}
```

### CSRF tokens

The `passes()` method of the `Validator` can automatically check a CSRF token. To enable this functionality, set `Validator::$tokenProvider` to an instance of a custom class that implements the `ITokenProvider` interface.

You can use `passes(false)` to skip token validation if a token provider is a configured.

```php
use SMWCentral\Validation\{ITokenProvider, Validator};

Validator::$tokenProvider = new class implements ITokenProvider {
    public function getTokenKey(Validator $validator): string {
        // the name of the field that should contain the token
        return 'token';
    }

    public function getTokenValue(Validator $validator): string {
        // the expected value of the token
        return $_SESSION['token'];
    }
};
```

### Messages

Validation errors are represented by `Message` objects.

```php
$message = $validator->errors()->getFirst('field');

echo $message;

// is equivalent to

echo $message->getTranslatedMessage();
```

Internally, messages only contain a key that is later passed to a message resolver to construct a human-friendly message. This package includes a rudimentary default resolver.

In most cases, you'll want to use your own resolver. Set `Message::$resolver` to an instance of a custom class that implements the `IMessageResolver` interface. Check the `getMessages()` method in `src/DefaultMessageResolver.php` for a list of message keys that the validator will use.

```php
use SMWCentral\Validation\{Message, MessageResolver};

Message::$resolver = new class implements IMessageResolver {
    public function resolveMessage(string $field, string $key, array $args): string {
        return "{$field}: {$key}";
    }
};
```

Note that `Message`s maintain their own copy of the field name, not necessarily identical to the field name that owns the `Message` in the `MessageBag`.

The `$args` array passed to `resolveMessage()` contains a set of arbitrary additional arguments. Some validation rules will give such arguments to their messages. For example, the `between()` rule will add `min` and `max` arguments. You can also provide custom arguments to any `Variable` using the third parameter of the `Validator`'s `retrieve()` method and its shorthands.

```php
$validator->string('name', null, ['context' => 'username'])->value();
```

When adding validation errors manually, you can create a `Message` yourself to provide arguments.

```php
use SMWCentral\Validation\Message;

$validator->errors()->add('recipient', new Message('recipient', 'not_found', ['context' => 'private_message']));
```

The default message resolver will directly include the field's name in the message. To ensure a coherent message is created, you can pass an argument named `field` to override the name:

```php
$validator->string('new_pass')->min(8);
// DefaultMessageResolver could produce: The new_pass must have at least 8 characters.

$validator->string('new_pass', null, ['field' => 'new password'])->min(8);
// DefaultMessageResolver could produce: The new password must have at least 8 characters.
```

Of course, it is up to you to implement such logic in a custom message resolver.

### Shorthand configuration

You can use `Validator::configure()` to set a message resolver and a token provider at once.

```php
Validator::configure($messageResolver, $tokenProvider);
```

If `null` is passed as one of the arguments, it will be ignored.

## Tests

Tests are in the `tests` directory and use PHPUnit. Run with `vendor/bin/phpunit tests`.

## License

Released under the [MIT License](https://github.com/telinc1/smwcentral-validator/blob/master/LICENSE).

## Credits

Built and maintained by [Telinc1](https://github.com/telinc1). SMW Central is property of [Noivern](https://smwc.me/u/6651).
