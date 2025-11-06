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
    	Bouncer::role()->firstOrCreate([ 'name' => 'branchadmin', 'title' => 'Branch Admin', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'companyadmin', 'title' => 'Company Admin', ]);
    	Bouncer::role()->firstOrCreate([ 'name' => 'company_staff', 'title' => 'Company Staff', ]);
    }
}
