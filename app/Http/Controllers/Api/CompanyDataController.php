<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CompanyDataController extends Controller
{
    /**
     * Récupérer les données d'une entreprise pour le RAG
     * 
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        if (!$slug) {
            return response()->json([
                'error' => 'Slug d\'entreprise manquant',
            ], 400);
        }

        try {
            // 1. Récupérer les informations de base de l'entreprise
            $company = DB::table('company')
                ->select([
                    'id_company', 'name', 'description', 'phone', 'email',
                    'address_line', 'postal_code', 'city', 'state', 'country',
                    'business_hour', 'virtual_office_is_active',
                    'post_mail_management_is_active', 'digicode_is_active',
                    'mail_scanning_is_active'
                ])
                ->where('slug', $slug)
                ->first();

            if (!$company) {
                return response()->json([
                    'error' => 'Entreprise non trouvée',
                ], 404);
            }

            $companyId = $company->id_company;

            // 2. Récupérer les offres de coworking avec bureaux
            $coworkingOffers = DB::table('coworking_offer as co')
                ->leftJoin('office as o', 'o.id_coworking_offer', '=', 'co.id_coworking_offer')
                ->select([
                    'co.type', 'co.description as coworking_description',
                    'co.hourly_rate', 'co.daily_rate', 'co.features as coworking_features',
                    'co.is_tagged', 'co.tag',
                    'o.name as office_name', 'o.description as office_description',
                    'o.features as office_features', 'o.max_seat_capacity',
                    'o.specific_address', 'o.specific_business_hour'
                ])
                ->where('co.id_company', $companyId)
                ->orderBy('co.id_coworking_offer')
                ->get();

            // 3. Récupérer les offres de bureau virtuel
            $virtualOfficeOffers = DB::table('virtual_office_offer')
                ->select(['name', 'description', 'features', 'price', 'is_tagged', 'tag'])
                ->where('id_company', $companyId)
                ->get();

            // 4. Récupérer les types de fichiers de domiciliation
            $domiciliationFileTypes = DB::table('domiciliation_file_type as dft')
                ->leftJoin('category_file as cf', 'cf.id_category_file', '=', 'dft.id_category_file')
                ->select([
                    'dft.label', 'dft.description',
                    'cf.category_name', 'cf.category_description',
                    'cf.label_id', 'cf.label_description'
                ])
                ->where('dft.id_company', $companyId)
                ->where('dft.is_archived', '0')
                ->get();

            // Structurer les données pour le RAG
            $ragData = [
                'entreprise' => [
                    'nom' => $company->name,
                    'description' => $company->description,
                    'telephone' => $company->phone,
                    'email' => $company->email,
                    'adresse' => [
                        'ligne_adresse' => $company->address_line,
                        'code_postal' => $company->postal_code,
                        'ville' => $company->city,
                        'region' => $company->state,
                        'pays' => $company->country,
                    ],
                    'horaires_ouverture' => $company->business_hour ? 
                        json_decode($company->business_hour, true) : null,
                    'services_actifs' => [
                        'bureau_virtuel' => (bool) $company->virtual_office_is_active,
                        'gestion_courrier' => (bool) $company->post_mail_management_is_active,
                        'digicode' => (bool) $company->digicode_is_active,
                        'numerisation_courrier' => (bool) $company->mail_scanning_is_active,
                    ],
                ],
                'domiciliation' => [
                    'services_disponibles' => $company->virtual_office_is_active ? 'Oui' : 'Non',
                    'offres_bureau_virtuel' => $virtualOfficeOffers->map(function ($offer) {
                        return [
                            'nom' => $offer->name,
                            'description' => $offer->description,
                            'prix_mensuel' => (float) $offer->price,
                            'services_inclus' => $offer->features ? 
                                json_decode($offer->features, true) : [],
                            'mise_en_avant' => (bool) $offer->is_tagged,
                            'tag' => $offer->tag ?? null,
                        ];
                    })->toArray(),
                    'types_documents_acceptes' => $domiciliationFileTypes->map(function ($fileType) {
                        return [
                            'type' => $fileType->label,
                            'description' => $fileType->description,
                            'categorie' => $fileType->category_name,
                            'pour' => $fileType->label_id === 'auto-contractor' ? 
                                'Auto-entrepreneur' : 'Société',
                        ];
                    })->toArray(),
                    'services_courrier' => [
                        'reception_courrier' => (bool) $company->post_mail_management_is_active,
                        'numerisation' => (bool) $company->mail_scanning_is_active,
                    ],
                ],
                'espaces_coworking' => [
                    'disponible' => $coworkingOffers->count() > 0,
                    'types_offres' => $coworkingOffers->map(function ($row) {
                        return [
                            'type_espace' => $row->type,
                            'description_offre' => $row->coworking_description,
                            'tarification' => [
                                'tarif_horaire' => $row->hourly_rate ? 
                                    (float) $row->hourly_rate : null,
                                'tarif_journalier' => $row->daily_rate ? 
                                    (float) $row->daily_rate : null,
                            ],
                            'services_inclus' => $row->coworking_features ? 
                                json_decode($row->coworking_features, true) : [],
                            'mise_en_avant' => (bool) $row->is_tagged,
                            'tag' => $row->tag ?? null,
                            'details_bureau' => $row->office_name ? [
                                'nom' => $row->office_name,
                                'description' => $row->office_description,
                                'capacite_maximum' => $row->max_seat_capacity,
                                'equipements' => $row->office_features ? 
                                    json_decode($row->office_features, true) : [],
                                'adresse_specifique' => $row->specific_address ? 
                                    json_decode($row->specific_address, true) : null,
                                'horaires_specifiques' => $row->specific_business_hour ? 
                                    json_decode($row->specific_business_hour, true) : null,
                            ] : null,
                        ];
                    })->toArray(),
                ],
            ];

            return response()->json($ragData);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
