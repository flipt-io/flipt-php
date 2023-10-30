# Flipt php client


This client is a wrapper around the [flipt.io](https://www.flipt.io) REST API to easily evaluate flags with a given context on a a remote flipt instance.


## Getting started


Install the client with composer:

```Bash
composer install flipt/client
```

Instantiate a client with the corresponding settings:

```php
$flipt = new \Flipt\Client( 'https://my-flipt.io', '<apiToken>', '<default namespace>', [ 'default' => 'context' ] );

// test on a boolean flag:
if( $flipt->boolean( 'my-boolean' ) ) {
    // do somthing 
}


// get a variant key
if( $flipt->variant( 'my-variant' ) == 'demo' ) {
    // do something
}


// get a variant attachment
$array = $flipt->variantAttchment( 'my-variant' );
// the returned value is an array and you can access properties like:
if( $array['key'] == 'demo' ) {
    // do something
}
```



### Adjust the context

You can setup the context in the constructor as shown in the example above.
But you can always overwrite context values when accessing a flag as the following example shows:
```php
$flipt = new \Flipt\Client( 'https://my-flipt.io', 'token', 'namespace', [ 'environment' => 'test', 'user' => '23' ] );


// will send the context [ 'environment' => 'test', 'user' => '23' ] as defined in the client
$test = $flipt->boolean( 'flag' ); 

// will send the context [ 'environment' => 'test', 'user' => '50' ] as it will merge the client context with the current from the call
$test2 = $flipt->boolean( 'flag', [ 'user' => '50' ] );

```


### Query another namespace

If you need the query another namespace you can switch the namespace as follows:

```php
// this client will query against the A namespace
$fliptA = new \Flipt\Client( 'https://my-flipt.io', 'token', 'A' );


// this will create a new client with all the settings from $fliptA client except the namespace that will change to B
$fliptB = $fliptA->withNamespace( 'B' ),

// this will test on namespace B
$fliptB->boolean('flag')
```
