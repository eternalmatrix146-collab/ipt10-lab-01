<?php

declare(strict_types=1);

namespace Tests\App;

use App\Services\AI\KnowledgeBase;
use App\Services\AI\OfflineAgent;
use App\Services\AI\Tool;
use App\Services\AI\ToolInput;
use App\Services\AI\ToolRegistry;
use App\Services\AI\ToolResult;
use Tests\TestCase;

/**
 * Rook's help-center knowledge base (resources/knowledge/faq.md).
 */
class KnowledgeBaseTest extends TestCase
{
    private function kb(): KnowledgeBase
    {
        return new KnowledgeBase(dirname(__DIR__, 2) . '/resources/knowledge/faq.md');
    }

    public function testQuestionsFindTheRightEntry(): void
    {
        $kb = $this->kb();
        $cases = [
            'Is shipping free?' => 'Shipping fees and free shipping',
            'how do I cancel my order' => 'Cancelling an order',
            'can I get a refund' => 'Returns and refunds',
            'I forgot my password' => 'Forgot password',
            'do you accept gcash' => 'Payment methods',
            'what does processing mean' => 'Order statuses',
            'can I pay in dollars' => 'Currencies and exchange rates',
            // the shop and Rook itself
            'who are you?' => 'Who is Rook?',
            'what can you do' => 'What Rook can and cannot do',
            'tell me about the company' => 'About Midnight Market',
            'where are you located' => 'About Midnight Market',
            'what do you sell' => 'What Midnight Market sells',
            'what is a heist' => 'Site guide and shop vocabulary',
            'why the thief theme' => 'The Midnight Market style and story',
            'do you save my chats' => 'How Rook handles your chats',
            'is this a real store' => 'Is Midnight Market a real store?',
            'who made this website' => 'Who built Midnight Market?',
        ];
        foreach ($cases as $question => $topic) {
            $this->assertSame($topic, $kb->search($question)[0]['topic'] ?? null, "for \"{$question}\"");
        }
    }

    public function testPlaceholdersAreFilledFromConfig(): void
    {
        $kb = $this->kb();
        $shipping = $kb->search('shipping fee')[0]['answer'];
        $this->assertStringContains(peso((float) config('app.shipping.flat_fee')), $shipping);
        $this->assertStringContains(peso((float) config('app.shipping.free_over')), $shipping);
        foreach ($kb->topics() as $topic) {
            $answer = $kb->search($topic, 1)[0]['answer'];
            $this->assertFalse(str_contains($answer, '{'), "unfilled placeholder in \"{$topic}\"");
        }
        $this->assertTrue(count($kb->topics()) >= 25);
    }

    public function testUnrelatedQuestionsReturnNothing(): void
    {
        $this->assertSame([], $this->kb()->search('xylophone quantum chromodynamics'));
        $this->assertSame([], $this->kb()->search('the and of'));
    }

    public function testOfflineRookAnswersPolicyQuestionsFromTheKnowledgeBase(): void
    {
        $kb = $this->kb();
        $registry = (new ToolRegistry())->register(new Tool(
            'search_knowledge_base',
            'Help center',
            ['properties' => ['query' => ['type' => 'string']], 'required' => ['query']],
            fn (ToolInput $in) => ToolResult::ok(['matches' => $kb->search($in->required('query'))])
        ));
        $result = (new OfflineAgent($registry, 'shop'))->run([], 'How much is shipping?');
        $this->assertSame('search_knowledge_base', $result->actions[0]['tool']);
        $this->assertStringContains('Shipping fees and free shipping', $result->reply);
    }
}
