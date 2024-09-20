@if($contact->getContacttype()?->hasHref())
    <a href="{{ $contact->getContacttype()->getHrefString() }}:{{ $contact->getContact() }}" target="_blank">
        @endif

        @if($contact->getContacttype())
            {!! $contact->getContacttype()->getIcon() !!}
        @endif

        {{ $contact->getContact() }}

        @if($contact->getContacttype()?->hasHref())
    </a>
@endif
