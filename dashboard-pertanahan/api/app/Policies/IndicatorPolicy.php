<?php

namespace App\Policies;

use App\Models\Indicator;
use App\Models\Region;
use App\Models\User;

class IndicatorPolicy
{
    private const KNOWN_ROLES = [
        'super_admin', 'admin_data_bpn', 'operator_pemda', 'operator_kantah', 'viewer_eksekutif',
    ];

    public function view(User $user, Indicator $indicator, Region $region): bool
    {
        if (! $this->baseAllowed($user, $indicator, $region)) return false;
        return $user->roles()->whereIn('code', self::KNOWN_ROLES)->where('roles.is_active', true)->exists();
    }

    public function update(User $user, Indicator $indicator, Region $region): bool
    {
        if (! $this->baseAllowed($user, $indicator, $region)
            || ! $indicator->allows_manual_input || $indicator->is_derived || $indicator->is_feature) return false;
        if ($user->hasActiveRole('super_admin')) return true;
        return match ($indicator->owner->code) {
            'pemda' => $user->hasActiveRole('operator_pemda'),
            'kantah' => $user->hasActiveRole('operator_kantah'),
            default => false,
        };
    }

    public function import(User $user, Indicator $indicator, Region $region): bool
    {
        if (! $this->baseAllowed($user, $indicator, $region) || $indicator->is_feature) return false;
        if ($user->hasActiveRole('super_admin')) return true;
        return $indicator->owner->code === 'atr_bpn' && $user->hasActiveRole('admin_data_bpn');
    }

    private function baseAllowed(User $user, Indicator $indicator, Region $region): bool
    {
        if (! $user->is_active || ! $indicator->is_active || ! $region->is_active) return false;
        $owner = $indicator->owner;
        if (! $owner || ! $owner->is_active
            || ! in_array($owner->code, ['atr_bpn','pemda','kantah','bhumi_external','deferred'], true)) return false;
        return $user->hasActiveRole('super_admin') || $user->hasRegionScope($region);
    }
}
