<?php

namespace Secret\Santa\Controllers;

use Secret\Santa\Models\PairModel;

class PairController {
    private $model;

    public function __construct() {
        $this->model = new PairModel();
    }

    private function checkCsrfToken() {
        $headers = getallheaders();
        $clientToken = $headers['X-CSRF-Token'] ?? '';
    
        if (!isset($_SESSION['csrf_token']) || $clientToken !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
            exit();
        }
    }

    
    private function getUserIdFromSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user']['username'])) {
            return null;
        }
        return $_SESSION['user']['username'];
    }

    public function createPair($gameId, $gifterId, $receiverId) {
        $this->checkCsrfToken();
        $success = $this->model->createPair($gameId, $gifterId, $receiverId);
        if ($success) {
            return json_encode(['status' => 'success', 'message' => 'Pair created successfully']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Failed to create pair']);
        }
    }

    public function getPairsByGameId($gameId) {
        $pairs = $this->model->getPairsByGameId($gameId);
        return json_encode(['status' => 'success', 'pairs' => $pairs]);
    }

    public function getPairById($pairId) {
        $pair = $this->model->getPairById($pairId);
        if ($pair) {
            return json_encode(['status' => 'success', 'pair' => $pair]);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Pair not found']);
        }
    }

    public function deletePair($pairId) {
        $this->checkCsrfToken();
        $success = $this->model->deletePair($pairId);
        if ($success) {
            return json_encode(['status' => 'success', 'message' => 'Pair deleted successfully']);
        } else {
            return json_encode(['status' => 'error', 'message' => 'Failed to delete pair']);
        }
    }

    public function generateRandomPairs($gameId) {
        $this->checkCsrfToken();
        $players = $this->model->getPlayersByGameId($gameId);

        $numPlayers = count($players);

        if ($numPlayers < 2) {
            return json_encode(['status' => 'error', 'message' => 'At least two players are required to create pairs']);
        }

        shuffle($players);

        $pairs = [];

        $receivers = $players;

        do {
            shuffle($receivers);
            $isValid = true;
            for ($i = 0; $i < $numPlayers; $i++) {
                if ($players[$i] === $receivers[$i]) {
                    $isValid = false;
                    break;
                }
            }
        } while (!$isValid);

        for ($i = 0; $i < $numPlayers; $i++) {
            $gifter = $players[$i];
            $receiver = $receivers[$i];
            $pairs[] = ['gifter' => $gifter, 'receiver' => $receiver];
        }

        $this->model->deletePairsByGameId($gameId);

        foreach ($pairs as $pair) {
            $this->model->createPair($gameId, $pair['gifter'], $pair['receiver']);
        }

        return json_encode(['status' => 'success', 'message' => 'Random pairs created successfully', 'pairs' => $pairs]);
    }

    public function getUserPairForGame($uuid) {
        $userId = $this->getUserIdFromSession();
        if (!$userId) {
            http_response_code(401);
            return json_encode(['status' => 'error', 'message' => 'User not authenticated']);
        }
    
        $pair = $this->model->getPairForGifter($uuid, $userId);
        if (!$pair) {
            return json_encode(['status' => 'error', 'message' => 'No pair found for this user in the given game']);
        }
    
        // Получаем статус подарка для текущего пользователя и его получателя
        $playerGameModel = new \Secret\Santa\Models\PlayerGameModel();
        $userStatus = $playerGameModel->getPlayerStatus($userId, $uuid);
        $receiverStatus = $playerGameModel->getPlayerStatus($pair['receiver_id'], $uuid);
    
        return json_encode([
            'status' => 'success',
            'gifter' => $userId,
            'receiver' => $pair['receiver_id'],
            'user_is_gifted' => $userStatus['is_gifted'] ?? false,
            'receiver_is_gifted' => $receiverStatus['is_gifted'] ?? false
        ]);
    }
    
}

?>
