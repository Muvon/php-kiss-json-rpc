<?php
namespace Muvon\KISS;

use Error;
use Closure;

final class JsonRpc {
  use RequestTrait;

  const OPT_CONNECT_TIMEOUT = 'request_connect_timeout';
  const OPT_TIMEOUT = 'request_timeout';
  const OPT_SSL_VERIFY = 'request_ssl_verify';

  protected ?Closure $check_result_fn = null;

  final protected function __construct(protected string $url, protected ?string $user, protected ?string $password) {}

  /**
   * @see self::__construct
   */
  public static function create(string $url, ?string $user, ?string $password): self {
    $Client = new self($url, $user, $password);
    $Client->request_keepalive = 0;
    return $Client;
  }

  /**
   * Optional set result checking in request
   * This function set return null if no error occured and we process response
   * Or error code as string in case if we have error and should return it by checkign result
   *
   * @param Closure $fn
   * @return self
   */
  public function setCheckResultFn(Closure $fn): self {
    $this->check_result_fn = $fn;
    return $this;
  }

  public function setOption(string $option, mixed $value): self {
    $this->$option = $value;
    return $this;
  }

  /**
   * Make RPC call with preferred method and params
   *
   * @param string $method
   * @param array $params
   * @return array [err, result]
   */
  public function call(string $method, array $params = []): array {
    [[$err, $result]] = $this->doRequest([[$method, $params]]);
    return [$err, $result];
  }

  /**
   * Do multiple request with async
   *
   * @param array $cmds list of commands that applyes to default call method
   * @return array list of struct [err, result] for each request perfromed
   */
  public function callMulti(array $cmds): array {
    $responses = $this->doRequest($cmds);
    $result = [];
    foreach ($responses as [$err, $response]) {
      $result[] = [$err, $response ?: null];
    }

    return $result;
  }

  /**
   * This methods make available to call RPC with camel case
   * You can use underscore notation also
   *
   * @param string $name
   * @param array $args
   * @return array Same as call method
   * @see self::call
   */
  public function __call(string $name, array $args): array {
    return $this->call($name, $args);
  }

  protected function doRequest(array $cmds): array {
    $headers = [
      'Connection: close',
    ];
    if ($this->user && $this->password) {
      $headers[] = 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password);
    }

    [$err, $responses] = $this->request(
      $this->url,
      array_map(function (array $item) {
        [$method, $params] = $item;
        return [
          'jsonrpc' => '2.0',
          'method' => $method,
          'params' => $params ?: null,
        ];
      }, $cmds),
      'POST',
      $headers
    );

    if ($err) {
      return [[$err, $responses]];
    }

    // Looks like PHP 8.0.3 has bug
    // When we call property directly it use __call method and recursion
    // So to workaround it we do reassign Closure and call it
    $fn = $this->check_result_fn;
    return array_map(function ($response) use ($fn) {
      // Check JSON rpc according to protocol
      if (isset($response['error'])) {
        return ['e_response_error', null];
      }

      if ($fn && ($err = $fn($response['result']))) {
        return [$err, null];
      }

      return [null, $response['result']];
    }, $responses);
  }
}