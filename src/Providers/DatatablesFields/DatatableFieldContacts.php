<?php

namespace IlBronza\Contacts\Providers\DatatablesFields;

use IlBronza\Datatables\DatatablesFields\DatatableField;

class DatatableFieldContacts extends DatatableField
{
	public ?string $tooltipClass = null;

	/**
	 * Renders a contact collection as clickable icons.
	 *
	 * The contact type controls both the icon and the URI scheme through its
	 * `fa_icon` and `href_string` attributes (for example `tel`, `mailto`, or
	 * `https`).
	 */
	public function transformValue($contacts)
	{
		if (! $contacts)
			return null;

		$result = [];

		foreach ($contacts as $contact)
		{
			$contacttype = $contact->getContacttype();
			$contactString = $contact->getContact();

			if (! $contacttype || ! $contactString)
				continue;

			$attributes = $this->getTooltipAttributes($contactString);
			$icon = preg_replace('/\s+title=(["\']).*?\1/i', '', $contacttype->getIcon());
			$icon = '<span ' . $attributes . '>' . $icon . '</span>';

			if ($contacttype->hasHref())
			{
				$href = $contacttype->getHrefString() . ':' . $contactString;

				$result[] = '<a href="' . e($href) . '" target="_blank">' . $icon . '</a>';
				continue;
			}

			$result[] = $icon;
		}

		return implode(' ', $result);
	}

	protected function getTooltipAttributes(string $contactString) : string
	{
		$tooltip = 'title: ' . e($contactString);

		if ($tooltipClass = trim((string) $this->tooltipClass))
			$tooltip .= '; cls: uk-active ' . e($tooltipClass);

		return 'uk-tooltip="' . $tooltip . '"';
	}
}
