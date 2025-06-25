<?php

namespace App\Http;

use App\Collections\FilterDTOCollection;
use App\DTO\FilterDTO;
use App\DTO\GetOffersDTO;
use App\Enums\Order;
use App\Enums\OrderAttribute;
use App\Http\Controllers\Controller;
use App\Requests\FilterRequest;
use App\Requests\OffersRequest;
use App\Services\RedisService;
use App\Services\Service;
use Illuminate\Http\JsonResponse;
use App\Collections\FilterResponseDTOCollection;
use App\Collections\OfferDTOCollection;

class MainController extends Controller
{
    public function __construct(
        private readonly Service $service,
    )
    {
    }

    /**
     * Get products
     * @response array{data: OfferDTOCollection, message:string}
     */
    public function offers(OffersRequest $request): JsonResponse
    {
        $filters = array_map(
            fn($value, $slug) => new FilterDTO($slug, $value),
            $request->filters,
            array_keys($request->input('filters'))
        );
        $dto = new GetOffersDTO(
            $request->input('page'),
            $request->input('limit'),
            new FilterDTOCollection($filters),
            OrderAttribute::tryFrom($request->input('sortAttribute')),
            Order::tryFrom(($request->input('sortBy')))
        );
        $result = $this->service->getOffers($dto);
        return $this->json($result);
    }

    /**
     * Get Filters
     * @response array{data: FilterResponseDTOCollection, message:string}
     */
    public function filters(FilterRequest $request): JsonResponse
    {
        $filters = array_map(
            fn($value, $slug) => new FilterDTO($slug, $value),
            $request->filters,
            array_keys($request->input('filters'))
        );
        $result = $this->service->getFilters(new FilterDTOCollection($filters));

        return $this->json($result);
    }
}