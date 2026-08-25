<?php

declare(strict_types=1);

/**
 * ⚠️⚠️ THE SET-EQUALITY PIN: every role that can TAKE a booking can also OVERRIDE a restriction.
 *
 * ⚠️ WITHOUT THIS GUARD, granting `reservations.create` to a new role later would silently re-create the
 * dead end — a role that can START a booking and cannot FINISH one — AND silently falsify the
 * documentation, which now states the RULE ("anyone who can take a booking can override") rather than a
 * list of roles. ⚠️ Nobody would notice until an agent hit it with a guest on the phone.
 *
 * ⚠️ THE RULE IS ONLY SAFE TO WRITE DOWN BECAUSE IT IS GUARDED HERE. `guide-rates` needed correcting five
 * times in five passes, largely because it named roles and the matrix kept moving; a rule survives the
 * matrix moving, but only while something holds it true.
 */
function rolesHolding(string $permission): array
{
    $roles = [];
    foreach ((array) config('authorization.roles') as $role => $permissions) {
        if (in_array($permission, (array) $permissions, true)) {
            $roles[] = $role;
        }
    }
    sort($roles);

    return $roles;
}

it('⚠️⚠️ every role that can CREATE a booking can also OVERRIDE a restriction', function () {
    $create = rolesHolding('reservations.create');
    $override = rolesHolding('reservations.restriction_override');

    expect($create)->not->toBeEmpty();

    // ⚠️ Reported as the difference, not a bare boolean: a failure must name the offending role.
    expect(array_values(array_diff($create, $override)))->toBe([], 'a role can start a booking it cannot finish');
});

it('⚠️ and no role can override without being able to book at all', function () {
    $create = rolesHolding('reservations.create');
    $override = rolesHolding('reservations.restriction_override');

    expect(array_values(array_diff($override, $create)))->toBe([], 'a role can override without booking rights');
});
