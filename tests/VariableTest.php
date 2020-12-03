<?php

use PHPUnit\Framework\TestCase;

use SMWCentral\Validation\{IMessageResolver, Message, Validator, Variable};

final class VariableTest extends TestCase
{
	protected function setUp(): void
	{
		Validator::configure(new class implements IMessageResolver
		{
			public function resolveMessage(string $field, string $message, array $args): string
			{
				return empty($args)
					? $message
					: "{$message} - " . implode(',', array_map(function($key, $value)
					{
						return "{$key}={$value}";
					}, array_keys($args), $args));
			}
		});
	}
	
	protected function tearDown(): void
	{
		Message::$resolver = null;
	}
	
	public function testRequired(): void
	{
		$this->assertValidationError('required', $this->variable(null)->required(), null);
		$this->assertValidationError('required', $this->variable('')->required(), '');
		$this->assertValidationError('required', $this->variable('   ')->required(), '   ');
		$this->assertValidationError('required', $this->variable([])->required(), []);
	}
	
	public function testIgnoreRulesIfRequiredFails(): void
	{
		$this->assertValidationErrors(
			['required'],
			$this->variable([])->required()->size(1),
			[]
		);
	}
	
	public function testDefaultValue(): void
	{
		$this->assertPassesValidation($this->variable(null)->default('fallback'), 'fallback');
		$this->assertPassesValidation($this->variable('')->default('fallback'), 'fallback');
		$this->assertPassesValidation($this->variable('   ')->default('fallback'), 'fallback');
		$this->assertPassesValidation($this->variable([])->default('fallback'), 'fallback');
	}
	
	public function testTypeRuleString(): void
	{
		$this->assertPassesValidation($this->variable(null)->string(), null);
		$this->assertPassesValidation($this->variable(true)->string(), '1');
		$this->assertPassesValidation($this->variable(1)->string(), '1');
		$this->assertPassesValidation($this->variable('bar')->string(), 'bar');
		$this->assertValidationError('string', $this->variable([])->string(), '');
	}
	
	public function testTypeRuleInteger(): void
	{
		$this->assertPassesValidation($this->variable(null)->integer(), null);
		$this->assertPassesValidation($this->variable(1)->integer(), 1);
		$this->assertPassesValidation($this->variable(1.1)->integer(), 1);
		$this->assertPassesValidation($this->variable('1.1')->integer(), 1);
		$this->assertValidationError('integer', $this->variable(true)->integer(), 0);
		$this->assertValidationError('integer', $this->variable([])->integer(), 0);
	}
	
	public function testTypeRuleFloat(): void
	{
		$this->assertPassesValidation($this->variable(null)->float(), null);
		$this->assertPassesValidation($this->variable(1)->float(), 1.0);
		$this->assertPassesValidation($this->variable(1.1)->float(), 1.1);
		$this->assertPassesValidation($this->variable('1.1')->float(), 1.1);
		$this->assertValidationError('float', $this->variable(true)->float(), 0.0);
		$this->assertValidationError('float', $this->variable([])->float(), 0.0);
	}
	
	public function testTypeRuleBoolean(): void
	{
		foreach(['yes', 'on', '1', 1, true, 'true'] as $truthy)
		{
			$this->assertPassesValidation($this->variable($truthy)->boolean(), true);
		}
		
		$this->assertPassesValidation($this->variable(null)->boolean(), false);
		$this->assertPassesValidation($this->variable(false)->boolean(), false);
		$this->assertPassesValidation($this->variable('bar')->boolean(), false);
		$this->assertPassesValidation($this->variable(-1)->boolean(), false);
	}
	
	public function testTypeRuleArray(): void
	{
		$this->assertPassesValidation($this->variable(null)->array(), null);
		$this->assertPassesValidation($this->variable([])->array(), []);
		$this->assertPassesValidation($this->variable(['bar', 1, false])->array(), ['bar', 1, false]);
		$this->assertValidationError('array', $this->variable(true)->array(), []);
		$this->assertValidationError('array', $this->variable('bar')->array(), []);
		$this->assertValidationError('array', $this->variable(0)->array(), []);
	}
	
	public function testRuleBetween(): void
	{
		$this->assertPassesValidation($this->variable(5)->integer()->between(4, 6), 5);
		$this->assertValidationError('between.number - min=4,max=6', $this->variable(3)->integer()->between(4, 6), 3);
		$this->assertValidationError('between.number - min=4,max=6', $this->variable(8)->integer()->between(4, 6), 8);
		
		$this->assertPassesValidation($this->variable('foo')->string()->between(2, 3), 'foo');
		$this->assertValidationError('between.string - min=2,max=3', $this->variable('a')->string()->between(2, 3), 'a');
		$this->assertValidationError('between.string - min=2,max=3', $this->variable('long string')->string()->between(2, 3), 'long string');
		
		$this->assertPassesValidation($this->variable([1, 2])->array()->between(2, 3), [1, 2]);
		$this->assertValidationError('between.array - min=2,max=3', $this->variable([1])->array()->between(2, 3), [1]);
		$this->assertValidationError('between.array - min=2,max=3', $this->variable([1, 2, 3, 4])->array()->between(2, 3), [1, 2, 3, 4]);
	}
	
	public function testRuleBetweenRejectsAmbiguousType(): void
	{
		$this->expectException(LogicException::class);
		$this->variable('foo')->between(4, 6);
	}
	
