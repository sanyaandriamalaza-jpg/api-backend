<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BasicUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BasicUserController extends Controller
{
    private $fieldMapping = [
        'name' => 'name',
        'firstName' => 'first_name',
        'phone' => 'phone',
        'email' => 'email',
        'addressLine' => 'address_line',
        'city' => 'city',
        'state' => 'state',
        'postalCode' => 'postal_code',
        'country' => 'country',
        'profilePictureUrl' => 'profile_picture_url',
        'officeName' => 'virtualOfficeName',
        'officeLegalForm' => 'virtualOfficeLegalForm',
        'officeSiret' => 'virtualOfficeSiret',
        'isBanned' => 'is_banned',
        'id_company' => 'id_company',
    ];

    // Champs autorisés pour la mise à jour
    private $allowedFields = [
        'name', 'firstName', 'phone', 'email', 'addressLine',
        'city', 'state', 'postalCode', 'country', 'profilePictureUrl',
        'virtualOfficeName', 'virtualOfficeLegalForm', 'virtualOfficeSiret',
        'isBanned', 'id_company'
    ];

    /**
     * Récupérer les utilisateurs d'une entreprise
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = $request->query('id_company');

            if (!$companyId || !is_numeric($companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un identifiant valide d\'entreprise est requis.',
                ], 400);
            }

            $users = BasicUser::select([
                    'id_basic_user AS id',
                    'name',
                    'first_name AS firstName',
                    'email',
                    'phone',
                    'address_line AS addressLine',
                    'city',
                    'state',
                    'postal_code AS postalCode',
                    'country',
                    'profile_picture_url AS profilePictureUrl',
                    'created_at AS createdAt',
                    'updated_at AS updatedAt',
                    'is_banned AS isBanned',
                    'id_company'
                ])
                ->where('id_company', $companyId)
                ->where(function($query) {
                    $query->where('is_banned', false)
                          ->orWhereNull('is_banned');
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'count' => $users->count(),
                'data' => $users,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des utilisateurs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un utilisateur spécifique
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        if (!$id || $id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide ou manquant.',
            ], 400);
        }

        try {
            $user = BasicUser::select([
                    'id_basic_user AS id',
                    'name',
                    'first_name AS firstName',
                    'email',
                    'phone',
                    'address_line AS addressLine',
                    'city',
                    'state',
                    'postal_code AS postalCode',
                    'country',
                    'profile_picture_url AS profilePictureUrl',
                    'created_at AS createdAt',
                    'updated_at AS updatedAt',
                    'is_banned AS isBanned'
                ])
                ->where('id_basic_user', $id)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération de l\'utilisateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            if (!$id || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID utilisateur invalide',
                ], 400);
            }

            $body = $request->all();

            if (empty($body)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée à mettre à jour',
                ], 400);
            }

            // Validation des champs
            $updateData = [];
            $errors = [];

            foreach ($body as $field => $value) {
                if (!in_array($field, $this->allowedFields)) {
                    $errors[] = "Champ non autorisé: {$field}";
                    continue;
                }

                if ($value === null && $field !== 'id_company') {
                    $errors[] = "Valeur invalide pour le champ: {$field}";
                    continue;
                }

                // Validations spécifiques
                if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Format invalide pour le champ: {$field}";
                    continue;
                }

                if ($field === 'phone' && (!is_string($value) || strlen($value) > 20)) {
                    $errors[] = "Format invalide pour le champ: {$field}";
                    continue;
                }

                if ($field === 'isBanned' && !is_bool($value)) {
                    $errors[] = "Format invalide pour le champ: {$field}";
                    continue;
                }

                if ($field === 'id_company' && $value !== null && !is_numeric($value)) {
                    $errors[] = "Format invalide pour le champ: {$field}";
                    continue;
                }

                $updateData[$field] = $value;
            }

            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $errors,
                ], 400);
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée valide à mettre à jour',
                ], 400);
            }

            // Vérifier si l'utilisateur existe
            $user = BasicUser::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ], 404);
            }

            // Vérifier l'unicité de l'email
            if (isset($updateData['email']) && $updateData['email'] !== $user->email) {
                $emailExists = BasicUser::where('email', $updateData['email'])
                    ->where('id_basic_user', '!=', $id)
                    ->exists();

                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'L\'email est déjà utilisé',
                    ], 409);
                }
            }

            // Construire les données de mise à jour avec mapping
            $dbUpdateData = [];
            foreach ($updateData as $field => $value) {
                $dbField = $this->fieldMapping[$field] ?? $field;
                $dbUpdateData[$dbField] = $value;
            }

            $dbUpdateData['updated_at'] = now();

            // Mettre à jour
            $user->update($dbUpdateData);

            // Récupérer l'utilisateur mis à jour
            $updatedUser = BasicUser::select([
                    'id_basic_user AS id',
                    'name',
                    'first_name AS firstName',
                    'email',
                    'phone',
                    'address_line AS addressLine',
                    'city',
                    'state',
                    'postal_code AS postalCode',
                    'country',
                    'profile_picture_url AS profilePictureUrl',
                    'created_at AS createdAt',
                    'updated_at AS updatedAt',
                    'is_banned AS isBanned',
                    'id_company'
                ])
                ->where('id_basic_user', $id)
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => $updatedUser,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            if (!$id || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID utilisateur invalide',
                ], 400);
            }

            // Vérifier si l'utilisateur existe
            $user = BasicUser::select(['id_basic_user', 'name', 'first_name'])
                ->where('id_basic_user', $id)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé',
                ], 404);
            }

            // Supprimer l'utilisateur
            $deleted = BasicUser::where('id_basic_user', $id)->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Échec de la suppression de l\'utilisateur',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Utilisateur {$user->first_name} {$user->name} supprimé avec succès",
                'deletedUserId' => $id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la suppression de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
