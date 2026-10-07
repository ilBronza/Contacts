@php($href = $contact->getHref())
@if($href)
    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer">
@endif

@if($type = $contact->getContacttype())
    {!! $type->getIcon() !!}
@endif

{{ $contact->getContact() }}

@if($href)
    </a>
@endif
