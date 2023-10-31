<?php

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Flipt\Client\FliptClient;


final class FliptClientTest extends TestCase
{

    protected MockHandler $mockHandler;
    protected array $history;
    protected FliptClient $apiClient;

    public function setUp(): void
    {

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));

        $httpClient = new Client([
            'handler' => $handlerStack,
        ]);

        $this->apiClient = new FliptClient($httpClient, 'token', 'namespace', ['context' => 'demo'], 'entityId');
    }



    public function testContextMerge(): void
    {

        $this->queueResponse(['enabled' => true, 'reason' => 'UNKNOWN_EVALUATION_REASON', 'requestDurationMillis' => 123456, 'requestId' => '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789', 'requestDurationMillis' => 0.39315, 'timestamp' => '2023-10-31T00:57:47.263242143Z']);

        $client2 = $this->apiClient->withContext(['context1' => 'one', 'context2' => 'two']);

        $client2->boolean('flag', ['user' => 'demo2', 'context1' => 'new']);
        $payload = $this->getLastPayload();

        $this->assertEquals($payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => ['user' => 'demo2', 'context1' => 'new', 'context2' => 'two'],
            'entityId' => 'entityId',
        ]);
    }


    public function testEntityId(): void
    {

        $this->queueResponse(['enabled' => true, 'reason' => 'UNKNOWN_EVALUATION_REASON', 'requestDurationMillis' => 123456, 'requestId' => '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789', 'requestDurationMillis' => 0.39315, 'timestamp' => '2023-10-31T00:57:47.263242143Z']);

        $result = $this->apiClient->boolean('flag', [], 'ENTITY');

        $payload = $this->getLastPayload();
        $this->assertEquals($payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => ['context' => 'demo'],
            'entityId' => 'ENTITY',
        ]);
    }


    public function testBoolean(): void
    {
        $this->queueResponse(['enabled' => true, 'reason' => 'MATCH_EVALUATION_REASON', 'requestDurationMillis' => 123456, 'requestId' => '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789', 'requestDurationMillis' => 0.39315, 'timestamp' => '2023-10-31T00:57:47.263242143Z']);

        // execute the client function
        $result = $this->apiClient->boolean('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertEquals($payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => ['context' => 'demo'],
            'entityId' => 'entityId',
        ]);

        $this->assertTrue($result->getEnabled());
        $this->assertEquals($result->getReason(), 'MATCH_EVALUATION_REASON');
        $this->assertEquals($result->getRequestDurationMillis(), 0.39315);
        $this->assertEquals($result->getRequestId(), '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789');
        $this->assertEquals($result->getTimestamp(), '2023-10-31T00:57:47.263242143Z');
    }


    public function testVariant(): void
    {

        $this->queueResponse(['match' => true, 'reason' => 'MATCH_EVALUATION_REASON', 'requestDurationMillis' => 123456, 'requestId' => '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789', 'requestDurationMillis' => 0.39315, 'timestamp' => '2023-10-31T00:57:47.263242143Z', 'segmentKeys' => ['foo', 'bar'], 'variantKey' => 'A', 'variantAttachment' => "{'data':'attachment'}"]);

        // execute the client function
        $result = $this->apiClient->variant('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertEquals($payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => ['context' => 'demo'],
            'entityId' => 'entityId',
        ]);

        $this->assertTrue($result->getMatch());
        $this->assertEquals($result->getReason(), 'MATCH_EVALUATION_REASON');
        $this->assertEquals($result->getRequestDurationMillis(), 0.39315);
        $this->assertEquals($result->getRequestId(), '621e48e2-9127-4309-b786-3bfa5885f4bc"23456789');
        $this->assertEquals($result->getTimestamp(), '2023-10-31T00:57:47.263242143Z');
        $this->assertEquals($result->getSegmentKeys(), ['foo', 'bar']);
        $this->assertEquals($result->getVariantKey(), 'A');
        $this->assertEquals($result->getVariantAttachment(), "{'data':'attachment'}");
    }

    protected function getLastPayload()
    {
        return json_decode($this->history[0]['request']->getBody()->getContents(), true);
    }

    protected function queueResponse(array $response)
    {
        $this->mockHandler->append(new Response(200, [], json_encode($response)));
    }
}
