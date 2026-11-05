<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu;

use Closure;
use EastpointLabs\LaravelAu\Abr\AbrClient;
use EastpointLabs\LaravelAu\Abr\Contracts\LooksUpAbns;
use EastpointLabs\LaravelAu\Rules\Abn;
use EastpointLabs\LaravelAu\Rules\AbnOrAcn;
use EastpointLabs\LaravelAu\Rules\Acn;
use EastpointLabs\LaravelAu\Rules\AuPhone;
use EastpointLabs\LaravelAu\Rules\AuPostcode;
use EastpointLabs\LaravelAu\Rules\Bsb;
use EastpointLabs\LaravelAu\Rules\StringRule;
use EastpointLabs\LaravelAu\Support\PhoneType;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\Factory as HttpFactory;
use InvalidArgumentException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Wires the package into a Laravel application.
 */
class LaravelAuServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-au')
            ->hasConfigFile('au')
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AbrClient::class, function (Application $app): AbrClient {
            $config = $app->make('config');
            $guid = $config->get('au.abr.guid');
            $store = $config->get('au.abr.cache.store');

            return new AbrClient(
                http: $app->make(HttpFactory::class),
                cache: $app->make(CacheFactory::class)->store(is_string($store) ? $store : null),
                guid: is_string($guid) ? $guid : null,
                baseUrl: $config->string('au.abr.base_url', 'https://abr.business.gov.au/json'),
                timeout: $config->integer('au.abr.timeout', 10),
                cacheEnabled: $config->boolean('au.abr.cache.enabled', true),
                cacheTtl: $config->integer('au.abr.cache.ttl', 86400),
            );
        });

        // Bound separately rather than aliased, so a test fake can replace the contract alone.
        $this->app->bind(LooksUpAbns::class, fn (Application $app): LooksUpAbns => $app->make(AbrClient::class));
    }

    public function packageBooted(): void
    {
        $this->callAfterResolving('validator', function (ValidationFactory $validator): void {
            $translator = $this->app->make(Translator::class);

            foreach ($this->stringRules() as $name => $makeRule) {
                $rule = new StringRule($translator, $makeRule);

                $validator->extend($name, $rule->passes(...));
                $validator->replacer($name, $rule->message(...));
            }
        });
    }

    /**
     * Each string rule mapped to a factory that builds its rule object from the parameters.
     *
     * @return array<string, Closure(list<string>): ValidationRule>
     */
    private function stringRules(): array
    {
        return [
            'abn' => fn (): ValidationRule => new Abn,
            'acn' => fn (): ValidationRule => new Acn,
            'abn_or_acn' => fn (): ValidationRule => new AbnOrAcn,
            'bsb' => fn (): ValidationRule => new Bsb,
            'au_phone' => fn (array $types): ValidationRule => new AuPhone(...array_map(self::phoneType(...), $types)),
            'au_postcode' => fn (array $parameters): ValidationRule => new AuPostcode($parameters[0] ?? null),
        ];
    }

    /**
     * Read an "au_phone" parameter such as "mobile", in any case.
     *
     * @throws InvalidArgumentException
     */
    private static function phoneType(string $type): PhoneType
    {
        return PhoneType::tryFrom(strtolower(trim($type)))
            ?? throw new InvalidArgumentException('The au_phone rule accepts only mobile, landline and service.');
    }
}
