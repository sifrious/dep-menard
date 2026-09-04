<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding;

use InvalidArgumentException;

final readonly class EmbeddingProfile
{
    /**
     * @param  array<string, bool|float|int|string|null>  $normalization
     */
    public function __construct(
        public string $provider,
        public string $model,
        public string $revisionDigest,
        public int $dimensions,
        public array $normalization,
        public string $tokenizer,
        public string $preprocessing,
    ) {
        foreach ([
            'provider' => $provider,
            'model' => $model,
            'revision digest' => $revisionDigest,
            'tokenizer' => $tokenizer,
            'preprocessing' => $preprocessing,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException("Embedding profile {$field} cannot be empty.");
            }
        }

        if ($dimensions < 1) {
            throw new InvalidArgumentException('Embedding profile dimensions must be positive.');
        }

    }

    public function identity(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @return array{
     *     provider: string,
     *     model: string,
     *     revision_digest: string,
     *     dimensions: int,
     *     normalization: array<string, bool|float|int|string|null>,
     *     tokenizer: string,
     *     preprocessing: string
     * }
     */
    public function toArray(): array
    {
        $normalization = $this->normalization;
        ksort($normalization);

        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'revision_digest' => $this->revisionDigest,
            'dimensions' => $this->dimensions,
            'normalization' => $normalization,
            'tokenizer' => $this->tokenizer,
            'preprocessing' => $this->preprocessing,
        ];
    }

    /**
     * @param  array{
     *     provider: string,
     *     model: string,
     *     revision_digest: string,
     *     dimensions: int,
     *     normalization: array<string, bool|float|int|string|null>,
     *     tokenizer: string,
     *     preprocessing: string
     * }  $profile
     */
    public static function fromArray(array $profile): self
    {
        return new self(
            provider: $profile['provider'],
            model: $profile['model'],
            revisionDigest: $profile['revision_digest'],
            dimensions: $profile['dimensions'],
            normalization: $profile['normalization'],
            tokenizer: $profile['tokenizer'],
            preprocessing: $profile['preprocessing'],
        );
    }
}
