<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Bouncer;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('username','superadmin')->first();
        $role = Bouncer::role()->where('name','superadmin')->first();
        if(!isset($user)){
            $data = [
                'name'     => 'Superadmin',
                'username'     => 'superadmin',
                'email'     => 'superadmin@gmail.com',
                'password'     => Hash::make($this->initialPassword()),
                'role_id'     => $role->id,
                'is_active'     => 1,
            ];
            $user = User::create($data);
            $user->assign($role->name);
        }
        
        $abilities = Bouncer::ability()->all()->pluck('id');
        $bouncerRole = $user->getRoles()->first();
        Bouncer::allow($bouncerRole)->to($abilities);

        $user = User::where('username','admin')->first();
        $role = Bouncer::role()->where('name','admin')->first();
        if(!isset($user)){
            $data = [
                'name'     => 'Admin',
                'username'     => 'admin',
                'email'     => 'admin@gmail.com',
                'password'     => Hash::make($this->initialPassword()),
                'role_id'     => $role->id,
                'is_active'     => 1,
            ];
            $user = User::create($data);
            $user->assign($role->name);
        }
        
        $abilities = Bouncer::ability()->all()->pluck('id');
        $bouncerRole = $user->getRoles()->first();
        Bouncer::allow($bouncerRole)->to($abilities);
    }

    /**
     * Password for newly created admin accounts: SEED_ADMIN_PASSWORD from .env,
     * otherwise a random one shown once in the console. Never hard-coded.
     */
    private function initialPassword(): string
    {
        static $password = null;
        if ($password === null) {
            $password = env('SEED_ADMIN_PASSWORD') ?: \Illuminate\Support\Str::password(14, symbols: false);
            if (!env('SEED_ADMIN_PASSWORD')) {
                $this->command?->warn("New admin accounts get password: $password  (change it after logging in)");
            }
        }
        return $password;
    }
}
