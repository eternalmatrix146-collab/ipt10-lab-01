<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Comment;

class ReplyController
{
    private Comment $comments;

    public function __construct()
    {
        $this->comments = new Comment();
    }

    public function store(): void
    {
        // 1. Check authentication on the server first
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            flash('error', 'You must be logged in to post a reply.');
            redirect('/login');
            return;
        }

        // 2. Validate session-backed CSRF token
        if (!csrf_check()) {
            http_response_code(400);
            flash('error', 'Invalid or expired CSRF token.');
            redirect($_SERVER['HTTP_REFERER'] ?? '/discussions/1');
            return;
        }

        // 3. Validate inputs
        $discussionId = filter_input(INPUT_POST, 'discussion_id', FILTER_VALIDATE_INT);
        $body = trim((string)($_POST['body'] ?? ''));

        if (!$discussionId) {
            flash('error', 'Invalid discussion ID.');
            redirect('/discussions/1');
            return;
        }

        $rawParentId = $_POST['parent_id'] ?? '';
        $parentId = ($rawParentId === '' || $rawParentId === null) ? null : filter_var($rawParentId, FILTER_VALIDATE_INT);
        if ($rawParentId !== '' && $rawParentId !== null && $parentId === false) {
            flash('error', 'Invalid parent comment ID.');
            redirect('/discussions/' . $discussionId);
            return;
        }

        try {
            $this->comments->create((int)$discussionId, (int)$userId, $body, $parentId !== null ? (int)$parentId : null);
            flash('success', 'Reply posted successfully.');
        } catch (\InvalidArgumentException $e) {
            flash('error', $e->getMessage());
        }

        redirect('/discussions/' . $discussionId);
    }
}
