<?php

namespace Flipt\Client;

use GuzzleHttp\Client;
use Flipt\Models\BooleanEvaluationResult;
use Flipt\Models\VariantEvaluationResult;
use Flipt\Models\DefaultBooleanEvaluationResult;
use Flipt\Models\DefaultVariantEvaluationResult;

final class FliptClient
{

    protected Client $client;
    protected string $apiToken;
    protected string $namespace;
    protected string $entityId;
    protected array $context;


    public function __construct(string|Client $host, string $apiToken, string $namespace, array $context = [], string $entityId = '')
    {
        $this->apiToken = $apiToken;
        $this->namespace = $namespace;
        $this->context = $context;
        $this->entityId = $entityId;

        $this->client = (is_string($host)) ? new Client(['base_uri' => $host]) : $host;
    }


    /**
     * Returns the boolean evaluation result
     */
    public function boolean(string $name, $context = [], $entityId = NULL): BooleanEvaluationResult
    {

        $response = $this->evaluationRequest('/evaluate/v1/boolean', $name, $context, $entityId);
        return new DefaultBooleanEvaluationResult($response['enabled'], $response['reason'], $response['requestDurationMillis'], $response['requestId'], $response['timestamp']);
    }



    /**
     * Returns the variant evaluation result
     */
    public function variant(string $name, $context = [], $entityId = NULL): VariantEvaluationResult
    {

        $response = $this->evaluationRequest('/evaluate/v1/variant', $name, $context, $entityId);
        return new DefaultVariantEvaluationResult($response['match'], $response['reason'], $response['requestDurationMillis'], $response['requestId'], $response['timestamp'], $response['segmentKeys'], $response['variantKey'], $response['variantAttachment']);
    }



    /**
     * Helper function to create an evaluation request based on the client settings
     */
    protected function evaluationRequest(string $path, string $name, $context = [], $entityId = NULL)
    {

        return $this->apiRequest($path, [
            'context' => array_merge($this->context, $context),
            'entityId' => isset($entityId) ? $entityId : $this->entityId,
            'flagKey' => $name,
            'namespaceKey' => $this->namespace,
        ]);
    }



    /**
     * Helper function to perform a guzzle request with the correct headers and body
     */
    protected function apiRequest(string $path, array $body = [], string $method = 'POST')
    {

        $response = $this->client->request($method, $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json'
            ],
            'body' => json_encode($body, JSON_FORCE_OBJECT),
        ]);

        return json_decode($response->getBody(), true);
    }


    /**
     * Create a new client with a different namespace
     */
    public function withNamespace(string $namespace)
    {
        return new FliptClient($this->client, $this->apiToken, $namespace, $this->context, $this->entityId);
    }

    /**
     * Create a new client with a different context
     */
    public function withContext(array $context)
    {
        return new FliptClient($this->client, $this->apiToken, $this->namespace, $context, $this->entityId);
    }
}
