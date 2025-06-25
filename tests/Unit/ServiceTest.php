<?php

namespace Tests\Unit;

use App\Collections\FilterDTOCollection;
use App\Collections\FilterResponseDTOCollection;
use App\DTO\FilterDTO;
use App\DTO\FilterResponseDTO;
use App\DTO\FilterValuesResponseDTO;
use App\DTO\GetOffersDTO;
use App\Enums\Order;
use App\Enums\OrderAttribute;
use App\Repositories\Abstracts\MainRepositoryInterface;
use App\Repositories\MainRepository;
use App\Services\RedisService;
use App\Services\Service;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ServiceTest extends TestCase
{
    private MainRepositoryInterface $repository;
    private RedisService $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(MainRepositoryInterface::class);
        $this->redis = $this->createMock(RedisService::class);
    }

    public function testGetOffers()
    {
        $filterDTOCollectionMock = $this->createMock(FilterDtoCollection::class);
        $filterDTOCollectionMock->expects($this->once())
            ->method('getFilterRedisKeys')
            ->willReturn([]);

        $getOffersDTOMock = $this->createMock(GetOffersDTO::class);
        $getOffersDTOMock->expects($this->once())
            ->method('getFilters')
            ->willReturn($filterDTOCollectionMock);

        $getOffersDTOMock->expects($this->once())
            ->method('getPage')
            ->willReturn(1);

        $getOffersDTOMock->expects($this->exactly(2))
            ->method('getLimit')
            ->willReturn(10);

        $getOffersDTOMock->expects($this->once())
            ->method('getOrderAttribute')
            ->willReturn(OrderAttribute::ID);

        $getOffersDTOMock->expects($this->once())
            ->method('getOrder')
            ->willReturn(Order::ASC);

        $this->redis->expects($this->once())
            ->method('getOfferIdsFromFilters')
            ->willReturn([]);

        $collectionMock = $this->createMock(Collection::class);
        $this->repository->expects($this->once())
            ->method('getOffersByIds')
            ->willReturn($collectionMock);

        $this->getService()->getOffers($getOffersDTOMock);

    }

    public function testGetFilters()
    {
        $filtersFromDb = new Collection([
            ['slug' => 'brand', 'value' => 'Nike', 'name' => 'Brand'],
            ['slug' => 'brand', 'value' => 'Adidas', 'name' => 'Brand'],
            ['slug' => 'color', 'value' => 'Red', 'name' => 'Color'],
        ]);

        $this->repository->expects($this->once())
            ->method('getFilters')
            ->willReturn($filtersFromDb);

        $this->redis->method('getOfferIdsFromFilters')
            ->willReturnOnConsecutiveCalls(
                [1, 2, 3],
                [4, 5],
                [6]
            );

        $filterDtoCollectionMock = $this->createMock(FilterDTOCollection::class);
        $filterDtoCollectionMock->method('getFilterRedisKeys')
            ->willReturn(['filter:brand:Nike']);
        $filterDtoCollectionMock->method('getFilterValues')
            ->willReturn(['Nike']);

        $result = $this->getService()->getFilters($filterDtoCollectionMock);

        $items = $result->all();
        $this->assertNotEmpty($items);
        $first = $items[0];
        $this->assertInstanceOf(FilterResponseDTO::class, $first);
        $this->assertEquals('Brand', $first->getName());
        $this->assertEquals('brand', $first->getSlug());

        $values = $first->getValues();
        $this->assertNotEmpty($values);

        $firstValue = $values->all()[0];
        $this->assertInstanceOf(FilterValuesResponseDTO::class, $firstValue);
        $this->assertEquals('Nike', $firstValue->getValue());
        $this->assertEquals(3, $firstValue->getCount());
        $this->assertTrue($firstValue->getActive());
    }

    private function getService(): Service
    {
        return new Service($this->repository, $this->redis);
    }
}
