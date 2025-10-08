<?php

// app/Http/Controllers/Api/InvoiceDetailsController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InvoiceDetailsController extends Controller
{
    /**
     * Récupérer les détails complets d'une facture avec toutes les relations
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        if (!is_numeric($id) || $id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID de facture invalide.',
            ], 400);
        }

        try {
            $query = "
                SELECT 
                    -- Informations de la facture
                    i.id_invoice AS invoice_id,
                    i.reference AS invoice_reference,
                    i.reference_num AS invoice_reference_num,
                    i.user_name AS customer_name,
                    i.user_first_name AS customer_first_name,
                    i.user_email AS customer_email,
                    i.user_address_line AS customer_address_line,
                    i.user_city AS customer_city,
                    i.user_state AS customer_state,
                    i.user_postal_code AS customer_postal_code,
                    i.user_country AS customer_country,
                    i.issue_date AS invoice_issue_date,
                    i.start_subscription AS service_start_date,
                    i.duration AS service_duration,
                    i.duration_type AS service_duration_type,
                    i.note AS invoice_note,
                    i.amount AS invoice_amount,
                    i.amount_net AS invoice_amount_net,
                    i.currency AS invoice_currency,
                    i.status AS invoice_status,
                    i.subscription_status AS service_status,
                    i.payment_method AS payment_method,
                    i.stripe_payment_id AS stripe_payment_id,
                    i.is_processed AS invoice_is_processed,
                    i.created_at AS invoice_created_at,
                    
                    -- Informations de l'entreprise
                    c.id_company AS company_id,
                    c.slug AS company_slug,
                    c.name AS company_name,
                    c.description AS company_description,
                    c.legal_form AS company_legal_form,
                    c.Nif_number AS company_nif,
                    c.stat_number AS company_stat,
                    c.logo_url AS company_logo_url,
                    c.phone AS company_phone,
                    c.email AS company_email,
                    c.social_links AS company_social_links,
                    c.address_line AS company_address_line,
                    c.postal_code AS company_postal_code,
                    c.city AS company_city,
                    c.state AS company_state,
                    c.country AS company_country,
                    
                    -- Informations du client (utilisateur de base)
                    bu.id_basic_user AS customer_id,
                    bu.profile_picture_url AS customer_profile_picture,
                    bu.phone AS customer_phone,
                    bu.created_at AS customer_created_at,
                    
                    -- Informations du bureau virtuel (si applicable)
                    vo.id_virtual_office_offer AS virtual_office_offer_id,
                    vo.name AS virtual_office_name,
                    vo.description AS virtual_office_description,
                    vo.features AS virtual_office_features,
                    vo.price AS virtual_office_price,
                    vo.is_tagged AS virtual_office_is_tagged,
                    vo.tag AS virtual_office_tag
                    
                FROM invoice i
                INNER JOIN basic_user bu ON bu.id_basic_user = i.id_basic_user
                INNER JOIN company c ON c.id_company = bu.id_company
                LEFT JOIN virtual_office_offer vo ON vo.id_virtual_office_offer = i.id_virtual_office_offer
                WHERE i.id_invoice = ?
            ";

            $result = DB::select($query, [$id]);

            if (empty($result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture introuvable.',
                ], 404);
            }

            $row = (object) $result[0];

            // Construction de la réponse structurée
            $invoiceData = [
                'invoice' => [
                    'id' => $row->invoice_id,
                    'reference' => $row->invoice_reference,
                    'referenceNum' => $row->invoice_reference_num,
                    'issueDate' => $row->invoice_issue_date,
                    'serviceStartDate' => $row->service_start_date,
                    'duration' => $row->service_duration,
                    'durationType' => $row->service_duration_type,
                    'note' => $row->invoice_note,
                    'amount' => (float) ($row->invoice_amount ?? 0),
                    'amountNet' => $row->invoice_amount_net ? (float) $row->invoice_amount_net : null,
                    'currency' => $row->invoice_currency,
                    'status' => $row->invoice_status,
                    'subscriptionStatus' => $row->service_status,
                    'paymentMethod' => $row->payment_method,
                    'stripePaymentId' => $row->stripe_payment_id,
                    'isProcessed' => (bool) $row->invoice_is_processed,
                    'createdAt' => $row->invoice_created_at,
                ],
                'company' => [
                    'id' => $row->company_id,
                    'slug' => $row->company_slug,
                    'name' => $row->company_name,
                    'description' => $row->company_description,
                    'legalForm' => $row->company_legal_form,
                    'siren' => $row->company_nif,
                    'siret' => $row->company_stat,
                    'logoUrl' => $row->company_logo_url,
                    'phone' => $row->company_phone,
                    'email' => $row->company_email,
                    'socialLinks' => $row->company_social_links ? json_decode($row->company_social_links, true) : null,
                    'address' => [
                        'addressLine' => $row->company_address_line,
                        'postalCode' => $row->company_postal_code,
                        'city' => $row->company_city,
                        'state' => $row->company_state,
                        'country' => $row->company_country,
                    ],
                ],
                'customer' => [
                    'id' => $row->customer_id,
                    'name' => $row->customer_name,
                    'firstName' => $row->customer_first_name,
                    'email' => $row->customer_email,
                    'phone' => $row->customer_phone,
                    'profilePictureUrl' => $row->customer_profile_picture,
                    'address' => [
                        'addressLine' => $row->customer_address_line,
                        'city' => $row->customer_city,
                        'state' => $row->customer_state,
                        'postalCode' => $row->customer_postal_code,
                        'country' => $row->customer_country,
                    ],
                    'createdAt' => $row->customer_created_at,
                ],
                'service' => null,
            ];

            // Déterminer le type de service et construire les données appropriées
            if ($row->virtual_office_offer_id) {
                // Service de bureau virtuel
                $invoiceData['service'] = [
                    'type' => 'virtual_office',
                    'id' => $row->virtual_office_offer_id,
                    'name' => $row->virtual_office_name,
                    'description' => $row->virtual_office_description,
                    'features' => $row->virtual_office_features ? json_decode($row->virtual_office_features, true) : [],
                    'price' => (float) ($row->virtual_office_price ?? 0),
                    'isTagged' => (bool) $row->virtual_office_is_tagged,
                    'tag' => $row->virtual_office_tag,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $invoiceData,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération de la facture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}