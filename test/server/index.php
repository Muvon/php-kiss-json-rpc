<?php
$request = json_decode(file_get_contents('php://input'), true);
$params = $request['params'];
echo match ($request['method']) {
  'value' => response(null, ['value' => $params['value']]),
  'date' => response(null, ['date' => date('Y-m-d')]),
  default => response('default', null),
};

function response(?string $error_key, ?array $result): string {
  static $errors = [
    'default' => [
      'error' => [
        'code' => 1,
      ],
    ],
  ];

  $response = [
    'id' => microtime(true),
  ];

  $error = $errors[$error_key] ?? null;
  if ($error) {
    $response['error'] = $error;
  }

  if ($result) {
    $response['result'] = $result;
  }

  return json_encode($response);
}