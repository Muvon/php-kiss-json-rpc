# php-kiss-json-rpc

KISS implementation of lib to work with JSON RPC protocol

## Example of usage

Here is fast snippets of how to use lib

```php
use Muvon\KISS\JsonRpc;
$rpc = JsonRpc::creete(
  'http://rpc-server.ip.or.host',
  'username',
  'password'
);

// Call one method
[$err, $result] = $rpc->call('method', [1, 2, 3]);

// Call several methods asycn
$multi = [
  ['method1', ['param1', 'param2']],
  ['method2', ['param1', 'param2']],
];
[$err, $results] = $rpc->callMulti($multi);

// Call method using magic
[$err, $result] = $rpc->method_name(['param1', 'param2']);
```

## Methods

### create(string $url, ?string $username = null, ?string $password = null)

This method creates instance of object with given params. If RPC requruires authentification you need to pass username and password. Otherwise it can be omitted.

All requests made by lib is POST and JSON with Connection: close.

```php
use Muvon\KISS\JsonRpc;

$rpc = JsonRpc::create($url);
```

### call(string $method, array $params = [])

This method do simple call to RPC with given method and params. 

It returns common structure as array: [error, result]. Where is error is string presentation of error or null and result is array of result or null or even mixed value in case of we has error and want to give more info about it we send it to result var.

```php
[$err, $result] = $rpc->call('getblockcount');
```

### callMulti(array $multi)

This method is way the same as call method but receive list of commands as argument and returns same structure but result contains list of results. In case if one or more requests failed we throw Error in this method and catch itself to return error message.

```php
[$err, $results] = $rpc->callMulti(
  ['getblockhash', [1]],
  ['getblockhash', [2]],
);

// var_dump($results)
// [ array of response1, array of response2 ]
```

### magic methods

You can call any method of rpc just using magic and same rpc name method to call. It will route you to call method.

```php
$rpc->getblockhash(1);
```

### setOption(string $opt, mixed $value)

This method allows you to change available options before use of lib.

Available options are:

- **OPT_CONNECT_TIMEOUT** - int time to wait to connect to host
- **OPT_TIMEOUT** - int time to wait for response in connection
- **OPT_SSL_VERIFY** - int 0 skip verification as default or 1 check SSL cert

## Test coverage

- [x] Simple single request with call
- [x] Simple multi requests
- [x] Send correct params
