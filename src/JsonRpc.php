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
    if (!str_contains($name, '_')) {
      $name = preg_replace(
        '/(^|[a-z])([A-Z])/e',
        'strtolower(strlen("\\1") ? "\\1_\\2" : "\\2")',
        $name
      );
    }

    return $this->call($name, $args);
  }
}