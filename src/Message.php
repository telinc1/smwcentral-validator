<?php

namespace SMWCentral\Validation;

use JsonSerializable;

// FIXME
use SMWCentral\Locale\Translator;

class Message implements JsonSerializable
{
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
	 * FIXME
	 */
	protected $fallback;
	
	/**
	 * Create a new message instance.
	 * 
	 * @param string $name
	 * @param string $key
	 * @param array $args
	 * @param bool $fallback
	 */
	public function __construct(string $name, string $key, array $args = [], bool $fallback = true)
	{
		$this->name = $name;
		$this->key = $key;
		$this->args = $args;
		$this->fallback = true;
	}
	
	/**
	 * Get the translated message contents.
	 * 
	 * @return string
	 */
	public function getTranslatedMessage(): string
	{
		$translator = Translator::get();
		
		$args = $this->args;
		$args['title'] = $translator->translate(["form.{$this->name}.title", "form.{$this->name}"]);
		
		$key = "form.{$this->name}.{$this->key}";
		
		if(!$this->fallback)
		{
			return $translator->translate($key, null, $args);
		}
		
		$message = $translator->translate($key, null, $args, null, false);
		
		return ($message === $key) ? $translator->translate("validation.{$this->key}", 'form', $args) : $message;
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
