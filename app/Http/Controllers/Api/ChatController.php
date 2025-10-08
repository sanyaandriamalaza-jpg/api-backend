<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private $openaiApiKey;
    private $openaiApiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
    }

    /**
     * Traiter un message de chat avec RAG
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'message' => 'required|string',
                'companyData' => 'required|array',
            ], [
                'message.required' => 'Le message est obligatoire',
                'companyData.required' => 'Les données de l\'entreprise sont obligatoires',
            ]);

            $message = $request->input('message');
            $companyData = $request->input('companyData');

            if (!$this->openaiApiKey) {
                return response()->json([
                    'reply' => 'L\'assistant IA n\'est pas configuré. Clé API manquante.',
                ], 500);
            }

            // Construire le contexte à partir des données de l'entreprise
            $context = $this->buildContextFromCompanyData($companyData);
            $companyName = $companyData['entreprise']['nom'] ?? 'l\'entreprise';

            // Appeler OpenAI avec le contexte
            $aiReply = $this->callAIWithRAG($message, $context, $companyName);

            return response()->json([
                'reply' => $aiReply,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'reply' => 'Message invalide.',
                'errors' => $e->errors(),
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Erreur serveur. Veuillez réessayer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Appeler OpenAI avec RAG (Retrieval Augmented Generation)
     * 
     * @param string $message
     * @param string $context
     * @param string $companyName
     * @return string
     */
    private function callAIWithRAG(string $message, string $context, string $companyName): string
    {
        $systemPrompt = "Tu es l'assistant virtuel de {$companyName}.

CONTEXTE:
{$context}

Instructions importantes :
- Réponds en français de manière professionnelle et concise
- Utilise UNIQUEMENT les informations fournies dans le contexte ci-dessus
- Si tu n'as pas l'information, dis-le clairement
- Structure ta réponse avec du HTML simple (par ex. <p>, <ul>, <li>, <strong>)
- N'utilise pas de styles inline, de background, de couleurs ou de CSS
- Pas de <div>, <span> avec attributs, ni de mise en page complexe
- Utilise uniquement du HTML minimal pour améliorer la lisibilité
- Exemple attendu :
  <p>Voici nos services :</p>
  <ul>
    <li>Bureau virtuel</li>
    <li>Gestion du courrier</li>
  </ul>
";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->openaiApiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ]);

            if (!$response->successful()) {
                $errorData = $response->json();
                throw new \Exception(
                    'Erreur OpenAI: ' . ($errorData['error']['message'] ?? 'Erreur inconnue')
                );
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'] ?? 
                   'Désolé, je n\'ai pas pu générer une réponse.';

        } catch (\Exception $e) {
            throw new \Exception('Erreur lors de l\'appel à OpenAI: ' . $e->getMessage());
        }
    }

    /**
     * Construire le contexte à partir des données de l'entreprise
     * 
     * @param array $companyData
     * @return string
     */
    private function buildContextFromCompanyData(array $companyData): string
    {
        $entreprise = $companyData['entreprise'] ?? [];
        $domiciliation = $companyData['domiciliation'] ?? [];
        // $espacesCoworking = $companyData['espaces_coworking'] ?? [];

        $context = "INFORMATIONS ENTREPRISE:\n- Nom: " . ($entreprise['nom'] ?? '');

        if (!empty($entreprise['description'])) {
            $context .= "\n- Description: " . $entreprise['description'];
        }
        if (!empty($entreprise['telephone'])) {
            $context .= "\n- Téléphone: " . $entreprise['telephone'];
        }
        if (!empty($entreprise['email'])) {
            $context .= "\n- Email: " . $entreprise['email'];
        }

        // Adresse
        if (!empty($entreprise['adresse'])) {
            $adresseParts = array_filter([
                $entreprise['adresse']['ligne_adresse'] ?? null,
                $entreprise['adresse']['code_postal'] ?? null,
                $entreprise['adresse']['ville'] ?? null,
                $entreprise['adresse']['region'] ?? null,
                $entreprise['adresse']['pays'] ?? null,
            ]);
            $adresse = implode(', ', $adresseParts);
            if ($adresse) {
                $context .= "\n- Adresse: " . $adresse;
            }
        }

        // // Horaires d'ouverture
        // if (!empty($entreprise['horaires_ouverture'])) {
        //     $context .= "\n\nHORAIRES D'OUVERTURE:\n" . 
        //                json_encode($entreprise['horaires_ouverture'], JSON_PRETTY_PRINT);
        // }

        // Services actifs
        if (!empty($entreprise['services_actifs'])) {
            $servicesActifs = [];
            foreach ($entreprise['services_actifs'] as $service => $actif) {
                if ($actif) {
                    $servicesActifs[] = "- " . $service;
                }
            }
            if (!empty($servicesActifs)) {
                $context .= "\n\nSERVICES ACTIFS:\n" . implode("\n", $servicesActifs);
            }
        }

        // Domiciliation
        if (!empty($domiciliation['offres_bureau_virtuel'])) {
            $context .= "\n\nDOMICILIATION:\n";
            foreach ($domiciliation['offres_bureau_virtuel'] as $offre) {
                $context .= "- {$offre['nom']}: {$offre['description']} ({$offre['prix_mensuel']}€/mois)\n";
            }

            if (!empty($domiciliation['types_documents_acceptes'])) {
                $context .= "- Types de documents acceptés:\n";
                foreach ($domiciliation['types_documents_acceptes'] as $doc) {
                    $context .= "  • {$doc['type']} ({$doc['pour']})\n";
                }
            }
        }

        return trim($context);
    }
}
