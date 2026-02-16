<?php
declare(strict_types=1);

namespace App\Controller\V1;

use App\Entity\Country;
use App\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use OpenApi\Attributes as OA;


#[Route('countries')]
class CountryController extends AbstractController
{

    public function __construct(
        private readonly CountryRepository $countryRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }


    #[Route('/list', methods: ['GET'])]
    #[OA\Get(
        summary: 'List all countries (paginated)',
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number (default: 1)',
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Items per page (default: 25, max: 250)',
                schema: new OA\Schema(type: 'integer', default: 25, minimum: 1, maximum: 250)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns a paginated list of countries',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'uuid', type: 'string', example: 'USA'),
                                    new OA\Property(property: 'name', type: 'string', example: 'United States'),
                                    new OA\Property(property: 'region', type: 'string', example: 'Americas'),
                                    new OA\Property(property: 'subRegion', type: 'string', example: 'North America'),
                                    new OA\Property(property: 'demonym', type: 'string', example: 'American'),
                                    new OA\Property(property: 'population', type: 'integer', example: 331449281),
                                    new OA\Property(property: 'independant', type: 'boolean', example: true),
                                    new OA\Property(property: 'flag', type: 'string', example: '🇺🇸'),
                                    new OA\Property(
                                        property: 'currency',
                                        properties: [
                                            new OA\Property(property: 'name', type: 'string', example: 'United States dollar'),
                                            new OA\Property(property: 'symbol', type: 'string', example: '$'),
                                        ],
                                        type: 'object'
                                    ),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            properties: [
                                new OA\Property(property: 'currentPage', type: 'integer', example: 1),
                                new OA\Property(property: 'perPage', type: 'integer', example: 25),
                                new OA\Property(property: 'total', type: 'integer', example: 250),
                                new OA\Property(property: 'totalPages', type: 'integer', example: 10),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'links',
                            properties: [
                                new OA\Property(property: 'first', type: 'integer', example: 1),
                                new OA\Property(property: 'last', type: 'integer', example: 10),
                                new OA\Property(property: 'prev', type: 'integer', example: null, nullable: true),
                                new OA\Property(property: 'next', type: 'integer', example: 2, nullable: true),
                            ],
                            type: 'object'
                        ),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function getCountries(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(250, max(1, (int) $request->query->get('limit', 25)));

        $paginator = $this->countryRepository->findPaginated($page, $limit);
        $total = count($paginator);
        $totalPages = (int) ceil($total / $limit);

        $data = [];
        foreach ($paginator as $country) {
            $data[] = $country->toArray();
        }

        return new JsonResponse([
            'data' => $data,
            'meta' => [
                'currentPage' => $page,
                'perPage' => $limit,
                'total' => $total,
                'totalPages' => $totalPages,
            ],
            'links' => [
                'first' => 1,
                'last' => $totalPages,
                'prev' => $page > 1 ? $page - 1 : null,
                'next' => $page < $totalPages ? $page + 1 : null,
            ],
        ]);
    }