	public function testRuleMax(): void
	{
		$this->assertPassesValidation($this->variable(5)->integer()->max(6), 5);
		$this->assertValidationError('max.number - max=6', $this->variable(8)->integer()->max(6), 8);
		
		$this->assertPassesValidation($this->variable('foo')->string()->max(3), 'foo');
		$this->assertValidationError('max.string - max=3', $this->variable('long string')->string()->max(3), 'long string');
		
		$this->assertPassesValidation($this->variable([1, 2])->array()->max(3), [1, 2]);
		$this->assertValidationError('max.array - max=3', $this->variable([1, 2, 3, 4])->array()->max(3), [1, 2, 3, 4]);
	}
	
	public function testRuleMaxRejectsAmbiguousType(): void
	{
		$this->expectException(LogicException::class);
		$this->variable('foo')->max(6);
	}
	
	public function testRuleMin(): void
	{
		$this->assertPassesValidation($this->variable(5)->integer()->min(4), 5);
		$this->assertValidationError('min.number - min=4', $this->variable(3)->integer()->min(4), 3);
		
		$this->assertPassesValidation($this->variable('foo')->string()->min(2), 'foo');
		$this->assertValidationError('min.string - min=2', $this->variable('a')->string()->min(2), 'a');
		
		$this->assertPassesValidation($this->variable([1, 2])->array()->min(2), [1, 2]);
		$this->assertValidationError('min.array - min=2', $this->variable([1])->array()->min(2), [1]);
	}
	
	public function testRuleMinRejectsAmbiguousType(): void
	{
		$this->expectException(LogicException::class);
		$this->variable('foo')->min(4);
	}
	
	public function testRuleSize(): void
	{
		$this->assertPassesValidation($this->variable(4)->integer()->size(4), 4);
		$this->assertValidationError('size.number - size=4', $this->variable(3)->integer()->size(4), 3);
		
		$this->assertPassesValidation($this->variable('foo')->string()->size(3), 'foo');
		$this->assertValidationError('size.string - size=3', $this->variable('ab')->string()->size(3), 'ab');
		$this->assertValidationError('size.string - size=3', $this->variable('abcd')->string()->size(3), 'abcd');
		
		$this->assertPassesValidation($this->variable([1, 2])->array()->size(2), [1, 2]);
		$this->assertValidationError('size.array - size=2', $this->variable([1])->array()->size(2), [1]);
	}
	
	public function testRuleSizeRejectsAmbiguousType(): void
	{
		$this->expectException(LogicException::class);
		$this->variable('foo')->size(3);
	}
	
	public function testRuleIn(): void
	{
		$this->assertPassesValidation($this->variable('one')->string()->in(['one', 'two']), 'one');
		$this->assertPassesValidation($this->variable('two')->string()->in(['one', 'two']), 'two');
		$this->assertValidationError('in - values=one, two', $this->variable('foo')->string()->in(['one', 'two']), 'foo');
	}
	
	public function testRuleInRejectsAmbiguousType(): void
	{
		$this->expectException(LogicException::class);
		$this->variable('foo')->in(['one', 'two']);
	}
	
	public function testIgnoreRulesIfTypeFails(): void
	{
		$this->assertValidationErrors(
			['string'],
			$this->variable([])->string()->size(1),
			''
		);
		
		$this->assertValidationErrors(
			['integer'],
			$this->variable([])->integer()->size(1),
			0
		);
		
		$this->assertValidationErrors(
			['float'],
			$this->variable([])->float()->size(1),
			0.0
		);
		
		$this->assertValidationErrors(
			['array'],
			$this->variable('bar')->array()->size(1),
			[]
		);
	}
	
	public function testStackErrorsFromManyRules(): void
	{
		$this->assertValidationErrors(
			[
				'size.string - size=1',
				'in - values=one, two'
			],
			$this->variable('some very long string')->string()->size(1)->in(['one', 'two']),
			'some very long string'
		);
	}
	
	public function testBailIfRuleFails(): void
	{
		$this->assertValidationErrors(
			['size.string - size=1'],
			$this->variable('some very long string')->bail()->string()->size(1)->in(['one', 'two']),
			'some very long string'
		);
	}
	
	protected function variable($value, array $args = []): Variable
	{
		return new Variable(new Validator([]), 'field', $value, $args);
	}
	
	protected function assertPassesValidation(Variable $variable, $expectedValue): void
	{
		$property = new ReflectionProperty($variable, 'validator');
		$property->setAccessible(true);
		
		$validator = $property->getValue($variable);
		
		$this->assertTrue($validator->passes(), 'Did not pass validation.');
		$this->assertSame($expectedValue, $variable->value());
	}
	
	protected function assertValidationErrors(array $expectedErrors, Variable $variable, $expectedValue): void
	{
		$property = new ReflectionProperty($variable, 'validator');
		$property->setAccessible(true);
		
		$validator = $property->getValue($variable);
		
		$this->assertFalse($validator->passes());
		$this->assertSame($expectedErrors, array_map('strval', $validator->errors()->all()), 'Received unexpected errors.');
		$this->assertSame($expectedValue, $variable->value(), 'Received unexpected final value.');
	}
	
	protected function assertValidationError(string $expectedError, Variable $variable, $expectedValue): void
	{
		$this->assertValidationErrors([$expectedError], $variable, $expectedValue);
	}
}
