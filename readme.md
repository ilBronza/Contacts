# Contacts

Gestione di recapiti associati a modelli Eloquent tramite una relazione polimorfica.
Il package include tipi di contatto, CRUD, viste Blade, pulsanti di creazione e campi
Datatables per email, telefoni e collezioni di icone.

## Requisiti e installazione

PHP 8.2 o successivo e Laravel 11 o 12. I test locali sono stati eseguiti con
PHP 8.4, Laravel 11, PHPUnit 11 e SQLite in memoria; Laravel 12 e MySQL non sono
stati verificati in questa sessione.

Il package si integra con lo stack IlBronza dichiarato in `composer.json`.
CRUD richiede un controller `App\Http\Controllers\Controller` nell'applicazione
ospite. I package IlBronza utilizzano ancora versioni di sviluppo: nell'applicazione
che li installa occorre consentirle e mantenere un lock file con revisioni compatibili.

```bash
composer require ilbronza/contacts
php artisan vendor:publish --tag=contacts.config
php artisan migrate
```

Il service provider viene scoperto automaticamente. Migrazioni, traduzioni e viste
vengono caricate dal package. I nomi delle tabelle e le classi concrete sono
configurabili in `config/contacts.php`.

## Collegare un modello

```php
use IlBronza\Contacts\Models\Traits\InteractsWithContact;

class Customer extends Model
{
    use InteractsWithContact;
}
```

Per usare le route, aggiungere la classe a `contacts.associableModelTypesFullClass`.
I valori predefiniti sono User, Client e Operator dello stack IlBronza; Client e
Operator richiedono i rispettivi package opzionali. Le classi non installate vengono
ignorate. Per i modelli IlBronza viene rispettato `getProjectClassName()`.

È consigliato registrare un alias stabile:

```php
Relation::morphMap(['customer' => Customer::class]);
```

Le route accettano l'alias o la classe configurata e salvano il tipo polimorfico
canonico. Il modello deve esporre `contacts()` e autorizzare lettura e aggiornamento
con `userCanSee()` / `userCanUpdate()` oppure con policy Laravel `view` / `update`.

```php
$customer->addContact('Mario@EXAMPLE.COM', 'email');
$customer->addContact('+39 (02) 1234-5678', 'mobile');

$customer->getContacts();
$customer->getContactsByType('email');
$customer->getContactsByTypes(['email', 'mobile']);
$customer->getMobileString();
```

`addContact()` richiede un proprietario già salvato. Il secondo argomento è il **nome**
del tipo: se manca, viene creato con uno slug. Il contatto viene cercato soltanto
nella relazione del proprietario, per tipo e valore normalizzato. Lo stesso recapito
può appartenere a più soggetti. Una seconda chiamata equivalente sullo stesso soggetto
riusa la riga; i contatti eliminati logicamente non vengono ripristinati.
La collezione eventualmente caricata viene invalidata dopo il salvataggio.

La creazione del tipo e del contatto avviene in una transazione sulla connessione
del proprietario: proprietario, contatti e tipi devono usare la stessa connessione.
Non è introdotto un vincolo univoco sui dati esistenti; chiamate concorrenti possono
ancora creare duplicati, da gestire nell'importazione o nel flusso chiamante.

## Validazione e normalizzazione

Le impostazioni `contacts.contactTypes` sono indicizzate per **slug**, non per nome
tradotto. Il comportamento predefinito è:

| Tipo | Normalizzazione | Validazione |
| --- | --- | --- |
| `email` | Spazi esterni rimossi, dominio in minuscolo, parte locale preservata | Regola Laravel `email` |
| `mobile`, `phone` | Spazi, parentesi, punti e trattini rimossi; `x` / `ext.` convertiti in `X` | 7–15 cifre, `+` iniziale opzionale, interno di 1–10 cifre |
| Altri tipi | Spazi esterni rimossi | Stringa obbligatoria, massimo 191 caratteri |

Gli zeri iniziali sono preservati; non viene indovinato un prefisso nazionale.
La verifica del telefono controlla il formato, non l'esistenza o raggiungibilità.
Tutti i tipi mantengono il limite di 191 caratteri del form.

```php
'contactTypes' => [
    'email' => ['normalizer' => 'email', 'rules' => ['email']],
    'telefono-ufficio' => [
        'normalizer' => 'phone',
        'rules' => ['regex:/\A\+?[0-9]{7,15}\z/'],
    ],
    'riferimento' => ['normalizer' => 'trim', 'rules' => ['max:80']],
],
```

