<?php
namespace Muvon\KISS;

final class JsonRpc {
  use RequestTrait;

  final protected function __construct(protected string $url, protected ?string $user, protected ?string $password) {}

  /**
   * @see self::__construct
   */
  public function create(array ...$args): self {
    return new self(...$args);
  }

  /**
   * Make RPC call with preferred method and params
   * 
   * @param string $method
   * @param array $params
   * @return array [err, result]
   */
  public function call(string $method, array $params): array {
    if ($this->user && $this->password) {
      $params['username'] = $this->user;
      $params['password'] = $this->password;
    }

    [$err, $response] = $this->request(
      $this->url,
      [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params ?: null
      ],
      'POST'
    );

    if ($err) {
      return ['e_request_failed', null];
    }

    // Check JSON rpc according to protocol
    if (isset($response['error'])) {
      return [$response['error'], null];
    }

    return $response['result'];
  }
}