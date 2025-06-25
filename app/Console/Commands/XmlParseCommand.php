<?php

namespace App\Console\Commands;

use App\Repositories\Abstracts\MainRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use XMLReader;

class XmlParseCommand extends Command
{
    protected $signature = 'xml:parse {path}';
    protected $description = 'Parsing XML';

    private const int SIZE = 500;


    public function __construct(
        private readonly MainRepositoryInterface $mainRepository,
    )
    {
        parent::__construct();
        Redis::flushAll();
        $this->cleanDB();
    }

    public function handle(): int
    {
        $path = $this->argument('path');

        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return Command::FAILURE;
        }

        $this->processXmlFile($path);
        $this->info("Success!");
        return Command::SUCCESS;
    }

    private function processXmlFile(string $path): void
    {
        $reader = new XMLReader();
        $reader->open($path);

        $offers = [];
        $redisSets = [];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'offer') {
                $offerData = $this->processOffer($reader, $redisSets);

                if ($offerData) {
                    $offers[] = $offerData;
                }

                if (count($offers) >= self::SIZE) {
                    $this->save($offers, $redisSets);
                    $offers = [];
                    $redisSets = [];
                }

                $reader->next('offer');
            }
        }

        $this->save($offers, $redisSets);
        $reader->close();
    }

    private function processOffer(XMLReader $reader, array &$redisSets): ?array
    {
        $node = simplexml_load_string($reader->readOuterXML());

        $offerData = [
            'id' => (string)$node->attributes()->id,
            'available' => ((string)$node->attributes()->available) === 'true' ? 1 : 0,
            'name' => (string)$node->name,
            'price' => (float)$node->price,
            'description' => (string)$node->description,
            'vendor' => (string)$node->vendor,
            'vendor_code' => (string)$node->vendorCode,
            'barcode' => (string)$node->barcode,
        ];

        $requiredFields = ['id', 'price', 'description', 'vendor', 'vendor_code', 'barcode', 'name'];
        if (!array_all($requiredFields, fn($field) => !empty($offerData[$field]))) {
            $this->warn("Skipping offer {$offerData['id']} — invalid data");
            return null;
        }

        $this->processOfferParams($node, $offerData['id'], $redisSets);

        return $offerData;
    }

    private function processOfferParams(\SimpleXMLElement $node, string $offerId, array &$redisSets): void
    {
        foreach ($node->param as $param) {
            $paramName = trim((string)$param['name']);
            $paramOriginalValue = trim((string)$param);

            if (!$paramName || !$paramOriginalValue) {
                continue;
            }

            $slug = Str::slug($paramName);
            $valueSlug = Str::slug($paramOriginalValue);

            $filterId = $this->getOrCreateEntity($paramName, $slug, 'filters', 'filterIdBySlug');
            $this->getOrCreateEntity($valueSlug, "$slug|$valueSlug", 'filter_values', 'getFilterValueId', $filterId);

            $redisSets["filter:$slug:$valueSlug"][] = $offerId;
        }
    }


    private function getOrCreateEntity(
        string $value,
        string $cacheKey,
        string $table,
        string $repositoryMethod,
        ?int   $filterId = null
    ): int
    {
        $mapProperty = $table === 'filters' ? 'filtersMap' : 'filterValuesMap';

        if (!isset($this->{$mapProperty}[$cacheKey])) {
            $entityId = $table === 'filters'
                ? $this->mainRepository->{$repositoryMethod}($cacheKey)
                : $this->mainRepository->{$repositoryMethod}($filterId, $value);

            if (!$entityId) {
                $data = $table === 'filters'
                    ? [['name' => $value, 'slug' => $cacheKey]]
                    : [['value' => $value, 'filter_id' => $filterId]];

                $columns = $table === 'filters'
                    ? ['name', 'slug']
                    : ['value', 'filter_id'];

                $this->mainRepository->insertMany($data, $table, $columns);

                $entityId = $table === 'filters'
                    ? $this->mainRepository->{$repositoryMethod}($cacheKey)
                    : $this->mainRepository->{$repositoryMethod}($filterId, $value);
            }

            $this->{$mapProperty}[$cacheKey] = $entityId;
        }

        return $this->{$mapProperty}[$cacheKey];
    }


    private function save(array $offers, array $redisSets): void
    {
        if (!empty($offers)) {
            $this->mainRepository->insertMany($offers, 'offers', [
                'id', 'available', 'name', 'price', 'description', 'vendor', 'vendor_code', 'barcode'
            ]);
        }

        if (!empty($redisSets)) {
            Redis::pipeline(function ($pipe) use ($redisSets) {
                foreach ($redisSets as $key => $offerIds) {
                    $pipe->sadd($key, ...$offerIds);
                }
            });
        }
    }

    private function cleanDB(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('offers')->truncate();
        DB::table('filter_values')->truncate();
        DB::table('filters')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}