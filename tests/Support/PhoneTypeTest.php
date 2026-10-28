<?php

declare(strict_types=1);

use EastpointLabs\LaravelAu\Support\PhoneType;

mutates(PhoneType::class);

describe('cases', function (): void {
    it('is a string-backed enum', function (): void {
        expect(PhoneType::Mobile)->toBeInstanceOf(BackedEnum::class)
            ->and((new ReflectionEnum(PhoneType::class))->getBackingType()?->getName())->toBe('string');
    });

    it('has exactly the mobile, landline and service cases, in order', function (): void {
        $values = array_map(fn (PhoneType $type): string => $type->value, PhoneType::cases());

        expect($values)->toBe(['mobile', 'landline', 'service']);
    });
});
