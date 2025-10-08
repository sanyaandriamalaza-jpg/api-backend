<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VirtualOfficeOffer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class VirtualOfficeOfferController extends Controller
{
    public function __construct()
    {
        // Initialiser Stripe avec la clé secrète
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Récupérer les offres de bureau virtuel
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idCompany = $request->query('id_company');
            $slug = $request->query('company_slug');

            $query = VirtualOfficeOffer::with('company');

            if ($idCompany) {
                $query->where('id_company', $idCompany);
            }

            if ($slug) {
                $query->whereHas('company', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            }

            $offers = $query->orderBy('created_at', 'desc')->get();

            $data = $offers->map(function ($offer) {
                return [
                    'id' => $offer->id_virtual_office_offer,
                    'name' => $offer->name,
                    'description' => $offer->description,
                    'features' => $offer->features,
                    'monthlyPrice' => (float) $offer->price,
                    'isTagged' => $offer->is_tagged,
                    'tag' => $offer->tag,
                    'createdAt' => $offer->created_at ? $offer->created_at->toISOString() : null,
                    'company' => [
                        'id' => $offer->company->id_company,
                        'name' => $offer->company->name,
                        'slug' => $offer->company->slug,
                        'address' => $offer->company->address_line ?? null,
                        'email' => $offer->company->email ?? null,
                        'phone' => $offer->company->phone ?? null,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $data->count(),
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer une nouvelle offre avec produit Stripe
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
                'price' => 'required|numeric|min:0',
                'is_tagged' => 'nullable|boolean',
                'tag' => 'nullable|string|max:100',
                'id_company' => 'required|integer|exists:company,id_company',
            ], [
                'name.required' => 'Le nom est obligatoire',
                'price.required' => 'Le prix est obligatoire',
                'price.numeric' => 'Le prix doit être un nombre',
                'id_company.required' => 'L\'entreprise est obligatoire',
                'id_company.exists' => 'L\'entreprise spécifiée n\'existe pas',
            ]);

            // Créer l'offre dans la base de données
            $offer = new VirtualOfficeOffer([
                'name' => $request->name,
                'description' => $request->description,
                'features' => $request->features,
                'price' => $request->price,
                'is_tagged' => $request->is_tagged ?? false,
                'tag' => $request->tag,
                'id_company' => $request->id_company,
            ]);
            
            $offer->created_at = now();
            $offer->save();

            // Créer le produit Stripe
            try {
                $product = Product::create([
                    'name' => $request->name,
                    'description' => $request->description ?? null,
                    'metadata' => [
                        'id_company' => (string) $request->id_company,
                        'db_offer_id' => (string) $offer->id_virtual_office_offer,
                    ],
                ]);

                // Créer le prix Stripe (abonnement mensuel)
                $stripePrice = Price::create([
                    'unit_amount' => (int) round($request->price * 100), // Convertir en centimes
                    'currency' => 'eur',
                    'recurring' => ['interval' => 'month'],
                    'product' => $product->id,
                ]);

                // Mettre à jour l'offre avec les IDs Stripe
                $offer->update([
                    'stripe_product_id' => $product->id,
                    'stripe_price_id' => $stripePrice->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Offre créée avec abonnement Stripe',
                    'insertedId' => $offer->id_virtual_office_offer,
                    'stripeProductId' => $product->id,
                    'stripePriceId' => $stripePrice->id,
                ], 201);

            } catch (\Stripe\Exception\ApiErrorException $e) {
                // Si erreur Stripe, supprimer l'offre créée
                $offer->delete();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du produit Stripe',
                    'error' => $e->getMessage(),
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer une offre spécifique
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $offer = VirtualOfficeOffer::with('company')->find($id);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offre non trouvée',
                ], 404);
            }

            $data = [
                'id' => $offer->id_virtual_office_offer,
                'name' => $offer->name,
                'description' => $offer->description,
                'features' => $offer->features,
                'monthlyPrice' => (float) $offer->price,
                'isTagged' => $offer->is_tagged,
                'tag' => $offer->tag,
                'createdAt' => $offer->created_at ? $offer->created_at->toISOString() : null,
                'company' => [
                    'id' => $offer->company->id_company,
                    'name' => $offer->company->name,
                    'slug' => $offer->company->slug,
                    'address' => $offer->company->address_line ?? null,
                    'email' => $offer->company->email ?? null,
                    'phone' => $offer->company->phone ?? null,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une offre
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $offer = VirtualOfficeOffer::find($id);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offre non trouvée',
                ], 404);
            }

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'features' => 'nullable|array',
                'price' => 'sometimes|required|numeric|min:0',
                'is_tagged' => 'nullable|boolean',
                'tag' => 'nullable|string|max:100',
            ]);

            $updateData = [];

            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }

            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }

            if ($request->has('features')) {
                $updateData['features'] = $request->features;
            }

            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }

            if ($request->has('is_tagged')) {
                $updateData['is_tagged'] = $request->is_tagged;
            }

            if ($request->has('tag')) {
                $updateData['tag'] = $request->tag;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée à mettre à jour',
                ], 400);
            }

            $offer->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Offre mise à jour avec succès',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une offre
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $offer = VirtualOfficeOffer::find($id);

            if (!$offer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offre non trouvée',
                ], 404);
            }

            // Optionnel : Archiver ou supprimer le produit Stripe
            // if ($offer->stripe_product_id) {
            //     try {
            //         Product::update($offer->stripe_product_id, ['active' => false]);
            //     } catch (\Exception $e) {
            //         // Log l'erreur mais ne pas bloquer la suppression
            //     }
            // }

            $offer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Offre supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
