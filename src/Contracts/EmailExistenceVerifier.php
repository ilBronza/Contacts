<?php

namespace IlBronza\Contacts\Contracts;

interface EmailExistenceVerifier
{
    /**
     * Return true for a verified mailbox, false for an invalid address, or null
     * when the provider cannot confirm it (including catch-all domains).
     * Throw on configuration, transport or provider errors.
     */
    public function verify(string $email): ?bool;
}
