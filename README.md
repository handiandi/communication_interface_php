# communication_interface_php
A comunication interface in PHP, using Factory design pattern


## Notice
Sorry for the danish in files. 
This 'project' is used in a volunteer matter. I have only uploaded it to git to share the basic idea with the design and to share it for in curriculum vitae purposes
I don't know how often I will be updating the code

Sms using gatewayapi (www.gatewayapi.com) for sending sms, while Mails using whatever SMTP-server you have. 

Settings (login, API-key, ect.) is hardcoded (and of cause removed in these files). So for using the setup, you have to fill these out first. 

## Quick guide

### Mail

```php
include_once 'CommunicationFactory.php';

$mails = CommunicationFactory::build("mail");
$recipients = [rec1@test2.com, res2@test2.com];
$bodyMessage = "Hello you to. <br><br> See U tomorrow";
$mails->newMessage("identifier", "test@test.com", $recipients, $bodyMessage);
$mails->setSubject("identifier", "This is a subject");

$files = ["path/to/file.pdf" => "filename.pdf",
		  "path/to/another/file.pdf" => "file.pdf"];
$mails->setAttachement("identifier", $files);

$mails->send(); //Sending all mails 
```

### SMS

```php
include_once 'CommunicationFactory.php';

$sms = CommunicationFactory::build("sms");
$recipients = [45xxxxxxxx, 45xxxxxxxx]; //45 is for Denmark
$bodyMessage = "Test of sms!";
$sms->newMessage("identifier", "45xxxxxxxx", $recipients, $bodyMessage);

$sms->send(); //Sending all sms 
```

## Dependencies
- guzzle-oauth-subscriber
- PHPMailer