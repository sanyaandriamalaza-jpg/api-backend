<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $userData = $this->userService->getUserByEmail($request->email);
        
        if (!$userData || !$this->userService->verifyPassword($request->password, $userData['password_hash'])) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $user = $userData['user'];
        
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $userData['id'],
                    'email' => $userData['email'],
                    'name' => $userData['name'],
                    'firstName' => $userData['firstName'],
                    'profilePictureUrl' => $userData['profilePictureUrl'],
                    'profileType' => $userData['profileType'],
                    'companyId' => $userData['companyId'],
                    'companySlug' => $userData['companySlug'],
                ]
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $userData = $this->userService->getUserByEmail($user->email);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'name' => $userData['name'],
                'firstName' => $userData['firstName'],
                'profilePictureUrl' => $userData['profilePictureUrl'],
                'profileType' => $userData['profileType'],
                'companyId' => $userData['companyId'],
                'companySlug' => $userData['companySlug'],
            ]
        ]);
    }

    public function getUserPasswordHash(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $userData = $this->userService->getUserByEmail($request->email);

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun utilisateur trouvé avec cette adresse email.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe haché récupéré avec succès.',
            'data' => [
                'id' => $userData['id'],
                'email' => $userData['email'],
                'password_hash' => $userData['password_hash'],
                'firstName' => $userData['firstName'],
                'name' => $userData['name'],
                'profilePictureUrl' => $userData['profilePictureUrl'],
                'profileType' => $userData['profileType'],
                'companyId' => $userData['companyId'],
                'companySlug' => $userData['companySlug'],
            ]
        ]);
    }
}