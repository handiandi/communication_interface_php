<?php

	include_once 'Communication.php';
	require_once '[path-to]/guzzle-oauth-subscriber/vendor/autoload.php';
	require_once 'CommunicationException.php';
	/**
	 * Klasse til at sende sms 
	 * ----------------------
	 * *** Metoder/funktioner ***
	 * -----------------
	 * Constructor: 		Initialisering af instancen med array/kø til sms
	 *
	 * Public funktioner:
	 * ---------
	 * newMessage: 		Opretter en ny sms
	 * getSms:			Returnerer alle oprettede sms'er (alle sms'er der er i køen)
	 * setBody: 		Sætter brødteksten for den pågældende mail
	 * setFrom:			Sætter hvilken mail-adresse og navn, mailen bliver sendt fra
	 * send: 			Sender alle mails i køen
	 * getErrors:		Returnerer en string med den specificerede fejl, hvis afsendelse af mails fejler
	 * deleteSms:		Fjerner/sletter en sms fra køen
	 *
	 * Private funktioner:
	 * ---------
	 *
	 * -----------------
	 * *** Guide/Tutorial ***
	 * -----------------
	 *
	 * 
	 */
	class Sms implements Communication
	{
		const HOST 			   = "https://gatewayapi.com/rest/";
		const REST_BASE_URL    = "mtsms";
		const CONSUMER_KEY     = '';
		const CONSUMER_SECRET  = '';
		
		
		function __construct(){
			//Opretter diverse objekter til at kunne sende sms'er via API'et
			$this->stack = \GuzzleHttp\HandlerStack::create();
			$this->oauth_middleware = new \GuzzleHttp\Subscriber\Oauth\Oauth1([
			    'consumer_key'    => self::CONSUMER_KEY,
			    'consumer_secret' => self::CONSUMER_SECRET,
			    'token'           => '',
			    'token_secret'    => ''
			]);
			$this->stack->push($this->oauth_middleware);
			$this->client = new \GuzzleHttp\Client([
			   'base_uri' => self::HOST,
			   'handler'  => $this->stack,
			   'auth'     => 'oauth'
			]);
			

			$this->sms = []; //SMS kø
			$this->Errors = [];

		}
		/**
		 * Opretter en ny meddelelse/sms
		 * @param  int/string   $label En label for at kunne identificere sms i køen
		 * @param  string       $from  En string med hvem sms'en er fra. Kan være et nummer i string format
		 * @param  int/array    $to    Modtagere af sms'en. Numrene skal være med lande kode foran uden 00: 45xxxxxxxx.
		 * @param  string       $body  Selve sms beskeden
		 * @return int        		   Antal sms'er i køen
		 */
		public function newMessage($label, $from, $to, $body){
			if (is_array($to)){
				$to = array_unique($to);  //Der må ikke være duplicates af modtagere
			}
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);
			}			
			if (!is_string($from)){
				throw new CommunicationException("'from' er ikke en string", 12);				
			}
			if (is_string($from) && strlen($from)>11){
				throw new CommunicationException("'from' skal være under 12 tegn, når det er en string. Den er nu " . strlen($from) . " tegn lang ('" . $from . "')", 13);
			}
			if (!is_string($body)){
				throw new CommunicationException("'body' er ikke en string", 14);
				
			}
			$label = strtolower($label);
			if (array_search($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "'' findes allerede. Du har allerede oprettet denne sms", 11);
				
			}
			$sms_temp = [];
			$sms_temp['sender'] = $from;	   
			$sms_temp['message'] = $body;
			if (is_array($to)){
				$sms_temp['recipients'] =[];
				foreach ($to as $phoneNumberMsisdn) {
					array_push($sms_temp['recipients'], ['msisdn' => $phoneNumberMsisdn]);
				}
			} else {
				$sms_temp['recipients'] =[['msisdn' => $to]];							
			}

			$this->sms[$label] = $sms_temp;
			return sizeof($this->sms);
		}


		/**
		 * Returnerer sms-køen
		 * @return array sms-kø
		 */
		public function getSms(){
			return $this->sms;
		}

		/**
		 * Sender alle sms'erne i køen. Modtager et History objekt til at kunne gemme hvad der sker, i databasen. Se History.php for mere
		 * @param  History|null $hist History object. Håndtere at gemme i databasen, hvis givet
		 */
		public function send(History $hist=null){					
			foreach ($this->sms as $label => $sms) {
				try{
					$response = $this->client->post(self::REST_BASE_URL, ['json' => $sms]);
					if ($response->getStatusCode() != 200){ //Der er sket en fejl!!
						echo "Der skete en fejl!";
						$sms['status'] = 'Blev ikke sendt. Status kode: ' . $response->getStatusCode();

					} else {
						$sms['status'] = True;
					}
					$this->sms[$label] = $sms;
				} catch (\GuzzleHttp\Exception\ClientException $e) {
					$sms['status'] = 'Blev ikke sendt. Fejl kode: ' . $e->getCode() . ". Meddelelse:\n" . $e->getMessage();
					$this->sms[$label] = $sms;
				    echo $sms['status'];
				}
			}
			if (!is_null($hist)){
				$hist->saveHistory($this->sms); //Gemmer i databasen
			}
		}

		/**
		 * Returnerer sms-teksten for en label i køen
		 * @param  int/string $label Identifikation af den sms-besked der skal hentes tekst fra
		 * @return string            sms-teksten  
		 */
		public function getBody($label){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);	
			}
			if (!array_key_exists($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			return $this->sms[$label]['message']; 
		}

		/**
		 * Sætter sms-teksten for en pågældene sms
		 * @param int/string $label Identifikation af den sms-besked der skal sættes tekst for
		 * @param string     $body  Teksten til sms'en
		 */
		public function setBody($label, $body){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);	
			}
			if (!array_key_exists($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			$this->sms[$label]['message'] = $body; 
		}

		/**
		 * Sætter hvem sms'en er fra, for en pågældene sms
		 * @param int/string $label Identifikation af den sms-besked der skal sættes hvem sms'en er fra
		 * @param string     $from  Hvem sms'en er fra
		 */
		public function setFrom($label, $from){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);	
			}
			if (!array_key_exists($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			$this->sms[$label]['sender'] = $from; 
		}

		/**
		 * Returnerer hvem sms'en er fra, for en label i køen
		 * @param  int/string $label Identifikation af den sms-besked der skal hentes, hvem sms'en er fra
		 * @return string            Hvem sms'en er fra  
		 */
		public function getFrom($label){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);	
			}
			if (!array_key_exists($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			return $this->sms[$label]['sender']; 
		}	

		/**
		 * Sletter en sms i køen. Mailen bliver identificeret ud fra $label
		 * @param string/int $label  Identifikationen på den ønskede mail, som skal slettes
		 */
		public function deleteSms($label){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}			
			if (!array_key_exists($label, $this->sms)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			unset($this->sms[$label]);			
		}

		


	} //end of sms-class
?>