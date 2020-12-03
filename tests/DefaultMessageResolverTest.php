<?php

use PHPUnit\Framework\TestCase;

use SMWCentral\Validation\DefaultMessageResolver;

final class DefaultMessageResolverTest extends TestCase
{
	public function testPullsMessage(): void
	{
		$resolver = new DefaultMessageResolver();
		$this->assertResolvesTo('Invalid token. Please try again.', 'foo', 'token');
	}
	
	public function testReturnsKeyAsFallback(): void
	{
		$resolver = new DefaultMessageResolver();
		$this->assertResolvesTo('doesnt_exist', 'foo', 'doesnt_exist');
	}
	
	public function testReplacesFieldName(): void
	{
		$this->assertResolvesTo('The name is required.', 'name', 'required');
	}
	
	public function testReplacesArgs(): void
	{
		$this->assertResolvesTo('The name may not have more than 5 characters.', 'name', 'max.string', ['max' => 5]);
	}
	
	public function testCustomFieldOverridesDefault(): void
	{
		$this->assertResolvesTo(
			'The weirdly named field is required.',
			'available_walruses',
			'required',
			['field' => 'weirdly named field']
		);
	}
	
	protected function assertResolvesTo(string $expected, string $field, string $key, array $args = []): void
	{
		$resolver = new DefaultMessageResolver();
		$this->assertSame($expected, $resolver->resolveMessage($field, $key, $args));
	}
}
