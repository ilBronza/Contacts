# Contribuire a Contacts

Ogni modifica al comportamento deve includere test di regressione e aggiornare
`readme.md` e `changelog.md` quando cambia l'API o la configurazione.

Usare `composer test` con un vendor locale già configurato, oppure
`php ../.ibtest/vendor/phpunit/phpunit/phpunit -c phpunit.xml` nel workspace condiviso.
Le istruzioni locali di `AGENTS.md` hanno precedenza per la preparazione dell'ambiente.

Nei test usare un database in memoria e modelli fittizi. Non usare dati reali né
`RefreshDatabase` nell'ambiente php-wasm. Conservare i test su proprietari diversi
con identico recapito, permessi negati e rendering di contenuti non attendibili.

Le dipendenze IlBronza sono condivise tra più package: limitare le modifiche a
Contacts, segnalare le incompatibilità esterne e mantenere compatibili i nomi delle
route e gli override dei modelli dell'applicazione ospite.
