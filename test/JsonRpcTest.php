<?php

use Muvon\KISS\JsonRpc;
use PHPUnit\Framework\TestCase;

final class JsonRpcTest extends TestCase {
  protected JsonRpc $Client;
  public function setUp(): void {
    parent::setUp();
    $this->Client = JsonRpc::create('http://localhost:8000', null, null);
  }

  public function testSimpleSingleRequestWithCall() {
    [$err, $result] = $this->Client->call('date', []);
    $this->assertEquals(null, $err);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('date', $result);
    $this->assertEquals(date('Y-m-d'), $result['date']);
  }

  public function testSimpleMultiRequests() {
    $cmds = [
      ['value', ['value' => uniqid()]],
      ['value', ['value' => uniqid()]],
      ['value', ['value' => uniqid()]],
    ];
    $results = $this->Client->callMulti($cmds);
    $this->assertEquals(sizeof($cmds), sizeof($results));
    foreach ($results as $k => [$err, $result]) {
      $this->assertEquals(null, $err);
      $this->assertIsArray($result);
      $this->assertArrayHasKey('value', $result);
      $this->assertEquals($cmds[$k][1]['value'], $result['value']);
    }
  }

  public function testSendCorrectParams() {
    $value = uniqid();
    [$err, $result] = $this->Client->call('value', ['value' => $value]);
    $this->assertEquals(null, $err);
    $this->assertIsArray($result);
    $this->assertArrayHasKey('value', $result);
    $this->assertEquals($value, $result['value']);
  }
}