<?php

/**
 * Character Store API
 * Standalone PHP script to manage online character card store.
 * Stores data in a JSON file.
 */

class CharacterStore
{
    private $storeFile;

    public function __construct()
    {
        $this->storeFile = __DIR__ . '/character_store.json';
        if (!file_exists($this->storeFile)) {
            file_put_contents($this->storeFile, json_encode([]));
        }
    }

    public function dispatch()
    {
        // CORS Headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }

        $action = isset($_GET['action']) ? $_GET['action'] : 'list';

        switch ($action) {
            case 'list':
                $this->listCards();
                break;
            case 'share':
                $this->shareCard();
                break;
            case 'get':
                $this->getCard();
                break;
            default:
                $this->jsonResponse(['error' => 'Invalid action'], 400);
        }
    }

    private function listCards()
    {
        $cards = json_decode(file_get_contents($this->storeFile), true);
        // Sort by likes (descending) or date
        // For now, just return all
        $this->jsonResponse(['cards' => $cards]);
    }

    private function shareCard()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['card'])) {
            $this->jsonResponse(['error' => 'No card data provided'], 400);
            return;
        }

        $card = $input['card'];
        
        // Basic validation
        if (empty($card['name']) || empty($card['systemPrompt'])) {
            $this->jsonResponse(['error' => 'Name and System Prompt are required'], 400);
            return;
        }

        // Sanitize and prepare card data
        $newCard = [
            'id' => uniqid('card_'),
            'name' => htmlspecialchars($card['name']),
            'avatar' => isset($card['avatar']) ? $card['avatar'] : '👤',
            'description' => isset($card['description']) ? htmlspecialchars($card['description']) : '',
            'systemPrompt' => $card['systemPrompt'], // Keep raw for functionality, maybe sanitize if needed
            'tags' => isset($card['tags']) ? $card['tags'] : [],
            'author' => isset($card['author']) ? htmlspecialchars($card['author']) : 'Anonymous',
            'date' => date('Y-m-d H:i:s'),
            'likes' => 0,
            'downloads' => 0
        ];

        $cards = json_decode(file_get_contents($this->storeFile), true);
        $cards[] = $newCard;
        
        file_put_contents($this->storeFile, json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->jsonResponse(['success' => true, 'card' => $newCard]);
    }

    private function getCard()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        if (!$id) {
            $this->jsonResponse(['error' => 'Missing ID'], 400);
            return;
        }

        $cards = json_decode(file_get_contents($this->storeFile), true);
        foreach ($cards as $card) {
            if ($card['id'] === $id) {
                $this->jsonResponse(['card' => $card]);
                return;
            }
        }

        $this->jsonResponse(['error' => 'Card not found'], 404);
    }

    private function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// Instantiate and dispatch
$app = new CharacterStore();
$app->dispatch();