La validazione produce una `ValidationException` Laravel sul campo `contact`.
Si applica ai nuovi modelli e quando cambiano recapito o tipo, anche con `saveQuietly()`.
Le modifiche a campi diversi non riscrivono o invalidano vecchi recapiti.
Gli aggiornamenti SQL diretti e le scritture massive tramite query builder non
passano dal modello e richiedono una validazione nel chiamante.

Helper disponibili:

```php
Contacts::extractEmails('Scrivi a nome@mail.example.technology');
Contacts::isValidEmail('nome@example.com');
Contacts::extractPhoneNumber('02 1234 5678 ext. 42'); // 0212345678X42
Contacts::normalizeAndValidateContact(' Nome@EXAMPLE.COM ', 'email');
```

L'estrattore email cerca candidati non racchiusi in doppi apici, supporta sottodomini,
TLD lunghi e tag `+`, e scarta i candidati non validi senza troncarne il dominio.

### Verifica della singola casella email con Verifalia

`ContactEmailVerificationHelper` verifica la singola casella tramite Verifalia.
Il controllo del solo formato o del DNS non imposta `existence_verified` a `true`.
Sono supportati il tipo `email` e quelli configurati con `normalizer => 'email'`,
ad esempio `pec`; altri tipi o indirizzi sintatticamente non validi producono
`false` senza contattare il servizio.

Configurare nell'applicazione ospite le credenziali di un utente API Verifalia:

```dotenv
CONTACTS_VERIFALIA_USERNAME=nome_utente_api
CONTACTS_VERIFALIA_PASSWORD=password_utente_api
```

