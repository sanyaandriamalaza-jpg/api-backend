<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoryFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryFileController extends Controller
{
    /**
     * Récupérer les catégories de fichiers avec leurs labels
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $categoryName = $request->query('category_name');

            // Query de base avec Eloquent
            $query = CategoryFile::query();

            // Filtrer par nom de catégorie si fourni
            if ($categoryName) {
                $query->where('category_name', $categoryName);
            }

            // Récupérer les catégories
            $categories = $query->get();

            // Préparer les données de réponse
            $data = $categories->map(function ($category) {
                // Décoder les fichiers de catégorie (JSON)
                $categoryFiles = [];
                if ($category->category_files) {
                    $categoryFiles = json_decode($category->category_files, true) ?? [];
                }

                return [
                    'id' => $category->id_category_file,
                    'categoryName' => $category->category_name,
                    'categoryDescription' => $category->category_description,
                    'categoryFiles' => $categoryFiles,
                ];
            });

            // Format de réponse selon le filtre
            if ($categoryName) {
                $result = $data->first();
                return response()->json([
                    'success' => true,
                    'count' => $result ? 1 : 0,
                    'data' => $result,
                ], 200);
            }

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
     * Récupérer une catégorie spécifique par ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = CategoryFile::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.',
                ], 404);
            }

            // Décoder les fichiers de catégorie
            $categoryFiles = [];
            if ($category->category_files) {
                $categoryFiles = json_decode($category->category_files, true) ?? [];
            }

            $data = [
                'id' => $category->id_category_file,
                'categoryName' => $category->category_name,
                'categoryDescription' => $category->category_description,
                'categoryFiles' => $categoryFiles,
            ];

            return response()->json([
                'success' => true,
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
     * Créer une nouvelle catégorie de fichier
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category_name' => 'required|string|max:170',
                'category_description' => 'nullable|string|max:200',
                'category_files' => 'nullable|array',
            ]);

            $categoryFile = CategoryFile::create([
                'category_name' => $request->category_name,
                'category_description' => $request->category_description,
                'category_files' => $request->category_files ? json_encode($request->category_files) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès',
                'data' => [
                    'id' => $categoryFile->id_category_file,
                    'categoryName' => $categoryFile->category_name,
                    'categoryDescription' => $categoryFile->category_description,
                    'categoryFiles' => $request->category_files ?? [],
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données de validation invalides',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une catégorie
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $category = CategoryFile::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.',
                ], 404);
            }

            $request->validate([
                'category_name' => 'sometimes|required|string|max:170',
                'category_description' => 'nullable|string|max:200',
                'category_files' => 'nullable|array',
            ]);

            $updateData = [];
            
            if ($request->has('category_name')) {
                $updateData['category_name'] = $request->category_name;
            }
            
            if ($request->has('category_description')) {
                $updateData['category_description'] = $request->category_description;
            }
            
            if ($request->has('category_files')) {
                $updateData['category_files'] = json_encode($request->category_files);
            }

            $category->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie mise à jour avec succès',
                'data' => [
                    'id' => $category->id_category_file,
                    'categoryName' => $category->category_name,
                    'categoryDescription' => $category->category_description,
                    'categoryFiles' => json_decode($category->category_files, true) ?? [],
                ]
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
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une catégorie
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = CategoryFile::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.',
                ], 404);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}