<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ColorTheme;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ColorThemeController extends Controller
{
    /**
     * GET - Récupérer tous les thèmes de couleur
     */
    public function index(): JsonResponse
    {
        try {
            $themes = ColorTheme::orderBy('created_at', 'desc')->get();

            $data = $themes->map(function ($theme) {
                return [
                    'id' => $theme->id_color_theme,
                    'name' => $theme->name,
                    'backgroundColor' => $theme->background_color,
                    'foregroundColor' => $theme->foreground_color,
                    'primaryColor' => $theme->primary_color,
                    'primaryColorHover' => $theme->primary_color_hover,
                    'standardColor' => $theme->standard_color,
                    'category' => $theme->category_theme,
                    'companyId' => $theme->id_company,
                    'createdAt' => $theme->created_at,
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
                'message' => 'Erreur interne du serveur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET - Récupérer un thème spécifique par ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $theme = ColorTheme::find($id);

            if (!$theme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thème non trouvé.',
                ], 404);
            }

            $data = [
                'id' => $theme->id_color_theme,
                'name' => $theme->name,
                'backgroundColor' => $theme->background_color,
                'foregroundColor' => $theme->foreground_color,
                'primaryColor' => $theme->primary_color,
                'primaryColorHover' => $theme->primary_color_hover,
                'standardColor' => $theme->standard_color,
                'category' => $theme->category_theme,
                'companyId' => $theme->id_company,
                'createdAt' => $theme->created_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du thème.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET - Récupérer le thème par id_company
     */
    public function getByCompany(int $companyId): JsonResponse
    {
        try {
            $theme = ColorTheme::where('id_company', $companyId)->first();

            if (!$theme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun thème trouvé pour cette entreprise.',
                ], 404);
            }

            $data = [
                'id' => $theme->id_color_theme,
                'name' => $theme->name,
                'backgroundColor' => $theme->background_color,
                'foregroundColor' => $theme->foreground_color,
                'primaryColor' => $theme->primary_color,
                'primaryColorHover' => $theme->primary_color_hover,
                'standardColor' => $theme->standard_color,
                'category' => $theme->category_theme,
                'companyId' => $theme->id_company,
                'createdAt' => $theme->created_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du thème.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST - Créer ou mettre à jour un thème (upsert)
     */
    public function upsert(Request $request): JsonResponse
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'background_color' => 'required|string|max:11',
                'primary_color' => 'required|string|max:11',
                'primary_color_hover' => 'required|string|max:11',
                'foreground_color' => 'required|string|max:11',
                'standard_color' => 'required|string|max:11',
                'category_theme' => 'nullable|string|max:50',
                'id_company' => 'nullable|exists:company,id_company',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Champs requis manquant ou invalide.',
                    'errors' => $validator->errors(),
                ], 400);
            }

            $data = $validator->validated();
            $idCompany = $data['id_company'] ?? null;

            // Vérifier si un thème existe déjà pour cette entreprise
            $existingTheme = null;
            if ($idCompany) {
                $existingTheme = ColorTheme::where('id_company', $idCompany)->first();
            }

            if ($existingTheme) {
                // Mise à jour du thème existant
                $existingTheme->update([
                    'name' => $data['name'],
                    'background_color' => $data['background_color'],
                    'primary_color' => $data['primary_color'],
                    'primary_color_hover' => $data['primary_color_hover'],
                    'foreground_color' => $data['foreground_color'],
                    'standard_color' => $data['standard_color'],
                    'category_theme' => $data['category_theme'] ?? null,
                ]);

                $theme = $existingTheme->fresh();
            } else {
                $theme = ColorTheme::create($data);
            }

            // Retourner les données dans le format attendu
            $responseData = [
                'id_color_theme' => $theme->id_color_theme,
                'name' => $theme->name,
                'background_color' => $theme->background_color,
                'primary_color' => $theme->primary_color,
                'primary_color_hover' => $theme->primary_color_hover,
                'foreground_color' => $theme->foreground_color,
                'standard_color' => $theme->standard_color,
                'category_theme' => $theme->category_theme,
                'id_company' => $theme->id_company,
                'created_at' => $theme->created_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de la création ou mise à jour de la palette.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST - Créer un nouveau thème 
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'background_color' => 'required|string|max:11',
                'primary_color' => 'required|string|max:11',
                'primary_color_hover' => 'required|string|max:11',
                'foreground_color' => 'required|string|max:11',
                'standard_color' => 'required|string|max:11',
                'category_theme' => 'nullable|string|max:50',
                'id_company' => 'nullable|exists:company,id_company',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $theme = ColorTheme::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Thème créé avec succès',
                'data' => [
                    'id' => $theme->id_color_theme,
                    'name' => $theme->name,
                    'backgroundColor' => $theme->background_color,
                    'foregroundColor' => $theme->foreground_color,
                    'primaryColor' => $theme->primary_color,
                    'primaryColorHover' => $theme->primary_color_hover,
                    'standardColor' => $theme->standard_color,
                    'category' => $theme->category_theme,
                    'companyId' => $theme->id_company,
                    'createdAt' => $theme->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du thème.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH - Mettre à jour un thème existant
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $theme = ColorTheme::find($id);

            if (!$theme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thème non trouvé.',
                ], 404);
            }

            // Champs autorisés pour la mise à jour
            $allowedFields = [
                'name', 'background_color', 'primary_color', 
                'primary_color_hover', 'foreground_color', 
                'standard_color', 'category_theme', 'id_company'
            ];

            $updateData = [];
            $hasValidFields = false;

            foreach ($allowedFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                    $hasValidFields = true;
                }
            }

            if (!$hasValidFields) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour.',
                ], 400);
            }

            // Validation des données à mettre à jour
            $rules = [
                'name' => 'sometimes|required|string|max:100',
                'background_color' => 'sometimes|required|string|max:11',
                'primary_color' => 'sometimes|required|string|max:11',
                'primary_color_hover' => 'sometimes|required|string|max:11',
                'foreground_color' => 'sometimes|required|string|max:11',
                'standard_color' => 'sometimes|required|string|max:11',
                'category_theme' => 'sometimes|nullable|string|max:50',
                'id_company' => 'sometimes|nullable|exists:company,id_company',
            ];

            $validator = Validator::make($updateData, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Mettre à jour le thème
            $theme->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Thème mis à jour avec succès',
                'data' => [
                    'id' => $theme->id_color_theme,
                    'name' => $theme->name,
                    'backgroundColor' => $theme->background_color,
                    'foregroundColor' => $theme->foreground_color,
                    'primaryColor' => $theme->primary_color,
                    'primaryColorHover' => $theme->primary_color_hover,
                    'standardColor' => $theme->standard_color,
                    'category' => $theme->category_theme,
                    'companyId' => $theme->id_company,
                    'createdAt' => $theme->created_at,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du thème.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE - Supprimer un thème
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $theme = ColorTheme::find($id);

            if (!$theme) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thème non trouvé.',
                ], 404);
            }

            // Vérifier si le thème est utilisé par des entreprises
            $companiesUsingTheme = Company::where('id_color_theme', $id)->count();

            if ($companiesUsingTheme > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Ce thème est utilisé par {$companiesUsingTheme} entreprise(s) et ne peut pas être supprimé.",
                ], 400);
            }

            $theme->delete();

            return response()->json([
                'success' => true,
                'message' => 'Thème supprimé avec succès.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du thème.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}