# PHP - A communication interface to send sms and mails
A comunication interface in PHP, using Factory design pattern. Can easily be extended to other communication channels, like maybe facebook.


## Notice
Sorry for the danish in files for the documentation. 
This 'project' is used in a volunteer matter. I have only uploaded it to git to share the basic idea with the design and to share it for in curriculum vitae purposes
I don't know how often I will be updating the code

Sms using gatewayapi (www.gatewayapi.com) for sending sms, while Mails using whatever SMTP-server you have. 

Settings (login, API-key, ect.) is hardcoded (and of cause removed in these files). So for using the setup, you have to fill these out first. 

## Quick guide

### Mail

```php
include_once 'CommunicationFactory.php';
include_once 'CommunicationException.php';

$recipients = ["rec1@test2.com", "res2@test2.com"];
$bodyMessage = "Hello you to. <br><br> See U tomorrow";
$mails = CommunicationFactory::build("mail");
$files = ["path/to/file.pdf" => "filename.pdf",
		  "path/to/another/file.pdf" => "file.pdf"];

try {
	$mails->newMessage("identifier", "test@test.com", $recipients, $bodyMessage);
	$mails->setSubject("identifier", "This is a subject");
	$mails->setAttachement("identifier", $files);
} catch(CommunicationException $e){
	echo "Something went wrong: " . $e->getMessage();	
}

try {
	$mails->send(); //Sending all mails 
} catch(CommunicationException $e){
	echo "Something went wrong while sending mails: " . $e->getMessage();	
}

```

### SMS

```php
include_once 'CommunicationFactory.php';
include_once 'CommunicationException.php';

$recipients = [45xxxxxxxx, 45xxxxxxxx]; //45 is for Denmark
$bodyMessage = "Test of sms!";

$sms = CommunicationFactory::build("sms");
try{
	$sms->newMessage("identifier", "45xxxxxxxx", $recipients, $bodyMessage);
	$sms->send(); //Sending all sms 

} catch (CommunicationException $e){
	echo "Something went wrong: " . $e->getMessage();	
}

```

## Dependencies
- guzzle-oauth-subscriber
- PHPMailer