<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReceivedFileController extends Controller
{
    /**
     * Liste des fichiers reçus (GET)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idCompany = $request->query('id_company');
            $idUser = $request->query('id_basic_user');

            $query = DB::table('received_file as rf')
                ->leftJoin('basic_user as bu', 'bu.id_basic_user', '=', 'rf.id_basic_user')
                ->select(
                    'rf.*',
                    'bu.name as user_name',
                    'bu.first_name as user_first_name',
                    'bu.email as user_email'
                );

            if ($idUser) {
                $query->where('rf.id_basic_user', $idUser);
            } elseif ($idCompany) {
                $query->where('rf.id_company', $idCompany);
            }

            $files = $query->orderByDesc('rf.uploaded_at')->get();

            $data = $files->map(function ($row) {
                return [
                    'id_received_file' => $row->id_received_file,
                    'received_from_name' => $row->received_from_name,
                    'recipient_name' => $row->recipient_name,
                    'courriel_object' => $row->courriel_object,
                    'resume' => $row->resume,
                    'recipient_email' => $row->recipient_email,
                    'status' => $row->status,
                    'send_at' => $row->send_at,
                    'file_url' => $row->file_url,
                    'uploaded_at' => $row->uploaded_at,
                    'is_sent' => (bool) $row->is_sent,
                    'is_archived' => (bool) $row->is_archived,
                    'id_company' => $row->id_company,
                    'id_basic_user' => $row->id_basic_user,
                    'user_name' => $row->user_name,
                    'user_first_name' => $row->user_first_name,
                    'user_email' => $row->user_email,
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
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Création d’un fichier reçu (POST)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $fileUrl = $request->input('file_url');
            $idCompany = $request->input('id_company');

            if (!$fileUrl || !$idCompany) {
                return response()->json([
                    'success' => false,
                    'message' => "file_url et id_company sont obligatoires"
                ], 400);
            }

            $insertedId = DB::table('received_file')->insertGetId([
                'status' => 'not-scanned',
                'file_url' => $fileUrl,
                'uploaded_at' => now(),
                'id_company' => $idCompany,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Fichier uploadé avec succès",
                'insertedId' => $insertedId,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mise à jour d’un fichier reçu (PATCH)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $file = DB::table('received_file')->where('id_received_file', $id)->first();
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }

            $data = $request->only([
                'received_from_name',
                'recipient_name',
                'courriel_object',
                'resume',
                'recipient_email',
                'status',
                'send_at',
                'file_url',
                'uploaded_at',
                'is_sent',
                'is_archived',
                'id_company',
                'id_basic_user'
            ]);

            if (empty($data)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ fourni pour la mise à jour'
                ], 400);
            }

            DB::table('received_file')->where('id_received_file', $id)->update($data);

            $updatedFile = DB::table('received_file as rf')
                ->leftJoin('basic_user as bu', 'bu.id_basic_user', '=', 'rf.id_basic_user')
                ->where('rf.id_received_file', $id)
                ->select(
                    'rf.*',
                    'bu.name as user_name',
                    'bu.first_name as user_first_name',
                    'bu.email as user_email'
                )
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Fichier mis à jour avec succès',
                'data' => $updatedFile,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer un fichier par ID (GET /id)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $row = DB::table('received_file as rf')
                ->join('company as c', 'c.id_company', '=', 'rf.id_company')
                ->leftJoin('basic_user as bu', 'bu.id_basic_user', '=', 'rf.id_basic_user')
                ->where('rf.id_received_file', $id)
                ->select(
                    'rf.*',
                    'c.id_company as companyId',
                    'c.name as companyName',
                    'c.slug as companySlug',
                    'c.address_line as companyAddress',
                    'c.email as companyEmail',
                    'c.phone as companyPhone',
                    'bu.id_basic_user as userId',
                    'bu.first_name as userFirstName',
                    'bu.name as userName',
                    'bu.email as userEmail'
                )
                ->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fichier non trouvé'
                ], 404);
            }

            $file = [
                'id' => $row->id_received_file,
                'receivedFrom' => $row->received_from,
                'courrielObject' => $row->courriel_object,
                'status' => $row->status,
                'sendAt' => $row->send_at,
                'fileUrl' => $row->file_url,
                'uploadedAt' => $row->uploaded_at,
                'isSent' => (bool) $row->is_sent,
                'isArchived' => (bool) $row->is_archived,
                'company' => [
                    'id' => $row->companyId,
                    'name' => $row->companyName,
                    'slug' => $row->companySlug,
                    'address' => $row->companyAddress,
                    'email' => $row->companyEmail,
                    'phone' => $row->companyPhone,
                ],
                'user' => $row->userId ? [
                    'id' => $row->userId,
                    'name' => $row->userName,
                    'firstName' => $row->userFirstName,
                    'email' => $row->userEmail,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $file,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}