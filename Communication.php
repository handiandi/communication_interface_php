<?php 


	interface Communication{


		public function newMessage($label, $to, $from, $body);
		
		public function send(History $hist=null);
	}


	

?>