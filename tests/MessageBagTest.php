<?php

use PHPUnit\Framework\TestCase;

use SMWCentral\Validation\{IMessageResolver, Message, MessageBag};

final class MessageBagTest extends TestCase
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
	
	public function testAddStringMessage(): void
	{
		$bag = new MessageBag();
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$messages = $bag->all();
		
		$this->assertCount(3, $messages);
		$this->assertSame('one: foo', (string)$messages[0]);
		$this->assertSame('one: bar', (string)$messages[1]);
		$this->assertSame('two: baz', (string)$messages[2]);
	}
	
	public function testAddMessageInstance(): void
	{
		$bag = new MessageBag();
		$bag->add('one', new Message('one', 'foo'));
		$bag->add('one', new Message('one', 'bar'));
		$bag->add('two', new Message('two', 'baz'));
		
		$messages = $bag->all();
		
		$this->assertCount(3, $messages);
		$this->assertSame('one: foo', (string)$messages[0]);
		$this->assertSame('one: bar', (string)$messages[1]);
		$this->assertSame('two: baz', (string)$messages[2]);
	}
	
	public function testRejectsInvalidMessages(): void
	{
		$this->expectException(InvalidArgumentException::class);
		
		$bag = new MessageBag();
		$bag->add('one', true);
	}
	
	public function testCountAllMessages(): void
	{
		$bag = new MessageBag();
		
		$this->assertCount(0, $bag);
		
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$this->assertCount(3, $bag);
	}
	
	public function testGetAllMessagesAndExcludeField(): void
	{
		$bag = new MessageBag();
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$messages = $bag->except(['one']);
		
		$this->assertCount(1, $messages);
		$this->assertSame('two: baz', (string)$messages[0]);
	}
	
	public function testGetAllMessagesForField(): void
	{
		$bag = new MessageBag();
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$messages = $bag->get('two');
		
		$this->assertCount(1, $messages);
		$this->assertSame('two: baz', (string)$messages[0]);
	}
	
	public function testGetFirstMessageForField(): void
	{
		$bag = new MessageBag();
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$message = $bag->getFirst('one');
		
		$this->assertNotNull($message);
		$this->assertSame('one: foo', (string)$message);
		
		$this->assertNull($bag->getFirst('three'));
	}
	
	public function testCheckIfEmpty(): void
	{
		$bag = new MessageBag();
		
		$this->assertTrue($bag->isEmpty());
		
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$this->assertFalse($bag->isEmpty());
	}
	
	public function testSerializeToJson(): void
	{
		$bag = new MessageBag();
		$bag->add('one', 'foo');
		$bag->add('one', 'bar');
		$bag->add('two', 'baz');
		
		$this->assertJsonStringEqualsJsonString(
			json_encode([
				'one' => ['one: foo', 'one: bar'],
				'two' => ['two: baz']
			]),
			json_encode($bag)
		);
	}
}
