<?php

namespace App\Service;

use Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchService
{
    private $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(['https://search.olona-talents.com'])
            ->build();
    }

    public function createIndex(string $indexName, array $settings = [], array $mappings = [])
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'settings' => $settings,
                'mappings' => $mappings,
            ],
        ];

        return $this->client->indices()->create($params);
    }

    public function search(array $params)
    {
        return $this->client->search($params);
    }

    public function index(array $params)
    {
        return $this->client->index($params);
    }

    public function deleteIndex(string $indexName)
    {
        return $this->client->indices()->delete(['index' => $indexName]);
    }
}