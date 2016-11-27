<?php

	include_once 'Communication.php';
	include_once 'Sms.php';
	include_once 'Mails.php';
	include_once 'CommunicationException.php';
	
	/**
	* Kla
	*/
	class CommunicationFactory
	{
		
		public function build($type) {
			$com = NULL;	
			switch (strtolower($type)) {
				case 'sms':
					$com = new Sms();
					break;
				
				case 'mail':
					$com = new Mail();
					break;
				
				default:
					throw new CommunicationException("Communication-typen er ikke kendt", 0);
					break;
			}
			return $com;
		}
	}


?>