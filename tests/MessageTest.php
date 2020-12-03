<?php

use PHPUnit\Framework\TestCase;

use SMWCentral\Validation\{IMessageResolver, Message};

final class MessageTest extends TestCase
{
	protected function setUp(): void
	{
		Message::$resolver = new class implements IMessageResolver
		{
			public function resolveMessage(string $field, string $key, array $args): string
			{
				return "{$field}: {$key}";
			}
		};
	}
	
	protected function tearDown(): void
	{
		Message::$resolver = null;
	}
	
	public function testResolveMessageKey(): void
	{
		$message = new Message('foo', 'bar');
		$this->assertSame('foo: bar', $message->getTranslatedMessage());
	}
	
	public function testCastToString(): void
	{
		$message = new Message('foo', 'bar');
		$this->assertSame($message->getTranslatedMessage(), (string)$message);
	}
	
	public function testSerializeToJson(): void
	{
		$message = new Message('foo', 'bar');
		
		$this->assertJsonStringEqualsJsonString(
			json_encode($message->getTranslatedMessage()),
			json_encode($message)
		);
	}
}
