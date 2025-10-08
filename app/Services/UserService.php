<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\BasicUser;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getUserByEmail(string $email)
    {
        // Chercher dans admin_user
        $admin = AdminUser::with(['company', 'subRole'])
            ->where('email', $email)
            ->first();
            
        if ($admin) {
            return [
                'user' => $admin,
                'type' => 'admin_user',
                'id' => $admin->id_admin_user,
                'email' => $admin->email,
                'password_hash' => $admin->password_hash,
                'firstName' => $admin->first_name,
                'name' => $admin->name,
                'profilePictureUrl' => $admin->profile_picture_url,
                'profileType' => 'adminUser',
                'companyId' => $admin->company?->id_company,
                'companySlug' => $admin->company?->slug,
            ];
        }

        // Chercher dans basic_user
        $basic = BasicUser::with('company')
            ->where('email', $email)
            ->first();
            
        if ($basic) {
            return [
                'user' => $basic,
                'type' => 'basic_user',
                'id' => $basic->id_basic_user,
                'email' => $basic->email,
                'password_hash' => $basic->password_hash,
                'firstName' => $basic->first_name,
                'name' => $basic->name,
                'profilePictureUrl' => $basic->profile_picture_url,
                'profileType' => 'basicUser',
                'companyId' => $basic->company?->id_company,
                'companySlug' => $basic->company?->slug,
            ];
        }

        return null;
    }

    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }
}