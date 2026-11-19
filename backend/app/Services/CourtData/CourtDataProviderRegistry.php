<?php

namespace App\Services\CourtData;

use App\Contracts\CourtDataProvider;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class CourtDataProviderRegistry
{
    public function __construct(
        private Container $container,
        private array $providers
    ) {
    }

    public function get(string $key): CourtDataProvider
    {
        $class = $this->providers[$key] ?? null;

        if (!$class) {
            throw new InvalidArgumentException(
                "Court data provider '{$key}' is not configured."
            );
        }

        $provider = $this->container->make($class);

        if (!$provider instanceof CourtDataProvider) {
            throw new InvalidArgumentException(
                "Provider '{$key}' does not implement CourtDataProvider."
            );
        }

        return $provider;
    }

    public function default(): CourtDataProvider
    {
        $key = config('court-data.default_provider', 'mock');

        return $this->get($key);
    }

    public function availableProviders(): array
    {
        return array_keys($this->providers);
    }
}
