<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    /**
     * Récupérer les administrateurs avec leurs entreprises
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = $request->query('id_company');

            $query = AdminUser::with(['company', 'subRole'])
                ->select([
                    'admin_user.id_admin_user AS id',
                    'admin_user.name',
                    'admin_user.first_name AS firstName',
                    'admin_user.email',
                    'admin_user.phone',
                    'admin_user.profile_picture_url AS profilePictureUrl',
                    'admin_user.created_at AS createdAt',
                    'admin_user.updated_at AS updatedAt',
                    'admin_user.is_banned AS isBanned',
                    'admin_user.id_company',
                    'admin_user.id_sub_role',
                ]);

            // Filtre optionnel par entreprise
            if ($companyId && is_numeric($companyId)) {
                $query->where('admin_user.id_company', $companyId);
            }

            $adminUsers = $query->get();

            $formatted = $adminUsers->map(function ($admin) {
                return [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'firstName' => $admin->firstName,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'profilePictureUrl' => $admin->profilePictureUrl,
                    'createdAt' => $admin->createdAt,
                    'updatedAt' => $admin->updatedAt,
                    'isBanned' => $admin->isBanned,
                    'companyInfo' => $admin->company ? [
                        'id' => $admin->company->id_company,
                        'name' => $admin->company->name,
                        'description' => $admin->company->description,
                        'legalForm' => $admin->company->legal_form,
                        'siren' => $admin->company->siren,
                        'siret' => $admin->company->siret,
                        'logoUrl' => $admin->company->logo_url,
                        'phone' => $admin->company->phone,
                        'reservationIsActive' => (bool) $admin->company->reservation_is_active,
                        'managePlanIsActive' => (bool) $admin->company->manage_plan_is_active,
                        'virtualOfficeIsActive' => (bool) $admin->company->virtual_office_is_active,
                        'postMailManagementIsActive' => (bool) $admin->company->post_mail_management_is_active,
                        'digicodeIsActive' => (bool) $admin->company->digicode_is_active,
                        'mailScanningIsActive' => (bool) $admin->company->mail_scanning_is_active,
                        'electronicSignatureIsActive' => (bool) $admin->company->electronic_signature_is_active,
                        'isBanned' => (bool) $admin->company->is_banned,
                        'createdAt' => $admin->company->created_at,
                        'updatedAt' => $admin->company->updated_at,
                    ] : null,
                    'id_sub_role' => $admin->id_sub_role,
                    'sub_role_label' => $admin->subRole ? $admin->subRole->label : null,
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $formatted->count(),
                'data' => $formatted,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des administrateurs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un administrateur avec toutes ses informations
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
            $admin = AdminUser::with(['company.colorTheme'])
                ->where('id_admin_user', $id)
                ->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrateur non trouvé.',
                ], 404);
            }

            // Récupérer les documents de domiciliation si l'entreprise existe
            $documents = [];
            if ($admin->company) {
                $documents = DB::table('domiciliation_file_type as df')
                    ->leftJoin('category_file as cf', 'df.id_category_file', '=', 'cf.id_category_file')
                    ->select([
                        'df.id_file_type AS idDomiciliationFile',
                        'df.label AS documentFileLabel',
                        'df.description AS documentFileDescription',
                        'cf.id_category_file AS idCategory',
                        'cf.category_name AS categoryFile',
                        'cf.category_description AS categoryDescription',
                        'cf.category_files AS labelDescription'
                    ])
                    ->where('df.id_company', $admin->company->id_company)
                    ->get()
                    ->groupBy('idDomiciliationFile')
                    ->map(function ($group) {
                        $first = $group->first();
                        $labels = $group->where('idCategory', '!=', null)->map(function ($item) {
                            return [
                                'id' => $item->idCategory,
                                'description' => $item->labelDescription,
                            ];
                        })->values()->toArray();

                        return [
                            'id' => $first->idDomiciliationFile,
                            'file_type_label' => $first->documentFileLabel,
                            'file_description' => $first->documentFileDescription,
                            'categoryType' => [
                                'idCategory' => $first->idCategory,
                                'categoryName' => $first->categoryFile,
                                'categoryDescription' => $first->categoryDescription,
                                'labels' => $labels,
                            ],
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            $data = [
                'id' => $admin->id_admin_user,
                'name' => $admin->name,
                'firstName' => $admin->first_name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'profilePictureUrl' => $admin->profile_picture_url,
                'createdAt' => $admin->created_at,
                'updatedAt' => $admin->updated_at,
                'isBanned' => $admin->is_banned,
                'idCompany' => $admin->id_company,
                'companyInfo' => null,
            ];

            if ($admin->company) {
                $company = $admin->company;
                $data['companyInfo'] = [
                    'id' => $company->id_company,
                    'slug' => $company->slug,
                    'name' => $company->name,
                    'email' => $company->email,
                    'description' => $company->description,
                    'legalForm' => $company->legal_form,
                    'siren' => $company->siren,
                    'siret' => $company->siret,
                    'logoUrl' => $company->logo_url,
                    'phone' => $company->phone,
                    'reservationIsActive' => (bool) $company->reservation_is_active,
                    'managePlanIsActive' => (bool) $company->manage_plan_is_active,
                    'virtualOfficeIsActive' => (bool) $company->virtual_office_is_active,
                    'postMailManagementIsActive' => (bool) $company->post_mail_management_is_active,
                    'digicodeIsActive' => (bool) $company->digicode_is_active,
                    'mailScanningIsActive' => (bool) $company->mail_scanning_is_active,
                    'electronicSignatureIsActive' => (bool) $company->electronic_signature_is_active,
                    'tvaIsActive' => (bool) ($company->tva_is_active ?? false),
                    'tva' => $company->tva,
                    'stripePrivateKey' => $company->stripe_private_key,
                    'stripePublicKey' => $company->stripe_public_key,
                    'stripeWebhookSecret' => $company->stripe_webhook_secret,
                    'isBanned' => (bool) $company->is_banned,
                    'createdAt' => $company->created_at,
                    'updatedAt' => $company->updated_at,
                    'documents' => $documents,
                    'theme' => $company->colorTheme ? [
                        'id' => $company->colorTheme->id_color_theme,
                        'name' => $company->colorTheme->name,
                        'category' => $company->colorTheme->category_theme,
                        'backgroundColor' => $company->colorTheme->background_color,
                        'primaryColor' => $company->colorTheme->primary_color,
                        'primaryColorHover' => $company->colorTheme->primary_color_hover,
                        'foregroundColor' => $company->colorTheme->foreground_color,
                        'standardColor' => $company->colorTheme->standard_color,
                    ] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération de l\'administrateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un administrateur
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$id || $id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide ou manquant.',
            ], 400);
        }

        try {
            $body = $request->all();

            $allowedFields = [
                'name',
                'first_name',
                'email',
                'phone',
                'profile_picture_url',
                'password_hash',
                'is_banned',
            ];

            $updateData = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $body)) {
                    $updateData[$field] = $body[$field];
                }
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour.',
                ], 400);
            }

            $updateData['updated_at'] = now();

            $affectedRows = AdminUser::where('id_admin_user', $id)
                ->update($updateData);

            if ($affectedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrateur introuvable.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Administrateur mis à jour avec succès.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour de l\'administrateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
