<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(name: 'country')]
class Country
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 10, unique: true)]
    private string $uuid;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(name: 'sub_region', type: 'string', length: 255, nullable: true)]
    private ?string $subRegion = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $demonym = null;

    #[ORM\Column(type: 'integer')]
    private int $population = 0;

    #[ORM\Column(name: 'independant', type: 'boolean')]
    private bool $independant = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $flag = null;

    #[ORM\Column(name: 'currency_name', type: 'string', length: 255, nullable: true)]
    private ?string $currencyName = null;

    #[ORM\Column(name: 'currency_symbol', type: 'string', length: 50, nullable: true)]
    private ?string $currencySymbol = null;
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getSubRegion(): ?string
    {
        return $this->subRegion;
    }

    public function setSubRegion(?string $subRegion): self
    {
        $this->subRegion = $subRegion;
        return $this;
    }

    public function getDemonym(): ?string
    {
        return $this->demonym;
    }

    public function setDemonym(?string $demonym): self
    {
        $this->demonym = $demonym;
        return $this;
    }

    public function getPopulation(): int
    {
        return $this->population;
    }

    public function setPopulation(int $population): self
    {
        $this->population = $population;
        return $this;
    }

    public function isIndependant(): bool
    {
        return $this->independant;
    }

    public function setIndependant(bool $independant): self
    {
        $this->independant = $independant;
        return $this;
    }

    public function getFlag(): ?string
    {
        return $this->flag;
    }

    public function setFlag(?string $flag): self
    {
        $this->flag = $flag;
        return $this;
    }

    public function getCurrencyName(): ?string
    {
        return $this->currencyName;
    }

    public function setCurrencyName(?string $currencyName): self
    {
        $this->currencyName = $currencyName;
        return $this;
    }

    public function getCurrencySymbol(): ?string
    {
        return $this->currencySymbol;
    }

    public function setCurrencySymbol(?string $currencySymbol): self
    {
        $this->currencySymbol = $currencySymbol;
        return $this;
    }

    /**
     * Serializes the entity to an array matching the API response format.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'region' => $this->region,
            'subRegion' => $this->subRegion,
            'demonym' => $this->demonym,
            'population' => $this->population,
            'independant' => $this->independant,
            'flag' => $this->flag,
            'currency' => [
                'name' => $this->currencyName,
                'symbol' => $this->currencySymbol,
            ],
        ];
    }

}