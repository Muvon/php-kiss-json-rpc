<?php
namespace Muvon\KISS;

use Error;
use Closure;

final class JsonRpc {
  use RequestTrait;

  const OPT_CONNECT_TIMEOUT = 'request_connect_timeout';
  const OPT_TIMEOUT = 'request_timeout';
  const OPT_SSL_VERIFY = 'request_ssl_verify';

  final protected function __construct(protected string $url, protected ?string $user, protected ?string $password) {}

  /**
   * @see self::__construct
   */
  public static function create(string $url, ?string $user, ?string $password): self {
    $Client = new self($url, $user, $password);
    $Client->request_keepalive = 0;
    return $Client;
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
      array_map(function (array $item, int $idx) {
        [$method, $params] = $item;
        return [
          'jsonrpc' => '2.0',
          'id' => $idx,
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

    uasort($responses, function ($a, $b) {
      return $a['id'] < $b['id'] ? -1 : 1;
    });

    return array_map(function ($response) {
      // Check JSON rpc according to protocol
      if (isset($response['error'])) {
        return ['e_response_error', "{$response['error']['code']}: {$response['error']['message']}"];
      }

      return [null, $response['result']];
    }, $responses);
  }
}