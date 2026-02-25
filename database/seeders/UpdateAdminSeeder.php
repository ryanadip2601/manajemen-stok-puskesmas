<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UpdateAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        
        if ($admin) {
            $admin->update([
                'email' => 'ryanadip2601@gmail.com',
                'password' => Hash::make('ryan2601'),
                'phone' => '085348440470',
            ]);
            
            $this->command->info('Admin updated successfully!');
            $this->command->info('Email: ryanadip2601@gmail.com');
            $this->command->info('Password: ryan2601');
        } else {
            $this->command->error('Admin user not found!');
        }
    }
}
