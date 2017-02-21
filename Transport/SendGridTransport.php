<?php
namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;
use Swift_MimePart;

class SendGridTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SendGrid API key.
     *
     * @var string
     */
    private $key;

    /**
     * Create a new Mandrill transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface $client
     * @param  string $key
     * @return void
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->key = $key;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->client->post('https://api.sendgrid.com/v3/mail/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/json',
            ],
            'json'    => [
                'personalizations' => $this->getPersonalizations($message),
                'from'             => $this->getFrom($message),
                'subject'          => $message->getSubject(),
                'content'          => $this->getContents($message),
            ],
        ]);

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getPersonalizations(Swift_Mime_Message $message)
    {
        $setter = function (array $addresses) {
            $recipients = [];
            foreach ($addresses as $email => $name) {
                $address = [];
                $address['email'] = $email;
                if ($name) {
                    $address['name'] = $name;
                }
                $recipients[] = $address;
            }
            return $recipients;
        };

        $personalization['to'] = $setter($message->getTo());

        if ($cc = $message->getCc()) {
            $personalization['cc'] = $setter($cc);
        }

        if ($bcc = $message->getBcc()) {
            $personalization['bcc'] = $setter($bcc);
        }

        return [$personalization];
    }

    /**
     * Get From Addresses.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getFrom(Swift_Mime_Message $message)
    {
        if ($message->getFrom()) {
            foreach ($message->getFrom() as $email => $name) {
                return ['email' => $email, 'name' => $name];
            }
        }
        return [];
    }

    /**
     * Get contents.
     *
     * @param Swift_Mime_Message $message
     * @return array
     */
    private function getContents(Swift_Mime_Message $message)
    {
        $content = [];
        foreach ($message->getChildren() as $attachment) {
            if ($attachment instanceof Swift_MimePart) {
                $content[] = [
                    'type'  => 'text/plain',
                    'value' => $attachment->getBody(),
                ];
                break;
            }
        }

        if (empty($content) || strpos($message->getContentType(), 'multipart') !== false) {
            $content[] = [
                'type'  => 'text/html',
                'value' => $message->getBody(),
            ];
        }
        return $content;
    }
}