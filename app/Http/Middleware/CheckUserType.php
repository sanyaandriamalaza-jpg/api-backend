<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\UserService;

class CheckUserType
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(Request $request, Closure $next, string $requiredType)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }

        $userData = $this->userService->getUserByEmail($user->email);
        
        if (!$userData || $userData['type'] !== $requiredType) {
            return response()->json([
                'message' => 'Accès non autorisé pour ce type d\'utilisateur'
            ], 403);
        }

        // Ajouter les données utilisateur à la requête
        $request->merge(['user_data' => $userData]);

        return $next($request);
    }
}
