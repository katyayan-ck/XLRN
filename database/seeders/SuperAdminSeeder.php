<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Core\Person;
use App\Models\Core\UserType;
use App\Models\Core\GraphNode;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // User Type
        $userType = UserType::firstOrCreate(
            ['code' => 'super_admin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Full system access',
                'is_active' => true
            ]
        );

        // Person
        $person = Person::firstOrCreate(
            ['email_primary' => 'super.admin@bmpl.com'],
            [
                'code' => Person::generateCode(),
                'salutation' => 'Mr',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'display_name' => 'Super Admin',
                'gender' => 'male',
                'mobile_primary' => '9212677774',
                'email_primary' => 'super.admin@bmpl.com',
            ]
        );

        if ($person->addresses()->count() === 0) {
            $person->addresses()->create([
                'type' => 'residential',
                'address_line_1' => 'Super Space',
                'city' => 'Delhi',
                'state' => 'Delhi',
                'pincode' => '110053',
                'country' => 'India',
                'is_primary' => true,
            ]);
        }

        // Role (Spatie)
        Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web'
        ]);

        // User
        $user = User::firstOrCreate(
            ['email' => 'super.admin@bmpl.com'],
            [
                'user_type_id' => $userType->id,
                'person_id' => $person->id,
                'name' => 'Super Admin',
                'email' => 'super.admin@bmpl.com',
                'password' => bcrypt('admin1234'),
                'is_active' => true,
                'code' => 'SUDO',
            ]
        );

        $user->assignRole('super-admin');

        // Graph Node with wildcard
        GraphNode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => 'super-admin',
                'attributes' => json_encode(['*' => true])
            ]
        );
    }
}
