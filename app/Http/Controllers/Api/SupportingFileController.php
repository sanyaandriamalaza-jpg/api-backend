<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportingFile;
use App\Models\BasicUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportingFileController extends Controller
{
    /**
     * Récupérer les fichiers de support
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idBasicUser = $request->query('id_basic_user');
            $isValidate = $request->query('is_validate');

            $allFiles = [];

            if ($idBasicUser) {
                // Récupérer l'utilisateur
                $user = BasicUser::find($idBasicUser);

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Utilisateur non trouvé',
                    ], 404);
                }

                // Récupérer les fichiers de cet utilisateur
                $files = $this->fetchSupportingFilesForUser($idBasicUser, $isValidate);

                $stats = $this->buildStats($files);

                return response()->json([
                    'success' => true,
                    'message' => count($files) . ' fichier(s) récupéré(s) pour l\'utilisateur',
                    'data' => [
                        'user' => [
                            'id' => $user->id_basic_user,
                            'name' => $user->name,
                            'firstName' => $user->first_name,
                            'email' => $user->email,
                            'phone' => $user->phone,
                        ],
                        'files' => [
                            'total' => count($files),
                            'stats' => $stats,
                            'documents' => $files,
                        ],
                    ],
                ], 200);

            } else {
                // Récupérer tous les utilisateurs
                $users = BasicUser::all();

                foreach ($users as $user) {
                    $userFiles = $this->fetchSupportingFilesForUser($user->id_basic_user, $isValidate);

                    if (count($userFiles) > 0) {
                        foreach ($userFiles as $file) {
                            $file['user'] = [
                                'id' => $user->id_basic_user,
                                'name' => $user->name,
                                'firstName' => $user->first_name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                            ];
                            $allFiles[] = $file;
                        }
                    }
                }

                // Trier par date décroissante
                usort($allFiles, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });

                $stats = $this->buildStats($allFiles);

                return response()->json([
                    'success' => true,
                    'message' => count($allFiles) . ' fichier(s) récupéré(s) pour tous les utilisateurs',
                    'data' => [
                        'files' => [
                            'total' => count($allFiles),
                            'stats' => $stats,
                            'documents' => $allFiles,
                        ],
                    ],
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un ou plusieurs fichiers de support
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'id_basic_user' => 'required|integer|exists:basic_user,id_basic_user',
                'files' => 'required|array|min:1',
                'files.*.supporting_file_url' => 'required|string',
                'files.*.supporting_file_note' => 'nullable|string|max:215',
                'files.*.id_file_type' => 'nullable|integer|exists:domiciliation_file_type,id_file_type',
            ], [
                'id_basic_user.required' => 'L\'ID de l\'utilisateur est obligatoire',
                'id_basic_user.exists' => 'L\'utilisateur spécifié n\'existe pas',
                'files.required' => 'Au moins un fichier est requis',
                'files.*.supporting_file_url.required' => 'Chaque fichier doit avoir une URL',
                'files.*.supporting_file_note.max' => 'La note ne peut pas dépasser 215 caractères',
                'files.*.id_file_type.exists' => 'Le type de fichier spécifié n\'existe pas',
            ]);

            $results = [];
            $errors = [];
            $filesArray = $request->input('files', []);

            foreach ($filesArray as $index => $fileData) {
                try {
                    $supportingFile = new SupportingFile([
                        'supporting_file_url' => $fileData['supporting_file_url'],
                        'supporting_file_note' => $fileData['supporting_file_note'] ?? null,
                        'id_basic_user' => $request->id_basic_user,
                        'id_file_type' => $fileData['id_file_type'] ?? null,
                        'is_validate' => false,
                    ]);
                    
                    $supportingFile->created_at = now();
                    $supportingFile->save();

                    $results[] = [
                        'index' => $index,
                        'success' => true,
                        'data' => [
                            'id' => $supportingFile->id_supporting_file,
                            'supporting_file_url' => $supportingFile->supporting_file_url,
                            'supporting_file_note' => $supportingFile->supporting_file_note,
                        ],
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $statusCode = count($errors) === 0 ? 201 : 207;

            return response()->json([
                'success' => count($errors) === 0,
                'message' => count($results) . '/' . count($filesArray) . ' fichiers ajoutés',
                'results' => $results,
                'errors' => count($errors) > 0 ? $errors : null,
            ], $statusCode);

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
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Récupérer un fichier de support spécifique
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $file = SupportingFile::with(['basicUser', 'fileType'])->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé',
                ], 404);
            }

            $data = [
                'id' => $file->id_supporting_file,
                'file_type' => $file->fileType ? $file->fileType->label : null,
                'file_url' => $file->supporting_file_url,
                'note' => $file->supporting_file_note,
                'is_validate' => $file->is_validate,
                'validate_at' => $file->validate_at,
                'created_at' => $file->created_at,
                'id_basic_user' => $file->id_basic_user,
                'file_type_label' => $file->fileType ? $file->fileType->label : null,
                'user' => $file->basicUser ? [
                    'id' => $file->basicUser->id_basic_user,
                    'name' => $file->basicUser->name,
                    'firstName' => $file->basicUser->first_name,
                    'email' => $file->basicUser->email,
                    'phone' => $file->basicUser->phone,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Fichier récupéré avec succès',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne du serveur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un fichier de support
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $file = SupportingFile::find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé',
                ], 404);
            }

            $request->validate([
                'supporting_file_note' => 'nullable|string|max:215',
                'is_validate' => 'nullable|boolean',
            ], [
                'supporting_file_note.max' => 'La note ne peut pas dépasser 215 caractères',
            ]);

            if (!$request->has('supporting_file_note') && !$request->has('is_validate')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour (note ou is_validate requis)',
                ], 400);
            }

            $updateData = [];

            if ($request->has('supporting_file_note')) {
                $updateData['supporting_file_note'] = $request->supporting_file_note;
            }

            if ($request->has('is_validate')) {
                $updateData['is_validate'] = $request->is_validate;
                
                if ($request->is_validate === true) {
                    $updateData['validate_at'] = now();
                } else {
                    $updateData['validate_at'] = null;
                }
            }

            $file->update($updateData);
            $file->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Fichier mis à jour avec succès',
                'data' => [
                    'id' => $file->id_supporting_file,
                    'supporting_file_note' => $file->supporting_file_note,
                    'is_validate' => $file->is_validate,
                    'validate_at' => $file->validate_at,
                    'created_at' => $file->created_at,
                ],
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
     * Récupérer les fichiers pour un utilisateur
     */
    private function fetchSupportingFilesForUser($idBasicUser, $isValidate)
    {
        $query = SupportingFile::with('fileType')
            ->where('id_basic_user', $idBasicUser);

        if ($isValidate !== null) {
            $query->where('is_validate', $isValidate === 'true' ? 1 : 0);
        }

        $files = $query->orderBy('created_at', 'desc')->get();

        return $files->map(function ($file) {
            return [
                'id' => $file->id_supporting_file,
                'file_url' => $file->supporting_file_url,
                'note' => $file->supporting_file_note,
                'is_validate' => $file->is_validate,
                'validate_at' => $file->validate_at,
                'created_at' => $file->created_at,
                'id_file_type' => $file->id_file_type,
                'idFileType' => $file->id_file_type,
                'file_type' => $file->fileType ? $file->fileType->label : null,
                'file_type_label' => $file->fileType ? $file->fileType->description : null,
            ];
        })->toArray();
    }

    /**
     * Construire les statistiques
     */
    private function buildStats($files)
    {
        $total = count($files);
        $validated = 0;
        $pending = 0;

        foreach ($files as $file) {
            if (isset($file['is_validate']) && $file['is_validate']) {
                $validated++;
            } else {
                $pending++;
            }
        }

        return [
            'total' => $total,
            'validated' => $validated,
            'pending' => $pending,
        ];
    }
}
