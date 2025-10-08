<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomiciliationFileType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomiciliationFileTypeController extends Controller
{
    /**
     * Récupérer les types de fichiers de domiciliation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idCompany = $request->query('id_company');
            $idCategory = $request->query('id_category_file');

            // Query de base avec Eloquent
            $query = DomiciliationFileType::with('categoryFile');

            // Filtrer selon les paramètres
            if ($idCompany) {
                $query->where('id_company', $idCompany);
            }

            if ($idCategory) {
                $query->where('id_category_file', $idCategory);
            }

            // Récupérer les types de fichiers triés par date
            $fileTypes = $query->orderBy('created_at', 'desc')->get();

            // Préparer les données de réponse
            $data = $fileTypes->map(function ($fileType) {
                return [
                    'id' => $fileType->id_file_type,
                    'file_type_label' => $fileType->label,
                    'file_description' => $fileType->description,
                    'created_at' => $fileType->created_at,
                    'is_archived' => $fileType->is_archived === '1',
                    'idCategoryType' => $fileType->id_category_file,
                    'idCompany' => $fileType->id_company,
                    'categoryType' => $fileType->categoryFile ? [
                        'id' => $fileType->categoryFile->id_category_file,
                        'categoryName' => $fileType->categoryFile->category_name,
                        'categoryDescription' => $fileType->categoryFile->category_description,
                        'labelId' => $fileType->categoryFile->label_id ?? null,
                        'labelDescription' => $fileType->categoryFile->label_description ?? null,
                    ] : null,
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
     * Créer un nouveau type de fichier de domiciliation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'label' => 'required|string|max:255',
                'description' => 'nullable|string',
                'id_category_file' => 'required|integer|exists:category_file,id_category_file',
                'id_company' => 'required|integer|exists:company,id_company',
            ], [
                'label.required' => 'Le label est obligatoire',
                'id_category_file.required' => 'La catégorie du fichier est obligatoire',
                'id_category_file.exists' => 'La catégorie spécifiée n\'existe pas',
                'id_company.required' => 'L\'entreprise associée est obligatoire',
                'id_company.exists' => 'L\'entreprise spécifiée n\'existe pas',
            ]);

            // Créer le type de fichier
            $fileType = new DomiciliationFileType([
                'label' => $request->label,
                'description' => $request->description,
                'is_archived' => '0',
                'id_category_file' => $request->id_category_file,
                'id_company' => $request->id_company,
            ]);
            
            $fileType->created_at = now();
            $fileType->save();

            return response()->json([
                'success' => true,
                'message' => 'Type de fichier créé avec succès',
                'insertedId' => $fileType->id_file_type,
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
                'message' => 'Erreur interne lors de la création du type de fichier.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un type de fichier spécifique
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $fileType = DomiciliationFileType::with('categoryFile')->find($id);

            if (!$fileType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de fichier non trouvé',
                ], 404);
            }

            $data = [
                'id' => $fileType->id_file_type,
                'file_type_label' => $fileType->label,
                'file_description' => $fileType->description,
                'created_at' => $fileType->created_at,
                'is_archived' => $fileType->is_archived === '1',
                'idCategoryType' => $fileType->id_category_file,
                'idCompany' => $fileType->id_company,
                'categoryType' => $fileType->categoryFile ? [
                    'id' => $fileType->categoryFile->id_category_file,
                    'categoryName' => $fileType->categoryFile->category_name,
                    'categoryDescription' => $fileType->categoryFile->category_description,
                    'labelId' => $fileType->categoryFile->label_id ?? null,
                    'labelDescription' => $fileType->categoryFile->label_description ?? null,
                ] : null,
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
     * Mettre à jour un type de fichier
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $fileType = DomiciliationFileType::find($id);

            if (!$fileType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de fichier non trouvé',
                ], 404);
            }

            $request->validate([
                'label' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'is_archived' => 'nullable|boolean',
                'id_category_file' => 'sometimes|required|integer|exists:category_file,id_category_file',
                'id_company' => 'sometimes|required|integer|exists:company,id_company',
            ], [
                'label.required' => 'Le label est obligatoire',
                'id_category_file.exists' => 'La catégorie spécifiée n\'existe pas',
                'id_company.exists' => 'L\'entreprise spécifiée n\'existe pas',
            ]);

            // Construction des champs à mettre à jour
            $updateData = [];

            if ($request->has('label')) {
                $updateData['label'] = $request->label;
            }

            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }

            if ($request->has('is_archived')) {
                $updateData['is_archived'] = $request->is_archived ? '1' : '0';
            }

            if ($request->has('id_category_file')) {
                $updateData['id_category_file'] = $request->id_category_file;
            }

            if ($request->has('id_company')) {
                $updateData['id_company'] = $request->id_company;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour',
                ], 400);
            }

            $fileType->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Type de fichier mis à jour avec succès',
                'affectedRows' => 1,
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
}
