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
                'password'     => Hash::make('admin99999'),
                'role_id'     => $role->id,
                'is_active'     => 1,
            ];
            $user = User::create($data);
            $user->assign($role->name);
        }
        
        $abilities = Bouncer::ability()->all()->pluck('id');
        $bouncerRole = $user->getRoles()->first();
        Bouncer::allow($bouncerRole)->to($abilities);

        $user = User::where('username','system')->first();
        $role = Bouncer::role()->where('name','superadmin')->first();
        if(!isset($user)){
            $data = [
                'name'     => 'System Admin',
                'username'     => 'system',
                'email'     => 'system@gmail.com',
                'password'     => Hash::make('admin99999'),
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
}
