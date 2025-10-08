<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LatestInvoiceController extends Controller
{
    /**
     * Récupérer la dernière facture d'une entreprise et le prochain numéro de référence
     * 
     * @param int $companyId
     * @return JsonResponse
     */
    public function show(int $companyId): JsonResponse
    {
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'error' => 'Missing companyId',
            ], 400);
        }

        try {
            $query = "
                SELECT 
                    i.reference_num, 
                    c.invoice_office_ref AS invoiceOfficeRef, 
                    c.invoice_virtual_office_ref AS invoiceVirtualOfficeRef
                FROM invoice i
                LEFT JOIN virtual_office_offer v ON i.id_virtual_office_offer = v.id_virtual_office_offer
                INNER JOIN company c ON c.id_company = v.id_company
                WHERE c.id_company = ?
                ORDER BY i.created_at DESC
                LIMIT 1
            ";

            $result = DB::select($query, [$companyId]);

            // Si aucune facture n'existe pour cette entreprise
            if (empty($result)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'referenceNum' => null,
                        'nextReferenceNum' => 1,
                        'invoiceOfficeRef' => null,
                        'invoiceVirtualOfficeRef' => null,
                    ],
                ]);
            }

            $latestInvoice = $result[0];
            $latestReferenceNum = $latestInvoice->reference_num;
            $nextReferenceNum = $this->incrementReferenceNum($latestReferenceNum);

            return response()->json([
                'success' => true,
                'data' => [
                    'referenceNum' => $latestReferenceNum,
                    'nextReferenceNum' => $nextReferenceNum,
                    'invoiceOfficeRef' => $latestInvoice->invoiceOfficeRef ?? null,
                    'invoiceVirtualOfficeRef' => $latestInvoice->invoiceVirtualOfficeRef ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Incrémenter le numéro de référence
     * 
     * @param int $referenceNum
     * @return int
     */
    private function incrementReferenceNum(int $referenceNum): int
    {
        return $referenceNum + 1;
    }
}