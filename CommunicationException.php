<?php

	/**
		 * Exception class for Communication
		 * ---------------------------
		 * Errorcodes:
		 * -----------
		 * 10 - 29: Mail errors
		 * 30 - 49: SMS errors
		 */	
		

	
	class CommunicationException extends Exception {
		public function __construct($message, $code=0, Exception $previous=null){
			//make sure everything is assigned properly
			parent::__construct($message, $code, $previous);
		}

		public function __toString(){
			return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
		}

		public function getCustomMessage(){
			return $this->message;
		}

		public function getCustomCode(){
			return $this->code;
		}

		public function customFunction($value=''){
			echo "A custom function for this type of Exception\n";
		}

	} //end exception class

?>