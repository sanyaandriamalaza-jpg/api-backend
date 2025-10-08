<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SingleInvoiceController extends Controller
{
    /**
     * Récupérer les factures avec offre de bureau virtuel
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idBasicUser = $request->query('id_basic_user');

            $query = Invoice::with('virtualOfficeOffer');

            if ($idBasicUser && is_numeric($idBasicUser)) {
                $query->where('id_basic_user', $idBasicUser);
            }

            $invoices = $query->get();

            if ($invoices->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune facture trouvée.',
                ], 404);
            }

            $data = $invoices->map(function ($invoice) {
                $virtualOfficeOffer = null;
                
                if ($invoice->virtualOfficeOffer) {
                    $offer = $invoice->virtualOfficeOffer;
                    $virtualOfficeOffer = [
                        'id' => $offer->id_virtual_office_offer,
                        'name' => $offer->name,
                        'description' => $offer->description ?? '',
                        'stripePriceId' => $offer->stripe_price_id,
                        'features' => $offer->features,
                        'monthlyPrice' => (float) $offer->price,
                        'idCompany' => $offer->id_company,
                    ];
                }

                return [
                    'id' => $invoice->id_invoice,
                    'reference' => $invoice->reference,
                    'referenceNum' => $invoice->reference_num,
                    'user' => [
                        'name' => $invoice->user_name,
                        'firstName' => $invoice->user_first_name,
                        'email' => $invoice->user_email,
                        'addressLine' => $invoice->user_address_line,
                        'city' => $invoice->user_city,
                        'state' => $invoice->user_state,
                        'postalCode' => $invoice->user_postal_code,
                        'country' => $invoice->user_country,
                    ],
                    'issueDate' => $invoice->issue_date,
                    'startSubscription' => $invoice->start_subscription,
                    'duration' => $invoice->duration,
                    'durationType' => $invoice->duration_type,
                    'note' => $invoice->note ?? '',
                    'amount' => (float) $invoice->amount,
                    'amountNet' => $invoice->amount_net ? (float) $invoice->amount_net : null,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'subscriptionStatus' => $invoice->subscription_status,
                    'companyTva' => $invoice->company_tva ? (float) $invoice->company_tva : null,
                    'paymentMethod' => $invoice->payment_method,
                    'stripePaymentId' => $invoice->stripe_payment_id,
                    'isProcessed' => $invoice->is_processed,
                    'createdAt' => $invoice->created_at,
                    'updatedAt' => $invoice->updated_at,
                    'idBasicUser' => $invoice->id_basic_user,
                    'idVirtualOfficeOffer' => $invoice->id_virtual_office_offer,
                    'idAccessCode' => $invoice->id_access_code,
                    'virtualOfficeOffer' => $virtualOfficeOffer,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des factures.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer une facture spécifique par son ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Validation de l'ID
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID invalide.',
            ], 400);
        }

        try {
            // Récupérer la facture avec ses relations
            $invoice = Invoice::with(['virtualOfficeOffer']);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée.',
                ], 404);
            }
            $invoice = $invoice->find($id);
            // Construire la réponse
            $data = [
                'id' => $invoice->id_invoice,
                'reference' => $invoice->reference,
                'referenceNum' => $invoice->reference_num,
                'user' => [
                    'name' => $invoice->user_name,
                    'firstName' => $invoice->user_first_name,
                    'email' => $invoice->user_email,
                    'addressLine' => $invoice->user_address_line,
                    'city' => $invoice->user_city,
                    'state' => $invoice->user_state,
                    'postalCode' => $invoice->user_postal_code,
                    'country' => $invoice->user_country,
                ],
                'issueDate' => $invoice->issue_date,
                'startSubscription' => $invoice->start_subscription,
                'duration' => $invoice->duration,
                'durationType' => $invoice->duration_type,
                'note' => $invoice->note ?? '',
                'amount' => (float) $invoice->amount,
                'amountNet' => $invoice->amount_net ? (float) $invoice->amount_net : null,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'subscriptionStatus' => $invoice->subscription_status,
                'paymentMethod' => $invoice->payment_method,
                'stripePaymentId' => $invoice->stripe_payment_id,
                'isProcessed' => $invoice->is_processed,
                'createdAt' => $invoice->created_at,
                'updatedAt' => $invoice->updated_at,
                'idBasicUser' => $invoice->id_basic_user,
                'idVirtualOfficeOffer' => $invoice->id_virtual_office_offer,
                'idAccessCode' => $invoice->id_access_code,
                'idOffice' => $invoice->id_office,
                'office' => null,
            ];

            // Ajouter les informations du bureau si présent
            if ($invoice->office) {
                $office = $invoice->office;
                
                $data['office'] = [
                    'id' => $office->id_office,
                    'name' => $office->name,
                    'description' => $office->description ?? '',
                    'features' => $office->features,
                    'specificBusinessHour' => $office->specific_business_hour,
                    'specificAddress' => $office->specific_address,
                    'maxSeatCapacity' => $office->max_seat_capacity,
                    'imageUrl' => $office->image_url,
                    'idCoworkingOffer' => $office->id_coworking_offer,
                    'createdAt' => $office->created_at,
                    'updatedAt' => $office->updated_at,
                    'coworkingOffer' => null,
                ];

                // Ajouter les informations de l'offre coworking si présente
                if ($office->coworkingOffer) {
                    $coworkingOffer = $office->coworkingOffer;
                    
                    $data['office']['coworkingOffer'] = [
                        'id' => $coworkingOffer->id_coworking_offer,
                        'type' => $coworkingOffer->type,
                        'description' => $coworkingOffer->description,
                        'hourlyRate' => $coworkingOffer->hourly_rate 
                            ? (float) $coworkingOffer->hourly_rate : null,
                        'dailyRate' => $coworkingOffer->daily_rate 
                            ? (float) $coworkingOffer->daily_rate : null,
                        'features' => $coworkingOffer->features,
                        'isTagged' => $coworkingOffer->is_tagged,
                        'tag' => $coworkingOffer->tag,
                        'createdAt' => $coworkingOffer->created_at,
                        'updatedAt' => $coworkingOffer->updated_at,
                        'idCompany' => $coworkingOffer->id_company,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération de la facture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une facture
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Validation de l'ID
        if ($id <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID de facture invalide.',
            ], 400);
        }

        // Vérifier qu'il y a des données dans le body
        if (!$request->all() || empty($request->all())) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun champ fourni pour la mise à jour.',
            ], 400);
        }

        try {
            // Récupérer la facture
            $invoice = Invoice::find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture introuvable.',
                ], 404);
            }

            // Récupérer les données actuelles
            $originalData = $invoice->getOriginal();
            
            // Préparer les champs à mettre à jour
            $updateData = [];
            $hasChanges = false;

            foreach ($request->all() as $key => $value) {
                // Vérifier si le champ existe dans le modèle et s'il a changé
                if (array_key_exists($key, $originalData) && $originalData[$key] != $value) {
                    $updateData[$key] = $value;
                    $hasChanges = true;
                }
            }

            // Si aucune modification détectée
            if (!$hasChanges) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune modification détectée.',
                ], 409);
            }

            // Effectuer la mise à jour
            $invoice->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Mise à jour effectuée avec succès.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}