<?php

namespace IlBronza\Contacts\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\CRUDSluggableTrait;
use IlBronza\CRUD\Traits\Model\PackagedModelsTrait;
use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Contacts;

class Contacttype extends BaseModel
{
	public $incrementing = false;
	protected $keyType = 'string';
	protected $primaryKey = 'slug';

	protected $fillable = [
		'name'
	];

	use PackagedModelsTrait;

	use CRUDSluggableTrait;

	static $packageConfigPrefix = 'contacts';
	static $modelConfigPrefix = 'contacttype';
    
    public function contacts()
    {
    	return $this->hasMany(Contact::getProjectClassName());
    }

    public function getRelatedContacts()
    {
    	return $this->contacts()->with('contactable', 'contacttype')->get();
    }

	public function getHrefString() : ? string
	{
		return $this->href_string;
	}

	public function getIcon() :  ? string
	{
        $icon = preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/i', (string) $this->fa_icon)
            ? $this->fa_icon
            : 'address-card';

        return '<i title="' . e($this->getName()) . '" class="fa-solid fa-' . e($icon) . '"></i>';
	}

    public function getHrefScheme() : ?string
    {
        $scheme = strtolower(rtrim(trim((string) $this->getHrefString()), ':'));

        if (! preg_match('/\A[a-z][a-z0-9+.-]*\z/', $scheme))
            return null;

        return in_array($scheme, config('contacts.allowedHrefSchemes', ['mailto', 'tel', 'sms', 'http', 'https']), true)
            ? $scheme
            : null;
    }

    public function getHrefForContact(string $contact) : ?string
    {
        $scheme = $this->getHrefScheme();
        $contact = trim($contact);

        if (! $scheme || $contact === '' || preg_match('/[\x00-\x1F\x7F]/', $contact))
            return null;

        if ($scheme === 'mailto') {
            if (! Contacts::isValidEmail($contact))
                return null;

            return 'mailto:' . str_replace('%40', '@', rawurlencode($contact));
        }

        if (in_array($scheme, ['tel', 'sms'], true)) {
            $phone = Contacts::extractPhoneNumber($contact);

            return $phone === null ? null : $scheme . ':' . str_replace('X', ';ext=', $phone);
        }

        if (in_array($scheme, ['http', 'https'], true)) {
            $url = preg_match('/\A[a-z][a-z0-9+.-]*:/i', $contact)
                ? $contact
                : $scheme . '://' . ltrim($contact, '/');
            $urlScheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

            if (! in_array($urlScheme, ['http', 'https'], true)
                || ! in_array($urlScheme, config('contacts.allowedHrefSchemes', ['mailto', 'tel', 'sms', 'http', 'https']), true)
                || ! filter_var($url, FILTER_VALIDATE_URL))
                return null;

            return $url;
        }

        return $scheme . ':' . $contact;
    }

	public function hasHref() : bool
	{
		return $this->getHrefScheme() !== null;
	}
}