    /**
     * Get a single country by UUID.
     */
    #[Route('/{uuid}', methods: ['GET'], priority: -1)]
    #[OA\Get(
        summary: 'Get a country by UUID',
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                description: 'Country UUID (cca3 code, e.g. USA)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Returns the country'),
            new OA\Response(response: 404, description: 'Country not found'),
        ]
    )]
    public function getCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if ($country === null) {
            return new JsonResponse(
                ['error' => sprintf('Country with UUID "%s" not found.', $uuid)],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse($country->toArray());
    }
    /**
     * Create a new country.
     */
    #[Route('/', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new country (requires authentication)',
        security: [['basicAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['uuid', 'name'],
                properties: [
                    new OA\Property(property: 'uuid', type: 'string', example: 'TST'),
                    new OA\Property(property: 'name', type: 'string', example: 'Testland'),
                    new OA\Property(property: 'region', type: 'string', example: 'Test Region'),
                    new OA\Property(property: 'subRegion', type: 'string', example: 'Sub Region'),
                    new OA\Property(property: 'demonym', type: 'string', example: 'Tester'),
                    new OA\Property(property: 'population', type: 'integer', example: 1000),
                    new OA\Property(property: 'independant', type: 'boolean', example: true),
                    new OA\Property(property: 'flag', type: 'string', example: '🏴'),
                    new OA\Property(
                        property: 'currency',
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: 'Test dollar'),
                            new OA\Property(property: 'symbol', type: 'string', example: 'T$'),
                        ],
                        type: 'object'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Country created successfully'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Authentication required'),
            new OA\Response(response: 409, description: 'Country with this UUID already exists'),
        ]
    )]
    public function addCountry(Request $request): JsonResponse
    {
        $data = $this->decodeJsonRequest($request);

        if ($data === null) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Validate required fields
        $validationErrors = $this->validateCountryData($data, isCreate: true);

        if (!empty($validationErrors)) {
            return new JsonResponse(
                ['errors' => $validationErrors],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Check for duplicate UUID
        $existing = $this->countryRepository->findByUuid($data['uuid']);

        if ($existing !== null) {
            return new JsonResponse(
                ['error' => sprintf('Country with UUID "%s" already exists.', $data['uuid'])],
                Response::HTTP_CONFLICT
            );
        }

        $country = new Country();
        $this->populateCountryFromData($country, $data);
        $country->setUuid($data['uuid']);

        $this->entityManager->persist($country);
        $this->entityManager->flush();

        return new JsonResponse($country->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{uuid}', methods: ['PATCH'], priority: -1)]
    #[OA\Patch(
        summary: 'Update a country (requires authentication)',
        security: [['basicAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                description: 'Country UUID (cca3 code)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'region', type: 'string'),
                    new OA\Property(property: 'subRegion', type: 'string'),
                    new OA\Property(property: 'demonym', type: 'string'),
                    new OA\Property(property: 'population', type: 'integer'),
                    new OA\Property(property: 'independant', type: 'boolean'),
                    new OA\Property(property: 'flag', type: 'string'),
                    new OA\Property(
                        property: 'currency',
                        properties: [
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'symbol', type: 'string'),
                        ],
                        type: 'object'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Country updated successfully'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Authentication required'),
            new OA\Response(response: 404, description: 'Country not found'),
        ]
    )] public function updateCountry(string $uuid, Request $request): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if ($country === null) {
            return new JsonResponse(
                ['error' => sprintf('Country with UUID "%s" not found.', $uuid)],
                Response::HTTP_NOT_FOUND
            );
        }

        $data = $this->decodeJsonRequest($request);

        if ($data === null) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $validationErrors = $this->validateCountryData($data, isCreate: false);

        if (!empty($validationErrors)) {
            return new JsonResponse(
                ['errors' => $validationErrors],
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->populateCountryFromData($country, $data);
        $this->entityManager->flush();

        return new JsonResponse($country->toArray());
    }

    #[Route('/{uuid}', methods: ['DELETE'], priority: -1)]
    #[OA\Delete(
        summary: 'Delete a country (requires authentication)',
        security: [['basicAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'uuid',
                in: 'path',
                required: true,
                description: 'Country UUID (cca3 code)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Country deleted successfully'),
            new OA\Response(response: 401, description: 'Authentication required'),
            new OA\Response(response: 404, description: 'Country not found'),
        ]
    )]
    public function deleteCountry(string $uuid): JsonResponse
    {
        $country = $this->countryRepository->findByUuid($uuid);

        if ($country === null) {
            return new JsonResponse(
                ['error' => sprintf('Country with UUID "%s" not found.', $uuid)],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->entityManager->remove($country);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Decodes the JSON request body into an associative array.
     *
     * @return array<string, mixed>|null
     */
    private function decodeJsonRequest(Request $request): ?array
    {
        try {
            /** @var array<string, mixed> $data */
            $data = json_decode(
                $request->getContent(),
                true
            );

            return is_array($data) ? $data : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * Validates the incoming country data.
     *
     * @param array<string, mixed> $data
     * @param bool                 $isCreate Whether this is a create (POST) operation
     *
     * @return string[] List of validation error messages
     */
    private function validateCountryData(array $data, bool $isCreate): array
    {
        $errors = [];

        if ($isCreate) {
            if (empty($data['uuid']) || !is_string($data['uuid'])) {
                $errors[] = 'The "uuid" field is required and must be a non-empty string.';
            }

            if (empty($data['name']) || !is_string($data['name'])) {
                $errors[] = 'The "name" field is required and must be a non-empty string.';
            }
        }

        if (isset($data['name']) && !is_string($data['name'])) {
            $errors[] = 'The "name" field must be a string.';
        }

        if (isset($data['population']) && !is_int($data['population'])) {
            $errors[] = 'The "population" field must be an integer.';
        }

        if (isset($data['independant']) && !is_bool($data['independant'])) {
            $errors[] = 'The "independant" field must be a boolean.';
        }

        if (isset($data['currency']) && !is_array($data['currency'])) {
            $errors[] = 'The "currency" field must be an object with "name" and "symbol" properties.';
        }

        return $errors;
    }

    /**
     * Populates a Country entity from the given data array (partial update safe).
     *
     * @param Country              $country
     * @param array<string, mixed> $data
     */
    private function populateCountryFromData(Country $country, array $data): void
    {
        if (isset($data['name'])) {
            $country->setName((string) $data['name']);
        }

        if (array_key_exists('region', $data)) {
            $country->setRegion($data['region']);
        }

        if (array_key_exists('subRegion', $data)) {
            $country->setSubRegion($data['subRegion']);
        }

        if (array_key_exists('demonym', $data)) {
            $country->setDemonym($data['demonym']);
        }

        if (isset($data['population'])) {
            $country->setPopulation((int) $data['population']);
        }

        if (isset($data['independant'])) {
            $country->setIndependant((bool) $data['independant']);
        }

        if (array_key_exists('flag', $data)) {
            $country->setFlag($data['flag']);
        }

        if (isset($data['currency']) && is_array($data['currency'])) {
            if (array_key_exists('name', $data['currency'])) {
                $country->setCurrencyName($data['currency']['name']);
            }

            if (array_key_exists('symbol', $data['currency'])) {
                $country->setCurrencySymbol($data['currency']['symbol']);
            }
        }
    }
}