<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ColorTheme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * GET - Récupérer toutes les entreprises avec relations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $slug = $request->query('slug');

            // Query avec toutes les relations
            $query = Company::with([
                'colorTheme',
                'adminUsers',
                'domiciliationFileTypes.categoryFile'
            ]);

            // Filtrer par slug si fourni
            if ($slug) {
                $query->where('slug', $slug);
            }

            $companies = $query->get();

            // Transformer les données selon votre format Next.js
            $result = $companies->map(function ($company) {
                return $this->transformCompanyData($company, true); 
            });

            return response()->json([
                'success' => true,
                'count' => $result->count(),
                'data' => $result->toArray(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des entreprises.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET - Récupérer une entreprise par ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = Company::with([
                'colorTheme',
                'adminUsers'
            ])->find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise introuvable.'
                ], 404);
            }

            $data = $this->transformCompanyData($company,true); 

            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST - Créer une nouvelle entreprise
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'legal_form' => 'nullable|string|max:100',
                'Nif_number' => 'nullable|string',
                'stat_number' => 'nullable|string',
                'logo_url' => 'nullable|string',
                'phone' => 'nullable|string|max:25',
                'email' => 'nullable|email|max:250',
                'social_links' => 'nullable|array',
                'address_line' => 'nullable|string|max:250',
                'postal_code' => 'nullable|string|max:20',
                'city' => 'nullable|string|max:250',
                'state' => 'nullable|string|max:250',
                'country' => 'nullable|string|max:100',
                'google_map_iframe' => 'nullable|string',
                'manage_plan_is_active' => 'boolean',
                'virtual_office_is_active' => 'boolean',
                'post_mail_management_is_active' => 'boolean',
                'mail_scanning_is_active' => 'boolean',
                'electronic_signature_is_active' => 'boolean',
                'tva_is_active' => 'boolean',
                'tva' => 'nullable|numeric|min:0',
                'stripe_private_key' => 'nullable|string',
                'stripe_public_key' => 'nullable|string',
                'stripe_webhook_secret' => 'nullable|string',
                'invoice_office_ref' => 'nullable|string|max:10',
                'invoice_virtual_office_ref' => 'nullable|string|max:10',
                'is_banned' => 'boolean',
                'id_color_theme' => 'required|exists:color_theme,id_color_theme',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();

            // Générer le slug unique
            $slug = $this->generateUniqueSlug($data['name']);
            $data['slug'] = $slug;

            // Encoder les JSON fields
            if (isset($data['social_links'])) {
                $data['social_links'] = json_encode($data['social_links']);
            }

            // Créer l'entreprise
            $company = Company::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Entreprise créée avec succès',
                'insertedId' => $company->id_company,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la création de l\'entreprise.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH - Mettre à jour une entreprise
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise introuvable.'
                ], 404);
            }

            // Champs autorisés pour la mise à jour
            $allowedFields = [
                'name', 'description', 'legal_form', 'Nif_number', 'stat_number',
                'logo_url', 'phone', 'email', 'social_links', 'address_line',
                'postal_code', 'city', 'state', 'country', 'google_map_iframe',
                'manage_plan_is_active', 'virtual_office_is_active',
                'post_mail_management_is_active', 'mail_scanning_is_active',
                'electronic_signature_is_active', 'tva_is_active', 'tva',
                'stripe_private_key', 'stripe_public_key', 'stripe_webhook_secret',
                'invoice_office_ref', 'invoice_virtual_office_ref',
                'is_banned', 'id_color_theme'
            ];

            $updateData = [];
            $hasValidFields = false;

            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $value = $request->input($field);
                    
                    // Traitement spécial pour les champs JSON
                    if ($field === 'social_links' && is_array($value)) {
                        $value = json_encode($value);
                    }
                    
                    $updateData[$field] = $value;
                    $hasValidFields = true;
                }
            }

            if (!$hasValidFields) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour.'
                ], 400);
            }

            // Validation des données à mettre à jour
            $rules = [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|nullable|email|max:250',
                'phone' => 'sometimes|nullable|string|max:25',
                'id_color_theme' => 'sometimes|exists:color_theme,id_color_theme',
                'tva' => 'sometimes|nullable|numeric|min:0',
            ];

            $validator = Validator::make($updateData, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Mettre à jour l'entreprise
            $company->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Entreprise mise à jour avec succès.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE - Supprimer une entreprise
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $company = Company::find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entreprise introuvable.'
                ], 404);
            }

            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Entreprise supprimée avec succès.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // MÉTHODES PRIVÉES UTILITAIRES
    // =====================================================

    /**
     * Transformer les données de l'entreprise
     */
    private function transformCompanyData(Company $company, bool $includeDocuments = false): array
    {
        $data = [
            'id' => $company->id_company,
            'name' => $company->name,
            'slug' => $company->slug,
            'description' => $company->description,
            'legalForm' => $company->legal_form,
            'nifNumber' => $company->Nif_number,
            'statNumber' => $company->stat_number,
            'logoUrl' => $company->logo_url,
            'phone' => $company->phone,
            'email' => $company->email,
            'socialLinks' => $company->social_links ? json_decode($company->social_links, true) : null,
            'addressLine' => $company->address_line,
            'postalCode' => $company->postal_code,
            'city' => $company->city,
            'state' => $company->state,
            'country' => $company->country,
            'googleMapIframe' => $company->google_map_iframe,
            'managePlanIsActive' => (bool) $company->manage_plan_is_active,
            'virtualOfficeIsActive' => (bool) $company->virtual_office_is_active,
            'postMailManagementIsActive' => (bool) $company->post_mail_management_is_active,
            'mailScanningIsActive' => (bool) $company->mail_scanning_is_active,
            'electronicSignatureIsActive' => (bool) $company->electronic_signature_is_active,
            'tvaIsActive' => (bool) $company->tva_is_active,
            'tva' => $company->tva,
            'stripePrivateKey' => $company->stripe_private_key,
            'stripePublicKey' => $company->stripe_public_key,
            'stripeWebhookSecret' => $company->stripe_webhook_secret,
            'invoiceOfficeRef' => $company->invoice_office_ref,
            'invoiceVirtualOfficeRef' => $company->invoice_virtual_office_ref,
            'isBanned' => (bool) $company->is_banned,
            'createdAt' => $company->created_at,
            'updatedAt' => $company->updated_at,
        ];

        // Ajouter le thème
        if ($company->colorTheme) {
            $data['theme'] = [
                'id' => $company->colorTheme->id_color_theme,
                'name' => $company->colorTheme->name,
                'backgroundColor' => $company->colorTheme->background_color,
                'primaryColor' => $company->colorTheme->primary_color,
                'primaryColorHover' => $company->colorTheme->primary_color_hover,
                'foregroundColor' => $company->colorTheme->foreground_color,
                'standardColor' => $company->colorTheme->standard_color,
                'createdAt' => $company->colorTheme->created_at,
            ];
        }

        // Ajouter les utilisateurs admin
        $data['adminUserList'] = $company->adminUsers->map(function ($admin) {
            return [
                'id' => $admin->id_admin_user,
                'name' => $admin->name,
                'firstName' => $admin->first_name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'profilePictureUrl' => $admin->profile_picture_url,
                'createdAt' => $admin->created_at,
                'updatedAt' => $admin->updated_at,
                'isBanned' => (bool) $admin->is_banned,
                'idCompany' => $admin->id_company,
            ];
        })->toArray();

        // Ajouter les documents si requis (pour l'index)
        if ($includeDocuments) {
            $data['documents'] = $company->domiciliationFileTypes->map(function ($fileType) {
                $document = [
                    'id' => $fileType->id_file_type,
                    'file_type_label' => $fileType->label,
                    'file_description' => $fileType->description,
                ];

                if ($fileType->categoryFile) {
                    $document['categoryType'] = [
                        'idCategory' => $fileType->categoryFile->id_category_file,
                        'categoryName' => $fileType->categoryFile->category_name,
                        'categoryDescription' => $fileType->categoryFile->category_description,
                        'labels' => $fileType->categoryFile->category_files
                    ];
                }

                return $document;
            })->toArray();
        }

        return $data;
    }

    /**
     * Générer un slug unique basé sur le nom
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
