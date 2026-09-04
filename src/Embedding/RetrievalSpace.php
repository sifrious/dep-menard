<?php

declare(strict_types=1);

namespace Sifrious\Menard\Embedding;

use InvalidArgumentException;

final readonly class RetrievalSpace
{
    public function __construct(
        public string $generation,
        public EmbeddingProfile $profile,
    ) {
        if (trim($generation) === '') {
            throw new InvalidArgumentException('Retrieval-space generation cannot be empty.');
        }
    }

    public function identity(): string
    {
        return hash('sha256', json_encode([
            'generation' => $this->generation,
            'profile_identity' => $this->profile->identity(),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{generation: string, identity: string, profile: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'generation' => $this->generation,
            'identity' => $this->identity(),
            'profile' => $this->profile->toArray(),
        ];
    }

    /**
     * @param  array{generation: string, identity?: string, profile: array<string, mixed>}  $space
     */
    public static function fromArray(array $space): self
    {
        /** @var array{
         *     provider: string,
         *     model: string,
         *     revision_digest: string,
         *     dimensions: int,
         *     normalization: array<string, bool|float|int|string|null>,
         *     tokenizer: string,
         *     preprocessing: string
         * } $profile
         */
        $profile = $space['profile'];
        $restored = new self($space['generation'], EmbeddingProfile::fromArray($profile));

        if (isset($space['identity']) && ! hash_equals($space['identity'], $restored->identity())) {
            throw new InvalidArgumentException('Retrieval-space identity does not match its generation and profile.');
        }

        return $restored;
    }
}
