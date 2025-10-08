<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * Récupérer les factures
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idVirtualOfficeOffer = $request->query('idVirtualOfficeOffer');
            $type = $request->query('type'); // "virtual-office-offer"

            $query = Invoice::with(['virtualOfficeOffer']);


            if ($idVirtualOfficeOffer) {
                $query->where('id_virtual_office_offer', $idVirtualOfficeOffer);
            }

            if ($type === 'office') {
                $query->whereNotNull('id_office');
            } elseif ($type === 'virtual-office-offer') {
                $query->whereNotNull('id_virtual_office_offer');
            }

            $invoices = $query->orderBy('created_at', 'desc')->get();

            $data = $invoices->map(function ($invoice) {
                $result = [
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
                ];


                return $result;
            });

            return response()->json([
                'success' => true,
                'count' => $data->count(),
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
     * Créer une nouvelle facture
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reference' => 'required|string',
                'referenceNum' => 'required|numeric',
                'userName' => 'required|string',
                'userFirstName' => 'required|string',
                'userEmail' => 'required|email',
                'userAddressLine' => 'nullable|string',
                'userCity' => 'nullable|string',
                'userState' => 'nullable|string',
                'userPostalCode' => 'nullable|string',
                'userCountry' => 'nullable|string',
                'startSubscription' => 'required|date',
                'duration' => 'required|numeric|min:1',
                'durationType' => 'required|in:hourly,daily,monthly,annualy',
                'amount' => 'required|numeric|min:0.01',
                'amountNet' => 'nullable|numeric',
                'currency' => 'required|string',
                'status' => 'required|string',
                'paymentMethod' => 'required|string',
                'stripePaymentId' => 'nullable|string',
                'idBasicUser' => 'required|integer|exists:basic_user,id_basic_user',
                'idVirtualOfficeOffer' => 'nullable|integer|exists:virtual_office_offer,id_virtual_office_offer',
                'note' => 'nullable|string',
                'companyTva' => 'nullable|numeric',
            ], [
                'reference.required' => 'La référence est obligatoire.',
                'referenceNum.required' => 'Le numéro de la référence est obligatoire.',
                'userName.required' => 'Le nom du client est obligatoire.',
                'userFirstName.required' => 'Le prénom du client est obligatoire.',
                'userEmail.required' => "L'adresse email du client est obligatoire.",
                'userEmail.email' => "L'adresse email doit être valide.",
                'startSubscription.required' => 'La date de début est obligatoire.',
                'duration.min' => 'La durée doit être supérieure à 0.',
                'amount.min' => 'Le montant doit être supérieur à 0.',
                'currency.required' => "L'unité monétaire est obligatoire.",
                'paymentMethod.required' => 'La méthode de paiement est obligatoire.',
                'idBasicUser.required' => "L'id de l'utilisateur est requis.",
                'idBasicUser.exists' => "L'utilisateur spécifié n'existe pas.",
            ]);

            // Déterminer le subscription_status
            $subscriptionStatus = $validated['status'] === 'paid' ? 'confirmed' : 'pending';

            $invoice = Invoice::create([
                'reference' => $validated['reference'],
                'reference_num' => $validated['referenceNum'],
                'user_name' => $validated['userName'],
                'user_first_name' => $validated['userFirstName'],
                'user_email' => $validated['userEmail'],
                'user_address_line' => $validated['userAddressLine'] ?? '',
                'user_city' => $validated['userCity'] ?? '',
                'user_state' => $validated['userState'] ?? '',
                'user_postal_code' => $validated['userPostalCode'] ?? '',
                'user_country' => $validated['userCountry'] ?? '',
                'issue_date' => now(),
                'start_subscription' => $validated['startSubscription'],
                'duration' => $validated['duration'],
                'duration_type' => $validated['durationType'],
                'note' => $validated['note'] ?? null,
                'amount' => $validated['amount'],
                'amount_net' => $validated['amountNet'] ?? null,
                'currency' => $validated['currency'],
                'status' => $validated['status'],
                'subscription_status' => $subscriptionStatus,
                'payment_method' => $validated['paymentMethod'],
                'stripe_payment_id' => $validated['stripePaymentId'] ?? null,
                'company_tva' => $validated['companyTva'] ?? null,
                'id_basic_user' => $validated['idBasicUser'],
                'id_virtual_office_offer' => $validated['idVirtualOfficeOffer'] ?? null,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès.',
                'insertedId' => $invoice->id_invoice,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $fieldErrors = [];
            foreach ($e->errors() as $field => $messages) {
                $fieldErrors[] = [
                    'field' => $field,
                    'message' => implode(', ', $messages),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'Champs invalides',
                'errors' => $fieldErrors,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la création de la facture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}