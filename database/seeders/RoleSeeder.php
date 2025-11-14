<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Bouncer;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	Bouncer::role()->firstOrCreate([ 'name' => 'superadmin', 'title' => 'Super Admin', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'admin', 'title' => 'Admin', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'branch_manager', 'title' => 'Branch Manager', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'company_manager', 'title' => 'Company Manager', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'company_staff', 'title' => 'Company Staff', ]);
    }
}
