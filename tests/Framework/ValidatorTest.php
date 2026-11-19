<?php

declare(strict_types=1);

namespace Tests\Framework;

use Phantom\Exceptions\ValidationException;
use Phantom\Validation\Validator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidDataPassesAndIsTrimmed(): void
    {
        $data = Validator::make(
            ['email' => ' ana@example.com ', 'age' => '21', 'extra' => 'ignored'],
            ['email' => 'required|email', 'age' => 'required|integer|min:18']
        )->validate();
        $this->assertSame(['email' => 'ana@example.com', 'age' => '21'], $data);
    }

    public function testCollectsErrorsPerField(): void
    {
        $v = Validator::make(
            ['email' => 'nope', 'password' => 'short', 'password_confirmation' => 'other'],
            ['email' => 'required|email', 'password' => 'required|min:8|confirmed', 'name' => 'required']
        );
        $this->assertTrue($v->fails());
        $errors = $v->errors();
        $this->assertSame(['The email must be a valid email address.'], $errors['email']);
        $this->assertCount(2, $errors['password']);
        $this->assertSame(['The name field is required.'], $errors['name']);
    }

    public function testNumericRulesCompareValuesNotLength(): void
    {
        $this->assertTrue(Validator::make(['price' => '1500'], ['price' => 'numeric|max:2000'])->passes());
        $this->assertFalse(Validator::make(['price' => '2500'], ['price' => 'numeric|max:2000'])->passes());
        $this->assertTrue(Validator::make(['rating' => '5'], ['rating' => 'integer|between:1,5'])->passes());
        $this->assertFalse(Validator::make(['rating' => '6'], ['rating' => 'integer|between:1,5'])->passes());
    }

    public function testNullableSkipsOtherRulesWhenEmpty(): void
    {
        $this->assertTrue(Validator::make(['notes' => ''], ['notes' => 'nullable|min:10'])->passes());
        $this->assertFalse(Validator::make(['notes' => 'short'], ['notes' => 'nullable|min:10'])->passes());
    }

    public function testInAndRegexRules(): void
    {
        $this->assertTrue(Validator::make(['m' => 'cod'], ['m' => 'in:cod,gcash'])->passes());
        $this->assertFalse(Validator::make(['m' => 'bitcoin'], ['m' => 'in:cod,gcash'])->passes());
        $this->assertTrue(Validator::make(['p' => '0917 555 1234'], ['p' => ['regex:/^[0-9+()\-\s]{7,20}$/']])->passes());
    }

    public function testUniqueRuleQueriesTheDatabase(): void
    {
        $db = $this->useSqlite();
        $db->table('users')->insert(['name' => 'A', 'email' => 'taken@x.test', 'password' => 'x', 'created_at' => '', 'updated_at' => '']);
        $this->assertFalse(Validator::make(['email' => 'taken@x.test'], ['email' => 'unique:users,email'])->passes());
        $this->assertTrue(Validator::make(['email' => 'free@x.test'], ['email' => 'unique:users,email'])->passes());
    }

    public function testValidateThrowsWithOldInput(): void
    {
        $e = $this->assertThrows(ValidationException::class, fn () => Validator::make(['name' => ''], ['name' => 'required'])->validate());
        $this->assertSame(['name' => ''], $e->input());
    }
}
