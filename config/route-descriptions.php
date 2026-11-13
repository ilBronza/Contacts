<?php

$prefix = (string) config('contacts.routePrefix');

return [
    $prefix . '.email.verify' => 'Conferma l’indirizzo email del contatto tramite il collegamento di verifica.',
    $prefix . 'contacttypes.index' => 'Mostra l’elenco delle tipologie di contatto.',
    $prefix . 'contacttypes.create' => 'Mostra il modulo per creare una tipologia di contatto.',
    $prefix . 'contacttypes.store' => 'Salva una nuova tipologia di contatto.',
    $prefix . 'contacttypes.show' => 'Mostra i dettagli della tipologia di contatto.',
    $prefix . 'contacttypes.edit' => 'Mostra il modulo di modifica della tipologia di contatto.',
    $prefix . 'contacttypes.update' => 'Salva le modifiche della tipologia di contatto.',
    $prefix . 'contacttypes.destroy' => 'Elimina una tipologia di contatto.',
    $prefix . 'contacts.index' => 'Mostra l’elenco dei contatti.',
    $prefix . 'contacts.byModelFetched' => 'Mostra la tabella dei contatti associati al record selezionato.',
    $prefix . 'contacts.show' => 'Mostra i dettagli del contatto.',
    $prefix . 'contacts.edit' => 'Mostra il modulo di modifica del contatto.',
    $prefix . 'contacts.update' => 'Salva le modifiche del contatto.',
    $prefix . 'contacts.destroy' => 'Elimina un contatto.',
    $prefix . 'contacts.createBy' => 'Mostra il modulo per aggiungere un contatto al record selezionato.',
    $prefix . 'contacts.store' => 'Salva un nuovo contatto associato al record selezionato.',
];
