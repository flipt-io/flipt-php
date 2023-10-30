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

        $this->apiClient = new FliptClient( $httpClient, 'token', 'namespace', [ 'context' => 'demo' ] );
    }

    
    public function testBoolean(): void
    {
        
        // specify the request response for the next call
        $this->queueResponse( [ 'enabled' => true ] );

        // execute the client function
        $result = $this->apiClient->boolean('flag');

        // get payload on request to validate
        $payload = $this->getLastPayload();

        $this->assertTrue( $result );
    }


    protected function getLastPayload() {
        return json_decode( $this->history[0]['request']->getBody()->getContents() );
    }

    protected function queueResponse( array $response ) {
        $this->mockHandler->append(new Response(200, [], json_encode( $response )));
    }

}