Le credenziali sono lette da `contacts.emailVerification.verifalia`. Se la
configurazione del package è già pubblicata, aggiungervi questa sezione da
`config/contacts.php`; rigenerare l'eventuale cache di configurazione Laravel.
Il provider usa l'API v2.7 via HTTPS con autenticazione Basic e qualità `Standard`.
Il piano gratuito include 25 crediti giornalieri, non cumulabili; una verifica
standard usa un credito. Sono necessari un account e le credenziali API, che non
vanno inserite nel repository. Riferimenti:
[configurazione API](https://verifalia.com/developers/authentication) e
[crediti gratuiti](https://verifalia.com/help/billing-pricing/how-do-daily-free-credits-work).

```php
use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;

$contact = $customer->addContact('Mario@example.com', 'email');
$result = app(ContactEmailVerificationHelper::class)->verify($contact);

// true: casella confermata, existence_verified = true.
// false: indirizzo non valido, existence_verified = false.
// null: controllo in corso o esito incerto, existence_verified = false.
```

Con questa modalità il flag diventa `true` solo per l'esito Verifalia `Success` / `Deliverable` della
stessa email inviata. Un dominio che accetta qualsiasi destinatario (catch-all),
greylisting, timeout della verifica, classificazioni personalizzate e codici
sconosciuti non confermano la casella e producono `null`. La classificazione
`Deliverable` da sola non basta, perché può essere personalizzata sul provider.
Il risultato è la valutazione del servizio, non una conferma del possesso della
casella da parte del contatto. Questa modalità non invia email di conferma;
per la conferma tramite link usare la modalità descritta di seguito.

Verifalia può completare il controllo subito oppure lasciarlo in lavorazione.
Ogni chiamata attende fino a 30 secondi lato API, con timeout HTTP di 35 secondi.
Quando il lavoro è ancora in corso, il suo ID viene conservato nella cache Laravel
per 24 ore: richiamando `verify()` sulla stessa email si recupera quel lavoro con
una GET, senza inviare nuovamente la casella. Utilizzare una cache persistente
con supporto ai lock, condivisa tra i worker. I risultati completati non vengono
messi in cache: una verifica successiva avvia un nuovo controllo e usa un credito.
Un job dell'applicazione ospite può richiamare l'helper per recuperare un lavoro
ancora in corso; il package non configura invii, code o pianificazioni automatiche.

`isValid($contact)` esegue lo stesso controllo senza scrivere sul modello e
funziona anche su contatti non salvati; può comunque consumare un credito.
`verify()` richiede un contatto persistito senza modifiche pendenti a recapito o
tipo. Aggiorna solo il flag e il timestamp, senza salvare altri campi pendenti né
emettere eventi Eloquent. Se il contatto cambia o viene eliminato durante la
richiesta, il risultato non viene applicato e viene sollevata una `RuntimeException`.

Credenziali mancanti producono una `LogicException`; errori HTTP, quota esaurita,
problemi di connessione e risposte malformate producono una `RuntimeException`,
senza modificare la verifica precedente. Non vengono effettuati retry HTTP
automatici; un errore durante il recupero conserva l'ID del lavoro per un nuovo
tentativo. Se una POST fallisce prima di restituire l'ID, non è possibile sapere
se il servizio l'abbia già accettata: consultare Verifalia prima di reinviarla.

La colonna `existence_verified` è booleana, non nullable e inizializzata a `false`,
anche sulle righe esistenti. Cambiare recapito normalizzato o tipo azzera il flag,
anche con `saveQuietly()`; modifiche non pertinenti o un indirizzo equivalente dopo
la normalizzazione conservano la verifica. Gli aggiornamenti SQL diretti devono
azzerare il flag nel chiamante. Il salvataggio ordinario continua a validare il
formato senza chiamare servizi esterni.

Per un altro provider, implementare `IlBronza\Contacts\Contracts\EmailExistenceVerifier`
e sostituirne il binding nel service provider dell'applicazione. I test simulano
le risposte HTTP: non inviano contatti reali e non consumano crediti.

### Conferma diretta tramite email

In alternativa a Verifalia, è possibile inviare un'email al contatto e considerare
verificato l'indirizzo quando viene aperto il link. Questa modalità usa il mailer
Laravel dell'applicazione e non richiede credenziali Verifalia.

```php
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Helpers\ContactEmailVerificationHelper;

$contact = Contact::findOrFail(123);
app(ContactEmailVerificationHelper::class)->sendVerificationEmail($contact);
```

Il messaggio standard contiene il link, un testo in italiano o inglese secondo il
locale dell'applicazione, e la durata della validità. L'invio è sincrono: un errore
del mailer viene propagato al chiamante. Per inviare dalla coda, richiamare il metodo
in un job dell'applicazione. L'invio o la sola generazione del link non modificano
`existence_verified`; il click imposta il campo a `true` e consuma il token.

Per inserire il link nel **vostro testo email**, generarlo senza inviare il messaggio
standard e passarlo alla vostra mail:

```php
$verificationUrl = app(ContactEmailVerificationHelper::class)
    ->createVerificationUrl($contact);

// Passare $verificationUrl alla propria Mailable o vista email.
```

Nel vostro template Blade:

```blade
<a href="{{ $verificationUrl }}">Conferma il tuo indirizzo email</a>
```

Il template standard è `contacts::emails.verify-contact` (HTML) e
`contacts::emails.verify-contact-text` (testo semplice). Per personalizzarlo
nell'applicazione, creare gli override sotto
`resources/views/vendor/contacts/emails/`. Entrambe le viste ricevono
`$verificationUrl` e `$expiresInDays`.

Il link è firmato, monouso e valido per **7 giorni** per impostazione predefinita.
La durata è configurabile in `contacts.emailVerification.email.expires_days`,
ad esempio `14` per due settimane. Generare un nuovo link revoca quello precedente;
cambiare recapito o tipo tramite modello revoca i link pendenti anche con
`saveQuietly()`. Il token memorizzato è soltanto un hash, nascosto dalla
serializzazione del modello e legato all'indirizzo e al tipo esatti.

Il destinatario non deve avere un account: la route pubblica
`GET /contact-management/email-verification/{contact}/{token}` verifica firma,
scadenza e token, quindi mostra la conferma. Link scaduti, alterati, sostituiti o
già usati restituiscono 403; un contatto eliminato restituisce 404. Le richieste
HEAD non consumano il token. La pagina di conferma si può personalizzare tramite
`resources/views/vendor/contacts/contacts/email-verified.blade.php`.

Configurare `APP_URL` con l'URL pubblico corretto e il mailer Laravel (`MAIL_*`),
eseguire `php artisan migrate` per aggiungere `email_verification_token_hash`,
e aggiornare l'eventuale configurazione pubblicata e la cache di configurazione.
L'invio è disponibile soltanto come helper: autorizzazioni e limiti di invio
vanno applicati dal chiamante, come per le altre operazioni dirette sui contatti.
Gli aggiornamenti SQL diretti devono azzerare sia `existence_verified` sia
`email_verification_token_hash`. I test usano mail simulate e non inviano messaggi reali.

## Tipi, icone e link

`Contacttype` usa `slug` come chiave primaria. I campi aggiuntivi sono:

- `fa_icon`: suffisso Font Awesome, ad esempio `envelope` o `mobile-screen`;
- `href_string`: protocollo, ad esempio `mailto`, `tel` o `https`.

I protocolli predefiniti consentiti sono `mailto`, `tel`, `sms`, `http`, `https`;
la lista è configurabile con `contacts.allowedHrefSchemes`. Aggiungere solo
protocolli di applicazioni attendibili. I valori non consentiti restano testo.

`$contact->getHref()` è il punto unico per generare il link. Gestisce domini web con
o senza schema e non concatena un secondo `https:` a URL già completi. I link email
codificano i caratteri riservati; i telefoni convertono l'interno in `;ext=`.
Le icone e i tooltip effettuano escaping dei valori. Un'icona malformata usa il
fallback `address-card`. Un tipo nullo o eliminato viene gestito senza errori.

Le viste del package e `DatatableFieldContacts` usano questa stessa logica.
I link aperti in una nuova scheda includono `rel="noopener noreferrer"`.

```blade
@include('contacts::contacts._contact', ['contact' => $contact])
@include('contacts::contacts._fetcherModelContacts', ['model' => $customer])
```

Per tabelle numerose precaricare `contacts.contacttype` per evitare query ripetute.
L'indice dei tipi carica anche `contacts_count`.

## Route e permessi

Le route di gestione sotto `/contact-management` richiedono `web`, `auth` e `contacts.roles`.
I ruoli predefiniti sono `administrator`, `superadmin`, `contacts`; si possono
configurare `defaultRoles` e `routeRoles`. Fa eccezione la conferma email pubblica, protetta
da firma, scadenza, token monouso e limite di richieste. Il prefisso dei nomi delle route è quello
esistente (`contacts`), senza aggiungere un punto durante l'aggiornamento.

| Operazione | Route |
| --- | --- |
| Elenco contatti | `GET /contact-management/contacts` |
| Contatti del proprietario | `GET /contact-management/contacts/by-model/{model}/{id}` |
| Creazione per proprietario | `GET /contact-management/contacts/create-by/{model}/{key}` |
| Salvataggio per proprietario | `POST /contact-management/contacts/store-by/{model}/{key}` |
| Tipi di contatto | CRUD sotto `/contact-management/types` |

La lettura per proprietario controlla la visibilità. Creazione e salvataggio
controllano sia visibilità sia diritto di aggiornamento del proprietario.
Modelli non configurati e record mancanti restituiscono 404; accessi negati 403.
Le operazioni dirette sui modelli, come `addContact()`, richiedono autorizzazione
nel servizio chiamante, come gli altri metodi Eloquent.

## Aggiornamento di un'installazione esistente

- Integrare nella configurazione pubblicata `allowedHrefSchemes`, `contactTypes`
  e tutte le classi effettivamente utilizzate in `associableModelTypesFullClass`.
- Verificare che chi crea contatti abbia un ruolo consentito e possa aggiornare
  il proprietario; le due route di creazione ora applicano entrambi i controlli.
- Correggere eventuali icone o protocolli non validi; il rendering li tratta in modo sicuro.
- Eseguire `php artisan migrate` per aggiungere `existence_verified` e
  `email_verification_token_hash` alla tabella contatti configurata. Le righe esistenti partono da `false`; nessuna verifica esterna
  viene eseguita dalla migrazione.
- I recapiti precedentemente assegnati al proprietario sbagliato non vengono
  ricostruiti automaticamente: manca lo storico per stabilire l'associazione originale.

## Test

Con un vendor locale già predisposto:

```bash
composer test
```

Nel workspace dei package, con il vendor condiviso già disponibile:

```bash
php ../.ibtest/vendor/phpunit/phpunit/phpunit -c phpunit.xml
```

`tests/bootstrap.php` usa il vendor locale oppure quello condiviso e registra le
working copy dei package. Manifest e viste compilate dei test vengono scritti
nella directory temporanea. Non occorre modificare il vendor condiviso.

La suite verifica persistenza, isolamento dei proprietari, normalizzazione,
validazione, verifica della casella con API simulata, conferma tramite email,
scadenza e revoca dei link, invalidazione del flag, permessi HTTP, rendering e
rollback delle migrazioni. Usa SQLite in
memoria e non usa `RefreshDatabase`, per compatibilità con l'ambiente php-wasm.

## Licenza

MIT. Vedere [license.md](license.md).
