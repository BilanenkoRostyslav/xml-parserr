<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh --seed');

        Redis::sAdd('filter:brand:Apple', 1, 2, 3, 4, 5);
        Redis::sAdd('filter:brand:Samsung', 1, 3, 5);
        Redis::sAdd('filter:color:Red', 1, 4);
        Redis::sAdd('filter:size:Small', 5, 6);
        Redis::sAdd('filter:size:Large', 3, 4);
        Redis::sAdd('filter:color:BLue', 2, 6);
    }

    public function testOffersStructure()
    {
        $response = $this->get('/api/catalog/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'price',
                    'description',
                ]
            ],
            'message'
        ]);
    }

    public function testOffersWithFilters()
    {
        $response = $this->get('api/catalog/products?filters[brand]=Samsung&filters[color]=Red');
        $response->assertStatus(200);

        $this->assertJson($response->getContent());
        $this->assertJsonStringEqualsJsonString($response->getContent(), json_encode([
            'data' => [
                [
                    'id' => 1,
                    'name' => 'Offer 1',
                    'price' => "100",
                    'description' => 'Description for offer 1',
                ]
            ], 'message' => ''
        ]));
    }

    public function testOffersValidationFailed()
    {
        $response = $this->get('api/catalog/products?page=a');
        $response->assertStatus(422);
        $this->assertJson($response->getContent());
        $this->assertJsonStringEqualsJsonString($response->getContent(), json_encode([
            'page' => [
                "The page field must be an integer."
            ]
        ]));

    }

    public function testFilters()
    {
        $response = $this->get('api/catalog/filters');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'name',
                    'slug',
                    'values' => [
                        '*' => ['value',
                            'count',
                            'active']
                    ]
                ]
            ],
            'message'
        ]);
    }
}