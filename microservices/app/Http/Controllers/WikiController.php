<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikiController extends Controller
{
    public function index()
    {
        return view('ask');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:255'
        ]);

        $question = $request->input('question');
        
        // Analyse de la question pour détecter le type
        $questionType = $this->analyzeQuestionType($question);
        
        // Valeurs par défaut
        $results = [
            'question' => $question,
            'questionType' => $questionType,
            'title' => null,
            'answer' => "Aucune réponse disponible.",
            'pageUrl' => "https://fr.wikipedia.org/w/index.php?search=" . urlencode($question),
            'relatedTopics' => [],
            'sections' => [],
            'images' => [],
            'infobox' => [],
            'confidence' => 0
        ];

        try {
            // 1) Recherche intelligente avec plusieurs termes
            $searchTerms = $this->generateSearchTerms($question);
            $bestMatch = $this->findBestMatch($searchTerms);

            if ($bestMatch) {
                $results['title'] = $bestMatch['title'];
                $results['confidence'] = $bestMatch['confidence'];
                
                // 2) Récupération des données enrichies
                $wikiData = $this->getEnhancedWikiData($bestMatch['title']);
                
                if ($wikiData) {
                    $results = array_merge($results, $wikiData);
                    
                    // 3) Génération d'une réponse intelligente basée sur le type de question
                    $results['answer'] = $this->generateIntelligentAnswer(
                        $question, 
                        $questionType, 
                        $wikiData
                    );
                }
            } else {
                $results['answer'] = $this->generateNoResultsResponse($question);
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans WikiController::ask', [
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            $results['answer'] = "Une erreur s'est produite lors de la recherche. Veuillez réessayer.";
        }

        return view('answer', $results);
    }

    /**
     * Analyse le type de question pour adapter la réponse
     */
    private function analyzeQuestionType(string $question): string
    {
        $question = strtolower($question);
        
        $patterns = [
            'definition' => ['/qu\'est[- ]ce que/', '/c\'est quoi/', '/définition/', '/define/'],
            'who' => ['/qui est/', '/qui était/', '/qui a/', '/biographie/'],
            'when' => ['/quand/', '/à quelle époque/', '/en quelle année/', '/date/'],
            'where' => ['/où/', '/dans quel pays/', '/localisation/', '/situé/'],
            'how' => ['/comment/', '/de quelle manière/', '/procédure/'],
            'why' => ['/pourquoi/', '/raison/', '/cause/'],
            'count' => ['/combien/', '/nombre/', '/quantité/'],
            'comparison' => ['/différence/', '/comparaison/', '/versus/', '/vs/']
        ];

        foreach ($patterns as $type => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $question)) {
                    return $type;
                }
            }
        }

        return 'general';
    }

    /**
     * Génère des termes de recherche alternatifs
     */
    private function generateSearchTerms(string $question): array
    {
        $terms = [$question];
        
        // Nettoyage de la question
        $cleaned = preg_replace('/^(qu\'est[- ]ce que|c\'est quoi|qui est|quand|où|comment|pourquoi)\s+/i', '', $question);
        $cleaned = trim($cleaned, '?');
        
        if ($cleaned !== $question) {
            $terms[] = $cleaned;
        }

        // Extraction des mots-clés importants
        $keywords = $this->extractKeywords($cleaned);
        if (!empty($keywords)) {
            $terms[] = implode(' ', $keywords);
        }

        return array_unique($terms);
    }

    /**
     * Extrait les mots-clés importants
     */
    private function extractKeywords(string $text): array
    {
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'mais', 'donc', 'or', 'ni', 'car'];
        $words = explode(' ', strtolower($text));
        
        return array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
    }

    /**
     * Trouve la meilleure correspondance parmi les termes de recherche
     */
    private function findBestMatch(array $searchTerms): ?array
    {
        $bestMatch = null;
        $highestScore = 0;

        foreach ($searchTerms as $term) {
            $searchResponse = Http::withHeaders([
                'User-Agent' => 'MyLaravelApp/1.0 (https://ton-site.com; contact@ton-site.com)'
            ])->get('https://fr.wikipedia.org/w/api.php', [
                'action'   => 'query',
                'list'     => 'search',
                'srsearch' => $term,
                'format'   => 'json',
                'srlimit'  => 5,
                'srprop'   => 'score|snippet'
            ]);

            if ($searchResponse->successful()) {
                $searchData = $searchResponse->json();
                
                if (!empty($searchData['query']['search'])) {
                    foreach ($searchData['query']['search'] as $result) {
                        $score = $this->calculateRelevanceScore($term, $result);
                        
                        if ($score > $highestScore) {
                            $highestScore = $score;
                            $bestMatch = [
                                'title' => $result['title'],
                                'confidence' => min(100, $score),
                                'snippet' => $result['snippet'] ?? ''
                            ];
                        }
                    }
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Calcule un score de pertinence
     */
    private function calculateRelevanceScore(string $query, array $result): float
    {
        $score = 0;
        
        // Score de base Wikipedia
        $score += ($result['score'] ?? 0) / 100;
        
        // Bonus si le titre correspond exactement
        if (strtolower($result['title']) === strtolower($query)) {
            $score += 50;
        }
        
        // Bonus si le titre contient les mots de la requête
        $queryWords = explode(' ', strtolower($query));
        $titleWords = explode(' ', strtolower($result['title']));
        
        $matchingWords = array_intersect($queryWords, $titleWords);
        $score += (count($matchingWords) / count($queryWords)) * 30;
        
        return $score;
    }

    /**
     * Récupère des données enrichies de Wikipedia
     */
    private function getEnhancedWikiData(string $title): ?array
    {
        $data = [];
        
        // 1) Résumé de base
        $summaryResponse = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (https://ton-site.com; contact@ton-site.com)'
        ])->get('https://fr.wikipedia.org/api/rest_v1/page/summary/' . urlencode($title));

        if ($summaryResponse->successful()) {
            $summary = $summaryResponse->json();
            $data['summary'] = $summary['extract'] ?? '';
            $data['pageUrl'] = $summary['content_urls']['desktop']['page'] ?? '';
            $data['thumbnail'] = $summary['thumbnail']['source'] ?? null;
        }

        // 2) Contenu détaillé avec sections
        $contentResponse = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (https://ton-site.com; contact@ton-site.com)'
        ])->get('https://fr.wikipedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'titles' => $title,
            'prop' => 'extracts|pageimages|categories',
            'exsectionformat' => 'wiki',
            'exlimit' => 1,
            'piprop' => 'thumbnail',
            'pithumbsize' => 300
        ]);

        if ($contentResponse->successful()) {
            $contentData = $contentResponse->json();
            $pages = $contentData['query']['pages'] ?? [];
            $page = array_shift($pages);
            
            if ($page) {
                // Extraction des sections
                $extract = $page['extract'] ?? '';
                $data['sections'] = $this->extractSections($extract);
                
                // Image principale
                if (isset($page['thumbnail'])) {
                    $data['images'][] = $page['thumbnail']['source'];
                }
            }
        }

        // 3) Récupération de l'infobox
        $data['infobox'] = $this->getInfobox($title);
        
        // 4) Sujets connexes
        $data['relatedTopics'] = $this->getRelatedTopics($title);

        return $data;
    }

    /**
     * Extrait les sections du contenu
     */
    private function extractSections(string $content): array
    {
        $sections = [];
        $parts = preg_split('/\n=+\s*(.+?)\s*=+\n/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 1; $i < count($parts); $i += 2) {
            if (isset($parts[$i + 1])) {
                $sections[] = [
                    'title' => trim($parts[$i]),
                    'content' => trim(strip_tags($parts[$i + 1]))
                ];
            }
        }
        
        return array_slice($sections, 0, 5); // Limite à 5 sections
    }

    /**
     * Récupère les données de l'infobox
     */
    private function getInfobox(string $title): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (https://ton-site.com; contact@ton-site.com)'
        ])->get('https://fr.wikipedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'titles' => $title,
            'prop' => 'revisions',
            'rvprop' => 'content',
            'rvsection' => 0
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            $page = array_shift($pages);
            
            if (isset($page['revisions'][0]['*'])) {
                return $this->parseInfobox($page['revisions'][0]['*']);
            }
        }

        return [];
    }

    /**
     * Parse l'infobox depuis le wikitext
     */
    private function parseInfobox(string $wikitext): array
    {
        $infobox = [];
        
        // Regex simple pour capturer les paramètres d'infobox
        if (preg_match('/\{\{Infobox.*?\n(.*?)\n\}\}/s', $wikitext, $matches)) {
            $content = $matches[1];
            
            preg_match_all('/\|\s*([^=]+?)\s*=\s*([^|]+)/s', $content, $params);
            
            for ($i = 0; $i < count($params[1]); $i++) {
                $key = trim($params[1][$i]);
                $value = trim(strip_tags($params[2][$i]));
                
                if (!empty($value) && strlen($value) < 200) {
                    $infobox[$key] = $value;
                }
            }
        }
        
        return array_slice($infobox, 0, 10); // Limite à 10 éléments
    }

    /**
     * Récupère des sujets connexes
     */
    private function getRelatedTopics(string $title): array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'MyLaravelApp/1.0 (https://ton-site.com; contact@ton-site.com)'
        ])->get('https://fr.wikipedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'titles' => $title,
            'prop' => 'links',
            'pllimit' => 10
        ]);

        $related = [];
        
        if ($response->successful()) {
            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];
            $page = array_shift($pages);
            
            if (isset($page['links'])) {
                foreach ($page['links'] as $link) {
                    if (isset($link['title']) && !str_starts_with($link['title'], 'Catégorie:')) {
                        $related[] = $link['title'];
                    }
                }
            }
        }
        
        return array_slice($related, 0, 5);
    }

    /**
     * Génère une réponse intelligente basée sur le type de question
     */
    private function generateIntelligentAnswer(string $question, string $type, array $data): string
    {
        $summary = $data['summary'] ?? '';
        $sections = $data['sections'] ?? [];
        $infobox = $data['infobox'] ?? [];
        
        switch ($type) {
            case 'definition':
                return $this->generateDefinitionAnswer($summary, $infobox);
            
            case 'who':
                return $this->generatePersonAnswer($summary, $infobox, $sections);
            
            case 'when':
                return $this->generateTimeAnswer($summary, $infobox, $sections);
            
            case 'where':
                return $this->generateLocationAnswer($summary, $infobox);
            
            case 'how':
                return $this->generateProcessAnswer($summary, $sections);
            
            default:
                return $this->generateGeneralAnswer($summary, $sections, $infobox);
        }
    }

    private function generateDefinitionAnswer(string $summary, array $infobox): string
    {
        $answer = $summary;
        
        if (!empty($infobox)) {
            $keyInfo = [];
            foreach (['type', 'nature', 'genre', 'catégorie'] as $key) {
                if (isset($infobox[$key])) {
                    $keyInfo[] = $infobox[$key];
                }
            }
            
            if (!empty($keyInfo)) {
                $answer = "**Type :** " . implode(', ', $keyInfo) . "\n\n" . $answer;
            }
        }
        
        return $answer;
    }

    private function generatePersonAnswer(string $summary, array $infobox, array $sections): string
    {
        $answer = $summary;
        
        // Ajout des infos biographiques de l'infobox
        if (!empty($infobox)) {
            $bioInfo = [];
            foreach (['naissance', 'naissance_date', 'mort', 'mort_date', 'nationalité', 'profession'] as $key) {
                if (isset($infobox[$key])) {
                    $bioInfo[] = "**" . ucfirst($key) . " :** " . $infobox[$key];
                }
            }
            
            if (!empty($bioInfo)) {
                $answer .= "\n\n**Informations biographiques :**\n" . implode("\n", $bioInfo);
            }
        }
        
        return $answer;
    }

    private function generateTimeAnswer(string $summary, array $infobox, array $sections): string
    {
        $answer = $summary;
        
        // Recherche de dates dans l'infobox et les sections
        $timeInfo = [];
        
        foreach ($infobox as $key => $value) {
            if (preg_match('/\d{4}/', $value) || stripos($key, 'date') !== false) {
                $timeInfo[] = "**" . ucfirst($key) . " :** " . $value;
            }
        }
        
        if (!empty($timeInfo)) {
            $answer .= "\n\n**Informations temporelles :**\n" . implode("\n", $timeInfo);
        }
        
        return $answer;
    }

    private function generateLocationAnswer(string $summary, array $infobox): string
    {
        $answer = $summary;
        
        $locationInfo = [];
        foreach (['pays', 'région', 'ville', 'localisation', 'coordonnées'] as $key) {
            if (isset($infobox[$key])) {
                $locationInfo[] = "**" . ucfirst($key) . " :** " . $infobox[$key];
            }
        }
        
        if (!empty($locationInfo)) {
            $answer .= "\n\n**Informations géographiques :**\n" . implode("\n", $locationInfo);
        }
        
        return $answer;
    }

    private function generateProcessAnswer(string $summary, array $sections): string
    {
        $answer = $summary;
        
        // Recherche de sections explicatives
        foreach ($sections as $section) {
            if (stripos($section['title'], 'fonctionnement') !== false || 
                stripos($section['title'], 'méthode') !== false ||
                stripos($section['title'], 'processus') !== false) {
                $answer .= "\n\n**" . $section['title'] . " :**\n" . substr($section['content'], 0, 300) . "...";
                break;
            }
        }
        
        return $answer;
    }

    private function generateGeneralAnswer(string $summary, array $sections, array $infobox): string
    {
        $answer = $summary;
        
        // Ajout des informations clés de l'infobox
        if (!empty($infobox)) {
            $keyInfo = array_slice($infobox, 0, 3);
            if (!empty($keyInfo)) {
                $answer .= "\n\n**Informations clés :**";
                foreach ($keyInfo as $key => $value) {
                    $answer .= "\n- **" . ucfirst($key) . " :** " . $value;
                }
            }
        }
        
        return $answer;
    }

    private function generateNoResultsResponse(string $question): string
    {
        return "Désolé, je n'ai pas trouvé d'informations pertinentes sur Wikipedia pour votre question : « {$question} ». " .
               "Essayez de reformuler votre question ou d'utiliser des termes plus spécifiques.";
    }
}