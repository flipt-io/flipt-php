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
    
    public function setUp():void {

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create( $this->mockHandler );

        $this->history = [];
        $handlerStack->push( Middleware::history( $this->history ) );

        $httpClient = new Client([
            'handler' => $handlerStack,
        ]);

        $this->apiClient = new FliptClient( $httpClient, 'token', 'namespace', [ 'context' => 'demo' ], 'entityId' );
    }



    public function testContextMerge(): void {

        $this->queueResponse( [ 'enabled' => true ] );

        $client2 = $this->apiClient->withContext( [ 'context1' => 'one', 'context2' => 'two' ] );

        $client2->boolean('flag', [ 'user' => 'demo2', 'context1' => 'new' ] );
        $payload = $this->getLastPayload();

        $this->assertEquals( $payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => [ 'user' => 'demo2', 'context1' => 'new', 'context2' => 'two' ],
            'entityId' => 'entityId',
        ]);

    }


    public function testEntityId(): void {

        // test boolean entity request
        $this->queueResponse( [ 'enabled' => true ] );

        $result = $this->apiClient->boolean('flag', [], 'ENTITY' );

        $payload = $this->getLastPayload();
        $this->assertEquals( $payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => [ 'context' => 'demo' ],
            'entityId' => 'ENTITY',
        ]);

        
    }

    
    public function testBoolean(): void
    {
        
        // specify the request response for the next call
        $this->queueResponse( [ 'enabled' => true ] );

        // execute the client function
        $result = $this->apiClient->boolean('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertEquals( $payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => [ 'context' => 'demo' ],
            'entityId' => 'entityId',
        ]);

        $this->assertTrue( $result );


        // test false 
        $this->queueResponse( [ 'enabled' => false ] );
        $result = $this->apiClient->boolean('flag');
        $this->assertFalse( $result );
    }


    public function testVariant(): void
    {
        
        $this->queueResponse( [ 'match' => true, 'variantKey' => 'A' ] );

        // execute the client function
        $result = $this->apiClient->variant('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertEquals( $payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => [ 'context' => 'demo' ],
            'entityId' => 'entityId',
        ]);

        $this->assertEquals( $result, 'A' );
        
        
        $this->queueResponse( [ 'match' => true, 'variantKey' => 'B' ] );
        $result = $this->apiClient->variant('flag');
        $this->assertEquals( $result, 'B' );

    }


    public function testVariantAttachment(): void
    {
        
        $this->queueResponse( [ 'match' => true, 'variantAttachment' => '{"demo":"json"}' ] );

        // execute the client function
        $result = $this->apiClient->variantAttachment('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertEquals( $payload, [
            'flagKey' => 'flag',
            'namespaceKey' => 'namespace',
            'context' => [ 'context' => 'demo' ],
            'entityId' => 'entityId',
        ]);

        $this->assertEquals( $result, [ 'demo' => 'json' ] );
        
        
        $this->queueResponse( [ 'match' => true, 'variantAttachment' => '{"demo2":"json2"}' ] );
        $result = $this->apiClient->variantAttachment('flag');
        $this->assertEquals( $result, [ 'demo2' => 'json2' ] );
    }



    protected function getLastPayload() {
        return json_decode( $this->history[0]['request']->getBody()->getContents(), true );
    }

    protected function queueResponse( array $response ) {
        $this->mockHandler->append(new Response(200, [], json_encode( $response )));
    }

}