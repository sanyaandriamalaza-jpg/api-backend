<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class DocumentAnalysisController extends Controller
{
    private $openaiApiKey;
    private $openaiApiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
    }

    /**
     * Analyser le contenu d'un document avec OpenAI
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function analyze(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'text' => 'required|string',
            ], [
                'text.required' => 'Le texte est obligatoire',
            ]);

            $text = trim($request->input('text'));

            if (empty($text)) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'error' => 'Aucun texte disponible pour être analysé',
                ], 400);
            }

            if (!$this->openaiApiKey) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'error' => 'L\'API OpenAI n\'est pas configurée',
                ], 500);
            }

            // Construire le prompt pour l'analyse
            $prompt = "Tu es un assistant spécialisé pour une entreprise de service de domiciliation. 
Analyse ce courrier et extrait les informations suivantes strictement au format JSON :

{
  \"expediteur\": null,
  \"destinataire\": null,
  \"email\": null,
  \"objet\": null,
  \"resume\": null
}

- expediteur : nom ou entité qui a envoyé le courrier
- destinataire : nom ou entité à qui le courrier est adressé
- email : adresse email à qui le courrier est adressé
- objet : sujet ou motif principal du courrier
- resume : résumé clair, concis et lisible du contenu

Ne mets **aucune phrase supplémentaire** en dehors du JSON, et si tu ne trouves pas les valeurs de chaque clé, mets simplement null.

Texte du courrier :
{$text}
";

            // Appeler OpenAI
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->openaiApiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0,
                'max_tokens' => 500,
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                throw new \Exception(
                    'Erreur OpenAI: ' . ($errorData['error']['message'] ?? 'Erreur inconnue')
                );
            }

            $data = $response->json();
            $contentRaw = $data['choices'][0]['message']['content'] ?? '';

            // Initialiser la structure de réponse
            $analysis = [
                'expediteur' => null,
                'destinataire' => null,
                'email' => null,
                'objet' => null,
                'resume' => null,
            ];
            $success = false;

            // Essayer de parser le JSON
            try {
                // Nettoyer la réponse (enlever les balises markdown si présentes)
                $cleanedString = preg_replace('/```json\s*|```/', '', $contentRaw);
                $cleanedString = trim($cleanedString);
                
                $parsedData = json_decode($cleanedString, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($parsedData)) {
                    $analysis = array_merge($analysis, $parsedData);
                    $success = true;
                } else {
                    // Si le parsing échoue, garder la réponse brute
                    $analysis['raw'] = $contentRaw;
                }
            } catch (\Exception $e) {
                // En cas d'erreur, garder la réponse brute
                $analysis['raw'] = $contentRaw;
            }

            return response()->json([
                'success' => $success,
                'data' => $analysis,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'error' => 'Validation échouée',
                'errors' => $e->errors(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}