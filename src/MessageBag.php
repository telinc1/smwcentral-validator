<?php

namespace SMWCentral\Validation;

use Countable;
use JsonSerializable;
use InvalidArgumentException;

class MessageBag implements Countable, JsonSerializable
{
	/**
	 * The messages inside the bag.
	 * 
	 * @var array
	 */
	protected $messages;
	
	/**
	 * Create a new message bag instance.
	 * 
	 * @return void
	 */
	public function __construct()
	{
		$this->messages = [];
	}
	
	/**
	 * Add a message for a given field into the bag.
	 * 
	 * @param string $name
	 * @param \SMWCentral\Validation\Message|string $message
	 * @return \SMWCentral\Validation\Message
	 */
	public function add(string $name, $message): Message
	{
		if(is_string($message))
		{
			$message = new Message($name, $message);
		}
		elseif(!$message instanceof Message)
		{
			throw new InvalidArgumentException('Argument 2 must be a string or an instance of ' . Message::class);
		}
		
		$this->messages[$name][] = $message;
		return $message;
	}
	
	/**
	 * Get all messages in the bag.
	 * 
	 * @return array
	 */
	public function all(): array
	{
		return empty($this->messages) ? [] : call_user_func_array('array_merge', array_values($this->messages));
	}
	
	/**
	 * Get the number of messages in the bag.
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
	}
	
	/**
	 * Get all messages in the bag, excluding those for a set of fields.
	 * 
	 * @param array $keys
	 * @return array
	 */
	public function except(array $keys): array
	{
		$result = [];
		
		foreach($this->messages as $key => $messages)
		{
			if(!in_array($key, $keys))
			{
				foreach($messages as $message)
				{
					$result[] = $message;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * Get all messages for a field.
	 * 
	 * @param string $key
	 * @return array
	 */
	public function get(string $key): array
	{
		return $this->messages[$key] ?? [];
	}
	
	/**
	 * Get the first message for a field.
	 * 
	 * @param string $key
	 * @return \SMWCentral\Validation\Message|null
	 */
	public function getFirst(string $key): ?Message
	{
		$messages = $this->get($key);
		
		foreach($messages as $message)
		{
			return $message;
		}
		
		return null;
	}
	
	/**
	 * Check if the message bag is empty.
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}
	
	/**
	 * Serialize messages to JSON.
	 * 
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->messages;
	}
}
