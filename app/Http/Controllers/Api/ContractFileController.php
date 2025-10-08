<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContractFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContractFileController extends Controller
{
    /**
     * Récupérer les contract files
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idCompany = $request->query('id_company');
            $idUser = $request->query('id_basic_user');

            // Query de base avec Eloquent
            $query = ContractFile::with(['company', 'basicUser']);

            // Filtrer selon les paramètres
            if ($idUser) {
                $query->where('id_basic_user', $idUser);
            } elseif ($idCompany) {
                $query->where('id_company', $idCompany);
            }

            // Récupérer les fichiers triés par date
            $contractFiles = $query->orderBy('created_at', 'desc')->get();

            // Préparer les données de réponse
            $data = $contractFiles->map(function ($file) {
                // Gérer created_at qui peut être string ou Carbon
                $createdAt = null;
                if ($file->created_at) {
                    if (is_string($file->created_at)) {
                        $createdAt = \Carbon\Carbon::parse($file->created_at)->toISOString();
                    } else {
                        $createdAt = $file->created_at->toISOString();
                    }
                }

                return [
                    'contractFileId' => $file->id_contract_file,
                    'url' => $file->tag === 'contract' 
                        ? $file->contract_file_url 
                        : $file->compensatory_file_url,
                    'createdAt' => $createdAt,
                    'contractFileTag' => $file->tag,
                    'isContractSignedByUser' => (bool) $file->is_signedBy_user,
                    'isContractSignedByAdmin' => (bool) $file->is_signedBy_admin,
                    'procedureId' => $file->yousign_procedure_id,
                    'company' => $file->company ? [
                        'id' => $file->company->id_company,
                        'name' => $file->company->name,
                        'slug' => $file->company->slug,
                    ] : null,
                    'user' => $file->basicUser ? [
                        'id' => $file->basicUser->id_basic_user,
                        'name' => $file->basicUser->name,
                        'firstName' => $file->basicUser->first_name,
                        'email' => $file->basicUser->email,
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
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau contract file
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'url' => 'required|string',
                'tag' => 'required|in:contract,compensatory',
                'is_signedBy_user' => 'nullable|boolean',
                'is_signedBy_admin' => 'nullable|boolean',
                'id_basic_user' => 'nullable|integer|exists:basic_user,id_basic_user',
                'id_company' => 'required|integer|exists:company,id_company',
            ], [
                'url.required' => "L'URL est obligatoire",
                'tag.required' => "Le champ 'tag' est obligatoire",
                'tag.in' => "Tag invalide. Utilisez 'contract' ou 'compensatory'",
                'id_company.required' => "Le champ 'id_company' est obligatoire",
                'id_company.exists' => "La société spécifiée n'existe pas",
                'id_basic_user.exists' => "L'utilisateur spécifié n'existe pas",
            ]);

            // Préparer les données selon le tag
            $contractData = [
                'tag' => $request->tag,
                'is_signedBy_user' => $request->is_signedBy_user ?? false,
                'is_signedBy_admin' => $request->is_signedBy_admin ?? false,
                'id_basic_user' => $request->id_basic_user ?? null,
                'id_company' => $request->id_company,
            ];

            if ($request->tag === 'contract') {
                $contractData['contract_file_url'] = $request->url;
            } else {
                $contractData['compensatory_file_url'] = $request->url;
            }

            // Créer le contract file avec created_at via DB::raw ou en utilisant le modèle
            $contractFile = new ContractFile($contractData);
            $contractFile->created_at = now();
            $contractFile->save();

            return response()->json([
                'success' => true,
                'message' => 'Contract file créé avec succès',
                'insertedId' => $contractFile->id_contract_file,
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
                'message' => 'Erreur lors de la création du contract file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer un contract file spécifique
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $contractFile = ContractFile::with(['company', 'basicUser'])->find($id);

            if (!$contractFile) {
                return response()->json([
                    'success' => false,
                    'error' => 'Contrat non trouvé',
                ], 404);
            }

            $data = [
                'contractFileId' => $contractFile->id_contract_file,
                'url' => $contractFile->tag === 'contract' 
                    ? $contractFile->contract_file_url 
                    : $contractFile->compensatory_file_url,
                'createdAt' => $contractFile->created_at ? $contractFile->created_at->toISOString() : null,
                'contractFileTag' => $contractFile->tag,
                'isContractSignedByUser' => (bool) $contractFile->is_signedBy_user,
                'isContractSignedByAdmin' => (bool) $contractFile->is_signedBy_admin,
                'procedureId' => $contractFile->yousign_procedure_id,
                'signedUrl' => $contractFile->signed_file_url,
                'company' => [
                    'id' => $contractFile->company->id_company,
                    'name' => $contractFile->company->name,
                    'slug' => $contractFile->company->slug,
                ],
                'user' => $contractFile->basicUser ? [
                    'id' => $contractFile->basicUser->id_basic_user,
                    'name' => $contractFile->basicUser->name,
                    'firstName' => $contractFile->basicUser->first_name,
                    'email' => $contractFile->basicUser->email,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un contract file
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $contractFile = ContractFile::with(['company', 'basicUser'])->find($id);

            if (!$contractFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé',
                ], 404);
            }

            $request->validate([
                'is_signedBy_user' => 'nullable|boolean',
                'is_signedBy_admin' => 'nullable|boolean',
            ], [
                'is_signedBy_user.boolean' => "Le champ 'is_signedBy_user' doit être un booléen",
                'is_signedBy_admin.boolean' => "Le champ 'is_signedBy_admin' doit être un booléen",
            ]);

            // Vérifier qu'au moins un champ est fourni
            if (!$request->has('is_signedBy_user') && !$request->has('is_signedBy_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour (is_signedBy_user ou is_signedBy_admin requis)',
                ], 400);
            }

            // Construction des champs à mettre à jour
            $updateData = [];

            if ($request->has('is_signedBy_admin')) {
                $updateData['is_signedBy_admin'] = $request->is_signedBy_admin;
            }

            if ($request->has('is_signedBy_user')) {
                $updateData['is_signedBy_user'] = $request->is_signedBy_user;
            }

            $contractFile->update($updateData);

            // Préparer la réponse avec le fichier mis à jour
            $data = [
                'contractFileId' => $contractFile->id_contract_file,
                'url' => $contractFile->tag === 'contract' 
                    ? $contractFile->contract_file_url 
                    : $contractFile->compensatory_file_url,
                'createdAt' => $contractFile->created_at ? $contractFile->created_at->toISOString() : null,
                'contractFileTag' => $contractFile->tag,
                'isContractSignedByUser' => (bool) $contractFile->is_signedBy_user,
                'isContractSignedByAdmin' => (bool) $contractFile->is_signedBy_admin,
                'procedureId' => $contractFile->yousign_procedure_id,
                'signedUrl' => $contractFile->signed_file_url,
                'company' => [
                    'id' => $contractFile->company->id_company,
                    'name' => $contractFile->company->name,
                    'slug' => $contractFile->company->slug,
                ],
                'user' => $contractFile->basicUser ? [
                    'id' => $contractFile->basicUser->id_basic_user,
                    'name' => $contractFile->basicUser->name,
                    'firstName' => $contractFile->basicUser->first_name,
                    'email' => $contractFile->basicUser->email,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fichier mis à jour avec succès',
                'data' => $data,
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
                'message' => 'Erreur lors de la mise à jour du fichier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}