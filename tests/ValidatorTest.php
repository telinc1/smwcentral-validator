<?php

use PHPUnit\Framework\TestCase;

use SMWCentral\Validation\{ITokenProvider, Validator};

final class ValidatorTest extends TestCase
{
	protected function tearDown(): void
	{
		Validator::$tokenProvider = null;
	}
	
	public function testValidatesToken(): void
	{
		Validator::configure(null, new class implements ITokenProvider
		{
			public function getTokenKey(Validator $validator): string
			{
				return 'token';
			}
			
			public function getTokenValue(Validator $validator): string
			{
				return 'foo';
			}
		});
		
		$validator = new Validator(['token' => 'bar']);
		
		$this->assertTrue($validator->passes(false));
		$this->assertFalse($validator->passes());
	}
	
	public function testGetValue(): void
	{
		$validator = new Validator([
			'shallow' => 'Shallow value',
			'nested' => [
				'value' => 'Nested value'
			]
		]);
		
		$this->assertSame('Shallow value', $validator->getValue('shallow'));
		$this->assertSame('Nested value', $validator->getValue('nested.value'));
		
		$this->assertNull($validator->getValue('doesnt_exist'));
		$this->assertNull($validator->getValue('nested.doesnt_exist'));
		
		$this->assertSame('Default value', $validator->getValue('doesnt_exist', 'Default value'));
		$this->assertSame('Default value', $validator->getValue('nested.doesnt_exist', 'Default value'));
	}
	
	public function testRetrievesRequiredVariable(): void
	{
		$validator = new Validator(['foo' => 'bar']);
		
		$this->assertSame('bar', $validator->retrieve('foo', null)->value());
		$this->assertTrue($validator->passes());
		
		$this->assertNull($validator->retrieve('doesnt_exist', null)->value());
		$this->assertFalse($validator->passes());
	}
	
	public function testRetrievesOptionalVariable(): void
	{
		$validator = new Validator(['foo' => 'bar']);
		
		$this->assertSame('default', $validator->retrieve('doesnt_exist', 'default')->value());
		$this->assertTrue($validator->passes());
	}
}
