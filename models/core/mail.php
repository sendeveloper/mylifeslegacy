<?php
class Mail
{
	var $api;
	
	var $body;
	var $subject;
	var $from;
	var $to;
	var $tags;
	var $attachment;
	
	function __construct()
	{
		//require_once('classes/class.mandrill.php');
		//$this->api = new Mandrill('lEbWklUPlOUY2vadBXZiKg');
	}
	
	public function setOptions($data)
	{
		$this->body = $data['body'];
		$this->subject = $data['subject'];
		$this->from = ($data['from'] != null) ? $data['from'] : array('name' => 'My Life\'s Legacy', 'email' => 'support@myllegacy.com');
		$this->to = $data['to'];
		$this->tags = isset($data['tags']) ? $data['tags'] : '';
		$this->attachment = (isset($data['attachment']) && ($data['attachment'] != null)) ? $data['attachment'] : null;
	}
	
	public function send()
	{
		if ($this->attachment == null)
		{
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=utf8' . "\r\n";
			$headers .= 'To: ' . $this->to[0]['name'] . ' <' . $this->to[0]['email'] . '>' . "\r\n";
			$headers .= 'From: ' . $this->from['name'] . ' <' . $this->from['email'] . '>' . "\r\n";

			$result = mail($this->to[0]['email'], $this->subject, $this->body, $headers);
		}
		else
		{
			$name        = $this->to[0]['name'];
			$email       = $this->to[0]['email'];
			$to          = "$name <$email>";
			$from        = $this->from['name'] . ' <' . $this->from['email'] . '>';
			$subject     = $this->subject;
			$message 	 = $this->body;
			$fileatt     = $this->attachment;
			$fileattname = "certification.pdf";
			$headers = "From: $from";
			$pdfdoc = file_get_contents($fileatt);
			

			$result = true;

	        $separator = md5(time());
	        $eol = PHP_EOL;
	        $filename = "certification.pdf";
	        $attachment = chunk_split(base64_encode($pdfdoc));
	        $headers = "From: " . $from . $eol;
	        $headers .= "MIME-Version: 1.0" . $eol;
			$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol . $eol;
			$body = "Content-Transfer-Encoding: 7bit" . $eol;
			$body .= "This is a MIME encoded message." . $eol; 
			    $body .= "--" . $separator . $eol;
			    $body .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
			    $body .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			    $body .= $message . $eol;
			    $body .= "--" . $separator . $eol;
			    $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
			    $body .= "Content-Transfer-Encoding: base64" . $eol;
			    $body .= "Content-Disposition: attachment" . $eol . $eol;
			    $body .= $attachment . $eol;
			    $body .= "--" . $separator . "--";
			$result = mail($to, $subject, $body, $headers);
		}

		return $result;
		
		$message = array(
			'html' => $this->body,
			'text' => strip_tags($this->body),
			'subject' => $this->subject,
			'from_email' => $this->from['email'],
			'from_name' => $this->from['name'],
			'to' => $this->to,
			'headers' => array('Reply-To' => $this->from['email']),
			'important' => false,
			'track_opens' => null,
			'track_clicks' => null,
			'auto_text' => null,
			'auto_html' => null,
			'inline_css' => null,
			'url_strip_qs' => null,
			'preserve_recipients' => null,
			'view_content_link' => null,
			'tracking_domain' => null,
			'signing_domain' => null,
			'return_path_domain' => null,
			'merge' => false,
			'tags' => $this->tags
		);
		
		if ($this->attachment != null) $message['attachments'] = array($this->attachment);
		
		return $this->api->messages->send($message, false, 'Main Pool', '');
	}
}
?>