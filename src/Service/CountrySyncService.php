<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CountrySyncService
{
    private const API_URL = 'https://restcountries.com/v3.1/all';
    private const API_FIELDS = 'name,cca3,region,subregion,demonyms,population,independent,flag,currencies';
    private const BATCH_SIZE = 200;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function sync(): array
    {
        $apiCountries = $this->fetchFromApi();
        $existingCountries = $this->countryRepository->findAllIndexedByUuid();
        $apiUuids = [];

        $created = 0;
        $updated = 0;
        $i = 0;

        foreach ($apiCountries as $data) {
            $uuid = $data['cca3'] ?? null;

            if ($uuid === null || $uuid === '') {
                continue;
            }

            $apiUuids[] = $uuid;

            if (isset($existingCountries[$uuid])) {
                $country = $existingCountries[$uuid];
                $updated++;
            } else {
                $country = new Country();
                $country->setUuid($uuid);
                $this->entityManager->persist($country);
                $created++;
            }

            $this->mapApiDataToEntity($country, $data);

            if (++$i % self::BATCH_SIZE === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $existingCountries = $this->countryRepository->findAllIndexedByUuid();
            }
        }

        $this->entityManager->flush();

        $staleUuids = array_diff(array_keys($existingCountries), $apiUuids);
        $deleted = 0;

        if (!empty($staleUuids)) {
            $deleted = $this->countryRepository->deleteByUuids($staleUuids);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
        ];
    }

    private function fetchFromApi(): array
    {
        $response = $this->httpClient->request('GET', self::API_URL, [
            'query' => ['fields' => self::API_FIELDS],
        ]);

        $data = $response->toArray();

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid response from REST Countries API.');
        }

        return $data;
    }

    private function mapApiDataToEntity(Country $country, array $data): void
    {
        $country->setName($data['name']['common'] ?? 'Unknown');
        $country->setRegion($data['region'] ?? null);
        $country->setSubRegion($data['subregion'] ?? null);
        $country->setPopulation((int) ($data['population'] ?? 0));
        $country->setIndependant((bool) ($data['independent'] ?? false));
        $country->setFlag($data['flag'] ?? null);

        $demonym = $data['demonyms']['eng']['m'] ?? null;
        $country->setDemonym($demonym);

        $currencies = $data['currencies'] ?? [];
        $firstCurrency = !empty($currencies) ? reset($currencies) : null;
        $country->setCurrencyName($firstCurrency['name'] ?? null);
        $country->setCurrencySymbol($firstCurrency['symbol'] ?? null);
    }
}
