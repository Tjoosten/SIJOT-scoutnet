<?php

namespace Sijot\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Class LeaseInfoAdmin
 *
 * @package Sijot\Mail
 */
class LeaseInfoAdmin extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var data
     */
    public $data;


    /**
     * Create a new message instance.
     *
     * @param  mixed $data The input data that the user has been given.
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('postmaster@st-joris-turnhout.be', 'Scouts en Gidsen - Sint-Joris')
            ->subject('Nieuwe verhurings aanvraag')
            ->markdown('lease.email.infoAdmin')
            ->with('data', $this->data);
    }
}
