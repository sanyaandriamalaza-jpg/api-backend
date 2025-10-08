<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VirtualOffice;
use App\Models\Invoice;
use App\Models\SupportingFile;
use App\Models\ContractFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VirtualOfficeController extends Controller
{
    /**
     * Récupérer les virtual offices avec leurs données complètes
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $idBasicUser = $request->query('id_basic_user');
            $isValidate = $request->query('is_validate');
            $companyName = $request->query('companyName');
            $idVirtualOfficeOffer = $request->query('idVirtualOfficeOffer');

            // Requête pour récupérer les invoices avec toutes les relations
            $query = Invoice::with([
                'virtualOfficeOffer',
                'basicUser.virtualOffice'
            ]);

            if ($idBasicUser) {
                $query->where('id_basic_user', $idBasicUser);
            }

            if ($companyName) {
                $query->whereHas('basicUser.virtualOffice', function ($q) use ($companyName) {
                    $q->where('virtual_office_name', $companyName);
                });
            }

            if ($idVirtualOfficeOffer) {
                $query->where('id_virtual_office_offer', $idVirtualOfficeOffer);
            } else {
                $query->whereNotNull('id_virtual_office_offer');
            }

            $invoices = $query->orderBy('created_at', 'desc')->get();

            $virtualOffices = $invoices->map(function ($invoice) use ($isValidate) {
                $user = $invoice->basicUser;
                $virtualOffice = $user ? $user->virtualOffice : null;

                // Récupérer les fichiers de support
                $userFiles = $this->fetchFilesForUser($invoice->id_basic_user, $isValidate);
                $stats = $this->buildStats($userFiles);

                // Récupérer les contract files à signer
                $contractFilesToSign = $this->fetchContractFilesToSign($invoice->id_basic_user);

                // Récupérer tous les contract files
                $allContractFiles = $this->fetchAllContractFiles($invoice->id_basic_user);

                return [
                    'id' => $invoice->id_invoice,
                    'user' => $user ? [
                        'userId' => $user->id_basic_user,
                        'name' => $user->name,
                        'firstName' => $user->first_name,
                        'email' => $user->email,
                        'role' => 'Gérant(e)',
                    ] : null,
                    'amount' => $invoice->amount,
                    'status' => $invoice->subscription_status,
                    'idBasicUser' => $invoice->id_basic_user,
                    'idVirtualOfficeOffer' => $invoice->id_virtual_office_offer,
                    'company' => $virtualOffice ? [
                        'idCompany' => $virtualOffice->id_virtual_office,
                        'companyName' => $virtualOffice->virtual_office_name,
                        'legalForm' => $virtualOffice->virtual_office_legal_form,
                        'siret' => $virtualOffice->virtual_office_siret,
                    ] : null,
                    'userFiles' => [
                        'total' => count($userFiles),
                        'stats' => $stats,
                        'documents' => $userFiles,
                    ],
                    'contractFileToSign' => $contractFilesToSign,
                    'contractFiles' => $allContractFiles,
                    'virtualOfficeOffer' => $invoice->virtualOfficeOffer ? [
                        'offerId' => $invoice->virtualOfficeOffer->id_virtual_office_offer,
                        'offerName' => $invoice->virtualOfficeOffer->name,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $virtualOffices->count(),
                'data' => $virtualOffices,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur lors de la récupération des virtual offices.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau virtual office
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'virtual_office_name' => 'required|string|max:255',
                'virtual_office_legal_form' => 'nullable|string|max:100',
                'virtual_office_siret' => 'nullable|string|max:14',
                'virtual_office_siren' => 'nullable|string|max:9',
                'virtual_office_rcs' => 'nullable|string|max:100',
                'virtual_office_tva' => 'nullable|string|max:50',
                'id_category_file' => 'nullable|integer|exists:category_file,id_category_file',
                'id_basic_user' => 'nullable|integer|exists:basic_user,id_basic_user',
            ], [
                'virtual_office_name.required' => 'Le nom du Virtual Office est obligatoire',
            ]);

            $virtualOffice = VirtualOffice::create([
                'virtual_office_name' => $request->virtual_office_name,
                'virtual_office_legal_form' => $request->virtual_office_legal_form,
                'virtual_office_siret' => $request->virtual_office_siret,
                'virtual_office_siren' => $request->virtual_office_siren,
                'virtual_office_rcs' => $request->virtual_office_rcs,
                'virtual_office_tva' => $request->virtual_office_tva,
                'id_category_file' => $request->id_category_file,
                'id_basic_user' => $request->id_basic_user,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Virtual Office créé avec succès',
                'insertedId' => $virtualOffice->id_virtual_office,
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
                'message' => 'Erreur interne lors de la création du Virtual Office.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un virtual office
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $virtualOffice = VirtualOffice::find($id);

            if (!$virtualOffice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Virtual Office non trouvé',
                ], 404);
            }

            $request->validate([
                'virtual_office_name' => 'sometimes|required|string|max:255',
                'virtual_office_legal_form' => 'nullable|string|max:100',
                'virtual_office_siret' => 'nullable|string|max:14',
                'virtual_office_siren' => 'nullable|string|max:9',
                'virtual_office_rcs' => 'nullable|string|max:100',
                'virtual_office_tva' => 'nullable|string|max:50',
                'is_activate' => 'nullable|boolean',
                'id_category_file' => 'nullable|integer|exists:category_file,id_category_file',
                'id_basic_user' => 'nullable|integer|exists:basic_user,id_basic_user',
            ]);

            $updateData = [];

            if ($request->has('virtual_office_name')) {
                $updateData['virtual_office_name'] = $request->virtual_office_name;
            }
            if ($request->has('virtual_office_legal_form')) {
                $updateData['virtual_office_legal_form'] = $request->virtual_office_legal_form;
            }
            if ($request->has('virtual_office_siret')) {
                $updateData['virtual_office_siret'] = $request->virtual_office_siret;
            }
            if ($request->has('virtual_office_siren')) {
                $updateData['virtual_office_siren'] = $request->virtual_office_siren;
            }
            if ($request->has('virtual_office_rcs')) {
                $updateData['virtual_office_rcs'] = $request->virtual_office_rcs;
            }
            if ($request->has('virtual_office_tva')) {
                $updateData['virtual_office_tva'] = $request->virtual_office_tva;
            }
            if ($request->has('is_activate')) {
                $updateData['is_activate'] = $request->is_activate;
            }
            if ($request->has('id_category_file')) {
                $updateData['id_category_file'] = $request->id_category_file;
            }
            if ($request->has('id_basic_user')) {
                $updateData['id_basic_user'] = $request->id_basic_user;
            }

            if (empty($updateData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun champ à mettre à jour',
                ], 400);
            }

            $virtualOffice->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Virtual Office mis à jour avec succès',
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

    /**
     * Méthodes privées pour récupérer les fichiers
     */
    private function fetchFilesForUser($idBasicUser, $isValidate)
    {
        $query = SupportingFile::with(['fileType.categoryFile'])
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
                'is_validate' => $file->is_validate ? 1 : 0,
                'validate_at' => $file->validate_at,
                'created_at' => $file->created_at,
                'id_file_type' => $file->id_file_type,
                'idFileType' => $file->id_file_type,
                'file_type' => $file->fileType ? $file->fileType->label : null,
                'file_type_label' => $file->fileType ? $file->fileType->description : null,
                'id_category_file' => $file->fileType ? $file->fileType->id_category_file : null,
                'categoryName' => $file->fileType && $file->fileType->categoryFile 
                    ? $file->fileType->categoryFile->category_name : null,
                'labelId' => $file->fileType && $file->fileType->categoryFile 
                    ? $file->fileType->categoryFile->label_id : null,
                'idCategory' => $file->fileType ? $file->fileType->id_category_file : null,
            ];
        })->toArray();
    }

    private function fetchContractFilesToSign($idBasicUser)
    {
        $files = ContractFile::where('id_basic_user', $idBasicUser)
            ->where('is_signedBy_user', true)
            ->where('is_signedBy_admin', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return $files->map(function ($file) {
            return [
                'contractFileId' => $file->id_contract_file,
                'contractFileUrl' => $file->contract_file_url,
                'compensatoryFileUrl' => $file->compensatory_file_url,
                'contractFileTag' => $file->tag,
                'isContractSignedByUser' => $file->is_signedBy_user,
                'isContractSignedByAdmin' => $file->is_signedBy_admin,
            ];
        })->toArray();
    }

    private function fetchAllContractFiles($idBasicUser)
    {
        $files = ContractFile::where('id_basic_user', $idBasicUser)
            ->orderBy('created_at', 'desc')
            ->get();

        return $files->map(function ($file) {
            return [
                'contractFileId' => $file->id_contract_file,
                'contractFileUrl' => $file->contract_file_url,
                'compensatoryFileUrl' => $file->compensatory_file_url,
                'contractFileTag' => $file->tag,
                'isContractSignedByUser' => $file->is_signedBy_user,
                'isContractSignedByAdmin' => $file->is_signedBy_admin,
            ];
        })->toArray();
    }

    private function buildStats($files)
    {
        $total = count($files);
        $validated = 0;
        $pending = 0;
        $rejected = 0;

        foreach ($files as $file) {
            if (isset($file['is_validate'])) {
                if ($file['is_validate'] === 1) {
                    $validated++;
                } elseif ($file['is_validate'] === 0) {
                    $rejected++;
                } else {
                    $pending++;
                }
            } else {
                $pending++;
            }
        }

        return [
            'total' => $total,
            'validated' => $validated,
            'pending' => $pending,
            'rejected' => $rejected,
        ];
    }
}
