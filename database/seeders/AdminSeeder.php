<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.principal_admin.email');
        $name = config('admin.principal_admin.name', 'Admin');

        if (empty($email)) {
            $this->command->warn('PRINCIPAL_ADMIN_EMAIL non défini — seeder admin ignoré.');
            Log::warning('AdminSeeder: PRINCIPAL_ADMIN_EMAIL is empty, skipping.');
            return;
        }

        $password = env('ADMIN_PASSWORD');
        if (empty($password)) {
            $password = 'ProxiPro' . date('Y') . '!' . bin2hex(random_bytes(4));
            $this->command->warn("ADMIN_PASSWORD non défini — mot de passe généré automatiquement.");
            Log::warning('AdminSeeder: ADMIN_PASSWORD not set, generated random password.');
        }

        // Ne crée le compte que s'il n'existe pas déjà
        if (!User::where('email', $email)->exists()) {
            $admin = new User();
            $admin->name = $name;
            $admin->email = $email;
            $admin->password = Hash::make($password);
            $admin->role = 'admin';
            $admin->is_verified = true;
            $admin->is_active = true;
            $admin->email_verified_at = now();
            $admin->user_type = 'particulier';
            $admin->save();

            $this->command->info("Compte admin créé : {$email}");
        } else {
            // S'assurer que le compte existant a bien le rôle admin
            User::where('email', $email)->update(['role' => 'admin']);
            $this->command->info("Compte admin déjà existant, rôle admin confirmé : {$email}");
        }
    }
}
