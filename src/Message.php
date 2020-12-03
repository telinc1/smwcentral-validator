<?php

namespace SMWCentral\Validation;

use JsonSerializable;

class Message implements JsonSerializable
{
	/**
	 * The resolver instance.
	 * 
	 * @var \SMWCentral\Validation\IMessageResolver|null
	 */
	public static $resolver = null;
	
	/**
	 * The field that this message belongs to.
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * The key for the contents of this message.
	 * 
	 * @var string
	 */
	protected $key;
	
	/**
	 * Additional args for the message.
	 * 
	 * @var array
	 */
	protected $args;
	
	/**
	 * Create a new message instance.
	 * 
	 * @param string $name
	 * @param string $key
	 * @param array $args
	 */
	public function __construct(string $name, string $key, array $args = [])
	{
		$this->name = $name;
		$this->key = $key;
		$this->args = $args;
	}
	
	/**
	 * Get the translated message contents.
	 * 
	 * @return string
	 */
	public function getTranslatedMessage(): string
	{
		if(self::$resolver === null)
		{
			self::$resolver = new DefaultMessageResolver();
		}
		
		return self::$resolver->resolveMessage($this->name, $this->key, $this->args);
	}
	
	/**
	 * Serialize message to JSON.
	 * 
	 * @return string
	 */
	public function jsonSerialize()
	{
		return $this->getTranslatedMessage();
	}
	
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getTranslatedMessage();
	}
}
