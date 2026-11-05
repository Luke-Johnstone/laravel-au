<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Abr\Contracts;

use EastpointLabs\LaravelAu\Abr\Data\AbnDetails;
use EastpointLabs\LaravelAu\Abr\Data\NameMatch;
use EastpointLabs\LaravelAu\Exceptions\AbrException;
use EastpointLabs\LaravelAu\Exceptions\InvalidValueException;
use EastpointLabs\LaravelAu\Support\Abn;
use EastpointLabs\LaravelAu\Support\Acn;
use InvalidArgumentException;

/**
 * Looks up businesses on the Australian Business Register.
 *
 * Type-hint this interface in application code rather than the client, so tests can swap in
 * a fake.
 */
interface LooksUpAbns
{
    /**
     * The register's details for an ABN, or null when it isn't registered.
     *
     * @throws InvalidValueException when the ABN isn't valid
     * @throws AbrException when ABN Lookup can't be reached or reports an error
     */
    public function abn(Abn|string $abn): ?AbnDetails;

    /**
     * The details of the ABN held by the company with this ACN, or null when there's none.
     *
     * @throws InvalidValueException when the ACN isn't valid
     * @throws AbrException when ABN Lookup can't be reached or reports an error
     */
    public function acn(Acn|string $acn): ?AbnDetails;

    /**
     * Search entity, business and trading names, in the order ABN Lookup ranks them.
     *
     * @return list<NameMatch>
     *
     * @throws InvalidArgumentException when the name is empty
     * @throws AbrException when ABN Lookup can't be reached or reports an error
     */
    public function searchByName(string $name, int $maxResults = 10): array;
}
