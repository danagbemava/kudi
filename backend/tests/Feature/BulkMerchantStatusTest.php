<?php

use App\Models\Merchant;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

describe('PATCH /api/merchants/status', function () {
    it('updates every named merchant in one call', function () {
        $merchants = Merchant::factory()->count(3)->create();

        $this->patchJson('/api/merchants/status', [
            'ids' => $merchants->pluck('id')->all(),
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('data.updated', 3);

        expect(Merchant::where('status', 'inactive')->count())->toBe(3);
    });

    it('leaves merchants that were not named alone', function () {
        $target = Merchant::factory()->create();
        $bystander = Merchant::factory()->create();

        $this->patchJson('/api/merchants/status', [
            'ids' => [$target->id],
            'status' => 'inactive',
        ])->assertOk();

        expect($bystander->refresh()->status->value)->toBe('active');
    });

    it('reports how many actually changed, not how many were named', function () {
        // Re-applying the status a merchant already has is not a change; saying
        // "3 updated" when nothing moved would be a lie in the toast.
        $already = Merchant::factory()->inactive()->create();
        $changing = Merchant::factory()->create();

        $this->patchJson('/api/merchants/status', [
            'ids' => [$already->id, $changing->id],
            'status' => 'inactive',
        ])->assertOk()->assertJsonPath('data.updated', 1);
    });

    it('rejects an unknown merchant, an empty list or a bad status', function () {
        $merchant = Merchant::factory()->create();

        $this->patchJson('/api/merchants/status', ['ids' => [999], 'status' => 'inactive'])
            ->assertUnprocessable()->assertJsonValidationErrors('ids.0');

        $this->patchJson('/api/merchants/status', ['ids' => [], 'status' => 'inactive'])
            ->assertUnprocessable()->assertJsonValidationErrors('ids');

        $this->patchJson('/api/merchants/status', ['ids' => [$merchant->id], 'status' => 'suspended'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    });

    it('is closed to guests', function () {
        auth()->logout();

        $this->patchJson('/api/merchants/status', ['ids' => [1], 'status' => 'inactive'])
            ->assertUnauthorized();
    });
});
