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

Tutte le route sotto `/contact-management` richiedono `web`, `auth` e `contacts.roles`.
I ruoli predefiniti sono `administrator`, `superadmin`, `contacts`; si possono
configurare `defaultRoles` e `routeRoles`. Il prefisso dei nomi delle route è quello
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
- Non serve una nuova migrazione: la correzione riguarda il `down()` di quella esistente.
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
validazione, permessi HTTP, rendering e rollback delle migrazioni. Usa SQLite in
memoria e non usa `RefreshDatabase`, per compatibilità con l'ambiente php-wasm.

## Licenza

MIT. Vedere [license.md](license.md).
