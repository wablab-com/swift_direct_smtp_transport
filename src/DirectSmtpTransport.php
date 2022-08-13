<?php

namespace WabLab\Swiftmailer;

use Swift_Mime_SimpleMessage;

class DirectSmtpTransport extends \Swift_Transport_EsmtpTransport
{

    protected array $buffersByDomain = [];
    protected ?string $currentBufferDomain = null;

    public function __construct()
    {
        $this->eventDispatcher = new \Swift_Events_SimpleEventDispatcher();
        $this->addressEncoder = new \Swift_AddressEncoder_IdnAddressEncoder();
    }

    public function start()
    {
        $this->started = true;
    }

    protected function startByRecipientDomain($domain)
    {
        if (!isset($this->buffersByDomain[$domain])) {
            $this->buffer = $this->getBufferByDomain($domain);

            if ($evt = $this->eventDispatcher->createTransportChangeEvent($this)) {
                $this->eventDispatcher->dispatchEvent($evt, 'beforeTransportStarted');
                if ($evt->bubbleCancelled()) {
                    return;
                }
            }

            try {
                $this->buffer->initialize($this->getBufferParamsByDomain($domain));
            } catch (\Swift_TransportException $e) {
                $this->throwException($e);
            }
            $this->readGreeting();
            $this->doHeloCommand();

            if ($evt) {
                $this->eventDispatcher->dispatchEvent($evt, 'transportStarted');
            }

        }
    }

    protected function getBufferByDomain($domain)
    {
        if(!isset($this->buffersByDomain[$domain])) {
            $replacementFilterFactory = new \Swift_StreamFilters_StringReplacementFilterFactory();
            $buffer = new \Swift_Transport_StreamBuffer($replacementFilterFactory);
            $this->buffersByDomain[$domain] = $buffer;
        }
        return $this->buffersByDomain[$domain];
    }

    protected function getBufferParamsByDomain($domain)
    {
        $params = $this->getBufferParams();
        $params['host'] = $this->pickOneMxByDomain($domain);
        return $params;
    }

    protected function pickOneMxByDomain($domain)
    {
        $mxRecords = [];
        if(getmxrr($domain, $mxRecords)) {
            return $mxRecords[rand(0, count($mxRecords) - 1)];
        }

        throw new \Exception("No MX record assigned to domain '{$domain}'");
    }

    protected function getRecipientsDomains($recipients)
    {
        $domains = [];
        foreach ($recipients as $recipient) {
            $atPos = strpos($recipient, '@');
            if($atPos !== false) {
                $domain = strtolower(trim(substr($recipient, $atPos+1)));
                $domains[$domain] = null;
            }
        }
        return array_keys($domains);
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $to = (array) $message->getTo();
        $cc = (array) $message->getCc();
        $bcc = (array) $message->getBcc();
        $tos = array_merge($to, $cc, $bcc);

        $recipientDomains = $this->getRecipientsDomains(array_keys($tos));

        $sentCount = 0;
        foreach ($recipientDomains as $domain) {
            $this->currentBufferDomain = $domain;
            $this->startByRecipientDomain($domain);
            $sentCount += parent::send($message, $failedRecipients);
        }
        return $sentCount;
    }

    protected function doRcptToCommand($address)
    {
        if($this->currentBufferDomain && strpos(strtolower($address), $this->currentBufferDomain) !== false) {
            parent::doRcptToCommand($address);
        }
    }

}