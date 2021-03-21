<?php
namespace Muvon\KISS;

use Closure;

final class JsonRpc {
  use RequestTrait;
  protected ?Closure $check_result_fn = null;

  final protected function __construct(protected string $url, protected ?string $user, protected ?string $password) {}

  /**
   * @see self::__construct
   */
  public static function create(string $url, ?string $user, ?string $password): self {
    return new self($url, $user, $password);
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

  /**
   * Make RPC call with preferred method and params
   *
   * @param string $method
   * @param array $params
   * @return array [err, result]
   */
  public function call(string $method, array $params): array {
    $headers = [];
    if ($this->user && $this->password) {
      $headers[] = 'Authorization: Basic ' . base64_encode($this->user . ':' . $this->password);
    }

    [$err, $response] = $this->request(
      $this->url,
      [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params ?: null
      ],
      'POST',
      $headers
    );

    if ($err) {
      return ['e_request_failed', null];
    }

    // Check JSON rpc according to protocol
    if (isset($response['error'])) {
      return ['e_response_error', null];
    }

    // Looks like PHP 8.0.3 has bug
    // When we call property directly it use __call method and recursion
    // So to workaround it we do reassign Closure and call it
    $fn = $this->check_result_fn;
    if ($fn && ($err = $fn($response['result']))) {
      return [$err, null];
    }

    // Check up result also. Cuz for example some clients can return error in result
    return [null, $response['result']];
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
}