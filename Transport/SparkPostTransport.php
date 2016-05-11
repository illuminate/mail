<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class SparkPostTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @return void
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->client = $client;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $recipients = $this->getRecipients($message);

        $message->setBcc([]);

        $options = [
            'headers' => [
                'Authorization' => $this->key,
            ],
            'json' => [
                'recipients' => $recipients,
                'content' => [
                    'html' => $message->getBody(),
                    'from' => $this->getFrom($message),
                    'reply_to' => $this->getReplyTo($message),
                    'subject' => $message->getSubject(),
                ],
            ],
            'connect_timeout' => 60,
        ];

        $this->client->post('https://api.sparkpost.com/api/v1/transmissions', $options);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * Note that SparkPost still respects CC, BCC headers in raw message itself.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getRecipients(Swift_Mime_Message $message)
    {
        $to = [];

        if ($message->getTo()) {
            $to = array_merge($to, array_keys($message->getTo()));
        }

        if ($message->getCc()) {
            $to = array_merge($to, array_keys($message->getCc()));
        }

        if ($message->getBcc()) {
            $to = array_merge($to, array_keys($message->getBcc()));
        }

        $recipients = array_map(function ($address) {
            return ['address' => ['email' => $address, 'header_to' => $address]];
        }, $to);

        return $recipients;
    }

    /**
     * Get the "from" contacts in the format required by SparkPost.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getFrom(Swift_Mime_Message $message)
    {
        return array_map(function ($email, $name) {
            return compact('name', 'email');
        }, array_keys($message->getFrom()), $message->getFrom())[0];
    }

    /**
     * Get the 'reply_to' headers and format as required by SparkPost.
     *
     * @param  Swift_Mime_Message  $message
     * @return string
     */
    protected function getReplyTo(Swift_Mime_Message $message)
    {
        if (is_array($message->getReplyTo())) {
            return current($message->getReplyTo()).' <'.key($message->getReplyTo()).'>';
        }
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }
}
