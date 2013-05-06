<?php

	namespace Gisleburt\LazyData;
	
	/**
	 * Minor extension of the Exception class that stores a second message for public display.
	 * @author Daniel Mason
	 * @copyright DanielMason.com, 2012
	 * @version 1.0
	 * @package LazyData
	 */
	
	class Exception extends \Exception {
		
		const MESSAGE_BADCODE = 'Oh no! Found some bad code. The developers has been informed and we\'ll fix is as soon as possible';
		const MESSAGE_DATABASE = 'Oh no! We\'ve experianced a problem connecting to our database. Hopefully this problem is temporary, but just in case, we\'ve let the developers know.';
		
		/**
		 * A user safe explaination of the error.
		 * @var string
		 */
		protected $publicMessage;

		/**
		 * Create a new Exception
		 * @param string $message
		 * @param string|null $publicMessage
		 * @param Exception $previous
		 */
		public function __construct($message, $publicMessage = null, Exception $previous = null) {
			
			if($publicMessage)
				$this->publicMessage = $publicMessage;
			
			parent::__construct($message, 0, $previous);
			
		}
		
		/**
		 * Get the message to display to the user
		 * @return string (Might be null if a message wasn't set)
		 */
		public function getPublicMessage() {
			return $this->publicMessage;
		}
		
	}