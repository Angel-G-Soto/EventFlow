<?php
/**
 * RoleTest.php
 *
 * Purpose:
 *  Verify Role model configuration and behaviors WITHOUT touching the DB.
 *  Focus:
 *   - Table + fillables
 *   - BelongsToMany(users) relationship wiring
 *   - Query scope: scopeFindByCode()
 *   - Helpers: assignTo(), removeFrom()
 *   - Accessor: user_count
 *
 * Notes:
 *  - Relationship methods are introspected (pivot table & key names).
 *  - Mutating helpers are verified via mocked BelongsToMany relations.
 */

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
//use Mockery;

/* -----------------------------------------------------------
 |  Basic configuration
 |------------------------------------------------------------
*/

/**
 * Table + fillable attributes
 */
it('uses the expected table and fillable attributes', function () {
    $m = new Role();

    expect($m->getTable())->toBe('roles');

    expect($m->getFillable())->toEqualCanonicalizing([
        'name',
        'code',
    ]);
});

/* -----------------------------------------------------------
 |  Relationship: users()
 |------------------------------------------------------------
*/

/**
 * users(): BelongsToMany via pivot 'user_role'
 *  - Pivot table: user_role
 *  - Foreign pivot key (for Role): role_id
 *  - Related pivot key (for User): user_id
 *
 * These values are derived by Eloquent conventions unless you override them.
 */
it('defines users() as BelongsToMany with correct pivot and keys', function () {
    $m = new Role();
    $rel = $m->users();

    expect($rel)->toBeInstanceOf(BelongsToMany::class);

    // Pivot table name:
    expect($rel->getTable())->toBe('user_role');

    // Pivot key names (unqualified)
    expect($rel->getForeignPivotKeyName())->toBe('role_id');
    expect($rel->getRelatedPivotKeyName())->toBe('user_id');

    // Qualified pivot key names (table.key)
    expect($rel->getQualifiedForeignPivotKeyName())->toBe('user_role.role_id');
    expect($rel->getQualifiedRelatedPivotKeyName())->toBe('user_role.user_id');
});

/* -----------------------------------------------------------
 |  Scope: scopeFindByCode()
 |------------------------------------------------------------
*/

/**
 * scopeFindByCode(): ensures it adds the proper where clause.
 * We assert the SQL fragment and the bound value, without executing.
 */
it('builds the correct where clause in scopeFindByCode()', function () {
    $query = Role::query()->findByCode('system-admin');

    // DB-agnostic assertion: look for the 'code = ?' pattern
    expect($query->toSql())->toContain('`code` = ?');

    // Ensure the binding is the incoming code
    expect($query->getBindings())->toContain('system-admin');
});

/* -----------------------------------------------------------
 |  Helpers: assignTo() and removeFrom()
 |------------------------------------------------------------
*/

/**
 * assignTo(): should call syncWithoutDetaching(user->user_id) on the relation.
 * We mock the relation and override users() to return it.
 */
it('assignTo() syncs the user on the pivot without detaching others', function () {
    // Mock the BelongsToMany relation
    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('syncWithoutDetaching')
        ->once()
        ->with(7);

    // Partial mock Role to stub users()
    $role = Mockery::mock(Role::class)->makePartial();
    $role->shouldReceive('users')->andReturn($relation);

    // Minimal User instance with the expected PK attribute
    $user = new User();
    $user->user_id = 7;

    // Act
    $role->assignTo($user);
});

/**
 * removeFrom(): should call detach(user->user_id) on the relation.
 */
it('removeFrom() detaches the user from the pivot', function () {
    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('detach')
        ->once()
        ->with(42);

    $role = Mockery::mock(Role::class)->makePartial();
    $role->shouldReceive('users')->andReturn($relation);

    $user = new User();
    $user->user_id = 42;

    $role->removeFrom($user);
});

/* -----------------------------------------------------------
 |  Accessor: user_count
 |------------------------------------------------------------
*/

/**
 * getUserCountAttribute(): proxies to users()->count()
 * We mock the relation so no DB is hit.
 */
it('user_count accessor returns users()->count()', function () {
    $relation = Mockery::mock(BelongsToMany::class);
    $relation->shouldReceive('count')
        ->once()
        ->andReturn(5);

    $role = Mockery::mock(Role::class)->makePartial();
    $role->shouldReceive('users')->andReturn($relation);

    expect($role->user_count)->toBe(5);
});

/* -----------------------------------------------------------
 |  Cleanup
 |------------------------------------------------------------
*/

afterEach(function () {
    Mockery::close();
});
