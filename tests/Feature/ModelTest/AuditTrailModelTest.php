<?php
/**
 * AuditTrailModelTest.php
 *
 * Purpose:
 *   Verify the AuditTrail model’s configuration WITHOUT touching the database.
 *   These tests assert:
 *     - Table/primary key/key-type configuration matches the ERD
 *     - Eloquent timestamps are enabled and mapped to ERD columns
 *     - Fillable attributes are exactly those intended for mass-assignment
 *     - Attribute casts (including Laravel’s normalized "integer") are correct
 *     - The user() relationship is wired to user_id -> users.user_id
 *
 *   Model metadata (table name, keys, casts, fillables, relations) can be tested
 *   by introspecting Eloquent objects. This keeps tests fast and focused.
 */

use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table + PK + key type should mirror the ERD:
 *   - Table: "AuditTrail" (legacy/pascal-cased)
 *   - PK:    "audit_id" (auto-increment int)
 */
it('uses the ERD table name, PK, and key settings', function () {
    $m = new AuditTrail();

    expect($m->getTable())->toBe('AuditTrail');                 // table name per ERD
    expect($m->getKeyName())->toBe(AuditTrail::COL_AUDIT_ID);   // PK column
    expect($m->getKeyType())->toBe('int');                      // PK type
    expect($m->getIncrementing())->toBeTrue();                  // auto-increment
});

/**
 * Eloquent timestamps:
 *   - Enabled (timestamps = true)
 *   - Mapped to ERD’s custom columns:
 *       CREATED_AT = a_created_at
 *       UPDATED_AT = a_updated_at
 *   - Casts for both should be "datetime"
 */
it('maps Eloquent timestamps to ERD columns', function () {
    $m = new AuditTrail();

    expect($m->timestamps)->toBeTrue();
    expect(AuditTrail::CREATED_AT)->toBe(AuditTrail::COL_CREATED_AT);
    expect(AuditTrail::UPDATED_AT)->toBe(AuditTrail::COL_UPDATED_AT);

    $casts = $m->getCasts();
    expect($casts[AuditTrail::COL_CREATED_AT] ?? null)->toBe('datetime');
    expect($casts[AuditTrail::COL_UPDATED_AT] ?? null)->toBe('datetime');
});

/**
 * Fillable (mass-assignable) attributes:
 *   Only the fields you expect to set explicitly when creating records.
 *   Timestamps are handled by Eloquent and are not required to be fillable.
 */
it('exposes expected fillable attributes', function () {
    $m = new AuditTrail();

    expect($m->getFillable())->toEqualCanonicalizing([
        AuditTrail::COL_USER_ID,
        AuditTrail::COL_ACTION,
        AuditTrail::COL_TARGET_TYPE,
        AuditTrail::COL_TARGET_ID,
    ]);
});

/**
 * Casts:
 *   - Laravel normalizes integer casts to the string "integer" in getCasts()
 *   - We assert both foreign key columns are integers
 */
it('casts user_id and a_target_id to integers', function () {
    $m = new AuditTrail();

    $casts = $m->getCasts();
    expect($casts[AuditTrail::COL_USER_ID]   ?? null)->toBe('integer');
    expect($casts[AuditTrail::COL_TARGET_ID] ?? null)->toBe('integer');
});

/**
 * Relationship: user()
 *   - BelongsTo(User::class)
 *   - Foreign key on AuditTrail: user_id
 *   - Owner key on User:        user_id   (ERD shows non-default PK name)
 */
it('defines user() belongsTo with user_id -> users.user_id', function () {
    $m   = new AuditTrail();
    $rel = $m->user();

    expect($rel)->toBeInstanceOf(BelongsTo::class);
    expect($rel->getForeignKeyName())->toBe(AuditTrail::COL_USER_ID); // FK on AuditTrail
    expect($rel->getOwnerKeyName())->toBe('user_id');                  // PK on User
    expect($rel->getRelated())->toBeInstanceOf(User::class);           // related model type
});
