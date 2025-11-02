<?php

use App\Models\User;
use App\Models\AuditTrail;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('AuditService', function () {
    it('logs a description containing SQL keywords or code safely', function () {
        $service = new AuditService();
        $user = User::factory()->create();
        $sqlDesc = "SELECT * FROM users WHERE id = 1; DROP TABLE audit_trail; --";
        $displayName = $user->first_name . ' ' . $user->last_name;
        $audit = $service->logAction($user->id, $displayName, 'SQL_TEST', $sqlDesc);
        expect($audit->target_id)->toBe($sqlDesc);
        // Optionally, check that the audit_trail table still exists (if you want to be extra safe)
        expect(Schema::hasTable('audit_trail'))->toBeTrue();
    });
    /*it('logs an action with a very long description', function () {
    $service = new AuditService();
    $user = User::factory()->create();
    $longDesc = str_repeat('LongDescription', 50); // 750+ chars
    $audit = $service->logAction($user->user_id, $user->u_name, 'LONG_DESC', $longDesc);
    expect($audit->at_description)->toBe($longDesc);
  });*/

    it('logs an action with special characters in all fields', function () {
        $service = new AuditService();
        $user = User::factory()->create(['first_name' => '!@#$_User', 'last_name' => '']);
        $action = '!@#$%^&*()_ACTION';
        $desc = 'Description with emoji 🚀 and symbols ©®™';
        $displayName = $user->first_name;
        $audit = $service->logAction($user->id, $displayName, $action, $desc);
        expect($audit->target_type)->toBe('!@#$_User')
            ->and($audit->action)->toBe($action)
            ->and($audit->target_id)->toBe($desc);
    });
    it('logAction returns an AuditTrail model instance', function () {
        $service = new AuditService();
        $user = User::factory()->create();
        $displayName = $user->first_name . ' ' . $user->last_name;
        $audit = $service->logAction($user->id, $displayName, 'MODEL_INSTANCE', 'Should return model instance');
        expect($audit)->toBeInstanceOf(AuditTrail::class);
    });

    it('logs a standard action', function () {
        $service   = new AuditService();
        $user      = User::factory()->create();
        $userId    = $user->id;
        $userName  = $user->first_name . ' ' . $user->last_name;
        $action    = 'EVENT_CREATED';
        $desc      = 'User created an event.';

        $audit = $service->logAction($userId, $userName, $action, $desc);

        expect($audit)->toBeInstanceOf(AuditTrail::class)
            ->and($audit->user_id)->toBe($userId)
            ->and($audit->action)->toBe($action)
            ->and($audit->target_id)->toBe($desc)
            ->and($audit->target_type)->toBe($userName);
    });

    it('logs an admin action', function () {
        $service   = new AuditService();
        $admin     = User::factory()->create();
        $adminId   = $admin->id;
        $userName  = $admin->first_name . ' ' . $admin->last_name;
        $action    = 'ADMIN_OVERRIDE';
        $desc      = 'Admin performed override.';

        $audit = $service->logAdminAction($adminId, $userName, $action, $desc);

        expect($audit)->toBeInstanceOf(AuditTrail::class)
            /*************  ✨ Windsurf Command ⭐  *************/
            /**
             * Tests that the logAction method returns an instance of the AuditTrail model.
             *
             * This test verifies that the logAction method correctly logs an action and returns
             * an instance of the AuditTrail model. It asserts that the returned instance has the
             * correct user ID and target type.
             */
            /*******  9e14882d-c8b6-4562-bca0-b8cd7dafe33b  *******/
            ->and($audit->user_id)->toBe($adminId)
            ->and($audit->action)->toBe($action)
            ->and($audit->target_id)->toBe($desc)
            ->and($audit->target_type)->toBe($userName);
    });

    it('logs an action with empty description', function () {
        $service  = new AuditService();
        $user     = User::factory()->create();

        $displayName = $user->first_name . ' ' . $user->last_name;
        $audit = $service->logAction($user->id, $displayName, 'EMPTY_DESC', '');

        expect($audit->target_id)->toBe('');
    });

    /*it('truncates long action codes to 255 chars', function () {
    $service  = new AuditService();
    $user     = User::factory()->create();

    $original = str_repeat('X', 255) . '!@#$%^&*()'; // > 255
    $audit = $service->logAction($user->user_id, $user->u_name, $original, 'Unusual code test');

    $expected = Str::limit($original, 255, ''); // how the service trims
    expect($audit->at_action)->toBe($expected)
      ->and(strlen($audit->at_action))->toBeLessThanOrEqual(255);
  });*/

    it('logs an action with edge-case user names', function () {
        $service = new AuditService();
        $u1 = User::factory()->create(['first_name' => '', 'last_name' => '']);
        $u2 = User::factory()->create(['first_name' => str_repeat('A', 100), 'last_name' => '']);
        $u3 = User::factory()->create(['first_name' => 'Üser!@#$', 'last_name' => '']);

        $a1 = $service->logAction($u1->id, $u1->first_name, 'EDGE_USER', 'Empty user name');
        $a2 = $service->logAction($u2->id, $u2->first_name, 'EDGE_USER', 'Long user name');
        $a3 = $service->logAction($u3->id, $u3->first_name, 'EDGE_USER', 'Special chars user name');

        expect($a1->target_type)->toBe('');
        expect($a2->target_type)->toBe(str_repeat('A', 100));
        expect($a3->target_type)->toBe('Üser!@#$');
    });

    it('logs duplicate actions and both are stored', function () {
        $service = new AuditService();
        $user    = User::factory()->create();

        $displayName = $user->first_name . ' ' . $user->last_name;
        $a1 = $service->logAction($user->id, $displayName, 'DUPLICATE', 'Duplicate test');
        $a2 = $service->logAction($user->id, $displayName, 'DUPLICATE', 'Duplicate test');

        expect($a1->id)->not->toBe($a2->id);
    });

    // This test was incompatible with your FK constraint and should be removed or rewritten.
    // If you insist on keeping a variant, create actual users and assert they insert.
    // it('logs action with invalid user IDs', ...)

    it('fails when required fields are missing (typed params)', function () {
        $service = new AuditService();

        expect(fn() => $service->logAction(null, 'User', 'CODE', 'desc'))->toThrow(TypeError::class);
        expect(fn() => $service->logAction(1, null, 'CODE', 'desc'))->toThrow(TypeError::class);
        expect(fn() => $service->logAction(1, 'User', null, 'desc'))->toThrow(TypeError::class);
        expect(fn() => $service->logAction(1, 'User', 'CODE', null))->toThrow(TypeError::class);
    });

    it('truncates action, user name, and description to 255 chars', function () {
        $service = new AuditService();
        $user    = User::factory()->create();

        $longAction = str_repeat('A', 400);
        $longName   = str_repeat('N', 400);
        $longDesc   = str_repeat('D', 400);

        $audit = $service->logAction($user->id, $longName, $longAction, $longDesc);

        expect(mb_strlen($audit->action))->toBe(255)
            ->and($audit->action)->toBe(mb_substr($longAction, 0, 255))
            ->and(mb_strlen($audit->target_type))->toBe(255)
            ->and($audit->target_type)->toBe(mb_substr($longName, 0, 255))
            ->and(mb_strlen($audit->target_id))->toBe(255)
            ->and($audit->target_id)->toBe(mb_substr($longDesc, 0, 255));
    });

    it('preserves unicode and emoji while truncating safely', function () {
        $service = new AuditService();
        $user    = User::factory()->create();

        $unicodeName = str_repeat('名', 300); // multibyte char
        $emojiDesc   = str_repeat('🚀', 300); // surrogate pairs
        $action      = str_repeat('Æ', 300);

        $audit = $service->logAction($user->id, $unicodeName, $action, $emojiDesc);

        // Should match exact mb_substr and have no replacement chars
        expect($audit->target_type)->toBe(mb_substr($unicodeName, 0, 255))
            ->and($audit->action)->toBe(mb_substr($action, 0, 255))
            ->and($audit->target_id)->toBe(mb_substr($emojiDesc, 0, 255));
        // Ensure no broken glyphs (very loose check: no Unicode replacement character)
        expect(str_contains($audit->target_type, "\xEF\xBF\xBD"))->toBeFalse()
            ->and(str_contains($audit->target_id, "\xEF\xBF\xBD"))->toBeFalse();
    });

    it('sets timestamps on audit entries', function () {
        $service = new AuditService();
        $user    = User::factory()->create();

        $now = Carbon::now();
        $audit = $service->logAction($user->id, $user->first_name . ' ' . $user->last_name, 'TS_CHECK', 'Check timestamps');

        expect($audit->created_at)->not->toBeNull()
            ->and($audit->updated_at)->not->toBeNull();

        // created_at should be recent (within ~5 seconds)
        expect($audit->created_at->lessThanOrEqualTo(Carbon::now()->addSeconds(5)))->toBeTrue();
    });

    it('getAuditedUsers returns distinct users ordered by display name', function () {
        $service = new AuditService();
        $u1 = User::factory()->create(['first_name' => 'Bob', 'last_name' => '']);
        $u2 = User::factory()->create(['first_name' => 'Alice', 'last_name' => '']);

        // Log actions (multiple for the same user)
        $service->logAction($u1->id, 'Bob', 'CODE', 'desc');
        $service->logAction($u2->id, 'Alice', 'CODE', 'desc');
        $service->logAction($u1->id, 'Bob', 'OTHER', 'desc');

        $list = $service->getAuditedUsers(); // [user_id => display name], ordered by name

        // Values should be alphabetically ordered: Alice, Bob
        expect(array_values($list))->toBe(['Alice', 'Bob']);
        // Keys include both users
        expect(array_keys($list))->toContain($u2->id, $u1->id);
    });

    it('getPaginatedLogs filters by action substring and user_id, ordered by newest first', function () {
        $service = new AuditService();
        $user    = User::factory()->create(['first_name' => 'Alpha', 'last_name' => '']);
        $user2   = User::factory()->create(['first_name' => 'Beta', 'last_name' => '']);

        // Seed logs with controlled timestamps
        $a1 = $service->logAction($user->id, 'Alpha', 'USER_LOGIN',  'First login');
        $a2 = $service->logAction($user->id, 'Alpha', 'USER_LOGOUT', 'Logout');
        $a3 = $service->logAction($user->id, 'Alpha', 'USER_LOGIN',  'Second login');
        $a4 = $service->logAction($user2->id, 'Beta',  'USER_LOGIN',  'Other user login');

        // Adjust created_at so we can assert ordering: a1 oldest, a3 newest among LOGINs
        $a1->update(['created_at' => Carbon::now()->subDays(3)]);
        $a2->update(['created_at' => Carbon::now()->subDays(2)]);
        $a3->update(['created_at' => Carbon::now()->subDay()]);
        $a4->update(['created_at' => Carbon::now()]);

        // Filter by action substring 'LOGIN' and specific user
        $page = $service->getPaginatedLogs(['action' => 'LOGIN', 'user_id' => $user->id], perPage: 10);

        // Expect only a1 and a3 (user1's LOGINs)
        expect($page->total())->toBe(2);
        $items = $page->items();
        // Contains the correct IDs (order-agnostic)
        expect(collect($items)->pluck('id')->sort()->values()->all())
            ->toBe(collect([$a1->id, $a3->id])->sort()->values()->all());
        // And the ordering is newest first by created_at
        expect($items[0]->created_at->greaterThanOrEqualTo($items[1]->created_at))->toBeTrue();
    });
});
