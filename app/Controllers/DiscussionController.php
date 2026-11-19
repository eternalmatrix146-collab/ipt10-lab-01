<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Discussion;
use App\Models\Comment;

class DiscussionController
{
    private Discussion $discussions;
    private Comment $comments;

    public function __construct()
    {
        $this->discussions = new Discussion();
        $this->comments = new Comment();
    }

    public function show(string $id): void
    {
        // Malformed ID check: must be a positive integer
        if (!preg_match('/^[1-9]\d*$/', $id)) {
            http_response_code(404);
            $this->renderError(404, 'Invalid discussion ID: "' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '". Must be a numeric identifier.');
            return;
        }

        $discussion = $this->discussions->findById((int)$id);
        if (!$discussion) {
            http_response_code(404);
            $this->renderError(404, 'Discussion #' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' not found.');
            return;
        }

        $commentTree = $this->comments->treeForDiscussion((int)$id);

        $mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../app/Views'),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../app/Views'),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
        ]);

        echo $mustache->render('discussion', [
            'site_title' => $discussion['title'] . ' — Nested Forum',
            'discussion' => $discussion,
            'comments' => $commentTree,
            'has_any_comments' => !empty($commentTree),
            'csrf_token' => csrf_token(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'flash' => [
                'error' => flash('error'),
                'success' => flash('success'),
            ],
        ]);
    }

    private function renderError(int $code, string $message): void
    {
        $mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../app/Views'),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
        ]);

        echo $mustache->render('error', [
            'status_code' => $code,
            'error_message' => $message,
            'site_title' => $code . ' — Nested Forum',
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'csrf_token' => csrf_token(),
        ]);
    }
}
