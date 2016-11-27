<?php 

	include_once 'Communication.php';
	include_once '[path-to]/vendor/phpmailer/phpmailer/PHPMailerAutoload.php'; //'PHPMailer.php';
	require_once 'CommunicationException.php';
	
	/**
	 * Klasse til at sende mails 
	 * ----------------------
	 * *** Metoder/funktioner ***
	 * -----------------
	 * Constructor: 		Initialisering af instancen med arrays/kø til mail samt et til at indeholde evt. fejl
	 *
	 * Public funktioner:
	 * ---------
	 * newMessage: 		Opretter en ny mail
	 * getMails:		Returnerer alle oprettede mail objekter (alle mails der er i køen)
	 * setBody: 		Sætter brødteksten for den pågældende mail
	 * setSubject: 		Sætter emne-feltet for den pågældende mail
	 * setAttachment:	Sætter vedhæftninger til den pågælgende mail
	 * setFrom:			Sætter hvilken mail-adresse og navn, mailen bliver sendt fra
	 * setCC:			Sætter folk på mailen i CC
	 * setBCC:			Sætter folk på mailen i BCC
	 * send: 			Sender alle mails i køen
	 * getErrors:		Returnerer en string med den specificerede fejl, hvis afsendelse af mails fejler
	 * deleteMail:		Fjerner/sletter en mail fra køen
	 *
	 * Private funktioner:
	 * ---------
	 * setupMail: 			    	En funktion som tilføjer det mest basale til mail-objektet (om der er SSL forbindelse, SMTP-host og login mm.)
	 * validateArgs:	  		    Validerer alle argumenter som addMail får 
	 * validateArrayStructure:		Hjælper med valideringen. Validerer arrays for korrekt struktur/opbygning
	 * validateAttachment:  		Validerer om array med vedhæftninger er korrekt
	 *
	 * -----------------
	 * *** Guide/Tutorial ***
	 * -----------------
	 *
	 * 
	 */
	class Mails implements Communication
	{
		//Konstanter til SMTP forbindelse
		const SMTP_HOST="";  	// Host/adresse til SMTP serveren                //"127.0.0.1" //"smtp.localhost";	
		const SMTP_PORT=25;					// Port til SMTP serveren
		const SMTP_LOGIN="";	// Login/brugernavn til SMTP serveren
		const SMTP_PASS="";	// Adgangskode til SMTP serveren
		const SMTP_AUTH=True; 

		function __construct(){
			$this->mails = array();
			$this->Errors = array();
		}

		/**
		 * Opretter en ny mail 
		 * @param  string/int  $label      En label som identificere mailen i køen. Gør at man nemt kan finde mailen igen
		 * @param  array  	   $from       Et array med afsender af mailen. Der kan kun være én afsendere pr. mail. 
		 *                                 Arrayet skal have længden 2: Første position til mail-adresse på personen. Anden position til navnet på personen
		 *                                 Eks:
		 *                                		array("mail@påafsender.dk"), "Navn på afsender")
		 *      
		 * @param  array       $to         Et array med modtagere af mailen. Kan være tom, hvis '$bcc' ikke er det
		 *                                 Arrayet skal indeholde sub-arrays med længden 2:
		 *                                 Første position til mail-adresse på personen. Anden position til navnet på personen
		 *                                 Eks.:
		 *                                		array(
		 *                                			  array("mail@påmodtager.dk"), "Navn på modtager"), 
		 *                                			  array("mail@påandenmodtager.dk", "Navn på anden modtager")
		 *                                			  )
		 *                                			                
		 * @param  string      $body       Brødteksten til mailen
		 * @return int Returnere antallet af emails der er i køen
		 */
		public function newMessage($label, $from, $to, $body){
			$label = strtolower($label);
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("'label' er ikke en string eller et tal", 10);	
			}
			if (array_key_exists($label, $this->mails)){
				throw new CommunicationException("Følgende label findes allerede: <br>'" . $label . "'<br>Du har allerede oprettet denne mail", 11);
			}
			if (is_string($from) || sizeof($this->validateArrayStructure($from))>0 || sizeof($from)<>2) {
				throw new CommunicationException("Der kan umiddelbart være 3 mulige grunde til den fejler: <br> (1) - 'from' er en string <br> (2) - 'from' har under-arrays<br> (3) - 'from' har ikke en størrelse på 2. <br> -----------------<br>'from' skal have følgende struktur: array('mail på afsender', 'navn på afsender)", 12);
			} 
			if (is_string($to) || sizeof($this->validateArrayStructure($to))!=sizeof($to)) { 
				throw new CommunicationException("Der kan umiddelbart være 2 mulige grunde til den fejler: <br> (1) - 'to' er en string <br> (2) - Et af elementerne i 'to', er ikke et under-array<br> -----------------<br>'to' skal have følgende struktur: array(array('mail på modtager1', 'navn på modtager1), array('mail på modtager2', 'navn på modtager2), ...)", 13);
			} 
						
			if (!is_string($body)){
				throw new CommunicationException("Body er ikke en string", 14);	
			}
			echo "Mail - newMessage\n";
			$mail = new PHPMailer();
			//$this->validateArgs($label, $to, $from, $subject, $body, $bcc, $attachment);
			$this->setupMail($mail);

			$mail->setFrom($from[0], $from[1]);
			foreach ($to as $mailName) {
				$mail->addAddress($mailName[0], $mailName[1]);
			}
			
			
			$mail->Body = $body;			
			$mail->AltBody = $mail->html2text($body); //Alternativ body til klienter der ikke understøtter html. 
			
			$mail->isHTML(true);
			$this->mails[$label] = $mail;
			return sizeof($this->mails);	
		}

		/**
		 * Tilføjer vigtige men konstante informationer, til hver mail. F.eks. information om SMTP-serveren. 
		 * Bruger de konstante værdier i toppen af klassen
		 * @param  PHPMailer-objekt $mail En mail repræsenteret som PHPMailer-objekt
		 */
		private function setupMail($mail){
			$mail->isSMTP(); 	//Siger at den bruger SMTP
			$mail->Host = self::SMTP_HOST;                         
			$mail->Port = self::SMTP_PORT;
			$mail->SMTPAuth = self::SMTP_AUTH;
			$mail->Username = self::SMTP_LOGIN;
			$mail->Password = self::SMTP_PASS;
		}

		
		
		/**
		 * Sætter et emne på en mail. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * @param string/int $label  	Identifikationen på den ønskede mail
		 * @param string     $subject 	Emnet på mailen
		 */
		public function setSubject($label, $subject){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			if (!is_string($subject)){
				throw new CommunicationException("Subject er ikke en string", 16);	
			}
			$this->mails[$label]->Subject = $subject;
		}
		/**
		 * Sætter en mdtager på i BCC på en mail. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * @param string/int $label        Identifikationen på den ønskede mail
		 * @param array      $bccAdresses  Et array med modtagere af mailen, som skal i BCC. Arrayet skal indeholde sub-arrays med længden 2:
		 *                                 Første position til mail-adresse på personen. Anden position til navnet på personen
		 *                                 Eks.:
		 *                                		array(
		 *                                			  array("mail@påmodtager.dk"), "Navn på modtager"), 
		 *                                			  array("mail@påandenmodtager.dk", "Navn på anden modtager")
		 *                                			  )
		 */
		public function setBCC($label, $bccAdresses){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			if (is_string($bccAdresses) || sizeof($this->validateArrayStructure($bccAdresses))>0 || sizeof($bccAdresses)<>2) {
				throw new CommunicationException("Der kan umiddelbart være 2 mulige grunde til den fejler: <br> (1) - 'to' er en string <br> (2) - Et af elementerne i 'to', er ikke et under-array<br> -----------------<br>'to' skal have følgende struktur: array(array('mail på modtager1', 'navn på modtager1), array('mail på modtager2', 'navn på modtager2), ...)", 17);
			}
			foreach ($bccAdresses as $mailName) {
				$this->mails[$label]->addBCC($mailName[0], $mailName[1]);
			}
		}

		/**
		 * Validere et array-argument fra validateArgs
		 * Funktionen tjekker om et array har sub-arrays som har en længde på 2. 
		 * @param  array $arr Arrayet som skal valideres
		 * @return array      Array med kun de elementer der opfylder valideringen
		 */
		private function validateArrayStructure($arr){
			return array_filter($arr, function($array_elem){return is_array($array_elem) && sizeof($array_elem)==2;});
		}


		
		/**
		 * Sætter en modtagere på en mail i CC. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * @param string/int $label  	  Identifikationen på den ønskede mail
		 * @param array      $ccAdresses  Et array med modtagere af mailen. 
		 *                                Arrayet skal indeholde sub-arrays med længden 2:
		 *                                Første position til mail-adresse på personen. Anden position til navnet på personen
		 *                                Eks.:
		 *                                		array(
		 *                                			  array("mail@påmodtager.dk"), "Navn på modtager"), 
		 *                                			  array("mail@påandenmodtager.dk", "Navn på anden modtager")
		 *                                			  )
		 */
		public function setCC($label, $ccAdresses){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			if (is_string($ccAdresses) || sizeof($this->validateArrayStructure($ccAdresses))>0 || sizeof($ccAdresses)<>2) {
				throw new CommunicationException("Der kan umiddelbart være 2 mulige grunde til den fejler: <br> (1) - 'to' er en string <br> (2) - Et af elementerne i 'to', er ikke et under-array<br> -----------------<br>'to' skal have følgende struktur: array(array('mail på modtager1', 'navn på modtager1), array('mail på modtager2', 'navn på modtager2), ...)", 17);
			}
			foreach ($ccAdresses as $mailName) {
				$this->mails[$label]->addCC($mailName[0], $mailName[1]);
			}
		}

		/**
		 * Vedhæfter filer til en mail. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * Arrayet med de ønskede filer, bliver valideret gennem funktionen validateAttachment
		 * @param string/int $label   		Identifikationen på den ønskede mail
		 * @param array      $attachment    Array med ønskede filer som skal vedhæftes. 
		 *                                  Arrayet skal være associative med stien til filen inkl. navnet på filen som key, og navnet på filen som værdi:
		 *                                  OBS: de to navne på filen behøver ikke at være ens
		 *                                  Eks.:
		 *                                		array("sti/til/fil/inkl/navn.pdf" => "evt et andet navn på fil.pdf")
		 */
		public function setAttachement($label, $attachment){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			$this->validateAttachment($attachment);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			foreach ($attachment as $pathFile => $filename) {
				$this->mails[$label]->addAttachment($pathFile, $filename, 'base64', mime_content_type($pathFile));
			}
		}


		/**
		 * Validerer array med ønskede filer til vedhæftning. 
		 * Tjekker om arrayet har den korekte struktur, samt om filerne findes
		 * @param array    $attachment    Array med ønskede filer som skal vedhæftes. 
		 *                                Arrayet skal være associative med stien til filen inkl. navnet på filen som key, og navnet på filen som værdi:
		 *                                OBS: de to navne på filen behøver ikke at være ens
		 *                                Eks.:
		 *                                		array("sti/til/fil/inkl/navn.pdf" => "evt et andet navn på fil.pdf")
		 */
		private function validateAttachment($attachment){
			if (sizeof($attachment)>0){
				if (sizeof(array_keys($attachment)) == 0 || count(array_filter(array_keys($attachment), 'is_string')) != sizeof(array_keys($attachment))){
					throw new CommunicationException("Der kan umiddelbart være 3 mulige grunde til den fejler: <br> (1) - 'attachment' er ikke et associative array overhovet<br> (2) - 'attachment' er et associative array, men et eller fleres key er ikke en string <br> (3) - Der er en eller flere værdier (fileName) i 'attachment' der ikke har en key<br> -----------------<br>'attachment' skal have følgende struktur: array('pathToFileInclFile' => 'fileName', ...) <br> 'fileName' og filnavnet i keyen behøver ikke at være ens. Extention skal være med i både key og value.", 18);
				}
				if (count(array_filter(array_values($attachment), 'is_string')) != sizeof(array_keys($attachment))) {
					throw new CommunicationException("Der kan umiddelbart være 1 mulig grund til den fejler: <br> (1) - Værdierne (fileName) i 'attachment' er ikke alle strings <br> -----------------<br>'attachment' skal have følgende struktur: array('pathToFileInclFile' => 'fileName', ...) <br> 'fileName' og filnavnet i keyen behøver ikke at være ens. Extention skal være med i både key og value.", 19);
				}
			}
			foreach ($attachment as $pathFile => $filename) {
				if (file_exists($pathFile) === False) {
					throw new CommunicationException("filen '" . $pathFile . "' findes ikke", 20);
				}
			}
		}


		/**
		 * Returnerer køen af mails
		 * @return array Array af PHPMailer-objekter
		 */
		public function getMails(){
			return $this->mails;
		}
		/**
		 * Sætter en afsender på en mail. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * @param string/int $label  	Identifikationen på den ønskede mail
		 * @param array      $from      Et array med afsender af mailen. Der kan kun være én afsendere pr. mail. 
		 *                              Arrayet skal have længden 2: Første position til mail-adresse på personen. Anden position til navnet på personen
		 *                              Eks:
		 *                             		array("mail@påafsender.dk"), "Navn på afsender")
		 */
		public function setFrom($label, $from){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			if (is_string($from) || sizeof($this->validateArrayStructure($from))>0 || sizeof($from)<>2) {
				throw new CommunicationException("Der kan umiddelbart være 3 mulige grunde til den fejler: <br> (1) - 'from' er en string <br> (2) - 'from' har under-arrays<br> (3) - 'from' har ikke en størrelse på 2. <br> -----------------<br>'from' skal have følgende struktur: array('mail på afsender', 'navn på afsender)", 21);
			}
			$this->mails[$label]->setFrom($from[0], $from[1]);
		}


		

		/**
		 * Sætter en brødtekst på en mail. Mailen bliver identificeret ud fra $label, hvilket vil sige at mailen skal være i køen. Hvis ikke, smides der en exception
		 * @param string/int $label Identifikationen på den ønskede mail
		 * @param string     $body  Brødteksten på mailen
		 */
		public function setBody($label, $body){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et talf", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			if (!is_string($body)){
				throw new CommunicationException("Body er ikke en string", 14);	
			}
			$this->mails[$label]->Body = $body;
		}


		/**
		 * Sender alle mailsne i køen
		 * @return boolean Fortæller om en eller flere mails fejlede ved afsendele (False), eller om alt gik godt (True)
		 */
		public function send(History $hist=null){
			echo "Mail - send\n";
			$success = True;
			$numberFaults = 0;
			foreach ($this->mails as $label => $mail) {
				if (!$mail->send()){  //hvis afsendelse af mailen gik galt
					$success = False;
					$numberFaults++;
				    $this->Errors['labels'][$label] = $mail->ErrorInfo;
				}
			}
			if (!$success){ //hvis afsendelse af en eller flere mails gik galt
				$this->Errors['number_fault'] = $numberFaults;
				$this->Errors['number_of_mails'] = (sizeof($labels)>0) ? sizeof($labels) : sizeof($this->mails);
				//Specificere hvornår fejlen opstod
				date_default_timezone_set("Europe/Copenhagen");
				$info = getdate();
				$this->Errors['time'] = $info['mday'] . "/" . $info['mon'] . "-" . $info['year'] . " kl. " . $info['hours'] . "." . $info['minutes'] . "." . $info['seconds'];
			}
			return $success;
		}

		/**
		 * Returnere en formateret HTML string med de seneste opstået fejl
		 * @return string Formateret HTML string med de seneste opstået fejl
		 */
		public function getErrors(){
			if (sizeof($this->Errors)==0) {
				return "";
			}
			$errors = "<u><h3>Der opstod fejl ved afsendelse af mails</h3></u>Fejlen opstod <b>" . $this->Errors['time'] . "</b> og berørte <b>" . $this->Errors['number_fault'] . "</b> ud af " . $this->Errors['number_of_mails'] . " mails. <br><br>" . "Labels og fejlinfo for de berørte er følgende:<br>";
			$i = 1;
			foreach ($this->Errors['labels'] as $label => $errorInfo) {
				$errors .= "<b>" . $i . " - " . $label . "</b><br>&nbsp;&nbsp;&nbsp;&nbsp;" . $errorInfo . "<br><br>";
				$i++;
			}
			return $errors;
		}

		/**
		 * Sletter en mail i køen. Mailen bliver identificeret ud fra $label
		 * @param string/int $label  Identifikationen på den ønskede mail, som skal slettes
		 */
		public function deleteMail($label){
			if (!is_string($label) && !is_int($label)){
				throw new CommunicationException("Label er ikke en string eller et tal", 10);
			}
			$label = strtolower($label);
			if (!array_key_exists($label, $this->mails)){
				throw new CommunicationException("Label '" . $label . "' findes ikke", 15);
			}
			unset($this->mails[$label]);			
		}


	} //end Mails class


 ?>