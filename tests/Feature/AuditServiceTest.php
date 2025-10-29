<?php

use App\Models\User;
use App\Models\AuditTrail;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

describe('AuditService', function () {
  it('logs a description containing SQL keywords or code safely', function () {
    $service = new AuditService();
    $user = User::factory()->create();
    $sqlDesc = "SELECT * FROM users WHERE id = 1; DROP TABLE audit_trail; --";
    $displayName = $user->first_name.' '.$user->last_name;
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
    $displayName = $user->first_name.' '.$user->last_name;
    $audit = $service->logAction($user->id, $displayName, 'MODEL_INSTANCE', 'Should return model instance');
    expect($audit)->toBeInstanceOf(AuditTrail::class);
  });

  it('logs a standard action', function () {
    $service   = new AuditService();
    $user      = User::factory()->create();
    $userId    = $user->id;
    $userName  = $user->first_name.' '.$user->last_name;
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
    $userName  = $admin->first_name.' '.$admin->last_name;
    $action    = 'ADMIN_OVERRIDE';
    $desc      = 'Admin performed override.';

    $audit = $service->logAdminAction($adminId, $userName, $action, $desc);

    expect($audit)->toBeInstanceOf(AuditTrail::class)
      ->and($audit->user_id)->toBe($adminId)
      ->and($audit->action)->toBe($action)
      ->and($audit->target_id)->toBe($desc)
      ->and($audit->target_type)->toBe($userName);
  });

  it('logs an action with empty description', function () {
    $service  = new AuditService();
    $user     = User::factory()->create();

    $displayName = $user->first_name.' '.$user->last_name;
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

    $displayName = $user->first_name.' '.$user->last_name;
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
});
