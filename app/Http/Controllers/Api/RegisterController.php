<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\BasicUser;
use App\Models\Company;
use App\Models\SubRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admin_user,email|unique:basic_user,email',
            'password' => 'required|string|min:6',
            'typeOfUser' => 'required|in:basic_user,admin_user',
            'tagOfAdmin' => 'required_if:typeOfUser,admin_user',
            'id_company' => 'required|exists:company,id_company',
            'phone' => 'nullable|string|max:50',
            'allowSendingEmail' => 'boolean',
            'sendCredentialsViaMail' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Récupérer les infos de l'entreprise
        $companyInfo = Company::find($data['id_company']);
        if (!$companyInfo) {
            return response()->json([
                'success' => false,
                'message' => "L'entreprise n'existe pas."
            ], 404);
        }

        try {
            $userId = null;

            if ($data['typeOfUser'] === 'admin_user') {
                // Vérifier le sous-rôle
                $subRole = SubRole::where('label', $data['tagOfAdmin'])->first();
                if (!$subRole) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le tag fourni n'existe pas."
                    ], 404);
                }

                $user = AdminUser::create([
                    'name' => $data['name'],
                    'first_name' => $data['first_name'],
                    'email' => $data['email'],
                    'password_hash' => Hash::make($data['password']),
                    'phone' => $data['phone'] ?? null,
                    'id_company' => $data['id_company'],
                    'id_sub_role' => $subRole->id_sub_role,
                ]);
                $userId = $user->id_admin_user;

            } else { // basic_user
                $user = BasicUser::create([
                    'name' => $data['name'],
                    'first_name' => $data['first_name'],
                    'email' => $data['email'],
                    'password_hash' => Hash::make($data['password']),
                    'phone' => $data['phone'] ?? null,
                    'id_company' => $data['id_company'],
                ]);
                $userId = $user->id_basic_user;
            }

            // Envoi d'email si requis (pour basic_user seulement)
            if ($data['typeOfUser'] === 'basic_user' && 
                ($data['allowSendingEmail'] ?? false)) {
                $this->sendWelcomeEmail($data, $companyInfo);
            }

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur créé avec succès',
                'id' => $userId
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'inscription",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function sendWelcomeEmail(array $data, Company $companyInfo)
    {
        // Adaptez selon votre système d'envoi d'email existant
        $emailData = [
            'to' => $data['email'],
            'subject' => "Bienvenue parmi nous, chez {$companyInfo->name}",
            'firstName' => $data['first_name'],
            'companyInfo' => [
                'name' => $companyInfo->name,
                'addressLine' => $companyInfo->address_line,
                'postalCode' => $companyInfo->postal_code,
                'city' => $companyInfo->city,
                'country' => $companyInfo->country,
                'phone' => $companyInfo->phone,
                'state' => $companyInfo->state,
            ],
            'credentials' => ($data['sendCredentialsViaMail'] ?? false) ? [
                'email' => $data['email'],
                'password' => $data['password']
            ] : null
        ];

        // Appel à votre service d'envoi d'email
        // Http::post(config('app.url') . '/api/send-general-email', $emailData);
    }
}