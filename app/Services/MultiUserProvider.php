<?php

namespace App\Services;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Services\UserService;

class MultiUserProvider implements UserProvider
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function retrieveById($identifier)
    {
        // Cette méthode n'est pas utilisée avec Sanctum
        return null;
    }

    public function retrieveByToken($identifier, $token)
    {
        // Cette méthode n'est pas utilisée avec Sanctum
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Cette méthode n'est pas utilisée avec Sanctum
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials['email'])) {
            return null;
        }

        $userData = $this->userService->getUserByEmail($credentials['email']);
        return $userData ? $userData['user'] : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $this->userService->verifyPassword(
            $credentials['password'], 
            $user->getAuthPassword()
        );
    }
}