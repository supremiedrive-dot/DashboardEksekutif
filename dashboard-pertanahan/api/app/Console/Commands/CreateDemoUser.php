<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDemoUser extends Command
{
    protected $signature = 'dashboard:create-demo-user';
    protected $description = 'Create or update a presentation account';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        while (! filter_var($email, FILTER_VALIDATE_EMAIL)) $email = $this->ask('Email');
        $password = $this->secret('Password');
        while (! $password) $password = $this->secret('Password');
        $user = User::where('email', $email)->first();
        $selected = $this->choice('Role', ['super_admin','operator_pemda','operator_kantah','viewer_eksekutif']);
        if ($user && $user->roles()->pluck('code')->implode(', ') !== $selected
            && ! $this->confirm('Replace existing role?', false)) return self::FAILURE;
        $user ??= new User(['email' => $email]);
        $user->name = $name; $user->password = Hash::make($password); $user->is_active = true; $user->save();
        $role = Role::where('code', $selected)->where('is_active', true)->firstOrFail();
        $user->roles()->sync([$role->id]);
        if (in_array($selected, ['operator_pemda','operator_kantah','viewer_eksekutif'], true)) {
            $regions = Region::where('level','regency_city')->where('is_active',true)->orderBy('name')->get();
            $region = $this->choice('Region', $regions->pluck('name')->all());
            $user->regionScopes()->sync([$regions->firstWhere('name',$region)->id]);
        } else $user->regionScopes()->sync([]);
        $this->info("Presentation account is ready.\nRole: {$selected}");
        return self::SUCCESS;
    }
}
