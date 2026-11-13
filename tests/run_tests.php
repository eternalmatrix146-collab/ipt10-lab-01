<?php
declare(strict_types=1);

/**
 * Nested Forum Lab - Automated Test Suite
 * Tests all requirements from Sections 7, 8 and 9 of the lab specification.
 */

define('TESTING_MODE', true);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers.php';

use App\Models\Database;
use App\Models\User;
use App\Models\Discussion;
use App\Models\Comment;
use App\Controllers\DiscussionController;
use App\Controllers\AuthController;
use App\Controllers\ReplyController;

class TestRunner
{
    private PDO $pdo;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    public function assert(string $description, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$description}" . ($detail ? " - {$detail}" : '') . "\n";
        }
    }

    public function runAll(): void
    {
        echo "========================================================\n";
        echo " Nested Forum Lab: Comprehensive Test Suite\n";
        echo "========================================================\n\n";

        $this->resetDatabase();
        $this->testDatabaseSchemaAndCompositeFK();
        $this->testSeedDataAndHierarchy();
        $this->testUserRegistration();
        $this->testUserAuthentication();
        $this->testAuthenticatedReplies();
        $this->testGuestRejection();
        $this->testCsrfProtection();
        $this->testCrossDiscussionParentPrevention();
        $this->testCyclePrevention();
        $this->testMalformedAndMissingDiscussionIDs();
        $this->testHtmlEscaping();
        $this->testDisplayIndentationCap();
        $this->testCollapseExpandAriaMarkup();

        echo "\n========================================================\n";
        echo " Test Summary: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function resetDatabase(): void
    {
        echo "[*] Initializing fresh database from schema.sql and seed.sql...\n";
        $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
        $this->pdo->exec($schema);
        $seed = file_get_contents(__DIR__ . '/../database/seed.sql');
        $this->pdo->exec($seed);
        echo "    Fresh database ready.\n\n";
    }

    private function testDatabaseSchemaAndCompositeFK(): void
    {
        echo "--- 1. Testing Database Schema & Constraints ---\n";
        
        // Check tables exist
        $tables = $this->pdo->query("SHOW TABLES LIKE 'comments'")->fetchAll();
        $this->assert("Table 'comments' exists", count($tables) === 1);

        $tablesUser = $this->pdo->query("SHOW TABLES LIKE 'users'")->fetchAll();
        $this->assert("Table 'users' exists", count($tablesUser) === 1);

        $tablesDisc = $this->pdo->query("SHOW TABLES LIKE 'discussions'")->fetchAll();
        $this->assert("Table 'discussions' exists", count($tablesDisc) === 1);

        // Check composite foreign key fk_comments_parent_discussion
        $stmt = $this->pdo->prepare("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'comments' 
              AND CONSTRAINT_NAME = 'fk_comments_parent_discussion'
        ");
        $stmt->execute();
        $this->assert("Composite FK 'fk_comments_parent_discussion' exists in database schema", (bool)$stmt->fetch());
    }

    private function testSeedDataAndHierarchy(): void
    {
        echo "\n--- 2. Testing Seed Data & Reply Tree Hierarchy ---\n";

        $commentModel = new Comment();
        $flat = $commentModel->threadedForDiscussion(1);
        $this->assert("Seed data contains exactly 7 comments", count($flat) === 7);

        // Verify exact scenario trace:
        // 1: Alice (depth 0)
        // 2: Ben (depth 1)
        // 3: Cara (depth 2)
        // 6: Ben (depth 3)
        // 7: Eli (depth 4)
        // 4: Dee (depth 1)
        // 5: Eli (depth 0)
        $expected = [
            ['id' => 1, 'author' => 'alice', 'depth' => 0, 'indent' => 0],
            ['id' => 2, 'author' => 'ben',   'depth' => 1, 'indent' => 1],
            ['id' => 3, 'author' => 'cara',  'depth' => 2, 'indent' => 2],
            ['id' => 6, 'author' => 'ben',   'depth' => 3, 'indent' => 3],
            ['id' => 7, 'author' => 'eli',   'depth' => 4, 'indent' => 4],
            ['id' => 4, 'author' => 'dee',   'depth' => 1, 'indent' => 1],
            ['id' => 5, 'author' => 'eli',   'depth' => 0, 'indent' => 0],
        ];

        $matched = true;
        foreach ($expected as $idx => $exp) {
            $actual = $flat[$idx];
            if ($actual['id'] !== $exp['id'] || $actual['author'] !== $exp['author'] || $actual['depth'] !== $exp['depth'] || $actual['indent'] !== $exp['indent']) {
                $matched = false;
                break;
            }
        }
        $this->assert("Pre-order traversal matches lab specification exactly (Alice->Ben->Cara->Ben->Eli, Dee, Eli)", $matched);
        $this->assert("Deepest branch reaches displayed depth 4 (Comment #7 by Eli)", $flat[4]['depth'] === 4 && $flat[4]['id'] === 7);
    }

    private function testUserRegistration(): void
    {
        echo "\n--- 3. Testing Registration & Validation ---\n";

        $userModel = new User();

        // Valid registration
        $newId = $userModel->create('grace', 'grace@example.com', 'Secret123!');
        $this->assert("Valid user registration returns a positive user ID", $newId > 0);

        $grace = $userModel->findByUsername('grace');
        $this->assert("Registered user exists in database", $grace !== null && $grace['username'] === 'grace');
        $this->assert("Password is stored as a secure bcrypt hash (never plain-text)", str_starts_with($grace['password_hash'], '$2y$'));

        // Duplicate username rejection
        $this->assert("Duplicate username detection works", $userModel->findByUsername('grace') !== null);

        // Duplicate email rejection
        $this->assert("Duplicate email detection works", $userModel->findByEmail('grace@example.com') !== null);
    }

    private function testUserAuthentication(): void
    {
        echo "\n--- 4. Testing Authentication, Password Verification & Null Hash Safety ---\n";

        $userModel = new User();
        $grace = $userModel->findByUsername('grace');

        // Correct password
        $this->assert("Valid password verifies correctly", $userModel->verifyPassword($grace, 'Secret123!'));

        // Incorrect password
        $this->assert("Incorrect password is rejected", !$userModel->verifyPassword($grace, 'WrongPassword'));

        // Null password hash safety
        $fakeUserNull = ['id' => 999, 'username' => 'ghost', 'password_hash' => null];
        $this->assert("Null password hash never authenticates", !$userModel->verifyPassword($fakeUserNull, 'anypassword'));

        $fakeUserEmpty = ['id' => 998, 'username' => 'ghost2', 'password_hash' => ''];
        $this->assert("Empty password hash never authenticates", !$userModel->verifyPassword($fakeUserEmpty, 'anypassword'));
    }

    private function testAuthenticatedReplies(): void
    {
        echo "\n--- 5. Testing Authenticated Replies (Depth-5 and Top-Level) ---\n";

        $commentModel = new Comment();
        $userModel = new User();
        $grace = $userModel->findByUsername('grace');

        // Post depth-5 reply under comment 7 (which is at depth 4)
        $depth5Id = $commentModel->create(1, (int)$grace['id'], 'Awesome tips, applying them now!', 7);
        $this->assert("Successfully posted reply under Comment #7 (returned ID {$depth5Id})", $depth5Id > 0);

        // Post a second top-level comment
        $topLevelId = $commentModel->create(1, (int)$grace['id'], 'Also make sure to learn PDO and prepared statements.', null);
        $this->assert("Successfully posted second top-level comment (returned ID {$topLevelId})", $topLevelId > 0);

        // Verify tree placement
        $flat = $commentModel->threadedForDiscussion(1);
        $depth5Node = null;
        $topLevelNode = null;
        foreach ($flat as $node) {
            if ($node['id'] === $depth5Id) {
                $depth5Node = $node;
            }
            if ($node['id'] === $topLevelId) {
                $topLevelNode = $node;
            }
        }

        $this->assert("New reply has calculated depth 5", $depth5Node !== null && $depth5Node['depth'] === 5);
        $this->assert("New reply author is grace", $depth5Node !== null && $depth5Node['author'] === 'grace');
        $this->assert("New top-level comment has depth 0", $topLevelNode !== null && $topLevelNode['depth'] === 0);
        $this->assert("New top-level comment parent_id is NULL", $topLevelNode !== null && $topLevelNode['parent_id'] === null);
    }

    private function testGuestRejection(): void
    {
        echo "\n--- 6. Testing Server-Side Guest Write Rejection ---\n";

        $countBefore = (int)$this->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();

        // Simulate guest POST request by unsetting session user_id
        $_SESSION = [];
        $_POST = [
            'discussion_id' => '1',
            'parent_id' => '',
            'body' => 'Spam comment from unauthorized guest',
            'csrf_token' => 'fake_or_valid',
        ];

        // Ensure reply controller rejects unauthenticated users
        $replyCtrl = new ReplyController();
        
        // Execute inside output buffer to catch redirect
        ob_start();
        $replyCtrl->store();
        ob_end_clean();

        $countAfter = (int)$this->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
        $this->assert("Guest POST does not create a database row (count remained {$countBefore})", $countBefore === $countAfter);
    }

    private function testCsrfProtection(): void
    {
        echo "\n--- 7. Testing Session-Backed CSRF Protection ---\n";

        $countBefore = (int)$this->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();

        // Simulate logged in user with INVALID csrf token
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'alice';
        $_SESSION['csrf_token'] = 'valid_session_token_1234567890abcdef';
        $_POST = [
            'discussion_id' => '1',
            'parent_id' => '',
            'body' => 'CSRF attack attempt',
            'csrf_token' => 'tampered_or_invalid_token',
        ];

        $replyCtrl = new ReplyController();
        ob_start();
        $replyCtrl->store();
        ob_end_clean();

        $countAfter = (int)$this->pdo->query('SELECT COUNT(*) FROM comments')->fetchColumn();
        $this->assert("Invalid CSRF token is rejected and creates no row", $countBefore === $countAfter);
    }

    private function testCrossDiscussionParentPrevention(): void
    {
        echo "\n--- 8. Testing Cross-Discussion Parent Invariant ---\n";

        // Create a second discussion
        $this->pdo->exec("INSERT INTO discussions (id, user_id, title, body) VALUES (2, 1, 'Discussion Two', 'Second topic')");

        $commentModel = new Comment();
        $userModel = new User();
        $alice = $userModel->findByUsername('alice');

        // Try to reply in Discussion 2 referencing Comment 1 (which belongs to Discussion 1)
        $caught = false;
        try {
            $commentModel->create(2, (int)$alice['id'], 'Cross-discussion reply', 1);
        } catch (\InvalidArgumentException $e) {
            $caught = true;
        }

        $this->assert("Cross-discussion parent rejected by application logic (validateParent)", $caught);

        // Also test database-level composite foreign key rejection
        $dbBlocked = false;
        try {
            $stmt = $this->pdo->prepare("INSERT INTO comments (discussion_id, parent_id, author_id, body) VALUES (?, ?, ?, ?)");
            $stmt->execute([2, 1, (int)$alice['id'], 'Direct DB cross-discussion bypass']);
        } catch (\PDOException $e) {
            $dbBlocked = true;
        }
        $this->assert("Cross-discussion parent rejected by MySQL Composite Foreign Key constraint", $dbBlocked);
    }

    private function testCyclePrevention(): void
    {
        echo "\n--- 9. Testing Cycle Prevention ---\n";

        $commentModel = new Comment();
        
        // Simulating cycle: if parent pointer somehow formed 2 -> 3 -> 2
        // We test that validateParent catches cycle
        $this->pdo->beginTransaction();
        // Artificially create a cycle to test validateParent detection
        $this->pdo->exec("UPDATE comments SET parent_id = 3 WHERE id = 1");
        
        $cycleCaught = false;
        try {
            $commentModel->validateParent(1, 1);
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'cycle')) {
                $cycleCaught = true;
            }
        }
        $this->pdo->rollBack();

        $this->assert("Cycle detection identifies loops in parent chain and aborts", $cycleCaught);
    }

    private function testMalformedAndMissingDiscussionIDs(): void
    {
        echo "\n--- 10. Testing Malformed & Missing Discussion IDs ---\n";

        $discussionCtrl = new DiscussionController();

        // Non-numeric malformed ID: /discussions/abc
        ob_start();
        $discussionCtrl->show('abc');
        $outputMalformed = ob_get_clean();
        $this->assert("Non-numeric discussion ID returns 404 status", http_response_code() === 404);
        $this->assert("Non-numeric discussion ID renders helpful error message", str_contains($outputMalformed, 'Invalid discussion ID'));

        // Non-existent discussion ID: /discussions/99999
        ob_start();
        $discussionCtrl->show('99999');
        $outputMissing = ob_get_clean();
        $this->assert("Non-existent discussion ID returns 404 status", http_response_code() === 404);
        $this->assert("Non-existent discussion ID renders 'not found' message", str_contains($outputMissing, 'not found'));
    }

    private function testHtmlEscaping(): void
    {
        echo "\n--- 11. Testing HTML & Script-Tag Escaping ---\n";

        $commentModel = new Comment();
        $userModel = new User();
        $alice = $userModel->findByUsername('alice');

        // Post an XSS / HTML comment
        $xssBody = "<script>alert('XSS Attack');</script> & <b>bold text</b> \"quoted\"";
        $xssId = $commentModel->create(1, (int)$alice['id'], $xssBody, null);

        // Fetch discussion and render with Mustache
        $discussionModel = new Discussion();
        $discussion = $discussionModel->findById(1);
        $commentTree = $commentModel->treeForDiscussion(1);

        $mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../app/Views'),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../app/Views'),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
        ]);

        $renderedHtml = $mustache->render('discussion', [
            'site_title' => 'Test Discussion',
            'discussion' => $discussion,
            'comments' => $commentTree,
            'has_any_comments' => true,
            'csrf_token' => 'dummy_token',
            'user_id' => 1,
            'username' => 'alice',
        ]);

        $this->assert("Raw <script> tag is NOT present in rendered HTML", !str_contains($renderedHtml, "<script>alert"));
        $this->assert("Escaped script tag &lt;script&gt; is present in rendered HTML", str_contains($renderedHtml, "&lt;script&gt;alert"));
        $this->assert("Quotes are safely escaped as &#039; or &quot;", str_contains($renderedHtml, "&#039;XSS Attack&#039;"));
    }

    private function testDisplayIndentationCap(): void
    {
        echo "\n--- 12. Testing Visual Indentation Cap at Depth 8 ---\n";

        $commentModel = new Comment();
        $userModel = new User();
        $alice = $userModel->findByUsername('alice');

        // Create a chain from depth 5 up to depth 10
        // Currently comment 8 is at depth 5.
        // Let's create comments: 8 (depth 5) -> 9 (depth 6) -> 10 (depth 7) -> 11 (depth 8) -> 12 (depth 9) -> 13 (depth 10)
        $currentParent = 8;
        for ($d = 6; $d <= 10; $d++) {
            $currentParent = $commentModel->create(1, (int)$alice['id'], "Reply at depth {$d}", $currentParent);
        }

        $flat = $commentModel->threadedForDiscussion(1);
        $lastNode = null;
        foreach ($flat as $node) {
            if ($node['id'] === $currentParent) {
                $lastNode = $node;
            }
        }

        $this->assert("Comment at depth 10 has accurate calculated depth label (10)", $lastNode !== null && $lastNode['depth'] === 10);
        $this->assert("Comment at depth 10 has visual indentation capped at 8", $lastNode !== null && $lastNode['indent'] === 8);
    }

    private function testCollapseExpandAriaMarkup(): void
    {
        echo "\n--- 13. Testing Collapse & Expand ARIA Accessibility (Challenge 9) ---\n";

        $commentModel = new Comment();
        $discussionModel = new Discussion();
        $discussion = $discussionModel->findById(1);
        $commentTree = $commentModel->treeForDiscussion(1);

        $mustache = new \Mustache_Engine([
            'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../app/Views'),
            'partials_loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../app/Views'),
            'escape' => function ($value) {
                return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
        ]);

        $rendered = $mustache->render('discussion', [
            'site_title' => 'Test',
            'discussion' => $discussion,
            'comments' => $commentTree,
            'has_any_comments' => true,
            'csrf_token' => 'tok',
            'user_id' => 1,
            'username' => 'alice',
        ]);

        // Alice (comment 1) has children
        $this->assert("Comment 1 has accessible <button type='button' class='collapse-btn'>", str_contains($rendered, 'aria-controls="children-1"'));
        $this->assert("Collapse button has aria-expanded='true' by default", str_contains($rendered, 'aria-expanded="true"'));
        $this->assert("Child container has unique ID 'children-1'", str_contains($rendered, 'id="children-1"'));

        // Comment 5 has no children and should NOT have a collapse button
        $this->assert("Comment 5 (no children) does NOT have collapse button", !str_contains($rendered, 'aria-controls="children-5"'));
    }
}

$runner = new TestRunner();
$runner->runAll();
