@if(count($contacts))
    <script type="text/javascript">
        $("table.{{ $key }} form").submit(function (event)
        {
            var that = this;
            event.preventDefault();
            var url = $(this).attr('action');

            var formData = {
                _method : $(that).find('input[name="_method"]').val()
            };

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                dataType: "json",
            }).done(function (response)
            {
                if(response.success == true)
                {
                    window.addSuccessNotification(response.message)

                    $(that).parents('.ibfetchercontainer').find('.refresh').click();
                }
            });

        });
    </script>

    <table class="uk-width-1-1 uk-table uk-table-small {{ $key }}">
        @foreach($contacts as $contact)
            <tr>
                <td class="uk-visible@l">
                    <a href="{{ $contact->getEditUrl() }}" uk-icon="file-edit"></a>
                </td>
                <td class="uk-visible@l">
                    @if($type = $contact->getType())
                        {!! $type->getIcon() !!}
                    @endif
                </td>
                <td>
                    @if($type?->hasHref())
                        <a href="{{ $type->getHrefString() }}:{{ $contact->getContact() }}" target="_blank">
                    @endif

                    {{ $contact->getContact() }}

                    @if($contact->getType()?->hasHref())
                        </a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    {!! IlBronza\Contacts\Helpers\ContactCreatorHelper::getCreateContactButtonByModel($model)->render() !!}

@endif